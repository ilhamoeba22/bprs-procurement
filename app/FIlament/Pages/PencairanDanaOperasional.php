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
use Illuminate\Support\Facades\Storage;


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
            TextColumn::make('total_nilai')->label('Nilai Pengajuan'),
            BadgeColumn::make('status')
                ->label('Status Saat Ini')
                ->color(fn($state) => Pengajuan::getStatusBadgeColor($state)),
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
        $getPrivateFileUrl = function (string $path): ?string {
            if (!Storage::disk('private')->exists($path)) {
                return null;
            }
            return route('private.file', ['path' => $path]);
        };
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading(fn(Pengajuan $record): string => "Detail Pengajuan {$record->kode_pengajuan}")
                ->modalWidth('5xl')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $record->load([
                        'items.vendorFinal',
                        'items.surveiHargas',
                        'pemohon.divisi'
                    ]);

                    $formData = $record->toArray();
                    $formData['items_with_final_vendor'] = $record->items->filter(fn($item) => $item->vendorFinal)->values()->toArray();

                    $getScenarioDetails = function ($items, $tipeSurvei) {
                        $details = [];
                        $totalCost = 0;
                        foreach ($items as $item) {
                            $cheapestSurvey = $item->surveiHargas->where('tipe_survei', $tipeSurvei)->sortBy('harga')->first();
                            if (!$cheapestSurvey) continue;
                            $itemCost = $cheapestSurvey->harga * $item->kuantitas;
                            $taxInfo = 'Tidak ada pajak';
                            $taxCost = 0;
                            if ($cheapestSurvey->kondisi_pajak === 'Pajak ditanggung kita') {
                                $taxCost = $cheapestSurvey->nominal_pajak;
                                $taxInfo = ($cheapestSurvey->jenis_pajak ?? 'Pajak') . ': Rp ' . number_format($taxCost, 0, ',', '.');
                            }
                            $details[] = [
                                'nama_barang' => $item->nama_barang . " (x{$item->kuantitas})",
                                'harga_vendor' => 'Rp ' . number_format($itemCost, 0, ',', '.'),
                                'pajak_info' => $taxInfo,
                            ];
                            $totalCost += ($itemCost + $taxCost);
                        }
                        return empty($details) ? null : ['details' => $details, 'total' => 'Rp ' . number_format($totalCost, 0, ',', '.')];
                    };
                    $formData['pengadaan_details'] = $getScenarioDetails($record->items, 'Pengadaan');
                    $formData['perbaikan_details'] = $getScenarioDetails($record->items, 'Perbaikan');

                    $form->fill($formData);
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
                                Grid::make(3)->schema([
                                    TextInput::make('kategori_barang')->disabled(),
                                    TextInput::make('nama_barang')->disabled(),
                                    TextInput::make('kuantitas')->disabled(),
                                ]),
                                Grid::make(2)->schema([
                                    Textarea::make('spesifikasi')->disabled(),
                                    Textarea::make('justifikasi')->disabled(),
                                ]),
                            ])->columns(1)->disabled()->addActionLabel('Tambah Barang'),
                        ])->collapsible()->collapsed(),

                    Section::make('Rincian Estimasi Biaya - Skenario PENGADAAN')
                        ->schema([
                            Repeater::make('pengadaan_details.details')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('nama_barang')->label('Item')->disabled(),
                                        TextInput::make('harga_vendor')->label('Harga dari Vendor')->disabled(),
                                        TextInput::make('pajak_info')->label('Pajak Ditanggung Perusahaan')->disabled(),
                                    ])
                                ])->disabled()->disableItemCreation()->disableItemDeletion()->disableItemMovement(),

                            Placeholder::make('pengadaan_details.total')
                                ->label('TOTAL ESTIMASI BIAYA PENGADAAN')
                                ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . $get('pengadaan_details.total') . '</b>')),
                        ])->collapsible()->collapsed(),

                    Section::make('Rincian Estimasi Biaya - Skenario PERBAIKAN')
                        ->schema([
                            Repeater::make('perbaikan_details.details')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('nama_barang')->label('Item')->disabled(),
                                        TextInput::make('harga_vendor')->label('Harga dari Vendor')->disabled(),
                                        TextInput::make('pajak_info')->label('Pajak Ditanggung Perusahaan')->disabled(),
                                    ])
                                ])->disabled()->disableItemCreation()->disableItemDeletion()->disableItemMovement(),

                            Placeholder::make('perbaikan_details.total')
                                ->label('TOTAL ESTIMASI BIAYA PERBAIKAN')
                                ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . $get('perbaikan_details.total') . '</b>')),
                        ])
                        ->visible(fn($get) => !is_null($get('perbaikan_details')) && !empty($get('perbaikan_details')['details']))
                        ->collapsible()->collapsed(),

                    Section::make('Review Budgeting')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->disabled(),
                                Textarea::make('budget_catatan_pengadaan')->label('Catatan Budget Pengadaan')->disabled(),

                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->disabled(),
                                Textarea::make('budget_catatan_perbaikan')->label('Catatan Budget Perbaikan')->disabled(),
                            ])->visible(fn($get) => !is_null($get('budget_status_perbaikan'))),
                        ])->collapsible()->collapsed(),

                    Section::make('Final Approve')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('kadiv_ga_decision_type')
                                    ->label('Keputusan Kadiv GA')
                                    ->disabled()
                                    ->default(fn($get) => $get('kadiv_ga_decision_type') ?? 'Tidak Diketahui'),
                                Textarea::make('kadiv_ga_catatan')
                                    ->label('Catatan Kadiv GA')
                                    ->disabled()
                                    ->default(fn($get) => $get('kadiv_ga_catatan') ?? 'Tidak ada catatan'),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('direktur_operasional_decision_type')
                                    ->label('Keputusan Direktur Operasional')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_operasional_decision_type') ?? 'Tidak Diketahui'),
                                Textarea::make('direktur_operasional_catatan')
                                    ->label('Catatan Direktur Operasional')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_operasional_catatan') ?? 'Tidak ada catatan'),
                            ])->visible(fn($get) => !empty($get('direktur_operasional_decision_type')) || !empty($get('direktur_operasional_catatan'))),
                            Grid::make(2)->schema([
                                TextInput::make('direktur_utama_decision_type')
                                    ->label('Keputusan Direktur Utama')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_utama_decision_type') ?? 'Tidak Diketahui'),
                                Textarea::make('direktur_utama_catatan')
                                    ->label('Catatan Direktur Utama')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_utama_catatan') ?? 'Tidak ada catatan'),
                            ])->visible(fn($get) => !empty($get('direktur_utama_decision_type')) || !empty($get('direktur_utama_catatan'))),
                        ])
                        ->visible(fn(Pengajuan $record) => in_array($record->status, [
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                        ]))
                        ->collapsed()
                        ->columnSpanFull(),

                    Section::make('Vendor & Harga Final yang Disetujui')
                        ->schema([
                            Repeater::make('items_with_final_vendor')
                                ->label('')
                                ->schema([
                                    Grid::make(5)->schema([
                                        TextInput::make('nama_barang')
                                            ->label('Nama Barang')
                                            ->disabled(),
                                        TextInput::make('vendor_final.nama_vendor')
                                            ->label('Nama Vendor')
                                            ->disabled(),
                                        // --- PERBAIKAN DI SINI ---
                                        TextInput::make('vendor_final.harga')
                                            ->label('Harga Satuan')
                                            ->prefix('Rp') // Gunakan prefix untuk 'Rp'
                                            // Format angka dengan pemisah ribuan
                                            ->formatStateUsing(fn(?string $state): string => number_format($state, 0, ',', '.'))
                                            ->disabled(),
                                        TextInput::make('vendor_final.metode_pembayaran')
                                            ->label('Metode Bayar')
                                            ->disabled(),
                                        TextInput::make('vendor_final.opsi_pembayaran')
                                            ->label('Opsi Bayar')
                                            ->disabled(),
                                    ]),
                                ])
                                ->disabled()
                                ->disableItemCreation()
                                ->disableItemDeletion()
                                ->disableItemMovement(),
                        ])
                        ->collapsible()
                        ->visible(fn($get) => !empty($get('items_with_final_vendor'))),
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
                    return 'Pembayaran Selesai';
                })
                ->modalWidth('sm')
                ->form(function (Pengajuan $record) use ($getPrivateFileUrl) {
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
                            ->disk('private')
                            ->directory('bukti-pembayaran')
                            ->visibility('private')
                            ->getUploadedFileNameForStorageUsing(function ($file) use ($record) {
                                $kode = str_replace('/', '_', $record->kode_pengajuan);
                                return "{$kode}_BUKTI_DP_" . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->required()
                            ->downloadable()
                            ->visible(function () use ($hasDpOption, $anyDpPaid, $anyPaid) {
                                return $hasDpOption && !$anyDpPaid && !$anyPaid;
                            })
                            ->columnSpanFull(),

                        FileUpload::make('bukti_pelunasan')
                            ->label('Upload Bukti Pelunasan')
                            ->disk('private')
                            ->directory('bukti-pembayaran')
                            ->visibility('private')
                            ->getUploadedFileNameForStorageUsing(function ($file) use ($record) {
                                $kode = str_replace('/', '_', $record->kode_pengajuan);
                                return "{$kode}_BUKTI_PELUNASAN_" . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->required()
                            ->downloadable()
                            ->visible(function () use ($hasDpOption, $anyDpPaid, $anyPaid, $allLunasOption) {
                                return ($hasDpOption && $anyDpPaid && !$anyPaid) || ($allLunasOption && !$anyPaid);
                            })
                            ->columnSpanFull(),

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
                    $direkturQrCode = null;
                    $kadivGaQrCode = null;
                    $direktur = null;
                    if ($record->direktur_utama_approved_by) {
                        $direktur = User::find($record->direktur_utama_approved_by);
                        $direkturJabatan = 'Direktur Utama';
                    } elseif ($record->direktur_operasional_approved_by) {
                        $direktur = User::find($record->direktur_operasional_approved_by);
                        $direkturJabatan = 'Direktur Operasional';
                    }
                    if ($direktur) {
                        $verificationUrl = URL::signedRoute('approval.verify', ['pengajuan' => $record, 'user' => $direktur]);
                        $qrCodeData = QrCode::format('png')->size(80)->margin(1)->generate($verificationUrl);
                        $direkturQrCode = 'data:image/png;base64,' . base64_encode($qrCodeData);
                    }
                    $kadivGa = User::find($record->kadiv_ga_approved_by);
                    if ($kadivGa) {
                        $verificationUrl = URL::signedRoute('approval.verify', ['pengajuan' => $record, 'user' => $kadivGa]);
                        $qrCodeData = QrCode::format('png')->size(80)->margin(1)->generate($verificationUrl);
                        $kadivGaQrCode = 'data:image/png;base64,' . base64_encode($qrCodeData);
                    }
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
