<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Notification;
use App\Models\Tank;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'in:all,read,unread',
            'type' => 'in:alert,order,system,maintenance',
            'limit' => 'integer|min:1|max:100',
            'page' => 'integer|min:1',
        ]);

        $user = $request->user();
        $status = $request->input('status', 'all');
        $type = $request->input('type');
        $limit = $request->input('limit', 20);

        $query = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by read status
        if ($status === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($status === 'unread') {
            $query->whereNull('read_at');
        }

        // Filter by type
        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->paginate($limit);

        return response()->json([
            'data' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'has_more' => $notifications->hasMorePages(),
            ],
            'summary' => [
                'total_unread' => Notification::where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count(),
                'total_today' => Notification::where('user_id', $user->id)
                    ->whereDate('created_at', Carbon::today())
                    ->count(),
            ]
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->update([
            'read_at' => Carbon::now()
        ]);

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    /**
     * Mark multiple notifications as read
     */
    public function markMultipleAsRead(Request $request)
    {
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'uuid|exists:notifications,id',
        ]);

        $user = $request->user();
        $notificationIds = $request->input('notification_ids');

        $updated = Notification::where('user_id', $user->id)
            ->whereIn('id', $notificationIds)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json([
            'message' => "{$updated} notifications marked as read",
            'updated_count' => $updated
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        $updated = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json([
            'message' => "All notifications marked as read",
            'updated_count' => $updated
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $notification = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Get notification preferences
     * Supports both nested format (backend) and flat format (mobile app)
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();

        // Get or create default preferences (nested format)
        $nestedPreferences = $user->notification_preferences ?? [
            'alerts' => [
                'low_water_level' => true,
                'sensor_offline' => true,
                'tank_maintenance' => true,
                'water_delivery' => true,
            ],
            'channels' => [
                'push' => true,
                'email' => false,
                'sms' => false,
            ],
            'schedule' => [
                'quiet_hours_enabled' => false,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '07:00',
                'weekend_notifications' => true,
            ],
            'thresholds' => [
                'low_level_percentage' => 20,
                'critical_level_percentage' => 10,
                'sensor_offline_minutes' => 30,
            ]
        ];

        // Check if mobile app format is requested (flat structure)
        $format = $request->input('format', 'nested');

        if ($format === 'flat') {
            // Convert to mobile app's expected flat format
            $flatPreferences = [
                'push_notifications' => $nestedPreferences['channels']['push'] ?? true,
                'email_notifications' => $nestedPreferences['channels']['email'] ?? false,
                'sms_notifications' => $nestedPreferences['channels']['sms'] ?? false,
                'tank_alerts' => $nestedPreferences['alerts']['low_water_level'] ?? true,
                'maintenance_reminders' => $nestedPreferences['alerts']['tank_maintenance'] ?? true,
                'system_updates' => true, // Default value
                'low_level_alerts' => $nestedPreferences['alerts']['low_water_level'] ?? true,
                'critical_level_alerts' => true, // Always enabled for safety
                'sensor_offline_alerts' => $nestedPreferences['alerts']['sensor_offline'] ?? true,
                'weekly_reports' => false, // Default value
                'monthly_reports' => false, // Default value
            ];

            return response()->json([
                'data' => $flatPreferences
            ]);
        }

        // Return nested format for backend/admin
        return response()->json([
            'data' => $nestedPreferences
        ]);
    }

    /**
     * Update notification preferences
     * Supports both nested format (backend) and flat format (mobile app)
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        // Detect format based on keys present
        $isFlatFormat = $request->has('push_notifications') ||
                       $request->has('email_notifications') ||
                       $request->has('tank_alerts');

        if ($isFlatFormat) {
            // Validate flat format (mobile app)
            $request->validate([
                'push_notifications' => 'boolean',
                'email_notifications' => 'boolean',
                'sms_notifications' => 'boolean',
                'tank_alerts' => 'boolean',
                'maintenance_reminders' => 'boolean',
                'system_updates' => 'boolean',
                'low_level_alerts' => 'boolean',
                'critical_level_alerts' => 'boolean',
                'sensor_offline_alerts' => 'boolean',
                'weekly_reports' => 'boolean',
                'monthly_reports' => 'boolean',
            ]);

            // Convert flat format to nested format for storage
            $currentPrefs = $user->notification_preferences ?? [];

            $nestedPreferences = [
                'alerts' => [
                    'low_water_level' => $request->input('low_level_alerts', $currentPrefs['alerts']['low_water_level'] ?? true),
                    'sensor_offline' => $request->input('sensor_offline_alerts', $currentPrefs['alerts']['sensor_offline'] ?? true),
                    'tank_maintenance' => $request->input('maintenance_reminders', $currentPrefs['alerts']['tank_maintenance'] ?? true),
                    'water_delivery' => $request->input('tank_alerts', $currentPrefs['alerts']['water_delivery'] ?? true),
                ],
                'channels' => [
                    'push' => $request->input('push_notifications', $currentPrefs['channels']['push'] ?? true),
                    'email' => $request->input('email_notifications', $currentPrefs['channels']['email'] ?? false),
                    'sms' => $request->input('sms_notifications', $currentPrefs['channels']['sms'] ?? false),
                ],
                'schedule' => $currentPrefs['schedule'] ?? [
                    'quiet_hours_enabled' => false,
                    'quiet_hours_start' => '22:00',
                    'quiet_hours_end' => '07:00',
                    'weekend_notifications' => true,
                ],
                'thresholds' => $currentPrefs['thresholds'] ?? [
                    'low_level_percentage' => 20,
                    'critical_level_percentage' => 10,
                    'sensor_offline_minutes' => 30,
                ],
                'reports' => [
                    'weekly' => $request->input('weekly_reports', false),
                    'monthly' => $request->input('monthly_reports', false),
                ],
            ];

            $user->update([
                'notification_preferences' => $nestedPreferences
            ]);

            return response()->json([
                'message' => 'Notification preferences updated successfully',
                'data' => $request->only([
                    'push_notifications', 'email_notifications', 'sms_notifications',
                    'tank_alerts', 'maintenance_reminders', 'system_updates',
                    'low_level_alerts', 'critical_level_alerts', 'sensor_offline_alerts',
                    'weekly_reports', 'monthly_reports'
                ])
            ]);
        }

        // Validate nested format (backend/admin)
        $request->validate([
            'alerts.low_water_level' => 'boolean',
            'alerts.sensor_offline' => 'boolean',
            'alerts.tank_maintenance' => 'boolean',
            'alerts.water_delivery' => 'boolean',
            'channels.push' => 'boolean',
            'channels.email' => 'boolean',
            'channels.sms' => 'boolean',
            'schedule.quiet_hours_enabled' => 'boolean',
            'schedule.quiet_hours_start' => 'string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'schedule.quiet_hours_end' => 'string|regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'schedule.weekend_notifications' => 'boolean',
            'thresholds.low_level_percentage' => 'integer|min:1|max:50',
            'thresholds.critical_level_percentage' => 'integer|min:1|max:30',
            'thresholds.sensor_offline_minutes' => 'integer|min:5|max:1440',
        ]);

        $preferences = $request->all();

        $user->update([
            'notification_preferences' => $preferences
        ]);

        return response()->json([
            'message' => 'Notification preferences updated successfully',
            'data' => $preferences
        ]);
    }

    /**
     * Test notification delivery
     */
    public function testNotification(Request $request)
    {
        $request->validate([
            'type' => 'required|in:push,email,sms',
            'message' => 'string|max:255',
        ]);

        $user = $request->user();
        $type = $request->input('type');
        $message = $request->input('message', 'This is a test notification from Chenesa.');

        try {
            // Create test notification
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => 'system',
                'title' => 'Test Notification',
                'message' => $message,
                'data' => [
                    'test' => true,
                    'timestamp' => Carbon::now()->toISOString(),
                ],
            ]);

            // Send based on type
            switch ($type) {
                case 'push':
                    $this->sendPushNotification($user, $notification);
                    break;
                case 'email':
                    $this->sendEmailNotification($user, $notification);
                    break;
                case 'sms':
                    $this->sendSMSNotification($user, $notification);
                    break;
            }

            return response()->json([
                'message' => "Test {$type} notification sent successfully",
                'notification_id' => $notification->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => "Failed to send test notification: {$e->getMessage()}"
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(Request $request)
    {
        $user = $request->user();
        $days = $request->input('days', 30);

        $startDate = Carbon::now()->subDays($days);

        $stats = [
            'total_notifications' => Notification::where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'by_type' => Notification::where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type'),
            'read_percentage' => $this->calculateReadPercentage($user->id, $startDate),
            'daily_counts' => $this->getDailyCounts($user->id, $days),
            'most_common_alerts' => $this->getMostCommonAlerts($user->id, $startDate),
        ];

        return response()->json([
            'data' => $stats,
            'period' => "{$days} days"
        ]);
    }

    /**
     * Send push notification (placeholder implementation)
     */
    private function sendPushNotification(User $user, Notification $notification)
    {
        // Implementation would use FCM or similar service
        // This is a placeholder
        \Log::info("Push notification sent to user {$user->id}: {$notification->message}");

        return true;
    }

    /**
     * Send email notification (placeholder implementation)
     */
    private function sendEmailNotification(User $user, Notification $notification)
    {
        // Implementation would use Laravel Mail
        // This is a placeholder
        \Log::info("Email notification sent to {$user->email}: {$notification->message}");

        return true;
    }

    /**
     * Send SMS notification (placeholder implementation)
     */
    private function sendSMSNotification(User $user, Notification $notification)
    {
        // Implementation would use Twilio or similar service
        // This is a placeholder
        \Log::info("SMS notification sent to user {$user->id}: {$notification->message}");

        return true;
    }

    /**
     * Calculate read percentage
     */
    private function calculateReadPercentage($userId, $startDate)
    {
        $total = Notification::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->count();

        if ($total === 0) {
            return 0;
        }

        $read = Notification::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('read_at')
            ->count();

        return round(($read / $total) * 100, 1);
    }

    /**
     * Get daily notification counts
     */
    private function getDailyCounts($userId, $days)
    {
        $counts = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $count = Notification::where('user_id', $userId)
                ->whereDate('created_at', $date)
                ->count();

            $counts[] = [
                'date' => $date,
                'count' => $count
            ];
        }

        return $counts;
    }

    /**
     * Get most common alert types
     */
    private function getMostCommonAlerts($userId, $startDate)
    {
        return Notification::where('user_id', $userId)
            ->where('type', 'alert')
            ->where('created_at', '>=', $startDate)
            ->select('title', DB::raw('count(*) as count'))
            ->groupBy('title')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();
    }
}