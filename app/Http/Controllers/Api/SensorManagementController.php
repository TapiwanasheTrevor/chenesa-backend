<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\Tank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SensorManagementController extends Controller
{
    /**
     * GET /api/sensors - Get all sensors for the organization
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $query = Sensor::whereHas('tank', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->with(['tank', 'latestReading']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('is_active', $request->status === 'active');
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('tank_id')) {
                $query->where('tank_id', $request->tank_id);
            }

            $sensors = $query->paginate($request->input('per_page', 10));

            $data = $sensors->getCollection()->map(function ($sensor) {
                $latestReading = $sensor->latestReading;

                return [
                    'id' => $sensor->id,
                    'name' => $sensor->name,
                    'device_id' => $sensor->device_id,
                    'type' => $sensor->type,
                    'manufacturer' => $sensor->manufacturer,
                    'model' => $sensor->model,
                    'is_active' => $sensor->is_active,
                    'tank' => [
                        'id' => $sensor->tank->id,
                        'name' => $sensor->tank->name,
                        'location' => $sensor->tank->location,
                    ],
                    'status' => $this->getSensorStatus($sensor),
                    'latest_reading' => $latestReading ? [
                        'timestamp' => $latestReading->created_at->toISOString(),
                        'water_level_percentage' => $latestReading->water_level_percentage,
                        'battery_level' => $latestReading->battery_level,
                        'temperature' => $latestReading->temperature,
                    ] : null,
                    'created_at' => $sensor->created_at,
                ];
            });

            return response()->json([
                'data' => $data,
                'pagination' => [
                    'current_page' => $sensors->currentPage(),
                    'total' => $sensors->total(),
                    'per_page' => $sensors->perPage(),
                    'last_page' => $sensors->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch sensors',
                'error' => 'Unable to retrieve sensor data'
            ], 500);
        }
    }

    /**
     * POST /api/sensors - Create/register a new sensor
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $validator = Validator::make($request->all(), [
                'tank_id' => 'required|uuid|exists:tanks,id',
                'name' => 'required|string|max:255',
                'device_id' => 'required|string|max:255|unique:sensors,device_id',
                'type' => ['required', Rule::in(['ultrasonic', 'pressure', 'float', 'radar'])],
                'manufacturer' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'installation_height_mm' => 'nullable|integer|min:0',
                'config' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify tank belongs to user's organization
            $tank = Tank::where('id', $request->tank_id)
                ->where('organization_id', $organizationId)
                ->first();

            if (!$tank) {
                return response()->json([
                    'message' => 'Tank not found or access denied'
                ], 404);
            }

            // Check if tank already has a sensor
            if ($tank->sensor) {
                return response()->json([
                    'message' => 'Tank already has a sensor assigned',
                    'error' => 'Please remove existing sensor first'
                ], 400);
            }

            $sensor = Sensor::create([
                'tank_id' => $request->tank_id,
                'name' => $request->name,
                'device_id' => $request->device_id,
                'type' => $request->type,
                'manufacturer' => $request->manufacturer,
                'model' => $request->model,
                'installation_height_mm' => $request->installation_height_mm ?? 0,
                'is_active' => true,
                'config' => $request->config ?? [],
            ]);

            return response()->json([
                'message' => 'Sensor created successfully',
                'data' => $sensor->load('tank')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create sensor',
                'error' => 'Unable to register sensor'
            ], 500);
        }
    }

    /**
     * GET /api/sensors/{id} - Get detailed sensor information
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $sensor = Sensor::with(['tank', 'latestReading'])
                ->whereHas('tank', function($q) use ($organizationId) {
                    $q->where('organization_id', $organizationId);
                })
                ->findOrFail($id);

            // Get recent readings
            $recentReadings = SensorReading::where('sensor_id', $sensor->id)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($reading) {
                    return [
                        'timestamp' => $reading->created_at->toISOString(),
                        'water_level_percentage' => $reading->water_level_percentage,
                        'volume_liters' => $reading->volume_liters,
                        'distance' => $reading->distance,
                        'temperature' => $reading->temperature,
                        'battery_level' => $reading->battery_level,
                        'signal_strength' => $reading->signal_strength,
                    ];
                });

            // Calculate sensor statistics
            $stats = $this->calculateSensorStats($sensor);

            $data = [
                'id' => $sensor->id,
                'name' => $sensor->name,
                'device_id' => $sensor->device_id,
                'type' => $sensor->type,
                'manufacturer' => $sensor->manufacturer,
                'model' => $sensor->model,
                'installation_height_mm' => $sensor->installation_height_mm,
                'is_active' => $sensor->is_active,
                'config' => $sensor->config,
                'tank' => [
                    'id' => $sensor->tank->id,
                    'name' => $sensor->tank->name,
                    'location' => $sensor->tank->location,
                    'capacity_liters' => $sensor->tank->capacity_liters,
                ],
                'status' => $this->getSensorStatus($sensor),
                'latest_reading' => $sensor->latestReading ? [
                    'timestamp' => $sensor->latestReading->created_at->toISOString(),
                    'water_level_percentage' => $sensor->latestReading->water_level_percentage,
                    'volume_liters' => $sensor->latestReading->volume_liters,
                    'distance' => $sensor->latestReading->distance,
                    'temperature' => $sensor->latestReading->temperature,
                    'battery_level' => $sensor->latestReading->battery_level,
                ] : null,
                'recent_readings' => $recentReadings,
                'statistics' => $stats,
                'created_at' => $sensor->created_at,
                'updated_at' => $sensor->updated_at,
            ];

            return response()->json(['data' => $data]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sensor not found',
                'error' => 'Unable to retrieve sensor details'
            ], 404);
        }
    }

    /**
     * PUT /api/sensors/{id} - Update sensor details
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $sensor = Sensor::whereHas('tank', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'device_id' => 'sometimes|required|string|max:255|unique:sensors,device_id,' . $id,
                'type' => ['sometimes', 'required', Rule::in(['ultrasonic', 'pressure', 'float', 'radar'])],
                'manufacturer' => 'sometimes|required|string|max:255',
                'model' => 'sometimes|required|string|max:255',
                'installation_height_mm' => 'sometimes|nullable|integer|min:0',
                'is_active' => 'sometimes|boolean',
                'config' => 'sometimes|nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sensor->update($validator->validated());

            return response()->json([
                'message' => 'Sensor updated successfully',
                'data' => $sensor->fresh()->load('tank')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update sensor',
                'error' => 'Unable to update sensor details'
            ], 500);
        }
    }

    /**
     * DELETE /api/sensors/{id} - Remove sensor
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $sensor = Sensor::whereHas('tank', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->findOrFail($id);

            // Check if sensor has readings
            $hasReadings = $sensor->sensorReadings()->exists();

            if ($hasReadings && !$request->input('force', false)) {
                return response()->json([
                    'message' => 'Sensor has historical data',
                    'error' => 'Use force=true to delete sensor with readings',
                    'readings_count' => $sensor->sensorReadings()->count()
                ], 400);
            }

            // Delete readings if force delete
            if ($hasReadings) {
                $sensor->sensorReadings()->delete();
            }

            $sensor->delete();

            return response()->json([
                'message' => 'Sensor deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete sensor',
                'error' => 'Unable to remove sensor'
            ], 500);
        }
    }

    /**
     * GET /api/sensors/{id}/readings - Get sensor readings with filtering
     */
    public function getReadings(Request $request, $id)
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $sensor = Sensor::whereHas('tank', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'from' => 'nullable|date',
                'to' => 'nullable|date|after_or_equal:from',
                'interval' => ['nullable', Rule::in(['minute', 'hour', 'day'])],
                'limit' => 'nullable|integer|min:1|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = SensorReading::where('sensor_id', $sensor->id);

            // Apply date filters
            if ($request->has('from')) {
                $query->where('created_at', '>=', $request->from);
            }

            if ($request->has('to')) {
                $query->where('created_at', '<=', $request->to);
            }

            // Apply interval grouping if specified
            $interval = $request->input('interval', 'hour');
            $limit = $request->input('limit', 100);

            $readings = $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($reading) {
                    return [
                        'timestamp' => $reading->created_at->toISOString(),
                        'water_level_percentage' => $reading->water_level_percentage,
                        'volume_liters' => $reading->volume_liters,
                        'distance' => $reading->distance,
                        'temperature' => $reading->temperature,
                        'humidity' => $reading->humidity,
                        'battery_level' => $reading->battery_level,
                        'signal_strength' => $reading->signal_strength,
                    ];
                });

            return response()->json([
                'data' => $readings,
                'metadata' => [
                    'sensor_id' => $sensor->id,
                    'sensor_name' => $sensor->name,
                    'total_readings' => $readings->count(),
                    'period' => [
                        'from' => $request->from,
                        'to' => $request->to,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch readings',
                'error' => 'Unable to retrieve sensor readings'
            ], 500);
        }
    }

    /**
     * POST /api/sensors/{id}/test - Test sensor connectivity
     */
    public function testSensor(Request $request, $id)
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $sensor = Sensor::whereHas('tank', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->findOrFail($id);

            // TODO: Implement actual sensor testing logic
            // This would typically involve:
            // 1. Sending a test command to the sensor
            // 2. Waiting for response
            // 3. Validating sensor functionality

            $testResult = [
                'sensor_id' => $sensor->id,
                'test_timestamp' => now()->toISOString(),
                'connectivity' => $this->testConnectivity($sensor),
                'last_reading_age' => $this->getLastReadingAge($sensor),
                'battery_status' => $this->getBatteryStatus($sensor),
                'signal_quality' => $this->getSignalQuality($sensor),
                'overall_status' => 'online', // online, offline, warning
                'recommendations' => $this->getSensorRecommendations($sensor),
            ];

            return response()->json([
                'message' => 'Sensor test completed',
                'data' => $testResult
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to test sensor',
                'error' => 'Unable to perform sensor test'
            ], 500);
        }
    }

    /**
     * PATCH /api/sensors/{id}/settings - Update sensor configuration
     */
    public function updateSettings(Request $request, $id)
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $sensor = Sensor::whereHas('tank', function($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            })->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'reading_interval' => 'nullable|integer|min:1|max:3600', // seconds
                'calibration_offset' => 'nullable|numeric',
                'temperature_unit' => ['nullable', Rule::in(['celsius', 'fahrenheit'])],
                'alert_enabled' => 'nullable|boolean',
                'low_battery_threshold' => 'nullable|integer|min:0|max:100',
                'config' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update sensor configuration
            $currentConfig = $sensor->config ?? [];
            $newConfig = array_merge($currentConfig, $request->only([
                'reading_interval',
                'calibration_offset',
                'temperature_unit',
                'alert_enabled',
                'low_battery_threshold'
            ]));

            if ($request->has('config')) {
                $newConfig = array_merge($newConfig, $request->config);
            }

            $sensor->update(['config' => $newConfig]);

            return response()->json([
                'message' => 'Sensor settings updated successfully',
                'data' => $sensor->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update sensor settings',
                'error' => 'Unable to save sensor configuration'
            ], 500);
        }
    }

    // Helper methods

    private function getSensorStatus($sensor)
    {
        $latestReading = $sensor->latestReading;

        if (!$latestReading) {
            return [
                'status' => 'no_data',
                'message' => 'No readings available',
                'last_seen' => null
            ];
        }

        $lastReadingAge = now()->diffInMinutes($latestReading->created_at);

        if ($lastReadingAge > 60) {
            return [
                'status' => 'offline',
                'message' => 'Sensor offline - no recent readings',
                'last_seen' => $latestReading->created_at->toISOString()
            ];
        } elseif ($lastReadingAge > 15) {
            return [
                'status' => 'warning',
                'message' => 'Sensor reporting delayed',
                'last_seen' => $latestReading->created_at->toISOString()
            ];
        } else {
            return [
                'status' => 'online',
                'message' => 'Sensor reporting normally',
                'last_seen' => $latestReading->created_at->toISOString()
            ];
        }
    }

    private function calculateSensorStats($sensor)
    {
        $readings = SensorReading::where('sensor_id', $sensor->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($readings->isEmpty()) {
            return ['message' => 'No data available for statistics'];
        }

        return [
            'total_readings_7d' => $readings->count(),
            'average_battery_level' => round($readings->whereNotNull('battery_level')->avg('battery_level'), 2),
            'data_reliability' => round(($readings->count() / (7 * 24)) * 100, 2), // Expected vs actual readings
            'temperature_range' => [
                'min' => $readings->whereNotNull('temperature')->min('temperature'),
                'max' => $readings->whereNotNull('temperature')->max('temperature'),
                'avg' => round($readings->whereNotNull('temperature')->avg('temperature'), 2),
            ],
        ];
    }

    private function testConnectivity($sensor)
    {
        // Placeholder implementation
        $latestReading = $sensor->latestReading;
        return $latestReading && $latestReading->created_at->gt(now()->subHour());
    }

    private function getLastReadingAge($sensor)
    {
        $latestReading = $sensor->latestReading;
        return $latestReading ? now()->diffInMinutes($latestReading->created_at) : null;
    }

    private function getBatteryStatus($sensor)
    {
        $latestReading = $sensor->latestReading;
        $batteryLevel = $latestReading?->battery_level;

        if (!$batteryLevel) return 'unknown';

        if ($batteryLevel < 20) return 'critical';
        if ($batteryLevel < 40) return 'low';
        return 'good';
    }

    private function getSignalQuality($sensor)
    {
        $latestReading = $sensor->latestReading;
        $signalStrength = $latestReading?->signal_strength;

        if (!$signalStrength) return 'unknown';

        if ($signalStrength > 80) return 'excellent';
        if ($signalStrength > 60) return 'good';
        if ($signalStrength > 40) return 'fair';
        return 'poor';
    }

    private function getSensorRecommendations($sensor)
    {
        $recommendations = [];
        $latestReading = $sensor->latestReading;

        if (!$latestReading) {
            $recommendations[] = 'No recent readings - check sensor connectivity';
            return $recommendations;
        }

        if ($latestReading->battery_level < 20) {
            $recommendations[] = 'Battery level critical - replace battery soon';
        }

        if (now()->diffInMinutes($latestReading->created_at) > 30) {
            $recommendations[] = 'Delayed readings - check network connectivity';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Sensor operating normally';
        }

        return $recommendations;
    }
}