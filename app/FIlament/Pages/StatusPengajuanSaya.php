<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\Pengajuan;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;

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
        return Pengajuan::query()
            ->with([
                'pemohon',
                'items'
            ])
            ->where('id_user_pemohon', Auth::id())
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode Pengajuan')->searchable(),
            BadgeColumn::make('status')
                ->colors([
                    'gray' => Pengajuan::STATUS_DRAFT,
                    'danger' => Pengajuan::STATUS_DITOLAK,
                    'warning' => fn($state) => in_array($state, [
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV,
                        Pengajuan::STATUS_REKOMENDASI_IT,
                        Pengajuan::STATUS_SURVEI_GA,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKSI,
                        'Menunggu Approval Kadiv GA'
                    ]),
                    'success' => Pengajuan::STATUS_DISETUJUI,
                ]),
            TextColumn::make('total_nilai')->label('Total Nilai')->money('IDR')->sortable(),
            TextColumn::make('created_at')->label('Tanggal Dibuat')->dateTime('d M Y H:i')->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()
                ->label('Lihat Detail')
                ->form([
                    Section::make('Detail Pengajuan')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kode_pengajuan')->disabled(),
                                TextInput::make('pemohon.nama_user')->label('Nama Pemohon')->disabled(),
                                TextInput::make('status')->disabled(),
                                TextInput::make('total_nilai')->prefix('Rp')->label('Total Nilai')->disabled()->columnSpan(2),
                            ]),
                            Textarea::make('catatan_revisi')->label('Catatan Approval/Revisi')->disabled()->columnSpanFull(),
                        ]),
                    Section::make('Items')
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('kategori_barang')->disabled(),
                                        TextInput::make('nama_barang')->disabled()->columnSpan(2),
                                        TextInput::make('kuantitas')->disabled(),
                                    ]),
                                    Textarea::make('spesifikasi')->disabled()->columnSpanFull(),
                                    Textarea::make('justifikasi')->disabled()->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->disabled(),
                        ]),
                ])
        ];
    }
}
