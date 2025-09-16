<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tank;
use App\Models\SensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TankController extends Controller
{

    /**
     * GET /api/tanks - Return paginated tanks for authenticated user's organization with current sensor data
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $query = Tank::where('organization_id', $organizationId)
                ->with(['sensor', 'latestReading'])
                ->orderBy('name');

            // Apply filters
            if ($request->has('status')) {
                $query->whereHas('latestReading', function ($q) use ($request) {
                    $level = 'water_level_percentage';
                    switch ($request->status) {
                        case 'critical':
                            $q->whereRaw("$level <= (SELECT critical_level_threshold FROM tanks WHERE tanks.id = sensor_readings.tank_id)");
                            break;
                        case 'low':
                            $q->whereRaw("$level <= (SELECT low_level_threshold FROM tanks WHERE tanks.id = sensor_readings.tank_id)")
                              ->whereRaw("$level > (SELECT critical_level_threshold FROM tanks WHERE tanks.id = sensor_readings.tank_id)");
                            break;
                        case 'normal':
                            $q->whereRaw("$level > (SELECT low_level_threshold FROM tanks WHERE tanks.id = sensor_readings.tank_id)");
                            break;
                    }
                });
            }

            if ($request->has('location')) {
                $query->where('location', 'like', '%' . $request->location . '%');
            }

            $perPage = min($request->get('per_page', 10), 50);
            $tanks = $query->paginate($perPage);

            $data = $tanks->map(function ($tank) {
                $latestReading = $tank->latestReading;

                return [
                    'id' => $tank->id,
                    'name' => $tank->name,
                    'location' => $tank->location,
                    'capacity_liters' => $tank->capacity_liters,
                    'current_level' => $latestReading?->water_level_percentage ?? 0,
                    'current_volume' => $latestReading?->volume_liters ?? 0,
                    'status' => $tank->status,
                    'last_reading_at' => $latestReading?->created_at?->toISOString(),
                    'sensor' => $tank->sensor ? [
                        'id' => $tank->sensor->id,
                        'device_id' => $tank->sensor->device_id,
                        'status' => $tank->sensor->status,
                        'battery_level' => $tank->sensor->battery_level,
                        'signal_strength' => $tank->sensor->signal_strength,
                        'last_seen' => $tank->sensor->last_seen?->toISOString(),
                    ] : null,
                    'settings' => [
                        'low_level_threshold' => $tank->low_level_threshold,
                        'critical_level_threshold' => $tank->critical_level_threshold,
                        'refill_enabled' => $tank->refill_enabled,
                        'auto_refill_threshold' => $tank->auto_refill_threshold,
                    ],
                    'created_at' => $tank->created_at->toISOString(),
                    'updated_at' => $tank->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'data' => $data,
                'pagination' => [
                    'current_page' => $tanks->currentPage(),
                    'per_page' => $tanks->perPage(),
                    'total' => $tanks->total(),
                    'last_page' => $tanks->lastPage(),
                    'from' => $tanks->firstItem(),
                    'to' => $tanks->lastItem(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch tanks',
                'error' => 'Unable to retrieve tank data'
            ], 500);
        }
    }

    /**
     * GET /api/tanks/{id} - Return detailed tank info with recent readings and statistics
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $tank = Tank::where('id', $id)
                ->where('organization_id', $organizationId)
                ->with(['sensor', 'latestReading'])
                ->first();

            if (!$tank) {
                return response()->json([
                    'message' => 'Tank not found'
                ], 404);
            }

            // Get recent readings (last 24 hours)
            $recentReadings = SensorReading::where('tank_id', $tank->id)
                ->where('created_at', '>=', now()->subDay())
                ->orderBy('created_at', 'desc')
                ->limit(24)
                ->get()
                ->map(function ($reading) {
                    return [
                        'timestamp' => $reading->created_at->toISOString(),
                        'water_level_percentage' => $reading->water_level_percentage,
                        'volume_liters' => $reading->volume_liters,
                        'temperature' => $reading->temperature,
                    ];
                });

            // Get statistics
            $stats = [
                'avg_level_24h' => SensorReading::where('tank_id', $tank->id)
                    ->where('created_at', '>=', now()->subDay())
                    ->avg('water_level_percentage') ?? 0,
                'avg_level_7d' => SensorReading::where('tank_id', $tank->id)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->avg('water_level_percentage') ?? 0,
                'min_level_24h' => SensorReading::where('tank_id', $tank->id)
                    ->where('created_at', '>=', now()->subDay())
                    ->min('water_level_percentage') ?? 0,
                'max_level_24h' => SensorReading::where('tank_id', $tank->id)
                    ->where('created_at', '>=', now()->subDay())
                    ->max('water_level_percentage') ?? 0,
            ];

            $latestReading = $tank->latestReading;

            return response()->json([
                'id' => $tank->id,
                'name' => $tank->name,
                'location' => $tank->location,
                'latitude' => $tank->latitude,
                'longitude' => $tank->longitude,
                'capacity_liters' => $tank->capacity_liters,
                'height_mm' => $tank->height_mm,
                'diameter_mm' => $tank->diameter_mm,
                'shape' => $tank->shape,
                'material' => $tank->material,
                'installation_height_mm' => $tank->installation_height_mm,
                'current_level' => $latestReading?->water_level_percentage ?? 0,
                'current_volume' => $latestReading?->volume_liters ?? 0,
                'status' => $tank->status,
                'last_reading_at' => $latestReading?->created_at?->toISOString(),
                'sensor' => $tank->sensor ? [
                    'id' => $tank->sensor->id,
                    'device_id' => $tank->sensor->device_id,
                    'imei' => $tank->sensor->imei,
                    'sim_number' => $tank->sensor->sim_number,
                    'model' => $tank->sensor->model,
                    'firmware_version' => $tank->sensor->firmware_version,
                    'status' => $tank->sensor->status,
                    'battery_level' => $tank->sensor->battery_level,
                    'signal_strength' => $tank->sensor->signal_strength,
                    'last_seen' => $tank->sensor->last_seen?->toISOString(),
                    'installation_date' => $tank->sensor->installation_date?->toDateString(),
                ] : null,
                'settings' => [
                    'low_level_threshold' => $tank->low_level_threshold,
                    'critical_level_threshold' => $tank->critical_level_threshold,
                    'refill_enabled' => $tank->refill_enabled,
                    'auto_refill_threshold' => $tank->auto_refill_threshold,
                ],
                'recent_readings' => $recentReadings,
                'statistics' => $stats,
                'created_at' => $tank->created_at->toISOString(),
                'updated_at' => $tank->updated_at->toISOString(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch tank details',
                'error' => 'Unable to retrieve tank data'
            ], 500);
        }
    }

    /**
     * GET /api/tanks/{id}/history - Return paginated sensor readings with date filtering
     */
    public function history(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            // Verify tank belongs to user's organization
            $tank = Tank::where('id', $id)
                ->where('organization_id', $organizationId)
                ->first();

            if (!$tank) {
                return response()->json([
                    'message' => 'Tank not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = SensorReading::where('tank_id', $tank->id)
                ->orderBy('created_at', 'desc');

            // Apply date filters
            if ($request->has('start_date')) {
                $query->where('created_at', '>=', $request->start_date);
            }
            if ($request->has('end_date')) {
                $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
            }

            // If no date filters, default to last 7 days
            if (!$request->has('start_date') && !$request->has('end_date')) {
                $query->where('created_at', '>=', now()->subDays(7));
            }

            $perPage = min($request->get('per_page', 50), 100);
            $readings = $query->paginate($perPage);

            $data = $readings->map(function ($reading) {
                return [
                    'id' => $reading->id,
                    'timestamp' => $reading->created_at->toISOString(),
                    'distance_mm' => $reading->distance_mm,
                    'water_level_mm' => $reading->water_level_mm,
                    'water_level_percentage' => $reading->water_level_percentage,
                    'volume_liters' => $reading->volume_liters,
                    'temperature' => $reading->temperature,
                    'battery_voltage' => $reading->battery_voltage,
                    'signal_rssi' => $reading->signal_rssi,
                ];
            });

            return response()->json([
                'data' => $data,
                'pagination' => [
                    'current_page' => $readings->currentPage(),
                    'per_page' => $readings->perPage(),
                    'total' => $readings->total(),
                    'last_page' => $readings->lastPage(),
                    'from' => $readings->firstItem(),
                    'to' => $readings->lastItem(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch tank history',
                'error' => 'Unable to retrieve historical data'
            ], 500);
        }
    }

    /**
     * PATCH /api/tanks/{id}/settings - Update tank settings
     */
    public function updateSettings(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $tank = Tank::where('id', $id)
                ->where('organization_id', $organizationId)
                ->first();

            if (!$tank) {
                return response()->json([
                    'message' => 'Tank not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'low_level_threshold' => 'nullable|integer|min:0|max:100',
                'critical_level_threshold' => 'nullable|integer|min:0|max:100|lt:low_level_threshold',
                'refill_enabled' => 'nullable|boolean',
                'auto_refill_threshold' => 'nullable|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update only provided fields
            $updateData = [];
            if ($request->has('low_level_threshold')) {
                $updateData['low_level_threshold'] = $request->low_level_threshold;
            }
            if ($request->has('critical_level_threshold')) {
                $updateData['critical_level_threshold'] = $request->critical_level_threshold;
            }
            if ($request->has('refill_enabled')) {
                $updateData['refill_enabled'] = $request->refill_enabled;
            }
            if ($request->has('auto_refill_threshold')) {
                $updateData['auto_refill_threshold'] = $request->auto_refill_threshold;
            }

            $tank->update($updateData);

            return response()->json([
                'message' => 'Tank settings updated successfully',
                'settings' => [
                    'low_level_threshold' => $tank->low_level_threshold,
                    'critical_level_threshold' => $tank->critical_level_threshold,
                    'refill_enabled' => $tank->refill_enabled,
                    'auto_refill_threshold' => $tank->auto_refill_threshold,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update tank settings',
                'error' => 'Unable to save settings'
            ], 500);
        }
    }
}
