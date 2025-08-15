<?php

namespace App\Filament\Components;

use Carbon\Carbon;
use App\Models\Pengajuan;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;

class RevisiTimelineSection
{
    public static function make(): Section
    {
        return Section::make('Detail Proses Revisi Harga')
            ->schema([
                // =================================================================
                // SECTION 1: Revisi Harga oleh GA (DENGAN PENAMBAHAN FIELD)
                // =================================================================
                Section::make('Revisi Harga oleh GA')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('revisi_per_vendor.0.harga_awal')->label('Total Harga Awal')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->disabled(),
                            TextInput::make('revisi_per_vendor.0.harga_revisi')->label('Harga Barang Revisi')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->disabled(),
                            TextInput::make('revisi_per_vendor.0.selisih_harga')->label('Selisih Harga')->prefix('Rp')->formatStateUsing(fn($state) => number_format(abs($state), 0, ',', '.') . ($state >= 0 ? ' (Kenaikan)' : ' (Pengurangan)'))->disabled(),
                            TextInput::make('revisi_per_vendor.0.nominal_pajak')->label('Nominal Pajak')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->disabled(),

                            // FIELD BARU: Nominal DP
                            TextInput::make('revisi_per_vendor.0.nominal_dp')
                                ->label('Nominal sudah DP')
                                ->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                                ->disabled(),
                            TextInput::make('revisi_per_vendor.0.revisi_tanggal')->label('Tanggal Revisi')->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('d F Y H:i'))->disabled(),
                        ]),
                        Grid::make(2)->schema([
                            Textarea::make('revisi_per_vendor.0.alasan_revisi')
                                ->label('Alasan Revisi')
                                ->disabled(),

                            Placeholder::make('revisi_per_vendor.0.total_setelah_revisi')
                                ->label('TOTAL ESTIMASI BIAYA SETELAH REVISI')
                                ->content(fn($get) => new HtmlString(
                                    '<b class="text-xl text-primary-600">Rp ' . number_format($get('revisi_per_vendor.0.total_setelah_revisi'), 0, ',', '.') . '</b>'
                                )),
                        ])

                    ])
                    ->collapsible()
                    ->collapsed(),

                // =================================================================
                // SECTION 2: Review & Validasi Budget (Revisi)
                // =================================================================
                Section::make('Review & Validasi Budget (Revisi)')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('revisi_budget_status_pengadaan')->label('Status Budget Revisi')->disabled(),
                            Textarea::make('revisi_budget_catatan_pengadaan')->label('Catatan Budget Revisi')->disabled(),
                            TextInput::make('revisi_budget_approver_name')->label('Direview Oleh')->disabled(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('revisi_budget_validated_by')->label('Divalidasi Oleh Kadiv Ops')->disabled(),
                            TextInput::make('revisi_budget_validated_at')->label('Tanggal Validasi')->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('d F Y H:i'))->disabled(),
                        ])->visible(fn($get) => !empty($get('revisi_budget_validated_by'))), // Hanya tampil jika sudah divalidasi
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn($get) => !empty($get('revisi_budget_status_pengadaan'))),

                // =================================================================
                // SECTION 3: Persetujuan Final (Revisi)
                // =================================================================
                Section::make('Persetujuan Final (Revisi)')
                    ->schema([
                        // Grid untuk Keputusan Kadiv GA
                        Grid::make(3)->schema([
                            TextInput::make('revisi_kadiv_ga_decision_type')->label('Keputusan Kadiv GA')->disabled(),
                            Textarea::make('revisi_kadiv_ga_catatan')->label('Catatan Revisi')->disabled(),
                            TextInput::make('revisi_kadiv_ga_approver_name')->label('Diberikan Oleh')->disabled(),
                        ]),

                        // Grid untuk Keputusan Direktur Operasional
                        Grid::make(3)->schema([
                            TextInput::make('revisi_direktur_operasional_decision_type')->label('Keputusan Direktur Operasional')->disabled(),
                            Textarea::make('revisi_direktur_operasional_catatan')->label('Catatan Revisi')->disabled(),
                            TextInput::make('revisi_direktur_operasional_approver_name')->label('Diberikan Oleh')->disabled(),
                        ])
                            // Tampil HANYA JIKA Direktur Operasional sudah memberi keputusan revisi
                            ->visible(function (Pengajuan $record): bool {
                                $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                                return $latestRevisi && !empty($latestRevisi->revisi_direktur_operasional_decision_type);
                            }),

                        // Grid untuk Keputusan Direktur Utama
                        Grid::make(3)->schema([
                            TextInput::make('revisi_direktur_utama_decision_type')->label('Keputusan Direktur Utama')->disabled(),
                            Textarea::make('revisi_direktur_utama_catatan')->label('Catatan Revisi')->disabled(),
                            TextInput::make('revisi_direktur_utama_approver_name')->label('Diberikan Oleh')->disabled(),
                        ])
                            // Tampil HANYA JIKA Direktur Utama sudah memberi keputusan revisi
                            ->visible(function (Pengajuan $record): bool {
                                $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                                return $latestRevisi && !empty($latestRevisi->revisi_direktur_utama_decision_type);
                            }),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(function (Pengajuan $record): bool {
                        // Cek langsung ke relasi: Apakah revisi terakhir sudah memiliki keputusan dari Kadiv GA?
                        $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                        return $latestRevisi && !empty($latestRevisi->revisi_kadiv_ga_decision_type);
                    }),

            ])
            ->collapsible()
            ->collapsed()
            ->visible(function (Pengajuan $record): bool {
                // Cek langsung ke relasi: Apakah ada data revisi yang tersimpan untuk pengajuan ini?
                return $record->items
                    ->flatMap->surveiHargas
                    ->flatMap->revisiHargas
                    ->isNotEmpty();
            });
    }
}
