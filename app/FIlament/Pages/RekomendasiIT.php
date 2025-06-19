<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\Pengajuan;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms;

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
        return Pengajuan::query()->with(['pemohon.divisi', 'items'])->where('status', Pengajuan::STATUS_REKOMENDASI_IT);
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
                    Select::make('rekomendasi_it_tipe')->label('Tipe Rekomendasi')->options(['Pembelian Baru' => 'Pembelian Baru', 'Perbaikan' => 'Perbaikan'])->required(),
                    Textarea::make('rekomendasi_it_catatan')->label('Catatan Rekomendasi')->required(),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'rekomendasi_it_tipe' => $data['rekomendasi_it_tipe'],
                        'rekomendasi_it_catatan' => $data['rekomendasi_it_catatan'],
                        'status' => Pengajuan::STATUS_SURVEI_GA,
                    ]);
                    Notification::make()->title('Rekomendasi berhasil disubmit')->success()->send();
                }),
        ];
    }
}
