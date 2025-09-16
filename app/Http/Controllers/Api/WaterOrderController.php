<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WaterOrder;
use App\Models\Tank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WaterOrderController extends Controller
{

    /**
     * POST /api/orders - Create new water order
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $validator = Validator::make($request->all(), [
                'tank_id' => [
                    'required',
                    'string',
                    Rule::exists('tanks', 'id')->where(function ($query) use ($organizationId) {
                        return $query->where('organization_id', $organizationId);
                    }),
                ],
                'volume_liters' => 'required|integer|min:100|max:50000',
                'delivery_date' => 'required|date|after_or_equal:today',
                'delivery_time_slot' => 'required|string|in:morning,afternoon,evening',
                'delivery_address' => 'nullable|string|max:500',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify tank belongs to organization
            $tank = Tank::where('id', $request->tank_id)
                ->where('organization_id', $organizationId)
                ->first();

            if (!$tank) {
                return response()->json([
                    'message' => 'Tank not found'
                ], 404);
            }

            // Calculate price (basic pricing - could be more complex)
            $pricePerLiter = 0.5; // Example price
            $price = $request->volume_liters * $pricePerLiter;

            $order = WaterOrder::create([
                'organization_id' => $organizationId,
                'tank_id' => $request->tank_id,
                'user_id' => $user->id,
                'volume_liters' => $request->volume_liters,
                'price' => $price,
                'currency' => 'USD',
                'status' => 'pending',
                'delivery_date' => $request->delivery_date,
                'delivery_time_slot' => $request->delivery_time_slot,
                'delivery_address' => $request->delivery_address ?: $tank->location,
                'notes' => $request->notes,
            ]);

            // Load relationships for response
            $order->load(['tank', 'user']);

            return response()->json([
                'id' => $order->id,
                'order_number' => $order->order_number,
                'volume_liters' => $order->volume_liters,
                'price' => $order->price,
                'currency' => $order->currency,
                'status' => $order->status,
                'delivery_date' => $order->delivery_date->toDateString(),
                'delivery_time_slot' => $order->delivery_time_slot,
                'delivery_address' => $order->delivery_address,
                'notes' => $order->notes,
                'tank' => [
                    'id' => $order->tank->id,
                    'name' => $order->tank->name,
                    'location' => $order->tank->location,
                ],
                'user' => [
                    'id' => $order->user->id,
                    'name' => $order->user->full_name,
                    'email' => $order->user->email,
                ],
                'created_at' => $order->created_at->toISOString(),
                'updated_at' => $order->updated_at->toISOString(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create water order',
                'error' => 'Unable to process order'
            ], 500);
        }
    }

    /**
     * GET /api/orders - Return paginated orders with filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $validator = Validator::make($request->all(), [
                'status' => 'nullable|string|in:pending,confirmed,dispatched,delivered,cancelled',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'tank_id' => 'nullable|string|exists:tanks,id',
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = WaterOrder::where('organization_id', $organizationId)
                ->with(['tank', 'user', 'driver'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('start_date')) {
                $query->where('delivery_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->where('delivery_date', '<=', $request->end_date);
            }

            if ($request->has('tank_id')) {
                $query->where('tank_id', $request->tank_id);
            }

            $perPage = min($request->get('per_page', 10), 50);
            $orders = $query->paginate($perPage);

            $data = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'volume_liters' => $order->volume_liters,
                    'price' => $order->price,
                    'currency' => $order->currency,
                    'status' => $order->status,
                    'delivery_date' => $order->delivery_date->toDateString(),
                    'delivery_time_slot' => $order->delivery_time_slot,
                    'delivery_address' => $order->delivery_address,
                    'notes' => $order->notes,
                    'delivered_at' => $order->delivered_at?->toISOString(),
                    'tank' => [
                        'id' => $order->tank->id,
                        'name' => $order->tank->name,
                        'location' => $order->tank->location,
                    ],
                    'user' => [
                        'id' => $order->user->id,
                        'name' => $order->user->full_name,
                        'email' => $order->user->email,
                    ],
                    'driver' => $order->driver ? [
                        'id' => $order->driver->id,
                        'name' => $order->driver->full_name,
                        'phone' => $order->driver->phone,
                    ] : null,
                    'created_at' => $order->created_at->toISOString(),
                    'updated_at' => $order->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'data' => $data,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch orders',
                'error' => 'Unable to retrieve order data'
            ], 500);
        }
    }

    /**
     * GET /api/orders/{id} - Return single order with relationships
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $order = WaterOrder::where('id', $id)
                ->where('organization_id', $organizationId)
                ->with(['tank', 'user', 'driver'])
                ->first();

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'id' => $order->id,
                'order_number' => $order->order_number,
                'volume_liters' => $order->volume_liters,
                'price' => $order->price,
                'currency' => $order->currency,
                'status' => $order->status,
                'delivery_date' => $order->delivery_date->toDateString(),
                'delivery_time_slot' => $order->delivery_time_slot,
                'delivery_address' => $order->delivery_address,
                'notes' => $order->notes,
                'delivered_at' => $order->delivered_at?->toISOString(),
                'tank' => [
                    'id' => $order->tank->id,
                    'name' => $order->tank->name,
                    'location' => $order->tank->location,
                    'capacity_liters' => $order->tank->capacity_liters,
                ],
                'user' => [
                    'id' => $order->user->id,
                    'name' => $order->user->full_name,
                    'email' => $order->user->email,
                    'phone' => $order->user->phone,
                ],
                'driver' => $order->driver ? [
                    'id' => $order->driver->id,
                    'name' => $order->driver->full_name,
                    'email' => $order->driver->email,
                    'phone' => $order->driver->phone,
                ] : null,
                'created_at' => $order->created_at->toISOString(),
                'updated_at' => $order->updated_at->toISOString(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch order details',
                'error' => 'Unable to retrieve order data'
            ], 500);
        }
    }

    /**
     * POST /api/orders/{id}/cancel - Cancel an order
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $organizationId = $user->organization_id;

            $order = WaterOrder::where('id', $id)
                ->where('organization_id', $organizationId)
                ->first();

            if (!$order) {
                return response()->json([
                    'message' => 'Order not found'
                ], 404);
            }

            // Check if order can be cancelled
            if (in_array($order->status, ['delivered', 'cancelled'])) {
                return response()->json([
                    'message' => 'Order cannot be cancelled',
                    'error' => 'Order is already ' . $order->status
                ], 400);
            }

            // Only allow cancellation if order is not dispatched or if it's the same user or admin
            if ($order->status === 'dispatched' && $order->user_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'message' => 'Order cannot be cancelled',
                    'error' => 'Order is already dispatched'
                ], 400);
            }

            $order->update([
                'status' => 'cancelled'
            ]);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'updated_at' => $order->updated_at->toISOString(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel order',
                'error' => 'Unable to process cancellation'
            ], 500);
        }
    }
}
