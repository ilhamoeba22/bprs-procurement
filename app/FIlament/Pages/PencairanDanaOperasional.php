<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\User;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Tables\Table;
use App\Models\SurveiHarga;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Concerns\InteractsWithTable;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\URL;

class PencairanDanaOperasional extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static string $view = 'filament.pages.pencairan-dana-operasional';
    protected static ?string $navigationLabel = 'Pencairan Dana';
    protected static ?int $navigationSort = 12;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Pencairan Dana)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Tim Budgeting', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas']);

        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->whereIn('status', [Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA, Pengajuan::STATUS_SUDAH_BAYAR])
                    ->orWhere('disbursed_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', [Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA, Pengajuan::STATUS_SUDAH_BAYAR])
                ->orWhereNotNull('disbursed_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Tiket Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('total_nilai')->label('Nilai Pengajuan')->money('IDR'),
            BadgeColumn::make('status')->label('Status Saat Ini')->color('info'),
            BadgeColumn::make('tindakan_saya')
                ->label('Tindakan Saya')
                ->state(function (Pengajuan $record): string {
                    $surveiHarga = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    if (!$surveiHarga || (!$surveiHarga->bukti_dp && !$surveiHarga->bukti_pelunasan)) {
                        return 'Menunggu Aksi';
                    }
                    return $surveiHarga->bukti_pelunasan ? 'Sudah Bayar' : 'Sudah DP';
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Bayar' => 'success',
                    'Sudah DP' => 'info',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading('')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $record->load(['items', 'items.surveiHargas', 'pemohon.divisi']);
                    $data = $record->toArray();
                    $data['estimasi_pengadaan'] = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $data['estimasi_perbaikan'] = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $data['nama_divisi'] = $record->pemohon->divisi->nama_divisi ?? 'Tidak tersedia';
                    $data['nama_barang'] = $record->items->pluck('nama_barang')->implode(', ') ?: 'Tidak tersedia';
                    $data['catatan_revisi'] = $record->catatan_revisi ?? 'Tidak ada riwayat catatan approval.';
                    $form->fill($data);
                })
                ->form([
                    Section::make('Detail Pengajuan')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kode_pengajuan')->disabled(),
                                TextInput::make('status')->disabled(),
                                TextInput::make('total_nilai')->label('Total Nilai')->disabled(),
                                TextInput::make('nama_divisi')->label('Divisi')->disabled(),
                                TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
                            ]),
                            Repeater::make('items')->relationship()->label('')->schema([
                                Grid::make(6)->schema([
                                    TextInput::make('kategori_barang')->disabled()->columnSpan(2),
                                    TextInput::make('nama_barang')->disabled()->columnSpan(3),
                                    TextInput::make('kuantitas')->disabled()->columnSpan(1),
                                ]),
                                Grid::make(2)->schema([
                                    Textarea::make('spesifikasi')->disabled(),
                                    Textarea::make('justifikasi')->disabled(),
                                ]),
                            ])->columns(2)->disabled()->addActionLabel('Tambah Barang'),
                        ]),
                    Section::make('Vendor Harga Final yang Di-approve')
                        ->schema(function (Pengajuan $record) {
                            Log::debug('Processing Vendor Harga Final for pengajuan ID: ' . $record->id_pengajuan);

                            $firstItem = $record->items->first();
                            if (!$firstItem) {
                                return [
                                    Placeholder::make('no_item_placeholder')
                                        ->content('Tidak ada item terkait untuk pengajuan ini.')
                                        ->columnSpanFull(),
                                ];
                            }

                            $surveiHarga = SurveiHarga::where('id_item', $firstItem->id_item)
                                ->where('is_final', 1)
                                ->first();

                            if (!$surveiHarga) {
                                return [
                                    Placeholder::make('no_final_vendor_placeholder')
                                        ->content('Tidak ada data vendor final untuk id_item = ' . $firstItem->id_item . '.')
                                        ->columnSpanFull(),
                                ];
                            }

                            $data = [
                                'nama_barang' => $surveiHarga->item->nama_barang ?? 'N/A',
                                'nama_vendor' => $surveiHarga->nama_vendor ?? 'N/A',
                                'harga' => 'Rp ' . number_format($surveiHarga->harga ?? 0, 0, ',', '.'),
                                'metode_pembayaran' => $surveiHarga->metode_pembayaran ?? 'N/A',
                                'opsi_pembayaran' => $surveiHarga->opsi_pembayaran ?? 'N/A',
                            ];

                            $content = '<table style="width: 100%; border-collapse: collapse; margin: 10px 0; color: #333; background-color: #fff;">'
                                . '<thead><tr>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; font-weight: bold;">Nama Barang</th>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; font-weight: bold;">Nama Vendor</th>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; font-weight: bold;">Harga</th>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; font-weight: bold;">Metode Pembayaran</th>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; font-weight: bold;">Opsi Pembayaran</th>'
                                . '</tr></thead>'
                                . '<tbody><tr>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['nama_barang']) . '</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['nama_vendor']) . '</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['harga']) . ' <span style="color: #888; font-size: 15px;">/ Item</span></td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['metode_pembayaran']) . '</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['opsi_pembayaran']) . '</td>'
                                . '</tr></tbody>'
                                . '</table>';

                            return [
                                Placeholder::make('final_vendor_data')
                                    ->content(new HtmlString($content))
                                    ->columnSpanFull(),
                            ];
                        })
                        ->visible(fn(Pengajuan $record) => in_array($record->status, [
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                        ]))
                        ->columnSpanFull(),
                ]),
            Action::make('payment')
                ->label('Pembayaran')
                ->color('success')
                ->icon('heroicon-o-currency-dollar')
                ->modalHeading(function (Pengajuan $record) {
                    $record->load('items.vendorFinal');
                    Log::debug('Modal Heading Debug for pengajuan ID: ' . $record->id_pengajuan, [
                        'items' => $record->items->map(fn($item) => [
                            'id_item' => $item->id_item,
                            'opsi_pembayaran' => $item->vendorFinal?->opsi_pembayaran ?? 'N/A',
                            'bukti_dp' => $item->vendorFinal?->bukti_dp ? 'Yes' : 'No',
                            'bukti_pelunasan' => $item->vendorFinal?->bukti_pelunasan ? 'Yes' : 'No',
                        ])->all(),
                    ]);

                    $allPaid = $record->items->every(fn($item) => $item->vendorFinal?->bukti_pelunasan);
                    if ($allPaid) {
                        return 'Pembayaran Selesai';
                    }

                    $hasDpOption = $record->items->some(fn($item) => strtolower(trim($item->vendorFinal?->opsi_pembayaran ?? '')) === 'bisa dp');
                    $anyDpPaid = $record->items->some(fn($item) => $item->vendorFinal?->bukti_dp);
                    $anyPaid = $record->items->some(fn($item) => $item->vendorFinal?->bukti_pelunasan);

                    if ($hasDpOption && !$anyDpPaid && !$anyPaid) {
                        return 'Pembayaran Down Payment (DP)';
                    }
                    if ($hasDpOption && $anyDpPaid && !$anyPaid) {
                        return 'Pelunasan Pembayaran';
                    }
                    $allLunasOption = $record->items->every(fn($item) => strtolower(trim($item->vendorFinal?->opsi_pembayaran ?? '')) === 'langsung lunas');
                    if ($allLunasOption && !$anyPaid) {
                        return 'Pembayaran Lunas';
                    }
                    return 'Pembayaran Selesai'; // Fallback jika data tidak sesuai
                })
                ->modalWidth('sm')
                ->form(function (Pengajuan $record) {
                    $record->load('items.vendorFinal');
                    $totalHarga = $record->items->sum(function ($item) {
                        return $item->vendorFinal ? ($item->vendorFinal->harga ?? 0) * $item->kuantitas : 0;
                    });
                    $allPaid = $record->items->every(fn($item) => $item->vendorFinal?->bukti_pelunasan);
                    $anyDpPaid = $record->items->some(fn($item) => $item->vendorFinal?->bukti_dp);
                    $anyPaid = $record->items->some(fn($item) => $item->vendorFinal?->bukti_pelunasan);
                    $totalDp = $record->items->sum(function ($item) {
                        return $item->vendorFinal ? ($item->vendorFinal->nominal_dp ?? 0) * $item->kuantitas : 0;
                    });

                    if (!$totalHarga) {
                        return [
                            TextInput::make('no_final_vendor')
                                ->label('Peringatan')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('Data survei harga final tidak ditemukan. Hubungi administrator.')
                                ->columnSpanFull(),
                        ];
                    }

                    $rekeningDetails = [];
                    foreach ($record->items as $item) {
                        if ($item->vendorFinal?->metode_pembayaran === 'Transfer') {
                            $rekeningDetails[] = [
                                'nama_bank' => $item->vendorFinal->nama_bank ?? 'N/A',
                                'no_rekening' => $item->vendorFinal->no_rekening ?? 'N/A',
                                'nama_rekening' => $item->vendorFinal->nama_rekening ?? 'N/A',
                            ];
                        }
                    }
                    $rekeningInfo = !empty($rekeningDetails) ? $rekeningDetails[0] : ['nama_bank' => 'N/A', 'no_rekening' => 'N/A', 'nama_rekening' => 'N/A'];

                    $hasDpOption = $record->items->some(fn($item) => strtolower(trim($item->vendorFinal?->opsi_pembayaran ?? '')) === 'bisa dp');
                    $allLunasOption = $record->items->every(fn($item) => strtolower(trim($item->vendorFinal?->opsi_pembayaran ?? '')) === 'langsung lunas');

                    Log::debug('Form Debug for pengajuan ID: ' . $record->id_pengajuan, [
                        'hasDpOption' => $hasDpOption,
                        'allLunasOption' => $allLunasOption,
                        'anyDpPaid' => $anyDpPaid,
                        'anyPaid' => $anyPaid,
                    ]);

                    return [
                        Grid::make(2)->schema([
                            TextInput::make('total_harga')
                                ->label('Total Harga')
                                ->disabled()
                                ->dehydrated(false)
                                ->default('Rp ' . number_format($totalHarga, 0, ',', '.')),
                            TextInput::make('total_dp')
                                ->label('Total DP')
                                ->disabled()
                                ->dehydrated(false)
                                ->default($totalDp > 0 ? 'Rp ' . number_format($totalDp, 0, ',', '.') : 'N/A')
                                ->visible($hasDpOption && $totalDp > 0),
                            TextInput::make('status_pembayaran')
                                ->label('Status Pembayaran')
                                ->disabled()
                                ->dehydrated(false)
                                ->default($allPaid ? 'Lunas' : ($anyDpPaid ? 'DP Dibayar' : 'Belum Dibayar')),
                            TextInput::make('nama_bank')
                                ->label('Bank')
                                ->disabled()
                                ->dehydrated(false)
                                ->default($rekeningInfo['nama_bank']),
                            TextInput::make('no_rekening')
                                ->label('No. Rekening')
                                ->disabled()
                                ->dehydrated(false)
                                ->default($rekeningInfo['no_rekening']),
                            TextInput::make('nama_rekening')
                                ->label('a.n.')
                                ->disabled()
                                ->dehydrated(false)
                                ->default($rekeningInfo['nama_rekening']),
                        ])->columns(2),

                        FileUpload::make('bukti_dp')
                            ->label('Upload Bukti DP')
                            ->helperText(function () use ($record) {
                                $dpItems = $record->items->filter(fn($item) => $item->vendorFinal?->nominal_dp > 0);
                                if ($dpItems->isEmpty()) return 'Tidak ada DP yang ditentukan.';
                                $dpValues = $dpItems->map(fn($item) => 'Rp ' . number_format($item->vendorFinal->nominal_dp ?? 0, 0, ',', '.'))
                                    ->implode(', ');
                                return 'Nominal DP per item: ' . $dpValues;
                            })
                            ->directory('bukti-pembayaran')->visibility('private')->required()
                            ->visible(function () use ($hasDpOption, $anyDpPaid, $anyPaid) {
                                return $hasDpOption && !$anyDpPaid && !$anyPaid;
                            })
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'mt-4']),

                        FileUpload::make('bukti_pelunasan')
                            ->label('Upload Bukti Pelunasan')
                            ->directory('bukti-pembayaran')->visibility('private')->required()
                            ->visible(function () use ($hasDpOption, $anyDpPaid, $anyPaid, $allLunasOption) {
                                return ($hasDpOption && $anyDpPaid && !$anyPaid) || ($allLunasOption && !$anyPaid);
                            })
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'mt-4']),

                        TextInput::make('payment_complete_info')
                            ->label('Status')
                            ->disabled()
                            ->dehydrated(false)
                            ->default('Pembayaran untuk pengajuan ini telah Lunas.')
                            ->columnSpanFull()
                            ->visible(fn() => $allPaid),
                    ];
                })
                ->action(function (array $data, Pengajuan $record) {
                    $updatePayload = [];
                    $catatanTambahan = '';

                    if (isset($data['bukti_dp']) && !$record->items->some(fn($item) => $item->vendorFinal?->bukti_dp)) {
                        $updatePayload['bukti_dp'] = $data['bukti_dp'];
                        $catatanTambahan = "\n\n[Pembayaran DP oleh Kepala Divisi Operasional: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                        $record->items->each(function ($item) use ($updatePayload) {
                            if ($item->vendorFinal) {
                                $item->vendorFinal->update($updatePayload);
                            }
                        });
                    }

                    if (isset($data['bukti_pelunasan']) && !$record->items->every(fn($item) => $item->vendorFinal?->bukti_pelunasan)) {
                        $updatePayload['bukti_pelunasan'] = $data['bukti_pelunasan'];
                        $catatanTambahan = "\n\n[Pelunasan Pembayaran oleh Kepala Divisi Operasional: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                        $record->items->each(function ($item) use ($updatePayload) {
                            if ($item->vendorFinal) {
                                $item->vendorFinal->update($updatePayload);
                            }
                        });
                    }

                    $record->load('items.vendorFinal');
                    $allPaid = $record->items->every(fn($item) => $item->vendorFinal?->bukti_pelunasan);

                    $catatanRevisi = $record->catatan_revisi ?? '';
                    if (!empty($catatanTambahan)) {
                        $catatanRevisi .= $catatanTambahan;
                    }

                    if ($allPaid) {
                        $record->update([
                            'status' => Pengajuan::STATUS_SUDAH_BAYAR,
                            'catatan_revisi' => trim($catatanRevisi),
                            'disbursed_by' => Auth::id(),
                        ]);
                        Notification::make()->title('Semua pembayaran telah lunas!')->success()->send();
                    } else {
                        $record->update([
                            'catatan_revisi' => trim($catatanRevisi),
                            'disbursed_by' => Auth::id(),
                        ]);
                        Notification::make()->title('Pembayaran berhasil disimpan.')->success()->send();
                    }
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA),
            Action::make('download_spm')
                ->label('SPM')
                ->color('info')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (Pengajuan $record) {
                    // 1. Kumpulkan semua data yang dibutuhkan oleh template
                    $itemsToPay = [];
                    $total = 0;
                    foreach ($record->items as $item) {
                        if ($item->vendorFinal) {
                            $vendor = $item->vendorFinal;
                            $subtotal = ($vendor->harga ?? 0) * $item->kuantitas;
                            $itemsToPay[] = [
                                'barang' => $item->nama_barang,
                                'kuantitas' => $item->kuantitas,
                                'harga' => $vendor->harga ?? 0,
                                'subtotal' => $subtotal,
                                'vendor' => $vendor->nama_vendor,
                                'metode_pembayaran' => $vendor->metode_pembayaran,
                                'opsi_pembayaran' => $vendor->opsi_pembayaran,
                                'rekening_details' => [
                                    'nama_bank' => $vendor->nama_bank,
                                    'no_rekening' => $vendor->no_rekening,
                                    'nama_rekening' => $vendor->nama_rekening,
                                ],
                                'dp_details' => [
                                    'nominal_dp' => $vendor->nominal_dp,
                                    'tanggal_dp' => $vendor->tanggal_dp ? Carbon::parse($vendor->tanggal_dp)->translatedFormat('d M Y') : '-',
                                ],
                                'tanggal_pelunasan' => $vendor->tanggal_pelunasan ? Carbon::parse($vendor->tanggal_pelunasan)->translatedFormat('d M Y') : '-',
                            ];
                            $total += $subtotal;
                        }
                    }

                    // 2. Tentukan siapa direktur yang approve
                    // --- Generate QR Code untuk setiap penanda tangan ---
                    $direkturQrCode = null;
                    $kadivGaQrCode = null;

                    // Tentukan siapa direktur yang approve
                    $direktur = null;
                    if ($record->direktur_utama_approved_by) {
                        $direktur = User::find($record->direktur_utama_approved_by);
                        $direkturJabatan = 'Direktur Utama';
                    } elseif ($record->direktur_operasional_approved_by) {
                        $direktur = User::find($record->direktur_operasional_approved_by);
                        $direkturJabatan = 'Direktur Operasional';
                    }

                    // Generate QR Code untuk Direktur jika ada
                    if ($direktur) {
                        $verificationUrl = URL::signedRoute('approval.verify', ['pengajuan' => $record, 'user' => $direktur]);
                        $qrCodeData = QrCode::format('png')->size(80)->margin(1)->generate($verificationUrl);
                        $direkturQrCode = 'data:image/png;base64,' . base64_encode($qrCodeData);
                    }

                    // Generate QR Code untuk Kadiv GA
                    $kadivGa = User::find($record->kadiv_ga_approved_by);
                    if ($kadivGa) {
                        $verificationUrl = URL::signedRoute('approval.verify', ['pengajuan' => $record, 'user' => $kadivGa]);
                        $qrCodeData = QrCode::format('png')->size(80)->margin(1)->generate($verificationUrl);
                        $kadivGaQrCode = 'data:image/png;base64,' . base64_encode($qrCodeData);
                    }


                    // 3. Siapkan semua data dalam satu array untuk dikirim ke view
                    $data = [
                        'kode_pengajuan' => $record->kode_pengajuan,
                        'tanggal_pengajuan' => $record->created_at->translatedFormat('d F Y'),
                        'pemohon' => $record->pemohon->nama_user,
                        'divisi' => $record->pemohon->divisi->nama_divisi,
                        'items' => $itemsToPay,
                        'total' => $total,
                        'kadivGaName' => $kadivGa?->nama_user ?? '(Kadiv GA)',
                        'direkturName' => $direktur?->nama_user,
                        'direkturJabatan' => $direkturJabatan ?? null,
                        'kadivGaQrCode' => $kadivGaQrCode,
                        'direkturQrCode' => $direkturQrCode,
                    ];

                    $pdf = Pdf::loadView('documents.spm_template', $data);
                    $fileName = 'SPM_' . str_replace('/', '_', $record->kode_pengajuan) . '.pdf';

                    return response()->streamDownload(
                        fn() => print($pdf->output()),
                        $fileName
                    );
                }),
        ];
    }
}
