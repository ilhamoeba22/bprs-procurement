<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Tables\Table;
use App\Models\SurveiHarga;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
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
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Components\RevisiTimelineSection;
use App\Filament\Components\StandardDetailSections;

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
                $q->whereIn('status', [
                    Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                    Pengajuan::STATUS_SUDAH_BAYAR,
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
                    Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                ])
                    ->orWhere('disbursed_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', [
                Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                Pengajuan::STATUS_SUDAH_BAYAR,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
                Pengajuan::STATUS_MENUNGGU_PELUNASAN,
            ])
                ->orWhereNotNull('disbursed_by');
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
                        if (!$vendorName) return 'Nilai Awal: -'; // Fallback jika vendor tidak ditemukan
                        $totalPajakAwal = 0;
                        foreach ($record->items as $item) {
                            $survey = $item->surveiHargas
                                ->where('nama_vendor', $vendorName)
                                ->where('kondisi_pajak', 'Pajak ditanggung BPRS')
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
                                $taxCost = $survey->kondisi_pajak === 'Pajak ditanggung BPRS' ? ($survey->nominal_pajak ?? 0) : 0;
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
                            if ($survey->kondisi_pajak === 'Pajak ditanggung BPRS') {
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

            // =================================================================
            // ACTION PEMBAYARAN DENGAN LOGIKA BARU YANG DINAMIS
            // =================================================================
            Action::make('payment')
                ->label('Pembayaran')
                ->color('success')
                ->icon('heroicon-o-currency-dollar')
                ->modalWidth('4xl')
                ->modalHeading(function (Pengajuan $record) {
                    $finalVendor = $record->vendorPembayaran->where('is_final', true)->first();
                    if ($finalVendor?->opsi_pembayaran === 'Bisa DP' && !$finalVendor?->bukti_dp) {
                        return 'Form Pembayaran Down Payment (DP)';
                    }
                    return 'Form Pelunasan Pembayaran';
                })
                ->form(function (Pengajuan $record) {
                    $finalVendor = $record->vendorPembayaran->where('is_final', true)->first();
                    if (!$finalVendor) {
                        return [Placeholder::make('error')->content('Data vendor final tidak ditemukan!')];
                    }

                    $metodePembayaran = strtolower(trim($finalVendor->metode_pembayaran ?? ''));

                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();

                    $displayTotalBarang = 0;
                    $displayTotalPajak = 0;
                    $displayJenisPajak = null;
                    $pajakDitanggungKita = false;
                    $kondisiPajakFinal = 'Tidak Ada Pajak';

                    if ($latestRevisi) {
                        $displayTotalBarang = $latestRevisi->harga_revisi;
                        $displayTotalPajak = $latestRevisi->nominal_pajak;
                        $displayJenisPajak = $latestRevisi->jenis_pajak;
                        $kondisiPajakFinal = $latestRevisi->kondisi_pajak;
                    } else {
                        foreach ($record->items as $item) {
                            $survey = $item->surveiHargas->where('nama_vendor', $finalVendor->nama_vendor)->first();
                            if ($survey) {
                                $displayTotalBarang += ($survey->harga * $item->kuantitas);
                                $displayTotalPajak += $survey->nominal_pajak ?? 0;
                                if (!$displayJenisPajak && $survey->jenis_pajak) {
                                    $displayJenisPajak = $survey->jenis_pajak;
                                }
                                // Ambil kondisi pajak dari item pertama yang punya kondisi selain "Tidak Ada Pajak"
                                if ($kondisiPajakFinal === 'Tidak Ada Pajak' && $survey->kondisi_pajak !== 'Tidak Ada Pajak') {
                                    $kondisiPajakFinal = $survey->kondisi_pajak;
                                }
                            }
                        }
                    }

                    // [PERBAIKAN] Logika baru untuk menentukan visibilitas field bukti pajak
                    $adaPajak = $kondisiPajakFinal !== 'Tidak Ada Pajak';
                    $pajakDitanggungKita = in_array($kondisiPajakFinal, ['Pajak ditanggung BPRS', 'Pajak ditanggung kita', 'Pajak ditanggung Perusahaan (Exclude)']);

                    $isDpStage = $finalVendor->opsi_pembayaran === 'Bisa DP' && !$finalVendor->bukti_dp;
                    $isPelunasanStage = ($finalVendor->opsi_pembayaran === 'Langsung Lunas' && !$finalVendor->bukti_pelunasan) ||
                        ($finalVendor->opsi_pembayaran === 'Bisa DP' && $finalVendor->bukti_dp && !$finalVendor->bukti_pelunasan);
                    $isPaid = (bool)$finalVendor->bukti_pelunasan;

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
                                $taxCost = $survey->kondisi_pajak === 'Pajak ditanggung BPRS' ? ($survey->nominal_pajak ?? 0) : 0;
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
                            if ($survey->kondisi_pajak === 'Pajak ditanggung BPRS') {
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
                    $estimasiBiaya = $getScenarioDetails($record->items);

                    $isDpStage = $finalVendor->opsi_pembayaran === 'Bisa DP' && !$finalVendor->bukti_dp;
                    $isPelunasanStage = !$isDpStage;

                    return [
                        Section::make('Detail Vendor & Rekening')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('nama_vendor')->default($finalVendor->nama_vendor)->disabled(),
                                    TextInput::make('nama_bank')->default($finalVendor->nama_bank)->disabled(),
                                    TextInput::make('no_rekening')->default($finalVendor->no_rekening)->disabled(),
                                    TextInput::make('nama_rekening')->label('Atas Nama')->default($finalVendor->nama_rekening)->disabled(),
                                ]),
                            ])
                            ->collapsible()
                            ->visible($metodePembayaran === 'transfer'),

                        Section::make('Rincian Pembayaran')->schema([
                            Grid::make(3)->schema([
                                TextInput::make('metode_pembayaran')->label('Metode Bayar')->default($finalVendor->metode_pembayaran)->disabled(),
                                TextInput::make('opsi_pembayaran')->label('Opsi Bayar')->default($finalVendor->opsi_pembayaran)->disabled(),
                                TextInput::make('total_nilai_barang')->label('Total Nilai Barang')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->default($displayTotalBarang)->disabled(),

                                TextInput::make('nominal_dp')
                                    ->label($isDpStage ? 'Nominal DP (Belum Dibayar)' : 'Nominal DP (Sudah Dibayar)')
                                    ->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                    ->default($finalVendor->nominal_dp)
                                    ->disabled()
                                    ->visible($finalVendor->opsi_pembayaran === 'Bisa DP'),

                                TextInput::make('nominal_pelunasan')
                                    ->label('Nominal Pelunasan (Ke Vendor)')
                                    ->helperText('Nominal yang dibayarkan ke vendor, di luar pajak.')
                                    ->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                    ->default(function () use ($displayTotalBarang, $finalVendor) {
                                        // [PERBAIKAN] Pelunasan ke vendor tidak termasuk pajak
                                        if ($finalVendor->opsi_pembayaran === 'Bisa DP') {
                                            return $displayTotalBarang - ($finalVendor->nominal_dp ?? 0);
                                        }
                                        return $displayTotalBarang;
                                    })
                                    ->disabled(),
                                // [PERUBAHAN] Tampilan Pajak Dinamis
                                TextInput::make('total_pajak')
                                    ->label(function () use ($kondisiPajakFinal) {
                                        return str_contains($kondisiPajakFinal, 'Include') ? 'Total Pajak (Include)' : 'Total Pajak (Exclude)';
                                    })
                                    ->helperText(function () use ($kondisiPajakFinal) {
                                        return str_contains($kondisiPajakFinal, 'Include') ? '(Sudah termasuk dalam total nilai barang)' : null;
                                    })
                                    ->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                    ->default($displayTotalPajak)->disabled()->visible($adaPajak),

                                Placeholder::make('total_biaya_final')
                                    ->label('TOTAL BIAYA FINAL (Disetujui)')
                                    ->content(function () use ($displayTotalBarang, $displayTotalPajak, $pajakDitanggungKita): HtmlString {
                                        // [PERBAIKAN] Total biaya selalu menjumlahkan pajak jika exclude
                                        $total = $displayTotalBarang + ($pajakDitanggungKita ? $displayTotalPajak : 0);
                                        return new HtmlString('<b class="text-xl text-primary-600">Rp ' . number_format($total, 0, ',', '.') . '</b>');
                                    }),
                            ]),
                        ]),

                        Section::make('Upload Bukti Pembayaran')->schema([
                            FileUpload::make('bukti_dp')
                                ->label('Upload Bukti DP')
                                ->required()
                                ->disk('private')
                                ->directory(fn(Pengajuan $record) => $record->getStorageDirectory())
                                ->getUploadedFileNameForStorageUsing(fn(UploadedFile $file) => $record->generateUniqueFileName("bukti_dp", $file))
                                ->visible($isDpStage), // <-- Tampil HANYA jika di tahap DP

                            FileUpload::make('bukti_pelunasan')
                                ->label('Upload Bukti Pelunasan')
                                ->required()
                                ->disk('private')
                                ->directory(fn(Pengajuan $record) => $record->getStorageDirectory())
                                ->getUploadedFileNameForStorageUsing(fn(UploadedFile $file) => $record->generateUniqueFileName("bukti_pelunasan", $file))
                                ->visible($isPelunasanStage), // <-- Tampil HANYA jika di tahap Pelunasan

                            FileUpload::make('bukti_pajak')
                                ->label('Upload Bukti Bayar Pajak')
                                ->helperText('Wajib diisi jika ada pajak (Include/Exclude).')
                                ->disk('private')
                                ->directory(fn(Pengajuan $record) => $record->getStorageDirectory())
                                ->getUploadedFileNameForStorageUsing(fn(UploadedFile $file) => $record->generateUniqueFileName("bukti_pajak", $file))
                                ->visible($isPelunasanStage && $adaPajak)
                                ->required($adaPajak),

                            Placeholder::make('payment_complete_info')
                                ->label('Status')
                                ->content('Pembayaran untuk pengajuan ini telah Lunas.')
                                ->visible($isPaid), // <-- Tampil jika sudah lunas semua
                        ]),
                    ];
                })
                ->action(function (array $data, Pengajuan $record) {
                    $finalVendor = $record->vendorPembayaran->where('is_final', true)->first();
                    if (!$finalVendor) {
                        Notification::make()->title('Error')->body('Vendor final tidak ditemukan.')->danger()->send();
                        return;
                    }

                    $catatanTambahan = '';
                    $newStatus = $record->status;
                    $notificationTitle = '';

                    // Jika ini adalah tahap pembayaran DP
                    if (isset($data['bukti_dp'])) {
                        $finalVendor->update([
                            'bukti_dp' => $data['bukti_dp'],
                            'tanggal_dp_aktual' => now(),
                        ]);
                        $newStatus = Pengajuan::STATUS_MENUNGGU_PELUNASAN;
                        $catatanTambahan = "\n\n[Pembayaran DP oleh " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                        $notificationTitle = 'Pembayaran DP berhasil disimpan.';
                    }

                    // Tahap Pelunasan
                    if (isset($data['bukti_pelunasan'])) {
                        $updateData = [
                            'bukti_pelunasan' => $data['bukti_pelunasan'],
                            'tanggal_pelunasan_aktual' => now(),
                        ];

                        // Simpan bukti_pajak jika diupload
                        if (isset($data['bukti_pajak'])) {
                            $updateData['bukti_pajak'] = $data['bukti_pajak'];
                        }

                        $finalVendor->update($updateData);
                        $newStatus = Pengajuan::STATUS_SUDAH_BAYAR;
                        // $catatanTambahan = "\n\n[Pelunasan oleh " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                        $notificationTitle = 'Pelunasan berhasil disimpan.';
                    }

                    $record->update([
                        'status' => $newStatus,
                        // 'catatan_revisi' => trim(($record->catatan_revisi ?? '') . $catatanTambahan),
                        'disbursed_by' => Auth::id(),
                        'disbursed_at' => now(),
                    ]);

                    Notification::make()->title($notificationTitle)->success()->send();
                })
                ->visible(fn(Pengajuan $record) => in_array($record->status, [
                    Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                    Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                ])),

            Action::make('download_spm')
                ->label('SPM')
                ->color('info')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (Pengajuan $record) {
                    $finalVendor = $record->vendorPembayaran()->where('is_final', true)->first();
                    if (!$finalVendor) {
                        Notification::make()->title('Gagal Mencetak SPM')->body('Vendor final untuk pengajuan ini belum ditentukan.')->danger()->send();
                        return;
                    }
                    $record->load('approverDirUtama', 'approverDirOps', 'approverKadivGa', 'disbursedBy');

                    $itemsOriginal = [];
                    $totalNilaiBarangOriginal = 0;
                    $totalPajakOriginal = 0;
                    $taxConditionOriginal = 'Tidak Ada Pajak';
                    $taxTypeOriginal = null;

                    foreach ($record->items as $item) {
                        $survey = $item->surveiHargas->where('nama_vendor', $finalVendor->nama_vendor)->first();
                        $hargaSatuan = $survey->harga ?? 0;
                        $subtotal = $hargaSatuan * $item->kuantitas;
                        $itemsOriginal[] = [
                            'barang' => $item->nama_barang,
                            'kuantitas' => $item->kuantitas,
                            'harga' => $hargaSatuan,
                            'subtotal' => $subtotal,
                        ];
                        $totalNilaiBarangOriginal += $subtotal;

                        if ($survey) {
                            $isTaxExclude = in_array($survey->kondisi_pajak, ['Pajak ditanggung Perusahaan (Exclude)', 'Pajak ditanggung kita', 'Pajak ditanggung BPRS']);
                            if ($isTaxExclude) {
                                $totalPajakOriginal += $survey->nominal_pajak ?? 0;
                                $taxConditionOriginal = 'Pajak ditanggung Perusahaan (Exclude)';
                                if (!$taxTypeOriginal) $taxTypeOriginal = $survey->jenis_pajak;
                            } elseif ($survey->kondisi_pajak === 'Pajak ditanggung Vendor (Include)') {
                                $totalPajakOriginal += $survey->nominal_pajak ?? 0;
                                $taxConditionOriginal = 'Pajak ditanggung Vendor (Include)';
                                if (!$taxTypeOriginal) $taxTypeOriginal = $survey->jenis_pajak;
                            }
                        }
                    }

                    $totalBiayaOriginal = $totalNilaiBarangOriginal;
                    if ($taxConditionOriginal === 'Pajak ditanggung Perusahaan (Exclude)') {
                        $totalBiayaOriginal += $totalPajakOriginal;
                    }

                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                    $isRevisi = !is_null($latestRevisi);
                    $revisionDetails = null;
                    $taxTypeFinal = $taxTypeOriginal;
                    $taxConditionFinal = $taxConditionOriginal;

                    if ($isRevisi) {
                        $totalNilaiBarangFinal = $latestRevisi->harga_revisi;
                        $totalPajakFinal = $latestRevisi->nominal_pajak;
                        $totalFinal = $totalNilaiBarangFinal + $totalPajakFinal;
                        $taxTypeFinal = $latestRevisi->jenis_pajak;
                        $taxConditionFinal = $latestRevisi->kondisi_pajak;
                        $revisionDetails = [
                            'selisih_total' => $totalFinal - $totalBiayaOriginal,
                            'alasan_revisi' => $latestRevisi->alasan_revisi,
                            'tanggal_revisi' => Carbon::parse($latestRevisi->tanggal_revisi)->translatedFormat('d F Y'),
                        ];
                    } else {
                        $totalNilaiBarangFinal = $totalNilaiBarangOriginal;
                        $totalPajakFinal = $totalPajakOriginal;
                        $totalFinal = $totalBiayaOriginal;
                    }

                    // [PERBAIKAN] Menggunakan helper function untuk generate QR Code
                    $generateQrCode = function ($user) use ($record) {
                        if (!$user) return null;
                        $url = URL::signedRoute('approval.verify', ['pengajuan' => $record, 'user' => $user]);
                        $qrCodeData = QrCode::format('png')->size(80)->margin(1)->generate($url);
                        return 'data:image/png;base64,' . base64_encode($qrCodeData);
                    };

                    $direktur = $record->approverDirUtama ?? $record->approverDirOps;
                    $direkturJabatan = $record->approverDirUtama ? 'Direktur Utama' : 'Direktur Operasional';


                    $data = [
                        'kode_pengajuan' => $record->kode_pengajuan,
                        'tanggal_pengajuan' => $record->created_at->translatedFormat('d F Y'),
                        'pemohon' => $record->pemohon->nama_user,
                        'divisi' => $record->pemohon->divisi->nama_divisi,
                        'items_original' => $itemsOriginal,
                        'total_nilai_barang_original' => $totalNilaiBarangOriginal,
                        'total_pajak_original' => $totalPajakOriginal,
                        'tax_condition_original' => $taxConditionOriginal,
                        'tax_type_original' => $taxTypeOriginal,
                        'total_biaya_original' => $totalBiayaOriginal,
                        'total_nilai_barang_final' => $totalNilaiBarangFinal,
                        'total_pajak_final' => $totalPajakFinal,
                        'tax_condition_final' => $taxConditionFinal,
                        'tax_type_final' => $taxTypeFinal,
                        'total_final' => $totalFinal,
                        'is_revisi' => $isRevisi,
                        'revision_details' => $revisionDetails,
                        'payment_details' => [
                            'vendor' => $finalVendor->nama_vendor,
                            'metode_pembayaran' => $finalVendor->metode_pembayaran,
                            'opsi_pembayaran' => $finalVendor->opsi_pembayaran,
                            'nama_bank' => $finalVendor->nama_bank,
                            'no_rekening' => $finalVendor->no_rekening,
                            'nama_rekening' => $finalVendor->nama_rekening,
                            'nominal_dp' => $finalVendor->nominal_dp,
                            'tanggal_dp' => $finalVendor->tanggal_dp ? Carbon::parse($finalVendor->tanggal_dp)->translatedFormat('d F Y') : '-',
                            'tanggal_dp_aktual' => $finalVendor->tanggal_dp_aktual ? Carbon::parse($finalVendor->tanggal_dp_aktual)->translatedFormat('d F Y') : null,
                            'tanggal_pelunasan' => $finalVendor->tanggal_pelunasan ? Carbon::parse($finalVendor->tanggal_pelunasan)->translatedFormat('d F Y') : '-',
                            'tanggal_pelunasan_aktual' => $finalVendor->tanggal_pelunasan_aktual ? Carbon::parse($finalVendor->tanggal_pelunasan_aktual)->translatedFormat('d F Y') : null,
                        ],
                        'kadivGaName' => $record->approverKadivGa?->nama_user ?? '(Kadiv GA)',
                        'direkturName' => $direktur?->nama_user,
                        'direkturJabatan' => $direkturJabatan,
                        'disbursedByName' => $record->disbursedBy?->nama_user,
                        'kadivGaQrCode' => $generateQrCode($record->approverKadivGa),
                        'direkturQrCode' => $generateQrCode($direktur),
                        'disbursedByQrCode' => $generateQrCode($record->disbursedBy), // <-- QR Code untuk pembayar
                        'is_paid' => !empty($finalVendor->bukti_pelunasan),
                    ];

                    $pdf = Pdf::loadView('documents.spm_template', $data);
                    $fileName = 'SPM_' . str_replace('/', '_', $record->kode_pengajuan) . '.pdf';
                    return response()->streamDownload(fn() => print($pdf->output()), $fileName);
                })
            // ->visible(fn(Pengajuan $record): bool => $record->status !== Pengajuan::STATUS_SELESAI),
        ];
    }
}
