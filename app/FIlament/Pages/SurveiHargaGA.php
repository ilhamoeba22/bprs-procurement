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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;

class SurveiHargaGA extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string $view = 'filament.pages.survei-harga-g-a';
    protected static ?string $navigationLabel = 'Survei Harga GA';
    protected static ?int $navigationSort = 6;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Survei Harga GA)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['General Affairs', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        return Pengajuan::query()->with(['pemohon.divisi', 'items'])->where('status', Pengajuan::STATUS_SURVEI_GA);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            BadgeColumn::make('status'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')->form([
                Section::make('Detail Pengajuan')->schema([Grid::make(3)->schema([TextInput::make('kode_pengajuan')->disabled(), TextInput::make('status')->disabled(), TextInput::make('total_nilai')->prefix('Rp')->label('Total Nilai')->disabled(),]), Textarea::make('catatan_revisi')->label('Catatan Approval/Revisi')->disabled()->columnSpanFull(),]),
                Section::make('Items')->schema([Repeater::make('items')->relationship()->schema([Grid::make(3)->schema([TextInput::make('kategori_barang')->disabled(), TextInput::make('nama_barang')->disabled()->columnSpan(2), TextInput::make('kuantitas')->disabled(),]), Textarea::make('spesifikasi')->disabled()->columnSpanFull(), Textarea::make('justifikasi')->disabled()->columnSpanFull(),])->columns(2)->disabled(),]),
            ]),

            Action::make('submit_survey')
                ->label('Input Hasil Survei')->color('info')->icon('heroicon-o-document-check')
                ->form([
                    Repeater::make('items')
                        ->relationship()
                        ->schema([
                            TextInput::make('nama_barang')->disabled()->columnSpanFull(),
                            Repeater::make('surveiHargas')
                                ->relationship()
                                ->schema([
                                    TextInput::make('nama_vendor')->required(),
                                    TextInput::make('harga')->numeric()->required()->prefix('Rp'),
                                    FileUpload::make('bukti_path')->required()->directory('bukti-survei')->visibility('private'),
                                ])->minItems(3)->maxItems(3)->addActionLabel('Tambah Pembanding'),
                        ])->columnSpanFull(),
                ])
                ->action(function (Pengajuan $record) {
                    $totalNilaiPengajuan = 0;
                    foreach ($record->items as $item) {
                        $hargaTermurah = $item->surveiHargas()->min('harga');
                        $item->update(['harga_final' => $hargaTermurah]);
                        $totalNilaiPengajuan += $hargaTermurah * $item->kuantitas;
                    }

                    // Setelah survei, SELALU kirim ke Tim Budgeting
                    $record->update([
                        'total_nilai' => $totalNilaiPengajuan,
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET, // <-- Tujuan baru
                    ]);
                    Notification::make()->title('Hasil survei berhasil disubmit')->success()->send();
                }),
        ];
    }
}
