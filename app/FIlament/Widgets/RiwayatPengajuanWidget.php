<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use App\Models\User;
use Filament\Tables;
use App\Models\Pengajuan;
use Filament\Tables\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\URL;

class RiwayatPengajuanWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Riwayat Pengajuan Selesai';

    // Di dalam file app/Filament/Widgets/RiwayatPengajuanWidget.php

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Pengajuan::query()->where('status', Pengajuan::STATUS_SELESAI)
            )
            ->columns([
                Tables\Columns\TextColumn::make('kode_pengajuan')->label('Kode')->searchable(),
                Tables\Columns\TextColumn::make('pemohon.nama_user')->label('Pemohon')->searchable(),
                Tables\Columns\TextColumn::make('total_nilai')->label('Nilai Akhir')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Tanggal Selesai')->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('download_laporan_akhir')
                    ->label('Download Laporan')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Pengajuan $record) {
                        // 1. Load semua relasi yang dibutuhkan untuk efisiensi
                        $record->load([
                            'pemohon.divisi',
                            'pemohon.kantor',
                            'pemohon.jabatan',
                            'items.vendorFinal',
                            'items.surveiHargas',
                            'approverManager.jabatan',
                            'approverKadiv.jabatan',
                            'recommenderIt.jabatan',
                            'surveyorGa.jabatan',
                            'approverBudget.jabatan',
                            'validatorBudgetOps.jabatan',
                            'approverKadivGa.jabatan',
                            'approverDirOps',
                            'approverDirUtama'
                        ]);

                        // 2. Fungsi helper untuk membuat QR Code
                        $generateQrCode = function ($user) use ($record) {
                            if (!$user) return null;
                            $url = URL::signedRoute('approval.verify', ['pengajuan' => $record, 'user' => $user]);
                            $qrCodeData = QrCode::format('png')->size(70)->margin(1)->generate($url);
                            return 'data:image/png;base64,' . base64_encode($qrCodeData);
                        };

                        // 3. Tentukan siapa atasan pemohon
                        $atasan = $record->approverKadiv ?? $record->approverManager;

                        // 4. Tentukan siapa direksi final
                        $direksi = $record->approverDirUtama ?? $record->approverDirOps;

                        // 5. Kumpulkan semua data yang akan dikirim ke view
                        $data = [
                            'pengajuan' => $record,
                            'pemohonQrCode' => $generateQrCode($record->pemohon),
                            'atasan' => $atasan,
                            'atasanQrCode' => $generateQrCode($atasan),
                            'surveyorGaQrCode' => $generateQrCode($record->surveyorGa),
                            'approverBudgetQrCode' => $generateQrCode($record->approverBudget),
                            'validatorBudgetOpsQrCode' => $generateQrCode($record->validatorBudgetOps),
                            'approverKadivGaQrCode' => $generateQrCode($record->approverKadivGa),
                            'direksi' => $direksi,
                            'direksiQrCode' => $generateQrCode($direksi),
                        ];

                        $pdf = Pdf::loadView('documents.laporan-akhir', $data)->setPaper('a4', 'portrait');
                        $fileName = 'Laporan_Akhir_' . str_replace('/', '_', $record->kode_pengajuan) . '.pdf';

                        return response()->streamDownload(fn() => print($pdf->output()), $fileName);
                    }),
            ]);
    }
}
