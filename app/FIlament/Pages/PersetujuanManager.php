<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
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
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\Textarea as FormsTextarea;

class PersetujuanManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static string $view = 'filament.pages.persetujuan-manager';
    protected static ?string $navigationLabel = 'Persetujuan Manager';
    protected static ?int $navigationSort = 3;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Manager)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Manager', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();

        return Pengajuan::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER)
                    ->whereHas('pemohon', fn(Builder $q) => $q->where('id_divisi', $user->id_divisi));
            })
            ->orWhere('manager_approved_by', $user->id_user);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            BadgeColumn::make('status'),

            // === KOLOM BARU DI SINI ===
            // TextColumn::make('tindakan_saya')
            //     ->label('Tindakan Saya')
            //     ->state(function (Pengajuan $record): string {
            //         if ($record->manager_approved_by !== Auth::id()) {
            //             return 'Menunggu Aksi';
            //         }

            //         if ($record->status === Pengajuan::STATUS_DITOLAK_MANAGER) {
            //             return 'Ditolak';
            //         }

            //         return 'Disetujui';
            //     })
            //     ->badge()
            //     ->color(function (Pengajuan $record): string {
            //         if ($record->manager_approved_by !== Auth::id()) {
            //             return 'gray';
            //         }
            //         if ($record->status === Pengajuan::STATUS_DITOLAK_MANAGER) {
            //             return 'danger';
            //         }
            //         return 'success';
            //     }),

            TextColumn::make('created_at')->label('Tanggal Dibuat')->dateTime('d M Y H:i'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->form([
                    Section::make('Detail Pengajuan')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kode_pengajuan')->disabled(),
                                TextInput::make('status')->disabled(),
                                TextInput::make('total_nilai')->prefix('Rp')->label('Total Nilai')->disabled(),
                            ]),
                            FormsTextarea::make('catatan_revisi')->label('Catatan Approval/Revisi')->disabled()->columnSpanFull(),
                        ]),
                    Section::make('Opsi Pembayaran (Hasil Survei GA)')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('opsi_pembayaran')->label('Opsi Pembayaran')->disabled(),
                                DatePicker::make('tanggal_dp')->label('Tanggal DP')->disabled(),
                                DatePicker::make('tanggal_pelunasan')->label('Tanggal Pelunasan')->disabled(),
                            ])
                        ])
                        ->visible(fn($record) => !empty($record?->opsi_pembayaran)),
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
                                    FormsTextarea::make('spesifikasi')->disabled()->columnSpanFull(),
                                    FormsTextarea::make('justifikasi')->disabled()->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->disabled(),
                        ]),
                ]),
            Action::make('approve')
                ->label('Setujui')->color('success')->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function (Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV,
                        'manager_approved_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Pengajuan disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER),

            Action::make('reject')
                ->label('Tolak')->color('danger')->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([Textarea::make('catatan_revisi')->label('Alasan Penolakan')->required(),])
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK_MANAGER,
                        'catatan_revisi' => "[Ditolak oleh Manager: " . Auth::user()->nama_user . "]\n" . $data['catatan_revisi'],
                    ]);
                    Notification::make()->title('Pengajuan ditolak')->danger()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER),
        ];
    }
}
