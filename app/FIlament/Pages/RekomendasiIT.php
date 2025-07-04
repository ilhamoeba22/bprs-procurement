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
                ->modalHeading('')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $record->load(['items', 'items.surveiHargas']);
                    $record->estimasi_pengadaan = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $record->estimasi_perbaikan = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $form->fill($record->toArray());
                })->form([
                    Section::make('Detail Pengajuan')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('kode_pengajuan')->disabled(),
                            TextInput::make('status')->disabled(),
                            TextInput::make('total_nilai')->label('Total Nilai'),
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
                        Grid::make(2)->schema([
                            TextInput::make('estimasi_pengadaan')->label('Estimasi Biaya Pengadaan')->disabled(),
                            TextInput::make('estimasi_perbaikan')->label('Estimasi Biaya Perbaikan')->disabled(),
                            TextInput::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->disabled(),
                            TextInput::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->disabled(),
                            Textarea::make('budget_catatan_pengadaan')->label('Catatan Budget Pengadaan')->disabled(),
                            Textarea::make('budget_catatan_perbaikan')->label('Catatan Budget Perbaikan')->disabled(),
                        ]),
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
