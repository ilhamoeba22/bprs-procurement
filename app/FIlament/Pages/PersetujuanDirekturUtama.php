<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Pengajuan;
use Filament\Infolists\Infolist;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
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
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Forms\Form;

class PersetujuanDirekturUtama extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static string $view = 'filament.pages.persetujuan-direktur-utama';
    protected static ?string $navigationLabel = 'Persetujuan Direktur Utama';
    protected static ?int $navigationSort = 10;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Direktur Utama)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Direktur Utama', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        return Pengajuan::query()->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('total_nilai')->label('Nilai Pengajuan')->money('IDR'),
            BadgeColumn::make('status'),
            TextColumn::make('created_at')->label('Tanggal Dibuat')->dateTime('d M Y H:i'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->mountUsing(fn(Form $form, Pengajuan $record) => $form->fill($record->load('items')->toArray()))
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
            Action::make('approve')
                ->label('Setujui')->color('success')->icon('heroicon-o-check-circle')->requiresConfirmation()
                ->action(fn(Pengajuan $record) => $record->update(['status' => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA])),
            Action::make('reject')
                ->label('Tolak')->color('danger')->icon('heroicon-o-x-circle')->requiresConfirmation()
                ->form([Textarea::make('catatan_revisi')->label('Alasan Penolakan')->required(),])
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                        'catatan_revisi' => $record->catatan_revisi . "\n\n[Ditolak oleh Direktur Utama: " . Auth::user()->nama_user . "]\n" . $data['catatan_revisi'],
                    ]);
                    Notification::make()->title('Pengajuan ditolak')->danger()->send();
                }),
        ];
    }

    protected function getDetailInfolist(Infolist $infolist): Infolist
    {
        return $infolist->record($infolist->getRecord())->schema([
            InfolistSection::make('Detail Pengajuan')
                ->columns(3)
                ->schema([
                    TextEntry::make('kode_pengajuan'),
                    TextEntry::make('status'),
                    TextEntry::make('total_nilai')->money('IDR')->label('Total Nilai'),
                    TextEntry::make('catatan_revisi')->label('Catatan Approval/Revisi')->columnSpanFull(),
                ]),
            InfolistSection::make('Items')
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            TextEntry::make('kategori_barang'),
                            TextEntry::make('nama_barang'),
                            TextEntry::make('kuantitas'),
                            TextEntry::make('spesifikasi')->columnSpanFull(),
                            TextEntry::make('justifikasi')->columnSpanFull(),
                        ])->columns(2),
                ]),
        ]);
    }
}
