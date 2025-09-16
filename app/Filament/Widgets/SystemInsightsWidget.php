<?php

namespace App\Filament\Widgets;

use App\Models\Tank;
use App\Models\WaterOrder;
use App\Models\Alert;
use App\Models\User;
use App\Models\SensorReading;
use Filament\Widgets\Widget;
use Carbon\Carbon;

class SystemInsightsWidget extends Widget
{
    protected static string $view = 'filament.widgets.system-insights';
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        // Calculate average refill time (simplified)
        $deliveredOrders = WaterOrder::where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->get();

        $avgRefillTime = 24; // Default
        if ($deliveredOrders->isNotEmpty()) {
            $totalHours = $deliveredOrders->sum(function ($order) {
                return Carbon::parse($order->delivered_at)->diffInHours(Carbon::parse($order->created_at));
            });
            $avgRefillTime = round($totalHours / $deliveredOrders->count());
        }

        // Find most active hour (simplified)
        $alertsByHour = Alert::get()->groupBy(function ($alert) {
            return Carbon::parse($alert->created_at)->hour;
        });

        $mostActiveHour = null;
        $maxCount = 0;
        foreach ($alertsByHour as $hour => $alerts) {
            if ($alerts->count() > $maxCount) {
                $maxCount = $alerts->count();
                $mostActiveHour = (object)['hour' => $hour];
            }
        }

        // Calculate tank efficiency
        $tankEfficiency = SensorReading::where('created_at', '>=', now()->subDay())
            ->avg('water_level_percentage') ?? 70;

        // Count tanks needing refill
        $predictedRefills = SensorReading::where('created_at', '>=', now()->subDay())
            ->where('water_level_percentage', '<', 30)
            ->distinct('tank_id')
            ->count('tank_id');

        $insights = [
            [
                'title' => 'Average Refill Time',
                'value' => round($avgRefillTime) . ' hours',
                'description' => 'Time from order to delivery',
                'icon' => 'heroicon-o-clock',
                'color' => 'primary',
            ],
            [
                'title' => 'Peak Alert Time',
                'value' => $mostActiveHour ? sprintf('%02d:00', $mostActiveHour->hour) : 'N/A',
                'description' => 'Most alerts occur at this time',
                'icon' => 'heroicon-o-bell-alert',
                'color' => 'warning',
            ],
            [
                'title' => 'System Efficiency',
                'value' => number_format($tankEfficiency, 1) . '%',
                'description' => 'Average tank capacity utilization',
                'icon' => 'heroicon-o-chart-pie',
                'color' => $tankEfficiency > 70 ? 'success' : 'danger',
            ],
            [
                'title' => 'Predicted Refills',
                'value' => $predictedRefills,
                'description' => 'Tanks needing refill soon',
                'icon' => 'heroicon-o-arrow-trending-up',
                'color' => $predictedRefills > 5 ? 'danger' : 'info',
            ],
        ];

        $recommendations = $this->generateRecommendations();

        return [
            'insights' => $insights,
            'recommendations' => $recommendations,
        ];
    }

    private function generateRecommendations(): array
    {
        $recommendations = [];

        $criticalTanks = SensorReading::where('created_at', '>=', now()->subDay())
            ->where('water_level_percentage', '<', 20)
            ->distinct('tank_id')
            ->count('tank_id');

        if ($criticalTanks > 0) {
            $recommendations[] = [
                'type' => 'urgent',
                'message' => "$criticalTanks tanks are critically low on water. Immediate refill recommended.",
            ];
        }

        $pendingOrders = WaterOrder::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->count();
        if ($pendingOrders > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => "$pendingOrders orders have been pending for over 24 hours.",
            ];
        }

        $inefficientTanks = SensorReading::where('created_at', '>=', now()->subDay())
            ->where('water_level_percentage', '<', 30)
            ->distinct('tank_id')
            ->count('tank_id');

        if ($inefficientTanks > 3) {
            $recommendations[] = [
                'type' => 'info',
                'message' => "Consider scheduling bulk refills for $inefficientTanks low tanks to optimize delivery costs.",
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'System is operating optimally. No immediate actions required.',
            ];
        }

        return $recommendations;
    }
}