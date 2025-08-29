<?php

namespace App\Filament\Widgets;

use ZipArchive;
use Carbon\Carbon;
use Filament\Tables;
use App\Models\Pengajuan;
use App\Models\SurveiHarga;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Notification;
use Filament\Widgets\TableWidget as BaseWidget;

class RiwayatPengajuanWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = '';

    public function table(Table $table): Table
    {
        $finalStatuses = [
            Pengajuan::STATUS_SELESAI,
            Pengajuan::STATUS_DITOLAK_MANAGER,
            Pengajuan::STATUS_DITOLAK_KADIV,
            Pengajuan::STATUS_DITOLAK_KADIV_GA,
            Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
            Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
        ];

        return $table
            ->query(
                fn() => Pengajuan::query()
                    ->with(['pemohon.divisi'])
                    ->whereIn('status', $finalStatuses)
            )
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('kode_pengajuan')
                    ->label('Kode Pengajuan')
                    ->searchable(),
                TextColumn::make('pemohon.nama_user')
                    ->label('Nama Pengaju')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('pemohon.divisi.nama_divisi')
                    ->label('Divisi')
                    ->toggleable(),
                TextColumn::make('total_nilai')
                    ->label('Total Nilai')
                    ->money('IDR')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status Final')
                    ->color(fn(string $state) => str_contains(strtolower($state), 'ditolak') ? 'danger' : 'success'),
                TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('download_laporan_akhir')
                    ->label('Laporan')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function (Pengajuan $record) {
                        $record->load([
                            'pemohon.divisi',
                            'pemohon.jabatan',
                            'items.surveiHargas.revisiHargas' => function ($query) {
                                $query->with([
                                    'direvisiOleh.jabatan',
                                    'revisiBudgetApprover.jabatan',
                                    'revisiBudgetValidator.jabatan',
                                    'revisiKadivGaApprover.jabatan',
                                    'revisiDirekturOperasionalApprover.jabatan',
                                    'revisiDirekturUtamaApprover.jabatan'
                                ])->latest();
                            },
                            'vendorPembayaran',
                            'approverManager.jabatan',
                            'approverKadiv.jabatan',
                            'recommenderIt.jabatan',
                            'surveyorGa.jabatan',
                            'approverBudget.jabatan',
                            'validatorBudgetOps.jabatan',
                            'approverKadivGa.jabatan',
                            'approverDirOps.jabatan',
                            'approverDirUtama.jabatan',
                            'disbursedBy.jabatan'
                        ]);

                        // [LOGIKA DISAMAKAN DENGAN SPM]
                        $finalVendor = $record->vendorPembayaran()->where('is_final', true)->first();

                        $itemsOriginal = [];
                        $totalNilaiBarangOriginal = 0;
                        $totalPajakOriginal = 0;
                        $taxConditionOriginal = 'Tidak Ada Pajak';
                        $taxTypeOriginal = null;

                        foreach ($record->items as $item) {
                            $survey = $item->surveiHargas->where('nama_vendor', $finalVendor->nama_vendor)->first();
                            $hargaSatuan = $survey->harga ?? 0;
                            $subtotal = $hargaSatuan * $item->kuantitas;
                            $itemsOriginal[] = ['barang' => $item->nama_barang, 'kuantitas' => $item->kuantitas, 'harga' => $hargaSatuan, 'subtotal' => $subtotal];
                            $totalNilaiBarangOriginal += $subtotal;
                            if ($survey) {
                                $isTaxExclude = in_array($survey->kondisi_pajak, ['Pajak ditanggung Perusahaan (Exclude)', 'Pajak ditanggung kita', 'Pajak ditanggung BPRS']);
                                if ($isTaxExclude) {
                                    $totalPajakOriginal += $survey->nominal_pajak ?? 0;
                                    $taxConditionOriginal = 'Pajak ditanggung Perusahaan (Exclude)';
                                    if (!$taxTypeOriginal) $taxTypeOriginal = $survey->jenis_pajak;
                                } elseif ($survey->kondisi_pajak === 'Pajak ditanggung Vendor (Include)') {
                                    $totalPajakOriginal += $survey->nominal_pajak ?? 0;
                                    $taxConditionOriginal = 'Pajak ditanggung Vendor (Include)';
                                    if (!$taxTypeOriginal) $taxTypeOriginal = $survey->jenis_pajak;
                                }
                            }
                        }

                        $totalBiayaOriginal = $totalNilaiBarangOriginal;
                        if ($taxConditionOriginal === 'Pajak ditanggung Perusahaan (Exclude)') {
                            $totalBiayaOriginal += $totalPajakOriginal;
                        }

                        $latestRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->sortByDesc('created_at')->first();
                        $isRevisi = !is_null($latestRevisi);
                        $revisionDetails = null;
                        $taxTypeFinal = $taxTypeOriginal;
                        $taxConditionFinal = $taxConditionOriginal;

                        if ($isRevisi) {
                            $totalNilaiBarangFinal = $latestRevisi->harga_revisi;
                            $totalPajakFinal = $latestRevisi->nominal_pajak;
                            $totalFinal = $totalNilaiBarangFinal + $totalPajakFinal;
                            $taxTypeFinal = $latestRevisi->jenis_pajak;
                            $taxConditionFinal = $latestRevisi->kondisi_pajak;
                            $revisionDetails = ['selisih_total' => $totalFinal - $totalBiayaOriginal, 'alasan_revisi' => $latestRevisi->alasan_revisi, 'tanggal_revisi' => Carbon::parse($latestRevisi->tanggal_revisi)->translatedFormat('d F Y')];
                        } else {
                            $totalNilaiBarangFinal = $totalNilaiBarangOriginal;
                            $totalPajakFinal = $totalPajakOriginal;
                            $totalFinal = $totalBiayaOriginal;
                        }

                        $atasan = $record->approverKadiv ?? $record->approverManager;
                        $direksi = $record->approverDirUtama ?? $record->approverDirOps;
                        $is_paid = $finalVendor ? !empty($finalVendor->bukti_pelunasan) : false;
                        $payment_details = null;
                        if ($finalVendor) {
                            $payment_details = ['vendor' => $finalVendor->nama_vendor, 'metode_pembayaran' => $finalVendor->metode_pembayaran, 'opsi_pembayaran' => $finalVendor->opsi_pembayaran, 'nama_bank' => $finalVendor->nama_bank, 'no_rekening' => $finalVendor->no_rekening, 'nama_rekening' => $finalVendor->nama_rekening, 'nominal_dp' => $finalVendor->nominal_dp, 'tanggal_dp' => $finalVendor->tanggal_dp ? \Carbon\Carbon::parse($finalVendor->tanggal_dp)->translatedFormat('d F Y') : '-', 'tanggal_dp_aktual' => $finalVendor->tanggal_dp_aktual ? \Carbon\Carbon::parse($finalVendor->tanggal_dp_aktual)->translatedFormat('d F Y') : null, 'tanggal_pelunasan' => $finalVendor->tanggal_pelunasan ? \Carbon\Carbon::parse($finalVendor->tanggal_pelunasan)->translatedFormat('d F Y') : '-', 'tanggal_pelunasan_aktual' => $finalVendor->tanggal_pelunasan_aktual ? \Carbon\Carbon::parse($finalVendor->tanggal_pelunasan_aktual)->translatedFormat('d F Y') : null];
                        }

                        $generateQrCode = function ($user) use ($record) {
                            if (!$user) return null;
                            $url = URL::signedRoute('approval.verify', ['pengajuan' => $record, 'user' => $user]);
                            $qrCodeData = QrCode::format('png')->size(70)->margin(1)->generate($url);
                            return 'data:image/png;base64,' . base64_encode($qrCodeData);
                        };

                        $data = [
                            'pengajuan' => $record,
                            'atasan' => $atasan,
                            'direksi' => $direksi,
                            'latestRevisi' => $latestRevisi,
                            'finalVendor' => $finalVendor,
                            'items_original' => $itemsOriginal,
                            'total_nilai_barang_original' => $totalNilaiBarangOriginal,
                            'total_pajak_original' => $totalPajakOriginal,
                            'tax_condition_original' => $taxConditionOriginal,
                            'tax_type_original' => $taxTypeOriginal,
                            'total_biaya_original' => $totalBiayaOriginal,
                            'total_nilai_barang_final' => $totalNilaiBarangFinal,
                            'total_pajak_final' => $totalPajakFinal,
                            'tax_condition_final' => $taxConditionFinal,
                            'tax_type_final' => $taxTypeFinal,
                            'total_final' => $totalFinal,
                            'is_revisi' => $isRevisi,
                            'revision_details' => $revisionDetails,
                            'payment_details' => $payment_details,
                            'is_paid' => $is_paid,
                            'qrCodes' => ['pemohon' => $generateQrCode($record->pemohon), 'atasan' => $generateQrCode($atasan), 'it' => $generateQrCode($record->recommenderIt), 'ga_surveyor' => $generateQrCode($record->surveyorGa), 'budget_approver' => $generateQrCode($record->approverBudget), 'budget_validator' => $generateQrCode($record->validatorBudgetOps), 'pembayar' => $generateQrCode($record->disbursedBy), 'kadiv_ga' => $generateQrCode($record->approverKadivGa), 'direksi' => $generateQrCode($direksi)],
                        ];

                        $pdf = Pdf::loadView('documents.laporan-akhir', $data)->setPaper('a4', 'portrait');
                        $fileName = 'Laporan_Akhir_' . str_replace('/', '_', $record->kode_pengajuan) . '.pdf';
                        return response()->streamDownload(fn() => print($pdf->output()), $fileName);
                    }),

                Action::make('download_lampiran')
                    ->label('Lampiran')
                    ->icon('heroicon-o-folder-arrow-down')
                    ->color('gray')
                    ->action(function (Pengajuan $record) {
                        // 1. Eager load semua relasi yang mengandung path file
                        $record->load(['items.surveiHargas.revisiHargas', 'vendorPembayaran']);

                        $filePaths = [];

                        // 2. Kumpulkan semua path file dari berbagai sumber
                        // a. Bukti Survei dari setiap vendor
                        $buktiSurvei = $record->items->flatMap->surveiHargas->pluck('bukti_path')->unique()->filter();
                        foreach ($buktiSurvei as $path) {
                            $filePaths[] = $path;
                        }

                        // b. Bukti Revisi
                        $buktiRevisi = $record->items->flatMap->surveiHargas->flatMap->revisiHargas->pluck('bukti_revisi')->unique()->filter();
                        foreach ($buktiRevisi as $path) {
                            $filePaths[] = $path;
                        }

                        // c. Bukti Pembayaran (DP, Pelunasan, Pajak)
                        foreach ($record->vendorPembayaran as $pembayaran) {
                            if ($pembayaran->bukti_dp) $filePaths[] = $pembayaran->bukti_dp;
                            if ($pembayaran->bukti_pelunasan) $filePaths[] = $pembayaran->bukti_pelunasan;
                            if ($pembayaran->bukti_pajak) $filePaths[] = $pembayaran->bukti_pajak;

                            // d. Bukti Penyelesaian (handle format JSON)
                            if ($pembayaran->bukti_penyelesaian) {
                                $buktiPenyelesaian = is_string($pembayaran->bukti_penyelesaian) ? json_decode($pembayaran->bukti_penyelesaian, true) : $pembayaran->bukti_penyelesaian;
                                if (is_array($buktiPenyelesaian)) {
                                    foreach ($buktiPenyelesaian as $bukti) {
                                        if (isset($bukti['file_path'])) {
                                            $filePaths[] = $bukti['file_path'];
                                        }
                                    }
                                }
                            }
                        }

                        $uniqueFilePaths = array_unique($filePaths);

                        if (empty($uniqueFilePaths)) {
                            Notification::make()->title('Tidak Ada Lampiran')->body('Tidak ada file lampiran yang ditemukan untuk pengajuan ini.')->warning()->send();
                            return;
                        }

                        // 3. Buat file ZIP
                        $zipFileName = 'Lampiran_' . str_replace('/', '_', $record->kode_pengajuan) . '.zip';
                        $tempZipPath = storage_path('app/temp/' . $zipFileName);

                        // Pastikan direktori temp ada
                        File::ensureDirectoryExists(dirname($tempZipPath));

                        $zip = new ZipArchive;
                        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                            foreach ($uniqueFilePaths as $filePath) {
                                // Pastikan file ada di storage sebelum menambahkannya ke ZIP
                                if (Storage::disk('private')->exists($filePath)) {
                                    $absolutePath = Storage::disk('private')->path($filePath);
                                    $fileNameInZip = basename($filePath);
                                    $zip->addFile($absolutePath, $fileNameInZip);
                                }
                            }
                            $zip->close();
                        } else {
                            Notification::make()->title('Gagal Membuat ZIP')->body('Tidak dapat membuat file arsip.')->danger()->send();
                            return;
                        }

                        // 4. Download file ZIP dan hapus setelahnya
                        return response()->download($tempZipPath)->deleteFileAfterSend(true);
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        '' => 'Semua',
                        'selesai' => 'Selesai',
                        'ditolak' => 'Ditolak',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (($data['value'] ?? '') === 'selesai') {
                            return $query->where('status', Pengajuan::STATUS_SELESAI);
                        }
                        if (($data['value'] ?? '') === 'ditolak') {
                            return $query->whereIn('status', [
                                Pengajuan::STATUS_DITOLAK_MANAGER,
                                Pengajuan::STATUS_DITOLAK_KADIV,
                                Pengajuan::STATUS_DITOLAK_KADIV_GA,
                                Pengajuan::STATUS_DITOLAK_DIREKTUR_OPERASIONAL,
                                Pengajuan::STATUS_DITOLAK_DIREKTUR_UTAMA,
                            ]);
                        }
                        return $query;
                    })
            ]);
    }
}
