<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\Pengajuan;
use App\Models\RevisiHarga;
use App\Models\SurveiHarga;
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
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Components\RevisiTimelineSection;

class PersetujuanDirekturOperasional extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static string $view = 'filament.pages.persetujuan-direktur-operasional';
    protected static ?string $navigationLabel = 'Persetujuan Direktur Operasional';
    protected static ?int $navigationSort = 10;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Approval Direktur Operasional)';
    }

    public static function canAccess(): bool
    {
        return Auth::user() && Auth::user()->hasAnyRole(['Direktur Operasional', 'Super Admin']);
    }

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon', 'pemohon.divisi']);

        if ($user && !$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user) {
                $q->whereIn('status', [
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                    Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
                ])->orWhere('direktur_operasional_approved_by', $user->id_user);
            });
        } else {
            $query->whereIn('status', [
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
            ])->orWhereNotNull('direktur_operasional_approved_by');
        }

        return $query->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
            TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
            TextColumn::make('pemohon.divisi.nama_divisi')->label('Divisi'),
            TextColumn::make('total_nilai')
                ->label('Total Nilai')
                ->state(function (Pengajuan $record): ?float {
                    $revisi = $record->items->flatMap->surveiHargas->where('is_final', true)->first()?->revisiHargas?->first();
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
                    if (!$record->direktur_operasional_approved_by) {
                        return 'Menunggu Aksi';
                    }
                    return $record->direktur_operasional_decision_type ?? ($record->status === Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL ? 'Ditolak' : 'Disetujui');
                })
                ->color(fn(string $state): string => match ($state) {
                    'Disetujui' => 'success',
                    'Ditolak' => 'danger',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading('Detail Pengajuan dan Review Budget')
                ->modalWidth('4xl')
                ->mountUsing(function (Form $form, Pengajuan $record) {
                    $record->load([
                        'items.surveiHargas.revisiHargas.direvisiOleh',
                        'items.surveiHargas.revisiHargas.revisiBudgetApprover',
                        'items.surveiHargas.revisiHargas.revisiKadivGaApprover',
                        'items.surveiHargas.revisiHargas.revisiDirekturOperasionalApprover',
                        'items.surveiHargas.revisiHargas.revisiDirekturUtamaApprover',
                        'items.vendorFinal',
                        'pemohon.divisi',
                        'approverBudget',
                        'validatorBudgetOps',
                        'approverKadivGa',
                        'approverDirOps',
                        'approverDirUtama'
                    ]);

                    $formData = $record->toArray();

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
                    $formData['pengadaan_details'] = $getScenarioDetails($record->items, 'Pengadaan');
                    $formData['perbaikan_details'] = $getScenarioDetails($record->items, 'Perbaikan');

                    $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    $revisi = $finalSurvey ? $finalSurvey->revisiHargas->first() : null;
                    if ($revisi) {
                        $formData['revisi_harga_final'] = $revisi->harga_revisi;
                        $formData['revisi_pajak_final'] = $revisi->nominal_pajak;
                        $formData['revisi_oleh_user'] = $revisi->direvisiOleh->nama_user ?? 'Tidak Diketahui';
                        $formData['revisi_alasan_final'] = $revisi->alasan_revisi;
                        $formData['revisi_tanggal_final'] = $revisi->tanggal_revisi;
                        $formData['revisi_budget_status_pengadaan'] = $revisi->revisi_budget_status_pengadaan;
                        $formData['revisi_budget_catatan_pengadaan'] = $revisi->revisi_budget_catatan_pengadaan;
                        $formData['revisi_budget_approver_name'] = $revisi->revisiBudgetApprover->nama_user ?? 'Tidak Diketahui';
                        $formData['revisi_kadiv_ga_decision'] = $revisi->revisi_kadiv_ga_decision_type;
                        $formData['revisi_kadiv_ga_catatan'] = $revisi->revisi_kadiv_ga_catatan;
                        $formData['revisi_kadiv_ga_approver_name'] = $revisi->revisiKadivGaApprover ? $revisi->revisiKadivGaApprover->nama_user : 'Tidak Diketahui';
                        $formData['revisi_budget_validated_by'] = $revisi->revisiBudgetValidator ? $revisi->revisiBudgetValidator->nama_user : null;
                        $formData['revisi_budget_validated_at'] = $revisi->revisi_budget_validated_at ? \Carbon\Carbon::parse($revisi->revisi_budget_validated_at)->format('Y-m-d') : null;
                        $formData['revisi_direktur_operasional_decision'] = $revisi->revisi_direktur_operasional_decision_type;
                        $formData['revisi_direktur_operasional_catatan'] = $revisi->revisi_direktur_operasional_catatan;
                        $formData['revisi_direktur_operasional_approver_name'] = $revisi->revisiDirekturOperasionalApprover ? $revisi->revisiDirekturOperasionalApprover->nama_user : 'Tidak Diketahui';
                        $formData['revisi_direktur_utama_decision'] = $revisi->revisi_direktur_utama_decision_type;
                        $formData['revisi_direktur_utama_catatan'] = $revisi->revisi_direktur_utama_catatan;
                        $formData['revisi_direktur_utama_approver_name'] = $revisi->revisiDirekturUtamaApprover ? $revisi->revisiDirekturUtamaApprover->nama_user : 'Tidak Diketahui';
                    } else {
                        Log::warning('No revision data found for pengajuan ID: ' . $record->id_pengajuan);
                    }

                    $formData['items_with_final_vendor'] = $record->items->filter(fn($item) => $item->vendorFinal)->map(fn($item) => [
                        'nama_barang' => $item->nama_barang,
                        'nama_vendor' => $item->vendorFinal->nama_vendor,
                        'harga' => $item->vendorFinal->harga,
                        'metode_pembayaran' => $item->vendorFinal->metode_pembayaran,
                        'opsi_pembayaran' => $item->vendorFinal->opsi_pembayaran,
                    ])->values()->toArray();

                    $form->fill($formData);
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
                        ])->columns(1)->disabled(),
                        Grid::make(2)->schema([
                            TextInput::make('rekomendasi_it_tipe')->label('Rekomendasi Tipe dari IT')->disabled(),
                            Textarea::make('rekomendasi_it_catatan')->label('Rekomendasi Catatan dari IT')->disabled(),
                        ])->visible(fn($get) => !empty($get('rekomendasi_it_tipe'))),
                        Textarea::make('catatan_revisi')->label('Catatan Approval Sebelumnya')->disabled(),
                    ])->collapsible()->collapsed(),

                    Section::make('Hasil Survei Harga')->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Grid::make(1)->schema([
                                    Repeater::make('surveiHargas')
                                        ->label('Detail Harga Pembanding')
                                        ->relationship()
                                        ->schema([
                                            Grid::make(4)->schema([
                                                TextInput::make('tipe_survei')->disabled(),
                                                TextInput::make('nama_vendor')->label('Vendor/Link')->disabled(),
                                                TextInput::make('harga')->prefix('Rp')->disabled(),
                                                TextInput::make('is_final')
                                                    ->label('Vendor Final')
                                                    ->disabled()
                                                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak'),
                                            ]),
                                        ])->disabled()->columns(1),
                                ]),
                            ])->columnSpanFull()->disabled(),
                    ])->collapsed(),

                    Section::make('Rincian Estimasi Biaya - Skenario PENGADAAN')
                        ->schema([
                            Repeater::make('pengadaan_details.details')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('nama_barang')->label('Item')->disabled(),
                                        TextInput::make('harga_vendor')->label('Harga dari Vendor')->disabled(),
                                        TextInput::make('pajak_info')->label('Pajak Ditanggung Perusahaan')->disabled(),
                                    ])
                                ])->disabled()->disableItemCreation()->disableItemDeletion()->disableItemMovement(),
                            Placeholder::make('pengadaan_details.total')
                                ->label('TOTAL ESTIMASI BIAYA PENGADAAN')
                                ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . ($get('pengadaan_details.total') ?? 'Rp 0') . '</b>')),
                        ])->collapsible()->collapsed()
                        ->visible(fn($get) => !is_null($get('pengadaan_details')) && !empty($get('pengadaan_details')['details'])),

                    Section::make('Rincian Estimasi Biaya - Skenario PERBAIKAN')
                        ->schema([
                            Repeater::make('perbaikan_details.details')
                                ->label('')
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('nama_barang')->label('Item')->disabled(),
                                        TextInput::make('harga_vendor')->label('Harga dari Vendor')->disabled(),
                                        TextInput::make('pajak_info')->label('Pajak Ditanggung Perusahaan')->disabled(),
                                    ])
                                ])->disabled()->disableItemCreation()->disableItemDeletion()->disableItemMovement(),
                            Placeholder::make('perbaikan_details.total')
                                ->label('TOTAL ESTIMASI BIAYA PERBAIKAN')
                                ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . ($get('perbaikan_details.total') ?? 'Rp 0') . '</b>')),
                        ])
                        ->visible(fn($get) => !is_null($get('perbaikan_details')) && !empty($get('perbaikan_details')['details']))
                        ->collapsible()->collapsed(),

                    Section::make('Review Budgeting')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->disabled(),
                                Textarea::make('budget_catatan_pengadaan')->label('Catatan Budget Pengadaan')->disabled(),
                            ]),
                            Grid::make(2)->schema([
                                TextInput::make('budget_status_perbaikan')->label('Status Budget Perbaikan')->disabled(),
                                Textarea::make('budget_catatan_perbaikan')->label('Catatan Budget Perbaikan')->disabled(),
                            ])->visible(fn($get) => !is_null($get('budget_status_perbaikan'))),
                        ])
                        ->visible(fn(Pengajuan $record) => in_array($record->status, [
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                        ]))->collapsible()->collapsed(),

                    Section::make('Hasil Keputusan atas Revisi')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('revisi_kadiv_ga_decision')
                                    ->label('Keputusan Kadiv GA atas Revisi')
                                    ->disabled()
                                    ->default(fn($get) => $get('revisi_kadiv_ga_decision') ?? 'Tidak Diketahui'),
                                Textarea::make('revisi_kadiv_ga_catatan')
                                    ->label('Catatan Kadiv GA atas Revisi')
                                    ->disabled()
                                    ->default(fn($get) => $get('revisi_kadiv_ga_catatan') ?? 'Tidak ada catatan'),
                                TextInput::make('revisi_kadiv_ga_approver_name')
                                    ->label('Disetujui oleh Kadiv GA')
                                    ->disabled()
                                    ->default(fn($get) => $get('revisi_kadiv_ga_approver_name') ?? 'Tidak Diketahui'),
                                TextInput::make('revisi_direktur_operasional_decision')
                                    ->label('Keputusan Direktur Operasional atas Revisi')
                                    ->disabled()
                                    ->default(fn($get) => $get('revisi_direktur_operasional_decision') ?? 'Tidak Diketahui'),
                                Textarea::make('revisi_direktur_operasional_catatan')
                                    ->label('Catatan Direktur Operasional atas Revisi')
                                    ->disabled()
                                    ->default(fn($get) => $get('revisi_direktur_operasional_catatan') ?? 'Tidak ada catatan'),
                                TextInput::make('revisi_direktur_operasional_approver_name')
                                    ->label('Disetujui oleh Direktur Operasional')
                                    ->disabled()
                                    ->default(fn($get) => $get('revisi_direktur_operasional_approver_name') ?? 'Tidak Diketahui'),
                            ]),
                        ])
                        ->visible(fn($get) => !empty($get('revisi_kadiv_ga_decision')) || !empty($get('revisi_direktur_operasional_decision')))
                        ->collapsible()->collapsed(),

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
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                        ]))
                        ->collapsible()->collapsed(),

                    Section::make('Vendor & Harga Final yang Disetujui')
                        ->schema([
                            Repeater::make('items_with_final_vendor')
                                ->label('')
                                ->schema([
                                    Grid::make(5)->schema([
                                        TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
                                        TextInput::make('nama_vendor')->label('Nama Vendor')->disabled(),
                                        TextInput::make('harga')
                                            ->label('Harga Satuan')
                                            ->prefix('Rp')
                                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                            ->disabled(),
                                        TextInput::make('metode_pembayaran')->label('Metode Bayar')->disabled(),
                                        TextInput::make('opsi_pembayaran')->label('Opsi Bayar')->disabled(),
                                    ]),
                                ])
                                ->disabled()->disableItemCreation()->disableItemDeletion()->disableItemMovement(),
                        ])
                        ->collapsible()->collapsed()
                        ->visible(fn($get) => !empty($get('items_with_final_vendor'))),

                    RevisiTimelineSection::make(),
                ]),

            Action::make('approve')
                ->label('Setujui')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->form([
                    Hidden::make('direktur_operasional_decision_type')
                        ->default('Disetujui'),
                    TextInput::make('display_decision_type')
                        ->label('Jenis Keputusan')
                        ->default('Disetujui')
                        ->disabled(),
                    Textarea::make('direktur_operasional_catatan')
                        ->label('Catatan (Opsional)')
                        ->required(false),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $catatanTambahan = "\n\n[Disetujui oleh Direktur Operasional: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                    $catatanRevisi = ($record->catatan_revisi ?? '') . $catatanTambahan;

                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                        'direktur_operasional_approved_by' => Auth::id(),
                        'direktur_operasional_decision_type' => $data['direktur_operasional_decision_type'] ?? 'Disetujui',
                        'direktur_operasional_catatan' => $data['direktur_operasional_catatan'] ?? null,
                        'catatan_revisi' => trim($catatanRevisi),
                    ]);

                    Log::info('Pengajuan disetujui oleh Direktur Operasional', [
                        'id_pengajuan' => $record->id_pengajuan,
                        'status' => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                        'approved_by' => Auth::id(),
                    ]);

                    Notification::make()->title('Pengajuan disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL),

            Action::make('reject')
                ->label('Tolak')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([
                    Hidden::make('direktur_operasional_decision_type')
                        ->default('Ditolak'),
                    TextInput::make('display_decision_type')
                        ->label('Jenis Keputusan')
                        ->default('Ditolak')
                        ->disabled(),
                    Textarea::make('direktur_operasional_catatan')
                        ->label('Catatan (Wajib)')
                        ->required(),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $catatanTambahan = "\n\n[Ditolak oleh Direktur Operasional: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                    $catatanRevisi = ($record->catatan_revisi ?? '') . $catatanTambahan;

                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                        'direktur_operasional_approved_by' => Auth::id(),
                        'direktur_operasional_decision_type' => $data['direktur_operasional_decision_type'] ?? 'Ditolak',
                        'direktur_operasional_catatan' => $data['direktur_operasional_catatan'],
                        'catatan_revisi' => trim($catatanRevisi),
                    ]);

                    Log::info('Pengajuan ditolak oleh Direktur Operasional', [
                        'id_pengajuan' => $record->id_pengajuan,
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                        'approved_by' => Auth::id(),
                    ]);

                    Notification::make()->title('Pengajuan ditolak')->danger()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL),

            Action::make('approve_revisi')
                ->label('Setujui Revisi')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->form([
                    Hidden::make('revisi_direktur_operasional_decision_type')
                        ->default('Disetujui'),
                    TextInput::make('display_revisi_status')
                        ->label('Jenis Keputusan Revisi')
                        ->default('Disetujui')
                        ->disabled(),
                    Textarea::make('revisi_direktur_operasional_catatan')
                        ->label('Catatan Revisi (Opsional)')
                        ->required(false),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    if (!$finalSurvey) {
                        Notification::make()->title('Error')->body('Survei final tidak ditemukan.')->danger()->send();
                        return;
                    }
                    $revisi = $finalSurvey->revisiHargas()->first();
                    if (!$revisi) {
                        Notification::make()->title('Error')->body('Data revisi tidak ditemukan.')->danger()->send();
                        return;
                    }

                    $revisi->update([
                        'revisi_direktur_operasional_decision_type' => $data['revisi_direktur_operasional_decision_type'] ?? 'Disetujui',
                        'revisi_direktur_operasional_catatan' => $data['revisi_direktur_operasional_catatan'] ?? null,
                        'revisi_direktur_operasional_approved_by' => Auth::id(),
                        'revisi_budget_validated_by' => Auth::id(),
                        'revisi_budget_validated_at' => now(),
                    ]);

                    $catatanTambahan = "\n\n[Revisi disetujui oleh Direktur Operasional: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                    $catatanRevisi = ($record->catatan_revisi ?? '') . $catatanTambahan;

                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                        'catatan_revisi' => trim($catatanRevisi),
                    ]);

                    Log::info('Revisi pengajuan disetujui oleh Direktur Operasional', [
                        'id_pengajuan' => $record->id_pengajuan,
                        'revisi_id' => $revisi->id,
                        'status' => Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                        'approved_by' => Auth::id(),
                    ]);

                    Notification::make()->title('Revisi pengajuan disetujui')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => in_array($record->status, [
                    Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
                ])),

            Action::make('reject_revisi')
                ->label('Tolak Revisi')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->form([
                    Hidden::make('revisi_direktur_operasional_decision_type')
                        ->default('Ditolak'),
                    TextInput::make('display_revisi_status')
                        ->label('Jenis Keputusan Revisi')
                        ->default('Ditolak')
                        ->disabled(),
                    Textarea::make('revisi_direktur_operasional_catatan')
                        ->label('Catatan Revisi (Wajib)')
                        ->required(),
                ])
                ->action(function (array $data, Pengajuan $record) {
                    $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    if (!$finalSurvey) {
                        Notification::make()->title('Error')->body('Survei final tidak ditemukan.')->danger()->send();
                        return;
                    }
                    $revisi = $finalSurvey->revisiHargas()->first();
                    if (!$revisi) {
                        Notification::make()->title('Error')->body('Data revisi tidak ditemukan.')->danger()->send();
                        return;
                    }

                    $revisi->update([
                        'revisi_direktur_operasional_decision_type' => $data['revisi_direktur_operasional_decision_type'] ?? 'Ditolak',
                        'revisi_direktur_operasional_catatan' => $data['revisi_direktur_operasional_catatan'],
                        'revisi_direktur_operasional_approved_by' => Auth::id(),
                        'revisi_budget_validated_by' => Auth::id(),
                        'revisi_budget_validated_at' => now(),
                    ]);

                    $catatanTambahan = "\n\n[Revisi ditolak oleh Direktur Operasional: " . Auth::user()->nama_user . " pada " . now()->format('d-m-Y H:i') . "]";
                    $catatanRevisi = ($record->catatan_revisi ?? '') . $catatanTambahan;

                    $record->update([
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                        'catatan_revisi' => trim($catatanRevisi),
                    ]);

                    Log::info('Revisi pengajuan ditolak oleh Direktur Operasional', [
                        'id_pengajuan' => $record->id_pengajuan,
                        'revisi_id' => $revisi->id,
                        'status' => Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                        'approved_by' => Auth::id(),
                    ]);

                    Notification::make()->title('Revisi pengajuan ditolak')->danger()->send();
                })
                ->visible(fn(Pengajuan $record) => in_array($record->status, [
                    Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                    Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
                ])),
        ];
    }
}
