<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\Pengajuan;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms;

class StatusPengajuanSaya extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static string $view = 'filament.pages.status-pengajuan-saya';
    protected static ?string $navigationLabel = 'Status Pengajuan Saya';
    protected static ?int $navigationSort = 2;

    public function getTitle(): string
    {
        return 'Status Pengajuan Saya';
    }

    protected function getTableQuery(): Builder
    {
        return Pengajuan::query()->with('items')->where('id_user_pemohon', Auth::id())->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Tiket Pengajuan')->sortable()->searchable(),
            BadgeColumn::make('status')
                ->colors([
                    'gray'      => Pengajuan::STATUS_DRAFT,
                    'danger'    => [
                        Pengajuan::STATUS_DITOLAK_MANAGER,
                        Pengajuan::STATUS_DITOLAK_KADIV,
                        Pengajuan::STATUS_DITOLAK_KADIV_GA,
                        Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                        Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                    ],
                    'warning'   => fn($state) => in_array($state, [
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV,
                        Pengajuan::STATUS_REKOMENDASI_IT,
                        Pengajuan::STATUS_SURVEI_GA,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                    ]),
                    'info'      => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                    'success'   => Pengajuan::STATUS_SELESAI,
                ]),
            TextColumn::make('created_at')->label('Tanggal Dibuat')->dateTime('d M Y H:i')->sortable(),
            TextColumn::make('total_nilai')->label('Total Nilai')->money('IDR')->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading('')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $record->load(['items', 'items.surveiHargas']);
                    $record->estimasi_pengadaan = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $record->estimasi_perbaikan = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $form->fill($record->toArray());
                })->form([
                    Section::make('Detail Pengajuan')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('kode_pengajuan')->disabled(),
                            TextInput::make('status')->disabled(),
                            TextInput::make('total_nilai')->label('Total Nilai'),
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
                        Grid::make(2)->schema([
                            TextInput::make('rekomendasi_it_tipe')->label('Rekomendasi Tipe dari IT')->disabled(),
                            Textarea::make('rekomendasi_it_catatan')->label('Rekomendasi Catatan dari IT')->disabled(),
                        ])->visible(fn($record) => !empty($record?->rekomendasi_it_tipe)),

                        Textarea::make('catatan_revisi')->label('Catatan Approval Sebelumnya')->disabled(),
                        Grid::make(2)->schema([
                            TextInput::make('estimasi_pengadaan')->label('Estimasi Biaya Pengadaan')->disabled(),
                            TextInput::make('estimasi_perbaikan')->label('Estimasi Biaya Perbaikan')->disabled(),
                            TextInput::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->disabled(),
                            TextInput::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->disabled(),
                            Textarea::make('budget_catatan_pengadaan')->label('Catatan Budget Pengadaan')->disabled(),
                            Textarea::make('budget_catatan_perbaikan')->label('Catatan Budget Perbaikan')->disabled(),
                            Textarea::make('catatan_revisi')->label('Riwayat Catatan Approval')->disabled()->columnSpanFull(),
                        ]),
                    ]),
                ]),
        ];
    }
}
