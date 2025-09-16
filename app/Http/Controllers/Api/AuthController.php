<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account is deactivated'
            ], 403);
        }

        // Update last login
        $user->update(['last_login' => now()]);

        // Create API token
        $token = $user->createToken('api-token')->plainTextToken;

        // Create a refresh token (simplified - store as personal access token)
        $refreshToken = $user->createToken('refresh-token', ['refresh'])->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'organization_id' => $user->organization_id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'last_login' => $user->last_login?->toISOString(),
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ],
            'token' => $token,
            'refresh_token' => $refreshToken
        ], 200);
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'organization_name' => 'required|string|max:255',
            'country' => 'required|string|in:zimbabwe,south_africa',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create organization first
            $organization = Organization::create([
                'name' => $request->organization_name,
                'country' => $request->country,
                'contact_email' => $request->email,
                'contact_phone' => $request->phone,
                'is_active' => true,
            ]);

            // Create user
            $user = User::create([
                'organization_id' => $organization->id,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'role' => 'admin', // First user is admin
                'is_active' => true,
                'last_login' => now(),
            ]);

            // Create API token
            $token = $user->createToken('api-token')->plainTextToken;

            // Create a refresh token
            $refreshToken = $user->createToken('refresh-token', ['refresh'])->plainTextToken;

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'organization_id' => $user->organization_id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'last_login' => $user->last_login?->toISOString(),
                    'created_at' => $user->created_at->toISOString(),
                    'updated_at' => $user->updated_at->toISOString(),
                ],
                'token' => $token,
                'refresh_token' => $refreshToken
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => 'Unable to create account'
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Successfully logged out'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed'
            ], 500);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find the refresh token
            $tokenParts = explode('|', $request->refresh_token);
            if (count($tokenParts) !== 2) {
                return response()->json([
                    'message' => 'Invalid refresh token format'
                ], 401);
            }

            $token = PersonalAccessToken::findToken($request->refresh_token);

            if (!$token || !$token->can('refresh')) {
                return response()->json([
                    'message' => 'Invalid refresh token'
                ], 401);
            }

            $user = $token->tokenable;

            if (!$user->is_active) {
                return response()->json([
                    'message' => 'Account is deactivated'
                ], 403);
            }

            // Revoke old tokens
            $user->tokens()->delete();

            // Create new tokens
            $newToken = $user->createToken('api-token')->plainTextToken;
            $newRefreshToken = $user->createToken('refresh-token', ['refresh'])->plainTextToken;

            return response()->json([
                'token' => $newToken,
                'refresh_token' => $newRefreshToken
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token refresh failed'
            ], 500);
        }
    }
}