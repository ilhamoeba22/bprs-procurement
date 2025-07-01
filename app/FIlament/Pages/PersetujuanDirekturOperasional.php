<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Pengajuan;
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
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;

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

    // PERBAIKAN 1: Mengubah Kueri untuk Menampilkan Riwayat
    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon']);

        if (! $user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL)
                    ->orWhere('direktur_operasional_approved_by', $user->id_user);
            });
        } else {
            $query->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL)
                ->orWhereNotNull('direktur_operasional_approved_by');
        }

        return $query->latest();
    }

    // PERBAIKAN 2: Menambahkan Kolom "Tindakan Saya"
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('total_nilai')->label('Nilai Pengajuan')->money('IDR'),
            BadgeColumn::make('status')->label('Status Saat Ini'),
            BadgeColumn::make('tindakan_saya')
                ->label('Tindakan Saya')
                ->state(function (Pengajuan $record): string {
                    if (!$record->direktur_operasional_approved_by) {
                        return 'Menunggu Aksi';
                    }
                    if (in_array($record->status, [Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL])) {
                        return 'Ditolak';
                    }
                    return 'Disetujui';
                })
                ->color(fn(string $state): string => match ($state) {
                    'Disetujui' => 'success',
                    'Ditolak' => 'danger',
                    default => 'gray',
                }),
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
                        'direktur_operasional_approved_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Pengajuan disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL),

            Action::make('reject')
                ->label('Tolak')->color('danger')->icon('heroicon-o-x-circle')->requiresConfirmation()
                ->form([Forms\Components\Textarea::make('catatan_revisi_direksi')->label('Alasan Penolakan')->required(),])
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                        'catatan_revisi' => $record->catatan_revisi . "\n\n[Ditolak oleh Direktur Operasional: " . Auth::user()->nama_user . "]\n" . $data['catatan_revisi_direksi'],
                        'direktur_operasional_approved_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Pengajuan ditolak')->danger()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL),
        ];
    }
}
