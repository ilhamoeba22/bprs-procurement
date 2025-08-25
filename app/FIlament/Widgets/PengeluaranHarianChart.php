<?php

namespace App\Filament\Widgets;

use Carbon\CarbonPeriod;
use App\Models\Pengajuan;
use Filament\Widgets\ChartWidget;

class PengeluaranHarianChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Pengeluaran';
    protected static ?int $sort = 1;
    protected static ?string $maxHeight = '210px';

    /**
     * Filter dropdown chart
     */
    protected function getFilters(): array
    {
        return [
            // 'today' => 'Hari Ini',
            '7d'    => '7 Hari Terakhir',
            // '30d'   => '1 Bulan Terakhir',
            '3m'    => '3 Bulan Terakhir',
            '1y'    => '1 Tahun Terakhir',
            '3y'    => '3 Tahun Terakhir',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'position' => 'right', // Move y-axis to the right
                    'ticks' => [
                        'font' => [
                            'size' => 13, // Set font size to prevent labels from being too large
                        ],
                    ],
                    'grid' => [
                        'drawTicks' => true, // Ensure ticks are drawn
                        'lineWidth' => 1, // Keep grid lines subtle
                    ],
                ],
            ],
        ];
    }

    protected function getData(): array
    {
        $filter = $this->filter ?? '7d';
        $start = $end = null;
        $groupFormat = '%Y-%m-%d'; // default harian
        $labelFormat = 'd M';

        switch ($filter) {
            // case 'today':
            //     $start = now()->startOfDay();
            //     $end   = now()->endOfDay();
            //     break;
            case '7d':
                $start = now()->subDays(6)->startOfDay();
                $end   = now()->endOfDay();
                break;
            // case '30d':
            //     $start = now()->subDays(29)->startOfDay();
            //     $end   = now()->endOfDay();
            //     break;
            case '3m':
                $start = now()->subMonths(2)->startOfMonth();
                $end   = now()->endOfDay();
                $groupFormat = '%Y-%m'; // group bulanan
                $labelFormat = 'M Y';
                break;
            case '1y':
                $start = now()->subYear()->startOfMonth();
                $end   = now()->endOfMonth();
                $groupFormat = '%Y-%m'; // group bulanan
                $labelFormat = 'M Y';
                break;
            case '3y':
                $start = now()->subYears(3)->startOfYear();
                $end   = now()->endOfYear();
                $groupFormat = '%Y'; // group tahunan
                $labelFormat = 'Y';
                break;
            default:
                $start = now()->subDays(6)->startOfDay();
                $end   = now()->endOfDay();
                break;
        }

        // Query sesuai group format
        $data = Pengajuan::whereBetween('updated_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(updated_at, '{$groupFormat}') as periode, SUM(total_nilai) as total")
            ->groupBy('periode')
            ->orderBy('periode')
            ->pluck('total', 'periode');

        $labels = [];
        $totals = [];

        // Bentuk labels sesuai filter
        if (in_array($filter, ['30d', '3m', '1y', '3y'])) {
            if ($filter === '30d') {
                // Hanya awal & akhir
                $labels = [
                    $start->format('d M'),
                    $end->format('d M'),
                ];
                $totals = [
                    (float) ($data[$start->format('Y-m-d')] ?? 0),
                    (float) ($data[$end->format('Y-m-d')] ?? 0),
                ];
            } elseif ($filter === '3m') {
                // Bulan awal, tengah, akhir
                $mid = $start->copy()->addMonth();
                $months = [$start, $mid, $end];
                foreach ($months as $m) {
                    $key = $m->format('Y-m');
                    $labels[] = $m->format($labelFormat);
                    $totals[] = (float) ($data[$key] ?? 0);
                }
            } else {
                // 1 tahun & 3 tahun → tampil semua sesuai grouping
                foreach ($data as $key => $total) {
                    $labels[] = \Carbon\Carbon::createFromFormat(
                        $groupFormat === '%Y-%m' ? 'Y-m' : 'Y',
                        $key
                    )->format($labelFormat);
                    $totals[] = (float) $total;
                }
            }
        } else {
            // Hari ini & 7 hari terakhir → tampil harian
            $period = \Carbon\CarbonPeriod::create($start, $end);
            foreach ($period as $date) {
                $key = $date->format('Y-m-d');
                $labels[] = $date->format($labelFormat);
                $totals[] = (float) ($data[$key] ?? 0);
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Pengeluaran',
                    'data'  => $totals,
                ],
            ],
        ];
    }
}
