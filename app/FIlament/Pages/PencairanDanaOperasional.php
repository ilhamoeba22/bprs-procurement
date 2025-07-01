<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\Repeater;

class PencairanDanaOperasional extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static string $view = 'filament.pages.pencairan-dana-operasional';
    protected static ?string $navigationLabel = 'Pencairan Dana';
    protected static ?int $navigationSort = 11;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Pencairan Dana)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Kepala Divisi Operasional', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        return Pengajuan::query()->with('items')->where('status', Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Tiket Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('total_nilai')->label('Nilai Pengajuan')->money('IDR'),
            BadgeColumn::make('status')->color('info'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->mountUsing(fn(Forms\Form $form, Pengajuan $record) => $form->fill($record->load('items')->toArray()))
                ->form([
                    Section::make('Detail Pengajuan')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kode_pengajuan')->disabled(),
                                TextInput::make('status')->disabled(),
                                TextInput::make('total_nilai')->prefix('Rp')->label('Total Nilai')->disabled(),
                            ]),
                            Textarea::make('catatan_revisi')->label('Catatan Approval/Revisi')->disabled()->columnSpanFull(),

                            // Menampilkan semua catatan dari tahap sebelumnya
                            Grid::make(2)->schema([
                                TextInput::make('rekomendasi_it_tipe')->label('Rekomendasi Tipe dari IT')->disabled(),
                                Textarea::make('rekomendasi_it_catatan')->label('Rekomendasi Catatan dari IT')->disabled(),
                            ])->visible(fn($record) => !empty($record?->rekomendasi_it_tipe)),

                            Grid::make(2)->schema([
                                TextInput::make('budget_status')->label('Status Budget')->disabled(),
                                Textarea::make('budget_catatan')->label('Catatan Budget')->disabled(),
                            ])->visible(fn($record) => !empty($record?->budget_status)),

                            Grid::make(2)->schema([
                                TextInput::make('kadiv_ga_decision_type')->label('Keputusan Tipe dari Kadiv GA')->disabled(),
                                Textarea::make('kadiv_ga_catatan')->label('Keputusan Catatan dari Kadiv GA')->disabled(),
                            ])->visible(fn($record) => !empty($record?->kadiv_ga_decision_type)),
                        ]),

                    Section::make('Items')->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
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

            Action::make('process_disbursement')
                ->label('Tandai Selesai')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Proses Pencairan Dana')
                ->modalDescription('Apakah Anda yakin ingin menandai pengajuan ini sebagai Selesai?')
                ->action(function (Pengajuan $record) {
                    $record->update(['status' => Pengajuan::STATUS_SELESAI]);
                    Notification::make()->title('Pengajuan telah ditandai selesai')->success()->send();
                }),
        ];
    }
}
