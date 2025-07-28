<?php

namespace App\Filament\Pages;

use App\Models\Pengajuan;
use App\Models\RevisiHarga;
use App\Models\SurveiHarga;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class SurveiHargaGA extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string $view = 'filament.pages.survei-harga-g-a';
    protected static ?string $navigationLabel = 'Survei Harga GA';
    protected static ?int $navigationSort = 6;

    /**
     * Mendapatkan judul halaman.
     */
    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Survei Harga GA)';
    }

    /**
     * Menentukan apakah pengguna dapat mengakses halaman ini.
     */
    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['General Affairs', 'Super Admin']);
    }

    /**
     * Mendapatkan kueri untuk data tabel.
     */
    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items', 'items.surveiHargas', 'items.vendorFinal']);

        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->whereIn('status', [
                    Pengajuan::STATUS_SURVEI_GA,
                    Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                    Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                    Pengajuan::STATUS_SUDAH_BAYAR,
                    Pengajuan::STATUS_SELESAI,
                ])->orWhere('ga_surveyed_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', [
                Pengajuan::STATUS_SURVEI_GA,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                Pengajuan::STATUS_SUDAH_BAYAR,
                Pengajuan::STATUS_SELESAI,
            ])->orWhereNotNull('ga_surveyed_by');
        }

        return $query->latest();
    }

    /**
     * Menentukan kolom tabel.
     */
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            // --- KOLOM TOTAL NILAI DENGAN LOGIKA PERMANEN ---
            TextColumn::make('total_nilai')
                ->label('Total Nilai')
                ->state(function (Pengajuan $record): ?float {
                    $revisi = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas?->first();
                    // Jika ada revisi, nilai revisi menjadi nilai permanen. Jika tidak, gunakan nilai awal.
                    return $revisi?->harga_revisi ?? $record->total_nilai;
                })
                ->money('IDR')
                ->icon(function (Pengajuan $record): ?string {
                    $revisiExists = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas()->exists();
                    return $revisiExists ? 'heroicon-o-arrow-path' : null;
                })
                ->color(function (Pengajuan $record): ?string {
                    $revisiExists = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas()->exists();
                    return $revisiExists ? 'warning' : null;
                })
                ->description(function (Pengajuan $record): ?string {
                    $revisiExists = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas()->exists();
                    if ($revisiExists) {
                        return 'Nilai Awal: ' . number_format($record->total_nilai, 0, ',', '.');
                    }
                    return null;
                })
                ->sortable(),
            BadgeColumn::make('status')
                ->label('Status Saat Ini')
                ->color(fn($state) => Pengajuan::getStatusBadgeColor($state)),
            BadgeColumn::make('tindakan_saya')
                ->label('Keterangan')
                ->state(fn(Pengajuan $record): string => match ($record->status) {
                    Pengajuan::STATUS_SELESAI => 'Selesai',
                    Pengajuan::STATUS_SUDAH_BAYAR => 'Menunggu Penyelesaian',
                    default => $record->ga_surveyed_by === Auth::id() ? 'Sudah Disurvei' : 'Menunggu Aksi',
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Disurvei', 'Selesai' => 'success',
                    'Menunggu Penyelesaian' => 'warning',
                    default => 'gray',
                }),
        ];
    }

    /**
     * Mendapatkan skema formulir untuk input survei.
     */
    protected function getSurveyFormSchema(Pengajuan $record): array
    {
        $itemsForSurvey = $record->items->map(fn($item) => [
            'id_item' => $item->id_item,
            'nama_barang' => $item->nama_barang,
            'kategori_barang' => $item->kategori_barang,
        ])->toArray();

        return [
            Section::make('Input Survei Harga Berdasarkan Vendor')
                ->schema([
                    Repeater::make('survei_per_vendor')
                        ->label('Data Penawaran Vendor')
                        ->addActionLabel('Tambah Data Vendor')
                        ->minItems(1)
                        ->collapsible()
                        ->cloneable()
                        ->schema([
                            TextInput::make('nama_vendor')->label('Nama Vendor / Link')->required(),
                            Repeater::make('item_details')
                                ->label('Rincian Harga per Item')
                                ->default($itemsForSurvey)
                                ->disableItemCreation()->disableItemDeletion()->disableItemMovement()
                                ->schema([
                                    Placeholder::make('nama_barang_info')
                                        ->label(false)
                                        ->content(fn($get) => new HtmlString('<b class="text-base">' . $get('nama_barang') . '</b>' . ' <span class="text-sm text-gray-500">(' . $get('kategori_barang') . ')</span>')),
                                    Hidden::make('id_item'),
                                    Hidden::make('kategori_barang'),
                                    TextInput::make('harga')->label('Harga')->numeric()->required()->prefix('Rp'),
                                    Textarea::make('rincian_harga')->label('Rincian Harga (Opsional)'),
                                    Section::make('Detail Pajak')->collapsible()->collapsed()->schema([
                                        Radio::make('kondisi_pajak')
                                            ->label('Kondisi Pajak')
                                            ->options([
                                                'Tidak Ada Pajak' => 'Tidak Ada Pajak',
                                                'Pajak ditanggung kita' => 'Pajak ditanggung Perusahaan (Exclude)',
                                                'Pajak ditanggung Vendor' => 'Pajak ditanggung Vendor (Include)',
                                            ])
                                            ->default('Tidak Ada Pajak')
                                            ->live(),
                                        Select::make('jenis_pajak')
                                            ->label('Jenis Pajak')
                                            ->options(['PPh 21' => 'PPh 21', 'PPh 23' => 'PPh 23'])
                                            ->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')
                                            ->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                        TextInput::make('npwp_nik')
                                            ->label('NPWP / NIK')
                                            ->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')
                                            ->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                        TextInput::make('nama_pemilik_pajak')
                                            ->label('Nama Sesuai NPWP / NIK')
                                            ->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')
                                            ->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                        TextInput::make('nominal_pajak')
                                            ->label('Nominal Pajak')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')
                                            ->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                    ])->columns(2),
                                ])->columnSpanFull(),
                            Section::make('Opsi Pembayaran & Bukti (untuk vendor ini)')->schema([
                                Radio::make('metode_pembayaran')
                                    ->label('Metode Bayar')
                                    ->options(['Transfer' => 'Transfer', 'Tunai' => 'Tunai'])
                                    ->required()
                                    ->live(),
                                Radio::make('opsi_pembayaran')
                                    ->label('Opsi Bayar')
                                    ->options(['Bisa DP' => 'Bisa DP', 'Langsung Lunas' => 'Langsung Lunas'])
                                    ->required()
                                    ->live(),
                                DatePicker::make('tanggal_dp')
                                    ->label('Tgl. DP')
                                    ->required()
                                    ->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                DatePicker::make('tanggal_pelunasan')
                                    ->label('Tgl. Lunas')
                                    ->required()
                                    ->visible(fn($get) => in_array($get('opsi_pembayaran'), ['Bisa DP', 'Langsung Lunas'])),
                                TextInput::make('nama_rekening')
                                    ->label('Nama Rekening')
                                    ->required(fn($get) => $get('metode_pembayaran') === 'Transfer')
                                    ->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                TextInput::make('no_rekening')
                                    ->label('Nomor Rekening')
                                    ->required(fn($get) => $get('metode_pembayaran') === 'Transfer')
                                    ->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                TextInput::make('nama_bank')
                                    ->label('Nama Bank')
                                    ->required(fn($get) => $get('metode_pembayaran') === 'Transfer')
                                    ->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                FileUpload::make('bukti_path')
                                    ->label('Bukti Penawaran Vendor')
                                    ->required()
                                    ->disk('private')
                                    ->directory('bukti-survei')
                                    ->visibility('private'),
                            ])->columns(2),
                        ])->columnSpanFull(),
                ]),
        ];
    }

    /**
     * Mendapatkan logika untuk aksi survei.
     */
    protected function getSurveyActionLogic(): callable
    {
        return function (array $data, Pengajuan $record, string $successMessage) {
            $record->items()->with('surveiHargas')->get()->flatMap->surveiHargas->each->delete();
            foreach ($data['survei_per_vendor'] as $vendorSurvey) {
                if (empty($vendorSurvey['nama_vendor'])) continue;
                foreach ($vendorSurvey['item_details'] as $itemDetail) {
                    SurveiHarga::create(array_merge(
                        $itemDetail,
                        $vendorSurvey,
                        ['tipe_survei' => 'Pengadaan']
                    ));
                }
            }
            if ($record->status === Pengajuan::STATUS_SURVEI_GA) {
                $record->update([
                    'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                    'ga_surveyed_by' => Auth::id(),
                ]);
            }
            Notification::make()->title($successMessage)->success()->send();
        };
    }

    /**
     * Mendapatkan URL untuk file privat.
     */
    protected function getPrivateFileUrl(string $path): ?string
    {
        if (!Storage::disk('private')->exists($path)) {
            return null;
        }
        return route('private.file', ['path' => $path]);
    }

    /**
     * Menentukan aksi tabel.
     */
    protected function getTableActions(): array
    {

        // --- LOGIKA KALKULASI BARU & STABIL ---
        // Mendefinisikan logika kalkulasi dalam satu closure agar bisa digunakan kembali
        // dan menghindari duplikasi kode. Ini adalah pendekatan yang lebih stabil.
        $calculateRevisi = function (Forms\Get $get, Forms\Set $set) {
            $hargaLama = (float) ($get('harga_lama') ?? 0);
            $selisih = (float) ($get('selisih_harga') ?? 0);
            $kondisi = $get('kondisi_harga');
            $hargaRevisi = $hargaLama; // Nilai default

            if ($kondisi === 'Biaya Kurang') {
                $hargaRevisi = $hargaLama + $selisih;
            } elseif ($kondisi === 'Biaya Lebih') {
                $hargaRevisi = max(0, $hargaLama - $selisih);
            }

            // Mengatur nilai 'harga_revisi' secara eksplisit setiap kali ada perubahan.
            $set('harga_revisi', $hargaRevisi);
        };

        return [
            Action::make('revisiHarga')
                ->label('Revisi Harga')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->form([
                    Hidden::make('id_survei'),
                    Hidden::make('id_pengajuan'),
                    Section::make('Data Harga')->schema([
                        TextInput::make('harga_lama')
                            ->label('Harga Lama')
                            ->disabled()
                            ->numeric()
                            ->prefix('Rp'),
                        TextInput::make('pajak_lama')
                            ->label('Pajak Lama')
                            ->disabled()
                            ->numeric()
                            ->prefix('Rp')
                            ->visible(fn($get) => !empty($get('pajak_lama'))),

                        // --- PERUBAHAN PADA FIELD SUMBER ---
                        Radio::make('kondisi_harga')
                            ->label('Kondisi Harga')
                            ->options([
                                'Biaya Kurang' => 'Biaya Kurang (Penambahan Dana)',
                                'Biaya Lebih' => 'Biaya Lebih (Pengurangan Dana)',
                            ])
                            ->required()
                            ->live()
                            // Setiap kali field ini berubah, jalankan kalkulasi
                            ->afterStateUpdated($calculateRevisi),

                        TextInput::make('selisih_harga')
                            ->label('Selisih Harga')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->prefix('Rp')
                            ->live()
                            // Setiap kali field ini berubah, jalankan kalkulasi
                            ->afterStateUpdated($calculateRevisi)
                            ->helperText(fn($get) => $get('kondisi_harga') === 'Biaya Kurang' ? 'Masukkan jumlah kekurangan dana' : 'Masukkan jumlah kelebihan dana'),

                        // --- PERUBAHAN PADA FIELD TARGET ---
                        TextInput::make('harga_revisi')
                            ->label('Harga Revisi')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(true)
                        // Metode ->state(), ->reactive(), ->dehydrated(false) dihapus untuk stabilitas.
                        // Field ini sekarang menjadi pasif dan nilainya diatur oleh field lain.
                        // Secara default, field disabled akan tersimpan datanya.
                    ])->columns(2),
                    Section::make('Data Pajak')->schema([
                        Radio::make('opsi_pajak')
                            ->label('Opsi Pajak')
                            ->options([
                                'Pajak Sama' => 'Gunakan Pajak Sebelumnya',
                                'Pajak Berbeda' => 'Input Pajak Baru',
                            ])
                            ->default('Pajak Sama')
                            ->required()
                            ->live(),
                        Radio::make('kondisi_pajak')
                            ->label('Kondisi Pajak')
                            ->options([
                                'Tidak Ada Pajak' => 'Tidak Ada Pajak',
                                'Pajak ditanggung kita' => 'Pajak ditanggung Perusahaan (Exclude)',
                                'Pajak ditanggung Vendor' => 'Pajak ditanggung Vendor (Include)',
                            ])
                            ->default('Tidak Ada Pajak')
                            ->live()
                            ->required(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda')
                            ->visible(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda'),
                        Select::make('jenis_pajak')
                            ->label('Jenis Pajak')
                            ->options(['PPh 21' => 'PPh 21', 'PPh 23' => 'PPh 23'])
                            ->required(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda' && $get('kondisi_pajak') === 'Pajak ditanggung kita')
                            ->visible(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda' && $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                        TextInput::make('npwp_nik')
                            ->label('NPWP / NIK')
                            ->required(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda' && $get('kondisi_pajak') === 'Pajak ditanggung kita')
                            ->visible(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda' && $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                        TextInput::make('nama_pemilik_pajak')
                            ->label('Nama Sesuai NPWP / NIK')
                            ->required(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda' && $get('kondisi_pajak') === 'Pajak ditanggung kita')
                            ->visible(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda' && $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                        TextInput::make('nominal_pajak')
                            ->label('Nominal Pajak')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda' && $get('kondisi_pajak') === 'Pajak ditanggung kita')
                            ->visible(fn($get) => $get('opsi_pajak') === 'Pajak Berbeda' && $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                    ])->columns(2),
                    Textarea::make('alasan_revisi')
                        ->label('Alasan Revisi')
                        ->required()
                        ->columnSpanFull(),
                    FileUpload::make('bukti_revisi')
                        ->label('Bukti Revisi')
                        ->required()
                        ->directory('bukti-revisi')
                        ->disk('private')
                        ->visibility('private')
                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                        ->columnSpanFull(),
                ])
                ->action(function (array $data, Pengajuan $record): void {
                    $survei = SurveiHarga::find($data['id_survei']);
                    if (!$survei) {
                        Notification::make()
                            ->title('Error')
                            ->body('Data survei tidak ditemukan.')
                            ->danger()
                            ->send();
                        return;
                    }

                    RevisiHarga::create([
                        'survei_harga_id' => $survei->id,
                        'harga_revisi' => $data['harga_revisi'],
                        'opsi_pajak' => $data['opsi_pajak'],
                        'kondisi_pajak' => $data['opsi_pajak'] === 'Pajak Sama' ? $survei->kondisi_pajak : ($data['kondisi_pajak'] ?? null),
                        'jenis_pajak' => $data['opsi_pajak'] === 'Pajak Sama' ? $survei->jenis_pajak : ($data['jenis_pajak'] ?? null),
                        'npwp_nik' => $data['opsi_pajak'] === 'Pajak Sama' ? $survei->npwp_nik : ($data['npwp_nik'] ?? null),
                        'nama_pemilik_pajak' => $data['opsi_pajak'] === 'Pajak Sama' ? $survei->nama_pemilik_pajak : ($data['nama_pemilik_pajak'] ?? null),
                        'nominal_pajak' => $data['opsi_pajak'] === 'Pajak Sama' ? $survei->nominal_pajak : ($data['nominal_pajak'] ?? null),
                        'alasan_revisi' => $data['alasan_revisi'],
                        'bukti_revisi' => $data['bukti_revisi'],
                        'tanggal_revisi' => now(),
                        'direvisi_oleh' => Auth::id(),
                    ]);

                    // LOGIKA BARU UNTUK UPDATE STATUS
                    if ($data['kondisi_harga'] === 'Biaya Kurang') {
                        $record->update(['status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI]);
                    } elseif ($data['kondisi_harga'] === 'Biaya Lebih') {
                        $record->update(['status' => Pengajuan::STATUS_MENUNGGU_PELUNASAN]);
                    }

                    Notification::make()
                        ->title('Berhasil')
                        ->body('Harga berhasil direvisi.')
                        ->success()
                        ->send();
                })
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $survei = $record->items()
                        ->with(['surveiHargas' => function ($query) {
                            $query->where('is_final', true);
                        }])
                        ->get()
                        ->flatMap->surveiHargas
                        ->first();

                    $defaultHargaLama = $survei ? $survei->harga : 0;
                    $defaultPajakLama = $survei ? $survei->nominal_pajak : null;

                    if (!$survei) {
                        Notification::make()
                            ->title('Peringatan')
                            ->body('Tidak ada survei final yang ditemukan. Beberapa data mungkin tidak terisi.')
                            ->warning()
                            ->send();
                    }

                    $form->fill([
                        'id_survei' => $survei ? $survei->id : null,
                        'id_pengajuan' => $record->id_pengajuan,
                        'harga_lama' => $defaultHargaLama,
                        'pajak_lama' => $defaultPajakLama,
                        'harga_revisi' => $defaultHargaLama, // Mengisi nilai awal untuk harga_revisi
                    ]);
                })
                ->visible(fn(Pengajuan $record): bool => $record->canRevisePrice())
                ->modalWidth('4xl'),

            Action::make('submit_survey')
                ->label('Input Survei Harga')
                ->color('info')
                ->icon('heroicon-o-document-check')
                ->modalWidth('6xl')
                ->form(fn(Pengajuan $record) => $this->getSurveyFormSchema($record))
                ->action(fn(array $data, Pengajuan $record) => ($this->getSurveyActionLogic())($data, $record, 'Hasil survei berhasil disubmit'))
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_SURVEI_GA),

            Action::make('edit_survey')
                ->label('Edit Survei')
                ->color('warning')
                ->icon('heroicon-o-pencil')
                ->modalWidth('6xl')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record): void {
                    $allSurveys = $record->items->flatMap->surveiHargas;
                    $groupedByVendor = $allSurveys->groupBy('nama_vendor');
                    $defaultData = [];
                    foreach ($groupedByVendor as $namaVendor => $surveys) {
                        $vendorData = [
                            'nama_vendor' => $namaVendor,
                            'metode_pembayaran' => $surveys->first()->metode_pembayaran,
                            'opsi_pembayaran' => $surveys->first()->opsi_pembayaran,
                            'tanggal_dp' => $surveys->first()->tanggal_dp,
                            'tanggal_pelunasan' => $surveys->first()->tanggal_pelunasan,
                            'nama_rekening' => $surveys->first()->nama_rekening,
                            'no_rekening' => $surveys->first()->no_rekening,
                            'nama_bank' => $surveys->first()->nama_bank,
                            'bukti_path' => $surveys->first()->bukti_path,
                            'item_details' => [],
                        ];
                        foreach ($record->items as $item) {
                            $surveyForItem = $surveys->where('id_item', $item->id_item)->first();
                            $vendorData['item_details'][] = [
                                'id_item' => $item->id_item,
                                'nama_barang' => $item->nama_barang,
                                'kategori_barang' => $item->kategori_barang,
                                'harga' => $surveyForItem?->harga,
                                'rincian_harga' => $surveyForItem?->rincian_harga,
                                'kondisi_pajak' => $surveyForItem?->kondisi_pajak ?? 'Tidak Ada Pajak',
                                'jenis_pajak' => $surveyForItem?->jenis_pajak,
                                'npwp_nik' => $surveyForItem?->npwp_nik,
                                'nama_pemilik_pajak' => $surveyForItem?->nama_pemilik_pajak,
                                'nominal_pajak' => $surveyForItem?->nominal_pajak,
                            ];
                        }
                        $defaultData[] = $vendorData;
                    }
                    $form->fill(['survei_per_vendor' => $defaultData]);
                })
                ->form(fn(Pengajuan $record) => $this->getSurveyFormSchema($record))
                ->action(fn(array $data, Pengajuan $record) => ($this->getSurveyActionLogic())($data, $record, 'Hasil survei berhasil diperbarui'))
                ->visible(fn(Pengajuan $record) => in_array($record->status, [
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                ])),

            ViewAction::make()
                ->label('Detail')
                ->modalHeading(fn(Pengajuan $record): string => "Detail Pengajuan {$record->kode_pengajuan}")
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    // Eager load semua relasi yang dibutuhkan
                    $record->load([
                        'items.surveiHargas.revisiHargas.direvisiOleh',
                        'items.vendorFinal',
                        'pemohon.divisi'
                    ]);

                    $formData = $record->toArray();

                    // --- LOGIKA BARU: Flatten data revisi ---
                    $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    $revisi = $finalSurvey ? $finalSurvey->revisiHargas->first() : null;

                    // Hanya isi data revisi jika objek $revisi ada
                    if ($revisi) {
                        $formData['revisi_harga_final'] = $revisi->harga_revisi;
                        $formData['revisi_pajak_final'] = $revisi->nominal_pajak;
                        $formData['revisi_oleh_user'] = $revisi->direvisiOleh->nama_user ?? 'Tidak Diketahui';
                        $formData['revisi_alasan_final'] = $revisi->alasan_revisi;
                        $formData['revisi_tanggal_final'] = $revisi->tanggal_revisi;
                    }
                    // Jika tidak ada revisi, field-field di atas tidak akan pernah dibuat.
                    // --- AKHIR LOGIKA BARU ---

                    $formData['items_with_final_vendor'] = $record->items
                        ->filter(fn($item) => $item->vendorFinal)
                        ->map(fn($item) => [
                            'nama_barang' => $item->nama_barang,
                            'nama_vendor' => $item->vendorFinal->nama_vendor,
                            'harga' => $item->vendorFinal->harga,
                            'metode_pembayaran' => $item->vendorFinal->metode_pembayaran,
                            'opsi_pembayaran' => $item->vendorFinal->opsi_pembayaran,
                        ])
                        ->values()->toArray();

                    $form->fill($formData);
                })
                ->form([
                    Section::make('Detail Pengajuan')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('kode_pengajuan')->disabled(),
                            TextInput::make('status')->disabled(),
                            TextInput::make('total_nilai')->label('Total Nilai')->disabled(),
                        ]),
                        Repeater::make('items')->relationship()->label('')->schema([
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
                        Grid::make(2)->schema([
                            TextInput::make('rekomendasi_it_tipe')->label('Rekomendasi Tipe dari IT')->disabled(),
                            Textarea::make('rekomendasi_it_catatan')->label('Rekomendasi Catatan dari IT')->disabled(),
                        ])->visible(fn($get) => !empty($get('rekomendasi_it_tipe'))),
                        Textarea::make('catatan_revisi')->label('Catatan Approval Sebelumnya')->disabled(),
                    ])->collapsible()->collapsed(),

                    // HASIL SURVEI HARGA GA
                    Section::make('Hasil Survei Harga')->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Grid::make(1)->schema([
                                    Repeater::make('surveiHargas')
                                        ->label('Detail Harga Pembanding')
                                        ->relationship()
                                        ->schema([
                                            Grid::make(4)->schema([
                                                TextInput::make('tipe_survei')->disabled(),
                                                TextInput::make('nama_vendor')->label('Vendor/Link')->disabled(),
                                                TextInput::make('harga')->prefix('Rp')->disabled(),
                                                TextInput::make('is_final')
                                                    ->label('Vendor Final')
                                                    ->disabled()
                                                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak'),
                                            ]),
                                        ])->disabled()->columns(1),
                                ]),
                            ])->columnSpanFull()->disabled(),
                    ])->collapsed(),

                    // BUDGET REVIEW
                    Section::make('Review Budgeting')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->disabled(),
                                Textarea::make('budget_catatan_pengadaan')->label('Catatan Budget Pengadaan')->disabled(),

                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->disabled(),
                                Textarea::make('budget_catatan_perbaikan')->label('Catatan Budget Perbaikan')->disabled(),
                            ])->visible(fn($get) => !is_null($get('budget_status_perbaikan'))),
                        ])
                        ->visible(fn(Pengajuan $record) => in_array($record->status, [
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                        ]))->collapsible()->collapsed(),

                    // FINAL APPROVLE SECTION
                    Section::make('Final Approve')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('kadiv_ga_decision_type')
                                    ->label('Keputusan Kadiv GA')
                                    ->disabled()
                                    ->default(fn($get) => $get('kadiv_ga_decision_type') ?? 'Tidak Diketahui'),
                                Textarea::make('kadiv_ga_catatan')
                                    ->label('Catatan Kadiv GA')
                                    ->disabled()
                                    ->default(fn($get) => $get('kadiv_ga_catatan') ?? 'Tidak ada catatan'),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('direktur_operasional_decision_type')
                                    ->label('Keputusan Direktur Operasional')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_operasional_decision_type') ?? 'Tidak Diketahui'),
                                Textarea::make('direktur_operasional_catatan')
                                    ->label('Catatan Direktur Operasional')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_operasional_catatan') ?? 'Tidak ada catatan'),
                            ])->visible(fn($get) => !empty($get('direktur_operasional_decision_type')) || !empty($get('direktur_operasional_catatan'))),
                            Grid::make(2)->schema([
                                TextInput::make('direktur_utama_decision_type')
                                    ->label('Keputusan Direktur Utama')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_utama_decision_type') ?? 'Tidak Diketahui'),
                                Textarea::make('direktur_utama_catatan')
                                    ->label('Catatan Direktur Utama')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_utama_catatan') ?? 'Tidak ada catatan'),
                            ])->visible(fn($get) => !empty($get('direktur_utama_decision_type')) || !empty($get('direktur_utama_catatan'))),
                        ])
                        ->visible(fn(Pengajuan $record) => in_array($record->status, [
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
                        ]))
                        ->collapsible()->collapsed(),
                    // --- SECTION VENDOR FINAL ---
                    Section::make('Vendor & Harga Final yang Disetujui')
                        ->schema([
                            Repeater::make('items_with_final_vendor')
                                ->label('')
                                ->schema([
                                    Grid::make(5)->schema([
                                        TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
                                        TextInput::make('nama_vendor')->label('Nama Vendor')->disabled(),
                                        TextInput::make('harga')
                                            ->label('Harga Satuan')
                                            ->prefix('Rp')
                                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                            ->disabled(),
                                        TextInput::make('metode_pembayaran')->label('Metode Bayar')->disabled(),
                                        TextInput::make('opsi_pembayaran')->label('Opsi Bayar')->disabled(),
                                    ]),
                                ])
                                ->disabled()->disableItemCreation()->disableItemDeletion()->disableItemMovement(),
                        ])
                        ->collapsible()->collapsed()
                        ->visible(fn($get) => !empty($get('items_with_final_vendor'))),

                    // --- SECTION DETAIL REVISI DENGAN LOGIKA BARU ---
                    Section::make('Detail Revisi Harga')
                        ->schema([
                            Grid::make(3)->schema([
                                // Gunakan nama field yang sudah di-flatten
                                TextInput::make('revisi_harga_final')
                                    ->label('Harga Setelah Revisi')
                                    ->prefix('Rp')
                                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                    ->disabled(),
                                TextInput::make('revisi_pajak_final')
                                    ->label('Nominal Pajak Revisi')
                                    ->prefix('Rp')
                                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                    ->disabled()
                                    ->visible(fn($get) => !is_null($get('revisi_pajak_final'))),
                                TextInput::make('revisi_oleh_user')
                                    ->label('Direvisi Oleh')
                                    ->disabled(),
                            ]),
                            Textarea::make('revisi_alasan_final')->label('Alasan Revisi')->disabled(),
                            TextInput::make('revisi_tanggal_final')->label('Tanggal Revisi')->disabled(),
                        ])
                        ->collapsible()->collapsed()
                        ->visible(fn(Pengajuan $record) => in_array($record->status, [
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
                        ]))
                ]),
        ];
    }
}
