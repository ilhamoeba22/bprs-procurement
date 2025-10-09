<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
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
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Components\RevisiTimelineSection;
use App\Filament\Components\StandardDetailSections;
use Filament\Forms\Components\Textarea as FormsTextarea;

class PersetujuanKepalaDivisi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static string $view = 'filament.pages.persetujuan-kepala-divisi';
    protected static ?string $navigationLabel = 'Persetujuan Kepala Divisi';
    protected static ?int $navigationSort = 4;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan - Approval Kepala Divisi';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Kepala Divisi', 'Kepala Divisi IT', 'Kepala Divisi GA', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['items', 'pemohon.divisi']);

        if ($user->hasRole('Super Admin')) {
            return $query->whereIn('status', [
                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV,
                Pengajuan::STATUS_REKOMENDASI_IT,
                Pengajuan::STATUS_SURVEI_GA,
            ])->orWhereNotNull('kadiv_approved_by');
        }

        $query->where(function (Builder $q) use ($user) {

            $q->where(function (Builder $subQ) use ($user) {
                $subQ->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV)
                    ->whereHas('pemohon', function (Builder $pemohonQ) use ($user) {
                        $pemohonQ->where('id_divisi', $user->id_divisi);
                    });
            })

                ->orWhere('kadiv_approved_by', $user->id_user);
        });

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
                    if ($record->kadiv_approved_by === Auth::id()) {
                        return 'Sudah Diproses';
                    }
                    return match ($record->status) {
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV => 'Menunggu Aksi',
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

            Action::make('buat_keputusan')
                ->label('Buat Keputusan')
                ->color('primary')
                ->icon('heroicon-o-pencil-square')
                ->form(function (Pengajuan $record) {
                    return [
                        // [PERUBAHAN] Menampilkan section detail secara manual tanpa total nilai
                        Section::make('Detail Pengajuan')->schema([
                            Grid::make(2)->schema([ // Grid diubah menjadi 2 kolom
                                TextInput::make('kode_pengajuan')->disabled(),
                                TextInput::make('status')->disabled(),
                            ]),
                            Repeater::make('items')->relationship()->label('Barang yang Diajukan')->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('kategori_barang')->disabled(),
                                    TextInput::make('nama_barang')->disabled(),
                                    TextInput::make('kuantitas')->disabled(),
                                ]),
                                Grid::make(2)->schema([
                                    Textarea::make('spesifikasi')->disabled(),
                                    Textarea::make('justifikasi')->disabled(),
                                ]),
                            ])->columns(1)->disabled(),
                        ])->collapsible()->collapsed(),

                        Section::make('Form Keputusan')->schema([
                            Radio::make('keputusan')
                                ->label('Keputusan Anda')
                                ->options(['Setuju' => 'Setujui Pengajuan', 'Tolak' => 'Tolak Pengajuan'])
                                ->required()->live()->inline(),
                            Textarea::make('catatan_revisi')
                                ->label('Catatan / Alasan')
                                ->required(fn($get) => $get('keputusan') === 'Tolak')
                                ->visible(fn($get) => !empty($get('keputusan'))),
                        ]),
                    ];
                })
                ->mountUsing(function (Form $form, Pengajuan $record): void {
                    $form->fill($record->toArray());
                })
                ->action(function (array $data, Pengajuan $record) {
                    $user = Auth::user();
                    $jabatan = $user->jabatan->nama_jabatan ?? 'Kepala Divisi';

                    if (empty($data['catatan_revisi']) && $data['keputusan'] === 'Tolak') {
                        Notification::make()->title('Gagal')->body('Catatan atau alasan wajib diisi saat menolak.')->danger()->send();
                        return;
                    }

                    if (!empty($data['catatan_revisi']) || $data['keputusan'] === 'Setuju') {
                        $catatan = $record->catatan_revisi ?? '';
                        $catatanBaru = $data['catatan_revisi'] ?? 'Disetujui';

                        $catatan .= "\n\n" . $catatanBaru . " (" . $jabatan . ")";

                        if ($data['keputusan'] === 'Setuju') {
                            $needsITRecommendation = $record->items()
                                ->where('kategori_barang', 'Barang IT')
                                ->exists();
                            $newStatus = $needsITRecommendation ? Pengajuan::STATUS_REKOMENDASI_IT : Pengajuan::STATUS_SURVEI_GA;

                            $record->update([
                                'status' => $newStatus,
                                'catatan_revisi' => trim($catatan),
                                'kadiv_approved_by' => $user->id_user,
                                'kadiv_approved_at' => now(),
                            ]);
                            Notification::make()->title('Pengajuan disetujui')->success()->send();
                        } elseif ($data['keputusan'] === 'Tolak') {
                            $record->update([
                                'status' => Pengajuan::STATUS_DITOLAK_KADIV,
                                'catatan_revisi' => trim($catatan),
                                'kadiv_approved_by' => $user->id_user,
                                'kadiv_approved_at' => now(),
                            ]);
                            Notification::make()->title('Pengajuan ditolak')->danger()->send();
                        }
                    }
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV),
        ];
    }
}
