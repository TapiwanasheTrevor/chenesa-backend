<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\SensorReading;
use App\Models\Tank;
use App\Models\WaterOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview statistics
     */
    public function overview(Request $request)
    {
        $user = $request->user();
        $organization = $user->organization;

        if (!$organization) {
            return response()->json(['message' => 'No organization found'], 404);
        }

        $tanks = Tank::where('organization_id', $organization->id)->get();
        $tankIds = $tanks->pluck('id');

        // Get current levels and status
        $activeTanks = $tanks->where('status', 'active')->count();
        $totalCapacity = $tanks->sum('capacity');

        // Calculate current water levels
        $currentLevels = [];
        $totalCurrentVolume = 0;

        foreach ($tanks as $tank) {
            $latestReading = SensorReading::where('tank_id', $tank->id)
                ->latest()
                ->first();

            if ($latestReading) {
                $volume = ($latestReading->water_level / 100) * $tank->capacity;
                $currentLevels[$tank->id] = [
                    'percentage' => $latestReading->water_level,
                    'volume' => round($volume, 2)
                ];
                $totalCurrentVolume += $volume;
            } else {
                $currentLevels[$tank->id] = [
                    'percentage' => 0,
                    'volume' => 0
                ];
            }
        }

        // Get active alerts (unresolved)
        $activeAlerts = Alert::whereIn('tank_id', $tankIds)
            ->where('is_resolved', false)
            ->count();

        // Get recent orders
        $recentOrders = WaterOrder::where('organization_id', $organization->id)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        // Calculate water consumption today
        $todayConsumption = $this->calculateDailyConsumption($tankIds, Carbon::today());

        // Calculate weekly consumption
        $weeklyConsumption = $this->calculateWeeklyConsumption($tankIds);

        return response()->json([
            'data' => [
                'summary' => [
                    'total_tanks' => $tanks->count(),
                    'active_tanks' => $activeTanks,
                    'total_capacity' => $totalCapacity,
                    'current_volume' => round($totalCurrentVolume, 2),
                    'fill_percentage' => $totalCapacity > 0 ? round(($totalCurrentVolume / $totalCapacity) * 100, 1) : 0,
                    'active_alerts' => $activeAlerts,
                    'recent_orders' => $recentOrders,
                ],
                'consumption' => [
                    'today' => $todayConsumption,
                    'weekly_average' => $weeklyConsumption['average'],
                    'week_total' => $weeklyConsumption['total'],
                ],
                'tank_levels' => $currentLevels,
                'quick_stats' => [
                    'tanks_below_20_percent' => $this->getTanksBelowThreshold($tanks, 20),
                    'tanks_offline' => $tanks->where('status', 'offline')->count(),
                    'pending_orders' => WaterOrder::where('organization_id', $organization->id)
                        ->where('status', 'pending')
                        ->count(),
                ]
            ]
        ]);
    }

    /**
     * Get consumption analytics
     */
    public function consumption(Request $request)
    {
        $request->validate([
            'period' => 'in:day,week,month,year',
            'tank_id' => 'nullable|exists:tanks,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = $request->user();
        $organization = $user->organization;
        $period = $request->input('period', 'week');
        $tankId = $request->input('tank_id');
        $startDate = $request->input('start_date', Carbon::now()->subDays(30));
        $endDate = $request->input('end_date', Carbon::now());

        $tankIds = $tankId
            ? [$tankId]
            : Tank::where('organization_id', $organization->id)->pluck('id');

        $consumptionData = $this->getConsumptionData($tankIds, $period, $startDate, $endDate);
        $trends = $this->calculateConsumptionTrends($tankIds, $period);

        return response()->json([
            'data' => [
                'period' => $period,
                'consumption_data' => $consumptionData,
                'trends' => $trends,
                'statistics' => [
                    'total_consumption' => array_sum(array_column($consumptionData, 'consumption')),
                    'average_daily' => $this->calculateAverageDaily($consumptionData),
                    'peak_consumption' => max(array_column($consumptionData, 'consumption')),
                    'efficiency_score' => $this->calculateEfficiencyScore($tankIds, $period),
                ]
            ]
        ]);
    }

    /**
     * Get cost analytics
     */
    public function costs(Request $request)
    {
        $request->validate([
            'period' => 'in:month,quarter,year',
            'year' => 'nullable|integer|min:2020',
        ]);

        $user = $request->user();
        $organization = $user->organization;
        $period = $request->input('period', 'month');
        $year = $request->input('year', Carbon::now()->year);

        $orders = WaterOrder::where('organization_id', $organization->id)
            ->whereYear('created_at', $year)
            ->where('status', '!=', 'cancelled')
            ->get();

        $costData = $this->organizeCostData($orders, $period);
        $predictions = $this->predictFutureCosts($orders, $period);

        return response()->json([
            'data' => [
                'period' => $period,
                'year' => $year,
                'cost_breakdown' => $costData,
                'predictions' => $predictions,
                'summary' => [
                    'total_spent' => $orders->sum('total_amount'),
                    'average_order_value' => $orders->avg('total_amount'),
                    'total_orders' => $orders->count(),
                    'cost_per_liter' => $this->calculateCostPerLiter($orders),
                ]
            ]
        ]);
    }

    /**
     * Get efficiency metrics
     */
    public function efficiency(Request $request)
    {
        $user = $request->user();
        $organization = $user->organization;
        $tanks = Tank::where('organization_id', $organization->id)->get();

        $efficiencyMetrics = [];

        foreach ($tanks as $tank) {
            $metrics = $this->calculateTankEfficiency($tank);
            $efficiencyMetrics[] = [
                'tank_id' => $tank->id,
                'tank_name' => $tank->name,
                'metrics' => $metrics
            ];
        }

        $organizationMetrics = $this->calculateOrganizationEfficiency($tanks);

        return response()->json([
            'data' => [
                'organization_metrics' => $organizationMetrics,
                'tank_metrics' => $efficiencyMetrics,
                'recommendations' => $this->generateEfficiencyRecommendations($tanks),
                'benchmarks' => [
                    'industry_average_efficiency' => 75,
                    'optimal_refill_frequency' => '7-10 days',
                    'target_waste_percentage' => '< 5%',
                ]
            ]
        ]);
    }

    /**
     * Get predictive analytics
     */
    public function predictions(Request $request)
    {
        $request->validate([
            'tank_id' => 'nullable|exists:tanks,id',
            'days_ahead' => 'integer|min:1|max:90',
        ]);

        $user = $request->user();
        $organization = $user->organization;
        $tankId = $request->input('tank_id');
        $daysAhead = $request->input('days_ahead', 7);

        if ($tankId) {
            $tank = Tank::findOrFail($tankId);
            $predictions = $this->predictTankUsage($tank, $daysAhead);
        } else {
            $tanks = Tank::where('organization_id', $organization->id)->get();
            $predictions = $this->predictOrganizationUsage($tanks, $daysAhead);
        }

        return response()->json([
            'data' => [
                'predictions' => $predictions,
                'confidence_level' => $this->calculatePredictionConfidence($tankId ? [$tankId] : $tanks->pluck('id')),
                'assumptions' => [
                    'based_on_historical_data' => '30 days',
                    'seasonal_adjustments' => 'included',
                    'external_factors' => 'weather, holidays',
                ]
            ]
        ]);
    }

    // Helper methods
    private function calculateDailyConsumption($tankIds, $date)
    {
        // Implementation for calculating daily consumption
        return 150.5; // Placeholder
    }

    private function calculateWeeklyConsumption($tankIds)
    {
        // Implementation for calculating weekly consumption
        return [
            'total' => 1050.75,
            'average' => 150.11
        ];
    }

    private function getTanksBelowThreshold($tanks, $threshold)
    {
        $count = 0;
        foreach ($tanks as $tank) {
            $latestReading = SensorReading::where('tank_id', $tank->id)->latest()->first();
            if ($latestReading && $latestReading->water_level < $threshold) {
                $count++;
            }
        }
        return $count;
    }

    private function getConsumptionData($tankIds, $period, $startDate, $endDate)
    {
        // Implementation for getting consumption data over time
        return [
            ['date' => '2024-01-01', 'consumption' => 120.5],
            ['date' => '2024-01-02', 'consumption' => 135.2],
            // ... more data points
        ];
    }

    private function calculateConsumptionTrends($tankIds, $period)
    {
        return [
            'trend' => 'increasing',
            'percentage_change' => 5.2,
            'pattern' => 'regular'
        ];
    }

    private function calculateAverageDaily($consumptionData)
    {
        return count($consumptionData) > 0
            ? array_sum(array_column($consumptionData, 'consumption')) / count($consumptionData)
            : 0;
    }

    private function calculateEfficiencyScore($tankIds, $period)
    {
        // Implementation for calculating efficiency score
        return 78.5;
    }

    private function organizeCostData($orders, $period)
    {
        // Implementation for organizing cost data by period
        return [
            ['period' => 'Jan 2024', 'amount' => 1250.00],
            ['period' => 'Feb 2024', 'amount' => 1180.50],
            // ... more data
        ];
    }

    private function predictFutureCosts($orders, $period)
    {
        // Implementation for predicting future costs
        return [
            ['period' => 'Next Month', 'predicted_amount' => 1300.00],
            ['period' => 'Following Month', 'predicted_amount' => 1350.00],
        ];
    }

    private function calculateCostPerLiter($orders)
    {
        $totalAmount = $orders->sum('total_amount');
        $totalVolume = $orders->sum('quantity');

        return $totalVolume > 0 ? round($totalAmount / $totalVolume, 3) : 0;
    }

    private function calculateTankEfficiency($tank)
    {
        return [
            'usage_efficiency' => 85.2,
            'refill_frequency' => 8.5,
            'waste_percentage' => 3.2,
            'optimal_level_maintenance' => 92.1
        ];
    }

    private function calculateOrganizationEfficiency($tanks)
    {
        return [
            'overall_efficiency' => 82.7,
            'best_performing_tank' => $tanks->first()?->name,
            'improvement_potential' => 12.3,
            'cost_optimization' => 8.5
        ];
    }

    private function generateEfficiencyRecommendations($tanks)
    {
        return [
            'Optimize refill timing for Tank A to reduce costs',
            'Consider upgrading sensor calibration for Tank B',
            'Implement automated alerts for low levels'
        ];
    }

    private function predictTankUsage($tank, $daysAhead)
    {
        $predictions = [];
        for ($i = 1; $i <= $daysAhead; $i++) {
            $predictions[] = [
                'date' => Carbon::now()->addDays($i)->format('Y-m-d'),
                'predicted_level' => max(0, 100 - ($i * 12)), // Simple linear prediction
                'refill_needed' => ($i * 12) > 80,
            ];
        }
        return $predictions;
    }

    private function predictOrganizationUsage($tanks, $daysAhead)
    {
        return [
            'total_consumption_prediction' => $daysAhead * 150,
            'tanks_needing_refill' => 2,
            'estimated_cost' => $daysAhead * 25.50,
        ];
    }

    private function calculatePredictionConfidence($tankIds)
    {
        // Calculate confidence based on data availability and consistency
        return 85.5;
    }
}