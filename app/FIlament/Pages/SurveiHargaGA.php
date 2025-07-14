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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Storage;
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
    protected function getTableActions(): array
    {

        // FORM SCHEMA (untuk Submit dan Edit)
        $surveyFormSchema = function (Pengajuan $record) {
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
                                        Placeholder::make('nama_barang_info')->label(false)->content(fn($get) => new HtmlString('<b class="text-base">' . $get('nama_barang') . '</b>' . ' <span class="text-sm text-gray-500">(' . $get('kategori_barang') . ')</span>')),
                                        Hidden::make('id_item'),
                                        Hidden::make('kategori_barang'),
                                        TextInput::make('harga')->label('Harga')->numeric()->required()->prefix('Rp'),
                                        Textarea::make('rincian_harga')->label('Rincian Harga (Opsional)'),
                                        Section::make('Detail Pajak')->collapsible()->collapsed()->schema([
                                            Radio::make('kondisi_pajak')->label('Kondisi Pajak')->options(['Tidak Ada Pajak' => 'Tidak Ada Pajak', 'Pajak ditanggung kita' => 'Pajak ditanggung Perusahaan (Exclude)', 'Pajak ditanggung Vendor' => 'Pajak ditanggung Vendor (Include)',])->default('Tidak Ada Pajak')->live(),
                                            Select::make('jenis_pajak')->label('Jenis Pajak')->options(['PPh 21' => 'PPh 21', 'PPh 23' => 'PPh 23'])->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                            TextInput::make('npwp_nik')->label('NPWP / NIK')->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                            TextInput::make('nama_pemilik_pajak')->label('Nama Sesuai NPWP / NIK')->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                            TextInput::make('nominal_pajak')->label('Nominal Pajak')->numeric()->prefix('Rp')->required(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita')->visible(fn($get) => $get('kondisi_pajak') === 'Pajak ditanggung kita'),
                                        ])->columns(2),
                                    ])->columnSpanFull(),
                                Section::make('Opsi Pembayaran & Bukti (untuk vendor ini)')->schema([
                                    Radio::make('metode_pembayaran')->label('Metode Bayar')->options(['Transfer' => 'Transfer', 'Tunai' => 'Tunai'])->required()->live(),
                                    Radio::make('opsi_pembayaran')->label('Opsi Bayar')->options(['Bisa DP' => 'Bisa DP', 'Langsung Lunas' => 'Langsung Lunas'])->required()->live(),
                                    DatePicker::make('tanggal_dp')->label('Tgl. DP')->required()->visible(fn($get) => $get('opsi_pembayaran') === 'Bisa DP'),
                                    DatePicker::make('tanggal_pelunasan')->label('Tgl. Lunas')->required()->visible(fn($get) => in_array($get('opsi_pembayaran'), ['Bisa DP', 'Langsung Lunas'])),
                                    TextInput::make('nama_rekening')->label('Nama Rekening')->required(fn($get) => $get('metode_pembayaran') === 'Transfer')->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                    TextInput::make('no_rekening')->label('Nomor Rekening')->required(fn($get) => $get('metode_pembayaran') === 'Transfer')->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                    TextInput::make('nama_bank')->label('Nama Bank')->required(fn($get) => $get('metode_pembayaran') === 'Transfer')->visible(fn($get) => $get('metode_pembayaran') === 'Transfer'),
                                    FileUpload::make('bukti_path')->label('Bukti Penawaran Vendor')->required()->disk('private')->directory('bukti-survei')->visibility('private'),
                                ])->columns(2),
                            ])->columnSpanFull(),
                    ]),
            ];
        };

        // ACTION LOGIC (untuk Submit dan Edit)
        $surveyActionLogic = function (array $data, Pengajuan $record, string $successMessage) {
            $record->items()->with('surveiHargas')->get()->flatMap->surveiHargas->each->delete();
            foreach ($data['survei_per_vendor'] as $vendorSurvey) {
                if (empty($vendorSurvey['nama_vendor'])) continue;
                $namaVendor = $vendorSurvey['nama_vendor'];
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


        $getPrivateFileUrl = function (string $path): ?string {
            if (!Storage::disk('private')->exists($path)) {
                return null;
            }
            return route('private.file', ['path' => $path]);
        };

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
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                        ]))
                        ->collapsible()
                        ->columnSpanFull(),
                ]),
            Action::make('submit_survey')
                ->label('Input Survei Harga')
                ->color('info')->icon('heroicon-o-document-check')->modalWidth('6xl')
                ->form($surveyFormSchema)
                ->action(fn(array $data, Pengajuan $record) => $surveyActionLogic($data, $record, 'Hasil survei berhasil disubmit'))
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_SURVEI_GA),

            Action::make('edit_survey')
                ->label('Edit Survei')
                ->color('warning')->icon('heroicon-o-pencil')
                ->modalWidth('6xl')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record): void {
                    // Siapkan data yang ada untuk mengisi form
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
                            'item_details' => []
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
                ->form($surveyFormSchema)
                ->action(fn(array $data, Pengajuan $record) => $surveyActionLogic($data, $record, 'Hasil survei berhasil diperbarui'))
                ->visible(fn(Pengajuan $record) => in_array($record->status, [
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                ])),
        ];
    }
}
