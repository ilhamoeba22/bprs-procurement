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
use Filament\Forms;

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
            Action::make('submit_survey')
                ->label('Input Hasil Survei')->color('info')->icon('heroicon-o-document-check')
                ->form(function (Pengajuan $record) {
                    $itemsSchema = [];
                    foreach ($record->items as $item) {
                        $itemsSchema[] = Forms\Components\Section::make('Item: ' . $item->nama_barang)
                            ->schema([
                                Forms\Components\Section::make('Hasil Survei jika PENGADAAN BARU')
                                    ->schema([
                                        Forms\Components\Repeater::make("survei_pengadaan_{$item->id_item}")
                                            ->label('Input Harga Pembanding (Pengadaan)')
                                            ->schema([
                                                Forms\Components\TextInput::make('nama_vendor')->label('Nama Vendor / Link')->required(),
                                                Forms\Components\TextInput::make('harga')->numeric()->required()->prefix('Rp'),
                                                Forms\Components\FileUpload::make('bukti_path')->required()->directory('bukti-survei')->visibility('private'),
                                            ])->minItems(3)->maxItems(3)->addActionLabel('Tambah Pembanding'),
                                    ]),
                                Forms\Components\Section::make('Hasil Survei jika PERBAIKAN')
                                    ->schema([
                                        Forms\Components\Repeater::make("survei_perbaikan_{$item->id_item}")
                                            ->label('Input Harga Pembanding (Perbaikan)')
                                            ->schema([
                                                Forms\Components\TextInput::make('nama_vendor')->label('Nama Vendor / Link')->required(),
                                                Forms\Components\TextInput::make('harga')->numeric()->required()->prefix('Rp'),
                                                Forms\Components\FileUpload::make('bukti_path')->required()->directory('bukti-survei')->visibility('private'),
                                            ])->minItems(3)->maxItems(3)->addActionLabel('Tambah Pembanding'),
                                    ]),
                            ]);
                    }
                    return $itemsSchema;
                })
                ->action(function (array $data, Pengajuan $record) {
                    $totalNilaiPengajuan = 0;

                    foreach ($record->items as $item) {
                        // Proses data untuk survei pengadaan
                        $pengadaanKey = "survei_pengadaan_{$item->id_item}";
                        if (isset($data[$pengadaanKey])) {
                            $item->surveiPengadaan()->delete(); // Hapus data lama
                            foreach ($data[$pengadaanKey] as $surveyData) {
                                $item->surveiPengadaan()->create($surveyData);
                            }
                        }

                        // Proses data untuk survei perbaikan
                        $perbaikanKey = "survei_perbaikan_{$item->id_item}";
                        if (isset($data[$perbaikanKey])) {
                            $item->surveiPerbaikan()->delete(); // Hapus data lama
                            foreach ($data[$perbaikanKey] as $surveyData) {
                                $item->surveiPerbaikan()->create($surveyData);
                            }
                        }

                        // Tentukan harga final dari survei pengadaan
                        $hargaTermurah = $item->surveiPengadaan()->min('harga');
                        $item->update(['harga_final' => $hargaTermurah]);
                        $totalNilaiPengajuan += ($hargaTermurah ?? 0) * $item->kuantitas;
                    }

                    $record->update([
                        'total_nilai' => $totalNilaiPengajuan,
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                    ]);
                    Notification::make()->title('Hasil survei berhasil disubmit')->success()->send();
                })->modalWidth('5xl'),
        ];
    }
}
