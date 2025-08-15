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

            // =================================================================
            // ACTION PEMBAYARAN DENGAN LOGIKA BARU YANG DINAMIS
            // =================================================================
            Action::make('payment')
                ->label('Pembayaran')
                ->color('success')
                ->icon('heroicon-o-currency-dollar')
                ->modalWidth('4xl')
                ->modalHeading(function (Pengajuan $record) {
                    // Judul modal dinamis berdasarkan status pembayaran
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

                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();

                    $displayTotalBarang = 0;
                    $displayTotalPajak = 0;
                    $displayJenisPajak = null;
                    $pajakDitanggungKita = false;

                    if ($latestRevisi) {
                        // JIKA ADA REVISI, GUNAKAN DATA REVISI
                        $displayTotalBarang = $latestRevisi->harga_revisi;
                        if ($latestRevisi->kondisi_pajak === 'Pajak ditanggung kita') {
                            $pajakDitanggungKita = true;
                            $displayTotalPajak = $latestRevisi->nominal_pajak;
                            $displayJenisPajak = $latestRevisi->jenis_pajak;
                        }
                    } else {
                        // JIKA TIDAK ADA REVISI, HITUNG DARI SURVEI (LOGIKA LAMA)
                        foreach ($record->items as $item) {
                            $survey = $item->surveiHargas->where('nama_vendor', $finalVendor->nama_vendor)->first();
                            if ($survey) {
                                $displayTotalBarang += ($survey->harga * $item->kuantitas);
                                if ($survey->kondisi_pajak === 'Pajak ditanggung kita') {
                                    $pajakDitanggungKita = true;
                                    $displayTotalPajak += $survey->nominal_pajak ?? 0;
                                    if (!$displayJenisPajak && $survey->jenis_pajak) {
                                        $displayJenisPajak = $survey->jenis_pajak;
                                    }
                                }
                            }
                        }
                    }

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
                    $estimasiBiaya = $getScenarioDetails($record->items);

                    $isDpStage = $finalVendor->opsi_pembayaran === 'Bisa DP' && !$finalVendor->bukti_dp;
                    $isPelunasanStage = !$isDpStage;

                    // 4. Buat form dinamis
                    return [
                        Section::make('Detail Vendor & Rekening')->schema([
                            Grid::make(3)->schema([
                                TextInput::make('nama_vendor')->default($finalVendor->nama_vendor)->disabled(),
                                TextInput::make('nama_bank')->default($finalVendor->nama_bank)->disabled(),
                                TextInput::make('no_rekening')->default($finalVendor->no_rekening)->disabled(),
                                TextInput::make('nama_rekening')->label('Atas Nama')->default($finalVendor->nama_rekening)->disabled(),
                                TextInput::make('jenis_pajak')->label('Jenis Pajak')->default($displayJenisPajak)->disabled()->visible($pajakDitanggungKita),
                            ]),
                        ])->collapsible(),

                        Section::make('Rincian Pembayaran')->schema([
                            Grid::make(3)->schema([
                                TextInput::make('total_nilai_barang')->label('Total Nilai Barang')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->default($displayTotalBarang)->disabled(),
                                TextInput::make('total_pajak')->label('Total Pajak (Ditanggung Perusahaan)')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->default($displayTotalPajak)->disabled()->visible($pajakDitanggungKita),
                                TextInput::make('opsi_pembayaran')->label('Opsi Bayar')->default($finalVendor->opsi_pembayaran)->disabled(),
                                TextInput::make('nominal_dp')
                                    ->label(function () use ($isDpStage): string {
                                        if ($isDpStage) {
                                            return 'Nominal DP (Belum Dibayar)';
                                        }
                                        return 'Nominal DP (Sudah Dibayar)';
                                    })
                                    ->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                    ->default($finalVendor->nominal_dp)
                                    ->disabled()
                                    ->visible($finalVendor->opsi_pembayaran === 'Bisa DP'),
                                TextInput::make('nominal_pelunasan')
                                    ->label('Nominal Pelunasan (Termasuk Pajak)')
                                    ->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                    ->default(function () use ($displayTotalBarang, $displayTotalPajak, $finalVendor) {
                                        $totalDenganPajak = $displayTotalBarang + $displayTotalPajak;

                                        if ($finalVendor->opsi_pembayaran === 'Bisa DP') {
                                            $dp = $finalVendor->nominal_dp ?? 0;
                                            return $totalDenganPajak - $dp;
                                        }
                                        return $totalDenganPajak;
                                    })
                                    ->disabled(),
                                Placeholder::make('total_biaya_final')
                                    ->label('TOTAL BIAYA FINAL (DISETUJUI)')
                                    ->content(function (Pengajuan $record): HtmlString {
                                        $total = $record->total_nilai ?? 0;
                                        return new HtmlString('<b class="text-xl text-primary-600">Rp ' . number_format($total, 0, ',', '.') . '</b>');
                                    }),
                            ]),

                        ]),

                        Section::make('Upload Bukti Pembayaran')->schema([
                            FileUpload::make('bukti_dp')->label('Upload Bukti DP')->disk('private')->directory('bukti-pembayaran')->visibility('private')->required()->visible($isDpStage),
                            FileUpload::make('bukti_pelunasan')->label('1. Upload Bukti Pelunasan')->disk('private')->directory('bukti-pembayaran')->visibility('private')->required()->visible($isPelunasanStage),
                            FileUpload::make('bukti_pajak')->label('2. Upload Bukti Pajak')->disk('private')->directory('bukti-pajak')->visibility('private')->required()->visible($isPelunasanStage && $pajakDitanggungKita),
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
                            'tanggal_dp' => now(),
                        ]);
                        $newStatus = Pengajuan::STATUS_MENUNGGU_PELUNASAN;
                        $catatanTambahan = "\n\n[Pembayaran DP oleh " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                        $notificationTitle = 'Pembayaran DP berhasil disimpan.';
                    }

                    // Tahap Pelunasan
                    if (isset($data['bukti_pelunasan'])) {
                        $updateData = [
                            'bukti_pelunasan' => $data['bukti_pelunasan'],
                            'tanggal_pelunasan' => now(),
                        ];

                        // Simpan bukti_pajak jika diupload
                        if (isset($data['bukti_pajak'])) {
                            $updateData['bukti_pajak'] = $data['bukti_pajak'];
                        }

                        $finalVendor->update($updateData);

                        $newStatus = Pengajuan::STATUS_SUDAH_BAYAR;
                        $catatanTambahan = "\n\n[Pelunasan oleh " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                        $notificationTitle = 'Pelunasan berhasil disimpan.';
                    }

                    $record->update([
                        'status' => $newStatus,
                        'catatan_revisi' => trim(($record->catatan_revisi ?? '') . $catatanTambahan),
                        'disbursed_by' => Auth::id(),
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

                    // 1. Hitung rincian item & total original (barang & pajak)
                    $itemsOriginal = [];
                    $totalNilaiBarangOriginal = 0;
                    $totalPajakOriginal = 0;
                    foreach ($record->items as $item) {
                        $survey = $item->surveiHargas->where('nama_vendor', $finalVendor->nama_vendor)->first();
                        $hargaSatuan = $survey->harga ?? 0;
                        $pajakItem = ($survey && $survey->kondisi_pajak === 'Pajak ditanggung kita') ? ($survey->nominal_pajak ?? 0) : 0;
                        $subtotal = $hargaSatuan * $item->kuantitas;
                        $itemsOriginal[] = [
                            'barang' => $item->nama_barang,
                            'kuantitas' => $item->kuantitas,
                            'harga' => $hargaSatuan,
                            'subtotal' => $subtotal,
                        ];
                        $totalNilaiBarangOriginal += $subtotal;
                        $totalPajakOriginal += $pajakItem;
                    }
                    $totalBiayaOriginal = $totalNilaiBarangOriginal + $totalPajakOriginal;

                    // 2. Cek revisi dan siapkan semua data final
                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                    $isRevisi = !is_null($latestRevisi);
                    $revisionDetails = null;

                    if ($isRevisi) {
                        $totalNilaiBarangFinal = $latestRevisi->harga_revisi;
                        $totalPajakFinal = $latestRevisi->nominal_pajak;
                        $totalFinal = $totalNilaiBarangFinal + $totalPajakFinal;
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

                    // 5. Siapkan data QR Code untuk verifikasi tanda tangan
                    $direkturQrCode = null;
                    $kadivGaQrCode = null;
                    $direktur = null;
                    $direkturJabatan = null;
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

                    // 3. Kumpulkan semua data yang akan dikirim ke template PDF
                    $data = [
                        'kode_pengajuan' => $record->kode_pengajuan,
                        'tanggal_pengajuan' => $record->created_at->translatedFormat('d F Y'),
                        'pemohon' => $record->pemohon->nama_user,
                        'divisi' => $record->pemohon->divisi->nama_divisi,
                        'items_original' => $itemsOriginal,
                        'total_nilai_barang_original' => $totalNilaiBarangOriginal,
                        'total_pajak_original' => $totalPajakOriginal,
                        'total_biaya_original' => $totalBiayaOriginal,

                        'total_nilai_barang_final' => $totalNilaiBarangFinal,
                        'total_pajak_final' => $totalPajakFinal,
                        'total_final' => $totalFinal,

                        'is_revisi' => $isRevisi,
                        'revision_details' => $revisionDetails,

                        // Data Pembayaran Terpusat
                        'payment_details' => [
                            'vendor' => $finalVendor->nama_vendor,
                            'metode_pembayaran' => $finalVendor->metode_pembayaran,
                            'opsi_pembayaran' => $finalVendor->opsi_pembayaran,
                            'nama_bank' => $finalVendor->nama_bank,
                            'no_rekening' => $finalVendor->no_rekening,
                            'nama_rekening' => $finalVendor->nama_rekening,
                            'nominal_dp' => $finalVendor->nominal_dp,
                            'tanggal_dp' => $finalVendor->tanggal_dp ? Carbon::parse($finalVendor->tanggal_dp)->translatedFormat('d M Y') : '-',
                            'tanggal_pelunasan' => $finalVendor->tanggal_pelunasan ? Carbon::parse($finalVendor->tanggal_pelunasan)->translatedFormat('d M Y') : '-',
                        ],

                        // Data Tanda Tangan
                        'kadivGaName' => $kadivGa?->nama_user ?? '(Kadiv GA)',
                        'direkturName' => $direktur?->nama_user,
                        'direkturJabatan' => $direkturJabatan,
                        'kadivGaQrCode' => $kadivGaQrCode,
                        'direkturQrCode' => $direkturQrCode,
                    ];

                    $pdf = Pdf::loadView('documents.spm_template', $data);
                    $fileName = 'SPM_' . str_replace('/', '_', $record->kode_pengajuan) . '.pdf';
                    return response()->streamDownload(fn() => print($pdf->output()), $fileName);
                }),
        ];
    }
}
