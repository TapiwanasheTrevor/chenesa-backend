<?php

namespace App\Filament\Widgets;

use App\Models\Alert;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class AlertsTimelineChart extends ChartWidget
{
    protected static ?string $heading = 'Alert Distribution';
    protected static ?int $sort = 4;
    protected static ?string $maxHeight = '400px';
    protected int | string | array $columnSpan = 1;

    public ?string $filter = 'week';

    protected function getData(): array
    {
        $endDate = now();
        $labels = [];
        $data = [
            'info' => [],
            'warning' => [],
            'critical' => [],
        ];

        switch ($this->filter) {
            case 'today':
                $startDate = now()->startOfDay();
                for ($hour = 0; $hour < 24; $hour++) {
                    $labels[] = sprintf('%02d:00', $hour);
                    $hourStart = now()->startOfDay()->addHours($hour);
                    $hourEnd = $hourStart->copy()->addHour();

                    foreach (['info', 'warning', 'critical'] as $severity) {
                        $data[$severity][] = Alert::where('severity', $severity)
                            ->whereBetween('created_at', [$hourStart, $hourEnd])
                            ->count();
                    }
                }
                break;

            case 'week':
                $startDate = now()->subWeek();
                for ($day = 6; $day >= 0; $day--) {
                    $date = now()->subDays($day);
                    $labels[] = $date->format('D');

                    foreach (['info', 'warning', 'critical'] as $severity) {
                        $data[$severity][] = Alert::where('severity', $severity)
                            ->whereDate('created_at', $date)
                            ->count();
                    }
                }
                break;

            case 'month':
                $startDate = now()->subMonth();
                // Group by week
                for ($week = 0; $week < 4; $week++) {
                    $weekStart = now()->subMonth()->addWeeks($week);
                    $weekEnd = $weekStart->copy()->addWeek();
                    $labels[] = 'Week ' . ($week + 1);

                    foreach (['info', 'warning', 'critical'] as $severity) {
                        $data[$severity][] = Alert::where('severity', $severity)
                            ->whereBetween('created_at', [$weekStart, $weekEnd])
                            ->count();
                    }
                }
                break;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Critical',
                    'data' => $data['critical'],
                    'backgroundColor' => '#ef4444',
                    'stack' => 'alerts',
                ],
                [
                    'label' => 'Warning',
                    'data' => $data['warning'],
                    'backgroundColor' => '#f59e0b',
                    'stack' => 'alerts',
                ],
                [
                    'label' => 'Info',
                    'data' => $data['info'],
                    'backgroundColor' => '#3b82f6',
                    'stack' => 'alerts',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
        ];
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
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}