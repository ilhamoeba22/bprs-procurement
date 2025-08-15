<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\RevisiHarga;
use App\Models\SurveiHarga;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Radio;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
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

class PersetujuanDirekturOperasional extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static string $view = 'filament.pages.persetujuan-direktur-operasional';
    protected static ?string $navigationLabel = 'Persetujuan Direktur Operasional';
    protected static ?int $navigationSort = 10;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Direktur Operasional)';
    }

    public static function canAccess(): bool
    {
        return Auth::user() && Auth::user()->hasAnyRole(['Direktur Operasional', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon', 'pemohon.divisi']);

        if ($user && !$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->whereIn('status', [
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                    Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
                ])->orWhere(function (Builder $subq) use ($user) {
                    $subq->where('status', Pengajuan::STATUS_MENUNGGU_PELUNASAN)
                        ->whereNotNull('direktur_operasional_approved_by')
                        ->where('direktur_operasional_approved_by', $user->id_user);
                })->orWhere('direktur_operasional_approved_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', [
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
                Pengajuan::STATUS_MENUNGGU_PELUNASAN,
            ])->orWhereNotNull('direktur_operasional_approved_by');
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
            // ACTION PERSETUJUAN (LOGIKA FINAL)
            // =================================================================
            Action::make('approve')
                ->label('Setujui')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('direktur_operasional_catatan')->label('Catatan (Opsional)'),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    // 1. Cari vendor yang sudah ditandai final oleh Kadiv GA
                    $finalVendor = $record->vendorPembayaran->where('is_final', true)->first(); //

                    // 2. Tentukan status selanjutnya berdasarkan opsi pembayaran vendor tersebut
                    $newStatus = Pengajuan::STATUS_MENUNGGU_PELUNASAN; // Default status jika 'Langsung Lunas' atau tidak terdefinisi
                    if ($finalVendor && $finalVendor->opsi_pembayaran === 'Bisa DP') {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA; // Status jika 'Bisa DP'
                    }

                    $catatanTambahan = "\n\n[Disetujui oleh Direktur Operasional: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                    if (!empty($data['direktur_operasional_catatan'])) {
                        $catatanTambahan .= "\n" . $data['direktur_operasional_catatan'];
                    }

                    // 3. Update record pengajuan
                    $record->update([
                        'status' => $newStatus,
                        'direktur_operasional_approved_by' => Auth::id(),
                        'direktur_operasional_decision_type' => 'Disetujui',
                        'direktur_operasional_catatan' => $data['direktur_operasional_catatan'] ?? null,
                        'catatan_revisi' => trim(($record->catatan_revisi ?? '') . $catatanTambahan),
                    ]);

                    Notification::make()->title('Pengajuan disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL), //

            // =================================================================
            // ACTION PENOLAKAN (LOGIKA FINAL)
            // =================================================================
            Action::make('reject')
                ->label('Tolak')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('direktur_operasional_catatan')->label('Catatan Penolakan (Wajib)')->required(),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $catatanTambahan = "\n\n[Ditolak oleh Direktur Operasional: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]\n" . $data['direktur_operasional_catatan'];

                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL, // Status diubah menjadi ditolak
                        'direktur_operasional_approved_by' => Auth::id(),
                        'direktur_operasional_decision_type' => 'Ditolak',
                        'direktur_operasional_catatan' => $data['direktur_operasional_catatan'],
                        'catatan_revisi' => trim(($record->catatan_revisi ?? '') . $catatanTambahan),
                    ]);

                    Notification::make()->title('Pengajuan ditolak')->danger()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL),

            Action::make('approve_revisi')
                ->label('Setujui Revisi')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->form([
                    Hidden::make('revisi_direktur_operasional_decision_type')
                        ->default('Disetujui'),
                    TextInput::make('display_revisi_status')
                        ->label('Jenis Keputusan Revisi')
                        ->default('Disetujui')
                        ->disabled(),
                    Textarea::make('revisi_direktur_operasional_catatan')
                        ->label('Catatan Revisi (Opsional)')
                        ->required(false),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    if (!$finalSurvey) {
                        Notification::make()->title('Error')->body('Survei final tidak ditemukan.')->danger()->send();
                        return;
                    }
                    $revisi = $finalSurvey->revisiHargas()->first();
                    if (!$revisi) {
                        Notification::make()->title('Error')->body('Data revisi tidak ditemukan.')->danger()->send();
                        return;
                    }

                    $revisi->update([
                        'revisi_direktur_operasional_decision_type' => $data['revisi_direktur_operasional_decision_type'] ?? 'Disetujui',
                        'revisi_direktur_operasional_catatan' => $data['revisi_direktur_operasional_catatan'] ?? null,
                        'revisi_direktur_operasional_approved_by' => Auth::id(),
                        'revisi_budget_validated_by' => Auth::id(),
                        'revisi_budget_validated_at' => now(),
                    ]);

                    $catatanTambahan = "\n\n[Revisi disetujui oleh Direktur Operasional: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                    $catatanRevisi = ($record->catatan_revisi ?? '') . $catatanTambahan;

                    // Check if DP is required
                    $requiresDp = $record->items->filter(fn($item) => $item->vendorFinal)->some(function ($item) {
                        return in_array($item->vendorFinal->metode_pembayaran, ['DP', 'Down Payment']) ||
                            in_array($item->vendorFinal->opsi_pembayaran, ['DP', 'Down Payment']);
                    });

                    $newStatus = $requiresDp ? Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA_REVISI : Pengajuan::STATUS_MENUNGGU_PELUNASAN;

                    $record->update([
                        'status' => $newStatus,
                        'catatan_revisi' => trim($catatanRevisi),
                    ]);

                    Log::info('Revisi pengajuan disetujui oleh Direktur Operasional', [
                        'id_pengajuan' => $record->id_pengajuan,
                        'revisi_id' => $revisi->id,
                        'status' => $newStatus,
                        'approved_by' => Auth::id(),
                        'requires_dp' => $requiresDp,
                    ]);

                    Notification::make()->title('Revisi pengajuan disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => in_array($record->status, [
                    Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                ])),

            Action::make('reject_revisi')
                ->label('Tolak Revisi')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([
                    Hidden::make('revisi_direktur_operasional_decision_type')
                        ->default('Ditolak'),
                    TextInput::make('display_revisi_status')
                        ->label('Jenis Keputusan Revisi')
                        ->default('Ditolak')
                        ->disabled(),
                    Textarea::make('revisi_direktur_operasional_catatan')
                        ->label('Catatan Revisi (Wajib)')
                        ->required(),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    if (!$finalSurvey) {
                        Notification::make()->title('Error')->body('Survei final tidak ditemukan.')->danger()->send();
                        return;
                    }
                    $revisi = $finalSurvey->revisiHargas()->first();
                    if (!$revisi) {
                        Notification::make()->title('Error')->body('Data revisi tidak ditemukan.')->danger()->send();
                        return;
                    }

                    $revisi->update([
                        'revisi_direktur_operasional_decision_type' => $data['revisi_direktur_operasional_decision_type'] ?? 'Ditolak',
                        'revisi_direktur_operasional_catatan' => $data['revisi_direktur_operasional_catatan'],
                        'revisi_direktur_operasional_approved_by' => Auth::id(),
                        'revisi_budget_validated_by' => Auth::id(),
                        'revisi_budget_validated_at' => now(),
                    ]);

                    $catatanTambahan = "\n\n[Revisi ditolak oleh Direktur Operasional: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                    $catatanRevisi = ($record->catatan_revisi ?? '') . $catatanTambahan;

                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                        'catatan_revisi' => trim($catatanRevisi),
                    ]);

                    Log::info('Revisi pengajuan ditolak oleh Direktur Operasional', [
                        'id_pengajuan' => $record->id_pengajuan,
                        'revisi_id' => $revisi->id,
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                        'approved_by' => Auth::id(),
                    ]);

                    Notification::make()->title('Revisi pengajuan ditolak')->danger()->send();
                })
                ->visible(fn(Pengajuan $record) => in_array($record->status, [
                    Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                ])),

            // ACTION BARU: KHUSUS UNTUK PERSETUJUAN REVISI
            Action::make('process_revisi_decision_dir_ops')
                ->label('Proses Keputusan (Revisi)')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    Radio::make('revisi_direktur_operasional_decision_type')
                        ->label('Persetujuan Revisi')
                        ->options(['Disetujui' => 'Setujui Revisi', 'Ditolak' => 'Tolak Revisi'])
                        ->required(),
                    Textarea::make('revisi_direktur_operasional_catatan')
                        ->label('Catatan Keputusan (Wajib diisi)')
                        ->required(),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                    if (!$latestRevisi) { /* handle error */
                        return;
                    }

                    $latestRevisi->update([
                        'revisi_direktur_operasional_decision_type' => $data['revisi_direktur_operasional_decision_type'],
                        'revisi_direktur_operasional_catatan' => $data['revisi_direktur_operasional_catatan'],
                        'revisi_direktur_operasional_approved_by' => Auth::id(),
                    ]);

                    if ($data['revisi_direktur_operasional_decision_type'] === 'Ditolak') {
                        $record->update(['status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL]);
                        Notification::make()->title('Revisi ditolak')->danger()->send();
                        return;
                    }
                    $record->update(['status' => Pengajuan::STATUS_MENUNGGU_PELUNASAN]);
                    Notification::make()->title('Revisi berhasil disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => trim($record->status) === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI),
        ];
    }
}
