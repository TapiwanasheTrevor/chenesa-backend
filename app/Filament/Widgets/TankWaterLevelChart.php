<?php

namespace App\Filament\Widgets;

use App\Models\Tank;
use App\Models\SensorReading;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TankWaterLevelChart extends ChartWidget
{
    protected static ?string $heading = 'Water Levels by Tank';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Get latest reading for each tank
        $latestReadings = SensorReading::select('tank_id', DB::raw('MAX(created_at) as latest_date'))
            ->groupBy('tank_id')
            ->get();

        $tankData = [];
        foreach ($latestReadings as $latest) {
            $reading = SensorReading::where('tank_id', $latest->tank_id)
                ->where('created_at', $latest->latest_date)
                ->first();

            if ($reading && $reading->tank) {
                $tankData[] = [
                    'name' => $reading->tank->name,
                    'level' => $reading->water_level_percentage ?? 0,
                ];
            }
        }

        // Sort by water level and take top 10
        $tankData = collect($tankData)
            ->sortBy('level')
            ->take(10)
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Current Water Level (%)',
                    'data' => $tankData->pluck('level')->toArray(),
                    'backgroundColor' => $tankData->map(function ($item) {
                        $level = $item['level'];
                        if ($level < 20) return '#ef4444';
                        if ($level < 50) return '#f59e0b';
                        return '#10b981';
                    })->toArray(),
                    'borderColor' => '#e5e7eb',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $tankData->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'callback' => "function(value) { return value + '%'; }",
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) { return context.parsed.x + '%'; }",
                    ],
                ],
            ],
        ];
    }
}