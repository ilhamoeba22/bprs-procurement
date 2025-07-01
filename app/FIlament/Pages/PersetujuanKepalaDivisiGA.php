<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Radio;
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

class PersetujuanKepalaDivisiGA extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static string $view = 'filament.pages.persetujuan-kepala-divisi-g-a';
    protected static ?string $navigationLabel = 'Persetujuan Kadiv GA';
    protected static ?int $navigationSort = 8;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Kadiv GA)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Kepala Divisi GA', 'Super Admin']);
    }

    // PERBAIKAN 1: Mengubah Kueri untuk Menampilkan Riwayat
    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['items.surveiHargas', 'pemohon']);

        if (! $user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA)
                    ->orWhere('kadiv_ga_approved_by', $user->id_user);
            });
        } else {
            $query->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA)
                ->orWhereNotNull('kadiv_ga_approved_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Tiket Pengajuan')->sortable()->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->sortable()->searchable(),
            TextColumn::make('total_nilai')->label('Total Nilai')->money('IDR')->sortable(),
            BadgeColumn::make('status')->label('Status Saat Ini'),
            BadgeColumn::make('tindakan_saya')
                ->label('Tindakan Saya')
                ->state(function (Pengajuan $record): string {
                    if (!$record->kadiv_ga_approved_by || $record->kadiv_ga_approved_by !== Auth::id()) {
                        return 'Menunggu Aksi';
                    }

                    if (in_array($record->status, [Pengajuan::STATUS_DITOLAK_KADIV_GA])) {
                        return 'Ditolak';
                    }

                    if ($record->kadiv_ga_decision_type) {
                        return 'Setuju (' . $record->kadiv_ga_decision_type . ')';
                    }

                    return 'Sudah Diproses';
                })
                ->color(function (string $state): string {
                    return match ($state) {
                        'Setuju (Pengadaan)', 'Setuju (Perbaikan)' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    };
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
            // === PERBAIKAN 2: Sederhanakan Form "Proses Keputusan" ===
            Action::make('process_decision')
                ->label('Proses Keputusan')
                ->color('primary')->icon('heroicon-o-check-circle')
                ->form([
                    Radio::make('keputusan_final')
                        ->label('Persetujuan Final')
                        ->options(['Pengadaan' => 'Lanjutkan dengan Pengadaan', 'Perbaikan' => 'Lanjutkan dengan Perbaikan', 'Tolak' => 'Tolak Pengajuan'])
                        ->required(),
                    Textarea::make('kadiv_ga_catatan')->label('Catatan Keputusan (Wajib diisi jika ditolak)'),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    if ($data['keputusan_final'] === 'Tolak' && empty($data['kadiv_ga_catatan'])) {
                        Notification::make()->title('Catatan wajib diisi untuk menolak pengajuan.')->danger()->send();
                        return;
                    }

                    $catatan = $record->catatan_revisi;
                    if (!empty($data['kadiv_ga_catatan'])) {
                        $user = Auth::user()->nama_user;
                        $catatan .= "\n\n[Keputusan oleh Kadiv GA: {$user}]\n" . $data['kadiv_ga_catatan'];
                    }

                    if ($data['keputusan_final'] === 'Tolak') {
                        $record->update([
                            'status' => Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            'catatan_revisi' => trim($catatan),
                            'kadiv_ga_approved_by' => Auth::id()
                        ]);
                        Notification::make()->title('Pengajuan ditolak')->danger()->send();
                        return;
                    }

                    $nilaiFinal = 0;
                    $budgetFinal = '';
                    if ($data['keputusan_final'] === 'Pengadaan') {
                        $nilaiFinal = $record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $i->kuantitas), 0);
                        $budgetFinal = $record->budget_status_pengadaan;
                    } else {
                        $nilaiFinal = $record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $i->kuantitas), 0);
                        $budgetFinal = $record->budget_status_perbaikan;
                    }

                    $newStatus = '';
                    if ($budgetFinal === 'Budget Tersedia' && $nilaiFinal <= 5000000) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA;
                    } elseif ($budgetFinal === 'Budget Habis' || $budgetFinal === 'Budget Tidak Ada di RBB' || ($nilaiFinal > 5000000 && $nilaiFinal <= 100000000)) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL;
                    } elseif ($nilaiFinal > 100000000) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA;
                    }

                    $record->update([
                        'total_nilai' => $nilaiFinal,
                        'kadiv_ga_decision_type' => $data['keputusan_final'],
                        'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                        'catatan_revisi' => trim($catatan),
                        'status' => $newStatus,
                        'kadiv_ga_approved_by' => Auth::id() // <-- Catat ID user
                    ]);
                    Notification::make()->title('Keputusan berhasil diproses')->success()->send();
                })
                // Tombol ini HANYA akan muncul jika statusnya masih menunggu
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA),
        ];
    }
}
