<?php

namespace App\Filament\Components;

use App\Models\Pengajuan;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;

class StandardDetailSections
{
    /**
     * Mengembalikan array dari semua section detail standar.
     * @return array
     */
    public static function make(): array
    {
        return [
            Section::make('Detail Pengajuan')->schema([
                Grid::make(3)->schema([
                    TextInput::make('kode_pengajuan')->disabled(),
                    TextInput::make('status')->disabled(),
                    TextInput::make('total_nilai')->label('Total Nilai Final')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->disabled(),
                ]),
                Repeater::make('items')->relationship()->label('Barang yang Diajukan')->schema([
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
                    TextInput::make('rekomendasi_it_tipe')
                        ->label('Rekomendasi Tipe dari IT')
                        ->disabled(),
                    Textarea::make('rekomendasi_it_catatan')
                        ->label('Rekomendasi Catatan dari IT')
                        ->disabled(),
                ])->visible(fn(Pengajuan $record) => !is_null($record->it_recommended_by)),
                Textarea::make('catatan_revisi')
                    ->label('Riwayat Catatan Persetujuan Awal (Manager/Kadiv)')
                    ->disabled()
                    ->visible(fn(Pengajuan $record) => !empty($record->catatan_revisi)),

            ])->collapsible()->collapsed(),

            Section::make('Hasil Survei Harga GA')->schema([
                Repeater::make('items')->relationship()->label('')->schema([
                    Grid::make(1)->schema([
                        Repeater::make('surveiHargas')->label('Detail Harga Pembanding')->relationship()->schema([
                            Grid::make(3)->schema([
                                TextInput::make('tipe_survei')->label('Kategori')->disabled(),
                                TextInput::make('nama_vendor')->label('Vendor/Link')->disabled(),
                                TextInput::make('harga')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->disabled(),
                                TextInput::make('kondisi_pajak')->label('Kondisi Pajak')->disabled()->default('Tidak Ada Pajak'),
                                TextInput::make('nominal_pajak')->label('Nominal Pajak')->prefix('Rp')->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))->disabled()->default(0),
                            ]),
                        ])->disabled()->columns(1),
                    ]),
                ])->columnSpanFull()->disabled(),
            ])
                ->visible(fn(Pengajuan $record) => !is_null($record->ga_surveyed_by))
                ->collapsible()->collapsed(),

            Section::make('Rincian Estimasi Biaya')
                ->schema([
                    Repeater::make('estimasi_biaya.details')
                        ->label('')
                        ->schema([
                            Grid::make(4)->schema([
                                TextInput::make('nama_barang')->label('Item')->disabled(),
                                TextInput::make('tipe_survei')->label('Kategori')->disabled(),
                                TextInput::make('harga_vendor')->label('Harga dari Vendor')->disabled(),
                                TextInput::make('pajak_info')->label('Pajak')->disabled(),
                            ])
                        ])->disabled()->disableItemCreation()->disableItemDeletion()->disableItemMovement(),
                    Placeholder::make('estimasi_biaya_empty')
                        ->label('')
                        ->content(new HtmlString('<p class="text-gray-500">Belum ada data estimasi biaya tersedia.</p>'))
                        ->visible(fn($get) => empty($get('estimasi_biaya.details'))),
                    Grid::make(2)->schema([
                        Placeholder::make('estimasi_biaya.total')
                            ->label('TOTAL ESTIMASI BIAYA')
                            ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . ($get('estimasi_biaya.total') ?? 'Rp 0') . '</b>')),
                        Placeholder::make('estimasi_biaya.nominal_dp')
                            ->label('NOMINAL DP')
                            ->content(fn($get) => new HtmlString('<b class="text-xl text-primary-600">' . ($get('estimasi_biaya.nominal_dp') ?? 'Tidak ada DP') . '</b>')),
                    ])->visible(fn($get) => !empty($get('estimasi_biaya.details'))),
                ])
                ->visible(fn(Pengajuan $record) => !is_null($record->ga_surveyed_by))
                ->collapsible()->collapsed(),

            Section::make('Validasi Kepala Divisi GA')->schema([
                Grid::make(2)->schema([
                    TextInput::make('kadiv_ga_decision_type')->label('Keputusan Validasi')->disabled(),
                    TextInput::make('kadiv_ga_approved_by_name')->label('Divalidasi Oleh')->disabled(),
                ]),
                Textarea::make('kadiv_ga_catatan')->label('Catatan Validasi')->disabled(),
            ])->visible(fn(Pengajuan $record) => !is_null($record->kadiv_ga_approved_by))->collapsible()->collapsed(),

            Section::make('Budget Control')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('status_budget')
                            ->label('Status Budget')
                            ->disabled()
                            ->default(fn($get) => $get('status_budget') ?? 'Belum di-review'),

                        TextInput::make('budget_approved_by_name')
                            ->label('Direview Oleh')
                            ->disabled()
                            ->default(fn($get) => $get('budget_approved_by_name') ?? 'Belum direview'),

                        // TextInput::make('kadiv_ops_budget_approved_by_name')
                        //     ->label('Divalidasi Oleh')
                        //     ->disabled()
                        //     ->default(fn($get) => $get('kadiv_ops_budget_approved_by_name') ?? 'Belum divalidasi'),
                    ]),
                    Textarea::make('catatan_budget')
                        ->label('Catatan Budget')
                        ->disabled()
                        ->default(fn($get) => $get('catatan_budget') ?? 'Tidak ada catatan')
                        ->columnSpanFull(),
                ])->collapsible()->collapsed()
                ->visible(fn(Pengajuan $record) => !is_null($record->budget_approved_by)),

            Section::make('Final Approve')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('kadiv_ops_decision_type')
                            ->label('Keputusan Kadiv Operasional')
                            ->disabled()
                            ->default(fn($get) => $get('kadiv_ops_decision_type') ?? 'Belum Ada Keputusan'),
                        Textarea::make('kadiv_ops_catatan')
                            ->label('Catatan Kadiv Operasional')
                            ->disabled()
                            ->default(fn($get) => $get('kadiv_ops_catatan') ?? 'Tidak ada catatan'),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('direktur_operasional_decision_type')
                            ->label('Keputusan Direktur Operasional')
                            ->disabled()
                            ->default(fn($get) => $get('direktur_operasional_decision_type') ?? 'Belum Ada Keputusan'),
                        Textarea::make('direktur_operasional_catatan')
                            ->label('Catatan Direktur Operasional')
                            ->disabled()
                            ->default(fn($get) => $get('direktur_operasional_catatan') ?? 'Tidak ada catatan'),
                    ])->visible(fn(Pengajuan $record) => !is_null($record->direktur_operasional_approved_by)),
                    Grid::make(2)->schema([
                        TextInput::make('direktur_utama_decision_type')
                            ->label('Keputusan Direktur Utama')
                            ->disabled()
                            ->default(fn($get) => $get('direktur_utama_decision_type') ?? 'Belum Ada Keputusan'),
                        Textarea::make('direktur_utama_catatan')
                            ->label('Catatan Direktur Utama')
                            ->disabled()
                            ->default(fn($get) => $get('direktur_utama_catatan') ?? 'Tidak ada catatan'),
                    ])->visible(fn(Pengajuan $record) => !is_null($record->direktur_utama_approved_by)),
                ])
                ->visible(fn(Pengajuan $record) => !is_null($record->kadiv_ops_catatan))
                ->collapsible()->collapsed(),
        ];
    }
}
