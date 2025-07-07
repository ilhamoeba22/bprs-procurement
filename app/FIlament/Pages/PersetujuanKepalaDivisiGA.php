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
use Filament\Forms\Components\Radio;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Concerns\InteractsWithTable;

class PersetujuanKepalaDivisiGA extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static string $view = 'filament.pages.persetujuan-kepala-divisi-g-a';
    protected static ?string $navigationLabel = 'Persetujuan Kadiv GA';
    protected static ?int $navigationSort = 9;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Kadiv GA)';
    }

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['Kepala Divisi GA', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas', 'items.vendorFinal']);

        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA)
                    ->orWhere(function (Builder $q) use ($user) {
                        $q->whereIn('status', [
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                        ])->where('kadiv_ga_approved_by', $user->id_user);
                    });
            });
        } else {
            $query->whereIn('status', [
                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                Pengajuan::STATUS_DITOLAK_KADIV_GA,
                Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                Pengajuan::STATUS_SUDAH_BAYAR,
                Pengajuan::STATUS_SELESAI,
            ]);
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            TextColumn::make('total_nilai')->label('Nilai Pengajuan')->money('IDR'),
            BadgeColumn::make('status')->label('Status Saat Ini'),
            BadgeColumn::make('keputusan_kadiv_ga')
                ->label('Keputusan Kadiv GA')
                ->state(function (Pengajuan $record): string {
                    if ($record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA) {
                        return 'Menunggu Keputusan';
                    }
                    if ($record->kadiv_ga_decision_type === 'Pengadaan') {
                        return 'Disetujui Pengadaan';
                    }
                    if ($record->kadiv_ga_decision_type === 'Perbaikan') {
                        return 'Disetujui Perbaikan';
                    }
                    if ($record->status === Pengajuan::STATUS_DITOLAK_KADIV_GA) {
                        return 'Ditolak';
                    }
                    return $record->kadiv_ga_decision_type ? $record->kadiv_ga_decision_type : 'Tidak Diketahui';
                })
                ->color(function (string $state): string {
                    return match ($state) {
                        'Menunggu Keputusan' => 'warning',
                        'Disetujui Pengadaan', 'Disetujui Perbaikan' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    };
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading('')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $record->load(['items', 'items.surveiHargas', 'pemohon.divisi']);
                    $data = $record->toArray();
                    $data['estimasi_pengadaan'] = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $data['estimasi_perbaikan'] = 'Rp ' . number_format($record->items->reduce(fn($c, $i) => $c + (($i->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0) * $i->kuantitas), 0), 0, ',', '.');
                    $data['nama_divisi'] = $record->pemohon->divisi->nama_divisi ?? 'Tidak tersedia';
                    $data['nama_barang'] = $record->items->pluck('nama_barang')->implode(', ') ?: 'Tidak tersedia';
                    $data['catatan_revisi'] = $record->catatan_revisi ?? 'Tidak ada riwayat catatan approval.';
                    $form->fill($data);
                })
                ->form([
                    Section::make('Detail Pengajuan')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kode_pengajuan')->disabled(),
                                TextInput::make('status')->disabled(),
                                TextInput::make('total_nilai')->label('Total Nilai')->disabled(),
                                TextInput::make('nama_divisi')->label('Divisi')->disabled(),
                                TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
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
                                'opsi_pembayaran' => $surveiHarga->opsi_pembayaran ?? 'N/A',
                            ];

                            $content = '<table style="width: 100%; border-collapse: collapse; margin: 10px 0; color: #333; background-color: #fff;">'
                                . '<thead><tr>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; font-weight: bold;">Nama Barang</th>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; font-weight: bold;">Nama Vendor</th>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; font-weight: bold;">Harga</th>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; font-weight: bold;">Metode Pembayaran</th>'
                                . '<th style="border: 1px solid #ccc; padding: 8px; font-weight: bold;">Opsi Pembayaran</th>'
                                . '</tr></thead>'
                                . '<tbody><tr>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['nama_barang']) . '</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['nama_vendor']) . '</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['harga']) . ' <span style="color: #888; font-size: 15px;">/ Item</span></td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['metode_pembayaran']) . '</td>'
                                . '<td style="border: 1px solid #ccc; padding: 8px;">' . htmlspecialchars($data['opsi_pembayaran']) . '</td>'
                                . '</tr></tbody>'
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
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                        ]))
                        ->columnSpanFull(),
                ]),
            Action::make('process_decision')
                ->label('Proses Keputusan')
                ->color('primary')
                ->icon('heroicon-o-check-circle')
                ->form([
                    Radio::make('keputusan_final')
                        ->label('Persetujuan Final')
                        ->options(['Pengadaan' => 'Lanjutkan dengan Pengadaan', 'Perbaikan' => 'Lanjutkan dengan Perbaikan', 'Tolak' => 'Tolak Pengajuan'])
                        ->required(),
                    Textarea::make('kadiv_ga_catatan')->label('Catatan Keputusan (Wajib diisi jika ditolak)'),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    Log::info('Processing decision for pengajuan ID: ' . $record->id_pengajuan, [
                        'user_id' => Auth::id(),
                        'keputusan_final' => $data['keputusan_final'],
                        'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                    ]);

                    if ($data['keputusan_final'] === 'Tolak' && empty($data['kadiv_ga_catatan'])) {
                        Notification::make()->title('Catatan wajib diisi untuk menolak pengajuan.')->danger()->send();
                        Log::warning('Decision rejected due to missing kadiv_ga_catatan for pengajuan ID: ' . $record->id_pengajuan);
                        return;
                    }

                    $catatan = $record->catatan_revisi ?? '';
                    if (!empty($data['kadiv_ga_catatan'])) {
                        $user = Auth::user()->nama_user;
                        $catatan .= "\n\n[Keputusan oleh Kadiv GA: {$user} pada " . now()->format('d-m-Y H:i') . "]\n" . $data['kadiv_ga_catatan'];
                    }

                    if ($data['keputusan_final'] === 'Tolak') {
                        $record->update([
                            'status' => Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            'catatan_revisi' => trim($catatan),
                            'kadiv_ga_approved_by' => Auth::id(),
                        ]);
                        Notification::make()->title('Pengajuan ditolak')->danger()->send();
                        Log::info('Pengajuan ID: ' . $record->id_pengajuan . ' rejected by Kadiv GA');
                        return;
                    }

                    $nilaiFinal = 0;
                    $budgetFinal = '';
                    if ($data['keputusan_final'] === 'Pengadaan') {
                        $nilaiFinal = $record->items->reduce(function ($carry, $item) {
                            $minHarga = $item->surveiHargas->where('tipe_survei', 'Pengadaan')->min('harga') ?? 0;
                            $minSurveiHarga = $item->surveiHargas->where('tipe_survei', 'Pengadaan')->where('harga', $minHarga)->first();
                            if ($minSurveiHarga) {
                                $item->surveiHargas()->where('tipe_survei', 'Pengadaan')->where('id', '!=', $minSurveiHarga->id)->update(['is_final' => false]);
                                $minSurveiHarga->update(['is_final' => true]);
                                Log::info('Set is_final for survei_harga ID: ' . $minSurveiHarga->id . ' (Pengadaan) for item ID: ' . $item->id_item);
                            } else {
                                Log::warning('No surveiHarga found for Pengadaan for item ID: ' . $item->id_item);
                            }
                            return $carry + ($minHarga * $item->kuantitas);
                        }, 0);
                        $budgetFinal = $record->budget_status_pengadaan;
                    } else {
                        $nilaiFinal = $record->items->reduce(function ($carry, $item) {
                            $minHarga = $item->surveiHargas->where('tipe_survei', 'Perbaikan')->min('harga') ?? 0;
                            $minSurveiHarga = $item->surveiHargas->where('tipe_survei', 'Perbaikan')->where('harga', $minHarga)->first();
                            if ($minSurveiHarga) {
                                $item->surveiHargas()->where('tipe_survei', 'Perbaikan')->where('id', '!=', $minSurveiHarga->id)->update(['is_final' => false]);
                                $minSurveiHarga->update(['is_final' => true]);
                                Log::info('Set is_final for survei_harga ID: ' . $minSurveiHarga->id . ' (Perbaikan) for item ID: ' . $item->id_item);
                            } else {
                                Log::warning('No surveiHarga found for Perbaikan for item ID: ' . $item->id_item);
                            }
                            return $carry + ($minHarga * $item->kuantitas);
                        }, 0);
                        $budgetFinal = $record->budget_status_perbaikan;
                    }

                    $newStatus = '';
                    if ($budgetFinal === 'Budget Tersedia' && $nilaiFinal <= 5000000) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA;
                    } elseif ($budgetFinal === 'Budget Habis' || $budgetFinal === 'Budget Tidak Ada di RBB' || ($nilaiFinal > 5000000 && $nilaiFinal <= 100000000)) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL;
                    } elseif ($nilaiFinal > 100000000) {
                        $newStatus = Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA;
                    }

                    $record->update([
                        'total_nilai' => $nilaiFinal,
                        'kadiv_ga_decision_type' => $data['keputusan_final'],
                        'kadiv_ga_catatan' => $data['kadiv_ga_catatan'],
                        'catatan_revisi' => trim($catatan),
                        'status' => $newStatus,
                        'kadiv_ga_approved_by' => Auth::id(),
                    ]);

                    Notification::make()->title('Keputusan berhasil diproses')->success()->send();
                    Log::info('Decision processed for pengajuan ID: ' . $record->id_pengajuan, [
                        'new_status' => $newStatus,
                        'total_nilai' => $nilaiFinal,
                        'kadiv_ga_decision_type' => $data['keputusan_final'],
                    ]);
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA),
        ];
    }
}
