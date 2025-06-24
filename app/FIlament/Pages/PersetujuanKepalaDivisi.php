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
        $query = Pengajuan::query();

        // Super Admin melihat semua pengajuan di tahap ini
        if ($user->hasRole('Super Admin')) {
            return $query->whereIn('status', [
                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV,
                Pengajuan::STATUS_REKOMENDASI_IT,
                Pengajuan::STATUS_SURVEI_GA
            ])->orWhereNotNull('kadiv_approved_by');
        }

        // Kadiv hanya melihat pengajuan yang relevan untuknya
        return $query->where(function (Builder $query) use ($user) {
            $query->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV)
                ->whereHas('pemohon', fn(Builder $q) => $q->where('id_divisi', $user->id_divisi));
        })->orWhere('kadiv_approved_by', $user->id_user);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            BadgeColumn::make('status'),
            // TextColumn::make('tindakan_saya')
            //     ->label('Tindakan Saya')
            //     ->state(function (Pengajuan $record): string {
            //         if ($record->manager_approved_by !== Auth::id()) {
            //             return 'Menunggu Aksi';
            //         }

            //         if ($record->status === Pengajuan::STATUS_DITOLAK_KADIV) {
            //             return 'Ditolak';
            //         }

            //         return 'Disetujui';
            //     })
            //     ->badge()
            //     ->color(function (Pengajuan $record): string {
            //         if ($record->manager_approved_by !== Auth::id()) {
            //             return 'gray';
            //         }
            //         if ($record->status === Pengajuan::STATUS_DITOLAK_KADIV) {
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
                        'catatan_revisi' => trim($catatan),
                        'kadiv_approved_by' => Auth::id(), // Catat siapa yang approve
                    ]);
                    Notification::make()->title('Pengajuan disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV),

            Action::make('reject')
                ->label('Tolak')->color('danger')->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([FormsTextarea::make('catatan_revisi')->label('Alasan Penolakan')->required(),])
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK_KADIV,
                        'catatan_revisi' => $data['catatan_revisi'],
                        'kadiv_approved_by' => Auth::id(), // Catat siapa yang tolak
                    ]);
                    Notification::make()->title('Pengajuan ditolak')->danger()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV),
        ];
    }
}
