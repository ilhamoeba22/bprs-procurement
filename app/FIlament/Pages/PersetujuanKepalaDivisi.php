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
use Filament\Forms\Components\Textarea as FormsTextarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;

class PersetujuanKepalaDivisi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static string $view = 'filament.pages.persetujuan-kepala-divisi';
    protected static ?string $navigationLabel = 'Persetujuan Kepala Divisi';
    protected static ?int $navigationSort = 4;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Kepala Divisi)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Kepala Divisi', 'Kepala Divisi IT', 'Kepala Divisi GA', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV);

        if (! $user->hasRole('Super Admin')) {
            $query->whereHas('pemohon', function (Builder $q) use ($user) {
                $q->where('id_divisi', $user->id_divisi);
            });
        }

        return $query;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            BadgeColumn::make('status'),
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
                ->form([
                    FormsTextarea::make('catatan_approval')->label('Catatan Persetujuan (Opsional)'),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $catatan = $record->catatan_revisi;
                    if (!empty($data['catatan_approval'])) {
                        $user = Auth::user()->nama_user;
                        $catatan .= "\n\n[Disetujui oleh Kadiv: {$user}]\n" . $data['catatan_approval'];
                    }

                    $needsITRecommendation = $record->items()
                        ->whereIn('kategori_barang', ['1a. Software', '2a. Komputer & Hardware Sistem Informasi'])
                        ->exists();
                    $newStatus = $needsITRecommendation ? Pengajuan::STATUS_REKOMENDASI_IT : Pengajuan::STATUS_SURVEI_GA;

                    $record->update([
                        'status' => $newStatus,
                        'catatan_revisi' => trim($catatan)
                    ]);
                    Notification::make()->title('Pengajuan disetujui')->success()->send();
                }),

            Action::make('reject')
                ->label('Tolak')->color('danger')->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([
                    FormsTextarea::make('catatan_revisi')->label('Alasan Penolakan')->required(),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK,
                        'catatan_revisi' => $data['catatan_revisi'],
                    ]);
                    Notification::make()->title('Pengajuan ditolak')->danger()->send();
                }),
        ];
    }
}
