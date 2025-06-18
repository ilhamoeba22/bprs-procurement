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
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\RepeatableEntry;

class PencairanDanaOperasional extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static string $view = 'filament.pages.pencairan-dana-operasional';
    protected static ?string $navigationLabel = 'Pencairan Dana';
    protected static ?int $navigationSort = 11;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Pencairan Dana)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Kepala Divisi Operasional', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        return Pengajuan::query()->where('status', Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('total_nilai')->label('Nilai Pengajuan')->money('IDR'),
            BadgeColumn::make('status'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')->infolist(fn(Infolist $infolist) => $this->getDetailInfolist($infolist)),
            Action::make('process_disbursement')
                ->label('Proses Pencairan Dana')->color('success')->icon('heroicon-o-check-circle')->requiresConfirmation()
                ->action(function (Pengajuan $record) {
                    $record->update(['status' => Pengajuan::STATUS_SELESAI]);
                    Notification::make()->title('Pencairan dana diproses')->success()->send();
                }),
        ];
    }

    protected function getDetailInfolist(Infolist $infolist): Infolist
    {
        return $infolist->record($infolist->getRecord())->schema([
            InfolistSection::make('Detail Pengajuan')
                ->columns(3)
                ->schema([
                    TextEntry::make('kode_pengajuan'),
                    TextEntry::make('status'),
                    TextEntry::make('total_nilai')->money('IDR')->label('Total Nilai'),
                    TextEntry::make('catatan_revisi')->label('Catatan Approval/Revisi')->columnSpanFull(),
                ]),
            InfolistSection::make('Items')
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            TextEntry::make('kategori_barang'),
                            TextEntry::make('nama_barang'),
                            TextEntry::make('kuantitas'),
                            TextEntry::make('spesifikasi')->columnSpanFull(),
                            TextEntry::make('justifikasi')->columnSpanFull(),
                        ])->columns(2),
                ]),
        ]);
    }
}
