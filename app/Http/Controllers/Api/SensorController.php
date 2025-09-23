<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\Tank;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SensorController extends Controller
{
    /**
     * Receive data from Dingtek DF555 Ultrasonic Level Sensor
     * This endpoint is designed to handle TCP data transmission from GPRS/4G sensors
     */
    public function receiveDingtekData(Request $request)
    {
        try {
            // Log the incoming request for debugging
            Log::info('Dingtek sensor data received', [
                'headers' => $request->headers->all(),
                'body' => $request->getContent(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);

            // Get raw content as the sensor might send binary or custom format data
            $rawData = $request->getContent();

            // For now, let's also try to parse JSON if available
            $jsonData = null;
            if ($request->isJson()) {
                $jsonData = $request->json()->all();
            }

            // Basic validation for required fields
            // We'll need to adjust this based on the actual data format from the sensor
            $validator = Validator::make($request->all(), [
                'device_id' => 'sometimes|string',
                'sensor_id' => 'sometimes|string',
                'level' => 'sometimes|numeric',
                'distance' => 'sometimes|numeric',
                'temperature' => 'sometimes|numeric',
                'battery' => 'sometimes|numeric',
                'timestamp' => 'sometimes|date',
            ]);

            if ($validator->fails() && $request->isJson()) {
                Log::warning('Dingtek data validation failed', [
                    'errors' => $validator->errors(),
                    'data' => $request->all()
                ]);

                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 400);
            }

            // Process the sensor data
            $this->processSensorData($request, $rawData, $jsonData);

            // Return success response (some IoT devices expect specific response formats)
            return response()->json([
                'status' => 'success',
                'message' => 'Data received successfully',
                'timestamp' => now()->toISOString()
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error processing Dingtek sensor data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Process and store sensor data
     */
    private function processSensorData(Request $request, $rawData, $jsonData)
    {
        // Extract device/sensor identifier
        $deviceId = $this->extractDeviceId($request, $jsonData);

        // Find or create sensor
        $sensor = $this->findOrCreateSensor($deviceId, $request->ip());

        if (!$sensor) {
            Log::warning('Could not find or create sensor', ['device_id' => $deviceId]);
            return;
        }

        // Extract sensor readings
        $readings = $this->extractSensorReadings($request, $jsonData, $rawData);

        if (empty($readings)) {
            Log::warning('No valid readings extracted from sensor data', [
                'device_id' => $deviceId,
                'raw_data' => $rawData
            ]);
            return;
        }

        // Store readings
        foreach ($readings as $reading) {
            $this->storeSensorReading($sensor, $reading);
        }

        Log::info('Sensor data processed successfully', [
            'sensor_id' => $sensor->id,
            'device_id' => $deviceId,
            'readings_count' => count($readings)
        ]);
    }

    /**
     * Extract device ID from request
     */
    private function extractDeviceId(Request $request, $jsonData)
    {
        // Try various ways to get device ID
        if ($jsonData && isset($jsonData['device_id'])) {
            return $jsonData['device_id'];
        }

        if ($jsonData && isset($jsonData['deviceId'])) {
            return $jsonData['deviceId'];
        }

        if ($request->has('device_id')) {
            return $request->input('device_id');
        }

        if ($request->has('sensor_id')) {
            return $request->input('sensor_id');
        }

        // Fallback to IP address if no device ID provided
        return 'dingtek_' . str_replace('.', '_', $request->ip());
    }

    /**
     * Find existing sensor or create new one
     */
    private function findOrCreateSensor($deviceId, $ipAddress)
    {
        $sensor = Sensor::where('device_id', $deviceId)->first();

        if (!$sensor) {
            // Create the sensor first
            $sensor = Sensor::create([
                'device_id' => $deviceId,
                'model' => 'DF555',
                'status' => 'active',
                'last_seen' => now()
            ]);

            // Create or assign a tank to this sensor
            $tank = Tank::where('sensor_id', null)->first();
            if (!$tank) {
                // Get first available organization or create one
                $organization = \App\Models\Organization::first();
                if (!$organization) {
                    // Ensure we have a subscription plan
                    $subscriptionPlan = \App\Models\SubscriptionPlan::first();
                    if (!$subscriptionPlan) {
                        $subscriptionPlan = \App\Models\SubscriptionPlan::create([
                            'name' => 'Basic Plan',
                            'description' => 'Auto-created basic plan',
                            'price' => 0.00,
                            'features' => json_encode(['basic_monitoring'])
                        ]);
                    }

                    $organization = \App\Models\Organization::create([
                        'name' => 'Default Organization',
                        'subscription_plan_id' => $subscriptionPlan->id
                    ]);
                }

                // Create a default tank if none exists
                $tank = Tank::create([
                    'organization_id' => $organization->id,
                    'sensor_id' => $sensor->id,
                    'name' => 'Auto-created Tank for ' . $deviceId,
                    'location' => 'Unknown Location',
                    'capacity_liters' => 10000,
                    'height_mm' => 2000,
                    'diameter_mm' => 1500,
                    'shape' => 'cylindrical',
                    'material' => 'plastic'
                ]);
            } else {
                // Assign existing unassigned tank to this sensor
                $tank->sensor_id = $sensor->id;
                $tank->save();
            }

            Log::info('Created new sensor for Dingtek device', [
                'sensor_id' => $sensor->id,
                'device_id' => $deviceId,
                'ip_address' => $ipAddress
            ]);
        }

        return $sensor;
    }

    /**
     * Extract sensor readings from request data
     */
    private function extractSensorReadings(Request $request, $jsonData, $rawData)
    {
        $readings = [];

        // Handle JSON format
        if ($jsonData) {
            $reading = [];

            if (isset($jsonData['level'])) {
                $reading['level'] = $jsonData['level'];
            }

            if (isset($jsonData['distance'])) {
                $reading['distance'] = $jsonData['distance'];
            }

            if (isset($jsonData['temperature'])) {
                $reading['temperature'] = $jsonData['temperature'];
            }

            if (isset($jsonData['battery'])) {
                $reading['battery_level'] = $jsonData['battery'];
            }

            if (isset($jsonData['timestamp'])) {
                $reading['timestamp'] = $jsonData['timestamp'];
            }

            if (!empty($reading)) {
                $readings[] = $reading;
            }
        }

        // Handle form data
        if ($request->has('level') || $request->has('distance')) {
            $reading = [];

            if ($request->has('level')) {
                $reading['level'] = $request->input('level');
            }

            if ($request->has('distance')) {
                $reading['distance'] = $request->input('distance');
            }

            if ($request->has('temperature')) {
                $reading['temperature'] = $request->input('temperature');
            }

            if ($request->has('battery')) {
                $reading['battery_level'] = $request->input('battery');
            }

            $readings[] = $reading;
        }

        // TODO: Handle binary/custom Dingtek protocol format
        // This would require parsing the specific protocol used by DF555
        // For now, we log the raw data for analysis
        if (empty($readings) && !empty($rawData)) {
            Log::info('Raw sensor data for protocol analysis', [
                'raw_data' => $rawData,
                'hex_data' => bin2hex($rawData)
            ]);
        }

        return $readings;
    }

    /**
     * Store sensor reading in database
     */
    private function storeSensorReading(Sensor $sensor, array $reading)
    {
        // Get the tank associated with this sensor
        $tank = $sensor->tank;

        if (!$tank) {
            Log::warning('No tank associated with sensor, cannot store reading', [
                'sensor_id' => $sensor->id
            ]);
            return;
        }

        // Convert distance from meters to millimeters, default if not provided
        $distanceMm = null;
        if (isset($reading['distance'])) {
            $distanceMm = (int) ($reading['distance'] * 1000); // Convert meters to mm
        } elseif (isset($reading['level'])) {
            // If level is provided instead of distance, use tank height minus level
            $distanceMm = $tank->height_mm - (int) ($reading['level'] * 1000);
        } else {
            // Default distance if nothing provided
            $distanceMm = 1000; // 1 meter default
        }

        $sensorReading = new SensorReading([
            'sensor_id' => $sensor->id,
            'tank_id' => $tank->id,
            'distance_mm' => $distanceMm,
            'water_level_mm' => isset($reading['level']) ? (int) ($reading['level'] * 1000) : null,
            'temperature' => $reading['temperature'] ?? null,
            'battery_voltage' => isset($reading['battery_level']) ? $reading['battery_level'] / 100 * 3.7 : null, // Convert % to voltage estimate
            'raw_data' => json_encode($reading),
            'created_at' => isset($reading['timestamp']) ? $reading['timestamp'] : now(),
        ]);

        $sensorReading->save();

        // Sync with Firebase Realtime Database
        $this->syncWithFirebase($sensorReading);

        Log::info('Sensor reading stored', [
            'sensor_reading_id' => $sensorReading->id,
            'sensor_id' => $sensor->id,
            'tank_id' => $tank->id,
            'distance_mm' => $sensorReading->distance_mm,
            'water_level_mm' => $sensorReading->water_level_mm,
        ]);
    }

    /**
     * Sync sensor reading with Firebase Realtime Database
     */
    private function syncWithFirebase(SensorReading $sensorReading)
    {
        try {
            $firebaseService = app(FirebaseService::class);

            if (!$firebaseService->isConfigured()) {
                Log::debug('Firebase not configured, skipping realtime sync');
                return;
            }

            if (!$sensorReading->tank_id) {
                Log::debug('No tank associated with sensor reading, skipping Firebase sync');
                return;
            }

            // Get tank with organization for complete data
            $tank = Tank::with('organization', 'sensor')->find($sensorReading->tank_id);

            if (!$tank) {
                Log::warning('Tank not found for sensor reading', ['tank_id' => $sensorReading->tank_id]);
                return;
            }

            // Prepare tank data for Firebase
            $tankData = [
                'id' => $tank->id,
                'name' => $tank->name,
                'location' => $tank->location,
                'capacity_liters' => $tank->capacity_liters,
                'organization_id' => $tank->organization_id,
                'organization_name' => $tank->organization->name ?? null,
                'latest_reading' => [
                    'id' => $sensorReading->id,
                    'distance_mm' => $sensorReading->distance_mm,
                    'water_level_mm' => $sensorReading->water_level_mm,
                    'water_level_percentage' => $sensorReading->water_level_percentage,
                    'temperature' => $sensorReading->temperature,
                    'battery_voltage' => $sensorReading->battery_voltage,
                    'timestamp' => $sensorReading->created_at->toISOString(),
                ],
                'sensor' => [
                    'id' => $tank->sensor->id ?? null,
                    'device_id' => $tank->sensor->device_id ?? null,
                    'status' => $tank->sensor->status ?? null,
                ],
                'last_updated' => now()->toISOString()
            ];

            // Update Firebase
            $firebaseService->updateTankData($tank->id, $tankData);

            Log::debug('Firebase sync completed for tank', ['tank_id' => $tank->id]);

        } catch (\Exception $e) {
            Log::error('Failed to sync with Firebase: ' . $e->getMessage(), [
                'sensor_reading_id' => $sensorReading->id,
                'tank_id' => $sensorReading->tank_id
            ]);
        }
    }

    /**
     * Get sensor status (for debugging/monitoring)
     */
    public function getSensorStatus(Request $request)
    {
        $deviceId = $request->input('device_id');

        if (!$deviceId) {
            return response()->json(['error' => 'device_id is required'], 400);
        }

        $sensor = Sensor::where('device_id', $deviceId)->first();

        if (!$sensor) {
            return response()->json(['error' => 'Sensor not found'], 404);
        }

        $latestReading = SensorReading::where('sensor_id', $sensor->id)
            ->latest()
            ->first();

        return response()->json([
            'sensor' => $sensor,
            'latest_reading' => $latestReading,
            'readings_count' => SensorReading::where('sensor_id', $sensor->id)->count()
        ]);
    }
}