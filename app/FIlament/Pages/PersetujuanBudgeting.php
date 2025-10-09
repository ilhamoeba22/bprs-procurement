<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
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

class PersetujuanBudgeting extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.persetujuan-budgeting';
    protected static ?string $navigationLabel = 'Persetujuan Budgeting';
    protected static ?int $navigationSort = 7;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan - Budget Control';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Tim Budgeting', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas.revisiHargas']);
        $statuses = [
            Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
            Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
            Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
        ];
        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user, $statuses) {
                $q->whereIn('status', $statuses)->orWhere('budget_approved_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', $statuses)->orWhereNotNull('budget_approved_by');
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
                    // Prioritas utama: jika user ini sudah me-review, tampilkan "Sudah Direview"
                    if ($record->budget_approved_by === Auth::id()) {
                        return 'Sudah Direview';
                    }

                    // Jika belum, tampilkan status relevan lainnya
                    return match ($record->status) {
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI
                        => 'Menunggu Aksi',
                        Pengajuan::STATUS_SELESAI => 'Pengajuan Selesai',
                        default => 'Proses Lanjut', // Untuk status setelah tahap ini
                    };
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Direview', 'Pengajuan Selesai' => 'success',
                    'Menunggu Aksi' => 'warning',
                    default => 'gray',
                }),
        ];
    }

    protected function getBudgetReviewFormSchema(Pengajuan $record): array
    {
        $items = $record->items;

        $groupedSurveys = $items->flatMap->surveiHargas->groupBy('nama_vendor');
        $vendorTotals = [];
        foreach ($groupedSurveys as $namaVendor => $surveys) {
            $vendorTotal = 0;
            $allItemsCovered = $items->every(function ($item) use ($surveys) {
                return $surveys->where('id_item', $item->id_item)->isNotEmpty();
            });

            if (!$allItemsCovered) {
                continue;
            }

            foreach ($items as $item) {
                $survey = $surveys->where('id_item', $item->id_item)->first();
                if (!$survey) continue;
                $itemCost = $survey->harga * $item->kuantitas;
                $taxCost = $survey->kondisi_pajak === 'Pajak ditanggung BPRS' ? ($survey->nominal_pajak ?? 0) : 0;
                $vendorTotal += ($itemCost + $taxCost);
            }
            $vendorTotals[$namaVendor] = $vendorTotal;
        }

        $cheapestVendor = !empty($vendorTotals) ? array_key_first($vendorTotals) : null;
        $minTotal = !empty($vendorTotals) ? min($vendorTotals) : null;
        if ($cheapestVendor && $minTotal) {
            foreach ($vendorTotals as $vendor => $total) {
                if ($total === $minTotal) {
                    $cheapestVendor = $vendor;
                    break;
                }
            }
        }

        $details = [];
        $totalCost = 0;
        $nominalDp = 0;
        if ($cheapestVendor) {
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
        }

        $estimasiBiaya = empty($details) ? null : [
            'details' => $details,
            'total' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
            'nominal_dp' => $nominalDp > 0 ? 'Rp ' . number_format($nominalDp, 0, ',', '.') : 'Tidak ada DP'
        ];

        return [
            Section::make('Rincian Estimasi Biaya')
                ->schema([
                    Repeater::make('estimasi_biaya.details')
                        ->label('')
                        ->schema([
                            Grid::make(4)->schema([
                                TextInput::make('nama_barang')->label('Item')->disabled(),
                                TextInput::make('tipe_survei')->label('Kategori')->disabled(),
                                TextInput::make('harga_vendor')->label('Harga dari Vendor')->disabled(),
                                TextInput::make('pajak_info')->label('Pajak')->disabled(),
                            ])
                        ])->disabled()->disableItemCreation()->disableItemDeletion()->disableItemMovement(),
                    Grid::make(2)->schema([
                        Placeholder::make('estimasi_biaya.total')
                            ->label('TOTAL ESTIMASI BIAYA')
                            ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . ($get('estimasi_biaya.total') ?? 'Rp 0') . '</b>')),
                        Placeholder::make('estimasi_biaya.nominal_dp')
                            ->label('NOMINAL DP')
                            ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . ($get('estimasi_biaya.nominal_dp') ?? 'Tidak ada DP') . '</b>')),
                    ]),
                ])->collapsible()->collapsed()
                ->visible(fn() => !is_null($estimasiBiaya) && !empty($estimasiBiaya['details'])),

            Section::make('Review Budget')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('status_budget')
                            ->label('Status Budget')
                            ->options([
                                'Budget Tersedia' => 'Budget Tersedia',
                                'Budget Habis' => 'Budget Habis',
                                'Budget Tidak Ada di RBB' => 'Budget Tidak Ada di RBB',
                            ])
                            ->required()
                            ->reactive(),
                        Textarea::make('catatan_budget')
                            ->label('Catatan Budget')
                            ->required()
                            ->rows(4),
                    ]),
                ])->collapsible(),
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

                    // dd($formData);

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


            Action::make('submit_budget_review')
                ->label('Submit Review Budget')
                ->color('primary')
                ->icon('heroicon-o-pencil-square')
                ->modalHeading('Form Review Budget')
                ->form(fn(Pengajuan $record) => $this->getBudgetReviewFormSchema($record))
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $form->fill([
                        'estimasi_biaya' => $this->getScenarioDetails($record),
                    ]);
                })
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'status_budget' => $data['status_budget'],
                        'catatan_budget' => $data['catatan_budget'],
                        'budget_approved_by' => Auth::id(),
                        'budget_approved_at' => now(),
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                    ]);
                    Notification::make()->title('Review budget berhasil disubmit')->body('Pengajuan diteruskan ke Kepala Divisi Operasional.')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET),

            Action::make('edit_budget_review')
                ->label('Edit Review Budget')
                ->color('warning')
                ->icon('heroicon-o-pencil')
                ->modalHeading('Edit Review Budget')
                ->form(fn(Pengajuan $record) => $this->getBudgetReviewFormSchema($record))
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $form->fill([
                        'estimasi_biaya' => $this->getScenarioDetails($record),
                        'status_budget' => $record->status_budget,
                        'catatan_budget' => $record->catatan_budget,
                    ]);
                })
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'status_budget' => $data['status_budget'],
                        'catatan_budget' => $data['catatan_budget'],
                        'budget_approved_at' => now(),
                    ]);
                    Notification::make()->title('Review budget berhasil diupdate')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET),

            Action::make('submit_revisi_budget_review')
                ->label('Submit Review Budget (Revisi)')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->modalHeading('Form Review Budget (Revisi)')
                ->form([
                    Section::make('Form Review Budget untuk Revisi')->schema([
                        Grid::make(2)->schema([
                            Select::make('revisi_budget_status_pengadaan')
                                ->label('Status Budget Revisi')
                                ->options(['Budget Tersedia' => 'Budget Tersedia', 'Budget Habis' => 'Budget Habis', 'Budget Tidak Ada di RBB' => 'Budget Tidak Ada di RBB'])
                                ->required(),
                            Textarea::make('revisi_budget_catatan_pengadaan')
                                ->label('Catatan Budget Revisi')
                                ->required()->rows(4),
                        ]),
                    ]),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                    if (!$latestRevisi) {
                        Notification::make()->title('Error')->body('Data revisi aktif tidak ditemukan.')->danger()->send();
                        return;
                    }
                    $latestRevisi->update([
                        'revisi_budget_status_pengadaan' => $data['revisi_budget_status_pengadaan'],
                        'revisi_budget_catatan_pengadaan' => $data['revisi_budget_catatan_pengadaan'],
                        'revisi_budget_approved_by' => Auth::id(),
                    ]);
                    $record->update([
                        'budget_approved_at' => now(),
                        'status' => Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS
                    ]);
                    Notification::make()->title('Review budget revisi berhasil disubmit')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI),
        ];
    }

    private function getScenarioDetails(Pengajuan $record): ?array
    {
        $record->loadMissing(['items.surveiHargas', 'vendorPembayaran']);
        $items = $record->items;
        $groupedSurveys = $items->flatMap->surveiHargas->groupBy('nama_vendor');

        if ($groupedSurveys->isEmpty()) return null;

        $vendorTotals = [];
        foreach ($groupedSurveys as $namaVendor => $surveys) {
            $allItemsCovered = $items->every(fn($item) => $surveys->where('id_item', $item->id_item)->isNotEmpty());
            if (!$allItemsCovered) continue;

            $vendorTotal = 0;
            foreach ($items as $item) {
                $survey = $surveys->where('id_item', $item->id_item)->first();
                if (!$survey) continue;
                $itemCost = $survey->harga * $item->kuantitas;
                $taxCost = $survey->kondisi_pajak === 'Pajak ditanggung Perusahaan (Exclude)' ? ($survey->nominal_pajak ?? 0) : 0;
                $vendorTotal += ($itemCost + $taxCost);
            }
            $vendorTotals[$namaVendor] = $vendorTotal;
        }

        if (empty($vendorTotals)) return null;

        $minTotal = min($vendorTotals);
        $cheapestVendorName = array_search($minTotal, $vendorTotals);

        $details = [];
        $totalCost = 0;
        $nominalDp = 0;
        $cheapestSurveys = $groupedSurveys[$cheapestVendorName] ?? collect();

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

        $vendorPembayaran = $record->vendorPembayaran->where('nama_vendor', $cheapestVendorName)->first();
        if ($vendorPembayaran && $vendorPembayaran->nominal_dp > 0) {
            $nominalDp = $vendorPembayaran->nominal_dp;
        }

        return [
            'details' => $details,
            'total' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
            'nominal_dp' => $nominalDp > 0 ? 'Rp ' . number_format($nominalDp, 0, ',', '.') : 'Tidak ada DP',
            'total_cost_raw' => $totalCost,
        ];
    }

    private function getInitialTax(Pengajuan $record, ?string $vendorName): float
    {
        if (!$vendorName) return 0;
        $totalPajakAwal = 0;
        foreach ($record->items as $item) {
            $survey = $item->surveiHargas
                ->where('nama_vendor', $vendorName)
                ->where('kondisi_pajak', 'Pajak ditanggung Perusahaan (Exclude)')
                ->first();
            if ($survey) {
                $totalPajakAwal += $survey->nominal_pajak;
            }
        }
        return $totalPajakAwal;
    }
}
