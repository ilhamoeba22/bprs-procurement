<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Tables\Table;
use App\Models\SurveiHarga;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
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
            BadgeColumn::make('status')
                ->label('Status Saat Ini')
                ->color(fn($state) => Pengajuan::getStatusBadgeColor($state)),
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
                        ->visible(fn($get, Pengajuan $record) => !is_null($get('pengadaan_details')) && !empty($get('pengadaan_details')['details']) && in_array($record->status, [
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                        ]))
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
                                ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . $get('perbaikan_details.total') . '</b>')),
                        ])
                        ->visible(fn($get, Pengajuan $record) => !is_null($get('perbaikan_details')) && !empty($get('perbaikan_details')['details']) && in_array($record->status, [
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                        ]))
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
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
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
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                        ]))
                        ->collapsible()->collapsed(),
                ]),

            Action::make('submit_recommendation')
                ->label('Submit Rekomendasi')->color('primary')->icon('heroicon-o-chat-bubble-bottom-center-text')
                ->form([
                    Forms\Components\Select::make('rekomendasi_it_tipe')->label('Tipe Rekomendasi')->options(['Pembelian Baru' => 'Pembelian Baru', 'Perbaikan' => 'Perbaikan'])->required(),
                    Forms\Components\Textarea::make('rekomendasi_it_catatan')->label('Catatan Rekomendasi')->required(),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'rekomendasi_it_tipe' => $data['rekomendasi_it_tipe'],
                        'rekomendasi_it_catatan' => $data['rekomendasi_it_catatan'],
                        'status' => Pengajuan::STATUS_SURVEI_GA,
                        'it_recommended_by' => Auth::id(), // Catat siapa yang memberi rekomendasi
                    ]);
                    Notification::make()->title('Rekomendasi berhasil disubmit')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_REKOMENDASI_IT),
        ];
    }
}
