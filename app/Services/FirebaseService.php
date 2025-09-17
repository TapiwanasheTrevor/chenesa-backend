<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Database;
use Kreait\Firebase\Messaging;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private ?Messaging $messaging = null;
    private ?Database $database = null;
    private bool $isConfigured = false;

    public function __construct()
    {
        $this->initialize();
    }

    private function initialize(): void
    {
        try {
            // Try environment variable first (for production)
            $serviceAccountJson = env('FIREBASE_SERVICE_ACCOUNT');

            if ($serviceAccountJson) {
                $serviceAccount = json_decode($serviceAccountJson, true);
                if (!$serviceAccount) {
                    Log::error('Invalid Firebase service account JSON in environment variable');
                    return;
                }
                $factory = (new Factory)->withServiceAccount($serviceAccount);
            } else {
                // Fallback to file (for local development)
                $serviceAccountPath = storage_path('app/firebase-service-account.json');

                if (!file_exists($serviceAccountPath)) {
                    Log::warning('Firebase service account not found in environment or file. Push notifications disabled.');
                    return;
                }

                $factory = (new Factory)->withServiceAccount($serviceAccountPath);
            }

            if (config('services.firebase.database_url')) {
                $factory = $factory->withDatabaseUri(config('services.firebase.database_url'));
            }

            $this->messaging = $factory->createMessaging();
            $this->database = $factory->createDatabase();
            $this->isConfigured = true;

            Log::info('Firebase service initialized successfully.');
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            $this->isConfigured = false;
        }
    }

    public function isConfigured(): bool
    {
        return $this->isConfigured;
    }

    public function sendNotification(string $token, string $title, string $body, array $data = []): bool
    {
        if (!$this->isConfigured || !$this->messaging) {
            Log::warning('Firebase not configured. Cannot send notification.');
            return false;
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);

            if (!empty($data)) {
                $message = $message->withData($data);
            }

            $result = $this->messaging->send($message);

            Log::info('FCM notification sent successfully', [
                'token' => substr($token, 0, 20) . '...',
                'title' => $title
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send FCM notification: ' . $e->getMessage(), [
                'token' => substr($token, 0, 20) . '...',
                'title' => $title
            ]);
            return false;
        }
    }

    public function sendToMultipleTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        if (!$this->isConfigured || !$this->messaging) {
            Log::warning('Firebase not configured. Cannot send notifications.');
            return ['success' => 0, 'failed' => count($tokens)];
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification);

            if (!empty($data)) {
                $message = $message->withData($data);
            }

            $report = $this->messaging->sendMulticast($message, $tokens);

            Log::info('FCM multicast sent', [
                'success_count' => $report->successes()->count(),
                'failure_count' => $report->failures()->count(),
                'title' => $title
            ]);

            return [
                'success' => $report->successes()->count(),
                'failed' => $report->failures()->count(),
                'invalid_tokens' => $this->getInvalidTokens($report)
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send FCM multicast: ' . $e->getMessage());
            return ['success' => 0, 'failed' => count($tokens)];
        }
    }

    private function getInvalidTokens(MulticastSendReport $report): array
    {
        $invalidTokens = [];

        foreach ($report->failures()->getItems() as $failure) {
            $error = $failure->error();
            if ($error->code() === 'INVALID_ARGUMENT' || $error->code() === 'UNREGISTERED') {
                $invalidTokens[] = $failure->target()->value();
            }
        }

        return $invalidTokens;
    }

    public function updateRealtimeData(string $path, array $data): bool
    {
        if (!$this->isConfigured || !$this->database) {
            Log::warning('Firebase not configured. Cannot update realtime data.');
            return false;
        }

        try {
            $this->database->getReference($path)->set($data);

            Log::debug('Firebase realtime data updated', [
                'path' => $path,
                'data_keys' => array_keys($data)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update Firebase realtime data: ' . $e->getMessage(), [
                'path' => $path
            ]);
            return false;
        }
    }

    public function updateTankData(string $tankId, array $tankData): bool
    {
        return $this->updateRealtimeData("tanks/{$tankId}", $tankData);
    }

    public function updateSensorReading(string $tankId, array $reading): bool
    {
        $timestamp = now()->toISOString();
        return $this->updateRealtimeData("tanks/{$tankId}/latest_reading", [
            ...$reading,
            'timestamp' => $timestamp
        ]);
    }

    public function sendAlertNotification(string $token, string $alertType, string $tankName, float $level): bool
    {
        $title = match($alertType) {
            'low' => "Low Water Alert",
            'high' => "High Water Alert",
            'critical' => "Critical Water Alert",
            default => "Water Alert"
        };

        $body = "Tank '{$tankName}' water level is {$level}%";

        $data = [
            'type' => 'alert',
            'alert_type' => $alertType,
            'tank_name' => $tankName,
            'level' => (string)$level,
            'timestamp' => now()->toISOString()
        ];

        return $this->sendNotification($token, $title, $body, $data);
    }

    public function sendWaterOrderNotification(string $token, string $orderNumber, string $status): bool
    {
        $title = "Water Order Update";
        $body = "Order #{$orderNumber} is now {$status}";

        $data = [
            'type' => 'water_order',
            'order_number' => $orderNumber,
            'status' => $status,
            'timestamp' => now()->toISOString()
        ];

        return $this->sendNotification($token, $title, $body, $data);
    }
}