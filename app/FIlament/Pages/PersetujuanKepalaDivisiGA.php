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

    protected function getTableQuery(): Builder
    {
        return Pengajuan::query()->with(['items.surveiHargas'])->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA);
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
            //         if ($record->kadiv_approved_by !== Auth::id()) {
            //             return 'Menunggu Aksi';
            //         }
            //         if ($record->status === Pengajuan::STATUS_DITOLAK_KADIV_GA) {
            //             return 'Ditolak';
            //         }
            //         return 'Disetujui';
            //     })
            //     ->badge()
            //     ->color(function (Pengajuan $record): string {
            //         if ($record->kadiv_approved_by !== Auth::id()) {
            //             return 'gray';
            //         }
            //         if ($record->status === Pengajuan::STATUS_DITOLAK_KADIV_GA) {
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
                ->mountUsing(fn(Forms\Form $form, Pengajuan $record) => $form->fill($record->load('items')->toArray()))
                ->form([
                    Section::make('Detail Pengajuan')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kode_pengajuan')->disabled(),
                                TextInput::make('status')->disabled(),
                            ]),
                            Textarea::make('catatan_revisi')->label('Catatan Approval Sebelumnya')->disabled()->columnSpanFull(),

                            // Menampilkan Rekomendasi IT jika ada
                            Grid::make(2)->schema([
                                TextInput::make('rekomendasi_it_tipe')->label('Rekomendasi Tipe dari IT')->disabled(),
                                Textarea::make('rekomendasi_it_catatan')->label('Rekomendasi Catatan dari IT')->disabled(),
                            ])->visible(fn($record) => !empty($record?->rekomendasi_it_tipe)),
                        ]),
                    // PERBAIKAN DI SINI: Menambahkan bagian baru untuk menampilkan hasil budget control
                    Section::make('Hasil Budget Control')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('budget_status')->label('Status Ketersediaan Budget')->disabled(),
                                Textarea::make('budget_catatan')->label('Catatan Tim Budgeting')->disabled()->columnSpanFull(),
                            ])
                        ])
                        // Bagian ini hanya akan muncul jika data budget_status sudah ada
                        ->visible(fn($record) => !empty($record?->budget_status)),

                    Section::make('Items')
                        ->schema([
                            Repeater::make('items')->relationship()->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('kategori_barang')->disabled(),
                                    TextInput::make('nama_barang')->disabled()->columnSpan(2),
                                    TextInput::make('kuantitas')->disabled(),
                                ]),
                                Textarea::make('spesifikasi')->disabled()->columnSpanFull(),
                                Textarea::make('justifikasi')->disabled()->columnSpanFull(),
                            ])->columns(2)->disabled(),
                        ]),
                ]),

            Action::make('process_decision')
                ->label('Proses Keputusan')->color('primary')->icon('heroicon-o-check-circle')
                ->form(function (Pengajuan $record) {
                    // Hitung total estimasi untuk ditampilkan
                    $totalPengadaan = $record->items->reduce(fn($carry, $item) => $carry + (($item->surveiHargas()->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $item->kuantitas), 0);
                    $totalPerbaikan = $record->items->reduce(fn($carry, $item) => $carry + (($item->surveiHargas()->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $item->kuantitas), 0);

                    return [
                        Forms\Components\Section::make('Ringkasan Informasi')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Placeholder::make('estimasi_pengadaan')->label('Estimasi Biaya Pengadaan')->content('Rp ' . number_format($totalPengadaan, 0, ',', '.')),
                                Forms\Components\Placeholder::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->content($record->budget_status_pengadaan),
                                Forms\Components\Placeholder::make('estimasi_perbaikan')->label('Estimasi Biaya Perbaikan')->content('Rp ' . number_format($totalPerbaikan, 0, ',', '.')),
                                Forms\Components\Placeholder::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->content($record->budget_status_perbaikan),
                                Forms\Components\Textarea::make('budget_catatan')->label('Catatan Tim Budgeting')->default($record->budget_catatan_pengadaan . "\n" . $record->budget_catatan_perbaikan)->disabled()->columnSpanFull(),
                            ]),

                        Forms\Components\Radio::make('keputusan_final')
                            ->label('Persetujuan Final')
                            ->options(['Pengadaan' => 'Lanjutkan dengan Pengadaan', 'Perbaikan' => 'Lanjutkan dengan Perbaikan', 'Tolak' => 'Tolak Pengajuan'])
                            ->required(),

                        Forms\Components\Textarea::make('kadiv_ga_catatan')->label('Catatan Keputusan (Wajib diisi jika ditolak)'),
                    ];
                })
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
                        $record->update(['status' => Pengajuan::STATUS_DITOLAK_KADIV_GA, 'catatan_revisi' => trim($catatan)]);
                        Notification::make()->title('Pengajuan ditolak')->danger()->send();
                        return;
                    }

                    // Tentukan nilai final dan status budget final berdasarkan pilihan Kadiv GA
                    $nilaiFinal = 0;
                    $budgetFinal = '';
                    if ($data['keputusan_final'] === 'Pengadaan') {
                        $nilaiFinal = $record->items->reduce(fn($carry, $item) => $carry + (($item->surveiHargas()->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $item->kuantitas), 0);
                        $budgetFinal = $record->budget_status_pengadaan;
                    } else { // Jika Perbaikan
                        $nilaiFinal = $record->items->reduce(fn($carry, $item) => $carry + (($item->surveiHargas()->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $item->kuantitas), 0);
                        $budgetFinal = $record->budget_status_perbaikan;
                    }

                    // Tentukan alur selanjutnya berdasarkan logika yang sama
                    $newStatus = '';
                    if ($budgetFinal === 'Budget Tersedia' && $nilaiFinal <= 5000000) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA;
                    } elseif ($budgetFinal === 'Budget Habis' || $budgetFinal === 'Budget Tidak Ada di RBB' || ($nilaiFinal > 5000000 && $nilaiFinal <= 100000000)) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL;
                    } elseif ($nilaiFinal > 100000000) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA;
                    }

                    $record->update([
                        'total_nilai' => $nilaiFinal, // Set nilai final di sini
                        'kadiv_ga_decision_type' => $data['keputusan_final'],
                        'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                        'catatan_revisi' => trim($catatan),
                        'status' => $newStatus,
                    ]);
                    Notification::make()->title('Keputusan berhasil diproses')->success()->send();
                }),
        ];
    }
}
