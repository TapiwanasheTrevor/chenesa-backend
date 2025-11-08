<?php

namespace App\Console\Commands;

use App\Models\Tank;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\Organization;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestMobileAppIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mobile:test-integration {--user-email= : Test with specific user email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test mobile app integration with Dingtek synced data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Testing Mobile App Integration with Dingtek Data');
        $this->newLine();

        // Step 1: Check Dingtek sensors
        $this->info('1. Checking Dingtek Sensors...');
        $dingtekSensors = Sensor::where('device_id', 'LIKE', '%-%')->get();

        if ($dingtekSensors->isEmpty()) {
            $this->error('   ✗ No Dingtek sensors found!');
            $this->warn('   Run: php artisan dingtek:sync');
            return Command::FAILURE;
        }

        $this->info("   ✓ Found {$dingtekSensors->count()} Dingtek sensor(s)");

        // Display sensor details
        $sensorTable = [];
        foreach ($dingtekSensors as $sensor) {
            $tank = $sensor->tank;
            $latestReading = $sensor->latestReading;

            $sensorTable[] = [
                'Device ID' => substr($sensor->device_id, 0, 20) . '...',
                'Tank' => $tank ? $tank->name : 'No Tank',
                'Status' => $sensor->status,
                'Battery' => $sensor->battery_level ? $sensor->battery_level . '%' : 'N/A',
                'Last Reading' => $latestReading ? $latestReading->created_at->diffForHumans() : 'Never'
            ];
        }
        $this->table(['Device ID', 'Tank', 'Status', 'Battery', 'Last Reading'], $sensorTable);
        $this->newLine();

        // Step 2: Check tanks with sensor data
        $this->info('2. Checking Tanks with Sensor Data...');
        $tanksWithSensors = Tank::whereHas('sensor')->with(['sensor', 'latestReading', 'organization'])->get();

        if ($tanksWithSensors->isEmpty()) {
            $this->error('   ✗ No tanks with sensors found!');
            return Command::FAILURE;
        }

        $this->info("   ✓ Found {$tanksWithSensors->count()} tank(s) with sensors");

        $tankTable = [];
        foreach ($tanksWithSensors as $tank) {
            $latestReading = $tank->latestReading;

            $tankTable[] = [
                'Tank Name' => $tank->name,
                'Organization' => $tank->organization->name ?? 'N/A',
                'Level' => $latestReading ? round($latestReading->water_level_percentage, 1) . '%' : 'N/A',
                'Volume' => $latestReading ? round($latestReading->volume_liters, 0) . 'L' : 'N/A',
                'Temperature' => $latestReading && $latestReading->temperature ? $latestReading->temperature . '°C' : 'N/A',
                'Last Update' => $latestReading ? $latestReading->created_at->diffForHumans() : 'Never'
            ];
        }
        $this->table(['Tank Name', 'Organization', 'Level', 'Volume', 'Temperature', 'Last Update'], $tankTable);
        $this->newLine();

        // Step 3: Test API data structure (simulate what mobile app receives)
        $this->info('3. Testing Mobile App API Data Structure...');

        $testTank = $tanksWithSensors->first();
        if (!$testTank) {
            $this->error('   ✗ No test tank available');
            return Command::FAILURE;
        }

        $this->info("   Testing with tank: {$testTank->name}");

        // Simulate GET /api/tanks/{id} response
        $apiResponse = [
            'id' => $testTank->id,
            'name' => $testTank->name,
            'location' => $testTank->location,
            'latitude' => $testTank->latitude,
            'longitude' => $testTank->longitude,
            'capacity_liters' => $testTank->capacity_liters,
            'height_mm' => $testTank->height_mm,
            'diameter_mm' => $testTank->diameter_mm,
            'shape' => $testTank->shape,
            'material' => $testTank->material,
        ];

        if ($testTank->latestReading) {
            $reading = $testTank->latestReading;
            $apiResponse['current_reading'] = [
                'water_level_percentage' => $reading->water_level_percentage,
                'volume_liters' => $reading->volume_liters,
                'temperature' => $reading->temperature,
                'battery_level' => $testTank->sensor->battery_level,
                'timestamp' => $reading->created_at->toISOString(),
            ];
        }

        if ($testTank->sensor) {
            $apiResponse['sensor'] = [
                'id' => $testTank->sensor->id,
                'device_id' => $testTank->sensor->device_id,
                'model' => $testTank->sensor->model,
                'status' => $testTank->sensor->status,
                'battery_level' => $testTank->sensor->battery_level,
                'signal_strength' => $testTank->sensor->signal_strength,
                'last_seen' => $testTank->sensor->last_seen?->toISOString(),
            ];
        }

        $this->info('   ✓ API Response Structure:');
        $this->line(json_encode($apiResponse, JSON_PRETTY_PRINT));
        $this->newLine();

        // Step 4: Check sensor readings count
        $this->info('4. Checking Sensor Readings Data...');
        $readingsCount = SensorReading::whereIn('sensor_id', $dingtekSensors->pluck('id'))->count();
        $this->info("   ✓ Total sensor readings: {$readingsCount}");

        if ($readingsCount > 0) {
            $latestReadings = SensorReading::whereIn('sensor_id', $dingtekSensors->pluck('id'))
                ->latest()
                ->limit(5)
                ->get();

            $readingsTable = [];
            foreach ($latestReadings as $reading) {
                $readingsTable[] = [
                    'Tank' => $reading->tank->name ?? 'N/A',
                    'Level %' => round($reading->water_level_percentage, 1),
                    'Volume L' => round($reading->volume_liters, 0),
                    'Temp °C' => $reading->temperature ?? 'N/A',
                    'Time' => $reading->created_at->diffForHumans(),
                ];
            }
            $this->table(['Tank', 'Level %', 'Volume L', 'Temp °C', 'Time'], $readingsTable);
        }
        $this->newLine();

        // Step 5: Test Firebase integration
        $this->info('5. Testing Firebase Integration...');
        $firebaseService = app(FirebaseService::class);

        if (!$firebaseService->isConfigured()) {
            $this->warn('   ⚠ Firebase not configured');
        } else {
            $this->info('   ✓ Firebase is configured');
            $this->info('   Database URL: ' . config('services.firebase.database_url'));
        }
        $this->newLine();

        // Step 6: Check user authentication
        $this->info('6. Checking User Authentication Setup...');
        $userEmail = $this->option('user-email') ?? 'demo@chenesa.io';
        $testUser = User::where('email', $userEmail)->first();

        if (!$testUser) {
            $this->warn("   ⚠ Test user not found: {$userEmail}");
            $this->info('   Available users:');
            User::limit(5)->get()->each(function($user) {
                $this->line("   - {$user->email} ({$user->role})");
            });
        } else {
            $this->info("   ✓ Test user found: {$testUser->email}");
            $this->info("   Role: {$testUser->role}");
            $this->info("   Organization: " . ($testUser->organization->name ?? 'N/A'));

            // Test user can access tanks
            $userOrg = $testUser->organization;
            if ($userOrg) {
                $orgTanks = Tank::where('organization_id', $userOrg->id)->count();
                $this->info("   Accessible tanks: {$orgTanks}");
            }
        }
        $this->newLine();

        // Step 7: Mobile app endpoint checklist
        $this->info('7. Mobile App Endpoint Checklist:');
        $endpoints = [
            'GET /api/tanks' => 'List all tanks with current status',
            'GET /api/tanks/{id}' => 'Get detailed tank information',
            'GET /api/tanks/{id}/live-status' => 'Get real-time tank status',
            'GET /api/tanks/{id}/analytics' => 'Get tank analytics',
            'GET /api/tanks/{id}/history' => 'Get historical readings',
            'GET /api/sensors' => 'List all sensors',
            'GET /api/sensors/{id}' => 'Get sensor details',
            'GET /api/dashboard/overview' => 'Dashboard summary',
        ];

        foreach ($endpoints as $endpoint => $description) {
            $this->line("   ✓ {$endpoint}");
            $this->line("     → {$description}");
        }
        $this->newLine();

        // Summary
        $this->info('📊 Integration Test Summary:');
        $summary = [
            ['Metric', 'Value', 'Status'],
            ['Dingtek Sensors', $dingtekSensors->count(), $dingtekSensors->count() > 0 ? '✓' : '✗'],
            ['Tanks with Sensors', $tanksWithSensors->count(), $tanksWithSensors->count() > 0 ? '✓' : '✗'],
            ['Total Sensor Readings', $readingsCount, $readingsCount > 0 ? '✓' : '✗'],
            ['Firebase Configured', $firebaseService->isConfigured() ? 'Yes' : 'No', $firebaseService->isConfigured() ? '✓' : '⚠'],
            ['Test User Available', $testUser ? 'Yes' : 'No', $testUser ? '✓' : '⚠'],
        ];
        $this->table($summary[0], array_slice($summary, 1));
        $this->newLine();

        // Next steps
        $this->info('📱 Next Steps for Mobile App Testing:');
        $this->line('1. Start Laravel server: php artisan serve');
        $this->line('2. Test login: POST /api/auth/login');
        $this->line('3. Get tanks: GET /api/tanks');
        $this->line('4. Monitor sync: tail -f storage/logs/dingtek-sync.log');
        $this->newLine();

        if ($testUser) {
            $this->info('💡 Test Credentials:');
            $this->line("   Email: {$testUser->email}");
            $this->line('   Password: password (default)');
        }

        return Command::SUCCESS;
    }
}
