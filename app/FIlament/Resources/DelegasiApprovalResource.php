<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DelegasiApprovalResource\Pages;
use App\Models\DelegasiApproval;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DelegasiApprovalResource extends Resource
{
    protected static ?string $model = DelegasiApproval::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationLabel = 'Delegasi Approval (Cuti)';
    protected static ?string $navigationGroup = 'Pengaturan & Otorisasi';
    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()->is_active;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['pemberi.divisi', 'penerima.divisi']);
        $user = Auth::user();

        if ($user->hasRole('Super Admin')) {
            return $query->latest();
        }

        // Regular users can see delegations where they are either the delegator or the substitute
        return $query->where(function (Builder $q) use ($user) {
            $q->where('id_user_pemberi', $user->id_user)
              ->orWhere('id_user_penerima', $user->id_user);
        })->latest();
    }

    public static function form(Form $form): Form
    {
        $currentUser = Auth::user();
        $isSuperAdmin = $currentUser->hasRole('Super Admin');

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Delegasi & Cuti')
                    ->description('Pilih user pengganti (Plt) dari divisi yang sama untuk menggantikan persetujuan saat Anda berhalangan.')
                    ->schema([
                        Select::make('id_user_pemberi')
                            ->label('User Berhalangan / Cuti')
                            ->options(User::where('is_active', true)->pluck('nama_user', 'id_user'))
                            ->default($currentUser->id_user)
                            ->disabled(!$isSuperAdmin)
                            ->dehydrated()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($set) => $set('id_user_penerima', null)),

                        Select::make('id_user_penerima')
                            ->label('User Pengganti (Plt)')
                            ->helperText('Hanya menampilkan user aktif dari divisi yang sama.')
                            ->options(function (callable $get) use ($currentUser, $isSuperAdmin) {
                                $pemberiId = $get('id_user_pemberi') ?? $currentUser->id_user;
                                $pemberi = User::find($pemberiId);

                                if (!$pemberi || !$pemberi->id_divisi) {
                                    return [];
                                }

                                return User::where('id_divisi', $pemberi->id_divisi)
                                    ->where('id_user', '!=', $pemberiId)
                                    ->where('is_active', true)
                                    ->pluck('nama_user', 'id_user');
                            })
                            ->required()
                            ->searchable(),

                        Select::make('tipe_halangan')
                            ->label('Jenis Halangan')
                            ->options([
                                'Cuti Tahunan' => 'Cuti Tahunan',
                                'Izin Sakit / Emergency' => 'Izin Sakit / Emergency',
                                'Dinas Luar' => 'Dinas Luar',
                                'Acara Mendadak / Lainnya' => 'Acara Mendadak / Lainnya',
                            ])
                            ->default('Cuti Tahunan')
                            ->required(),

                        DateTimePicker::make('tanggal_mulai')
                            ->label('Tanggal & Waktu Mulai')
                            ->default(now())
                            ->required(),

                        DateTimePicker::make('tanggal_selesai')
                            ->label('Tanggal & Waktu Selesai')
                            ->default(now()->addDays(1))
                            ->afterOrEqual('tanggal_mulai')
                            ->required(),

                        Textarea::make('alasan')
                            ->label('Catatan / Alasan Halangan')
                            ->placeholder('Contoh: Cuti tahunan 3 hari / Dinas luar kota')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pemberi.nama_user')
                    ->label('User Cuti / Berhalangan')
                    ->description(fn (DelegasiApproval $record) => $record->pemberi?->divisi?->nama_divisi ?? '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('penerima.nama_user')
                    ->label('User Pengganti (Plt)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tipe_halangan')
                    ->label('Jenis Halangan')
                    ->badge()
                    ->color('info'),

                TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                BadgeColumn::make('status_label')
                    ->label('Status')
                    ->getStateUsing(fn (DelegasiApproval $record) => $record->status_label)
                    ->color(fn (DelegasiApproval $record) => $record->status_color),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Action::make('deactivate')
                    ->label('Akhiri Sekarang')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (DelegasiApproval $record) => $record->is_active && now()->lte($record->tanggal_selesai))
                    ->requiresConfirmation()
                    ->modalHeading('Akhiri Masa Delegasi?')
                    ->modalSubheading('User pengganti tidak akan lagi menerima wewenang persetujuan setelah delegasi ini diakhiri.')
                    ->action(function (DelegasiApproval $record) {
                        $record->update(['is_active' => false]);
                        Notification::make()
                            ->title('Delegasi Diakhiri')
                            ->body('Masa delegasi telah di-nonaktifkan.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDelegasiApprovals::route('/'),
            'create' => Pages\CreateDelegasiApproval::route('/create'),
            'edit' => Pages\EditDelegasiApproval::route('/{record}/edit'),
        ];
    }
}
