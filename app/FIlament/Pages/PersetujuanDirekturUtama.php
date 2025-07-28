<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\SurveiHarga;
use Filament\Infolists\Infolist;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
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
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;

class PersetujuanDirekturUtama extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static string $view = 'filament.pages.persetujuan-direktur-utama';
    protected static ?string $navigationLabel = 'Persetujuan Direktur Utama';
    protected static ?int $navigationSort = 11;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Direktur Utama)';
    }

    public static function canAccess(): bool
    {
        return Auth::user() && Auth::user()->hasAnyRole(['Direktur Utama', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon', 'pemohon.divisi']);

        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA)
                    ->orWhere('direktur_utama_approved_by', $user->id_user);
            });
        } else {
            $query->where('status', Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA)
                ->orWhereNotNull('direktur_utama_approved_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            // --- KOLOM TOTAL NILAI DENGAN LOGIKA PERMANEN ---
            TextColumn::make('total_nilai')
                ->label('Total Nilai')
                ->state(function (Pengajuan $record): ?float {
                    $revisi = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas?->first();
                    // Jika ada revisi, nilai revisi menjadi nilai permanen. Jika tidak, gunakan nilai awal.
                    return $revisi?->harga_revisi ?? $record->total_nilai;
                })
                ->money('IDR')
                ->icon(function (Pengajuan $record): ?string {
                    $revisiExists = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas()->exists();
                    return $revisiExists ? 'heroicon-o-arrow-path' : null;
                })
                ->color(function (Pengajuan $record): ?string {
                    $revisiExists = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas()->exists();
                    return $revisiExists ? 'warning' : null;
                })
                ->description(function (Pengajuan $record): ?string {
                    $revisiExists = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas()->exists();
                    if ($revisiExists) {
                        return 'Nilai Awal: ' . number_format($record->total_nilai, 0, ',', '.');
                    }
                    return null;
                })
                ->sortable(),
            BadgeColumn::make('status')
                ->label('Status Saat Ini')
                ->color(fn($state) => Pengajuan::getStatusBadgeColor($state)),
            BadgeColumn::make('tindakan_saya')
                ->label('Tindakan Saya')
                ->state(function (Pengajuan $record): string {
                    if (!$record->direktur_utama_approved_by) {
                        return 'Menunggu Aksi';
                    }
                    return $record->direktur_utama_decision_type ?? ($record->status === Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA ? 'Ditolak' : 'Disetujui');
                })
                ->color(fn(string $state): string => match ($state) {
                    'Disetujui' => 'success',
                    'Ditolak' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('created_at')
                ->label('Tanggal Dibuat')
                ->dateTime('d M Y H:i'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading('Detail Pengajuan dan Review Budget')
                ->modalWidth('4xl')
                ->mountUsing(function (Forms\Form $form, Pengajuan $record) {
                    $record->load(['items', 'items.surveiHargas', 'pemohon.divisi']);
                    $getScenarioDetails = function ($items, $tipeSurvei) {
                        $details = [];
                        $totalCost = 0;

                        foreach ($items as $item) {
                            $cheapestSurvey = $item->surveiHargas->where('tipe_survei', $tipeSurvei)->sortBy('harga')->first();
                            if (!$cheapestSurvey) continue;

                            $itemCost = $cheapestSurvey->harga * $item->kuantitas;
                            $taxInfo = 'Tidak ada pajak';
                            $taxCost = 0;

                            if ($cheapestSurvey->kondisi_pajak === 'Pajak ditanggung kita') {
                                $taxCost = $cheapestSurvey->nominal_pajak;
                                $taxInfo = ($cheapestSurvey->jenis_pajak ?? 'Pajak') . ': Rp ' . number_format($taxCost, 0, ',', '.');
                            }

                            $details[] = [
                                'nama_barang' => $item->nama_barang . " (x{$item->kuantitas})",
                                'harga_vendor' => 'Rp ' . number_format($itemCost, 0, ',', '.'),
                                'pajak_info' => $taxInfo,
                            ];

                            $totalCost += ($itemCost + $taxCost);
                        }

                        return empty($details) ? null : ['details' => $details, 'total' => 'Rp ' . number_format($totalCost, 0, ',', '.')];
                    };

                    $data = $record->toArray();
                    $data['pengadaan_details'] = $getScenarioDetails($record->items, 'Pengadaan');
                    $data['perbaikan_details'] = $getScenarioDetails($record->items, 'Perbaikan');
                    $data['nama_divisi'] = $record->pemohon->divisi->nama_divisi ?? 'Tidak tersedia';
                    $data['nama_barang'] = $record->items->pluck('nama_barang')->implode(', ') ?: 'Tidak tersedia';
                    $data['catatan_revisi'] = $record->catatan_revisi ?? 'Tidak ada riwayat catatan approval.';

                    Log::debug('Form data for Pengajuan ID: ' . $record->id_pengajuan, [
                        'pengadaan_details' => $data['pengadaan_details'],
                        'perbaikan_details' => $data['perbaikan_details'],
                    ]);

                    $form->fill($data);
                })
                ->form([
                    Section::make('Detail Pengajuan')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('kode_pengajuan')
                                    ->label('Kode Pengajuan')
                                    ->disabled(),
                                TextInput::make('status')
                                    ->label('Status')
                                    ->disabled(),
                                TextInput::make('total_nilai')
                                    ->label('Total Nilai')
                                    ->disabled(),
                                TextInput::make('nama_divisi')
                                    ->label('Divisi')
                                    ->disabled(),
                                TextInput::make('nama_barang')
                                    ->label('Nama Barang')
                                    ->disabled(),
                            ]),
                            Repeater::make('items')
                                ->relationship()
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('kategori_barang')
                                            ->label('Kategori Barang')
                                            ->disabled(),
                                        TextInput::make('nama_barang')
                                            ->label('Nama Barang')
                                            ->disabled(),
                                        TextInput::make('kuantitas')
                                            ->label('Kuantitas')
                                            ->disabled(),
                                    ]),
                                    Grid::make(2)->schema([
                                        Textarea::make('spesifikasi')
                                            ->label('Spesifikasi')
                                            ->disabled(),
                                        Textarea::make('justifikasi')
                                            ->label('Justifikasi')
                                            ->disabled(),
                                    ]),
                                ])
                                ->columns(1)
                                ->disabled()
                                ->addActionLabel('Tambah Barang'),
                            Grid::make(2)->schema([
                                TextInput::make('rekomendasi_it_tipe')
                                    ->label('Rekomendasi Tipe dari IT')
                                    ->disabled(),
                                Textarea::make('rekomendasi_it_catatan')
                                    ->label('Rekomendasi Catatan dari IT')
                                    ->disabled(),
                            ])
                                ->visible(fn($record) => !empty($record?->rekomendasi_it_tipe)),
                        ])
                        ->collapsible()
                        ->collapsed(),

                    Section::make('Rincian Estimasi Biaya - Skenario PENGADAAN')
                        ->schema([
                            Repeater::make('pengadaan_details.details')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('nama_barang')
                                            ->label('Item')
                                            ->disabled(),
                                        TextInput::make('harga_vendor')
                                            ->label('Harga dari Vendor')
                                            ->disabled(),
                                        TextInput::make('pajak_info')
                                            ->label('Pajak Ditanggung Perusahaan')
                                            ->disabled(),
                                    ])
                                ])
                                ->disabled()
                                ->disableItemCreation()
                                ->disableItemDeletion()
                                ->disableItemMovement(),
                            Placeholder::make('pengadaan_details.total')
                                ->label('TOTAL ESTIMASI BIAYA PENGADAAN')
                                ->content(fn($get) => new HtmlString('<span class="text-xl text-primary-600 font-bold">' . ($get('pengadaan_details.total') ?? 'Rp 0') . '</span>')),
                        ])
                        ->collapsible()
                        ->collapsed(),

                    Section::make('Rincian Estimasi Biaya - Skenario PERBAIKAN')
                        ->schema([
                            Repeater::make('perbaikan_details.details')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('nama_barang')
                                            ->label('Item')
                                            ->disabled(),
                                        TextInput::make('harga_vendor')
                                            ->label('Harga dari Vendor')
                                            ->disabled(),
                                        TextInput::make('pajak_info')
                                            ->label('Pajak Ditanggung Perusahaan')
                                            ->disabled(),
                                    ])
                                ])
                                ->disabled()
                                ->disableItemCreation()
                                ->disableItemDeletion()
                                ->disableItemMovement(),
                            Placeholder::make('perbaikan_details.total')
                                ->label('TOTAL ESTIMASI BIAYA PERBAIKAN')
                                ->content(fn($get) => new HtmlString('<span class="text-xl text-success-600 font-bold">' . ($get('perbaikan_details.total') ?? 'Rp 0') . '</span>')),
                        ])
                        ->visible(fn($get) => !empty($get('perbaikan_details.details')))
                        ->collapsible()
                        ->collapsed(),

                    Section::make('Review Budgeting')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('budget_status_pengadaan')
                                    ->label('Status Budget Pengadaan')
                                    ->disabled(),
                                Textarea::make('budget_catatan_pengadaan')
                                    ->label('Catatan Budget Pengadaan')
                                    ->disabled(),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('budget_status_perbaikan')
                                    ->label('Status Budget Perbaikan')
                                    ->disabled(),
                                Textarea::make('budget_catatan_perbaikan')
                                    ->label('Catatan Budget Perbaikan')
                                    ->disabled(),
                            ])
                                ->visible(fn($get) => !is_null($get('budget_status_perbaikan'))),
                        ])
                        ->collapsible()
                        ->collapsed(),

                    Section::make('Final Approve')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('kadiv_ga_decision_type')
                                    ->label('Keputusan Kadiv GA')
                                    ->disabled()
                                    ->default(fn($get) => $get('kadiv_ga_decision_type') ?? 'Tidak Diketahui'),
                                Textarea::make('kadiv_ga_catatan')
                                    ->label('Catatan Kadiv GA')
                                    ->disabled()
                                    ->default(fn($get) => $get('kadiv_ga_catatan') ?? 'Tidak ada catatan'),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('direktur_operasional_decision_type')
                                    ->label('Keputusan Direktur Operasional')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_operasional_decision_type') ?? 'Tidak Diketahui'),
                                Textarea::make('direktur_operasional_catatan')
                                    ->label('Catatan Direktur Operasional')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_operasional_catatan') ?? 'Tidak ada catatan'),
                            ])->visible(fn($get) => !empty($get('direktur_operasional_decision_type')) || !empty($get('direktur_operasional_catatan'))),
                            Grid::make(2)->schema([
                                TextInput::make('direktur_utama_decision_type')
                                    ->label('Keputusan Direktur Utama')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_utama_decision_type') ?? 'Tidak Diketahui'),
                                Textarea::make('direktur_utama_catatan')
                                    ->label('Catatan Direktur Utama')
                                    ->disabled()
                                    ->default(fn($get) => $get('direktur_utama_catatan') ?? 'Tidak ada catatan'),
                            ])->visible(fn($get) => !empty($get('direktur_utama_decision_type')) || !empty($get('direktur_utama_catatan'))),
                        ])
                        ->visible(fn(Pengajuan $record) => in_array($record->status, [
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                        ]))
                        ->collapsible()
                        ->collapsed(),
                ]),

            Action::make('approve')
                ->label('Setujui')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->form([
                    Hidden::make('direktur_utama_decision_type')
                        ->default('Disetujui'),
                    TextInput::make('display_decision_type')
                        ->label('Jenis Keputusan')
                        ->default('Disetujui')
                        ->disabled(),
                    Textarea::make('direktur_utama_catatan')
                        ->label('Catatan (Opsional)')
                        ->required(false),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $catatanTambahan = "\n\n[Disetujui oleh Direktur Utama: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                    $catatanRevisi = ($record->catatan_revisi ?? '') . $catatanTambahan;

                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                        'direktur_utama_approved_by' => Auth::id(),
                        'direktur_utama_decision_type' => $data['direktur_utama_decision_type'] ?? 'Disetujui',
                        'direktur_utama_catatan' => $data['direktur_utama_catatan'] ?? null,
                        'catatan_revisi' => trim($catatanRevisi),
                    ]);

                    Log::info('Pengajuan disetujui oleh Direktur Utama', [
                        'id_pengajuan' => $record->id_pengajuan,
                        'status' => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                        'approved_by' => Auth::id(),
                    ]);

                    Notification::make()->title('Pengajuan disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA),

            Action::make('reject')
                ->label('Tolak')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([
                    Hidden::make('direktur_utama_decision_type')
                        ->default('Ditolak'),
                    TextInput::make('display_decision_type')
                        ->label('Jenis Keputusan')
                        ->default('Ditolak')
                        ->disabled(),
                    Textarea::make('direktur_utama_catatan')
                        ->label('Catatan (Wajib)')
                        ->required(),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $catatanTambahan = "\n\n[Ditolak oleh Direktur Utama: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                    $catatanRevisi = ($record->catatan_revisi ?? '') . $catatanTambahan;

                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                        'direktur_utama_approved_by' => Auth::id(),
                        'direktur_utama_decision_type' => $data['direktur_utama_decision_type'] ?? 'Ditolak',
                        'direktur_utama_catatan' => $data['direktur_utama_catatan'],
                        'catatan_revisi' => trim($catatanRevisi),
                    ]);

                    Log::info('Pengajuan ditolak oleh Direktur Utama', [
                        'id_pengajuan' => $record->id_pengajuan,
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                        'approved_by' => Auth::id(),
                    ]);

                    Notification::make()->title('Pengajuan ditolak')->danger()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA),
        ];
    }
}
