<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DingtekThingsBoardService
{
    private Client $client;
    private string $baseUrl;
    private string $username;
    private string $password;
    private ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = config('services.dingtek.base_url', 'https://cloud.dingtek.com');
        $this->username = config('services.dingtek.username');
        $this->password = config('services.dingtek.password');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 60,
            'connect_timeout' => 30,
            'verify' => true,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    /**
     * Check if the service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->username) && !empty($this->password);
    }

    /**
     * Authenticate with Dingtek ThingsBoard and get access token
     * Token is cached for 23 hours (tokens usually expire in 24h)
     */
    public function authenticate(): ?string
    {
        if (!$this->isConfigured()) {
            Log::warning('Dingtek ThingsBoard credentials not configured');
            return null;
        }

        // Check if we have a cached token
        $cachedToken = Cache::get('dingtek_auth_token');
        if ($cachedToken) {
            $this->token = $cachedToken;
            return $this->token;
        }

        try {
            Log::info('Authenticating with Dingtek ThingsBoard', [
                'username' => $this->username,
                'base_url' => $this->baseUrl
            ]);

            $response = $this->client->post('/api/auth/login', [
                'json' => [
                    'username' => $this->username,
                    'password' => $this->password
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            Log::debug('Dingtek API response', [
                'status_code' => $statusCode,
                'body_length' => strlen($body)
            ]);

            if ($statusCode !== 200) {
                Log::error('Dingtek authentication failed with non-200 status', [
                    'status_code' => $statusCode,
                    'response_body' => $body
                ]);
                return null;
            }

            $data = json_decode($body, true);

            if (isset($data['token'])) {
                $this->token = $data['token'];

                // Cache the token for 23 hours
                Cache::put('dingtek_auth_token', $this->token, now()->addHours(23));

                Log::info('Successfully authenticated with Dingtek ThingsBoard');
                return $this->token;
            }

            Log::error('No token in Dingtek authentication response', ['response' => $data]);
            return null;

        } catch (GuzzleException $e) {
            Log::error('Dingtek authentication failed', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return null;
        }
    }

    /**
     * Get the authorization token (authenticate if needed)
     */
    private function getToken(): ?string
    {
        if (!$this->token) {
            $this->token = $this->authenticate();
        }
        return $this->token;
    }

    /**
     * Get all devices from Dingtek ThingsBoard
     */
    public function getDevices(int $pageSize = 100, int $page = 0): ?array
    {
        $token = $this->getToken();
        if (!$token) {
            Log::error('Cannot get devices: No authentication token');
            return null;
        }

        try {
            $response = $this->client->get('/api/tenant/devices', [
                'headers' => [
                    'X-Authorization' => 'Bearer ' . $token
                ],
                'query' => [
                    'pageSize' => $pageSize,
                    'page' => $page
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info('Retrieved devices from Dingtek', [
                'count' => count($data['data'] ?? [])
            ]);

            return $data;

        } catch (GuzzleException $e) {
            Log::error('Failed to get devices from Dingtek', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            // Token might be expired, try re-authenticating
            if ($e->getCode() === 401) {
                Cache::forget('dingtek_auth_token');
                $this->token = null;
            }

            return null;
        }
    }

    /**
     * Get user devices (alternative endpoint)
     */
    public function getUserDevices(int $pageSize = 100, int $page = 0): ?array
    {
        $token = $this->getToken();
        if (!$token) {
            Log::error('Cannot get user devices: No authentication token');
            return null;
        }

        try {
            $response = $this->client->get('/api/user/devices', [
                'headers' => [
                    'X-Authorization' => 'Bearer ' . $token
                ],
                'query' => [
                    'pageSize' => $pageSize,
                    'page' => $page
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info('Retrieved user devices from Dingtek', [
                'count' => count($data['data'] ?? [])
            ]);

            return $data;

        } catch (GuzzleException $e) {
            Log::error('Failed to get user devices from Dingtek', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            // Token might be expired, try re-authenticating
            if ($e->getCode() === 401) {
                Cache::forget('dingtek_auth_token');
                $this->token = null;
            }

            return null;
        }
    }

    /**
     * Get device attributes (configuration data)
     */
    public function getDeviceAttributes(string $entityType, string $entityId, string $scope = 'SERVER_SCOPE'): ?array
    {
        $token = $this->getToken();
        if (!$token) {
            Log::error('Cannot get device attributes: No authentication token');
            return null;
        }

        try {
            $response = $this->client->get("/api/plugins/telemetry/{$entityType}/{$entityId}/values/attributes", [
                'headers' => [
                    'X-Authorization' => 'Bearer ' . $token
                ],
                'query' => [
                    'scope' => $scope
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::debug('Retrieved device attributes from Dingtek', [
                'entityType' => $entityType,
                'entityId' => $entityId,
                'attributes_count' => count($data ?? [])
            ]);

            return $data;

        } catch (GuzzleException $e) {
            Log::error('Failed to get device attributes from Dingtek', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'entityType' => $entityType,
                'entityId' => $entityId
            ]);

            // Token might be expired, try re-authenticating
            if ($e->getCode() === 401) {
                Cache::forget('dingtek_auth_token');
                $this->token = null;
            }

            return null;
        }
    }

    /**
     * Get device telemetry (latest time series data)
     */
    public function getDeviceTelemetry(string $entityType, string $entityId, ?string $keys = null): ?array
    {
        $token = $this->getToken();
        if (!$token) {
            Log::error('Cannot get device telemetry: No authentication token');
            return null;
        }

        try {
            $url = "/api/plugins/telemetry/{$entityType}/{$entityId}/values/timeseries";
            $queryParams = [];

            if ($keys) {
                $queryParams['keys'] = $keys;
            }

            $response = $this->client->get($url, [
                'headers' => [
                    'X-Authorization' => 'Bearer ' . $token
                ],
                'query' => $queryParams
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::debug('Retrieved device telemetry from Dingtek', [
                'entityType' => $entityType,
                'entityId' => $entityId,
                'telemetry_keys' => array_keys($data ?? [])
            ]);

            return $data;

        } catch (GuzzleException $e) {
            Log::error('Failed to get device telemetry from Dingtek', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'entityType' => $entityType,
                'entityId' => $entityId
            ]);

            // Token might be expired, try re-authenticating
            if ($e->getCode() === 401) {
                Cache::forget('dingtek_auth_token');
                $this->token = null;
            }

            return null;
        }
    }

    /**
     * Get historical telemetry data for a device
     */
    public function getHistoricalTelemetry(
        string $entityType,
        string $entityId,
        string $keys,
        int $startTs,
        int $endTs,
        int $limit = 1000
    ): ?array {
        $token = $this->getToken();
        if (!$token) {
            Log::error('Cannot get historical telemetry: No authentication token');
            return null;
        }

        try {
            $response = $this->client->get("/api/plugins/telemetry/{$entityType}/{$entityId}/values/timeseries", [
                'headers' => [
                    'X-Authorization' => 'Bearer ' . $token
                ],
                'query' => [
                    'keys' => $keys,
                    'startTs' => $startTs,
                    'endTs' => $endTs,
                    'limit' => $limit
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::debug('Retrieved historical telemetry from Dingtek', [
                'entityType' => $entityType,
                'entityId' => $entityId,
                'keys' => $keys,
                'data_points' => count($data ?? [])
            ]);

            return $data;

        } catch (GuzzleException $e) {
            Log::error('Failed to get historical telemetry from Dingtek', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'entityType' => $entityType,
                'entityId' => $entityId
            ]);

            // Token might be expired, try re-authenticating
            if ($e->getCode() === 401) {
                Cache::forget('dingtek_auth_token');
                $this->token = null;
            }

            return null;
        }
    }

    /**
     * Sync all devices and their latest telemetry
     */
    public function syncAllDevices(): array
    {
        $results = [
            'synced' => 0,
            'failed' => 0,
            'errors' => []
        ];

        // Get all devices
        $devicesResponse = $this->getUserDevices();

        if (!$devicesResponse || !isset($devicesResponse['data'])) {
            Log::error('Failed to retrieve devices for sync');
            $results['errors'][] = 'Failed to retrieve devices from Dingtek';
            return $results;
        }

        $devices = $devicesResponse['data'];
        Log::info('Starting sync of devices', ['device_count' => count($devices)]);

        // Collect Dingtek device IDs
        $dingtekDeviceIds = collect($devices)->pluck('id.id')->filter()->toArray();

        // Sync each device from Dingtek
        foreach ($devices as $device) {
            try {
                $this->syncDevice($device);
                $results['synced']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'device_id' => $device['id']['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];

                Log::error('Failed to sync device', [
                    'device' => $device['name'] ?? $device['id']['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Remove sensors that no longer exist in Dingtek
        $removedCount = \App\Models\Sensor::whereNotIn('device_id', $dingtekDeviceIds)->delete();
        if ($removedCount > 0) {
            Log::info('Removed sensors no longer in Dingtek', ['count' => $removedCount]);
            $results['removed'] = $removedCount;
        }

        Log::info('Device sync completed', $results);
        return $results;
    }

    /**
     * Sync a single device's data
     */
    private function syncDevice(array $deviceData): void
    {
        $deviceId = $deviceData['id']['id'] ?? null;
        $deviceName = $deviceData['name'] ?? $deviceId;

        if (!$deviceId) {
            throw new \Exception('Device ID not found in device data');
        }

        Log::debug('Syncing device', [
            'device_id' => $deviceId,
            'device_name' => $deviceName
        ]);

        // Get latest telemetry for the device
        $telemetry = $this->getDeviceTelemetry('DEVICE', $deviceId);

        if (!$telemetry) {
            Log::warning('No telemetry data for device', ['device_id' => $deviceId]);
            return;
        }

        // Process and store the telemetry data
        $this->processTelemetryData($deviceData, $telemetry);
    }

    /**
     * Process telemetry data and store it in the database
     */
    private function processTelemetryData(array $deviceData, array $telemetry): void
    {
        $deviceId = $deviceData['id']['id'];
        $deviceName = $deviceData['name'] ?? $deviceId;

        // Find or create sensor in our database
        $sensor = \App\Models\Sensor::firstOrCreate(
            ['device_id' => $deviceId],
            [
                'model' => $deviceData['type'] ?? 'DF555',
                'imei' => $deviceName, // Store IMEI from Dingtek
                'status' => 'inactive', // Default to inactive, will be updated based on telemetry
            ]
        );

        // Update IMEI if changed
        if ($sensor->imei !== $deviceName) {
            $sensor->imei = $deviceName;
        }

        // Auto-generate friendly name if not set (using IMEI)
        if (!$sensor->name) {
            // Use last 6 digits of IMEI for friendly name
            $model = $deviceData['type'] ?? 'DF555';
            $sensor->name = $model . '-' . substr($deviceName, -6);
            $sensor->save();
        }

        // Extract relevant telemetry values
        // Common keys: distance, temperature, battery, level, rssi, etc.
        $reading = [];

        // Map telemetry keys to our reading structure
        foreach ($telemetry as $key => $values) {
            if (!empty($values) && is_array($values)) {
                $latestValue = $values[0]['value'] ?? null;

                switch (strtolower($key)) {
                    case 'distance':
                        $reading['distance'] = $latestValue / 10; // Convert cm to meters if needed
                        break;
                    case 'level':
                    case 'water_level':
                        // Dingtek level is in cm, convert to meters
                        $reading['level'] = $latestValue / 100;
                        break;
                    case 'temperature':
                    case 'temp':
                        $reading['temperature'] = $latestValue;
                        break;
                    case 'battery':
                    case 'battery_level':
                        $reading['battery_level'] = $latestValue;
                        break;
                    case 'volt':
                    case 'voltage':
                        // Battery voltage in 0.01V units (e.g., 364 = 3.64V)
                        $reading['battery_voltage'] = $latestValue / 100;
                        // Convert voltage to percentage (3.0V = 0%, 4.2V = 100%)
                        $voltage = $latestValue / 100;
                        $reading['battery_level'] = max(0, min(100, (($voltage - 3.0) / 1.2) * 100));
                        break;
                    case 'rssi':
                    case 'signal':
                    case 'rsrp':
                        // RSRP is in 0.1dBm units (e.g., -630 = -63dBm)
                        $rsrp = $latestValue / 10;
                        $reading['rssi'] = $rsrp;
                        break;
                }

                // Store timestamp if available (use first timestamp found)
                if (isset($values[0]['ts']) && !isset($reading['timestamp'])) {
                    $reading['timestamp'] = date('Y-m-d H:i:s', $values[0]['ts'] / 1000);
                }
            }
        }

        if (empty($reading)) {
            Log::debug('No processable telemetry data', [
                'device_id' => $deviceId,
                'telemetry_keys' => array_keys($telemetry)
            ]);
            return;
        }

        // Determine sensor status based on telemetry timestamp
        $telemetryTime = isset($reading['timestamp'])
            ? \Carbon\Carbon::parse($reading['timestamp'])
            : null;

        // If no telemetry timestamp, sensor cannot be considered active
        if (!$telemetryTime) {
            Log::warning('No timestamp in telemetry data', ['device_id' => $deviceId]);
            $sensor->update(['status' => 'inactive']);
            return;
        }

        // Calculate time difference (consider sensor inactive if no data in last 10 hours)
        $hoursSinceLastReading = $telemetryTime->diffInHours(now());
        $isOnline = $hoursSinceLastReading < 10;

        // Update sensor status
        $updateData = [
            'last_seen' => $telemetryTime, // Use actual telemetry timestamp
            'status' => $isOnline ? 'active' : 'inactive'
        ];

        if (isset($reading['battery_level'])) {
            $updateData['battery_level'] = (int) $reading['battery_level'];
        }

        if (isset($reading['rssi'])) {
            $rssi = (int) $reading['rssi'];
            $signalPercentage = max(0, min(100, 2 * ($rssi + 100)));
            $updateData['signal_strength'] = $signalPercentage;
        }

        $sensor->update($updateData);

        // Log status for debugging
        Log::info('Sensor status updated', [
            'device_id' => $deviceId,
            'telemetry_time' => $telemetryTime->toDateTimeString(),
            'hours_since_reading' => $hoursSinceLastReading,
            'status' => $updateData['status']
        ]);

        // Store the reading
        $this->storeSensorReading($sensor, $reading);

        Log::info('Device telemetry synced successfully', [
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'sensor_id' => $sensor->id
        ]);
    }

    /**
     * Store sensor reading in database
     */
    private function storeSensorReading(\App\Models\Sensor $sensor, array $reading): void
    {
        $tank = $sensor->tank;

        if (!$tank) {
            // Auto-create a tank for this sensor
            Log::info('Auto-creating tank for sensor', ['sensor_id' => $sensor->id]);

            // Get or create default organization
            $organization = \App\Models\Organization::first();
            if (!$organization) {
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

            // Note: Auto-tank creation disabled to give admins full control
            // Sensors without assigned tanks will simply not have tank data
            // Admins must manually create tanks and assign sensors through the Filament admin panel

            // Previously auto-created tanks with generic names like "Tank for Sensor {device_id}"
            // were confusing users in the mobile app. Manual assignment ensures proper naming,
            // location, and capacity configuration.

            // If you need to re-enable auto-creation for testing, uncomment the code below:
            /*
            $tank = \App\Models\Tank::create([
                'organization_id' => $organization->id,
                'sensor_id' => $sensor->id,
                'name' => 'Tank for Sensor ' . $sensor->device_id,
                'location' => 'Unknown Location',
                'capacity_liters' => 10000,
                'height_mm' => 2000,
                'diameter_mm' => 1500,
                'shape' => 'cylindrical',
                'material' => 'plastic'
            ]);

            // Reload sensor to get the tank relationship
            $sensor->load('tank');
            */
        }

        // Convert to millimeters
        // Dingtek 'level' is the actual water level in cm (not distance from sensor)
        $waterLevelMm = null;
        $distanceMm = null;

        if (isset($reading['level'])) {
            // Level is water level in meters, convert to mm
            $waterLevelMm = (int) ($reading['level'] * 1000);
            // Calculate distance from sensor (tank height - water level)
            $distanceMm = $tank->height_mm - $waterLevelMm;
        } elseif (isset($reading['distance'])) {
            // Distance from sensor in meters, convert to mm
            $distanceMm = (int) ($reading['distance'] * 1000);
            // Calculate water level
            $waterLevelMm = $tank->height_mm - $distanceMm;
        } else {
            // Default: assume half full
            $waterLevelMm = (int) ($tank->height_mm / 2);
            $distanceMm = $waterLevelMm;
        }

        // Ensure values are within valid range
        $distanceMm = max(0, min($tank->height_mm, $distanceMm));
        $waterLevelMm = max(0, min($tank->height_mm, $waterLevelMm));

        // Calculate water level percentage
        $waterLevelPercentage = $tank->height_mm > 0
            ? round(($waterLevelMm / $tank->height_mm) * 100, 2)
            : 0;

        // Calculate volume
        $volumeLiters = $this->calculateVolume($tank, $waterLevelMm);

        $sensorReading = new \App\Models\SensorReading([
            'sensor_id' => $sensor->id,
            'tank_id' => $tank->id,
            'distance_mm' => $distanceMm,
            'water_level_mm' => $waterLevelMm,
            'water_level_percentage' => $waterLevelPercentage,
            'volume_liters' => $volumeLiters,
            'temperature' => $reading['temperature'] ?? null,
            'battery_voltage' => $reading['battery_voltage'] ?? null,
            'signal_rssi' => $reading['rssi'] ?? null,
            'raw_data' => json_encode($reading),
            'created_at' => $reading['timestamp'] ?? now(),
        ]);

        $sensorReading->save();

        // Sync with Firebase
        $firebaseService = app(\App\Services\FirebaseService::class);
        if ($firebaseService->isConfigured() && $tank) {
            try {
                $tank->load('organization', 'sensor');
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
                        'id' => $sensor->id,
                        'device_id' => $sensor->device_id,
                        'status' => $sensor->status,
                    ],
                    'last_updated' => now()->toISOString()
                ];

                $firebaseService->updateTankData($tank->id, $tankData);
            } catch (\Exception $e) {
                Log::error('Failed to sync with Firebase', [
                    'tank_id' => $tank->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::debug('Sensor reading stored', [
            'sensor_id' => $sensor->id,
            'tank_id' => $tank->id,
            'water_level_percentage' => $waterLevelPercentage
        ]);
    }

    /**
     * Calculate volume based on tank shape and water level
     */
    private function calculateVolume(\App\Models\Tank $tank, int $waterLevelMm): float
    {
        $waterLevelM = $waterLevelMm / 1000;
        $diameterM = $tank->diameter_mm / 1000;
        $radiusM = $diameterM / 2;

        switch ($tank->shape) {
            case 'cylindrical':
                $volumeM3 = pi() * pow($radiusM, 2) * $waterLevelM;
                break;
            case 'rectangular':
                $volumeM3 = $diameterM * $diameterM * $waterLevelM;
                break;
            case 'spherical':
                $volumeM3 = (pi() * pow($waterLevelM, 2) / 3) * (3 * $radiusM - $waterLevelM);
                break;
            default:
                $volumeM3 = pi() * pow($radiusM, 2) * $waterLevelM;
                break;
        }

        return round($volumeM3 * 1000, 2);
    }
}
