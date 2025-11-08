<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{

    /**
     * GET /api/profile - Return authenticated user profile
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Load organization relationship
            $user->load('organization');

            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'organization_id' => $user->organization_id,
                    'email' => $user->email ?? '',
                    'first_name' => $user->first_name ?? '',
                    'last_name' => $user->last_name ?? '',
                    'full_name' => $user->full_name ?? '',
                    'phone' => $user->phone ?? '',
                    'role' => $user->role ?? 'user',
                    'is_active' => $user->is_active ?? true,
                    'last_login' => $user->last_login?->toISOString() ?? null,
                    'organization' => $user->organization ? [
                        'id' => $user->organization->id,
                        'name' => $user->organization->name ?? '',
                        'type' => $user->organization->type ?? '',
                        'address' => $user->organization->address ?? '',
                        'city' => $user->organization->city ?? '',
                        'country' => $user->organization->country ?? '',
                        'contact_email' => $user->organization->contact_email ?? '',
                        'contact_phone' => $user->organization->contact_phone ?? '',
                        'subscription_status' => $user->organization->subscription_status ?? 'inactive',
                    ] : null,
                    'created_at' => $user->created_at->toISOString(),
                    'updated_at' => $user->updated_at->toISOString(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch profile',
                'error' => 'Unable to retrieve profile data'
            ], 500);
        }
    }

    /**
     * PATCH /api/profile - Update user profile
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update only provided fields
            $updateData = [];

            if ($request->has('first_name')) {
                $updateData['first_name'] = $request->first_name;
            }

            if ($request->has('last_name')) {
                $updateData['last_name'] = $request->last_name;
            }

            if ($request->has('phone')) {
                $updateData['phone'] = $request->phone;
            }

            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email ?? '',
                    'first_name' => $user->first_name ?? '',
                    'last_name' => $user->last_name ?? '',
                    'full_name' => $user->full_name ?? '',
                    'phone' => $user->phone ?? '',
                    'role' => $user->role ?? 'user',
                    'updated_at' => $user->updated_at->toISOString(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => 'Unable to save profile data'
            ], 500);
        }
    }

    /**
     * POST /api/profile/change-password - Change user password
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
                'new_password_confirmation' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect',
                    'errors' => [
                        'current_password' => ['The current password is incorrect']
                    ]
                ], 422);
            }

            // Check if new password is different from current
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'message' => 'New password must be different from current password',
                    'errors' => [
                        'new_password' => ['New password must be different from current password']
                    ]
                ], 422);
            }

            // Update password
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            // Revoke all existing tokens to force re-authentication
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Password changed successfully. Please log in again.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to change password',
                'error' => 'Unable to update password'
            ], 500);
        }
    }

    /**
     * POST /api/profile/fcm-token - Update FCM token for notifications
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'fcm_token' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user->update([
                'fcm_token' => $request->fcm_token
            ]);

            return response()->json([
                'message' => 'FCM token updated successfully',
                'updated_at' => $user->updated_at->toISOString(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update FCM token',
                'error' => 'Unable to save FCM token'
            ], 500);
        }
    }

    /**
     * GET /api/profile/security-settings - Get security settings
     */
    public function getSecuritySettings(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $settings = $user->security_settings ?? [
                'two_factor_enabled' => false,
                'session_timeout_enabled' => true,
                'session_timeout_minutes' => 30,
                'biometric_enabled' => false,
            ];

            return response()->json([
                'data' => $settings
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch security settings',
                'error' => 'Unable to retrieve security settings'
            ], 500);
        }
    }

    /**
     * PATCH /api/profile/security-settings - Update security settings
     */
    public function updateSecuritySettings(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'two_factor_enabled' => 'boolean',
                'session_timeout_enabled' => 'boolean',
                'session_timeout_minutes' => 'integer|min:5|max:1440',
                'biometric_enabled' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 422);
            }

            $currentSettings = $user->security_settings ?? [];
            $newSettings = array_merge($currentSettings, $request->only([
                'two_factor_enabled',
                'session_timeout_enabled',
                'session_timeout_minutes',
                'biometric_enabled'
            ]));

            $user->update([
                'security_settings' => $newSettings
            ]);

            return response()->json([
                'message' => 'Security settings updated successfully',
                'data' => $newSettings
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update security settings',
                'error' => 'Unable to save security settings'
            ], 500);
        }
    }

    /**
     * POST /api/profile/avatar - Upload profile avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Delete old avatar if exists
            if ($user->avatar_path && \Storage::disk('public')->exists($user->avatar_path)) {
                \Storage::disk('public')->delete($user->avatar_path);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');

            $user->update([
                'avatar_path' => $path,
                'avatar_url' => \Storage::disk('public')->url($path)
            ]);

            return response()->json([
                'message' => 'Avatar uploaded successfully',
                'avatar_url' => $user->avatar_url,
                'updated_at' => $user->updated_at->toISOString(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload avatar',
                'error' => 'Unable to save avatar image'
            ], 500);
        }
    }

    /**
     * DELETE /api/profile/avatar - Remove profile avatar
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Delete avatar file if exists
            if ($user->avatar_path && \Storage::disk('public')->exists($user->avatar_path)) {
                \Storage::disk('public')->delete($user->avatar_path);
            }

            $user->update([
                'avatar_path' => null,
                'avatar_url' => null
            ]);

            return response()->json([
                'message' => 'Avatar deleted successfully',
                'updated_at' => $user->updated_at->toISOString(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete avatar',
                'error' => 'Unable to remove avatar'
            ], 500);
        }
    }

    /**
     * POST /api/profile/logout-all - Logout from all devices
     */
    public function logoutAllDevices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Verify password for security
            $validator = Validator::make($request->all(), [
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'The given data was invalid',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Incorrect password',
                    'errors' => [
                        'password' => ['The password is incorrect']
                    ]
                ], 422);
            }

            // Delete all tokens except current
            $currentToken = $request->bearerToken();
            $user->tokens()->where('token', '!=', hash('sha256', $currentToken))->delete();

            return response()->json([
                'message' => 'Successfully logged out from all other devices',
                'devices_logged_out' => $user->tokens()->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to logout from all devices',
                'error' => 'Unable to revoke sessions'
            ], 500);
        }
    }
}
