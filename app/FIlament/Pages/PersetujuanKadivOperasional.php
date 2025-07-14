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

class PersetujuanKadivOperasional extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static string $view = 'filament.pages.persetujuan-kadiv-operasional';
    protected static ?string $navigationLabel = 'Validasi Budget (Kadiv Ops)';
    protected static ?int $navigationSort = 7;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Validasi Budget Kadiv Operasional)';
    }

    public static function canAccess(): bool
    {
        return Auth::user() && Auth::user()->hasAnyRole(['Kepala Divisi Operasional', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi']);

        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET)
                    ->orWhere('kadiv_ops_budget_approved_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', [
                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
            ])->orWhereNotNull('kadiv_ops_budget_approved_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')
                ->label('Kode')
                ->searchable(),
            TextColumn::make('pemohon.nama_user')
                ->label('Pemohon')
                ->searchable(),
            BadgeColumn::make('status')
                ->label('Status Saat Ini')
                ->color(fn($state) => Pengajuan::getStatusBadgeColor($state)),
            BadgeColumn::make('tindakan_saya')
                ->label('Keterangan')
                ->state(function (Pengajuan $record): string {
                    if ($record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET) {
                        return 'Menunggu Validasi Anda';
                    }
                    return 'Sudah Divalidasi';
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Divalidasi' => 'success',
                    'Menunggu Validasi Anda' => 'warning',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()
                ->label('Detail')
                ->modalHeading('Detail Pengajuan dan Review Budget')
                ->modalWidth('4xl')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $record->load(['items', 'items.surveiHargas', 'pemohon.divisi']);
                    $data = $record->toArray();

                    // Menghitung estimasi biaya dan menyiapkan data untuk Repeater
                    $data['estimasi_pengadaan'] = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $data['estimasi_perbaikan'] = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');

                    // Menyiapkan pengadaan_details
                    $pengadaan_details = [];
                    foreach ($record->items as $item) {
                        $survei = $item->surveiHargas->where('tipe_survei', 'Pengadaan')->first();
                        if ($survei) {
                            $pengadaan_details[] = [
                                'nama_barang' => $item->nama_barang,
                                'harga_vendor' => 'Rp ' . number_format($survei->harga ?? 0, 0, ',', '.'),
                                'pajak_info' => $survei->pajak_info ?? 'Tidak ada informasi pajak',
                            ];
                        }
                    }
                    $data['pengadaan_details'] = [
                        'details' => $pengadaan_details,
                        'total' => $data['estimasi_pengadaan'],
                    ];

                    // Menyiapkan perbaikan_details
                    $perbaikan_details = [];
                    foreach ($record->items as $item) {
                        $survei = $item->surveiHargas->where('tipe_survei', 'Perbaikan')->first();
                        if ($survei) {
                            $perbaikan_details[] = [
                                'nama_barang' => $item->nama_barang,
                                'harga_vendor' => 'Rp ' . number_format($survei->harga ?? 0, 0, ',', '.'),
                                'pajak_info' => $survei->pajak_info ?? 'Tidak ada informasi pajak',
                            ];
                        }
                    }
                    $data['perbaikan_details'] = [
                        'details' => $perbaikan_details,
                        'total' => $data['estimasi_perbaikan'],
                    ];

                    // Logging untuk debugging
                    Log::debug('Form data for Pengajuan ID: ' . $record->id_pengajuan, [
                        'pengadaan_details' => $data['pengadaan_details'],
                        'perbaikan_details' => $data['perbaikan_details'],
                    ]);

                    $form->fill($data);
                })
                ->form([
                    Section::make('Detail Pengajuan')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kode_pengajuan')
                                    ->label('Kode Pengajuan')
                                    ->disabled(),
                                TextInput::make('status')
                                    ->label('Status')
                                    ->disabled(),
                                TextInput::make('total_nilai')
                                    ->label('Total Nilai')
                                    ->disabled(),
                            ]),
                            Repeater::make('items')
                                ->relationship()
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('kategori_barang')
                                            ->label('Kategori Barang')
                                            ->disabled(),
                                        TextInput::make('nama_barang')
                                            ->label('Nama Barang')
                                            ->disabled(),
                                        TextInput::make('kuantitas')
                                            ->label('Kuantitas')
                                            ->disabled(),
                                    ]),
                                    Grid::make(2)->schema([
                                        Textarea::make('spesifikasi')
                                            ->label('Spesifikasi')
                                            ->disabled(),
                                        Textarea::make('justifikasi')
                                            ->label('Justifikasi')
                                            ->disabled(),
                                    ]),
                                ])
                                ->columns(1)
                                ->disabled()
                                ->addActionLabel('Tambah Barang'),
                            Grid::make(2)->schema([
                                TextInput::make('rekomendasi_it_tipe')
                                    ->label('Rekomendasi Tipe dari IT')
                                    ->disabled(),
                                Textarea::make('rekomendasi_it_catatan')
                                    ->label('Rekomendasi Catatan dari IT')
                                    ->disabled(),
                            ])
                                ->visible(fn($record) => !empty($record?->rekomendasi_it_tipe)),
                        ])
                        ->collapsible()
                        ->collapsed(),

                    Section::make('Rincian Estimasi Biaya - Skenario PENGADAAN')
                        ->schema([
                            Repeater::make('pengadaan_details.details')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('nama_barang')
                                            ->label('Item')
                                            ->disabled(),
                                        TextInput::make('harga_vendor')
                                            ->label('Harga dari Vendor')
                                            ->disabled(),
                                        TextInput::make('pajak_info')
                                            ->label('Pajak Ditanggung Perusahaan')
                                            ->disabled(),
                                    ]),
                                ])
                                ->disabled()
                                ->disableItemCreation()
                                ->disableItemDeletion()
                                ->disableItemMovement(),
                            Placeholder::make('pengadaan_details.total')
                                ->label('TOTAL ESTIMASI BIAYA PENGADAAN')
                                ->content(fn($get) => new HtmlString('<span class="text-xl text-primary-600 font-bold">' . ($get('pengadaan_details.total') ?? 'Rp 0') . '</span>')),
                        ])
                        ->collapsible()
                        ->collapsed(),

                    Section::make('Rincian Estimasi Biaya - Skenario PERBAIKAN')
                        ->schema([
                            Repeater::make('perbaikan_details.details')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('nama_barang')
                                            ->label('Item')
                                            ->disabled(),
                                        TextInput::make('harga_vendor')
                                            ->label('Harga dari Vendor')
                                            ->disabled(),
                                        TextInput::make('pajak_info')
                                            ->label('Pajak Ditanggung Perusahaan')
                                            ->disabled(),
                                    ]),
                                ])
                                ->disabled()
                                ->disableItemCreation()
                                ->disableItemDeletion()
                                ->disableItemMovement(),
                            Placeholder::make('perbaikan_details.total')
                                ->label('TOTAL ESTIMASI BIAYA PERBAIKAN')
                                ->content(fn($get) => new HtmlString('<span class="text-xl text-success-600 font-bold">' . ($get('perbaikan_details.total') ?? 'Rp 0') . '</span>')),
                        ])
                        ->visible(fn($get) => !empty($get('perbaikan_details.details')))
                        ->collapsible()
                        ->collapsed(),

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
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                        ]) && !empty($record->kadiv_ga_decision_type))
                        ->collapsible()->collapsed(),
                ]),

            Action::make('confirm_budget')
                ->label('Konfirmasi Sesuai')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Review Budget')
                ->modalDescription('Apakah Anda yakin review budget dari tim Budgeting sudah sesuai?')
                ->action(function (Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        'kadiv_ops_budget_approved_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Review budget telah dikonfirmasi.')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET),
        ];
    }
}
