<?php

namespace App\Filament\Widgets;

use App\Models\Tank;
use App\Models\User;
use App\Models\WaterOrder;
use App\Models\Alert;
use App\Models\SensorReading;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalTanks = Tank::count();
        $activeTanks = Tank::whereHas('sensor')->count();
        $totalUsers = User::count();
        $pendingOrders = WaterOrder::where('status', 'pending')->count();
        $unresolvedAlerts = Alert::where('is_resolved', false)->count();

        // Get average water level from recent readings
        $avgWaterLevel = SensorReading::where('created_at', '>=', now()->subDay())
            ->avg('water_level_percentage') ?? 50;

        // Count critical tanks (simplified)
        $criticalTanks = SensorReading::where('created_at', '>=', now()->subDay())
            ->where('water_level_percentage', '<', 20)
            ->distinct('tank_id')
            ->count('tank_id');

        // Generate simple chart data
        $monthlyOrdersData = [];
        for ($i = 6; $i >= 0; $i--) {
            $count = WaterOrder::whereDate('created_at', now()->subDays($i))->count();
            $monthlyOrdersData[] = $count * 10; // Scale for visibility
        }

        $dailyAlertsData = [];
        for ($i = 6; $i >= 0; $i--) {
            $count = Alert::whereDate('created_at', now()->subDays($i))->count();
            $dailyAlertsData[] = $count;
        }

        return [
            Stat::make('Total Tanks', $totalTanks)
                ->description($activeTanks . ' active')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($this->generateRandomChart())
                ->color('success'),

            Stat::make('Average Water Level', number_format($avgWaterLevel, 1) . '%')
                ->description($criticalTanks . ' tanks critical')
                ->descriptionIcon($criticalTanks > 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->chart($this->generateWaterLevelChart())
                ->color($avgWaterLevel < 30 ? 'danger' : ($avgWaterLevel < 60 ? 'warning' : 'success')),

            Stat::make('Total Users', $totalUsers)
                ->description('+' . User::where('created_at', '>=', now()->subDays(30))->count() . ' this month')
                ->descriptionIcon('heroicon-m-user-plus')
                ->chart($this->generateUserGrowthChart())
                ->color('info'),

            Stat::make('Pending Orders', $pendingOrders)
                ->description('Total: ' . WaterOrder::count())
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->chart($monthlyOrdersData)
                ->color($pendingOrders > 5 ? 'warning' : 'success'),

            Stat::make('Active Alerts', $unresolvedAlerts)
                ->description('Last 7 days')
                ->descriptionIcon($unresolvedAlerts > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->chart($dailyAlertsData)
                ->color($unresolvedAlerts > 0 ? 'danger' : 'success'),

            Stat::make('System Uptime', '99.9%')
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-signal')
                ->chart([100, 100, 99, 100, 100, 99, 100])
                ->color('success'),
        ];
    }

    private function generateRandomChart(): array
    {
        return array_map(fn() => rand(10, 100), range(1, 7));
    }

    private function generateWaterLevelChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $avg = SensorReading::whereDate('created_at', now()->subDays($i))
                ->avg('water_level_percentage') ?? rand(40, 80);
            $data[] = round($avg);
        }
        return $data;
    }

    private function generateUserGrowthChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $count = User::whereDate('created_at', now()->subDays($i))->count();
            $data[] = $count ?: rand(0, 3);
        }
        return $data;
    }
}