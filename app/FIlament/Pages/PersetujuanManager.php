<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\SurveiHarga;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
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
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Concerns\InteractsWithTable;

class PersetujuanManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static string $view = 'filament.pages.persetujuan-manager';
    protected static ?string $navigationLabel = 'Persetujuan Manager';
    protected static ?int $navigationSort = 3;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Manager)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Manager', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();

        return Pengajuan::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER)
                    ->whereHas('pemohon', fn(Builder $q) => $q->where('id_divisi', $user->id_divisi));
            })
            ->orWhere('manager_approved_by', $user->id_user);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Tiket Pengajuan')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('total_nilai')->label('Nilai Pengajuan')->money('IDR'),
            BadgeColumn::make('status'),
            BadgeColumn::make('tindakan_saya')
                ->label('Tindakan Saya')
                ->state(function (Pengajuan $record): string {
                    if (!$record->manager_approved_by) {
                        return 'Menunggu Aksi';
                    }
                    if ($record->status === Pengajuan::STATUS_DITOLAK_MANAGER) {
                        return 'Ditolak';
                    }
                    return 'Disetujui';
                })
                ->color(fn(string $state): string => match ($state) {
                    'Disetujui' => 'success',
                    'Ditolak' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('created_at')->label('Tanggal Dibuat')->dateTime('d M Y H:i'),
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
                })
                ->form([
                    Section::make('Detail Pengajuan')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kode_pengajuan')->disabled(),
                                TextInput::make('status')->disabled(),
                                TextInput::make('total_nilai')->label('Total Nilai'),
                            ]),
                            Repeater::make('items')->relationship()->label('')->schema([
                                Grid::make(6)->schema([
                                    TextInput::make('kategori_barang')->disabled()->columnSpan(2),
                                    TextInput::make('nama_barang')->disabled()->columnSpan(3),
                                    TextInput::make('kuantitas')->disabled()->columnSpan(1),
                                ]),
                                Grid::make(2)->schema([
                                    Textarea::make('spesifikasi')->disabled(),
                                    Textarea::make('justifikasi')->disabled(),
                                ]),
                            ])->columns(2)->disabled()->addActionLabel('Tambah Barang'),
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
                    Section::make('Vendor Harga Final yang Di-approve')
                        ->schema(function (Pengajuan $record) {
                            Log::debug('Processing Vendor Harga Final for pengajuan ID: ' . $record->id_pengajuan);

                            $firstItem = $record->items->first();
                            if (!$firstItem) {
                                return [
                                    Placeholder::make('no_item_placeholder')
                                        ->content('Tidak ada item terkait untuk pengajuan ini.')
                                        ->columnSpanFull(),
                                ];
                            }

                            $surveiHarga = SurveiHarga::where('id_item', $firstItem->id_item)
                                ->where('is_final', 1)
                                ->first();

                            if (!$surveiHarga) {
                                return [
                                    Placeholder::make('no_final_vendor_placeholder')
                                        ->content('Tidak ada data vendor final untuk id_item = ' . $firstItem->id_item . '.')
                                        ->columnSpanFull(),
                                ];
                            }

                            $data = [
                                'nama_barang' => $surveiHarga->item->nama_barang ?? 'N/A',
                                'nama_vendor' => $surveiHarga->nama_vendor ?? 'N/A',
                                'harga' => 'Rp ' . number_format($surveiHarga->harga ?? 0, 0, ',', '.'),
                                'metode_pembayaran' => $surveiHarga->metode_pembayaran ?? 'N/A',
                            ];

                            $content = '<table style="width: 100%; border-collapse: collapse; margin: 10px 0; color: #333; background-color: #fff;">'
                                . '<thead>'
                                . '<tr style="background-color: #e0e0e0;">'
                                . '<th style="border: 1px solid #ccc; padding: 8px; text-align: left;">Label</th>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; text-align: left;">Detail</th>'
                                . '</tr>'
                                . '</thead>'
                                . '<tbody>'
                                . '<tr>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">Nama Barang</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['nama_barang']) . '</td>'
                                . '</tr>'
                                . '<tr>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">Nama Vendor</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['nama_vendor']) . '</td>'
                                . '</tr>'
                                . '<tr>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">Harga</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['harga']) . ' <span style="color: #888; font-size: 12px;">(harga per item)</span></td>'
                                . '</tr>'
                                . '<tr>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">Metode Pembayaran</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['metode_pembayaran']) . '</td>'
                                . '</tr>'
                                . '</tbody>'
                                . '</table>';

                            return [
                                Placeholder::make('final_vendor_data')
                                    ->content(new HtmlString($content))
                                    ->columnSpanFull(),
                            ];
                        })
                        ->visible(fn(Pengajuan $record) => in_array($record->status, [
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                        ]))
                        ->columnSpanFull(),
                ]),

            Action::make('approve')
                ->label('Setujui')->color('success')->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->action(function (Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV,
                        'manager_approved_by' => Auth::id(),
                    ]);
                    Notification::make()->title('Pengajuan disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER),

            Action::make('reject')
                ->label('Tolak')->color('danger')->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([Textarea::make('catatan_revisi')->label('Alasan Penolakan')->required(),])
                ->action(function (array $data, Pengajuan $record) {
                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK_MANAGER,
                        'catatan_revisi' => "[Ditolak oleh Manager: " . Auth::user()->nama_user . "]\n" . $data['catatan_revisi'],
                    ]);
                    Notification::make()->title('Pengajuan ditolak')->danger()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_MANAGER),
        ];
    }
}
