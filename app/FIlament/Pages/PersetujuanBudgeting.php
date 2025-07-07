<?php

namespace App\Filament\Pages;

use Log;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\SurveiHarga;
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
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;

class PersetujuanBudgeting extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.persetujuan-budgeting';
    protected static ?string $navigationLabel = 'Persetujuan Budgeting';
    protected static ?int $navigationSort = 7;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Persetujuan Budgeting)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Tim Budgeting', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas']);
        $statuses = [
            Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET,
            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
        ];

        if (! $user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user, $statuses) {
                $q->whereIn('status', $statuses)
                    ->orWhere('budget_approved_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', $statuses)
                ->orWhereNotNull('budget_approved_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Tiket Pengajuan')->sortable()->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->sortable()->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi')->sortable()->searchable(),
            TextColumn::make('total_nilai')->label('Total Nilai')->money('IDR')->sortable(),
            BadgeColumn::make('status')->label('Status Saat Ini'),
            BadgeColumn::make('tindakan_saya')
                ->label('Keterangan')
                ->state(function (Pengajuan $record): string {
                    if ($record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET) {
                        return 'Menunggu Aksi';
                    }
                    if ($record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET) {
                        return 'Menunggu Validasi Kadiv Ops';
                    }
                    return 'Sudah Direview';
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Direview' => 'success',
                    'Menunggu Validasi Kadiv Ops' => 'warning',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableActions(): array
    {
        $viewAction = ViewAction::make()->label('Detail')
            ->modalHeading('')
            ->mountUsing(function (Form $form, Pengajuan $record) {
                $record->load(['items', 'items.surveiHargas']);
                $data = $record->toArray();

                $itemIds = $record->items->pluck('id_item')->toArray();

                $pengadaan = SurveiHarga::whereIn('id_item', $itemIds)
                    ->where('tipe_survei', 'Pengadaan')
                    ->orderBy('harga', 'asc')
                    ->first();
                $perbaikan = SurveiHarga::whereIn('id_item', $itemIds)
                    ->where('tipe_survei', 'Perbaikan')
                    ->orderBy('harga', 'asc')
                    ->first();

                $totalPengadaan = $record->items->reduce(function ($carry, $item) {
                    $minHarga = SurveiHarga::where('id_item', $item->id_item)
                        ->where('tipe_survei', 'Pengadaan')
                        ->min('harga');
                    return $carry + (($minHarga ?? 0) * $item->kuantitas);
                }, 0);
                $totalPerbaikan = $record->items->reduce(function ($carry, $item) {
                    $minHarga = SurveiHarga::where('id_item', $item->id_item)
                        ->where('tipe_survei', 'Perbaikan')
                        ->min('harga');
                    return $carry + (($minHarga ?? 0) * $item->kuantitas);
                }, 0);

                $data['estimasi_pengadaan'] = 'Rp ' . number_format($totalPengadaan, 0, ',', '.');
                $data['estimasi_perbaikan'] = 'Rp ' . number_format($totalPerbaikan, 0, ',', '.');
                $data['nama_vendor_pengadaan'] = $pengadaan ? $pengadaan->nama_vendor : 'Tidak tersedia';
                $data['nama_vendor_perbaikan'] = $perbaikan ? $perbaikan->nama_vendor : 'Tidak tersedia';
                $data['budget_status_pengadaan'] = $record->budget_status_pengadaan ?? 'Tidak tersedia';
                $data['budget_status_perbaikan'] = $record->budget_status_perbaikan ?? 'Tidak tersedia';
                $data['nama_divisi'] = $record->pemohon->divisi->nama_divisi ?? 'Tidak tersedia';
                $data['nama_barang'] = $record->items->pluck('nama_barang')->implode(', ') ?: 'Tidak tersedia';

                Log::info('Mapped data for form:', $data);
                $form->fill($data);
            })
            ->form([
                Section::make('Detail Pengajuan')->schema([
                    Grid::make(3)->schema([
                        TextInput::make('kode_pengajuan')->disabled(),
                        TextInput::make('status')->disabled(),
                        TextInput::make('total_nilai')->label('Total Nilai')->disabled(),
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
                ]),
                Section::make('Hasil Survei Harga')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('estimasi_pengadaan')->label('Estimasi Biaya Pengadaan')->disabled(),
                        TextInput::make('estimasi_perbaikan')->label('Estimasi Biaya Perbaikan')->disabled(),
                        TextInput::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->disabled(),
                        TextInput::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->disabled(),
                        Textarea::make('budget_catatan_pengadaan')->label('Catatan Budget Pengadaan')->disabled(),
                        Textarea::make('budget_catatan_perbaikan')->label('Catatan Budget Perbaikan')->disabled(),
                        Textarea::make('catatan_revisi')->label('Riwayat Catatan Approval')->disabled()->columnSpanFull(),
                    ]),
                ]),
            ]);
        $budgetReviewForm = function (Pengajuan $record) {
            $totalPengadaan = $record->items->reduce(fn($c, $i) => $c + (($i->surveiPengadaan()->min('harga') ?? 0) * $i->kuantitas), 0);
            $totalPerbaikan = $record->items->reduce(fn($c, $i) => $c + (($i->surveiPerbaikan()->min('harga') ?? 0) * $i->kuantitas), 0);

            return [
                Section::make('Detail Pengajuan')->schema([
                    Grid::make(3)->schema([
                        TextInput::make('kode_pengajuan')->label('Kode Pengajuan')->disabled()->default($record->kode_pengajuan),
                        TextInput::make('nama_divisi')->label('Divisi')->disabled()->default($record->pemohon->divisi->nama_divisi ?? 'Tidak tersedia'),
                        TextInput::make('nama_barang')->label('Nama Barang')->disabled()->default($record->items->pluck('nama_barang')->implode(', ') ?: 'Tidak tersedia'),
                    ]),
                ]),
                Section::make('Review Budget Skenario Pengadaan')
                    ->description('Total Estimasi: Rp ' . number_format($totalPengadaan, 0, ',', '.'))
                    ->schema([
                        Select::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->options([
                            'Budget Tersedia' => 'Budget Tersedia',
                            'Budget Habis' => 'Budget Habis',
                            'Budget Tidak Ada di RBB' => 'Budget Tidak Ada di RBB'
                        ])
                            ->default($record->budget_status_pengadaan)
                            ->required(),
                        Textarea::make('budget_catatan_pengadaan')
                            ->label('Catatan')
                            ->default($record->budget_catatan_pengadaan)
                            ->required(),
                    ]),
                Section::make('Review Budget untuk Skenario PERBAIKAN')
                    ->description('Total Estimasi: Rp ' . number_format($totalPerbaikan, 0, ',', '.'))
                    ->schema([
                        Select::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->options([
                            'Budget Tersedia' => 'Budget Tersedia',
                            'Budget Habis' => 'Budget Habis',
                            'Budget Tidak Ada di RBB' => 'Budget Tidak Ada di RBB'
                        ])
                            ->default($record->budget_status_perbaikan)
                            ->required(),
                        Textarea::make('budget_catatan_perbaikan')
                            ->label('Catatan')
                            ->default($record->budget_catatan_perbaikan)
                            ->required(),
                    ]),
            ];
        };
        $submitAction = Action::make('submit_budget_review')
            ->label('Submit Review Budget')->color('primary')->icon('heroicon-o-pencil-square')
            ->modalHeading('Form Review Budget')
            ->form($budgetReviewForm)
            ->action(function (array $data, Pengajuan $record) {
                $record->update([
                    'budget_status_pengadaan' => $data['budget_status_pengadaan'],
                    'budget_catatan_pengadaan' => $data['budget_catatan_pengadaan'],
                    'budget_status_perbaikan' => $data['budget_status_perbaikan'],
                    'budget_catatan_perbaikan' => $data['budget_catatan_perbaikan'],
                    'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                    'budget_approved_by' => Auth::id(),
                ]);
                Notification::make()->title('Review budget berhasil disubmit ke Kadiv Operasional')->success()->send();
            })
            ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET);

        $editAction = Action::make('edit_budget_review')
            ->label('Edit Review Budget')->color('warning')->icon('heroicon-o-pencil')
            ->modalHeading('Edit Review Budget')
            ->form($budgetReviewForm)
            ->action(function (array $data, Pengajuan $record) {
                $record->update([
                    'budget_status_pengadaan' => $data['budget_status_pengadaan'],
                    'budget_catatan_pengadaan' => $data['budget_catatan_pengadaan'],
                    'budget_status_perbaikan' => $data['budget_status_perbaikan'],
                    'budget_catatan_perbaikan' => $data['budget_catatan_perbaikan'],
                ]);
                Notification::make()->title('Review budget berhasil diupdate')->success()->send();
            })
            ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET);

        return [
            $viewAction,
            $submitAction,
            $editAction,
        ];
    }
}
