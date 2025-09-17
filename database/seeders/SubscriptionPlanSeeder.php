<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic Plan',
                'description' => 'Perfect for small businesses and personal use. Monitor up to 3 tanks with essential features.',
                'price' => 29.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'max_tanks' => 3,
                'max_users' => 2,
                'features' => [
                    'Real-time monitoring',
                    'Mobile app access',
                    'Email alerts',
                    'Basic analytics',
                    'Standard support'
                ],
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Professional Plan',
                'description' => 'Ideal for growing businesses. Enhanced monitoring capabilities with advanced features.',
                'price' => 79.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'max_tanks' => 10,
                'max_users' => 5,
                'features' => [
                    'Real-time monitoring',
                    'Mobile app access',
                    'Email & SMS alerts',
                    'Advanced analytics',
                    'Custom reports',
                    'API access',
                    'Priority support',
                    'Data export'
                ],
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Enterprise Plan',
                'description' => 'Comprehensive solution for large organizations. Unlimited monitoring with premium features.',
                'price' => 199.99,
                'currency' => 'USD',
                'billing_cycle' => 'monthly',
                'max_tanks' => 50,
                'max_users' => 20,
                'features' => [
                    'Real-time monitoring',
                    'Mobile app access',
                    'Email & SMS alerts',
                    'Advanced analytics',
                    'Custom reports',
                    'API access',
                    'Multi-user access',
                    'White-label options',
                    'Custom integrations',
                    'Dedicated support',
                    'SLA guarantee',
                    'Data export',
                    'Custom dashboards'
                ],
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Basic Annual',
                'description' => 'Basic plan with annual billing. Save 20% compared to monthly billing.',
                'price' => 287.90,
                'currency' => 'USD',
                'billing_cycle' => 'annually',
                'max_tanks' => 3,
                'max_users' => 2,
                'features' => [
                    'Real-time monitoring',
                    'Mobile app access',
                    'Email alerts',
                    'Basic analytics',
                    'Standard support'
                ],
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Professional Annual',
                'description' => 'Professional plan with annual billing. Save 20% compared to monthly billing.',
                'price' => 767.90,
                'currency' => 'USD',
                'billing_cycle' => 'annually',
                'max_tanks' => 10,
                'max_users' => 5,
                'features' => [
                    'Real-time monitoring',
                    'Mobile app access',
                    'Email & SMS alerts',
                    'Advanced analytics',
                    'Custom reports',
                    'API access',
                    'Priority support',
                    'Data export'
                ],
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}