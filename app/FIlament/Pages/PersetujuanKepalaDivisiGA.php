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
use Illuminate\Support\Facades\Storage;
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
        return 'Daftar Pengajuan - Persetujuan Kadiv GA';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Kepala Divisi GA', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas.revisiHargas']);

        $statusesForAction = [
            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI,
        ];

        if ($user && !$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user, $statusesForAction) {
                $q->whereIn('status', $statusesForAction)
                    ->orWhere('kadiv_ga_approved_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', array_merge($statusesForAction, [
                Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                Pengajuan::STATUS_DITOLAK_KADIV_GA,
                // Tambahkan status lain setelah tahap ini jika perlu dilihat oleh Super Admin
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
                    if (!$record->total_nilai) {
                        $items = $record->items;
                        $groupedSurveys = $items->flatMap->surveiHargas->groupBy('nama_vendor');
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
                        return empty($vendorTotals) ? 0 : min($vendorTotals);
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
                }),

            BadgeColumn::make('status')
                ->label('Status Saat Ini')
                ->color(fn($state) => Pengajuan::getStatusBadgeColor($state)),

            BadgeColumn::make('tindakan_saya')
                ->label('Keterangan')
                ->state(function (Pengajuan $record): string {
                    if ($record->kadiv_ga_approved_by === Auth::id()) {
                        return 'Sudah Diproses';
                    }
                    return match ($record->status) {
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI
                        => 'Menunggu Aksi',
                        default => 'Proses Lanjut',
                    };
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Diproses' => 'success',
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

            Action::make('process_ga_approval')
                ->label('Proses Persetujuan')
                ->color('primary')
                ->icon('heroicon-o-check-circle')
                ->form([
                    Section::make('Validasi Hasil Survei Harga')->schema([
                        Radio::make('keputusan')
                            ->label('Keputusan')
                            ->options(['Setuju' => 'Setuju & Teruskan ke Budgeting', 'Tolak' => 'Tolak'])
                            ->required()
                            ->live(),
                        Textarea::make('kadiv_ga_catatan')
                            ->label('Catatan (Wajib diisi jika ditolak)')
                            ->required(fn($get) => $get('keputusan') === 'Tolak'),
                    ]),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $user = Auth::user();
                    if ($data['keputusan'] === 'Tolak') {
                        $record->update([
                            'status' => Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            'kadiv_ga_decision_type' => 'Tolak',
                            'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                            'kadiv_ga_approved_by' => $user->id_user,
                            'kadiv_ga_approved_at' => now(),
                        ]);
                        Notification::make()->title('Pengajuan Ditolak')->body('Hasil survei harga ditolak oleh Kepala Divisi GA.')->danger()->send();
                        return;
                    }

                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                        'kadiv_ga_decision_type' => 'Setuju',
                        'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                        'kadiv_ga_approved_by' => $user->id_user,
                        'kadiv_ga_approved_at' => now(),
                    ]);

                    Notification::make()->title('Survei Disetujui')->body('Pengajuan diteruskan ke Tim Budgeting untuk review.')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA),


            Action::make('process_ga_revisi_approval')
                ->label('Proses Persetujuan (Revisi)')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    Section::make('Validasi Hasil Survei Harga (Revisi)')->schema([
                        Radio::make('revisi_kadiv_ga_decision_type')
                            ->label('Keputusan Revisi')
                            ->options(['Disetujui' => 'Setujui Revisi & Teruskan ke Budgeting', 'Ditolak' => 'Tolak Revisi'])
                            ->required(),
                        Textarea::make('revisi_kadiv_ga_catatan')
                            ->label('Catatan Keputusan (Wajib diisi jika ditolak)')
                            ->required(fn($get) => $get('revisi_kadiv_ga_decision_type') === 'Ditolak'),
                    ]),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                    if (!$latestRevisi) {
                        Notification::make()->title('Error')->body('Data revisi aktif tidak ditemukan.')->danger()->send();
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
                        Notification::make()->title('Revisi Ditolak')->danger()->send();
                        return;
                    }

                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
                        'kadiv_ga_approved_at' => now(),
                    ]);

                    Notification::make()->title('Revisi Disetujui')->body('Pengajuan revisi diteruskan ke Tim Budgeting.')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => trim($record->status) === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI),

            Action::make('download_bukti_penawaran')
                ->label('Bukti Penawaran')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (Pengajuan $record) {
                    $record->loadMissing('items.surveiHargas');

                    $filePath = $record->items
                        ->flatMap->surveiHargas
                        ->whereNotNull('bukti_path')
                        ->pluck('bukti_path')
                        ->first();

                    if (!$filePath) {
                        Notification::make()
                            ->title('Tidak Ada Bukti')
                            ->body('Belum ada file bukti penawaran vendor yang diunggah.')
                            ->warning()
                            ->send();
                        return;
                    }

                    if (!Storage::disk('private')->exists($filePath)) {
                        Notification::make()
                            ->title('File Tidak Ditemukan')
                            ->body('File bukti penawaran tidak ditemukan di server.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $kodePengajuan = $record->kode_pengajuan ?? 'TANPA_KODE';
                    $kodePengajuan = str_replace(['/', '\\'], '-', $kodePengajuan);

                    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                    $namaFile = 'BP_' . $kodePengajuan . '.' . $ext;

                    return response()->download(Storage::disk('private')->path($filePath), $namaFile);
                }),
        ];
    }
}
