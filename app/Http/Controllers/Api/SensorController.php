<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\Tank;
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
            // Create a default tank first if none exists
            $tank = Tank::first();
            if (!$tank) {
                Log::error('No tank available to assign sensor');
                return null;
            }

            $sensor = Sensor::create([
                'tank_id' => $tank->id,
                'device_id' => $deviceId,
                'name' => 'Dingtek DF555 - ' . $deviceId,
                'type' => 'ultrasonic',
                'manufacturer' => 'Dingtek',
                'model' => 'DF555',
                'is_active' => true,
                'config' => [
                    'ip_address' => $ipAddress,
                    'auto_created' => true,
                    'created_at' => now()->toISOString()
                ]
            ]);

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
        $sensorReading = new SensorReading([
            'sensor_id' => $sensor->id,
            'tank_id' => $sensor->tank_id,
            'level' => $reading['level'] ?? null,
            'distance' => $reading['distance'] ?? null,
            'temperature' => $reading['temperature'] ?? null,
            'humidity' => null, // DF555 doesn't measure humidity
            'battery_level' => $reading['battery_level'] ?? null,
            'signal_strength' => null, // Could be added if available
            'created_at' => isset($reading['timestamp']) ? $reading['timestamp'] : now(),
        ]);

        $sensorReading->save();

        Log::info('Sensor reading stored', [
            'sensor_reading_id' => $sensorReading->id,
            'sensor_id' => $sensor->id,
            'tank_id' => $sensor->tank_id,
            'level' => $sensorReading->level,
            'distance' => $sensorReading->distance,
        ]);
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