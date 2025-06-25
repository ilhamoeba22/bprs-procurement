<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\SurveiHarga;
use Filament\Tables\Table;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;

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
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas']);

        if (! $user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', Pengajuan::STATUS_SURVEI_GA)
                    ->orWhere('ga_surveyed_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', [
                Pengajuan::STATUS_SURVEI_GA,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
            ])->orWhereNotNull('ga_surveyed_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            BadgeColumn::make('status'),
            TextColumn::make('created_at')->label('Tanggal Dibuat')->dateTime('d M Y H:i'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->mountUsing(fn(Forms\Form $form, Pengajuan $record) => $form->fill($record->load('items', 'items.surveiHargas')->toArray()))
                ->form([
                    Section::make('Detail Pengajuan')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('kode_pengajuan')->disabled(),
                            TextInput::make('status')->disabled(),
                        ]),
                        Textarea::make('catatan_revisi')->label('Catatan Approval Sebelumnya')->disabled()->columnSpanFull(),
                        Grid::make(2)->schema([
                            TextInput::make('rekomendasi_it_tipe')->label('Rekomendasi Tipe dari IT')->disabled(),
                            Textarea::make('rekomendasi_it_catatan')->label('Rekomendasi Catatan dari IT')->disabled(),
                        ])->visible(fn($record) => !empty($record?->rekomendasi_it_tipe)),
                    ]),
                    Section::make('Hasil Survei Harga')
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    TextInput::make('nama_barang')->disabled()->columnSpanFull(),
                                    Repeater::make('surveiHargas')
                                        ->label('Detail Harga Pembanding')
                                        ->relationship()
                                        ->schema([
                                            Grid::make(3)->schema([
                                                TextInput::make('tipe_survei')->disabled(),
                                                TextInput::make('nama_vendor')->label('Vendor/Link')->disabled(),
                                                TextInput::make('harga')->prefix('Rp')->disabled(),
                                                TextInput::make('opsi_pembayaran')->label('Opsi Bayar')->disabled(),
                                                TextInput::make('nominal_dp')->label('Nominal DP')->prefix('Rp')->disabled()
                                                    ->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                                DatePicker::make('tanggal_dp')->label('Tanggal DP')->disabled()
                                                    ->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                                DatePicker::make('tanggal_pelunasan')->label('Tanggal Pelunasan')->disabled()
                                                    ->visible(fn($get) => in_array($get('opsi_pembayaran'), ['Bisa DP', 'Langsung Lunas'])),
                                            ]),
                                        ])->disabled()->columns(1),
                                ])->columnSpanFull()->disabled(),
                        ]),
                ]),
            Action::make('submit_survey')
                ->label('Input Hasil Survei')->color('info')->icon('heroicon-o-document-check')
                ->form(function (Pengajuan $record) {
                    $itemsSchema = [];
                    foreach ($record->items as $item) {
                        $itemsSchema[] = Forms\Components\Section::make('Item: ' . $item->nama_barang)
                            ->description("Kategori: {$item->kategori_barang} | Kuantitas: {$item->kuantitas}")
                            ->schema([
                                Forms\Components\Section::make('Hasil Survei jika PENGADAAN BARU')->schema([
                                    Forms\Components\Repeater::make("survei_pengadaan_{$item->id_item}")->label('Input Harga Pembanding (Pengadaan)')->schema([
                                        Forms\Components\TextInput::make('nama_vendor')->label('Nama Vendor / Link')->required(),
                                        Forms\Components\TextInput::make('harga')->numeric()->required()->prefix('Rp'),
                                        Forms\Components\FileUpload::make('bukti_path')->required()->directory('bukti-survei')->visibility('private'),
                                        Forms\Components\Radio::make('opsi_pembayaran')->label('Opsi Bayar')->options(['Bisa DP' => 'Bisa DP', 'Langsung Lunas' => 'Langsung Lunas'])->required()->live(),
                                        Forms\Components\TextInput::make('nominal_dp')->label('Nominal DP')->numeric()->prefix('Rp')->required()->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                        Forms\Components\DatePicker::make('tanggal_dp')->label('Tgl. DP')->required()->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                        Forms\Components\DatePicker::make('tanggal_pelunasan')->label('Tgl. Lunas')->required()->visible(fn($get) => in_array($get('opsi_pembayaran'), ['Bisa DP', 'Langsung Lunas'])),
                                    ])->columns(2)->minItems(3)->maxItems(3)->addActionLabel('Tambah Pembanding'),
                                ]),
                                Forms\Components\Section::make('Hasil Survei jika PERBAIKAN')->schema([
                                    Forms\Components\Repeater::make("survei_perbaikan_{$item->id_item}")->label('Input Harga Pembanding (Perbaikan)')->schema([
                                        Forms\Components\TextInput::make('nama_vendor')->label('Nama Vendor / Link')->required(),
                                        Forms\Components\TextInput::make('harga')->numeric()->required()->prefix('Rp'),
                                        Forms\Components\FileUpload::make('bukti_path')->required()->directory('bukti-survei')->visibility('private'),
                                        Forms\Components\Radio::make('opsi_pembayaran')->label('Opsi Bayar')->options(['Bisa DP' => 'Bisa DP', 'Langsung Lunas' => 'Langsung Lunas'])->required()->live(),
                                        Forms\Components\TextInput::make('nominal_dp')->label('Nominal DP')->numeric()->prefix('Rp')->required()->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                        Forms\Components\DatePicker::make('tanggal_dp')->label('Tgl. DP')->required()->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                        Forms\Components\DatePicker::make('tanggal_pelunasan')->label('Tgl. Lunas')->required()->visible(fn($get) => in_array($get('opsi_pembayaran'), ['Bisa DP', 'Langsung Lunas'])),
                                    ])->columns(2)->minItems(1)->maxItems(1)->addActionLabel('Tambah Pembanding'),
                                ]),
                            ]);
                    }
                    return $itemsSchema;
                })
                // === PERBAIKAN LOGIKA PENYIMPANAN DI SINI ===
                ->action(function (array $data, Pengajuan $record) {
                    foreach ($record->items as $item) {
                        // Proses dan simpan data untuk survei pengadaan
                        $pengadaanKey = "survei_pengadaan_{$item->id_item}";
                        if (isset($data[$pengadaanKey]) && is_array($data[$pengadaanKey])) {
                            $item->surveiHargas()->where('tipe_survei', 'Pengadaan')->delete();
                            foreach ($data[$pengadaanKey] as $surveyData) {
                                $surveyData['tipe_survei'] = 'Pengadaan';
                                $item->surveiHargas()->create($surveyData);
                            }
                        }

                        // Proses dan simpan data untuk survei perbaikan
                        $perbaikanKey = "survei_perbaikan_{$item->id_item}";
                        if (isset($data[$perbaikanKey]) && is_array($data[$perbaikanKey])) {
                            $item->surveiHargas()->where('tipe_survei', 'Perbaikan')->delete();
                            foreach ($data[$perbaikanKey] as $surveyData) {
                                $surveyData['tipe_survei'] = 'Perbaikan';
                                $item->surveiHargas()->create($surveyData);
                            }
                        }
                    }

                    // Cukup ubah status untuk diteruskan ke Budget Control.
                    // Tidak ada lagi update 'opsi_pembayaran' di sini.
                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                        'ga_surveyed_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Hasil survei berhasil disubmit')->success()->send();
                })->modalWidth('6xl'),
        ];
    }
}
