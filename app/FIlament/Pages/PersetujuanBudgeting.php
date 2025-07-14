<?php

namespace App\Filament\Pages;

use Log;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\SurveiHarga;
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
use Filament\Forms\Components\DatePicker;
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
        ];

        if (! $user->hasRole('Super Admin')) {
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
            TextColumn::make('kode_pengajuan')->label('Tiket Pengajuan')->sortable()->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->sortable()->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi')->sortable()->searchable(),
            TextColumn::make('total_nilai')->label('Total Nilai')->money('IDR')->sortable(),
            BadgeColumn::make('status')
                ->label('Status Saat Ini')
                ->color(fn($state) => Pengajuan::getStatusBadgeColor($state)),
            BadgeColumn::make('tindakan_saya')
                ->label('Keterangan')
                ->state(function (Pengajuan $record): string {
                    if ($record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET) {
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

    protected function getTableActions(): array
    {
        $viewAction = ViewAction::make()->label('Detail')
            ->modalHeading('')
            ->modalWidth('4xl')
            ->mountUsing(function (Form $form, Pengajuan $record) {
                $record->load(['items.surveiHargas', 'pemohon.divisi']);

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

                $data = $record->toArray();
                $data['pengadaan_details'] = $getScenarioDetails($record->items, 'Pengadaan');
                $data['perbaikan_details'] = $getScenarioDetails($record->items, 'Perbaikan');
                $data['nama_divisi'] = $record->pemohon->divisi->nama_divisi ?? 'Tidak tersedia';
                $data['nama_barang'] = $record->items->pluck('nama_barang')->implode(', ') ?: 'Tidak tersedia';
                $data['catatan_revisi'] = $record->catatan_revisi ?? 'Tidak ada riwayat catatan approval.';

                $form->fill($data);
            })
            ->form([
                Section::make('Detail Pengajuan')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('kode_pengajuan')->label('Kode Pengajuan')->disabled(),
                            TextInput::make('status')->label('Status')->disabled(),
                            TextInput::make('total_nilai')->label('Total Nilai')->disabled(),
                            TextInput::make('nama_divisi')->label('Divisi')->disabled(),
                            TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
                        ]),
                        Repeater::make('items')->relationship()->label('')->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kategori_barang')->label('Kategori Barang')->disabled(),
                                TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
                                TextInput::make('kuantitas')->label('Kuantitas')->disabled(),
                            ]),
                            Grid::make(2)->schema([
                                Textarea::make('spesifikasi')->label('Spesifikasi')->disabled(),
                                Textarea::make('justifikasi')->label('Justifikasi')->disabled(),
                            ]),
                        ])->columns(1)->disabled()->addActionLabel('Tambah Barang'),
                        Grid::make(2)->schema([
                            TextInput::make('rekomendasi_it_tipe')->label('Rekomendasi Tipe dari IT')->disabled(),
                            Textarea::make('rekomendasi_it_catatan')->label('Rekomendasi Catatan dari IT')->disabled(),
                        ])->visible(fn($record) => !empty($record?->rekomendasi_it_tipe)),
                        Textarea::make('catatan_revisi')->label('Catatan Approval Sebelumnya')->disabled()->visible(fn($record) => !empty($record?->catatan_revisi)),
                    ])->collapsible()->collapsed(),

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
                            ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . $get('pengadaan_details.total') . '</b>')),
                    ])
                    ->visible(fn($get) => !is_null($get('pengadaan_details')) && !empty($get('pengadaan_details')['details']))
                    ->collapsible()->collapsed(),

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
                            ->content(fn($get) => new HtmlString('<b class="text-xl text-success-600">' . $get('perbaikan_details.total') . '</b>')),
                    ])
                    ->visible(fn($get) => !is_null($get('perbaikan_details')) && !empty($get('perbaikan_details')['details']))
                    ->collapsible()->collapsed(),

                Section::make('Review Budgeting')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->disabled(),
                            Textarea::make('budget_catatan_pengadaan')->label('Catatan Budget Pengadaan')->disabled(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->disabled(),
                            Textarea::make('budget_catatan_perbaikan')->label('Catatan Budget Perbaikan')->disabled(),
                        ])->visible(fn($get) => !is_null($get('budget_status_perbaikan')) && !empty($get('budget_status_perbaikan'))),
                    ])
                    ->visible(fn($get, Pengajuan $record) => (!is_null($get('budget_status_pengadaan')) && !empty($get('budget_status_pengadaan'))) && in_array($record->status, [
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                        Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                        Pengajuan::STATUS_SUDAH_BAYAR,
                        Pengajuan::STATUS_SELESAI,
                    ]))
                    ->collapsible()->collapsed(),

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
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                        Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                        Pengajuan::STATUS_SUDAH_BAYAR,
                        Pengajuan::STATUS_SELESAI,
                        Pengajuan::STATUS_DITOLAK_KADIV_GA,
                        Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                        Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                    ]) && !empty($record->kadiv_ga_decision_type))
                    ->collapsible()->collapsed(),
            ]);
        // Logika form untuk review budget
        $budgetReviewForm = function (Pengajuan $record) {
            $hasPerbaikan = $record->items->flatMap->surveiHargas->where('tipe_survei', 'Perbaikan')->isNotEmpty();

            $totalPengadaan = $record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $i->kuantitas), 0);
            $totalPerbaikan = $record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $i->kuantitas), 0);

            return [
                Section::make('Detail Pengajuan')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('kode_pengajuan')->label('Kode Pengajuan')->disabled()->default($record->kode_pengajuan),
                        TextInput::make('nama_divisi')->label('Divisi')->disabled()->default($record->pemohon->divisi->nama_divisi ?? 'Tidak tersedia'),
                    ]),
                ]),

                Section::make('Review Budget Skenario Pengadaan')
                    ->description('Estimasi Biaya dari GA: Rp ' . number_format($totalPengadaan, 0, ',', '.'))
                    ->schema([
                        Select::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->options([
                            'Budget Tersedia' => 'Budget Tersedia',
                            'Budget Habis' => 'Budget Habis',
                            'Budget Tidak Ada di RBB' => 'Budget Tidak Ada di RBB'
                        ])->default($record->budget_status_pengadaan)->required(),
                        Textarea::make('budget_catatan_pengadaan')->label('Catatan')->default($record->budget_catatan_pengadaan)
                            ->required(),
                    ]),

                // Section ini hanya akan muncul jika ada data perbaikan
                Section::make('Review Budget untuk Skenario PERBAIKAN')
                    ->description('Estimasi Biaya dari GA: Rp ' . number_format($totalPerbaikan, 0, ',', '.'))
                    ->schema([
                        Select::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->options([
                            'Budget Tersedia' => 'Budget Tersedia',
                            'Budget Habis' => 'Budget Habis',
                            'Budget Tidak Ada di RBB' => 'Budget Tidak Ada di RBB'
                        ])->default($record->budget_status_perbaikan)
                            ->required($hasPerbaikan),
                        Textarea::make('budget_catatan_perbaikan')->label('Catatan')->default($record->budget_catatan_perbaikan)
                            ->required(),
                    ])
                    ->visible($hasPerbaikan),

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
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                        Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                        Pengajuan::STATUS_SUDAH_BAYAR,
                        Pengajuan::STATUS_SELESAI,
                    ]) && !empty($record->kadiv_ga_decision_type))
                    ->collapsible()->collapsed(),
            ];
        };
        $submitAction = Action::make('submit_budget_review')
            ->label('Submit Review Budget')->color('primary')->icon('heroicon-o-pencil-square')
            ->modalHeading('Form Review Budget')
            ->form($budgetReviewForm)
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
            ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET);

        $editAction = Action::make('edit_budget_review')
            ->label('Edit Review Budget')->color('warning')->icon('heroicon-o-pencil')
            ->modalHeading('Edit Review Budget')
            ->form($budgetReviewForm)
            ->action(function (array $data, Pengajuan $record) {
                $record->update([
                    'budget_status_pengadaan' => $data['budget_status_pengadaan'],
                    'budget_catatan_pengadaan' => $data['budget_catatan_pengadaan'],
                    'budget_status_perbaikan' => $data['budget_status_perbaikan'] ?? null,
                    'budget_catatan_perbaikan' => $data['budget_catatan_perbaikan'] ?? null,
                ]);
                Notification::make()->title('Review budget berhasil diupdate')->success()->send();
            })
            ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET);

        return [
            $viewAction,
            $submitAction,
            $editAction,
        ];
    }
}
