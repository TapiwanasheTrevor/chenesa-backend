<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\SensorManagementController;
use App\Http\Controllers\Api\SubscriptionPlanController;
use App\Http\Controllers\Api\TankController;
use App\Http\Controllers\Api\WaterOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::get('/health', [HealthController::class, 'check']);

// Public subscription plans (for registration/upgrade views)
Route::prefix('subscription-plans')->group(function () {
    Route::get('/', [SubscriptionPlanController::class, 'index']);
    Route::get('/compare', [SubscriptionPlanController::class, 'compare']);
    Route::get('/{id}', [SubscriptionPlanController::class, 'show']);
});

// Sensor data endpoints (for IoT devices)
Route::prefix('sensors')->middleware('sensor.auth')->group(function () {
    // Endpoint for Dingtek DF555 sensor data
    Route::post('/dingtek/data', [SensorController::class, 'receiveDingtekData']);

    // Alternative endpoints for different data formats
    Route::put('/dingtek/data', [SensorController::class, 'receiveDingtekData']);
    Route::patch('/dingtek/data', [SensorController::class, 'receiveDingtekData']);

    // Status endpoint for debugging
    Route::get('/status', [SensorController::class, 'getSensorStatus']);
});

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Tank routes
    Route::prefix('tanks')->group(function () {
        Route::get('/', [TankController::class, 'index']);
        Route::post('/', [TankController::class, 'store']);
        Route::get('/{id}', [TankController::class, 'show']);
        Route::put('/{id}', [TankController::class, 'update']);
        Route::delete('/{id}', [TankController::class, 'destroy']);
        Route::get('/{id}/history', [TankController::class, 'history']);
        Route::get('/{id}/analytics', [TankController::class, 'analytics']);
        Route::get('/{id}/live-status', [TankController::class, 'liveStatus']);
        Route::post('/{id}/calibrate', [TankController::class, 'calibrate']);
        Route::patch('/{id}/settings', [TankController::class, 'updateSettings']);
    });

    // Sensor Management routes
    Route::prefix('sensors')->group(function () {
        Route::get('/', [SensorManagementController::class, 'index']);
        Route::post('/', [SensorManagementController::class, 'store']);
        Route::get('/{id}', [SensorManagementController::class, 'show']);
        Route::put('/{id}', [SensorManagementController::class, 'update']);
        Route::delete('/{id}', [SensorManagementController::class, 'destroy']);
        Route::get('/{id}/readings', [SensorManagementController::class, 'getReadings']);
        Route::post('/{id}/test', [SensorManagementController::class, 'testSensor']);
        Route::patch('/{id}/settings', [SensorManagementController::class, 'updateSettings']);
    });

    // Water Order routes
    Route::prefix('orders')->group(function () {
        Route::post('/', [WaterOrderController::class, 'create']);
        Route::get('/', [WaterOrderController::class, 'index']);
        Route::get('/{id}', [WaterOrderController::class, 'show']);
        Route::post('/{id}/cancel', [WaterOrderController::class, 'cancel']);
    });

    // Alert routes
    Route::prefix('alerts')->group(function () {
        Route::get('/', [AlertController::class, 'index']);
        Route::patch('/{id}/resolve', [AlertController::class, 'resolve']);
    });

    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::patch('/', [ProfileController::class, 'update']);
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
        Route::post('/fcm-token', [ProfileController::class, 'updateFcmToken']);
    });

    // Dashboard & Analytics routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/overview', [DashboardController::class, 'overview']);
        Route::get('/consumption', [DashboardController::class, 'consumption']);
        Route::get('/costs', [DashboardController::class, 'costs']);
        Route::get('/efficiency', [DashboardController::class, 'efficiency']);
        Route::get('/predictions', [DashboardController::class, 'predictions']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/mark-multiple-read', [NotificationController::class, 'markMultipleAsRead']);
        Route::patch('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::get('/preferences', [NotificationController::class, 'getPreferences']);
        Route::patch('/preferences', [NotificationController::class, 'updatePreferences']);
        Route::post('/test', [NotificationController::class, 'testNotification']);
        Route::get('/statistics', [NotificationController::class, 'getStatistics']);
    });

    // Organization & Subscription routes
    Route::prefix('organization')->group(function () {
        Route::get('/', [OrganizationController::class, 'show']);
        Route::patch('/', [OrganizationController::class, 'update']);
        Route::get('/subscription', [OrganizationController::class, 'getSubscription']);
        Route::get('/subscription/plans', [OrganizationController::class, 'getAvailablePlans']);
        Route::post('/subscription/upgrade', [OrganizationController::class, 'upgradeSubscription']);
        Route::get('/billing-history', [OrganizationController::class, 'getBillingHistory']);
        Route::get('/statistics', [OrganizationController::class, 'getStatistics']);
    });
});

// Fallback route for unmatched API requests
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found'
    ], 404);
});