<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    /**
     * Get the authenticated user's organization details
     */
    public function show(Request $request)
    {
        $organization = $request->user()->organization;

        if (!$organization) {
            return response()->json([
                'error' => 'No organization found for this user'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'type' => $organization->type,
                'address' => $organization->address,
                'city' => $organization->city,
                'country' => $organization->country,
                'contact_email' => $organization->contact_email,
                'contact_phone' => $organization->contact_phone,
                'subscription_status' => $organization->subscription_status,
                'subscription_plan' => $organization->subscriptionPlan ? [
                    'id' => $organization->subscriptionPlan->id,
                    'name' => $organization->subscriptionPlan->name,
                    'description' => $organization->subscriptionPlan->description,
                    'price' => $organization->subscriptionPlan->price,
                    'currency' => $organization->subscriptionPlan->currency,
                    'billing_cycle' => $organization->subscriptionPlan->billing_cycle,
                    'max_tanks' => $organization->subscriptionPlan->max_tanks,
                    'max_users' => $organization->subscriptionPlan->max_users,
                    'features' => $organization->subscriptionPlan->features,
                ] : null,
                'usage' => [
                    'tanks_count' => $organization->tanks()->count(),
                    'users_count' => $organization->users()->count(),
                    'orders_count' => $organization->waterOrders()->count(),
                ],
                'created_at' => $organization->created_at,
                'updated_at' => $organization->updated_at,
            ]
        ]);
    }

    /**
     * Update organization details
     */
    public function update(Request $request)
    {
        $organization = $request->user()->organization;

        if (!$organization) {
            return response()->json([
                'error' => 'No organization found for this user'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => ['sometimes', 'required', Rule::in(['residential', 'commercial', 'industrial', 'municipal'])],
            'address' => 'sometimes|nullable|string|max:500',
            'city' => 'sometimes|nullable|string|max:255',
            'country' => 'sometimes|required|string|max:255',
            'contact_email' => 'sometimes|required|email|max:255',
            'contact_phone' => 'sometimes|nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $organization->update($validator->validated());

        return response()->json([
            'message' => 'Organization updated successfully',
            'data' => $organization->fresh()
        ]);
    }

    /**
     * Get subscription details with usage analytics
     */
    public function getSubscription(Request $request)
    {
        $organization = $request->user()->organization;

        if (!$organization) {
            return response()->json([
                'error' => 'No organization found for this user'
            ], 404);
        }

        $subscriptionPlan = $organization->subscriptionPlan;
        $usage = [
            'tanks' => [
                'used' => $organization->tanks()->count(),
                'limit' => $subscriptionPlan ? $subscriptionPlan->max_tanks : 0,
                'percentage' => $subscriptionPlan && $subscriptionPlan->max_tanks > 0
                    ? round(($organization->tanks()->count() / $subscriptionPlan->max_tanks) * 100, 2)
                    : 0
            ],
            'users' => [
                'used' => $organization->users()->count(),
                'limit' => $subscriptionPlan ? $subscriptionPlan->max_users : 0,
                'percentage' => $subscriptionPlan && $subscriptionPlan->max_users > 0
                    ? round(($organization->users()->count() / $subscriptionPlan->max_users) * 100, 2)
                    : 0
            ],
        ];

        return response()->json([
            'data' => [
                'status' => $organization->subscription_status,
                'plan' => $subscriptionPlan,
                'usage' => $usage,
                'features_available' => $subscriptionPlan ? $subscriptionPlan->features : [],
                'next_billing_date' => null, // TODO: Implement billing dates
                'subscription_started' => $organization->created_at,
            ]
        ]);
    }

    /**
     * Get available subscription plans for upgrade
     */
    public function getAvailablePlans(Request $request)
    {
        $organization = $request->user()->organization;
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
                    'is_upgrade' => $currentPlanId ? $plan->price > SubscriptionPlan::find($currentPlanId)?->price : true,
                ];
            });

        return response()->json([
            'data' => $plans
        ]);
    }

    /**
     * Upgrade subscription plan
     */
    public function upgradeSubscription(Request $request)
    {
        $organization = $request->user()->organization;

        if (!$organization) {
            return response()->json([
                'error' => 'No organization found for this user'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|uuid|exists:subscription_plans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $newPlan = SubscriptionPlan::findOrFail($request->plan_id);

        if (!$newPlan->is_active) {
            return response()->json([
                'error' => 'Selected plan is not available'
            ], 400);
        }

        // Check if it's actually an upgrade
        $currentPlan = $organization->subscriptionPlan;
        if ($currentPlan && $newPlan->price <= $currentPlan->price) {
            return response()->json([
                'error' => 'You can only upgrade to a higher tier plan'
            ], 400);
        }

        // Update the organization's subscription
        $organization->update([
            'subscription_plan_id' => $newPlan->id,
            'subscription_status' => 'active'
        ]);

        // TODO: Integrate with payment processor
        // TODO: Create billing record
        // TODO: Send confirmation email

        return response()->json([
            'message' => 'Subscription upgraded successfully',
            'data' => [
                'old_plan' => $currentPlan,
                'new_plan' => $newPlan,
                'effective_date' => now(),
            ]
        ]);
    }

    /**
     * Get billing history (placeholder - to be implemented with payment system)
     */
    public function getBillingHistory(Request $request)
    {
        $organization = $request->user()->organization;

        if (!$organization) {
            return response()->json([
                'error' => 'No organization found for this user'
            ], 404);
        }

        // TODO: Implement actual billing history from payment system
        // For now, return placeholder data
        return response()->json([
            'data' => [
                'current_period' => [
                    'start' => now()->startOfMonth(),
                    'end' => now()->endOfMonth(),
                    'amount' => $organization->subscriptionPlan?->price ?? 0,
                    'currency' => $organization->subscriptionPlan?->currency ?? 'USD',
                    'status' => 'paid',
                ],
                'billing_history' => [], // Placeholder for past invoices
                'next_billing' => [
                    'date' => now()->addMonth()->startOfMonth(),
                    'amount' => $organization->subscriptionPlan?->price ?? 0,
                    'currency' => $organization->subscriptionPlan?->currency ?? 'USD',
                ]
            ]
        ]);
    }

    /**
     * Get organization statistics for mobile dashboard
     */
    public function getStatistics(Request $request)
    {
        $organization = $request->user()->organization;

        if (!$organization) {
            return response()->json([
                'error' => 'No organization found for this user'
            ], 404);
        }

        $stats = [
            'tanks' => [
                'total' => $organization->tanks()->count(),
                'active' => $organization->tanks()->where('status', 'active')->count(),
                'alerts' => $organization->tanks()->whereHas('alerts', function($query) {
                    $query->where('status', 'active');
                })->count(),
            ],
            'users' => [
                'total' => $organization->users()->count(),
                'active' => $organization->users()->where('is_active', true)->count(),
            ],
            'orders' => [
                'total' => $organization->waterOrders()->count(),
                'this_month' => $organization->waterOrders()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'pending' => $organization->waterOrders()->where('status', 'pending')->count(),
            ],
            'subscription' => [
                'plan' => $organization->subscriptionPlan?->name,
                'status' => $organization->subscription_status,
                'tanks_usage' => $organization->subscriptionPlan
                    ? round(($organization->tanks()->count() / $organization->subscriptionPlan->max_tanks) * 100, 1)
                    : 0,
            ]
        ];

        return response()->json([
            'data' => $stats
        ]);
    }
}