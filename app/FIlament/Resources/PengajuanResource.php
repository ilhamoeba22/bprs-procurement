<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanResource\Pages;
use App\Models\Pengajuan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput as FormsTextInput;
use Filament\Forms\Components\Textarea as FormsTextarea;

class PengajuanResource extends Resource
{
    protected static ?string $model = Pengajuan::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function canCreate(): bool
    {
        return Auth::user()->can('buat pengajuan');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasAnyRole(['Super Admin', 'Direksi', 'Kepala Divisi GA'])) {
            return $query;
        }

        if ($user->hasRole('Manager') || $user->hasRole('Kepala Divisi')) {
            return $query->whereHas('pemohon', function (Builder $q) use ($user) {
                $q->where('id_divisi', $user->id_divisi);
            });
        }

        return $query->where('id_user_pemohon', $user->id_user);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Detail Pengajuan')
                        ->schema([
                            Forms\Components\TextInput::make('kode_pengajuan')->default('REQ-' . Str::random(8))->disabled()->required(),
                            Forms\Components\Select::make('id_user_pemohon')->relationship('pemohon', 'nama_user')->default(fn() => Auth::id())->disabled()->required(),
                            Forms\Components\Hidden::make('status')->default(Pengajuan::STATUS_DRAFT),
                        ])->columns(2),
                    Wizard\Step::make('Daftar Barang')
                        ->schema([
                            Repeater::make('items')->relationship()->schema([
                                Select::make('kategori_barang')->options([
                                    '1a. Software' => '1a. Software',
                                    '1b. Hak Paten' => '1b. Hak Paten',
                                    '1c. Goodwill' => '1c. Goodwill',
                                    '1d. Lainnya (Aktiva Tidak Berwujud)' => '1d. Lainnya (Aktiva Tidak Berwujud)',
                                    '2a. Komputer & Hardware Sistem Informasi' => '2a. Komputer & Hardware Sistem Informasi',
                                    '2b. Peralatan atau Mesin Kantor' => '2b. Peralatan atau Mesin Kantor',
                                    '2c. Kendaraan Bermotor' => '2c. Kendaraan Bermotor',
                                    '2d. Perlengkapan Kantor Lainnya' => '2d. Perlengkapan Kantor Lainnya',
                                    '2e. Lainnya (Aktiva Berwujud)' => '2e. Lainnya (Aktiva Berwujud)',
                                ])->required(),
                                Forms\Components\TextInput::make('nama_barang')->required(),
                                Forms\Components\TextInput::make('kuantitas')->numeric()->required()->minValue(1),
                                Forms\Components\Textarea::make('spesifikasi')->required()->columnSpanFull(),
                                Forms\Components\Textarea::make('justifikasi')->required()->columnSpanFull(),
                            ])->columns(2)->columnSpanFull()->addActionLabel('Tambah Barang'),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_pengajuan')->searchable(),
                Tables\Columns\TextColumn::make('pemohon.nama_user')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->searchable()
                    ->color(fn(string $state): string => match ($state) {
                        Pengajuan::STATUS_DRAFT => 'gray',
                        Pengajuan::STATUS_DITOLAK => 'danger',
                        'Menunggu Approval Kadiv GA' => 'info',
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER, Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV, Pengajuan::STATUS_REKOMENDASI_IT, Pengajuan::STATUS_SURVEI_GA, Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET, Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKSI => 'warning',
                        Pengajuan::STATUS_DISETUJUI => 'success',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('total_nilai')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_DRAFT),

                Action::make('approve_manager')
                    ->label('Setujui (Manager)')->color('success')->icon('heroicon-o-check-circle')
                    ->action(fn(Pengajuan $record) => $record->update(['status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV]))
                    ->visible(fn(Pengajuan $record): bool => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER && Auth::user()->can('approval manager')),

                // === LOGIKA BARU DI SINI ===
                Action::make('approve_kadiv')
                    ->label('Setujui (Kadiv)')->color('success')->icon('heroicon-o-check-circle')
                    ->action(function (Pengajuan $record) {
                        // Cek apakah ada barang kategori IT di dalam pengajuan ini
                        $needsITRecommendation = $record->items()
                            ->whereIn('kategori_barang', ['1a. Software', '2a. Komputer & Hardware Sistem Informasi'])
                            ->exists();

                        if ($needsITRecommendation) {
                            $record->update(['status' => Pengajuan::STATUS_REKOMENDASI_IT]);
                        } else {
                            $record->update(['status' => Pengajuan::STATUS_SURVEI_GA]);
                        }
                    })
                    ->visible(fn(Pengajuan $record): bool => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV && Auth::user()->can('approval kadiv')),

                // === Tombol Baru untuk Rekomendasi IT ===
                Action::make('submit_it_recommendation')
                    ->label('Submit Rekomendasi IT')->color('info')->icon('heroicon-o-wrench-screwdriver')
                    ->form([
                        FormsTextarea::make('catatan_rekomendasi_it')->label('Catatan Rekomendasi')->required(),
                    ])
                    ->action(function (array $data, Pengajuan $record) {
                        // Simpan catatan revisi dari IT
                        $record->catatan_revisi = ($record->catatan_revisi ? $record->catatan_revisi . "\n\n" : '') . "Rekomendasi IT: " . $data['catatan_rekomendasi_it'];
                        // Lanjutkan alur ke GA
                        $record->status = Pengajuan::STATUS_SURVEI_GA;
                        $record->save();
                    })
                    ->visible(fn(Pengajuan $record): bool => $record->status === Pengajuan::STATUS_REKOMENDASI_IT && Auth::user()->can('rekomendasi it')),

                Action::make('submit_survey')
                    ->label('Input Hasil Survei')->color('info')->icon('heroicon-o-currency-dollar')
                    ->form([
                        FormsTextInput::make('total_nilai')->label('Total Nilai Pengajuan')->numeric()->required()->prefix('Rp'),
                        FormsTextarea::make('catatan_survey')->label('Catatan Survei Harga (jika ada)'),
                    ])
                    ->action(function (array $data, Pengajuan $record) {
                        $newStatus = $data['total_nilai'] <= 1000000 ? 'Menunggu Approval Kadiv GA' : Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKSI;
                        $record->update([
                            'total_nilai' => $data['total_nilai'],
                            'status' => $newStatus,
                            'catatan_revisi' => ($record->catatan_revisi ? $record->catatan_revisi . "\n\n" : '') . "Catatan GA: " . $data['catatan_survey'],
                        ]);
                    })
                    ->visible(fn(Pengajuan $record): bool => $record->status === Pengajuan::STATUS_SURVEI_GA && Auth::user()->can('survei harga ga')),

                Action::make('approve_kadiv_ga')
                    ->label('Approval Final (Kadiv GA)')->color('primary')->icon('heroicon-o-check-badge')->requiresConfirmation()
                    ->action(fn(Pengajuan $record) => $record->update(['status' => Pengajuan::STATUS_DISETUJUI]))
                    ->visible(fn(Pengajuan $record): bool => $record->status === 'Menunggu Approval Kadiv GA' && Auth::user()->can('approval kadiv ga <= 1jt')),

                Action::make('approve_direksi')
                    ->label('Approval Final (Direksi)')->color('primary')->icon('heroicon-o-check-badge')->requiresConfirmation()
                    ->action(fn(Pengajuan $record) => $record->update(['status' => Pengajuan::STATUS_DISETUJUI]))
                    ->visible(fn(Pengajuan $record): bool => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKSI && Auth::user()->can('approval direksi')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuans::route('/'),
            'create' => Pages\CreatePengajuan::route('/create'),
            'edit' => Pages\EditPengajuan::route('/{record}/edit'),
        ];
    }
}
