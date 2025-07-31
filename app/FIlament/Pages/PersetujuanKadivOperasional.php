<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
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
use App\Filament\Components\RevisiTimelineSection;

class PersetujuanKadivOperasional extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static string $view = 'filament.pages.persetujuan-kadiv-operasional';
    protected static ?string $navigationLabel = 'Validasi Budget (Kadiv Ops)';
    protected static ?int $navigationSort = 8;

    public function getTitle(): string
    {
        return 'Daftar Pengajuan (Validasi Budget Kadiv Operasional)';
    }

    public static function canAccess(): bool
    {
        return Auth::user() && Auth::user()->hasAnyRole(['Kepala Divisi Operasional', 'Super Admin']);
    }


    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Pengajuan::query()->with(['pemohon.divisi', 'items.surveiHargas.revisiHargas']);

        $statuses = [
            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
            Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
        ];

        if (!$user->hasRole('Super Admin')) {
            $query->where(function (Builder $q) use ($user, $statuses) {
                $q->whereIn('status', $statuses)
                    ->orWhere('kadiv_ops_budget_approved_by', $user->id_user);
            });
        } else {
            $statusesAfter = [
                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
            ];
            $query->whereIn('status', array_merge($statuses, $statusesAfter))
                ->orWhereNotNull('kadiv_ops_budget_approved_by');
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
                ->label('Keterangan')
                ->state(function (Pengajuan $record): string {
                    if (in_array($record->status, [
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET,
                        Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                    ])) {
                        return 'Menunggu Validasi Anda';
                    }
                    return 'Sudah Divalidasi';
                })
                ->color(fn(string $state): string => match ($state) {
                    'Sudah Divalidasi' => 'success',
                    'Menunggu Validasi Anda' => 'warning',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            ViewAction::make()->label('Detail')
                ->modalHeading('Detail Pengajuan')
                ->modalWidth('5xl')
                ->mountUsing(function (Form $form, Pengajuan $record) {
                    // Eager load all required relations
                    $record->load([
                        'items.surveiHargas.revisiHargas.revisiBudgetApprover',
                        'items.surveiHargas.revisiHargas.revisiKadivGaApprover',
                        'items.surveiHargas.revisiHargas.revisiBudgetValidator',
                        'items.vendorFinal',
                        'pemohon.divisi',
                        'approverBudget',
                        'validatorBudgetOps',
                        'approverKadivGa',
                        'approverDirOps',
                        'approverDirUtama'
                    ]);
                    $formData = $record->toArray();

                    // --- LOGIC FOR COST DETAILS ---
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

                    // --- FIX FOR REVISION DATA ---
                    $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    $revisi = $finalSurvey ? $finalSurvey->revisiHargas->first() : null;
                    \Illuminate\Support\Facades\Log::info('Revisi Data (Kadiv Ops):', [
                        'pengajuan_id' => $record->id_pengajuan,
                        'finalSurvey' => $finalSurvey ? $finalSurvey->toArray() : null,
                        'revisi' => $revisi ? $revisi->toArray() : null
                    ]);

                    if ($revisi) {
                        // Map revision data to keys expected by RevisiTimelineSection
                        $formData['revisi_harga_final'] = $revisi->harga_revisi;
                        $formData['revisi_pajak_final'] = $revisi->nominal_pajak;
                        $formData['revisi_oleh_user'] = $revisi->direvisiOleh ? $revisi->direvisiOleh->nama_user : 'Tidak Diketahui';
                        $formData['revisi_alasan_final'] = $revisi->alasan_revisi;
                        $formData['revisi_tanggal_final'] = $revisi->tanggal_revisi ? Carbon::parse($revisi->tanggal_revisi)->format('Y-m-d') : null;
                        $formData['revisi_budget_status_pengadaan'] = $revisi->revisi_budget_status_pengadaan;
                        $formData['revisi_budget_catatan_pengadaan'] = $revisi->revisi_budget_catatan_pengadaan;
                        $formData['revisi_budget_approver_name'] = $revisi->revisiBudgetApprover ? $revisi->revisiBudgetApprover->nama_user : 'Tidak Diketahui';
                        $formData['revisi_kadiv_ga_decision'] = $revisi->revisi_kadiv_ga_decision_type;
                        $formData['revisi_kadiv_ga_catatan'] = $revisi->revisi_kadiv_ga_catatan;
                        $formData['revisi_kadiv_ga_approver_name'] = $revisi->revisiKadivGaApprover ? $revisi->revisiKadivGaApprover->nama_user : 'Tidak Diketahui';
                        // Validation data using new columns
                        $formData['revisi_budget_validated_by'] = $revisi->revisiBudgetValidator ? $revisi->revisiBudgetValidator->nama_user : null;
                        $formData['revisi_budget_validated_at'] = $revisi->revisi_budget_validated_at ? Carbon::parse($revisi->revisi_budget_validated_at)->format('Y-m-d') : null;
                        $formData['catatan_validasi'] = $revisi->catatan_validasi;
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Tidak ada data revisi untuk pengajuan ID: ' . $record->id_pengajuan);
                    }

                    $formData['items_with_final_vendor'] = $record->items->filter(fn($item) => $item->vendorFinal)
                        ->map(fn($item) => [
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

                    // --- SECTION BARU: HASIL SURVEI HARGA ---
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
                        ])->collapsible()->collapsed(),

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

                    Section::make('Review Budget')->schema([
                        Grid::make(3)->schema([
                            TextInput::make('budget_status_pengadaan')->label('Status Budget Pengadaan')->disabled(),
                            Textarea::make('budget_catatan_pengadaan')->label('Catatan Budget Pengadaan')->disabled(),
                            TextInput::make('approverBudget.nama_user')->label('Direview Oleh')->disabled(),
                        ]),
                    ])->visible(fn($get) => !empty($get('budget_status_pengadaan')))->collapsible()->collapsed(),


                    // FINAL APPROVLE SECTION
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
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_DITOLAK_KADIV_GA,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                            Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA,
                            Pengajuan::STATUS_SUDAH_BAYAR,
                            Pengajuan::STATUS_SELESAI,
                            Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
                            Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                            Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI,
                        ]))
                        ->collapsible()->collapsed(),

                    Section::make('Vendor & Harga Final yang Disetujui')->schema([
                        Repeater::make('items_with_final_vendor')->label('')->schema([
                            Grid::make(5)->schema([
                                TextInput::make('nama_barang')->label('Nama Barang')->disabled(),
                                TextInput::make('nama_vendor')->label('Nama Vendor')->disabled(),
                                TextInput::make('harga')->label('Harga Satuan')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->disabled(),
                                TextInput::make('metode_pembayaran')->label('Metode Bayar')->disabled(),
                                TextInput::make('opsi_pembayaran')->label('Opsi Bayar')->disabled(),
                            ]),
                        ])->disabled()->disableItemCreation()->disableItemDeletion()->disableItemMovement(),
                    ])->collapsible()->collapsed()->visible(fn($get) => !empty($get('items_with_final_vendor'))),

                    RevisiTimelineSection::make(),
                ]),

            Action::make('confirm_budget')
                ->label('Konfirmasi Budget Awal')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Budget Awal')
                ->modalDescription('Apakah Anda yakin budget awal dari tim Budgeting sudah sesuai?')
                ->action(function (Pengajuan $record) {
                    \Illuminate\Support\Facades\Log::info('Confirm Budget Awal (Kadiv Ops) Attempt:', [
                        'pengajuan_id' => $record->id_pengajuan,
                        'current_status' => $record->status,
                    ]);

                    $record->update([
                        'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        'kadiv_ops_budget_approved_by' => Auth::id(),
                        'updated_at' => now(),
                    ]);

                    \Illuminate\Support\Facades\Log::info('Budget Awal Confirmed:', [
                        'pengajuan_id' => $record->id_pengajuan,
                        'new_status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA,
                        'kadiv_ops_budget_approved_by' => Auth::id(),
                    ]);

                    Notification::make()->title('Budget awal telah dikonfirmasi.')->success()->send();
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_OPERASIONAL_BUDGET),

            Action::make('confirm_budget_revisi')
                ->label('Konfirmasi Budget Revisi')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Budget Revisi')
                ->modalDescription('Apakah Anda yakin budget revisi dari tim Budgeting sudah sesuai?')
                ->action(function (Pengajuan $record) {
                    $finalSurvey = $record->items->flatMap->surveiHargas->where('is_final', true)->first();
                    $revisi = $finalSurvey ? $finalSurvey->revisiHargas->first() : null;

                    \Illuminate\Support\Facades\Log::info('Confirm Budget Revisi (Kadiv Ops) Attempt:', [
                        'pengajuan_id' => $record->id_pengajuan,
                        'final_survey_id' => $finalSurvey ? $finalSurvey->id : null,
                        'revisi_id' => $revisi ? $revisi->id : null,
                        'current_status' => $record->status,
                    ]);

                    if ($finalSurvey) {
                        if ($revisi) {
                            $revisi->update([
                                'revisi_budget_validated_by' => Auth::id(),
                                'revisi_budget_validated_at' => now(),
                            ]);
                            \Illuminate\Support\Facades\Log::info('Revisi Harga Updated:', [
                                'revisi_id' => $revisi->id,
                                'revisi_budget_validated_by' => Auth::id(),
                                'revisi_budget_validated_at' => now(),
                            ]);
                        } else {
                            $revisi = $finalSurvey->revisiHargas()->create([
                                'survei_harga_id' => $finalSurvey->id,
                                'harga_revisi' => $finalSurvey->harga,
                                'nominal_pajak' => $finalSurvey->nominal_pajak,
                                'kondisi_pajak' => $finalSurvey->kondisi_pajak,
                                'jenis_pajak' => $finalSurvey->jenis_pajak,
                                'npwp_nik' => $finalSurvey->npwp_nik,
                                'nama_pemilik_pajak' => $finalSurvey->nama_pemilik_pajak,
                                'alasan_revisi' => 'Validasi Kadiv Ops tanpa revisi harga sebelumnya',
                                'direvisi_oleh' => Auth::id(),
                                'tanggal_revisi' => now(),
                                'revisi_budget_validated_by' => Auth::id(),
                                'revisi_budget_validated_at' => now(),
                            ]);
                            \Illuminate\Support\Facades\Log::info('Revisi Harga Created:', [
                                'revisi_id' => $revisi->id,
                                'revisi_budget_validated_by' => Auth::id(),
                                'revisi_budget_validated_at' => now(),
                            ]);
                        }

                        $record->update([
                            'status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI,
                            'kadiv_ops_budget_approved_by' => Auth::id(),
                            'updated_at' => now(),
                        ]);

                        \Illuminate\Support\Facades\Log::info('Budget Revisi Confirmed:', [
                            'pengajuan_id' => $record->id_pengajuan,
                            'new_status' => Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI,
                            'kadiv_ops_budget_approved_by' => Auth::id(),
                        ]);

                        Notification::make()->title('Budget revisi telah dikonfirmasi.')->success()->send();
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Tidak ada survei harga final untuk pengajuan ID: ' . $record->id_pengajuan);
                        Notification::make()->title('Gagal menyimpan data validasi: Tidak ada survei harga final ditemukan.')->danger()->send();
                    }
                })
                ->visible(fn(Pengajuan $record) => $record->status === Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS),
        ];
    }
}
