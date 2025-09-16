<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create a subscription plan first
        $plan = SubscriptionPlan::create([
            'name' => 'Admin Plan',
            'description' => 'Full access plan for administrators',
            'price' => 0,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'max_tanks' => 999,
            'max_users' => 999,
            'is_active' => true,
        ]);

        // Create an organization
        $organization = Organization::create([
            'name' => 'Chenesa Admin',
            'type' => 'commercial',
            'country' => 'zimbabwe',
            'contact_email' => 'admin@chenesa.io',
            'contact_phone' => '+263123456789',
            'subscription_status' => 'active',
            'subscription_plan_id' => $plan->id,
        ]);

        // Create admin user
        User::create([
            'organization_id' => $organization->id,
            'email' => 'admin@chenesa.io',
            'password' => Hash::make('password123'),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@chenesa.io');
        $this->command->info('Password: password123');
    }
}