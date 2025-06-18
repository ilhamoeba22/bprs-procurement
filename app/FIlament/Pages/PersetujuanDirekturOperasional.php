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
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\RepeatableEntry;

class PersetujuanDirekturOperasional extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static string $view = 'filament.pages.persetujuan-direktur-operasional';
    protected static ?string $navigationLabel = 'Persetujuan Direktur Operasional';
    protected static ?int $navigationSort = 9;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Direktur Operasional)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Direktur Operasional', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        return Pengajuan::query()->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('total_nilai')->label('Nilai Pengajuan')->money('IDR'),
            BadgeColumn::make('status'),
            TextColumn::make('created_at')->label('Tanggal Dibuat')->dateTime('d M Y H:i'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')->infolist(fn(Infolist $infolist) => $this->getDetailInfolist($infolist)),
            Action::make('approve')
                ->label('Setujui')->color('success')->icon('heroicon-o-check-circle')->requiresConfirmation()
                ->action(fn(Pengajuan $record) => $record->update(['status' => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA])),
            Action::make('reject')
                ->label('Tolak')->color('danger')->icon('heroicon-o-x-circle')->requiresConfirmation()
                ->form([Textarea::make('catatan_revisi')->label('Alasan Penolakan')->required(),])
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK,
                        'catatan_revisi' => $record->catatan_revisi . "\n\n[Ditolak oleh Direktur Operasional: " . Auth::user()->nama_user . "]\n" . $data['catatan_revisi'],
                    ]);
                    Notification::make()->title('Pengajuan ditolak')->danger()->send();
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
