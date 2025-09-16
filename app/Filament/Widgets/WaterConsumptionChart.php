<?php

namespace App\Filament\Widgets;

use App\Models\Tank;
use App\Models\WaterOrder;
use App\Models\SensorReading;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class WaterConsumptionChart extends ChartWidget
{
    protected static ?string $heading = 'Water Consumption Trends';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';

    public ?string $filter = '7';

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $consumptionData = [];
        $orderData = [];
        $labels = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');

            // Get total consumption for the day (simplified calculation)
            $totalReadings = SensorReading::whereDate('created_at', $date)
                ->avg('water_level_percentage') ?? 50;

            // Simulate consumption based on average levels
            $consumptionData[] = round((100 - $totalReadings) * 50); // Multiplied by arbitrary factor

            // Get water orders for the day
            $orders = WaterOrder::whereDate('created_at', $date)
                ->sum('volume_liters') ?? 0;

            $orderData[] = round($orders / 100); // Convert to hectoliters
        }

        return [
            'datasets' => [
                [
                    'label' => 'Water Consumed (100L)',
                    'data' => $consumptionData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => 'Water Ordered (100L)',
                    'data' => $orderData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '14' => 'Last 14 days',
            '30' => 'Last 30 days',
            '90' => 'Last 3 months',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return value + ' hL'; }",
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}