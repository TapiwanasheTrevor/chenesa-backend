<?php

namespace App\Filament\Widgets;

use App\Models\Tank;
use Filament\Widgets\ChartWidget;
use Filament\Support\RawJs;

class TankStatusDistribution extends ChartWidget
{
    protected static ?string $heading = 'Tank Status Distribution';
    protected static ?int $sort = 5;
    protected static ?string $maxHeight = '400px';
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        // Get tanks with their latest readings and calculate status distribution
        $tanks = Tank::with('latestReading')->get();

        $statusCounts = [
            'Critical' => 0,
            'Low' => 0,
            'Normal' => 0,
            'Full' => 0,
        ];

        foreach ($tanks as $tank) {
            $level = $tank->latestReading?->water_level_percentage ?? 0;

            if ($level < 20) {
                $statusCounts['Critical']++;
            } elseif ($level < 50) {
                $statusCounts['Low']++;
            } elseif ($level < 80) {
                $statusCounts['Normal']++;
            } else {
                $statusCounts['Full']++;
            }
        }

        $data = $statusCounts;

        return [
            'datasets' => [
                [
                    'data' => array_values($data),
                    'backgroundColor' => [
                        '#ef4444',
                        '#f59e0b',
                        '#3b82f6',
                        '#10b981',
                    ],
                    'borderWidth' => 0,
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'devicePixelRatio' => 1,
            'height' => 300,
            'layout' => [
                'padding' => [
                    'top' => 10,
                    'bottom' => 10,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => new \Filament\Support\RawJs('function(context) {
                            const label = context.label || \'\';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + \': \' + value + \' (\' + percentage + \'%)\';
                        }'),
                    ],
                ],
            ],
            'cutout' => '60%',
        ];
    }
}