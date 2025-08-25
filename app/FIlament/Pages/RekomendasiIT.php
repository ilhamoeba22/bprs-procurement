<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Components\RevisiTimelineSection;
use App\Filament\Components\StandardDetailSections;

class RekomendasiIT extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static string $view = 'filament.pages.rekomendasi-i-t';
    protected static ?string $navigationLabel = 'Rekomendasi IT';
    protected static ?int $navigationSort = 5;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Rekomendasi IT)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Kepala Divisi IT', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        return Pengajuan::query()
            ->where('status', Pengajuan::STATUS_REKOMENDASI_IT)
            ->orWhere('it_recommended_by', $user->id_user);
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


            Action::make('submit_rekomendasi')
                ->label('Submit Rekomendasi')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalWidth('3xl')
                ->modalHeading('Konfirmasi Submit Rekomendasi')
                ->modalDescription('Apakah Anda yakin ingin menyubmit rekomendasi ini?')
                ->modalSubmitActionLabel('Ya, Submit')
                ->form(function (Forms\Form $form, Pengajuan $record): array {
                    // Fetch only IT items for display
                    $itItems = $record->items->filter(function ($item) {
                        return $item->kategori_barang === 'Barang IT';
                    })->map(function ($item) {
                        return [
                            'nama_barang' => $item->nama_barang,
                            'kuantitas' => $item->kuantitas,
                            'spesifikasi' => $item->spesifikasi,
                            'justifikasi' => $item->justifikasi,
                        ];
                    })->toArray();

                    return [
                        // New section to display IT items at the top of the modal
                        Section::make('Barang IT yang Diajukan')
                            ->description('Berikut adalah detail barang IT yang memerlukan rekomendasi.')
                            ->schema([
                                Repeater::make('it_items')
                                    ->label('Daftar Barang IT')
                                    ->schema([
                                        Forms\Components\TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
                                        Forms\Components\TextInput::make('kuantitas')->label('Kuantitas')->disabled(),
                                        Forms\Components\Textarea::make('spesifikasi')->label('Spesifikasi')->disabled(),
                                        Forms\Components\Textarea::make('justifikasi')->label('Justifikasi')->disabled(),
                                    ])
                                    ->default($itItems)
                                    ->disabled()
                                    ->collapsible()
                                    ->columnSpanFull(),
                            ]),

                        // Existing form fields for recommendation
                        Forms\Components\Select::make('rekomendasi_it_tipe')->label('Tipe Rekomendasi')->options(['Pembelian Baru' => 'Pembelian Baru', 'Perbaikan' => 'Perbaikan'])->required(),
                        Forms\Components\Textarea::make('rekomendasi_it_catatan')->label('Catatan Rekomendasi')->required(),
                    ];
                })
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'rekomendasi_it_tipe' => $data['rekomendasi_it_tipe'],
                        'rekomendasi_it_catatan' => $data['rekomendasi_it_catatan'],
                        'status' => Pengajuan::STATUS_SURVEI_GA,
                        'it_recommended_by' => Auth::id(),
                        'it_recommended_at' => now(),
                    ]);
                    Notification::make()->title('Rekomendasi berhasil disubmit')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_REKOMENDASI_IT),
        ];
    }
}
