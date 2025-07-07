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
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
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
        return Auth::user()->hasAnyRole(['Kepala Divisi Operasional', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi']);

        // FIX: Tampilkan pengajuan yang menunggu validasi ATAU yang sudah divalidasi oleh user ini
        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET)
                    ->orWhere('kadiv_ops_budget_approved_by', $user->id_user);
            });
        } else {
            // Super Admin bisa melihat semua yang terkait tahap ini
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
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            BadgeColumn::make('status')->label('Status Saat Ini'),
            // FIX: Tambahkan kolom keterangan untuk membedakan riwayat dan tugas
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
            // FIX: Tambahkan form detail ke dalam ViewAction
            ViewAction::make()->label('Detail')
                ->modalHeading('Detail Pengajuan dan Review Budget')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $record->load(['items', 'items.surveiHargas']);
                    $data = $record->toArray();

                    // Menghitung estimasi biaya untuk ditampilkan
                    $data['estimasi_pengadaan'] = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $data['estimasi_perbaikan'] = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');

                    $form->fill($data);
                })
                ->form([
                    Section::make('Detail Pengajuan')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('kode_pengajuan')->disabled(),
                            TextInput::make('status')->disabled(),
                            TextInput::make('total_nilai')->label('Total Nilai')->disabled(),
                        ]),
                        Repeater::make('items')->relationship()->label('Item Pengajuan')->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kategori_barang')->disabled(),
                                TextInput::make('nama_barang')->disabled(),
                                TextInput::make('kuantitas')->disabled(),
                            ]),
                            Grid::make(2)->schema([
                                Textarea::make('spesifikasi')->disabled(),
                                Textarea::make('justifikasi')->disabled(),
                            ]),
                        ])->columns(1)->disabled(),
                        Textarea::make('catatan_revisi')->label('Riwayat Catatan Approval')->disabled()->columnSpanFull(),
                    ])->collapsible(),

                    Section::make('Hasil Survei Harga')->description('Harap validasi informasi di bawah ini.')->schema([
                        Grid::make(2)->schema([
                            TextInput::make('estimasi_pengadaan')->label('Estimasi Biaya Pengadaan')->disabled(),
                            TextInput::make('estimasi_perbaikan')->label('Estimasi Biaya Perbaikan')->disabled(),
                            TextInput::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->disabled(),
                            TextInput::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->disabled(),
                            Textarea::make('budget_catatan_pengadaan')->label('Catatan Budget Pengadaan')->disabled(),
                            Textarea::make('budget_catatan_perbaikan')->label('Catatan Budget Perbaikan')->disabled(),
                        ]),
                    ]),
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
                // FIX: Tombol ini hanya muncul jika statusnya benar-benar menunggu validasi
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET),
        ];
    }
}
