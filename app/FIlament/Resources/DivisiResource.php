<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DivisiResource\Pages;
use App\Models\Divisi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;

class DivisiResource extends Resource
{
    protected static ?string $model = Divisi::class;

    // Pengaturan Navigasi
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_divisi')
                    ->required()
                    ->maxLength(255),
                Select::make('id_kantor')
                    ->relationship('kantor', 'nama_kantor') // Mengambil relasi 'kantor' dari Model Divisi
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_divisi')->searchable()->sortable(),
                // Menampilkan nama kantor melalui relasi
                Tables\Columns\TextColumn::make('kantor.nama_kantor')->searchable()->sortable(),
            ])
            ->filters([
                // Filter berdasarkan kantor
                Tables\Filters\SelectFilter::make('kantor')
                    ->relationship('kantor', 'nama_kantor')
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
            'index' => Pages\ListDivisis::route('/'),
            'create' => Pages\CreateDivisi::route('/create'),
            'edit' => Pages\EditDivisi::route('/{record}/edit'),
        ];
    }
}
