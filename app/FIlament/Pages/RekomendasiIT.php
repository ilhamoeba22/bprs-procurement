<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;

class RekomendasiIT extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static string $view = 'filament.pages.rekomendasi-i-t';
    protected static ?string $navigationLabel = 'Rekomendasi IT';
    protected static ?int $navigationSort = 5;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Rekomendasi IT)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Kepala Divisi IT', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        return Pengajuan::query()
            ->where('status', Pengajuan::STATUS_REKOMENDASI_IT)
            ->orWhere('it_recommended_by', $user->id_user);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            BadgeColumn::make('status'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->mountUsing(fn(Forms\Form $form, Pengajuan $record) => $form->fill($record->load(['items'])->toArray()))
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
                    Section::make('Items')->schema([
                        Repeater::make('items')->relationship()->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kategori_barang')->disabled(),
                                TextInput::make('nama_barang')->disabled()->columnSpan(2),
                                TextInput::make('kuantitas')->disabled(),
                            ]),
                            Textarea::make('spesifikasi')->disabled()->columnSpanFull(),
                            Textarea::make('justifikasi')->disabled()->columnSpanFull(),
                        ])->columns(2)->disabled(),
                    ]),
                ]),

            Action::make('submit_recommendation')
                ->label('Submit Rekomendasi')->color('primary')->icon('heroicon-o-chat-bubble-bottom-center-text')
                ->form([
                    Forms\Components\Select::make('rekomendasi_it_tipe')->label('Tipe Rekomendasi')->options(['Pembelian Baru' => 'Pembelian Baru', 'Perbaikan' => 'Perbaikan'])->required(),
                    Forms\Components\Textarea::make('rekomendasi_it_catatan')->label('Catatan Rekomendasi')->required(),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'rekomendasi_it_tipe' => $data['rekomendasi_it_tipe'],
                        'rekomendasi_it_catatan' => $data['rekomendasi_it_catatan'],
                        'status' => Pengajuan::STATUS_SURVEI_GA,
                        'it_recommended_by' => Auth::id(), // Catat siapa yang memberi rekomendasi
                    ]);
                    Notification::make()->title('Rekomendasi berhasil disubmit')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_REKOMENDASI_IT),
        ];
    }
}
