<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Infolists\Infolist;
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
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Forms;

class PersetujuanDirekturUtama extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static string $view = 'filament.pages.persetujuan-direktur-utama';
    protected static ?string $navigationLabel = 'Persetujuan Direktur Utama';
    protected static ?int $navigationSort = 10;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Direktur Utama)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Direktur Utama', 'Super Admin']);
    }

    // PERBAIKAN 1: Mengubah Kueri untuk Menampilkan Riwayat
    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon']);

        if (! $user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA)
                    ->orWhere('direktur_utama_approved_by', $user->id_user);
            });
        } else {
            $query->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA)
                ->orWhereNotNull('direktur_utama_approved_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Tiket Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            BadgeColumn::make('status')->label('Status Saat Ini'),
            BadgeColumn::make('tindakan_saya')
                ->label('Tindakan Saya')
                ->state(function (Pengajuan $record): string {
                    if (!$record->direktur_utama_approved_by) {
                        return 'Menunggu Aksi';
                    }
                    if ($record->status === Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA) {
                        return 'Ditolak';
                    }
                    return 'Disetujui';
                })
                ->color(fn(string $state): string => match ($state) {
                    'Disetujui' => 'success',
                    'Ditolak' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('created_at')->label('Tanggal Dibuat')->dateTime('d M Y H:i'),
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
                })
                ->form([
                    Section::make('Detail Pengajuan')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kode_pengajuan')->disabled(),
                                TextInput::make('status')->disabled(),
                                TextInput::make('total_nilai')->label('Total Nilai'),
                            ]),
                            Repeater::make('items')->relationship()->label('')->schema([
                                Grid::make(6)->schema([
                                    TextInput::make('kategori_barang')->disabled()->columnSpan(2),
                                    TextInput::make('nama_barang')->disabled()->columnSpan(3),
                                    TextInput::make('kuantitas')->disabled()->columnSpan(1),
                                ]),
                                Grid::make(2)->schema([
                                    Textarea::make('spesifikasi')->disabled(),
                                    Textarea::make('justifikasi')->disabled(),
                                ]),
                            ])->columns(2)->disabled()->addActionLabel('Tambah Barang'),
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
            Action::make('approve')
                ->label('Setujui')->color('success')->icon('heroicon-o-check-circle')->requiresConfirmation()
                ->action(function (Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                        'direktur_utama_approved_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Pengajuan disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA),
            Action::make('reject')
                ->label('Tolak')->color('danger')->icon('heroicon-o-x-circle')->requiresConfirmation()
                ->form([Textarea::make('catatan_revisi')->label('Alasan Penolakan')->required(),])
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                        'catatan_revisi' => $record->catatan_revisi . "\n\n[Ditolak oleh Direktur Utama: " . Auth::user()->nama_user . "]\n" . $data['catatan_revisi'],
                        'direktur_utama_approved_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Pengajuan ditolak')->danger()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA),
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
