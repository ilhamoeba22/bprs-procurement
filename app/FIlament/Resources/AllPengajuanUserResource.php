<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AllPengajuanUserResource\Pages;
use App\Models\Pengajuan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Components\StandardDetailSections;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Auth;

class AllPengajuanUserResource extends Resource
{
    protected static ?string $model = Pengajuan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'All Pengajuan User';
    protected static ?string $navigationGroup = 'Admin Area';
    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return Auth::user()->hasRole('Super Admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('kode_pengajuan')
                            ->label('Kode Pengajuan')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('id_user_pemohon')
                            ->label('Pemohon')
                            ->relationship('pemohon', 'nama_user')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                Pengajuan::STATUS_DRAFT => Pengajuan::STATUS_DRAFT,
                                Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER => Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER,
                                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV,
                                Pengajuan::STATUS_REKOMENDASI_IT => Pengajuan::STATUS_REKOMENDASI_IT,
                                Pengajuan::STATUS_SURVEI_GA => Pengajuan::STATUS_SURVEI_GA,
                                Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET => Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL => Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA => Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                                Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                                Pengajuan::STATUS_MENUNGGU_PELUNASAN => Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                                Pengajuan::STATUS_SUDAH_BAYAR => Pengajuan::STATUS_SUDAH_BAYAR,
                                Pengajuan::STATUS_SELESAI => Pengajuan::STATUS_SELESAI,
                                Pengajuan::STATUS_DITOLAK_MANAGER => Pengajuan::STATUS_DITOLAK_MANAGER,
                                Pengajuan::STATUS_DITOLAK_KADIV => Pengajuan::STATUS_DITOLAK_KADIV,
                                Pengajuan::STATUS_DITOLAK_KADIV_GA => Pengajuan::STATUS_DITOLAK_KADIV_GA,
                                Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL => Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                                Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA => Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                                Pengajuan::STATUS_DITOLAK_KADIV_OPS => Pengajuan::STATUS_DITOLAK_KADIV_OPS,
                            ])
                            ->searchable()
                            ->required(),
                         Forms\Components\TextInput::make('total_nilai')
                            ->label('Total Nilai')
                            ->numeric()
                            ->prefix('Rp'),
                    ])->columns(2),

                Forms\Components\Tabs::make('Detail Transaksi')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Items & Spesifikasi')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('nama_barang')->required(),
                                        Forms\Components\Textarea::make('spesifikasi')->required(),
                                        Forms\Components\TextInput::make('kuantitas')->numeric()->required(),
                                        Forms\Components\Select::make('kategori_barang')
                                            ->options([
                                                'Barang' => 'Barang',
                                                'Jasa' => 'Jasa',
                                            ])
                                            ->required(),
                                        Forms\Components\Textarea::make('justifikasi'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                            ]),
                        Forms\Components\Tabs\Tab::make('Keputusan & Catatan')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('rekomendasi_it_tipe')->label('Tipe Rekomendasi IT'),
                                        Forms\Components\Textarea::make('rekomendasi_it_catatan')->label('Catatan Rekomendasi IT'),
                                        Forms\Components\TextInput::make('status_budget')->label('Status Budget'),
                                        Forms\Components\Textarea::make('catatan_budget')->label('Catatan Budget'),
                                        Forms\Components\TextInput::make('kadiv_ga_decision_type')->label('Keputusan Kadiv GA'),
                                        Forms\Components\Textarea::make('kadiv_ga_catatan')->label('Catatan Kadiv GA'),
                                        Forms\Components\TextInput::make('direktur_utama_decision_type')->label('Keputusan DirUt'),
                                        Forms\Components\Textarea::make('direktur_utama_catatan')->label('Catatan DirUt'),
                                        Forms\Components\TextInput::make('direktur_operasional_decision_type')->label('Keputusan DirOps'),
                                        Forms\Components\Textarea::make('direktur_operasional_catatan')->label('Catatan DirOps'),
                                        Forms\Components\TextInput::make('kadiv_ops_decision_type')->label('Keputusan Kadiv Ops'),
                                        Forms\Components\Textarea::make('kadiv_ops_catatan')->label('Catatan Kadiv Ops'),
                                        Forms\Components\Textarea::make('catatan_revisi')->label('Catatan Revisi General'),
                                    ])
                            ]),
                        Forms\Components\Tabs\Tab::make('Vendor & Pembayaran')
                            ->schema([
                                Forms\Components\Repeater::make('vendorPembayaran')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('nama_vendor')->required(),
                                        Forms\Components\Select::make('metode_pembayaran')
                                            ->options(['Transfer' => 'Transfer', 'Tunai' => 'Tunai']),
                                        Forms\Components\Select::make('opsi_pembayaran')
                                            ->options(['Bisa DP' => 'Bisa DP', 'Langsung Lunas' => 'Langsung Lunas']),
                                        Forms\Components\TextInput::make('nominal_dp')->numeric()->prefix('Rp'),
                                        Forms\Components\DatePicker::make('tanggal_dp'),
                                        Forms\Components\DatePicker::make('tanggal_pelunasan'),
                                        Forms\Components\TextInput::make('nama_rekening'),
                                        Forms\Components\TextInput::make('no_rekening'),
                                        Forms\Components\TextInput::make('nama_bank'),
                                        Forms\Components\Toggle::make('is_final')->label('Vendor Terpilih'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_pengajuan')->label('Kode')->searchable()->sortable(),
                TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable()->sortable(),
                TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi')->sortable(),
                TextColumn::make('created_at')->label('Tanggal')->date()->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->color(fn ($state) => Pengajuan::getStatusBadgeColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                         Pengajuan::STATUS_SELESAI => 'Selesai',
                         Pengajuan::STATUS_SURVEI_GA => 'Survei GA',
                         // Add condensed options
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Can add relation managers here if we want to edit specific related data like surveys
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllPengajuanUsers::route('/'),
            'create' => Pages\CreateAllPengajuanUser::route('/create'),
            'edit' => Pages\EditAllPengajuanUser::route('/{record}/edit'),
        ];
    }
}
