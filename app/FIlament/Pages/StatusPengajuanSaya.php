<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Models\Pengajuan;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms;

class StatusPengajuanSaya extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static string $view = 'filament.pages.status-pengajuan-saya';
    protected static ?string $navigationLabel = 'Status Pengajuan Saya';
    protected static ?int $navigationSort = 2;

    public function getTitle(): string
    {
        return 'Status Pengajuan Saya';
    }

    protected function getTableQuery(): Builder
    {
        return Pengajuan::query()->with('items')->where('id_user_pemohon', Auth::id())->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode Pengajuan')->searchable(),
            BadgeColumn::make('status')
                ->colors([
                    'gray'      => Pengajuan::STATUS_DRAFT,
                    'danger'    => [
                        Pengajuan::STATUS_DITOLAK_MANAGER,
                        Pengajuan::STATUS_DITOLAK_KADIV,
                        Pengajuan::STATUS_DITOLAK_KADIV_GA,
                        Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                        Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                    ],
                    'warning'   => fn($state) => in_array($state, [
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV,
                        Pengajuan::STATUS_REKOMENDASI_IT,
                        Pengajuan::STATUS_SURVEI_GA,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                    ]),
                    'info'      => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                    'success'   => Pengajuan::STATUS_SELESAI,
                ]),
            TextColumn::make('total_nilai')->label('Total Nilai')->money('IDR')->sortable(),
            TextColumn::make('created_at')->label('Tanggal Dibuat')->dateTime('d M Y H:i')->sortable(),
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

                            // Menampilkan Rekomendasi IT jika ada
                            Grid::make(2)->schema([
                                TextInput::make('rekomendasi_it_tipe')->label('Rekomendasi Tipe dari IT')->disabled(),
                                TextInput::make('rekomendasi_it_catatan')->label('Rekomendasi Catatan dari IT')->disabled(),
                            ])->visible(fn($record) => !empty($record?->rekomendasi_it_tipe)),

                            // Menampilkan Hasil Budget Control jika ada
                            // Grid::make(2)->schema([
                            //     TextInput::make('budget_status')->label('Status Budget')->disabled(),
                            //     TextInput::make('budget_catatan')->label('Catatan Budget')->disabled(),
                            // ])->visible(fn($record) => !empty($record?->budget_status)),

                            // Menampilkan Keputusan Kadiv GA jika ada
                            Grid::make(2)->schema([
                                TextInput::make('kadiv_ga_decision_type')->label('Keputusan Tipe dari Kadiv GA')->disabled(),
                                TextInput::make('kadiv_ga_catatan')->label('Keputusan Catatan dari Kadiv GA')->disabled(),
                            ])->visible(fn($record) => !empty($record?->kadiv_ga_decision_type)),
                        ]),
                    // Menambahkan bagian baru untuk menampilkan hasil budget control
                    Section::make('Hasil Budget Control')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('budget_status')->label('Status Ketersediaan Budget')->disabled(),
                                Textarea::make('budget_catatan')->label('Catatan Tim Budgeting')->disabled()->columnSpanFull(),
                            ])
                        ])
                        ->visible(fn($record) => !empty($record?->budget_status)),

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
        ];
    }
}
