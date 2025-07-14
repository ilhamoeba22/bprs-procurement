<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\SurveiHarga;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Concerns\InteractsWithTable;

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
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas', 'items.vendorFinal']);

        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA)
                    ->orWhere(function (Builder $q) use ($user) {
                        $q->whereIn('status', [
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                        ])->where('kadiv_ga_approved_by', $user->id_user);
                    });
            });
        } else {
            $query->whereIn('status', [
                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                Pengajuan::STATUS_DITOLAK_KADIV_GA,
                Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                Pengajuan::STATUS_SUDAH_BAYAR,
                Pengajuan::STATUS_SELESAI,
            ]);
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            BadgeColumn::make('status')
                ->label('Status Saat Ini')
                ->color(fn($state) => Pengajuan::getStatusBadgeColor($state)),
            BadgeColumn::make('keputusan_kadiv_ga')
                ->label('Keputusan Anda')
                ->state(function (Pengajuan $record): string {
                    if ($record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA) {
                        return 'Menunggu Keputusan';
                    }
                    if ($record->status === Pengajuan::STATUS_DITOLAK_KADIV_GA) {
                        return 'Ditolak';
                    }
                    return 'Disetujui (' . ($record->kadiv_ga_decision_type ?? 'N/A') . ')';
                })
                ->color(fn(string $state): string => match (explode(' ', $state)[0]) {
                    'Menunggu' => 'warning',
                    'Disetujui' => 'success',
                    'Ditolak' => 'danger',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading('')
                ->modalWidth('4xl')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $record->load(['items', 'items.surveiHargas', 'pemohon.divisi']);
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
                                TextInput::make('kode_pengajuan')->disabled(),
                                TextInput::make('status')->disabled(),
                                TextInput::make('total_nilai')->label('Total Nilai')->disabled(),
                                TextInput::make('nama_divisi')->label('Divisi')->disabled(),
                                TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
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
                            ])->columns(1)->disabled()->addActionLabel('Tambah Barang'),
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
                                ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . $get('perbaikan_details.total') . '</b>')),
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
                            ])->visible(fn($get) => !is_null($get('budget_status_perbaikan'))),
                        ])->collapsible()->collapsed(),

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
                        ]))
                        ->collapsible()->collapsed(),
                ]),
            Action::make('process_decision')
                ->label('Proses Keputusan')
                ->color('primary')
                ->icon('heroicon-o-check-circle')
                ->form(function (Pengajuan $record) {
                    $hasPerbaikan = !is_null($record->perbaikan_details) && !empty($record->perbaikan_details['details']);
                    $options = [
                        'Pengadaan' => 'Lanjutkan dengan Pengadaan',
                        'Tolak' => 'Tolak Pengajuan',
                    ];
                    if ($hasPerbaikan) {
                        $options['Perbaikan'] = 'Lanjutkan dengan Perbaikan';
                    }

                    return [
                        Radio::make('keputusan_final')
                            ->label('Persetujuan Final')
                            ->options($options)
                            ->required(),
                        Textarea::make('kadiv_ga_catatan')->label('Catatan Keputusan (Wajib diisi)')->required(),
                    ];
                })
                ->action(function (array $data, Pengajuan $record) {
                    Log::info('Processing decision for pengajuan ID: ' . $record->id_pengajuan, [
                        'user_id' => Auth::id(),
                        'keputusan_final' => $data['keputusan_final'],
                        'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                    ]);

                    $catatan = $record->catatan_revisi ?? '';
                    if (!empty($data['kadiv_ga_catatan'])) {
                        $user = Auth::user()->nama_user;
                        $catatan .= "\n\n[Keputusan oleh Kadiv GA: {$user} pada " . now()->format('d-m-Y H:i') . "]\n" . $data['kadiv_ga_catatan'];
                    }

                    if ($data['keputusan_final'] === 'Tolak') {
                        $record->update([
                            'status' => Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            'kadiv_ga_decision_type' => 'Tolak',
                            'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                            'catatan_revisi' => trim($catatan),
                            'kadiv_ga_approved_by' => Auth::id(),
                        ]);
                        Notification::make()->title('Pengajuan ditolak')->danger()->send();
                        Log::info('Pengajuan ID: ' . $record->id_pengajuan . ' rejected by Kadiv GA');
                        return;
                    }

                    $nilaiFinal = 0;
                    $budgetFinal = '';
                    if ($data['keputusan_final'] === 'Pengadaan') {
                        $nilaiFinal = $record->items->reduce(function ($carry, $item) {
                            $minHarga = $item->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0;
                            $minSurveiHarga = $item->surveiHargas->where('tipe_survei', 'Pengadaan')->where('harga', $minHarga)->first();
                            if ($minSurveiHarga) {
                                $item->surveiHargas()->where('tipe_survei', 'Pengadaan')->where('id', '!=', $minSurveiHarga->id)->update(['is_final' => false]);
                                $minSurveiHarga->update(['is_final' => true]);
                                Log::info('Set is_final for survei_harga ID: ' . $minSurveiHarga->id . ' (Pengadaan) for item ID: ' . $item->id_item);
                            } else {
                                Log::warning('No surveiHarga found for Pengadaan for item ID: ' . $item->id_item);
                            }
                            return $carry + ($minHarga * $item->kuantitas);
                        }, 0);
                        $budgetFinal = $record->budget_status_pengadaan;
                    } else {
                        $nilaiFinal = $record->items->reduce(function ($carry, $item) {
                            $minHarga = $item->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0;
                            $minSurveiHarga = $item->surveiHargas->where('tipe_survei', 'Perbaikan')->where('harga', $minHarga)->first();
                            if ($minSurveiHarga) {
                                $item->surveiHargas()->where('tipe_survei', 'Perbaikan')->where('id', '!=', $minSurveiHarga->id)->update(['is_final' => false]);
                                $minSurveiHarga->update(['is_final' => true]);
                                Log::info('Set is_final for survei_harga ID: ' . $minSurveiHarga->id . ' (Perbaikan) for item ID: ' . $item->id_item);
                            } else {
                                Log::warning('No surveiHarga found for Perbaikan for item ID: ' . $item->id_item);
                            }
                            return $carry + ($minHarga * $item->kuantitas);
                        }, 0);
                        $budgetFinal = $record->budget_status_perbaikan;
                    }

                    $newStatus = '';
                    if ($budgetFinal === 'Budget Tersedia' && $nilaiFinal <= 5000000) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA;
                    } elseif ($budgetFinal === 'Budget Habis' || $budgetFinal === 'Budget Tidak Ada di RBB' || ($nilaiFinal > 5000000 && $nilaiFinal <= 100000000)) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL;
                    } elseif ($nilaiFinal > 100000000) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA;
                    }

                    $record->update([
                        'total_nilai' => $nilaiFinal,
                        'kadiv_ga_decision_type' => $data['keputusan_final'],
                        'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                        'catatan_revisi' => trim($catatan),
                        'status' => $newStatus,
                        'kadiv_ga_approved_by' => Auth::id(),
                    ]);

                    Notification::make()->title('Keputusan berhasil diproses')->success()->send();
                    Log::info('Decision processed for pengajuan ID: ' . $record->id_pengajuan, [
                        'new_status' => $newStatus,
                        'total_nilai' => $nilaiFinal,
                        'kadiv_ga_decision_type' => $data['keputusan_final'],
                    ]);
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA),
        ];
    }
}
