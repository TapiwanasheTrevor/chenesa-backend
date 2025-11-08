<?php

namespace App\Console\Commands;

use App\Services\DingtekThingsBoardService;
use Illuminate\Console\Command;

class SyncDingtekSensors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dingtek:sync {--test : Run in test mode to verify connection}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync sensor data from Dingtek ThingsBoard cloud platform';

    /**
     * Execute the console command.
     */
    public function handle(DingtekThingsBoardService $dingtekService)
    {
        $this->info('Starting Dingtek sensor sync...');

        // Check if service is configured
        if (!$dingtekService->isConfigured()) {
            $this->error('Dingtek service is not configured. Please set DINGTEK_USERNAME and DINGTEK_PASSWORD in .env file.');
            return Command::FAILURE;
        }

        // Test mode - just verify connection
        if ($this->option('test')) {
            return $this->testConnection($dingtekService);
        }

        // Authenticate
        $this->info('Authenticating with Dingtek ThingsBoard...');
        $token = $dingtekService->authenticate();

        if (!$token) {
            $this->error('Failed to authenticate with Dingtek ThingsBoard. Please check your credentials.');
            return Command::FAILURE;
        }

        $this->info('✓ Authentication successful');

        // Sync all devices
        $this->info('Syncing devices and telemetry data...');

        $results = $dingtekService->syncAllDevices();

        // Display results
        $this->newLine();
        $this->info('Sync completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Devices synced', $results['synced']],
                ['Failed', $results['failed']],
            ]
        );

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->warn('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $deviceId = is_array($error) ? ($error['device_id'] ?? 'unknown') : 'unknown';
                $message = is_array($error) ? ($error['error'] ?? $error) : $error;
                $this->error("  Device {$deviceId}: {$message}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Test connection to Dingtek ThingsBoard
     */
    private function testConnection(DingtekThingsBoardService $dingtekService): int
    {
        $this->info('Testing connection to Dingtek ThingsBoard...');
        $this->newLine();

        // Test authentication
        $this->info('1. Testing authentication...');
        $token = $dingtekService->authenticate();

        if (!$token) {
            $this->error('   ✗ Authentication failed');
            return Command::FAILURE;
        }

        $this->info('   ✓ Authentication successful');
        $this->newLine();

        // Test getting devices
        $this->info('2. Fetching devices...');
        $devices = $dingtekService->getUserDevices();

        if (!$devices) {
            $this->error('   ✗ Failed to fetch devices');
            return Command::FAILURE;
        }

        $deviceCount = count($devices['data'] ?? []);
        $this->info("   ✓ Successfully fetched {$deviceCount} device(s)");

        if ($deviceCount > 0) {
            $this->newLine();
            $this->info('Devices found:');

            $deviceList = [];
            foreach ($devices['data'] as $device) {
                $deviceList[] = [
                    'ID' => $device['id']['id'] ?? 'N/A',
                    'Name' => $device['name'] ?? 'N/A',
                    'Type' => $device['type'] ?? 'N/A',
                ];
            }

            $this->table(['ID', 'Name', 'Type'], $deviceList);

            // Test getting telemetry for first device
            if ($deviceCount > 0) {
                $firstDevice = $devices['data'][0];
                $deviceId = $firstDevice['id']['id'];

                $this->newLine();
                $this->info('3. Fetching telemetry for first device...');
                $telemetry = $dingtekService->getDeviceTelemetry('DEVICE', $deviceId);

                if ($telemetry) {
                    $this->info('   ✓ Successfully fetched telemetry');
                    $this->info('   Telemetry keys: ' . implode(', ', array_keys($telemetry)));

                    // Display telemetry values
                    if (!empty($telemetry)) {
                        $this->newLine();
                        $telemetryTable = [];
                        foreach ($telemetry as $key => $values) {
                            if (!empty($values) && is_array($values)) {
                                $latestValue = $values[0]['value'] ?? 'N/A';
                                $timestamp = isset($values[0]['ts']) ? date('Y-m-d H:i:s', $values[0]['ts'] / 1000) : 'N/A';
                                $telemetryTable[] = [$key, $latestValue, $timestamp];
                            }
                        }

                        if (!empty($telemetryTable)) {
                            $this->table(['Key', 'Value', 'Timestamp'], $telemetryTable);
                        }
                    }
                } else {
                    $this->warn('   ⚠ No telemetry data available for this device');
                }
            }
        }

        $this->newLine();
        $this->info('✓ Connection test completed successfully!');
        $this->info('Run without --test flag to perform actual sync.');

        return Command::SUCCESS;
    }
}
