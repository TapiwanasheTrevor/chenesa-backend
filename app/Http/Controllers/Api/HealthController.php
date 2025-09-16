<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tank;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\Alert;
use App\Models\WaterOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    /**
     * GET /api/health - Return system health status
     * This endpoint can be accessed without authentication for monitoring purposes
     */
    public function check(Request $request): JsonResponse
    {
        try {
            $startTime = microtime(true);

            // Cache health check results for 1 minute to avoid excessive database queries
            $healthData = Cache::remember('system_health', 60, function () {
                return $this->performHealthChecks();
            });

            $responseTime = round((microtime(true) - $startTime) * 1000, 2); // Convert to milliseconds

            return response()->json([
                'status' => $healthData['overall_status'],
                'timestamp' => now()->toISOString(),
                'response_time_ms' => $responseTime,
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
                'checks' => $healthData['checks'],
                'system_stats' => $healthData['system_stats'],
            ], $healthData['overall_status'] === 'healthy' ? 200 : 503);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'timestamp' => now()->toISOString(),
                'error' => 'Health check failed',
                'message' => config('app.debug') ? $e->getMessage() : 'System temporarily unavailable',
            ], 503);
        }
    }

    private function performHealthChecks(): array
    {
        $checks = [];
        $overallHealthy = true;

        // Database connectivity check
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'response_time_ms' => $this->measureDbResponseTime(),
            ];
        } catch (\Exception $e) {
            $checks['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Connection error',
            ];
            $overallHealthy = false;
        }

        // Cache system check
        try {
            Cache::put('health_check_test', 'test', 10);
            $testValue = Cache::get('health_check_test');
            Cache::forget('health_check_test');

            $checks['cache'] = [
                'status' => $testValue === 'test' ? 'healthy' : 'unhealthy',
                'message' => $testValue === 'test' ? 'Cache system operational' : 'Cache system malfunction',
            ];

            if ($testValue !== 'test') {
                $overallHealthy = false;
            }
        } catch (\Exception $e) {
            $checks['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache system error',
                'error' => config('app.debug') ? $e->getMessage() : 'Cache error',
            ];
            $overallHealthy = false;
        }

        // Application-specific health checks
        $checks['sensors'] = $this->checkSensorsHealth();
        $checks['recent_data'] = $this->checkRecentDataHealth();
        $checks['critical_alerts'] = $this->checkCriticalAlertsHealth();

        // If any application check is unhealthy, mark overall as degraded instead of unhealthy
        foreach (['sensors', 'recent_data', 'critical_alerts'] as $check) {
            if ($checks[$check]['status'] === 'unhealthy') {
                $overallHealthy = 'degraded';
                break;
            }
        }

        // System statistics
        $systemStats = $this->getSystemStats();

        return [
            'overall_status' => $overallHealthy === true ? 'healthy' : ($overallHealthy === 'degraded' ? 'degraded' : 'unhealthy'),
            'checks' => $checks,
            'system_stats' => $systemStats,
        ];
    }

    private function measureDbResponseTime(): float
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        return round((microtime(true) - $start) * 1000, 2);
    }

    private function checkSensorsHealth(): array
    {
        try {
            $totalSensors = Sensor::count();
            $onlineSensors = Sensor::where('status', 'active')
                ->where('last_seen', '>', now()->subMinutes(30))
                ->count();

            $offlineSensors = $totalSensors - $onlineSensors;
            $offlinePercentage = $totalSensors > 0 ? ($offlineSensors / $totalSensors) * 100 : 0;

            $status = 'healthy';
            $message = "All sensors operational";

            if ($offlinePercentage > 50) {
                $status = 'unhealthy';
                $message = "More than 50% of sensors offline";
            } elseif ($offlinePercentage > 20) {
                $status = 'degraded';
                $message = "Some sensors offline";
            }

            return [
                'status' => $status,
                'message' => $message,
                'total_sensors' => $totalSensors,
                'online_sensors' => $onlineSensors,
                'offline_sensors' => $offlineSensors,
                'offline_percentage' => round($offlinePercentage, 1),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Unable to check sensor status',
                'error' => config('app.debug') ? $e->getMessage() : 'Sensor check failed',
            ];
        }
    }

    private function checkRecentDataHealth(): array
    {
        try {
            $recentReadingsCount = SensorReading::where('created_at', '>', now()->subHour())->count();
            $expectedReadingsPerHour = Sensor::where('status', 'active')->count() * 4; // Assuming 15-minute intervals

            $dataHealthPercentage = $expectedReadingsPerHour > 0 ?
                ($recentReadingsCount / $expectedReadingsPerHour) * 100 : 100;

            $status = 'healthy';
            $message = "Data flow is normal";

            if ($dataHealthPercentage < 50) {
                $status = 'unhealthy';
                $message = "Significant data loss detected";
            } elseif ($dataHealthPercentage < 80) {
                $status = 'degraded';
                $message = "Reduced data flow detected";
            }

            return [
                'status' => $status,
                'message' => $message,
                'recent_readings_count' => $recentReadingsCount,
                'expected_readings' => $expectedReadingsPerHour,
                'data_health_percentage' => round($dataHealthPercentage, 1),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Unable to check data flow',
                'error' => config('app.debug') ? $e->getMessage() : 'Data flow check failed',
            ];
        }
    }

    private function checkCriticalAlertsHealth(): array
    {
        try {
            $criticalAlerts = Alert::where('severity', 'critical')
                ->where('is_resolved', false)
                ->count();

            $status = 'healthy';
            $message = "No critical alerts";

            if ($criticalAlerts > 10) {
                $status = 'unhealthy';
                $message = "High number of critical alerts";
            } elseif ($criticalAlerts > 5) {
                $status = 'degraded';
                $message = "Multiple critical alerts present";
            } elseif ($criticalAlerts > 0) {
                $status = 'healthy';
                $message = "Some critical alerts present";
            }

            return [
                'status' => $status,
                'message' => $message,
                'critical_alerts_count' => $criticalAlerts,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Unable to check alerts',
                'error' => config('app.debug') ? $e->getMessage() : 'Alert check failed',
            ];
        }
    }

    private function getSystemStats(): array
    {
        try {
            return [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'total_organizations' => DB::table('organizations')->count(),
                'total_tanks' => Tank::count(),
                'total_sensors' => Sensor::count(),
                'active_sensors' => Sensor::where('status', 'active')->count(),
                'total_readings_24h' => SensorReading::where('created_at', '>', now()->subDay())->count(),
                'total_orders_pending' => WaterOrder::where('status', 'pending')->count(),
                'total_alerts_unresolved' => Alert::where('is_resolved', false)->count(),
                'uptime_hours' => $this->getUptimeHours(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to retrieve system statistics',
                'message' => config('app.debug') ? $e->getMessage() : 'Stats unavailable',
            ];
        }
    }

    private function getUptimeHours(): float
    {
        // This is a simplified uptime calculation
        // In a real system, you might track this differently
        $uptimeFile = storage_path('app/uptime_start.txt');

        if (file_exists($uptimeFile)) {
            $startTime = (int) file_get_contents($uptimeFile);
        } else {
            $startTime = time();
            file_put_contents($uptimeFile, $startTime);
        }

        return round((time() - $startTime) / 3600, 2);
    }
}
