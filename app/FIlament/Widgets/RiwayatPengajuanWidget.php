<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
                    ->label('Download')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function (Pengajuan $record) {
                        $record->load([
                            'pemohon.divisi',
                            'pemohon.kantor',
                            'pemohon.jabatan',
                            'items.surveiHargas',
                            'vendorPembayaran',
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

                        $generateQrCode = function ($user) use ($record) {
                            if (!$user) return null;
                            $url = URL::signedRoute('approval.verify', [
                                'pengajuan' => $record,
                                'user' => $user
                            ]);
                            $qrCodeData = QrCode::format('png')->size(70)->margin(1)->generate($url);
                            return 'data:image/png;base64,' . base64_encode($qrCodeData);
                        };

                        $atasan = $record->approverKadiv ?? $record->approverManager;
                        $direksi = $record->approverDirUtama ?? $record->approverDirOps;

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

                        $pdf = Pdf::loadView('documents.laporan-akhir', $data)
                            ->setPaper('a4', 'portrait');

                        $fileName = 'Laporan_Akhir_' . str_replace('/', '_', $record->kode_pengajuan) . '.pdf';
                        return response()->streamDownload(fn() => print($pdf->output()), $fileName);
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
