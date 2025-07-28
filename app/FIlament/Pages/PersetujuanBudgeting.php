<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Tables\Table;
use App\Models\RevisiHarga;
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

class PersetujuanBudgeting extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.persetujuan-budgeting';
    protected static ?string $navigationLabel = 'Persetujuan Budgeting';
    protected static ?int $navigationSort = 7;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Persetujuan Budgeting)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Tim Budgeting', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas']);
        $statuses = [
            Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
            Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
        ];

        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user, $statuses) {
                $q->whereIn('status', $statuses)
                    ->orWhere('budget_approved_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', $statuses)
                ->orWhereNotNull('budget_approved_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),

            // --- KOLOM TOTAL NILAI DENGAN LOGIKA PERMANEN ---
            TextColumn::make('total_nilai')
                ->label('Total Nilai')
                ->state(function (Pengajuan $record): ?float {
                    $revisi = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas?->first();
                    // Jika ada revisi, nilai revisi menjadi nilai permanen. Jika tidak, gunakan nilai awal.
                    return $revisi?->harga_revisi ?? $record->total_nilai;
                })
                ->money('IDR')
                ->icon(function (Pengajuan $record): ?string {
                    $revisiExists = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas()->exists();
                    return $revisiExists ? 'heroicon-o-arrow-path' : null;
                })
                ->color(function (Pengajuan $record): ?string {
                    $revisiExists = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas()->exists();
                    return $revisiExists ? 'warning' : null;
                })
                ->description(function (Pengajuan $record): ?string {
                    $revisiExists = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas()->exists();
                    if ($revisiExists) {
                        return 'Nilai Awal: ' . number_format($record->total_nilai, 0, ',', '.');
                    }
                    return null;
                })
                ->sortable(),

            BadgeColumn::make('status')->label('Status Saat Ini')->color(fn($state) => Pengajuan::getStatusBadgeColor($state)),
            BadgeColumn::make('tindakan_saya')
                ->label('Keterangan')
                ->state(function (Pengajuan $record): string {
                    if (in_array($record->status, [
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI
                    ])) {
                        return 'Menunggu Aksi';
                    }
                    if ($record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET) {
                        return 'Menunggu Validasi Kadiv Ops';
                    }
                    return 'Sudah Direview';
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Direview' => 'success',
                    'Menunggu Validasi Kadiv Ops' => 'warning',
                    default => 'gray',
                }),
        ];
    }

    /**
     * Method baru untuk menghasilkan skema form review budget.
     */
    protected function getBudgetReviewFormSchema(Pengajuan $record): array
    {
        $hasPerbaikan = $record->items->flatMap->surveiHargas->where('tipe_survei', 'Perbaikan')->isNotEmpty();

        if ($record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI) {
            $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
            $revisi = $finalSurvey ? $finalSurvey->revisiHargas->first() : null;
            $totalPengadaan = $revisi ? $revisi->harga_revisi : 0;
            $totalPerbaikan = 0;
        } else {
            $totalPengadaan = $record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $i->kuantitas), 0);
            $totalPerbaikan = $record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $i->kuantitas), 0);
        }

        return [
            Section::make('Review Budget Skenario Pengadaan')
                ->description('Estimasi Biaya: Rp ' . number_format($totalPengadaan, 0, ',', '.'))
                ->schema([
                    Select::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->options([
                        'Budget Tersedia' => 'Budget Tersedia',
                        'Budget Habis' => 'Budget Habis',
                        'Budget Tidak Ada di RBB' => 'Budget Tidak Ada di RBB'
                    ])->default($record->budget_status_pengadaan)->required(),
                    Textarea::make('budget_catatan_pengadaan')->label('Catatan')->default($record->budget_catatan_pengadaan)->required(),
                ]),
            Section::make('Review Budget untuk Skenario PERBAIKAN')
                ->description('Estimasi Biaya: Rp ' . number_format($totalPerbaikan, 0, ',', '.'))
                ->schema([
                    Select::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->options([
                        'Budget Tersedia' => 'Budget Tersedia',
                        'Budget Habis' => 'Budget Habis',
                        'Budget Tidak Ada di RBB' => 'Budget Tidak Ada di RBB'
                    ])->default($record->budget_status_perbaikan)->required($hasPerbaikan),
                    Textarea::make('budget_catatan_perbaikan')->label('Catatan')->default($record->budget_catatan_perbaikan)->required($hasPerbaikan),
                ])
                ->visible($hasPerbaikan && $record->status !== Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            // AKSI 1: Tombol Detail (View)
            ViewAction::make()->label('Detail')
                ->modalHeading('Detail Pengajuan')
                ->modalWidth('5xl')
                ->mountUsing(function (Form $form, Pengajuan $record) {
                    // Eager load semua relasi yang dibutuhkan
                    $record->load([
                        'items.surveiHargas.revisiHargas.revisiBudgetApprover',
                        'items.vendorFinal',
                        'pemohon.divisi',
                        'approverBudget'
                    ]);
                    $formData = $record->toArray();

                    // --- LOGIKA UNTUK RINCIAN BIAYA (SUDAH ADA) ---
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
                    // --- AKHIR LOGIKA RINCIAN BIAYA ---

                    $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    $revisi = $finalSurvey ? $finalSurvey->revisiHargas->first() : null;
                    if ($revisi) {
                        $formData['revisi_data'] = $revisi->toArray();
                    }
                    $formData['items_with_final_vendor'] = $record->items->filter(fn($item) => $item->vendorFinal)
                        ->map(fn($item) => [
                            'nama_barang' => $item->nama_barang,
                            'nama_vendor' => $item->vendorFinal->nama_vendor,
                            'harga' => $item->vendorFinal->harga,
                            'metode_pembayaran' => $item->vendorFinal->metode_pembayaran,
                            'opsi_pembayaran' => $item->vendorFinal->opsi_pembayaran,
                        ])->values()->toArray();
                    $form->fill($formData);
                })
                ->form([
                    Section::make('Detail Pengajuan')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('kode_pengajuan')->disabled(),
                            TextInput::make('status')->disabled(),
                            TextInput::make('total_nilai')->label('Total Nilai')->disabled(),
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
                        ])->columns(1)->disabled(),
                        Grid::make(2)->schema([
                            TextInput::make('rekomendasi_it_tipe')->label('Rekomendasi Tipe dari IT')->disabled(),
                            Textarea::make('rekomendasi_it_catatan')->label('Rekomendasi Catatan dari IT')->disabled(),
                        ])->visible(fn($get) => !empty($get('rekomendasi_it_tipe'))),
                        Textarea::make('catatan_revisi')->label('Catatan Approval Sebelumnya')->disabled(),
                    ])->collapsible()->collapsed(),

                    // --- SECTION BARU: HASIL SURVEI HARGA ---
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
                                ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . ($get('pengadaan_details.total') ?? 'Rp 0') . '</b>')),
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
                                ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . ($get('perbaikan_details.total') ?? 'Rp 0') . '</b>')),
                        ])
                        ->visible(fn($get) => !is_null($get('perbaikan_details')) && !empty($get('perbaikan_details')['details']))
                        ->collapsible()->collapsed(),

                    Section::make('Review Budget')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->disabled(),
                            Textarea::make('budget_catatan_pengadaan')->label('Catatan Budget Pengadaan')->disabled(),
                            TextInput::make('approverBudget.nama_user')->label('Direview Oleh')->disabled(),
                        ]),
                    ])->visible(fn($get) => !empty($get('budget_status_pengadaan')))->collapsible()->collapsed(),


                    // FINAL APPROVLE SECTION
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
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
                            Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                        ]))
                        ->collapsible()->collapsed(),

                    Section::make('Vendor & Harga Final yang Disetujui')->schema([
                        Repeater::make('items_with_final_vendor')->label('')->schema([
                            Grid::make(5)->schema([
                                TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
                                TextInput::make('nama_vendor')->label('Nama Vendor')->disabled(),
                                TextInput::make('harga')->label('Harga Satuan')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->disabled(),
                                TextInput::make('metode_pembayaran')->label('Metode Bayar')->disabled(),
                                TextInput::make('opsi_pembayaran')->label('Opsi Bayar')->disabled(),
                            ]),
                        ])->disabled()->disableItemCreation()->disableItemDeletion()->disableItemMovement(),
                    ])->collapsible()->collapsed()->visible(fn($get) => !empty($get('items_with_final_vendor'))),


                    // --- SECTION DETAIL REVISI & REVIEW BUDGET REVISI (DIGABUNG & DIPERBAIKI) ---
                    Section::make('Detail Revisi & Review Budget Ulang')
                        ->schema([
                            // Detail Revisi Harga
                            Grid::make(3)->schema([
                                TextInput::make('revisi_data.harga_revisi')
                                    ->label('Harga Setelah Revisi')
                                    ->prefix('Rp')
                                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                    ->disabled(),
                                // TextInput::make('revisi_data.direvisi_oleh.nama_user')->label('Harga Direvisi Oleh')->disabled(),
                                TextInput::make('revisi_data.tanggal_revisi')
                                    ->label('Tanggal Revisi')
                                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('Y-m-d') : null)
                                    ->disabled(),
                                Textarea::make('revisi_data.alasan_revisi')->label('Alasan Revisi Harga')->disabled(),
                            ]),

                            // Garis Pemisah
                            Placeholder::make('')->label(false)->content(new HtmlString('<hr class="my-1"/>')),
                            // Detail Review Budget Revisi
                            Grid::make(3)->schema([
                                TextInput::make('revisi_data.revisi_budget_status_pengadaan')->label('Status Budget Revisi')->disabled(),
                                Textarea::make('revisi_data.revisi_budget_catatan_pengadaan')->label('Catatan Budget Revisi')->disabled(),
                                TextInput::make('revisi_data.revisi_budget_approver.nama_user')->label('Budget Revisi Direview Oleh')->disabled(),
                            ]),
                        ])
                        ->collapsible()->collapsed()
                        // ->visible(fn($get) => !empty($get('revisi_data'))),
                        ->visible(fn($get) => ($get('status') ?? null) === Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI),
                ]),

            // AKSI 2: Untuk review awal
            Action::make('submit_budget_review')
                ->label('Submit Review Budget')->color('primary')->icon('heroicon-o-pencil-square')
                ->form(fn(Pengajuan $record) => $this->getBudgetReviewFormSchema($record))
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'budget_status_pengadaan' => $data['budget_status_pengadaan'],
                        'budget_catatan_pengadaan' => $data['budget_catatan_pengadaan'],
                        'budget_status_perbaikan' => $data['budget_status_perbaikan'] ?? null,
                        'budget_catatan_perbaikan' => $data['budget_catatan_perbaikan'] ?? null,
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                        'budget_approved_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Review budget berhasil disubmit')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET),

            Action::make('submit_revisi_budget_review')
                ->label('Submit Review Revisi')->color('success')->icon('heroicon-o-check-badge')
                ->form(fn(Pengajuan $record) => $this->getBudgetReviewFormSchema($record))
                ->action(function (array $data, Pengajuan $record) {
                    $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    $revisi = $finalSurvey ? $finalSurvey->revisiHargas->first() : null;
                    if ($revisi) {
                        $revisi->update([
                            'revisi_budget_status_pengadaan' => $data['budget_status_pengadaan'],
                            'revisi_budget_catatan_pengadaan' => $data['budget_catatan_pengadaan'],
                            'revisi_budget_status_perbaikan' => $data['budget_status_perbaikan'] ?? null,
                            'revisi_budget_catatan_perbaikan' => $data['budget_catatan_perbaikan'] ?? null,
                            'revisi_budget_approved_by' => Auth::id(),
                        ]);
                    }
                    // --- PERBAIKAN UTAMA ADA DI SINI ---
                    $record->update(['status' => Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS]);
                    Notification::make()->title('Review budget revisi berhasil disubmit')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI),

            // AKSI 4: Untuk edit review
            Action::make('edit_budget_review')
                ->label('Edit Review Budget')->color('warning')->icon('heroicon-o-pencil')
                ->form(fn(Pengajuan $record) => $this->getBudgetReviewFormSchema($record))
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'budget_status_pengadaan' => $data['budget_status_pengadaan'],
                        'budget_catatan_pengadaan' => $data['budget_catatan_pengadaan'],
                        'budget_status_perbaikan' => $data['budget_status_perbaikan'] ?? null,
                        'budget_catatan_perbaikan' => $data['budget_catatan_perbaikan'] ?? null,
                    ]);
                    Notification::make()->title('Review budget berhasil diupdate')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET),
        ];
    }
}
