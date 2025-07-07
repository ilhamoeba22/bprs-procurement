<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\SurveiHarga;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
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
use Filament\Forms\Components\Placeholder;
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
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items', 'items.surveiHargas', 'items.vendorFinal']);

        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->whereIn('status', [
                    Pengajuan::STATUS_SURVEI_GA,
                    Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                    Pengajuan::STATUS_SUDAH_BAYAR,
                    Pengajuan::STATUS_SELESAI,
                ])->orWhere('ga_surveyed_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', [
                Pengajuan::STATUS_SURVEI_GA,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                Pengajuan::STATUS_SUDAH_BAYAR,
                Pengajuan::STATUS_SELESAI,
            ])->orWhereNotNull('ga_surveyed_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            BadgeColumn::make('status')->label('Status Saat Ini'),
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

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading('')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $record->load(['items', 'items.surveiHargas', 'items.vendorFinal']);
                    $record->estimasi_pengadaan = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $record->estimasi_perbaikan = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $form->fill($record->toArray());
                })->form([
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
                        ])->columns(1)->disabled()->addActionLabel('Tambah Barang'),
                        Grid::make(2)->schema([
                            TextInput::make('rekomendasi_it_tipe')->label('Rekomendasi Tipe dari IT')->disabled(),
                            Textarea::make('rekomendasi_it_catatan')->label('Rekomendasi Catatan dari IT')->disabled(),
                        ])->visible(fn($record) => !empty($record?->rekomendasi_it_tipe)),
                        Textarea::make('catatan_revisi')->label('Catatan Approval Sebelumnya')->disabled(),
                    ]),
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
                    ]),
                    Section::make('Vendor Harga Final yang Di-approve')
                        ->schema(function (Pengajuan $record) {
                            Log::debug('Processing Vendor Harga Final for pengajuan ID: ' . $record->id_pengajuan);

                            $firstItem = $record->items->first();
                            if (!$firstItem) {
                                return [
                                    Placeholder::make('no_item_placeholder')
                                        ->content('Tidak ada item terkait untuk pengajuan ini.')
                                        ->columnSpanFull(),
                                ];
                            }

                            $surveiHarga = SurveiHarga::where('id_item', $firstItem->id_item)
                                ->where('is_final', 1)
                                ->first();

                            if (!$surveiHarga) {
                                return [
                                    Placeholder::make('no_final_vendor_placeholder')
                                        ->content('Tidak ada data vendor final untuk id_item = ' . $firstItem->id_item . '.')
                                        ->columnSpanFull(),
                                ];
                            }

                            $data = [
                                'nama_barang' => $surveiHarga->item->nama_barang ?? 'N/A',
                                'nama_vendor' => $surveiHarga->nama_vendor ?? 'N/A',
                                'harga' => 'Rp ' . number_format($surveiHarga->harga ?? 0, 0, ',', '.'),
                                'metode_pembayaran' => $surveiHarga->metode_pembayaran ?? 'N/A',
                            ];

                            $content = '<table style="width: 100%; border-collapse: collapse; margin: 10px 0; color: #333; background-color: #fff;">'
                                . '<thead>'
                                . '<tr style="background-color: #e0e0e0;">'
                                . '<th style="border: 1px solid #ccc; padding: 8px; text-align: left;">Label</th>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; text-align: left;">Detail</th>'
                                . '</tr>'
                                . '</thead>'
                                . '<tbody>'
                                . '<tr>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">Nama Barang</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['nama_barang']) . '</td>'
                                . '</tr>'
                                . '<tr>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">Nama Vendor</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['nama_vendor']) . '</td>'
                                . '</tr>'
                                . '<tr>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">Harga</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['harga']) . ' <span style="color: #888; font-size: 12px;">(harga per item)</span></td>'
                                . '</tr>'
                                . '<tr>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">Metode Pembayaran</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['metode_pembayaran']) . '</td>'
                                . '</tr>'
                                . '</tbody>'
                                . '</table>';

                            return [
                                Placeholder::make('final_vendor_data')
                                    ->content(new HtmlString($content))
                                    ->columnSpanFull(),
                            ];
                        })
                        ->visible(fn(Pengajuan $record) => in_array($record->status, [
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                        ]))
                        ->columnSpanFull(),
                ]),
            Action::make('submit_survey')
                ->label('Input Survei Harga')
                ->color('info')
                ->icon('heroicon-o-document-check')
                ->modalHeading('')
                ->form(function (Pengajuan $record) {
                    return [
                        Section::make('Input Survei Harga')->schema(
                            array_map(function ($item) {
                                return Grid::make(1)->schema([
                                    Grid::make(6)->schema([
                                        TextInput::make("kategori_barang_{$item->id_item}")->label('Kategori Barang')->default($item->kategori_barang)->disabled()->columnSpan(2),
                                        TextInput::make("nama_barang_{$item->id_item}")->label('Nama Barang')->default($item->nama_barang)->disabled()->columnSpan(3),
                                        TextInput::make("kuantitas_{$item->id_item}")->label('Kuantitas')->default($item->kuantitas)->disabled()->columnSpan(1),
                                    ]),
                                    Grid::make(2)->schema([
                                        Textarea::make("spesifikasi_{$item->id_item}")->label('Spesifikasi')->default($item->spesifikasi)->disabled(),
                                        Textarea::make("justifikasi_{$item->id_item}")->label('Justifikasi')->default($item->justifikasi)->disabled(),
                                    ]),
                                    Grid::make(2)->schema([
                                        Repeater::make("survei_pengadaan_{$item->id_item}")
                                            ->label('Input Harga Pembanding (Pengadaan)')
                                            ->schema([
                                                TextInput::make('nama_vendor')->label('Nama Vendor / Link')->required(),
                                                TextInput::make('harga')->numeric()->required()->prefix('Rp')->live(onBlur: true),
                                                Radio::make('metode_pembayaran')->label('Metode Bayar')->options(['Transfer' => 'Transfer', 'Tunai' => 'Tunai'])->required()->live(),
                                                TextInput::make('nama_rekening')->label('Nama Rekening')->required(fn($get) => $get('metode_pembayaran') === 'Transfer')->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                                TextInput::make('no_rekening')->label('Nomor Rekening')->required(fn($get) => $get('metode_pembayaran') === 'Transfer')->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                                TextInput::make('nama_bank')->label('Nama Bank')->required(fn($get) => $get('metode_pembayaran') === 'Transfer')->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                                Radio::make('opsi_pembayaran')->label('Opsi Bayar')->options(['Bisa DP' => 'Bisa DP', 'Langsung Lunas' => 'Langsung Lunas'])->required()->live(),
                                                TextInput::make('nominal_dp')->label('Nominal DP')->numeric()->prefix('Rp')
                                                    ->required()
                                                    ->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP')
                                                    ->live(onBlur: true)
                                                    ->rules([
                                                        // FIX: Aturan 'lte:harga' dihapus dari sini
                                                        function ($get) {
                                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                                $harga = (float) $get('harga');
                                                                if ($value > $harga) {
                                                                    $fail('Nominal DP tidak boleh lebih besar dari Harga.');
                                                                }
                                                            };
                                                        },
                                                    ]),
                                                DatePicker::make('tanggal_dp')->label('Tgl. DP')->required()->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                                DatePicker::make('tanggal_pelunasan')->label('Tgl. Lunas')->required()->visible(fn($get) => in_array($get('opsi_pembayaran'), ['Bisa DP', 'Langsung Lunas'])),
                                                FileUpload::make('bukti_path')
                                                    ->label('Bukti Survei')
                                                    ->required()
                                                    ->directory('bukti-survei')
                                                    ->visibility('private')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->minItems(3)
                                            ->maxItems(3)
                                            ->addActionLabel('Tambah Pembanding'),
                                        Repeater::make("survei_perbaikan_{$item->id_item}")
                                            ->label('Input Harga Pembanding (Perbaikan)')
                                            ->schema([
                                                TextInput::make('nama_vendor')->label('Nama Vendor / Link')->required(),
                                                TextInput::make('harga')->numeric()->required()->prefix('Rp')->live(onBlur: true),
                                                Radio::make('metode_pembayaran')->label('Metode Bayar')->options(['Transfer' => 'Transfer', 'Tunai' => 'Tunai'])->required()->live(),
                                                TextInput::make('nama_rekening')->label('Nama Rekening')->required(fn($get) => $get('metode_pembayaran') === 'Transfer')->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                                TextInput::make('no_rekening')->label('Nomor Rekening')->required(fn($get) => $get('metode_pembayaran') === 'Transfer')->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                                TextInput::make('nama_bank')->label('Nama Bank')->required(fn($get) => $get('metode_pembayaran') === 'Transfer')->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                                Radio::make('opsi_pembayaran')->label('Opsi Bayar')->options(['Bisa DP' => 'Bisa DP', 'Langsung Lunas' => 'Langsung Lunas'])->required()->live(),
                                                TextInput::make('nominal_dp')->label('Nominal DP')->numeric()->prefix('Rp')
                                                    ->required()
                                                    ->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP')
                                                    ->live(onBlur: true)
                                                    ->rules([
                                                        // FIX: Pastikan aturan di sini juga sudah benar (tanpa 'lte:harga')
                                                        function ($get) {
                                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                                $harga = (float) $get('harga');
                                                                if ($value > $harga) {
                                                                    $fail('Nominal DP tidak boleh lebih besar dari Harga.');
                                                                }
                                                            };
                                                        },
                                                    ]),
                                                DatePicker::make('tanggal_dp')->label('Tgl. DP')->required()->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                                DatePicker::make('tanggal_pelunasan')->label('Tgl. Lunas')->required()->visible(fn($get) => in_array($get('opsi_pembayaran'), ['Bisa DP', 'Langsung Lunas'])),
                                                FileUpload::make('bukti_path')
                                                    ->label('Bukti Survei')
                                                    ->required()
                                                    ->directory('bukti-survei')
                                                    ->visibility('private')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->minItems(1)
                                            ->maxItems(1)
                                            ->addActionLabel('Tambah Pembanding'),
                                    ])->columnSpanFull(),
                                ]);
                            }, $record->items->all())
                        ),
                    ];
                })
                ->action(function (array $data, Pengajuan $record) {
                    Log::info('Submitting survey for pengajuan ID: ' . $record->id_pengajuan, [
                        'user_id' => Auth::id(),
                        'items_count' => count($record->items),
                    ]);

                    foreach ($record->items as $item) {
                        $pengadaanKey = "survei_pengadaan_{$item->id_item}";
                        if (isset($data[$pengadaanKey]) && is_array($data[$pengadaanKey])) {
                            $item->surveiHargas()->where('tipe_survei', 'Pengadaan')->delete();
                            foreach ($data[$pengadaanKey] as $surveyData) {
                                $surveyData['tipe_survei'] = 'Pengadaan';
                                $item->surveiHargas()->create($surveyData);
                            }
                        }

                        $perbaikanKey = "survei_perbaikan_{$item->id_item}";
                        if (isset($data[$perbaikanKey]) && is_array($data[$perbaikanKey])) {
                            $item->surveiHargas()->where('tipe_survei', 'Perbaikan')->delete();
                            foreach ($data[$perbaikanKey] as $surveyData) {
                                $surveyData['tipe_survei'] = 'Perbaikan';
                                $item->surveiHargas()->create($surveyData);
                            }
                        }
                    }

                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                        'ga_surveyed_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Hasil survei berhasil disubmit')->success()->send();
                })
                ->modalWidth('6xl')
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_SURVEI_GA),
            Action::make('complete')
                ->label('Selesai')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Tandai Pengajuan Selesai')
                ->modalDescription('Unggah bukti penyelesaian (misalnya, bukti serah terima) untuk menandai pengajuan ini sebagai selesai.')
                ->form(function (Pengajuan $record) {
                    $items = $record->items->map(function ($item) {
                        $surveiHarga = $item->vendorFinal;
                        return [
                            'id_item' => $item->id_item,
                            'nama_barang' => $item->nama_barang,
                            'survei_harga_id' => $surveiHarga ? $surveiHarga->id : null,
                        ];
                    })->filter(fn($item) => !empty($item['survei_harga_id']))->values();

                    if ($items->isEmpty()) {
                        return [
                            Textarea::make('error_message')
                                ->label('Kesalahan')
                                ->default('Data vendor harga final tidak ditemukan.')
                                ->disabled()
                                ->columnSpanFull(),
                        ];
                    }

                    return [
                        Repeater::make('items')
                            ->label('Unggah Bukti Penyelesaian per Item')
                            ->defaultItems($items->count())
                            ->schema([
                                Grid::make(1)->schema([
                                    TextInput::make('nama_barang')
                                        ->label('Nama Barang')
                                        ->disabled(),
                                    FileUpload::make('bukti_penyelesaian')
                                        ->label('Bukti Penyelesaian')
                                        ->required()
                                        ->directory('bukti-penyelesaian')
                                        ->visibility('private')
                                        ->disk('private')
                                        ->columnSpanFull(),
                                ]),
                            ])
                            ->default($items->toArray())
                            ->disabled(fn() => $record->status === Pengajuan::STATUS_SELESAI)
                            ->columnSpanFull(),
                    ];
                })
                ->action(function (array $data, Pengajuan $record) {
                    foreach ($record->items as $item) {
                        if ($item->vendorFinal && isset($data['items'])) {
                            foreach ($data['items'] as $itemData) {
                                if ($itemData['nama_barang'] === $item->nama_barang && isset($itemData['bukti_penyelesaian'])) {
                                    $item->vendorFinal->update(['bukti_penyelesaian' => $itemData['bukti_penyelesaian']]);
                                }
                            }
                        }
                    }
                    $record->update(['status' => Pengajuan::STATUS_SELESAI]);
                    Notification::make()->title('Pengajuan berhasil ditandai sebagai selesai')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_SUDAH_BAYAR),


            Action::make('edit_survey')
                ->label('Edit')
                ->color('warning')
                ->icon('heroicon-o-pencil')
                ->modalHeading('Edit Detail Vendor Final')
                ->modalDescription('Hanya Metode Pembayaran dan Tanggal yang bisa diedit.')
                ->form(function (Pengajuan $record) {
                    $items = $record->items->map(function ($item) {
                        $surveiHarga = $item->vendorFinal;
                        if (!$surveiHarga) {
                            return null;
                        }

                        // FIX 1: "Titipkan" ID unik dari survei harga ke dalam data form
                        return [
                            'id' => $surveiHarga->id, // INI KUNCINYA
                            'nama_barang' => $item->nama_barang,
                            'nama_vendor' => $surveiHarga->nama_vendor,
                            'harga' => number_format($surveiHarga->harga, 0, ',', '.'),
                            'opsi_pembayaran' => $surveiHarga->opsi_pembayaran,
                            'metode_pembayaran' => $surveiHarga->metode_pembayaran,
                            'nama_rekening' => $surveiHarga->nama_rekening,
                            'no_rekening' => $surveiHarga->no_rekening,
                            'nama_bank' => $surveiHarga->nama_bank,
                            'tanggal_dp' => $surveiHarga->tanggal_dp,
                            'tanggal_pelunasan' => $surveiHarga->tanggal_pelunasan,
                        ];
                    })->filter()->values();

                    if ($items->isEmpty()) {
                        return [
                            Placeholder::make('error_message')
                                ->content('Data vendor final tidak ditemukan untuk diedit.')
                        ];
                    }

                    return [
                        Repeater::make('items')
                            ->label('Detail per Item')
                            ->default($items->toArray())
                            ->disableItemCreation()->disableItemDeletion()->disableItemMovement()
                            ->schema([
                                // Field yang tidak bisa diedit (disabled)
                                Grid::make(3)->schema([
                                    TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
                                    TextInput::make('nama_vendor')->label('Nama Vendor')->disabled(),
                                    TextInput::make('harga')->label('Harga')->prefix('Rp')->disabled(),
                                ]),
                                // Field yang BISA diedit
                                Section::make('Detail Pembayaran (Editable)')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Radio::make('metode_pembayaran')
                                                ->label('Metode Bayar')
                                                ->options(['Transfer' => 'Transfer', 'Tunai' => 'Tunai'])
                                                ->required()->live(),
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
                                            DatePicker::make('tanggal_dp')
                                                ->label('Tanggal DP')
                                                ->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                            DatePicker::make('tanggal_pelunasan')
                                                ->label('Tanggal Pelunasan')
                                                ->visible(fn($get) => in_array($get('opsi_pembayaran'), ['Bisa DP', 'Langsung Lunas'])),
                                        ]),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ];
                })
                ->action(function (array $data, Pengajuan $record) {
                    // FIX 2: Logika penyimpanan dirombak total agar akurat
                    if (!isset($data['items']) || !is_array($data['items'])) {
                        Notification::make()->title('Gagal: Data formulir tidak valid.')->danger()->send();
                        return;
                    }

                    foreach ($data['items'] as $itemData) {
                        // Cek apakah ID ada di data yang di-submit
                        if (!isset($itemData['id'])) {
                            continue; // Lewati jika tidak ada ID
                        }

                        // Cari SurveiHarga berdasarkan ID unik yang sudah kita "titipkan"
                        $surveiHarga = SurveiHarga::find($itemData['id']);

                        if ($surveiHarga) {
                            // Siapkan data yang akan diupdate
                            $updateData = [
                                'metode_pembayaran' => $itemData['metode_pembayaran'],
                                'tanggal_dp' => $itemData['tanggal_dp'],
                                'tanggal_pelunasan' => $itemData['tanggal_pelunasan'],
                            ];

                            // Hanya update rekening jika metodenya transfer
                            if ($itemData['metode_pembayaran'] === 'Transfer') {
                                $updateData['nama_rekening'] = $itemData['nama_rekening'];
                                $updateData['no_rekening'] = $itemData['no_rekening'];
                                $updateData['nama_bank'] = $itemData['nama_bank'];
                            } else {
                                // Kosongkan data rekening jika metode diubah ke Tunai
                                $updateData['nama_rekening'] = null;
                                $updateData['no_rekening'] = null;
                                $updateData['nama_bank'] = null;
                            }

                            // Lakukan update ke database
                            $surveiHarga->update($updateData);
                        }
                    }

                    Notification::make()->title('Data vendor final berhasil diperbarui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => ($record->items->contains(fn($item) => $item->vendorFinal)) &&
                    $record->status === Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA),
        ];
    }
}
