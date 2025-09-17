<?php

namespace App\Models;

use App\Services\FirebaseService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Alert extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'tank_id',
        'type',
        'severity',
        'title',
        'message',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($alert) {
            $alert->sendPushNotifications();
        });
    }

    public function sendPushNotifications(): void
    {
        try {
            $firebaseService = app(FirebaseService::class);

            if (!$firebaseService->isConfigured()) {
                Log::warning('Firebase not configured, skipping push notifications for alert: ' . $this->id);
                return;
            }

            // Get organization users with FCM tokens
            $users = $this->organization->users()
                ->whereNotNull('fcm_token')
                ->where('is_active', true)
                ->get();

            if ($users->isEmpty()) {
                Log::info('No users with FCM tokens found for alert: ' . $this->id);
                return;
            }

            $title = $this->title;
            $body = $this->message;
            $data = [
                'type' => 'alert',
                'alert_id' => $this->id,
                'tank_id' => $this->tank_id,
                'severity' => $this->severity,
                'timestamp' => $this->created_at->toISOString()
            ];

            $tokens = $users->pluck('fcm_token')->filter()->toArray();

            if (!empty($tokens)) {
                $result = $firebaseService->sendToMultipleTokens($tokens, $title, $body, $data);

                Log::info('Alert push notifications sent', [
                    'alert_id' => $this->id,
                    'success_count' => $result['success'],
                    'failed_count' => $result['failed']
                ]);

                // Remove invalid tokens
                if (!empty($result['invalid_tokens'])) {
                    User::whereIn('fcm_token', $result['invalid_tokens'])
                        ->update(['fcm_token' => null]);

                    Log::info('Removed invalid FCM tokens', [
                        'count' => count($result['invalid_tokens'])
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send push notifications for alert: ' . $e->getMessage(), [
                'alert_id' => $this->id
            ]);
        }
    }
}