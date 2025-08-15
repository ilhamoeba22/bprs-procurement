<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\ChartWidget;

class PengajuanPerDivisiChart extends ChartWidget
{
    protected static ?string $heading = 'Rekap Divisi';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '210px';

    /**
     * Filter dropdown chart
     */
    protected function getFilters(): array
    {
        return [
            '1m' => '1 Bulan Terakhir',
            '6m' => '6 Bulan Terakhir',
            '1y' => '1 Tahun Terakhir',
        ];
    }

    protected function getType(): string
    {
        return 'pie'; // Tipe grafik: pie
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false, // Allow custom height
            'scales' => [
                'y' => [
                    'display' => false, // Hide y-axis scale to remove grid lines
                    'grid' => [
                        'display' => false, // Ensure no grid lines are drawn
                    ],
                ],
                'x' => [
                    'display' => false, // Hide x-axis scale to remove any residual lines
                    'grid' => [
                        'display' => false, // Ensure no grid lines are drawn
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'right', // Move legend to the right side
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        // Default filter if none selected
        $filter = $this->filter ?? '1m';
        $start = now()->startOfDay();

        // Set date range based on filter
        switch ($filter) {
            case '1m':
                $start = now()->subDays(30)->startOfDay();
                break;
            case '6m':
                $start = now()->subDays(180)->startOfDay();
                break;
            case '1y':
                $start = now()->subDays(365)->startOfDay();
                break;
        }

        // Query untuk menjumlahkan total_nilai dan mengelompokkannya berdasarkan divisi dalam rentang waktu
        $data = Pengajuan::query()
            ->join('users', 'pengajuans.id_user_pemohon', '=', 'users.id_user')
            ->join('divisis', 'users.id_divisi', '=', 'divisis.id_divisi')
            ->where('pengajuans.status', Pengajuan::STATUS_SELESAI)
            ->where('pengajuans.updated_at', '>=', $start)
            ->groupBy('divisis.nama_divisi')
            ->select(DB::raw('divisis.nama_divisi as divisi'), DB::raw('sum(pengajuans.total_nilai) as total'))
            ->pluck('total', 'divisi');

        // Siapkan data untuk dataset dan label grafik
        $labels = $data->keys()->toArray();
        $chartData = $data->values()->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Nilai',
                    'data' => $chartData,
                    // Sediakan beberapa warna agar pie chart menarik
                    'backgroundColor' => [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                        '#E7E9ED',
                        '#8DDF3C',
                        '#F171A7',
                        '#63C4FF',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }
}
