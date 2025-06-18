<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Jabatan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;
use Filament\Forms\Get;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Pendaftaran User';
    protected static ?string $modelLabel = 'Pengguna';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_user')->required()->maxLength(255),
                Forms\Components\TextInput::make('nik_user')->label('NIK (Username)')->required()->unique(ignoreRecord: true)->alphaNum()->maxLength(255),
                Forms\Components\TextInput::make('password')->password()->dehydrateStateUsing(fn(string $state): string => Hash::make($state))->dehydrated(fn(?string $state): bool => filled($state))->required(fn(string $operation): bool => $operation === 'create'),
                Select::make('roles') // Nama field harus 'roles'
                    ->multiple()
                    ->relationship('roles', 'name') // Mengambil relasi 'roles' dari model User
                    ->searchable()
                    ->preload(),
                Select::make('id_jabatan')->relationship('jabatan', 'nama_jabatan')->searchable()->preload()->required()->live()
                    ->afterStateUpdated(function (Set $set, ?int $state) {
                        $jabatan = $state ? Jabatan::find($state) : null;
                        $set('id_kantor', $jabatan?->id_kantor);
                        $set('id_divisi', $jabatan?->id_divisi);
                    }),
                Forms\Components\Hidden::make('id_kantor'),
                Forms\Components\Hidden::make('id_divisi'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_user')->searchable(),
                Tables\Columns\TextColumn::make('nik_user')->label('NIK (Username)')->searchable(),
                Tables\Columns\TextColumn::make('jabatan.nama_jabatan')->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListUsers::route('/'), 'create' => Pages\CreateUser::route('/create'), 'edit' => Pages\EditUser::route('/{record}/edit')];
    }
}
