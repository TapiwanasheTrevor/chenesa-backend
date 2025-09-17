<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionPlanController extends Controller
{
    /**
     * Get all available subscription plans
     */
    public function index(Request $request)
    {
        // This is a public endpoint, user may not be authenticated
        $organization = $request->user() ? $request->user()->organization : null;
        $currentPlanId = $organization ? $organization->subscription_plan_id : null;

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price', 'asc')
            ->get()
            ->map(function ($plan) use ($currentPlanId) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => $plan->price,
                    'currency' => $plan->currency,
                    'billing_cycle' => $plan->billing_cycle,
                    'max_tanks' => $plan->max_tanks,
                    'max_users' => $plan->max_users,
                    'features' => $plan->features,
                    'is_current' => $plan->id === $currentPlanId,
                    'is_popular' => $plan->name === 'Professional', // Mark popular plan
                    'savings' => $this->calculateSavings($plan),
                ];
            });

        return response()->json([
            'data' => $plans
        ]);
    }

    /**
     * Get specific subscription plan details
     */
    public function show(Request $request, $id)
    {
        $plan = SubscriptionPlan::where('is_active', true)->findOrFail($id);
        $organization = $request->user() ? $request->user()->organization : null;

        return response()->json([
            'data' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'price' => $plan->price,
                'currency' => $plan->currency,
                'billing_cycle' => $plan->billing_cycle,
                'max_tanks' => $plan->max_tanks,
                'max_users' => $plan->max_users,
                'features' => $plan->features,
                'is_current' => $organization && $plan->id === $organization->subscription_plan_id,
                'compatibility' => $this->checkPlanCompatibility($plan, $organization),
            ]
        ]);
    }

    /**
     * Compare subscription plans
     */
    public function compare(Request $request)
    {
        $planIds = $request->input('plan_ids', []);

        if (empty($planIds)) {
            $plans = SubscriptionPlan::where('is_active', true)
                ->orderBy('price', 'asc')
                ->limit(3)
                ->get();
        } else {
            $plans = SubscriptionPlan::where('is_active', true)
                ->whereIn('id', $planIds)
                ->orderBy('price', 'asc')
                ->get();
        }

        $comparison = $plans->map(function ($plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'price' => $plan->price,
                'currency' => $plan->currency,
                'billing_cycle' => $plan->billing_cycle,
                'features' => [
                    'max_tanks' => $plan->max_tanks,
                    'max_users' => $plan->max_users,
                    'features_list' => $plan->features,
                ],
                'monthly_cost' => $this->calculateMonthlyCost($plan),
            ];
        });

        return response()->json([
            'data' => $comparison
        ]);
    }

    /**
     * Calculate savings for annual plans
     */
    private function calculateSavings(SubscriptionPlan $plan)
    {
        if ($plan->billing_cycle === 'annual') {
            $monthlyEquivalent = $plan->price / 12;
            // Assume 20% discount for annual plans
            $regularMonthlyPrice = $monthlyEquivalent / 0.8;
            $savings = ($regularMonthlyPrice * 12) - $plan->price;

            return [
                'amount' => round($savings, 2),
                'percentage' => 20,
                'currency' => $plan->currency
            ];
        }

        return null;
    }

    /**
     * Calculate monthly cost for comparison
     */
    private function calculateMonthlyCost(SubscriptionPlan $plan)
    {
        return match ($plan->billing_cycle) {
            'monthly' => $plan->price,
            'annual' => round($plan->price / 12, 2),
            'quarterly' => round($plan->price / 3, 2),
            default => $plan->price
        };
    }

    /**
     * Check if plan is compatible with current usage
     */
    private function checkPlanCompatibility(SubscriptionPlan $plan, $organization)
    {
        if (!$organization) {
            return ['compatible' => true, 'issues' => []];
        }

        $issues = [];
        $currentTanks = $organization->tanks()->count();
        $currentUsers = $organization->users()->count();

        if ($currentTanks > $plan->max_tanks) {
            $issues[] = "You currently have {$currentTanks} tanks, but this plan only supports {$plan->max_tanks} tanks.";
        }

        if ($currentUsers > $plan->max_users) {
            $issues[] = "You currently have {$currentUsers} users, but this plan only supports {$plan->max_users} users.";
        }

        return [
            'compatible' => empty($issues),
            'issues' => $issues
        ];
    }
}