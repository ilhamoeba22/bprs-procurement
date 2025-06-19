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
        return Pengajuan::query()->with(['pemohon.divisi'])->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('total_nilai')->label('Nilai Pengajuan')->money('IDR'),
            BadgeColumn::make('budget_status')->label('Status Budget'),
            BadgeColumn::make('status'),
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

            Action::make('process_approval')
                ->label('Proses Keputusan')
                ->color('primary')->icon('heroicon-o-check-circle')
                ->form([
                    // === PERBAIKAN DI SINI: Menyederhanakan form ===
                    Radio::make('keputusan')
                        ->label('Keputusan')
                        ->options(['Setuju' => 'Setuju', 'Tolak' => 'Tolak'])
                        ->required(),
                    Textarea::make('kadiv_ga_catatan')
                        ->label('Catatan Keputusan (Wajib diisi jika ditolak)'),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    if ($data['keputusan'] === 'Tolak' && empty($data['kadiv_ga_catatan'])) {
                        Notification::make()->title('Catatan wajib diisi untuk menolak pengajuan.')->danger()->send();
                        return;
                    }

                    $catatan = $record->catatan_revisi;
                    if (!empty($data['kadiv_ga_catatan'])) {
                        $user = Auth::user()->nama_user;
                        $catatan .= "\n\n[Keputusan oleh Kadiv GA: {$user}]\n" . $data['kadiv_ga_catatan'];
                    }

                    if ($data['keputusan'] === 'Setuju') {
                        $newStatus = '';
                        $budgetStatus = $record->budget_status;
                        $nilai = $record->total_nilai;

                        if ($budgetStatus === 'Budget Tersedia' && $nilai <= 5000000) {
                            $newStatus = Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA;
                        } elseif ($budgetStatus === 'Budget Habis' || $budgetStatus === 'Budget Tidak Ada di RBB' || ($nilai > 5000000 && $nilai <= 100000000)) {
                            $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL;
                        } elseif ($nilai > 100000000) {
                            $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA;
                        }

                        $record->update([
                            'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                            'catatan_revisi' => trim($catatan),
                            'status' => $newStatus,
                        ]);
                        Notification::make()->title('Pengajuan berhasil diproses')->success()->send();
                    } else { // Jika keputusan adalah 'Tolak'
                        $record->update([
                            'status' => Pengajuan::STATUS_DITOLAK,
                            'catatan_revisi' => trim($catatan),
                        ]);
                        Notification::make()->title('Pengajuan ditolak')->danger()->send();
                    }
                }),
        ];
    }
}
