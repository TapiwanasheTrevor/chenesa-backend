<?php

use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ProfileController;
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
        Route::get('/{id}', [TankController::class, 'show']);
        Route::get('/{id}/history', [TankController::class, 'history']);
        Route::patch('/{id}/settings', [TankController::class, 'updateSettings']);
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
});

// Fallback route for unmatched API requests
Route::fallback(function () {
    return response()->json([
        'message' => 'API endpoint not found'
    ], 404);
});