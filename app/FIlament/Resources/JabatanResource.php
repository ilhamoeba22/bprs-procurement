<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JabatanResource\Pages;
use App\Models\Jabatan;
use App\Models\Divisi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder; // Tambahkan ini
use Illuminate\Support\Collection;

class JabatanResource extends Resource
{
    protected static ?string $model = Jabatan::class;

    // Pengaturan Navigasi
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Dropdown untuk memilih Kantor
                Select::make('id_kantor')
                    ->relationship('kantor', 'nama_kantor')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live() // Membuat field ini reaktif
                    ->afterStateUpdated(fn(Set $set) => $set('id_divisi', null)), // Reset divisi jika kantor berubah

                // Dropdown untuk memilih Divisi (bergantung pada Kantor)
                Select::make('id_divisi')
                    ->label('Divisi')
                    ->options(function (Get $get): Collection {
                        $kantorId = $get('id_kantor');
                        if (!$kantorId) {
                            return collect();
                        }
                        return Divisi::where('id_kantor', $kantorId)->pluck('nama_divisi', 'id_divisi');
                    })
                    ->searchable()
                    ->required()
                    ->live(),

                Forms\Components\TextInput::make('nama_jabatan')->required()->maxLength(255),
                Forms\Components\TextInput::make('type_jabatan')->maxLength(255)->helperText('Contoh: Staff, Manager, Direksi'),

                // ===== BAGIAN YANG DIPERBAIKI =====
                // Dropdown untuk memilih Atasan (ACC Jabatan)
                Select::make('acc_jabatan_id')
                    ->label('Atasan Langsung (ACC)')
                    ->helperText('Pilih jabatan atasan untuk approval. Kosongkan jika tidak ada.')
                    ->relationship(
                        name: 'atasan',
                        titleAttribute: 'nama_jabatan',
                        // Logika tambahan: jangan tampilkan jabatan yang sedang diedit sebagai pilihan atasan
                        modifyQueryUsing: fn(Builder $query, ?Jabatan $record) => $record ? $query->where('id_jabatan', '!=', $record->id_jabatan) : null
                    )
                    ->searchable()
                    ->preload() // Muat opsi di awal agar lebih cepat
                    ->nullable(), // Boleh kosong
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_jabatan')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('kantor.nama_kantor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('divisi.nama_divisi')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('atasan.nama_jabatan')->label('Atasan Langsung')->default('N/A'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kantor')->relationship('kantor', 'nama_kantor'),
                Tables\Filters\SelectFilter::make('divisi')->relationship('divisi', 'nama_divisi'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJabatans::route('/'),
            'create' => Pages\CreateJabatan::route('/create'),
            'edit' => Pages\EditJabatan::route('/{record}/edit'),
        ];
    }
}
