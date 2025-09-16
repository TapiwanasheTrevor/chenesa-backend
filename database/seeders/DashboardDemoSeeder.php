<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Tank;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\WaterOrder;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Create demo organization
        $organization = Organization::create([
            'name' => 'Chenesa Demo Company',
            'type' => 'commercial',
            'contact_email' => 'demo@chenesa.io',
            'contact_phone' => '+263 4 123 4567',
            'address' => 'Harare Business District',
            'city' => 'Harare',
            'country' => 'zimbabwe',
            'subscription_status' => 'active',
        ]);

        // Create demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@chenesa.io'],
            [
                'organization_id' => $organization->id,
                'first_name' => 'Demo',
                'last_name' => 'User',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'phone' => '+263 4 123 4567',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create demo sensors
        $sensors = [];
        for ($i = 1; $i <= 10; $i++) {
            $sensors[] = Sensor::create([
                'device_id' => 'SENSOR_' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'imei' => '86012345678901' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'sim_number' => '+263712345' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'model' => 'DF555',
                'firmware_version' => '1.2.' . $faker->numberBetween(1, 5),
                'status' => $faker->randomElement(['active', 'inactive', 'maintenance']),
                'battery_level' => $faker->numberBetween(20, 100),
                'signal_strength' => $faker->numberBetween(1, 5),
                'last_seen' => now()->subMinutes($faker->numberBetween(1, 60)),
                'installation_date' => $faker->dateTimeBetween('-1 year', 'now'),
            ]);
        }

        // Create demo tanks with realistic Zimbabwe/South Africa locations
        $tankLocations = [
            ['name' => 'Harare Central Tank', 'location' => 'Harare CBD', 'lat' => -17.8277, 'lng' => 31.0534],
            ['name' => 'Bulawayo Industrial Tank', 'location' => 'Bulawayo Industrial Site', 'lat' => -20.1378, 'lng' => 28.5906],
            ['name' => 'Gweru Municipal Tank', 'location' => 'Gweru Township', 'lat' => -19.4328, 'lng' => 29.8186],
            ['name' => 'Mutare Hospital Tank', 'location' => 'Mutare General Hospital', 'lat' => -18.9707, 'lng' => 32.6408],
            ['name' => 'Cape Town District Tank', 'location' => 'Cape Town, SA', 'lat' => -33.9249, 'lng' => 18.4241],
            ['name' => 'Johannesburg North Tank', 'location' => 'Johannesburg, SA', 'lat' => -26.2041, 'lng' => 28.0473],
            ['name' => 'Durban Coastal Tank', 'location' => 'Durban, SA', 'lat' => -29.8587, 'lng' => 31.0218],
            ['name' => 'Masvingo Rural Tank', 'location' => 'Masvingo District', 'lat' => -20.0633, 'lng' => 30.8275],
            ['name' => 'Chitungwiza Residential', 'location' => 'Chitungwiza', 'lat' => -18.0186, 'lng' => 31.0792],
            ['name' => 'Victoria Falls Hotel Tank', 'location' => 'Victoria Falls', 'lat' => -17.9322, 'lng' => 25.8309],
        ];

        $tanks = [];
        foreach ($tankLocations as $index => $location) {
            $sensor = $sensors[$index] ?? $sensors[0];

            $tank = Tank::create([
                'organization_id' => $organization->id,
                'sensor_id' => $sensor->id,
                'name' => $location['name'],
                'location' => $location['location'],
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
                'capacity_liters' => $faker->randomElement([5000, 10000, 15000, 20000, 25000]),
                'height_mm' => $faker->numberBetween(2000, 5000),
                'diameter_mm' => $faker->numberBetween(1500, 3000),
                'shape' => $faker->randomElement(['cylindrical', 'rectangular']),
                'material' => $faker->randomElement(['steel', 'plastic', 'concrete']),
                'installation_height_mm' => $faker->numberBetween(100, 500),
                'low_level_threshold' => 20,
                'critical_level_threshold' => 10,
                'refill_enabled' => true,
                'auto_refill_threshold' => 30,
            ]);

            $tanks[] = $tank;

            // Create sensor readings for the last 30 days
            for ($day = 30; $day >= 0; $day--) {
                for ($reading = 0; $reading < $faker->numberBetween(2, 8); $reading++) {
                    $date = now()->subDays($day)->addHours($faker->numberBetween(0, 23));
                    $waterLevel = $faker->numberBetween(5, 95);

                    SensorReading::create([
                        'sensor_id' => $sensor->id,
                        'tank_id' => $tank->id,
                        'distance_mm' => $faker->numberBetween(100, 4000),
                        'water_level_mm' => round($tank->height_mm * $waterLevel / 100),
                        'water_level_percentage' => $waterLevel,
                        'volume_liters' => round($tank->capacity_liters * $waterLevel / 100),
                        'temperature' => $faker->numberBetween(15, 35),
                        'battery_voltage' => $faker->numberBetween(3.2, 4.2),
                        'signal_rssi' => $faker->numberBetween(-100, -50),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                }
            }
        }

        // Create water orders
        foreach ($tanks as $index => $tank) {
            for ($i = 0; $i < $faker->numberBetween(1, 3); $i++) {
                WaterOrder::create([
                    'tank_id' => $tank->id,
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'volume_liters' => $faker->numberBetween(1000, min(5000, $tank->capacity_liters)),
                    'price' => $faker->numberBetween(100, 500),
                    'currency' => 'USD',
                    'status' => $faker->randomElement(['pending', 'confirmed', 'delivered', 'cancelled']),
                    'delivery_address' => $tank->location,
                    'delivery_date' => $faker->dateTimeBetween('-30 days', '+7 days'),
                    'delivery_time_slot' => $faker->randomElement(['morning', 'afternoon', 'evening']),
                    'notes' => $faker->optional()->sentence,
                    'created_at' => $faker->dateTimeBetween('-30 days', 'now'),
                ]);
            }
        }

        // Create alerts
        foreach ($tanks as $tank) {
            for ($i = 0; $i < $faker->numberBetween(0, 3); $i++) {
                $isResolved = $faker->boolean(30); // 30% chance of being resolved
                Alert::create([
                    'tank_id' => $tank->id,
                    'organization_id' => $organization->id,
                    'type' => $faker->randomElement(['low_water', 'critical_water', 'sensor_offline', 'refill_reminder', 'maintenance']),
                    'severity' => $faker->randomElement(['info', 'warning', 'critical']),
                    'is_resolved' => $isResolved,
                    'resolved_at' => $isResolved ? $faker->dateTimeBetween('-7 days', 'now') : null,
                    'title' => $faker->randomElement([
                        'Low Water Level Alert',
                        'Critical Water Level',
                        'Refill Required',
                        'System Maintenance',
                        'Sensor Offline',
                        'Connection Issue'
                    ]),
                    'message' => $faker->sentence,
                    'created_at' => $faker->dateTimeBetween('-7 days', 'now'),
                ]);
            }
        }

        $this->command->info('Dashboard demo data created successfully!');
    }
}