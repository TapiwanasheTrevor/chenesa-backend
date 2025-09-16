<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AlertController extends Controller
{

    /**
     * GET /api/alerts - Return paginated alerts with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $validator = Validator::make($request->all(), [
                'severity' => 'nullable|string|in:low,medium,high,critical',
                'type' => 'nullable|string|in:low_level,critical_level,sensor_offline,system_error,refill_needed',
                'is_resolved' => 'nullable|boolean',
                'tank_id' => 'nullable|string|exists:tanks,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Alert::where('organization_id', $organizationId)
                ->with(['tank'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('severity')) {
                $query->where('severity', $request->severity);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('is_resolved')) {
                $query->where('is_resolved', $request->boolean('is_resolved'));
            }

            if ($request->has('tank_id')) {
                $query->where('tank_id', $request->tank_id);
            }

            if ($request->has('start_date')) {
                $query->where('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
            }

            // Default to showing unresolved alerts first
            if (!$request->has('is_resolved')) {
                $query->orderBy('is_resolved', 'asc');
            }

            $perPage = min($request->get('per_page', 20), 50);
            $alerts = $query->paginate($perPage);

            $data = $alerts->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'severity' => $alert->severity,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'is_resolved' => $alert->is_resolved,
                    'resolved_at' => $alert->resolved_at?->toISOString(),
                    'tank' => $alert->tank ? [
                        'id' => $alert->tank->id,
                        'name' => $alert->tank->name,
                        'location' => $alert->tank->location,
                    ] : null,
                    'created_at' => $alert->created_at->toISOString(),
                    'updated_at' => $alert->updated_at->toISOString(),
                ];
            });

            // Get summary statistics
            $summary = [
                'total' => $alerts->total(),
                'unresolved' => Alert::where('organization_id', $organizationId)
                    ->where('is_resolved', false)
                    ->count(),
                'critical' => Alert::where('organization_id', $organizationId)
                    ->where('is_resolved', false)
                    ->where('severity', 'critical')
                    ->count(),
                'high' => Alert::where('organization_id', $organizationId)
                    ->where('is_resolved', false)
                    ->where('severity', 'high')
                    ->count(),
                'medium' => Alert::where('organization_id', $organizationId)
                    ->where('is_resolved', false)
                    ->where('severity', 'medium')
                    ->count(),
                'low' => Alert::where('organization_id', $organizationId)
                    ->where('is_resolved', false)
                    ->where('severity', 'low')
                    ->count(),
            ];

            return response()->json([
                'data' => $data,
                'summary' => $summary,
                'pagination' => [
                    'current_page' => $alerts->currentPage(),
                    'per_page' => $alerts->perPage(),
                    'total' => $alerts->total(),
                    'last_page' => $alerts->lastPage(),
                    'from' => $alerts->firstItem(),
                    'to' => $alerts->lastItem(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch alerts',
                'error' => 'Unable to retrieve alert data'
            ], 500);
        }
    }

    /**
     * PATCH /api/alerts/{id}/resolve - Mark alert as resolved
     */
    public function resolve(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $alert = Alert::where('id', $id)
                ->where('organization_id', $organizationId)
                ->first();

            if (!$alert) {
                return response()->json([
                    'message' => 'Alert not found'
                ], 404);
            }

            if ($alert->is_resolved) {
                return response()->json([
                    'message' => 'Alert is already resolved',
                    'alert' => [
                        'id' => $alert->id,
                        'is_resolved' => $alert->is_resolved,
                        'resolved_at' => $alert->resolved_at?->toISOString(),
                    ]
                ], 200);
            }

            $alert->update([
                'is_resolved' => true,
                'resolved_at' => now(),
            ]);

            $alert->load('tank');

            return response()->json([
                'message' => 'Alert resolved successfully',
                'alert' => [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'severity' => $alert->severity,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'is_resolved' => $alert->is_resolved,
                    'resolved_at' => $alert->resolved_at?->toISOString(),
                    'tank' => $alert->tank ? [
                        'id' => $alert->tank->id,
                        'name' => $alert->tank->name,
                        'location' => $alert->tank->location,
                    ] : null,
                    'created_at' => $alert->created_at->toISOString(),
                    'updated_at' => $alert->updated_at->toISOString(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to resolve alert',
                'error' => 'Unable to update alert status'
            ], 500);
        }
    }
}
