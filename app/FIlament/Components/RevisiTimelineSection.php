<?php

namespace App\Filament\Components;

use App\Models\Pengajuan;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\HtmlString;

class RevisiTimelineSection
{
    public static function make(): Section
    {
        return Section::make('Detail Revisi & Review Budget Ulang')
            ->schema([
                // Timeline: Revisi Harga oleh GA
                Section::make('Revisi Harga oleh GA')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('revisi_harga_final')
                                ->label('Harga Setelah Revisi')
                                ->prefix('Rp')
                                ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '')
                                ->disabled(),
                            TextInput::make('revisi_pajak_final')
                                ->label('Nominal Pajak Revisi')
                                ->prefix('Rp')
                                ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '')
                                ->disabled()
                                ->visible(fn($get) => !is_null($get('revisi_pajak_final'))),
                            TextInput::make('revisi_oleh_user')
                                ->label('Direvisi Oleh')
                                ->disabled(),
                        ]),
                        Grid::make(2)->schema([
                            Textarea::make('revisi_alasan_final')
                                ->label('Alasan Revisi')
                                ->disabled(),
                            TextInput::make('revisi_tanggal_final')
                                ->label('Tanggal Revisi')
                                ->disabled()
                                ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('d F Y') : ''),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn($get, $record) => $record instanceof Pengajuan && !is_null($get('revisi_harga_final')) && in_array($record->status, [
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
                        Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI,
                        Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA_REVISI,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA_REVISI,
                        Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                        Pengajuan::STATUS_SUDAH_BAYAR,
                        Pengajuan::STATUS_SELESAI,
                        Pengajuan::STATUS_DITOLAK_KADIV_GA,
                    ])),

                // Timeline: Review Budget Revisi
                Section::make('Review Budget Revisi')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('revisi_budget_status_pengadaan')
                                ->label('Status Budget Revisi')
                                ->disabled(),
                            Textarea::make('revisi_budget_catatan_pengadaan')
                                ->label('Catatan Budget Revisi')
                                ->disabled(),
                            TextInput::make('revisi_budget_approver_name')
                                ->label('Budget Revisi Direview Oleh')
                                ->disabled(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn($get, $record) => $record instanceof Pengajuan && !is_null($get('revisi_budget_status_pengadaan')) && in_array($record->status, [
                        Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI,
                        Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                        Pengajuan::STATUS_SUDAH_BAYAR,
                        Pengajuan::STATUS_SELESAI,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA_REVISI,
                    ])),

                // Bagian: Validasi Budget Revisi oleh Kadiv Ops
                Section::make('Validasi Budget Revisi oleh Kadiv Ops')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('revisi_budget_validated_by')
                                ->label('Divalidasi Oleh')
                                ->disabled()
                                ->default('Tidak Diketahui'),
                            TextInput::make('revisi_budget_validated_at')
                                ->label('Tanggal Validasi')
                                ->disabled()
                                ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('d F Y') : 'Belum Divalidasi'),
                        ])
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn($get, $record) => $record instanceof Pengajuan && !is_null($get('revisi_budget_validated_by')) && !is_null($get('revisi_budget_validated_at')) && in_array($record->status, [
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI,
                        Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA_REVISI,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
                        Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA_REVISI,
                        Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                        Pengajuan::STATUS_SUDAH_BAYAR,
                        Pengajuan::STATUS_SELESAI,
                        Pengajuan::STATUS_DITOLAK_KADIV_GA,
                    ])),

                // Bagian: Hasil Keputusan Kadiv GA atas Revisi
                Section::make('Hasil Keputusan Kadiv GA atas Revisi')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('revisi_kadiv_ga_decision')
                                ->label('Keputusan Final Revisi')
                                ->disabled(),
                            Textarea::make('revisi_kadiv_ga_catatan')
                                ->label('Catatan Keputusan Revisi')
                                ->disabled(),
                            TextInput::make('revisi_kadiv_ga_approver_name')
                                ->label('Keputusan Dibuat Oleh')
                                ->disabled(),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn($get) => !empty($get('revisi_kadiv_ga_decision'))),
            ])
            ->collapsible()
            ->collapsed()
            ->visible(fn(Pengajuan $record) => in_array($record->status, [
                Pengajuan::STATUS_MENUNGGU_APPROVAL_BUDGET_REVISI,
                Pengajuan::STATUS_MENUNGGU_VALIDASI_BUDGET_REVISI_KADIV_OPS,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_KADIV_GA_REVISI,
                Pengajuan::STATUS_MENUNGGU_PENCARIAN_DANA_REVISI,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_OPERASIONAL_REVISI,
                Pengajuan::STATUS_MENUNGGU_APPROVAL_DIREKTUR_UTAMA_REVISI,
                Pengajuan::STATUS_MENUNGGU_PELUNASAN,
                Pengajuan::STATUS_SUDAH_BAYAR,
                Pengajuan::STATUS_SELESAI,
                Pengajuan::STATUS_DITOLAK_KADIV_GA,
            ]));
    }
}
