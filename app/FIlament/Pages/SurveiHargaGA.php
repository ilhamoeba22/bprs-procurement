<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\RevisiHarga;
use App\Models\SurveiHarga;
use Illuminate\Support\Str;
use App\Models\VendorPembayaran;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Components\RevisiTimelineSection;
use App\Filament\Components\StandardDetailSections;

class SurveiHargaGA extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string $view = 'filament.pages.survei-harga-g-a';
    protected static ?string $navigationLabel = 'Survei Harga GA';
    protected static ?int $navigationSort = 6;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Survei Harga GA)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['General Affairs', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items', 'items.surveiHargas', 'vendorPembayaran']);

        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->whereIn('status', [
                    Pengajuan::STATUS_SURVEI_GA,
                    Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                    Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                    Pengajuan::STATUS_SUDAH_BAYAR,
                    Pengajuan::STATUS_SELESAI,
                ])->orWhere('ga_surveyed_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', [
                Pengajuan::STATUS_SURVEI_GA,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                Pengajuan::STATUS_SUDAH_BAYAR,
                Pengajuan::STATUS_SELESAI,
            ])->orWhereNotNull('ga_surveyed_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            TextColumn::make('total_nilai')
                ->label('Total Nilai')
                ->money('IDR')
                ->sortable()
                ->state(function (Pengajuan $record): ?float {
                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                    if ($latestRevisi) {
                        return $latestRevisi->harga_revisi + $latestRevisi->nominal_pajak;
                    }
                    return $record->total_nilai;
                })
                ->icon(function (Pengajuan $record): ?string {
                    $hasRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->isNotEmpty();
                    return $hasRevisi ? 'heroicon-o-arrow-path' : null;
                })
                ->color(function (Pengajuan $record): ?string {
                    $hasRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->isNotEmpty();
                    return $hasRevisi ? 'warning' : null;
                })
                ->description(function (Pengajuan $record): ?string {
                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                    if ($latestRevisi) {
                        $hargaAwalBarang = $latestRevisi->harga_awal;
                        $vendorName = $latestRevisi->surveiHarga?->nama_vendor;
                        if (!$vendorName) return 'Nilai Awal: -';
                        $totalPajakAwal = 0;
                        foreach ($record->items as $item) {
                            $survey = $item->surveiHargas
                                ->where('nama_vendor', $vendorName)
                                ->where('kondisi_pajak', 'Pajak ditanggung kita')
                                ->first();
                            if ($survey) {
                                $totalPajakAwal += $survey->nominal_pajak;
                            }
                        }

                        $totalBiayaAwal = $hargaAwalBarang + $totalPajakAwal;
                        return 'Nilai Awal: ' . number_format($totalBiayaAwal, 0, ',', '.');
                    }

                    return null;
                }),

            BadgeColumn::make('status')
                ->label('Status Saat Ini')
                ->color(fn($state) => Pengajuan::getStatusBadgeColor($state)),

            BadgeColumn::make('tindakan_saya')
                ->label('Keterangan')
                ->state(fn(Pengajuan $record): string => match ($record->status) {
                    Pengajuan::STATUS_SELESAI => 'Pengajuan Selesai',
                    Pengajuan::STATUS_SUDAH_BAYAR => 'Menunggu Penyelesaian',
                    default => $record->ga_surveyed_by === Auth::id() ? 'Sudah Disurvei' : 'Menunggu Aksi',
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Disurvei', 'Pengajuan Selesai' => 'success',
                    'Menunggu Penyelesaian' => 'warning',
                    default => 'gray',
                }),
        ];
    }

    protected function getSurveyFormSchema(Pengajuan $record): array
    {
        $itemsForSurvey = $record->items->map(fn($item) => [
            'id_item' => $item->id_item,
            'nama_barang' => $item->nama_barang,
            'kategori_barang' => $item->kategori_barang,
        ])->toArray();

        return [
            Section::make('Input Survei Harga Berdasarkan Vendor')
                ->schema([
                    Repeater::make('survei_per_vendor')
                        ->label('Data Penawaran Vendor')
                        ->addActionLabel('Tambah Data Vendor')
                        ->minItems(1)
                        ->collapsible()
                        ->cloneable()
                        ->schema([
                            TextInput::make('nama_vendor')->label('Nama Vendor / Link')->required(),
                            Repeater::make('item_details')
                                ->label('Rincian Harga per Item')
                                ->default($itemsForSurvey)
                                ->disableItemCreation()->disableItemDeletion()->disableItemMovement()
                                ->schema([
                                    Placeholder::make('nama_barang_info')
                                        ->label(false)
                                        ->content(fn($get) => new HtmlString('<b class="text-base">' . $get('nama_barang') . '</b>' . ' <span class="text-sm text-gray-500">(' . $get('kategori_barang') . ')</span>')),
                                    Hidden::make('id_item'),
                                    Hidden::make('kategori_barang'),
                                    Select::make('tipe_survei')
                                        ->label('Tipe Survei')
                                        ->options(['Barang' => 'Barang', 'Jasa' => 'Jasa', 'Sewa' => 'Sewa'])
                                        ->required(),
                                    TextInput::make('harga')->label('Harga')->numeric()->required()->prefix('Rp')->live(),
                                    Textarea::make('rincian_harga')->label('Rincian Harga (Opsional)'),
                                    Section::make('Detail Pajak')->collapsible()->collapsed()->schema([
                                        Radio::make('kondisi_pajak')
                                            ->label('Kondisi Pajak')
                                            ->options([
                                                'Tidak Ada Pajak' => 'Tidak Ada Pajak',
                                                'Pajak ditanggung kita' => 'Pajak ditanggung Perusahaan (Exclude)',
                                                'Pajak ditanggung Vendor' => 'Pajak ditanggung Vendor (Include)',
                                            ])
                                            ->default('Tidak Ada Pajak')
                                            ->live(),
                                        Select::make('jenis_pajak')
                                            ->label('Jenis Pajak')
                                            ->options(['PPh 21' => 'PPh 21', 'PPh 23' => 'PPh 23'])
                                            ->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')
                                            ->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                        TextInput::make('npwp_nik')
                                            ->label('NPWP / NIK')
                                            ->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')
                                            ->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                        TextInput::make('nama_pemilik_pajak')
                                            ->label('Nama Sesuai NPWP / NIK')
                                            ->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')
                                            ->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                        TextInput::make('nominal_pajak')
                                            ->label('Nominal Pajak')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')
                                            ->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                    ])->columns(2),
                                ])->columnSpanFull(),
                            Placeholder::make('total_biaya_vendor')
                                ->label('Total Biaya Vendor')
                                ->content(function (Forms\Get $get): HtmlString {
                                    $items = $get('item_details') ?? [];
                                    $total = 0;
                                    foreach ($items as $item) {
                                        $harga = (float) ($item['harga'] ?? 0);
                                        $pajak = (float) ($item['nominal_pajak'] ?? 0);
                                        $total += $harga + ($item['kondisi_pajak'] === 'Pajak ditanggung kita' ? $pajak : 0);
                                    }
                                    return new HtmlString('<b class="text-xl text-primary-600">Rp ' . number_format($total, 0, ',', '.') . '</b>');
                                }),
                            Section::make('Opsi Pembayaran & Bukti (untuk vendor ini)')->schema([
                                Radio::make('metode_pembayaran')
                                    ->label('Metode Bayar')
                                    ->options(['Transfer' => 'Transfer', 'Tunai' => 'Tunai'])
                                    ->required()
                                    ->live(),
                                Radio::make('opsi_pembayaran')
                                    ->label('Opsi Bayar')
                                    ->options(['Bisa DP' => 'Bisa DP', 'Langsung Lunas' => 'Langsung Lunas'])
                                    ->required()
                                    ->live(),
                                DatePicker::make('tanggal_dp')
                                    ->label('Tgl. DP')
                                    ->required()
                                    ->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                TextInput::make('nominal_dp')
                                    ->label('Nominal DP')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP')
                                    ->maxValue(function (Forms\Get $get): float {
                                        $items = $get('item_details') ?? [];
                                        $total = 0;
                                        foreach ($items as $item) {
                                            $harga = (float) ($item['harga'] ?? 0);
                                            $pajak = (float) ($item['nominal_pajak'] ?? 0);
                                            $total += $harga + ($item['kondisi_pajak'] === 'Pajak ditanggung kita' ? $pajak : 0);
                                        }
                                        return $total;
                                    })
                                    ->validationMessages([
                                        'max' => 'Nominal DP tidak boleh melebihi total biaya vendor (Rp :max).',
                                    ]),
                                DatePicker::make('tanggal_pelunasan')
                                    ->label('Tgl. Lunas')
                                    ->required()
                                    ->visible(fn($get) => in_array($get('opsi_pembayaran'), ['Bisa DP', 'Langsung Lunas'])),
                                TextInput::make('nama_rekening')
                                    ->label('Nama Rekening')
                                    ->required(fn($get) => $get('metode_pembayaran') === 'Transfer')
                                    ->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                TextInput::make('no_rekening')
                                    ->label('Nomor Rekening')
                                    ->required(fn($get) => $get('metode_pembayaran') === 'Transfer')
                                    ->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                TextInput::make('nama_bank')
                                    ->label('Nama Bank')
                                    ->required(fn($get) => $get('metode_pembayaran') === 'Transfer')
                                    ->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                FileUpload::make('bukti_path')
                                    ->label('Bukti Penawaran Vendor')
                                    ->required()
                                    ->disk('private')
                                    ->directory(fn(Pengajuan $record) => $record->getStorageDirectory())
                                    ->getUploadedFileNameForStorageUsing(function (UploadedFile $file, Forms\Get $get) use ($record): string {
                                        $vendorName = Str::slug($get('../../nama_vendor'));
                                        return $record->generateUniqueFileName("bukti_survei_{$vendorName}", $file);
                                    }),
                            ])->columns(2),
                        ])->columnSpanFull(),
                ]),
        ];
    }

    protected function getSurveyActionLogic(): callable
    {
        return function (array $data, Pengajuan $record, string $successMessage) {
            // Validasi jumlah vendor berdasarkan kategori item
            $items = $record->items;
            $isOnlyBarang = $items->every(fn($item) => $item->kategori_barang === 'Barang');
            $vendorCount = count(array_filter($data['survei_per_vendor'], fn($vendor) => !empty($vendor['nama_vendor'])));

            if ($isOnlyBarang && $vendorCount < 3) {
                Notification::make()
                    ->title('Error')
                    ->body('Pengajuan dengan kategori Barang saja wajib memiliki minimal 3 vendor sebagai pembanding.')
                    ->danger()
                    ->send();
                return;
            }

            if ($vendorCount < 1) {
                Notification::make()
                    ->title('Error')
                    ->body('Minimal 1 vendor diperlukan untuk submit survei harga.')
                    ->danger()
                    ->send();
                return;
            }

            // Deleting existing survey prices and vendor payments
            $record->items()->with('surveiHargas')->get()->flatMap->surveiHargas->each->delete();
            VendorPembayaran::where('id_pengajuan', $record->id_pengajuan)->delete();

            foreach ($data['survei_per_vendor'] as $vendorSurvey) {
                if (empty($vendorSurvey['nama_vendor'])) continue;

                // Creating vendor payment record
                VendorPembayaran::create([
                    'id_pengajuan' => $record->id_pengajuan,
                    'nama_vendor' => $vendorSurvey['nama_vendor'],
                    'metode_pembayaran' => $vendorSurvey['metode_pembayaran'],
                    'opsi_pembayaran' => $vendorSurvey['opsi_pembayaran'],
                    'nominal_dp' => $vendorSurvey['nominal_dp'] ?? 0,
                    'tanggal_dp' => $vendorSurvey['tanggal_dp'] ?? null,
                    'tanggal_pelunasan' => $vendorSurvey['tanggal_pelunasan'] ?? null,
                    'nama_rekening' => $vendorSurvey['nama_rekening'] ?? null,
                    'no_rekening' => $vendorSurvey['no_rekening'] ?? null,
                    'nama_bank' => $vendorSurvey['nama_bank'] ?? null,
                    'bukti_dp' => null,
                    'bukti_pelunasan' => null,
                    'bukti_penyelesaian' => null,
                ]);

                // Creating survey price records for each item
                foreach ($vendorSurvey['item_details'] as $itemDetail) {
                    SurveiHarga::create([
                        'id_item' => $itemDetail['id_item'],
                        'tipe_survei' => $itemDetail['tipe_survei'],
                        'nama_vendor' => $vendorSurvey['nama_vendor'],
                        'harga' => $itemDetail['harga'],
                        'bukti_path' => $vendorSurvey['bukti_path'],
                        'is_final' => false,
                        'kondisi_pajak' => $itemDetail['kondisi_pajak'] ?? 'Tidak Ada Pajak',
                        'jenis_pajak' => $itemDetail['jenis_pajak'] ?? null,
                        'npwp_nik' => $itemDetail['npwp_nik'] ?? null,
                        'nama_pemilik_pajak' => $itemDetail['nama_pemilik_pajak'] ?? null,
                        'nominal_pajak' => $itemDetail['nominal_pajak'] ?? 0,
                        'rincian_harga' => $itemDetail['rincian_harga'] ?? null,
                    ]);
                }
            }

            if ($record->status === Pengajuan::STATUS_SURVEI_GA) {
                $record->update([
                    'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                    'ga_surveyed_by' => Auth::id(),
                ]);
            }

            Notification::make()->title($successMessage)->success()->send();
        };
    }

    protected function getPrivateFileUrl(string $path): ?string
    {
        if (!Storage::disk('private')->exists($path)) {
            return null;
        }
        return route('private.file', ['path' => $path]);
    }

    protected function getTableActions(): array
    {
        $calculateRevisi = function (Forms\Get $get, Forms\Set $set) {
            $hargaLama = (float) ($get('harga_lama') ?? 0);
            $selisih = (float) ($get('selisih_harga') ?? 0);
            $kondisi = $get('kondisi_harga');
            $hargaRevisi = $hargaLama;

            if ($kondisi === 'Biaya Kurang') {
                $hargaRevisi = $hargaLama + $selisih;
            } elseif ($kondisi === 'Biaya Lebih') {
                $hargaRevisi = max(0, $hargaLama - $selisih);
            }

            $set('harga_revisi', $hargaRevisi);
        };

        return [
            ViewAction::make()->label('Detail')
                ->modalHeading(fn(Pengajuan $record): string => "Detail Pengajuan {$record->kode_pengajuan}")
                ->modalWidth('4xl')
                ->mountUsing(function (Form $form, Pengajuan $record) {
                    // 1. Memuat semua relasi yang dibutuhkan secara efisien
                    $record->load([
                        'items.surveiHargas.revisiHargas.direvisiOleh',
                        'items.surveiHargas.revisiHargas.revisiBudgetApprover',
                        'items.surveiHargas.revisiHargas.revisiBudgetValidator',
                        'items.surveiHargas.revisiHargas.revisiKadivGaApprover',
                        'items.surveiHargas.revisiHargas.revisiDirekturOperasionalApprover',
                        'items.surveiHargas.revisiHargas.revisiDirekturUtamaApprover',
                        'vendorPembayaran',
                        'pemohon.divisi',
                        'approverBudget',
                        'validatorBudgetOps',
                        'approverKadivGa',
                        'approverDirOps',
                        'approverDirUtama',
                    ]);

                    $formData = $record->toArray();

                    // 2. Menyiapkan nama-nama approver untuk ditampilkan di StandardDetailSections
                    $formData['budget_approved_by_name'] = $record->approverBudget?->nama_user;
                    $formData['kadiv_ops_budget_approved_by_name'] = $record->validatorBudgetOps?->nama_user;
                    $formData['kadiv_ga_approved_by_name'] = $record->approverKadivGa?->nama_user;
                    $formData['direktur_operasional_approved_by_name'] = $record->approverDirOps?->nama_user;
                    $formData['direktur_utama_approved_by_name'] = $record->approverDirUtama?->nama_user;
                    // dd($formData);
                    $getScenarioDetails = function ($items) use ($record) {
                        $details = [];
                        $totalCost = 0;
                        $nominalDp = 0;
                        $groupedSurveys = $items->flatMap->surveiHargas->groupBy('nama_vendor');
                        if ($groupedSurveys->isEmpty()) return null;

                        $vendorTotals = [];
                        foreach ($groupedSurveys as $namaVendor => $surveys) {
                            $allItemsCovered = $items->every(fn($item) => $surveys->where('id_item', $item->id_item)->isNotEmpty());
                            if (!$allItemsCovered) continue;

                            $vendorTotal = 0;
                            foreach ($items as $item) {
                                $survey = $surveys->where('id_item', $item->id_item)->first();
                                $itemCost = $survey->harga * $item->kuantitas;
                                $taxCost = $survey->kondisi_pajak === 'Pajak ditanggung kita' ? ($survey->nominal_pajak ?? 0) : 0;
                                $vendorTotal += ($itemCost + $taxCost);
                            }
                            $vendorTotals[$namaVendor] = $vendorTotal;
                        }

                        if (empty($vendorTotals)) {
                            return null;
                        }

                        $cheapestVendor = array_key_first($vendorTotals);
                        $minTotal = min($vendorTotals);
                        foreach ($vendorTotals as $vendor => $total) {
                            if ($total === $minTotal) {
                                $cheapestVendor = $vendor;
                                break;
                            }
                        }

                        $cheapestSurveys = $groupedSurveys[$cheapestVendor] ?? [];
                        foreach ($items as $item) {
                            $survey = $cheapestSurveys->where('id_item', $item->id_item)->first();
                            if (!$survey) continue;
                            $itemCost = $survey->harga * $item->kuantitas;
                            $taxInfo = 'Tidak ada pajak';
                            $taxCost = 0;
                            if ($survey->kondisi_pajak === 'Pajak ditanggung kita') {
                                $taxCost = $survey->nominal_pajak ?? 0;
                                $taxInfo = ($survey->jenis_pajak ?? 'Pajak') . ': Rp ' . number_format($taxCost, 0, ',', '.');
                            }
                            $details[] = [
                                'nama_barang' => $item->nama_barang . " (x{$item->kuantitas})",
                                'tipe_survei' => $survey->tipe_survei,
                                'harga_vendor' => 'Rp ' . number_format($itemCost, 0, ',', '.'),
                                'pajak_info' => $taxInfo,
                            ];
                            $totalCost += ($itemCost + $taxCost);
                        }

                        $vendorPembayaran = $record->vendorPembayaran->where('nama_vendor', $cheapestVendor)->first();
                        if ($vendorPembayaran && $vendorPembayaran->nominal_dp > 0) {
                            $nominalDp = $vendorPembayaran->nominal_dp;
                        }

                        return empty($details) ? null : [
                            'details' => $details,
                            'total' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
                            'nominal_dp' => $nominalDp > 0 ? 'Rp ' . number_format($nominalDp, 0, ',', '.') : 'Tidak ada DP'
                        ];
                    };
                    $formData['estimasi_biaya'] = $getScenarioDetails($record->items);

                    // 4. Menyiapkan data untuk RevisiTimelineSection (jika ada)
                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                    if ($latestRevisi) {
                        $finalVendor = $record->vendorPembayaran->where('is_final', true)->first();

                        // PERBAIKAN FINAL: Gunakan data snapshot dari tabel revisi
                        $totalBiayaAwal = $latestRevisi->harga_awal; // <-- Mengambil dari snapshot
                        $totalBiayaSetelahRevisi = $latestRevisi->harga_revisi + $latestRevisi->nominal_pajak;
                        $selisihTotal = $latestRevisi->harga_revisi - $totalBiayaAwal;

                        $formData['revisi_per_vendor'] = [[
                            'harga_awal' => $totalBiayaAwal,
                            'harga_revisi' => $latestRevisi->harga_revisi,
                            'selisih_harga' => $selisihTotal,
                            'nominal_pajak' => $latestRevisi->nominal_pajak,
                            'alasan_revisi' => $latestRevisi->alasan_revisi,
                            'revisi_tanggal' => $latestRevisi->tanggal_revisi,
                            'total_setelah_revisi' => $totalBiayaSetelahRevisi,
                            'nominal_dp' => $finalVendor?->nominal_dp,
                        ]];


                        $formData['revisi_budget_status_pengadaan'] = $latestRevisi->revisi_budget_status_pengadaan;
                        $formData['revisi_budget_catatan_pengadaan'] = $latestRevisi->revisi_budget_catatan_pengadaan;
                        $formData['revisi_budget_approver_name'] = $latestRevisi->revisiBudgetApprover?->nama_user;
                        $formData['revisi_budget_validated_by'] = $latestRevisi->revisiBudgetValidator?->nama_user;
                        $formData['revisi_budget_validated_at'] = $latestRevisi->revisi_budget_validated_at;
                        $formData['revisi_kadiv_ga_decision_type'] = $latestRevisi->revisi_kadiv_ga_decision_type;
                        $formData['revisi_kadiv_ga_catatan'] = $latestRevisi->revisi_kadiv_ga_catatan;
                        $formData['revisi_kadiv_ga_approver_name'] = $latestRevisi->revisiKadivGaApprover?->nama_user;
                        $formData['revisi_direktur_operasional_decision_type'] = $latestRevisi->revisi_direktur_operasional_decision_type;
                        $formData['revisi_direktur_operasional_catatan'] = $latestRevisi->revisi_direktur_operasional_catatan;
                        $formData['revisi_direktur_operasional_approver_name'] = $latestRevisi->revisiDirekturOperasionalApprover?->nama_user;
                        $formData['revisi_direktur_utama_decision_type'] = $latestRevisi->revisi_direktur_utama_decision_type;
                        $formData['revisi_direktur_utama_catatan'] = $latestRevisi->revisi_direktur_utama_catatan;
                        $formData['revisi_direktur_utama_approver_name'] = $latestRevisi->revisiDirekturUtamaApprover?->nama_user;
                    }

                    // 5. Mengisi form dengan semua data yang telah disiapkan
                    $form->fill($formData);
                })
                ->form([
                    ...StandardDetailSections::make(),
                    RevisiTimelineSection::make(),
                ]),


            Action::make('revisiHarga')
                ->label('Revisi Harga')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->modalWidth('2xl') // Modal dibuat lebih ramping
                ->form(function (Pengajuan $record) {
                    // Kalkulator untuk menghitung harga revisi secara otomatis
                    $calculateRevisi = function (Forms\Get $get, Forms\Set $set) {
                        $hargaLama = (float) str_replace('.', '', $get('harga_lama') ?? '0');
                        $selisih = (float) str_replace('.', '', $get('selisih_harga') ?? '0');
                        $kondisi = $get('kondisi_harga');
                        $hargaRevisi = $hargaLama;
                        if ($kondisi === 'Biaya Kurang') {
                            $hargaRevisi = $hargaLama + $selisih;
                        } elseif ($kondisi === 'Biaya Lebih') {
                            $hargaRevisi = max(0, $hargaLama - $selisih);
                        }
                        $set('harga_revisi', $hargaRevisi);
                    };

                    return [
                        Section::make('Revisi Total Harga Barang')->schema([
                            TextInput::make('harga_lama')
                                ->label('Total Harga Barang Lama (Non-Pajak)') // Label diperjelas
                                ->disabled()->prefix('Rp')
                                ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.')),
                            Radio::make('kondisi_harga')
                                ->label('Kondisi Harga Baru')
                                ->options(['Biaya Kurang' => 'Biaya Kurang (Butuh dana tambahan)', 'Biaya Lebih' => 'Biaya Lebih (Ada sisa dana)'])
                                ->required()->live()->afterStateUpdated($calculateRevisi),
                            TextInput::make('selisih_harga')
                                ->label('Selisih Harga Barang (Non-Pajak)') // Label diperjelas
                                ->numeric()->required()->minValue(1)->prefix('Rp')
                                ->live()
                                ->afterStateUpdated($calculateRevisi),
                            TextInput::make('harga_revisi')
                                ->label('Total Harga Barang Revisi (Non-Pajak)') // Label diperjelas
                                ->numeric()->required()->prefix('Rp')
                                ->disabled()->dehydrated(true),
                        ])->columns(2),
                        // =================================================================
                        // BAGIAN REVISI PAJAK DENGAN LOGIKA BARU
                        // =================================================================
                        Section::make('Revisi Pajak')->schema([
                            Radio::make('opsi_pajak')
                                ->label('Opsi Pajak')
                                ->options([
                                    'Pajak Sama' => 'Gunakan Total Pajak Sebelumnya',
                                    'Pajak Berbeda' => 'Input Total Pajak Baru',
                                ])->default('Pajak Sama')->required()->live(),

                            // Field ini hanya muncul jika 'Pajak Berbeda' dipilih
                            TextInput::make('nominal_pajak_baru')
                                ->label('Total Nominal Pajak Baru')
                                ->numeric()->prefix('Rp')
                                ->helperText('Masukkan total pajak baru. Jika tidak ada, isi dengan 0.')
                                ->required(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda')
                                ->visible(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda'),
                            Select::make('jenis_pajak_baru')
                                ->label('Jenis Pajak (jika ada)')
                                ->options(['PPh 21' => 'PPh 21', 'PPh 23' => 'PPh 23'])
                                ->visible(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda'),
                        ])->columns(2)->collapsible(),

                        Textarea::make('alasan_revisi')->label('Alasan Revisi')->required()->columnSpanFull(),
                        FileUpload::make('bukti_revisi')
                            ->disk('private')
                            ->directory(fn(Pengajuan $record) => $record->getStorageDirectory())
                            ->getUploadedFileNameForStorageUsing(fn(UploadedFile $file) => $record->generateUniqueFileName("bukti_revisi", $file))
                    ];
                })
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $finalVendor = $record->vendorPembayaran->where('is_final', true)->first();
                    $totalNilaiBarangOriginal = 0;
                    if ($finalVendor) {
                        foreach ($record->items as $item) {
                            $survey = $item->surveiHargas->where('nama_vendor', $finalVendor->nama_vendor)->first();
                            if ($survey) {
                                $totalNilaiBarangOriginal += ($survey->harga * $item->kuantitas);
                            }
                        }
                    }

                    $form->fill([
                        'harga_lama' => $totalNilaiBarangOriginal,
                        'harga_revisi' => $totalNilaiBarangOriginal,
                    ]);
                })
                ->action(function (array $data, Pengajuan $record): void {
                    // 1. Cari vendor yang sudah final
                    $finalVendor = $record->vendorPembayaran()->where('is_final', true)->first(); //
                    if (!$finalVendor) {
                        Notification::make()->title('Error')->body('Vendor final tidak ditemukan.')->danger()->send();
                        return;
                    }

                    // 2. Ambil data survei pertama dari vendor final sebagai "jangkar"
                    $firstSurvey = $record->items()
                        ->first()
                        ?->surveiHargas()
                        ->where('nama_vendor', $finalVendor->nama_vendor)
                        ->first(); //

                    if (!$firstSurvey) {
                        Notification::make()->title('Error')->body('Data survei harga final tidak ditemukan.')->danger()->send();
                        return;
                    }
                    // Hitung kembali total barang original untuk disimpan sebagai snapshot
                    $totalNilaiBarangOriginal = 0;
                    foreach ($record->items as $item) {
                        $survey = $item->surveiHargas->where('nama_vendor', $finalVendor->nama_vendor)->first();
                        if ($survey) {
                            $totalNilaiBarangOriginal += ($survey->harga * $item->kuantitas);
                        }
                    }

                    // =================================================================
                    // LOGIKA BARU: HITUNG TOTAL PAJAK AWAL
                    // =================================================================
                    $totalPajakAwal = 0;
                    $jenisPajakAwal = null;
                    foreach ($record->items as $item) {
                        $survey = $item->surveiHargas->where('nama_vendor', $finalVendor->nama_vendor)->first();
                        if ($survey && $survey->kondisi_pajak === 'Pajak ditanggung kita') {
                            $totalPajakAwal += $survey->nominal_pajak;
                            if (!$jenisPajakAwal) {
                                $jenisPajakAwal = $survey->jenis_pajak; // Ambil jenis pajak pertama yang ditemukan
                            }
                        }
                    }

                    // Ambil data pajak berdasarkan opsi yang dipilih
                    $pajakData = [];
                    if ($data['opsi_pajak'] === 'Pajak Sama') {
                        $pajakData = [
                            'kondisi_pajak' => $totalPajakAwal > 0 ? 'Pajak ditanggung kita' : 'Tidak Ada Pajak',
                            'jenis_pajak' => $jenisPajakAwal,
                            'nominal_pajak' => $totalPajakAwal,
                        ];
                    } else { // Pajak Berbeda
                        $nominalPajakBaru = $data['nominal_pajak_baru'] ?? 0;
                        $pajakData = [
                            'kondisi_pajak' => $nominalPajakBaru > 0 ? 'Pajak ditanggung kita' : 'Tidak Ada Pajak',
                            'jenis_pajak' => $data['jenis_pajak_baru'] ?? null,
                            'nominal_pajak' => $nominalPajakBaru,
                        ];
                    }

                    RevisiHarga::create(array_merge([
                        'survei_harga_id' => $firstSurvey->id,
                        'harga_awal' => $totalNilaiBarangOriginal,
                        'harga_revisi' => $data['harga_revisi'],
                        'alasan_revisi' => $data['alasan_revisi'],
                        'bukti_revisi' => $data['bukti_revisi'],
                        'tanggal_revisi' => now(),
                        'direvisi_oleh' => Auth::id(),
                        'opsi_pajak' => $data['opsi_pajak'],
                    ], $pajakData));

                    $record->update(['status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI]);

                    Notification::make()->title('Revisi Harga Berhasil')->body('Pengajuan akan diproses untuk approval ulang.')->success()->send();
                })
                ->visible(fn(Pengajuan $record): bool => $record->canRevisePrice()),

            Action::make('submit_survey')
                ->label('Input Survei Harga')
                ->color('info')
                ->icon('heroicon-o-document-check')
                ->modalWidth('6xl')
                ->form(fn(Pengajuan $record) => $this->getSurveyFormSchema($record))
                ->action(fn(array $data, Pengajuan $record) => ($this->getSurveyActionLogic())($data, $record, 'Hasil survei berhasil disubmit'))
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_SURVEI_GA),

            Action::make('edit_survey')
                ->label('Edit Survei')
                ->color('warning')
                ->icon('heroicon-o-pencil')
                ->modalWidth('6xl')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record): void {
                    $allSurveys = $record->items->flatMap->surveiHargas;
                    $vendorPembayaran = VendorPembayaran::where('id_pengajuan', $record->id_pengajuan)->get()->keyBy('nama_vendor');
                    $groupedByVendor = $allSurveys->groupBy('nama_vendor');
                    $defaultData = [];
                    foreach ($groupedByVendor as $namaVendor => $surveys) {
                        $vendorData = $vendorPembayaran[$namaVendor] ?? null;
                        $vendorSurvey = [
                            'nama_vendor' => $namaVendor,
                            'metode_pembayaran' => $vendorData?->metode_pembayaran,
                            'opsi_pembayaran' => $vendorData?->opsi_pembayaran,
                            'tanggal_dp' => $vendorData?->tanggal_dp,
                            'nominal_dp' => $vendorData?->nominal_dp,
                            'tanggal_pelunasan' => $vendorData?->tanggal_pelunasan,
                            'nama_rekening' => $vendorData?->nama_rekening,
                            'no_rekening' => $vendorData?->no_rekening,
                            'nama_bank' => $vendorData?->nama_bank,
                            'bukti_path' => $surveys->first()->bukti_path,
                            'bukti_dp' => $vendorData?->bukti_dp,
                            'bukti_pelunasan' => $vendorData?->bukti_pelunasan,
                            'bukti_penyelesaian' => $vendorData?->bukti_penyelesaian,
                            'item_details' => [],
                        ];
                        foreach ($record->items as $item) {
                            $surveyForItem = $surveys->where('id_item', $item->id_item)->first();
                            $vendorSurvey['item_details'][] = [
                                'id_item' => $item->id_item,
                                'nama_barang' => $item->nama_barang,
                                'kategori_barang' => $item->kategori_barang,
                                'tipe_survei' => $surveyForItem?->tipe_survei,
                                'harga' => $surveyForItem?->harga,
                                'rincian_harga' => $surveyForItem?->rincian_harga,
                                'kondisi_pajak' => $surveyForItem?->kondisi_pajak ?? 'Tidak Ada Pajak',
                                'jenis_pajak' => $surveyForItem?->jenis_pajak,
                                'npwp_nik' => $surveyForItem?->npwp_nik,
                                'nama_pemilik_pajak' => $surveyForItem?->nama_pemilik_pajak,
                                'nominal_pajak' => $surveyForItem?->nominal_pajak,
                            ];
                        }
                        $defaultData[] = $vendorSurvey;
                    }
                    $form->fill(['survei_per_vendor' => $defaultData]);
                })
                ->form(fn(Pengajuan $record) => $this->getSurveyFormSchema($record))
                ->action(fn(array $data, Pengajuan $record) => ($this->getSurveyActionLogic())($data, $record, 'Hasil survei berhasil diperbarui'))
                ->visible(fn(Pengajuan $record) => in_array($record->status, [
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                ])),

            Action::make('penyelesaian')
                ->label('Penyelesaian')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->modalHeading('Form Penyelesaian Pengadaan')
                ->modalWidth('2xl') // Modal bisa dibuat lebih lebar
                ->form(fn(Pengajuan $record): array => [
                    // Menggunakan Repeater untuk tampilan yang lebih baik
                    Repeater::make('bukti_penyelesaian_list')
                        ->label('Daftar Bukti Penyelesaian')
                        ->addActionLabel('Tambah Bukti Penyelesaian') // Tombol yang Anda inginkan
                        ->minItems(1)
                        ->collapsible()
                        ->cloneable()
                        ->schema([
                            FileUpload::make('file_path')
                                ->label('Upload File Bukti')
                                ->required()
                                ->disk('private')
                                ->directory(fn() => $record->getStorageDirectory())
                                ->getUploadedFileNameForStorageUsing(fn(UploadedFile $file) => $record->generateUniqueFileName("bukti_penyelesaian", $file)),
                        ])
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, Pengajuan $record): void {
                    // 1. Cari vendor final untuk menyimpan bukti
                    $finalVendor = $record->vendorPembayaran()->where('is_final', true)->first();
                    if (!$finalVendor) {
                        Notification::make()->title('Error')->body('Vendor final tidak ditemukan.')->danger()->send();
                        return;
                    }

                    // 2. Data yang disimpan sekarang berupa array (JSON) dari repeater
                    // Pastikan kolom 'bukti_penyelesaian' di database Anda bisa menampung JSON (tipe TEXT atau JSON)
                    $finalVendor->update([
                        'bukti_penyelesaian' => $data['bukti_penyelesaian_list'],
                    ]);

                    // 3. Tambahkan catatan ke riwayat dan ubah status menjadi SELESAI
                    $catatanTambahan = "\n\n[Proses diselesaikan oleh GA: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                    $record->update([
                        'status' => Pengajuan::STATUS_SELESAI,
                        'catatan_revisi' => trim(($record->catatan_revisi ?? '') . $catatanTambahan),
                    ]);

                    Notification::make()->title('Pengajuan Telah Selesai')->body('Proses pengadaan untuk tiket ini telah berhasil ditutup.')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_SUDAH_BAYAR),
        ];
    }
}
