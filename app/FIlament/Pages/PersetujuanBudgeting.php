<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
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

class PersetujuanBudgeting extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.persetujuan-budgeting';
    protected static ?string $navigationLabel = 'Persetujuan Budgeting';
    protected static ?int $navigationSort = 7;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Persetujuan Budgeting)';
    }
    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Tim Budgeting', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas']);

        if (! $user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET)
                    ->orWhere('budget_approved_by', $user->id_user);
            });
        } else {
            $query->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET)
                ->orWhereNotNull('budget_approved_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            BadgeColumn::make('status')->label('Status Saat Ini'),
            BadgeColumn::make('tindakan_saya')
                ->label('Keterangan')
                ->state(function (Pengajuan $record): string {
                    if ($record->budget_approved_by !== Auth::id()) {
                        return 'Menunggu Aksi';
                    }
                    return 'Sudah Direview';
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Direview' => 'success',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            // === PERBAIKAN PADA LIHAT DETAIL ===
            ViewAction::make()->label('Detail')
                ->mountUsing(function (Form $form, Pengajuan $record) {
                    $record->load(['items', 'items.surveiHargas']);
                    $record->estimasi_pengadaan = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $record->estimasi_perbaikan = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $form->fill($record->toArray());
                })
                ->form([
                    Section::make('Detail Pengajuan')->schema([
                        Grid::make(2)->schema([
                            TextInput::make('kode_pengajuan')->disabled(),
                            TextInput::make('status')->disabled(),
                            TextInput::make('estimasi_pengadaan')->label('Estimasi Total Pengadaan')->disabled(),
                            TextInput::make('estimasi_perbaikan')->label('Estimasi Total Perbaikan')->disabled(),
                        ]),
                        Textarea::make('catatan_revisi')->label('Catatan Approval Sebelumnya')->disabled()->columnSpanFull(),
                    ]),
                    Section::make('Hasil Survei Harga')->schema([
                        Repeater::make('items')->relationship()->schema([
                            TextInput::make('nama_barang')->disabled()->columnSpanFull(),
                            Repeater::make('surveiHargas')->label('Detail Harga Pembanding')->relationship()->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('tipe_survei')->disabled(),
                                    TextInput::make('nama_vendor')->label('Vendor/Link')->disabled(),
                                    TextInput::make('harga')->prefix('Rp')->disabled(),
                                ]),
                                // ... (Detail pembayaran lainnya)
                            ])->disabled()->columns(1),
                        ])->columnSpanFull()->disabled(),
                    ]),
                ]),

            Action::make('submit_budget_review')
                ->label('Submit Review Budget')->color('primary')->icon('heroicon-o-pencil-square')
                ->form(function (Pengajuan $record) {
                    $totalPengadaan = $record->items->reduce(function ($carry, $item) {
                        // Menggunakan koleksi yang sudah di-load, bukan query baru
                        $minHarga = $item->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga');
                        return $carry + (($minHarga ?? 0) * $item->kuantitas);
                    }, 0);

                    $totalPerbaikan = $record->items->reduce(function ($carry, $item) {
                        // Menggunakan koleksi yang sudah di-load, bukan query baru
                        $minHarga = $item->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga');
                        return $carry + (($minHarga ?? 0) * $item->kuantitas);
                    }, 0);

                    return [
                        Section::make('Review Budget untuk Skenario PENGADAAN')
                            ->description('Total Estimasi: Rp ' . number_format($totalPengadaan, 0, ',', '.'))
                            ->schema([
                                Select::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->options(['Budget Tersedia' => 'Budget Tersedia', 'Budget Habis' => 'Budget Habis', 'Budget Tidak Ada di RBB' => 'Budget Tidak Ada di RBB'])->required(),
                                Textarea::make('budget_catatan_pengadaan')->label('Catatan (Opsional)'),
                            ]),

                        Section::make('Review Budget untuk Skenario PERBAIKAN')
                            ->description('Total Estimasi: Rp ' . number_format($totalPerbaikan, 0, ',', '.'))
                            ->schema([
                                Select::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->options(['Budget Tersedia' => 'Budget Tersedia', 'Budget Habis' => 'Budget Habis', 'Budget Tidak Ada di RBB' => 'Budget Tidak Ada di RBB'])->required(),
                                Textarea::make('budget_catatan_perbaikan')->label('Catatan (Opsional)'),
                            ]),
                    ];
                })
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'budget_status_pengadaan' => $data['budget_status_pengadaan'],
                        'budget_catatan_pengadaan' => $data['budget_catatan_pengadaan'],
                        'budget_status_perbaikan' => $data['budget_status_perbaikan'],
                        'budget_catatan_perbaikan' => $data['budget_catatan_perbaikan'],
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        'budget_approved_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Review budget berhasil disubmit ke Kadiv GA')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET),
        ];
    }
}
