<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\VendorPembayaran;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Radio;
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
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Components\RevisiTimelineSection;
use App\Filament\Components\StandardDetailSections;

class PersetujuanKepalaDivisiGA extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static string $view = 'filament.pages.persetujuan-kepala-divisi-g-a';
    protected static ?string $navigationLabel = 'Persetujuan Kadiv GA';
    protected static ?int $navigationSort = 9;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Kadiv GA)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Kepala Divisi GA', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas.revisiHargas', 'vendorPembayaran']);

        $statusesForAction = [
            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI,
        ];

        if ($user && !$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user, $statusesForAction) {
                $q->whereIn('status', $statusesForAction)
                    ->orWhere(function (Builder $subq) use ($user) {
                        $subq->where('status', Pengajuan::STATUS_MENUNGGU_PELUNASAN)
                            ->whereNotNull('kadiv_ga_approved_by')
                            ->where('kadiv_ga_approved_by', $user->id_user);
                    })->orWhere('kadiv_ga_approved_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', array_merge($statusesForAction, [
                Pengajuan::STATUS_MENUNGGU_PELUNASAN,
            ]))->orWhereNotNull('kadiv_ga_approved_by');
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
                ->state(function (Pengajuan $record): string {
                    // Prioritas utama: jika user ini sudah memproses, tampilkan "Sudah Diproses"
                    if ($record->kadiv_ga_approved_by === Auth::id()) {
                        return 'Sudah Diproses';
                    }

                    // Jika belum, tampilkan status relevan lainnya
                    return match ($record->status) {
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI
                        => 'Menunggu Aksi',
                        Pengajuan::STATUS_SELESAI => 'Pengajuan Selesai',
                        default => 'Proses Lanjut',
                    };
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Diproses', 'Pengajuan Selesai' => 'success',
                    'Menunggu Aksi' => 'warning',
                    default => 'gray',
                }),
        ];
    }

    private function getSummarySchema(): array
    {
        return [
            Section::make('Ringkasan Pengajuan')
                ->schema([
                    Repeater::make('items')->relationship()->label('Barang yang Diajukan')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('nama_barang')->disabled(),
                                TextInput::make('kuantitas')->disabled(),
                            ])
                        ])->disabled()->columns(1),
                    Grid::make(2)->schema([
                        Placeholder::make('estimasi_biaya.total')
                            ->label('TOTAL ESTIMASI BIAYA')
                            ->content(fn($get) => new HtmlString('<b class="text-lg text-primary-600">' . ($get('estimasi_biaya.total') ?? 'Rp 0') . '</b>')),
                        Placeholder::make('estimasi_biaya.nominal_dp')
                            ->label('NOMINAL DP')
                            ->content(fn($get) => new HtmlString('<b class="text-lg text-primary-600">' . ($get('estimasi_biaya.nominal_dp') ?? 'Tidak ada DP') . '</b>')),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('status_budget')->label('Status Budget')->disabled(),
                        Textarea::make('catatan_budget')->label('Catatan Budget')->disabled(),
                    ]),
                ])
                ->collapsible()->collapsed(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading(fn(Pengajuan $record): string => "Detail Pengajuan {$record->kode_pengajuan}")
                ->modalWidth('4xl')
                ->mountUsing(function (Form $form, Pengajuan $record) {
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
                    $formData['budget_approved_by_name'] = $record->approverBudget?->nama_user;
                    $formData['kadiv_ops_budget_approved_by_name'] = $record->validatorBudgetOps?->nama_user;
                    $formData['kadiv_ga_approved_by_name'] = $record->approverKadivGa?->nama_user;
                    $formData['direktur_operasional_approved_by_name'] = $record->approverDirOps?->nama_user;
                    $formData['direktur_utama_approved_by_name'] = $record->approverDirUtama?->nama_user;

                    $getScenarioDetails = function ($items) use ($record) {
                        $details = [];
                        $totalCost = 0;
                        $nominalDp = 0;
                        $groupedSurveys = $items->flatMap->surveiHargas->groupBy('nama_vendor');
                        if ($groupedSurveys->isEmpty()) {
                            return [
                                'details' => [],
                                'total' => 'Rp 0',
                                'nominal_dp' => 'Tidak ada DP'
                            ];
                        }

                        $vendorTotals = [];
                        foreach ($groupedSurveys as $namaVendor => $surveys) {
                            $vendorTotal = 0;
                            $allItemsCovered = true;
                            foreach ($items as $item) {
                                $survey = $surveys->where('id_item', $item->id_item)->first();
                                if (!$survey) {
                                    $allItemsCovered = false;
                                    break;
                                }
                                $itemCost = $survey->harga * $item->kuantitas;
                                $taxCost = $survey->kondisi_pajak === 'Pajak ditanggung Perusahaan (Exclude)' ? ($survey->nominal_pajak ?? 0) : 0;
                                $vendorTotal += ($itemCost + $taxCost);
                            }
                            if ($allItemsCovered) {
                                $vendorTotals[$namaVendor] = $vendorTotal;
                            }
                        }

                        if (empty($vendorTotals)) {
                            return [
                                'details' => [],
                                'total' => 'Rp 0',
                                'nominal_dp' => 'Tidak ada DP'
                            ];
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
                            if ($survey->kondisi_pajak === 'Pajak ditanggung Perusahaan (Exclude)') {
                                $taxCost = $survey->nominal_pajak ?? 0;
                                $taxInfo = ($survey->jenis_pajak ?? 'Pajak') . ': Rp ' . number_format($taxCost, 0, ',', '.');
                            } elseif ($survey->kondisi_pajak === 'Pajak ditanggung Vendor (Include)') {
                                $taxInfo = ($survey->jenis_pajak ?? 'Pajak') . ': Included';
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

                        return [
                            'details' => $details,
                            'total' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
                            'nominal_dp' => $nominalDp > 0 ? 'Rp ' . number_format($nominalDp, 0, ',', '.') : 'Tidak ada DP'
                        ];
                    };
                    $formData['estimasi_biaya'] = $getScenarioDetails($record->items);

                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                    if ($latestRevisi) {
                        $finalVendor = $record->vendorPembayaran->where('is_final', true)->first();
                        $totalBiayaAwal = $latestRevisi->harga_awal;
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

                    $form->fill($formData);
                })
                ->form([
                    ...StandardDetailSections::make(),
                    RevisiTimelineSection::make(),
                ]),


            Action::make('process_decision')
                ->label('Proses Keputusan')
                ->color('primary')
                ->icon('heroicon-o-check-circle')
                ->form([
                    ...$this->getSummarySchema(), // <-- Menambahkan section ringkasan
                    Section::make('Form Keputusan')->schema([
                        Radio::make('keputusan_final')
                            ->label('Persetujuan Final')
                            ->options(['Setuju' => 'Setuju', 'Tolak' => 'Tolak'])
                            ->required(),
                        Textarea::make('kadiv_ga_catatan')
                            ->label('Catatan Keputusan (Wajib diisi)')
                            ->required(),
                    ]),
                ])
                ->mountUsing(function (Form $form, Pengajuan $record): void {
                    // mountUsing untuk mengisi data ringkasan
                    $formData = $record->toArray();
                    $getScenarioDetails = function ($items) use ($record) {
                        $details = [];
                        $totalCost = 0;
                        $nominalDp = 0;
                        $groupedSurveys = $items->flatMap->surveiHargas->groupBy('nama_vendor');
                        if ($groupedSurveys->isEmpty()) {
                            return [
                                'details' => [],
                                'total' => 'Rp 0',
                                'nominal_dp' => 'Tidak ada DP'
                            ];
                        }

                        $vendorTotals = [];
                        foreach ($groupedSurveys as $namaVendor => $surveys) {
                            $vendorTotal = 0;
                            $allItemsCovered = true;
                            foreach ($items as $item) {
                                $survey = $surveys->where('id_item', $item->id_item)->first();
                                if (!$survey) {
                                    $allItemsCovered = false;
                                    break;
                                }
                                $itemCost = $survey->harga * $item->kuantitas;
                                $taxCost = $survey->kondisi_pajak === 'Pajak ditanggung Perusahaan (Exclude)' ? ($survey->nominal_pajak ?? 0) : 0;
                                $vendorTotal += ($itemCost + $taxCost);
                            }
                            if ($allItemsCovered) {
                                $vendorTotals[$namaVendor] = $vendorTotal;
                            }
                        }

                        if (empty($vendorTotals)) {
                            return [
                                'details' => [],
                                'total' => 'Rp 0',
                                'nominal_dp' => 'Tidak ada DP'
                            ];
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
                            if ($survey->kondisi_pajak === 'Pajak ditanggung Perusahaan (Exclude)') {
                                $taxCost = $survey->nominal_pajak ?? 0;
                                $taxInfo = ($survey->jenis_pajak ?? 'Pajak') . ': Rp ' . number_format($taxCost, 0, ',', '.');
                            } elseif ($survey->kondisi_pajak === 'Pajak ditanggung Vendor (Include)') {
                                $taxInfo = ($survey->jenis_pajak ?? 'Pajak') . ': Included';
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

                        return [
                            'details' => $details,
                            'total' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
                            'nominal_dp' => $nominalDp > 0 ? 'Rp ' . number_format($nominalDp, 0, ',', '.') : 'Tidak ada DP'
                        ];
                    };
                    $formData['estimasi_biaya'] = $getScenarioDetails($record->items);
                    $form->fill($formData);
                })
                ->action(function (array $data, Pengajuan $record) {
                    $user = Auth::user();
                    $catatan = $record->catatan_revisi ?? '';
                    $catatan .= "\n\n[Keputusan oleh Kadiv GA: {$user->nama_user} pada " . now()->format('d-m-Y H:i') . "]\n" . $data['kadiv_ga_catatan'];

                    if ($data['keputusan_final'] === 'Tolak') {
                        $record->update([
                            'status' => Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            'kadiv_ga_decision_type' => 'Tolak',
                            'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                            // 'catatan_revisi' => trim($catatan),
                            'kadiv_ga_approved_by' => $user->id_user,
                            'kadiv_ga_approved_at' => now(),
                        ]);
                        Notification::make()->title('Pengajuan ditolak')->danger()->send();
                        return;
                    }

                    $items = $record->items;
                    $groupedSurveys = $items->flatMap->surveiHargas->groupBy('nama_vendor');
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
                        Notification::make()->title('Aksi Gagal')->body('Tidak ada vendor yang dapat memenuhi semua item.')->danger()->send();
                        return;
                    }
                    $nilaiFinal = min($vendorTotals);
                    $vendorFinalName = array_search($nilaiFinal, $vendorTotals);

                    VendorPembayaran::where('id_pengajuan', $record->id_pengajuan)->update(['is_final' => false]);
                    VendorPembayaran::updateOrCreate(
                        ['id_pengajuan' => $record->id_pengajuan, 'nama_vendor' => $vendorFinalName],
                        ['is_final' => true]
                    );

                    $budgetFinal = $record->status_budget;
                    $vendorPembayaranFinal = $record->vendorPembayaran()->where('nama_vendor', $vendorFinalName)->first();
                    $opsiPembayaranFinal = $vendorPembayaranFinal?->opsi_pembayaran;
                    $newStatus = '';

                    if ($nilaiFinal > 100000000) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA;
                    } elseif (($nilaiFinal > 5000000 && $nilaiFinal <= 100000000) || $budgetFinal === 'Budget Habis' || $budgetFinal === 'Budget Tidak Ada di RBB') {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL;
                    } else {
                        if ($opsiPembayaranFinal === 'Bisa DP') {
                            $newStatus = Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA;
                        } elseif ($opsiPembayaranFinal === 'Langsung Lunas') {
                            $newStatus = Pengajuan::STATUS_MENUNGGU_PELUNASAN;
                        } else {
                            $newStatus = Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA;
                        }
                    }

                    $record->update([
                        'total_nilai' => $nilaiFinal,
                        'kadiv_ga_decision_type' => 'Setuju',
                        'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                        // 'catatan_revisi' => trim($catatan),
                        'status' => $newStatus,
                        'kadiv_ga_approved_by' => $user->id_user,
                        'kadiv_ga_approved_at' => now(),
                    ]);

                    Notification::make()->title('Keputusan berhasil diproses')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA),

            Action::make('process_revisi_decision')
                ->label('Proses Keputusan (Revisi)')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    ...$this->getSummarySchema(), // <-- Menambahkan section ringkasan
                    Section::make('Form Keputusan Revisi')->schema([
                        Radio::make('revisi_kadiv_ga_decision_type')
                            ->label('Persetujuan Final Revisi')
                            ->options(['Disetujui' => 'Setujui Revisi', 'Ditolak' => 'Tolak Revisi'])
                            ->required(),
                        Textarea::make('revisi_kadiv_ga_catatan')
                            ->label('Catatan Keputusan (Wajib diisi)')
                            ->required(),
                    ]),
                ])
                ->mountUsing(function (Form $form, Pengajuan $record): void {
                    $formData = $record->toArray();
                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                    if ($latestRevisi) {
                        $totalCost = $latestRevisi->harga_revisi + $latestRevisi->nominal_pajak;
                        $finalVendor = $record->vendorPembayaran->where('is_final', true)->first();
                        $nominalDp = $finalVendor?->nominal_dp ?? 0;

                        $formData['estimasi_biaya'] = [
                            'total' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
                            'nominal_dp' => $nominalDp > 0 ? 'Rp ' . number_format($nominalDp, 0, ',', '.') : 'Tidak ada DP',
                        ];
                        $formData['status_budget'] = $latestRevisi->revisi_budget_status_pengadaan;
                        $formData['catatan_budget'] = $latestRevisi->revisi_budget_catatan_pengadaan;
                    }
                    $form->fill($formData);
                })
                ->action(function (array $data, Pengajuan $record) {
                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                    if (!$latestRevisi) {
                        return;
                    }

                    $latestRevisi->update([
                        'revisi_kadiv_ga_decision_type' => $data['revisi_kadiv_ga_decision_type'],
                        'revisi_kadiv_ga_catatan' => $data['revisi_kadiv_ga_catatan'],
                        'revisi_kadiv_ga_approved_by' => Auth::id(),
                    ]);

                    if ($data['revisi_kadiv_ga_decision_type'] === 'Ditolak') {
                        $record->update([
                            'status' => Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            'kadiv_ga_approved_at' => now(),
                        ]);
                        Notification::make()->title('Revisi ditolak')->danger()->send();
                        return;
                    }

                    $nilaiFinalBaru = $latestRevisi->harga_revisi + $latestRevisi->nominal_pajak;
                    $budgetFinalRevisi = $latestRevisi->revisi_budget_status_pengadaan;
                    $newStatus = '';

                    if ($nilaiFinalBaru > 100000000) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA_REVISI;
                    } elseif (($nilaiFinalBaru > 5000000 && $nilaiFinalBaru <= 100000000) || $budgetFinalRevisi === 'Budget Habis' || $budgetFinalRevisi === 'Budget Tidak Ada di RBB') {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI;
                    } else {
                        $finalVendor = $record->vendorPembayaran->where('is_final', true)->first();
                        if ($finalVendor && $finalVendor->opsi_pembayaran === 'Bisa DP' && !empty($finalVendor->bukti_dp)) {
                            $newStatus = Pengajuan::STATUS_MENUNGGU_PELUNASAN;
                        } elseif ($finalVendor && $finalVendor->opsi_pembayaran === 'Bisa DP') {
                            $newStatus = Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA_REVISI;
                        } else {
                            $newStatus = Pengajuan::STATUS_MENUNGGU_PELUNASAN;
                        }
                    }

                    $record->update([
                        'total_nilai' => $nilaiFinalBaru,
                        'status' => $newStatus,
                        'kadiv_ga_approved_at' => now(),
                    ]);

                    Notification::make()->title('Revisi berhasil disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => trim($record->status) === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI),
        ];
    }
}
