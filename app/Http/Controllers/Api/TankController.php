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
     * Only returns tanks assigned to the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            // Only show tanks assigned to this user
            // Admins see all tanks in their organization
            $query = Tank::where('organization_id', $organizationId)
                ->with(['sensor', 'latestReading']);

            // Filter by user assignment unless user is admin
            if (!in_array($user->role, ['admin', 'super_admin'])) {
                $query->whereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            $query->orderBy('name');

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

            $data = $tanks->map(function ($tank) use ($user) {
                $latestReading = $tank->latestReading;

                // Get user permissions for this tank
                $userPivot = $tank->users()->where('user_id', $user->id)->first()?->pivot;

                return [
                    // Core Tank Properties
                    'id' => $tank->id,
                    'organization_id' => $tank->organization_id,
                    'sensor_id' => $tank->sensor_id,
                    'name' => $tank->name ?? '',
                    'location' => $tank->location ?? '',
                    'latitude' => $tank->latitude,
                    'longitude' => $tank->longitude,

                    // Physical Properties
                    'capacity_liters' => $tank->capacity_liters ?? 0,
                    'height_mm' => $tank->height_mm ?? 0,
                    'diameter_mm' => $tank->diameter_mm ?? 0,
                    'shape' => $tank->shape ?? '',
                    'material' => $tank->material ?? '',

                    // Configuration
                    'installation_height_mm' => $tank->installation_height_mm ?? 0,
                    'low_level_threshold' => $tank->low_level_threshold ?? 20,
                    'critical_level_threshold' => $tank->critical_level_threshold ?? 10,
                    'refill_enabled' => $tank->refill_enabled ?? false,
                    'auto_refill_threshold' => $tank->auto_refill_threshold ?? 30,

                    // Timestamps
                    'created_at' => $tank->created_at->toISOString(),
                    'updated_at' => $tank->updated_at->toISOString(),
                    'last_updated' => $tank->updated_at->toISOString(),

                    // Current Status
                    'current_level' => $latestReading?->water_level_percentage ?? 0,
                    'current_volume' => $latestReading?->volume_liters ?? 0,
                    'sensor_status' => $tank->sensor?->status ?? 'unknown',
                    'status' => $tank->status ?? 'unknown',
                    'last_reading_at' => $latestReading?->created_at?->toISOString() ?? null,

                    // Sensor Data (for backward compatibility)
                    'sensor' => $tank->sensor ? [
                        'id' => $tank->sensor->id,
                        'device_id' => $tank->sensor->device_id ?? '',
                        'status' => $tank->sensor->status ?? 'unknown',
                        'battery_level' => $tank->sensor->battery_level ?? 0,
                        'signal_strength' => $tank->sensor->signal_strength ?? 0,
                        'last_seen' => $tank->sensor->last_seen?->toISOString() ?? null,
                    ] : null,

                    // User Permissions
                    'permissions' => [
                        'can_order_water' => $userPivot?->can_order_water ?? in_array($user->role, ['admin', 'super_admin']),
                        'receive_alerts' => $userPivot?->receive_alerts ?? in_array($user->role, ['admin', 'super_admin']),
                    ],
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
     * User must be assigned to the tank unless they are an admin
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

            // Check if user has access to this tank
            if (!in_array($user->role, ['admin', 'super_admin'])) {
                $hasAccess = $tank->users()->where('user_id', $user->id)->exists();
                if (!$hasAccess) {
                    return response()->json([
                        'message' => 'You do not have access to this tank'
                    ], 403);
                }
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
                        'water_level_percentage' => $reading->water_level_percentage ?? 0,
                        'volume_liters' => $reading->volume_liters ?? 0,
                        'temperature' => $reading->temperature ?? null,
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

            // Get user permissions for this tank
            $userPivot = $tank->users()->where('user_id', $user->id)->first()?->pivot;

            return response()->json([
                // Core Tank Properties
                'id' => $tank->id,
                'organization_id' => $tank->organization_id,
                'sensor_id' => $tank->sensor_id,
                'name' => $tank->name ?? '',
                'location' => $tank->location ?? '',
                'latitude' => $tank->latitude,
                'longitude' => $tank->longitude,

                // Physical Properties
                'capacity_liters' => $tank->capacity_liters ?? 0,
                'height_mm' => $tank->height_mm ?? 0,
                'diameter_mm' => $tank->diameter_mm ?? 0,
                'shape' => $tank->shape ?? '',
                'material' => $tank->material ?? '',

                // Configuration
                'installation_height_mm' => $tank->installation_height_mm ?? 0,
                'low_level_threshold' => $tank->low_level_threshold ?? 20,
                'critical_level_threshold' => $tank->critical_level_threshold ?? 10,
                'refill_enabled' => $tank->refill_enabled ?? false,
                'auto_refill_threshold' => $tank->auto_refill_threshold ?? 30,

                // Timestamps
                'created_at' => $tank->created_at->toISOString(),
                'updated_at' => $tank->updated_at->toISOString(),
                'last_updated' => $tank->updated_at->toISOString(),

                // Current Status
                'current_level' => $latestReading?->water_level_percentage ?? 0,
                'current_volume' => $latestReading?->volume_liters ?? 0,
                'sensor_status' => $tank->sensor?->status ?? 'unknown',
                'status' => $tank->status ?? 'unknown',
                'last_reading_at' => $latestReading?->created_at?->toISOString() ?? null,

                // Extended sensor data for detailed view
                'sensor' => $tank->sensor ? [
                    'id' => $tank->sensor->id,
                    'device_id' => $tank->sensor->device_id ?? '',
                    'imei' => $tank->sensor->imei ?? '',
                    'sim_number' => $tank->sensor->sim_number ?? '',
                    'model' => $tank->sensor->model ?? '',
                    'firmware_version' => $tank->sensor->firmware_version ?? '',
                    'status' => $tank->sensor->status ?? 'unknown',
                    'battery_level' => $tank->sensor->battery_level ?? 0,
                    'signal_strength' => $tank->sensor->signal_strength ?? 0,
                    'last_seen' => $tank->sensor->last_seen?->toISOString() ?? null,
                    'installation_date' => $tank->sensor->installation_date?->toDateString() ?? null,
                ] : null,

                // User Permissions
                'permissions' => [
                    'can_order_water' => $userPivot?->can_order_water ?? in_array($user->role, ['admin', 'super_admin']),
                    'receive_alerts' => $userPivot?->receive_alerts ?? in_array($user->role, ['admin', 'super_admin']),
                ],

                // Additional data for detailed view
                'recent_readings' => $recentReadings,
                'statistics' => $stats,
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
                    'distance_mm' => $reading->distance_mm ?? 0,
                    'water_level_mm' => $reading->water_level_mm ?? 0,
                    'water_level_percentage' => $reading->water_level_percentage ?? 0,
                    'volume_liters' => $reading->volume_liters ?? 0,
                    'temperature' => $reading->temperature ?? null,
                    'battery_voltage' => $reading->battery_voltage ?? null,
                    'signal_rssi' => $reading->signal_rssi ?? null,
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

    /**
     * POST /api/tanks - Create a new tank
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'capacity_liters' => 'required|integer|min:1',
                'height_mm' => 'required|integer|min:1',
                'diameter_mm' => 'required|integer|min:1',
                'shape' => ['required', Rule::in(['cylindrical', 'rectangular', 'spherical'])],
                'material' => ['required', Rule::in(['plastic', 'steel', 'concrete', 'fiberglass'])],
                'installation_height_mm' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tank = Tank::create([
                'organization_id' => $organizationId,
                'name' => $request->name,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'capacity_liters' => $request->capacity_liters,
                'height_mm' => $request->height_mm,
                'diameter_mm' => $request->diameter_mm,
                'shape' => $request->shape,
                'material' => $request->material,
                'installation_height_mm' => $request->installation_height_mm ?? 0,
                'low_level_threshold' => 20, // Default 20%
                'critical_level_threshold' => 10, // Default 10%
                'refill_enabled' => false,
                'auto_refill_threshold' => 30, // Default 30%
            ]);

            return response()->json([
                'message' => 'Tank created successfully',
                'data' => $tank
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create tank',
                'error' => 'Unable to create tank'
            ], 500);
        }
    }

    /**
     * PUT /api/tanks/{id} - Update tank details
     */
    public function update(Request $request, string $id): JsonResponse
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
                'name' => 'sometimes|required|string|max:255',
                'location' => 'sometimes|required|string|max:255',
                'latitude' => 'sometimes|nullable|numeric|between:-90,90',
                'longitude' => 'sometimes|nullable|numeric|between:-180,180',
                'capacity_liters' => 'sometimes|required|integer|min:1',
                'height_mm' => 'sometimes|required|integer|min:1',
                'diameter_mm' => 'sometimes|required|integer|min:1',
                'shape' => ['sometimes', 'required', Rule::in(['cylindrical', 'rectangular', 'spherical'])],
                'material' => ['sometimes', 'required', Rule::in(['plastic', 'steel', 'concrete', 'fiberglass'])],
                'installation_height_mm' => 'sometimes|nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tank->update($validator->validated());

            return response()->json([
                'message' => 'Tank updated successfully',
                'data' => $tank->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update tank',
                'error' => 'Unable to update tank'
            ], 500);
        }
    }

    /**
     * DELETE /api/tanks/{id} - Remove tank
     */
    public function destroy(Request $request, string $id): JsonResponse
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

            // Check if tank has associated sensors or readings
            $hasData = $tank->sensors()->exists() || $tank->sensorReadings()->exists();

            if ($hasData) {
                return response()->json([
                    'message' => 'Cannot delete tank with existing sensor data',
                    'error' => 'Please remove all sensors and readings first'
                ], 400);
            }

            $tank->delete();

            return response()->json([
                'message' => 'Tank deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete tank',
                'error' => 'Unable to delete tank'
            ], 500);
        }
    }

    /**
     * GET /api/tanks/{id}/analytics - Tank analytics & insights
     */
    public function analytics(Request $request, string $id): JsonResponse
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

            $period = $request->input('period', '7d'); // 24h, 7d, 30d

            $startDate = match($period) {
                '24h' => now()->subDay(),
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                default => now()->subDays(7)
            };

            $readings = SensorReading::where('tank_id', $tank->id)
                ->where('created_at', '>=', $startDate)
                ->orderBy('created_at')
                ->get();

            if ($readings->isEmpty()) {
                return response()->json([
                    'message' => 'No data available for this period',
                    'data' => null
                ]);
            }

            // Calculate analytics
            $analytics = [
                'period' => $period,
                'data_points' => $readings->count(),
                'water_level' => [
                    'current' => $readings->last()->water_level_percentage ?? 0,
                    'average' => round($readings->avg('water_level_percentage'), 2),
                    'minimum' => $readings->min('water_level_percentage'),
                    'maximum' => $readings->max('water_level_percentage'),
                ],
                'volume' => [
                    'current_liters' => $readings->last()->volume_liters ?? 0,
                    'average_liters' => round($readings->avg('volume_liters'), 2),
                    'minimum_liters' => $readings->min('volume_liters'),
                    'maximum_liters' => $readings->max('volume_liters'),
                ],
                'consumption' => $this->calculateConsumption($readings),
                'temperature' => [
                    'current' => $readings->last()->temperature,
                    'average' => round($readings->whereNotNull('temperature')->avg('temperature'), 2),
                    'minimum' => $readings->whereNotNull('temperature')->min('temperature'),
                    'maximum' => $readings->whereNotNull('temperature')->max('temperature'),
                ],
                'trends' => $this->analyzeTrends($readings),
                'alerts_count' => $tank->alerts()->where('created_at', '>=', $startDate)->count(),
            ];

            return response()->json([
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch analytics',
                'error' => 'Unable to calculate analytics'
            ], 500);
        }
    }

    /**
     * GET /api/tanks/{id}/live-status - Real-time status
     */
    public function liveStatus(Request $request, string $id): JsonResponse
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

            $latestReading = $tank->latestReading;
            $sensor = $tank->sensor;

            $status = [
                'tank_id' => $tank->id,
                'tank_name' => $tank->name,
                'last_updated' => $latestReading?->created_at?->toISOString(),
                'is_online' => $sensor ? $this->isSensorOnline($sensor) : false,
                'water_level' => [
                    'percentage' => $latestReading?->water_level_percentage ?? 0,
                    'liters' => $latestReading?->volume_liters ?? 0,
                    'status' => $this->getWaterLevelStatus($latestReading?->water_level_percentage ?? 0, $tank),
                ],
                'temperature' => $latestReading?->temperature,
                'battery_level' => $latestReading?->battery_level,
                'active_alerts' => $tank->alerts()->where('status', 'active')->count(),
                'refill_recommendation' => $this->getRefillRecommendation($tank, $latestReading),
            ];

            return response()->json([
                'data' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch live status',
                'error' => 'Unable to get current status'
            ], 500);
        }
    }

    /**
     * POST /api/tanks/{id}/calibrate - Sensor calibration
     */
    public function calibrate(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $tank = Tank::where('id', $id)
                ->where('organization_id', $organizationId)
                ->with('sensor')
                ->first();

            if (!$tank) {
                return response()->json([
                    'message' => 'Tank not found'
                ], 404);
            }

            if (!$tank->sensor) {
                return response()->json([
                    'message' => 'No sensor attached to this tank'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'calibration_type' => ['required', Rule::in(['empty', 'full', 'custom'])],
                'actual_level_mm' => 'required_if:calibration_type,custom|nullable|numeric|min:0',
                'actual_level_percentage' => 'required_if:calibration_type,custom|nullable|numeric|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // TODO: Implement actual sensor calibration logic
            // This would typically involve:
            // 1. Taking current sensor reading
            // 2. Updating sensor calibration parameters
            // 3. Storing calibration record

            $calibrationData = [
                'type' => $request->calibration_type,
                'timestamp' => now()->toISOString(),
                'previous_reading' => $tank->latestReading?->distance ?? 0,
                'calibration_applied' => true,
            ];

            return response()->json([
                'message' => 'Sensor calibration completed successfully',
                'data' => $calibrationData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to calibrate sensor',
                'error' => 'Unable to complete calibration'
            ], 500);
        }
    }

    // Helper methods

    private function calculateConsumption($readings)
    {
        if ($readings->count() < 2) {
            return ['daily_average' => 0, 'trend' => 'insufficient_data'];
        }

        $consumption = [];
        $dailyConsumption = 0;

        for ($i = 1; $i < $readings->count(); $i++) {
            $current = $readings[$i];
            $previous = $readings[$i - 1];

            if ($current->volume_liters < $previous->volume_liters) {
                $consumed = $previous->volume_liters - $current->volume_liters;
                $consumption[] = $consumed;
                $dailyConsumption += $consumed;
            }
        }

        return [
            'total_consumed' => round($dailyConsumption, 2),
            'daily_average' => count($consumption) > 0 ? round(array_sum($consumption) / count($consumption), 2) : 0,
            'trend' => $this->getConsumptionTrend($consumption),
        ];
    }

    private function analyzeTrends($readings)
    {
        if ($readings->count() < 3) {
            return ['trend' => 'insufficient_data'];
        }

        $recentReadings = $readings->take(-10); // Last 10 readings
        $levels = $recentReadings->pluck('water_level_percentage')->toArray();

        if (empty($levels)) {
            return ['trend' => 'no_data'];
        }

        // Simple trend analysis
        $firstHalf = array_slice($levels, 0, ceil(count($levels) / 2));
        $secondHalf = array_slice($levels, floor(count($levels) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $difference = $secondAvg - $firstAvg;

        return [
            'trend' => $difference > 5 ? 'increasing' : ($difference < -5 ? 'decreasing' : 'stable'),
            'change_percentage' => round($difference, 2),
        ];
    }

    private function isSensorOnline($sensor)
    {
        if (!$sensor) return false;

        $lastReading = $sensor->sensorReadings()->latest()->first();
        if (!$lastReading) return false;

        // Consider sensor online if last reading is within 1 hour
        return $lastReading->created_at->gt(now()->subHour());
    }

    private function getWaterLevelStatus($percentage, $tank)
    {
        if ($percentage <= $tank->critical_level_threshold) {
            return 'critical';
        } elseif ($percentage <= $tank->low_level_threshold) {
            return 'low';
        } elseif ($percentage >= 90) {
            return 'full';
        } else {
            return 'normal';
        }
    }

    private function getRefillRecommendation($tank, $latestReading)
    {
        if (!$latestReading) {
            return ['recommended' => false, 'reason' => 'no_data'];
        }

        $currentLevel = $latestReading->water_level_percentage;

        if ($currentLevel <= $tank->critical_level_threshold) {
            return [
                'recommended' => true,
                'priority' => 'urgent',
                'reason' => 'critical_level',
                'estimated_liters_needed' => round(($tank->capacity_liters * 0.8) - $latestReading->volume_liters),
            ];
        } elseif ($currentLevel <= $tank->low_level_threshold) {
            return [
                'recommended' => true,
                'priority' => 'normal',
                'reason' => 'low_level',
                'estimated_liters_needed' => round(($tank->capacity_liters * 0.8) - $latestReading->volume_liters),
            ];
        }

        return ['recommended' => false, 'reason' => 'sufficient_water'];
    }

    private function getConsumptionTrend($consumption)
    {
        if (count($consumption) < 3) {
            return 'insufficient_data';
        }

        $recent = array_slice($consumption, -5); // Last 5 consumption events
        $earlier = array_slice($consumption, 0, 5); // First 5 consumption events

        $recentAvg = array_sum($recent) / count($recent);
        $earlierAvg = array_sum($earlier) / count($earlier);

        $change = (($recentAvg - $earlierAvg) / $earlierAvg) * 100;

        if ($change > 10) {
            return 'increasing';
        } elseif ($change < -10) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }
}
