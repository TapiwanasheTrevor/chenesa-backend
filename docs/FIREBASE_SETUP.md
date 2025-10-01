# Firebase Integration Setup Guide

This guide explains how to set up Firebase integration for the Chenesa water monitoring system.

## Overview

The Laravel backend integrates with Firebase to provide:
- **Push Notifications**: Real-time alerts sent to mobile devices
- **Realtime Database**: Live tank data synchronization for mobile apps
- **FCM Token Management**: Device registration and notification targeting

## Features

### ✅ Push Notifications
- Low/High/Critical water level alerts
- Water order status updates
- Automatic notification to all organization users
- Invalid token cleanup and management

### ✅ Real-time Data Sync
- Tank levels sync to Firebase Realtime Database
- Sensor readings pushed in real-time
- Live dashboard updates for mobile apps
- Organization-specific data isolation

### ✅ FCM Token Management
- API endpoint for mobile apps to register FCM tokens
- Automatic targeting of active users with valid tokens
- Token validation and cleanup

## Setup Instructions

### 1. Firebase Project Setup

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select project: **chenesa-14844**
3. Enable these services:
   - **Authentication** (Email/Password)
   - **Realtime Database**
   - **Cloud Messaging (FCM)**

### 2. Service Account Configuration

1. In Firebase Console → Project Settings → Service Accounts
2. Click "Generate new private key"
3. Save the downloaded JSON file as:
   ```
   storage/app/firebase-service-account.json
   ```

### 3. Environment Variables

Add to your `.env` file:
```env
FIREBASE_DATABASE_URL=https://chenesa-14844-default-rtdb.firebaseio.com/
FIREBASE_PROJECT_ID=chenesa-14844
```

### 4. Realtime Database Rules

Set these rules in Firebase Console → Realtime Database → Rules:
```json
{
  "rules": {
    "tanks": {
      "$tankId": {
        ".read": "auth != null",
        ".write": "auth != null"
      }
    }
  }
}
```

### 5. FCM Configuration

1. In Firebase Console → Project Settings → Cloud Messaging
2. Add your server key to mobile app configuration
3. Configure Android app with package name: `com.chenesa.mobile_app`

## API Endpoints

### Update FCM Token
```http
POST /api/profile/fcm-token
Authorization: Bearer {token}
Content-Type: application/json

{
  "fcm_token": "device_fcm_token_here"
}
```

## Data Structure

### Tank Data in Firebase
```json
{
  "tanks": {
    "tank_uuid": {
      "id": "tank_uuid",
      "name": "Tank Name",
      "location": "Tank Location",
      "capacity_liters": 5000,
      "organization_id": "org_uuid",
      "organization_name": "Organization Name",
      "latest_reading": {
        "id": "reading_uuid",
        "level": 75.5,
        "distance": 1.2,
        "temperature": 23.4,
        "battery_level": 85,
        "timestamp": "2025-01-15T10:30:00Z"
      },
      "sensor": {
        "id": "sensor_uuid",
        "device_id": "DT001",
        "status": "active"
      },
      "last_updated": "2025-01-15T10:30:00Z"
    }
  }
}
```

### Push Notification Payload
```json
{
  "notification": {
    "title": "Low Water Alert",
    "body": "Tank 'Main Storage' water level is 15%"
  },
  "data": {
    "type": "alert",
    "alert_id": "alert_uuid",
    "tank_id": "tank_uuid",
    "severity": "low",
    "timestamp": "2025-01-15T10:30:00Z"
  }
}
```

## Testing Firebase Integration

### Test Firebase Configuration
```bash
php artisan tinker
>>> $firebase = app(\App\Services\FirebaseService::class);
>>> $firebase->isConfigured()
```

### Test Push Notification
```bash
php artisan tinker
>>> $firebase = app(\App\Services\FirebaseService::class);
>>> $firebase->sendNotification('FCM_TOKEN_HERE', 'Test Title', 'Test Body');
```

### Test Realtime Data Update
```bash
php artisan tinker
>>> $firebase = app(\App\Services\FirebaseService::class);
>>> $firebase->updateTankData('tank_id', ['test' => 'data']);
```

## Troubleshooting

### Firebase Not Configured
- Check service account file exists: `storage/app/firebase-service-account.json`
- Verify environment variables are set
- Check Laravel logs for Firebase errors

### Push Notifications Not Working
- Verify FCM tokens are registered via API
- Check Firebase Console → Cloud Messaging for quota limits
- Review Laravel logs for notification errors
- Ensure mobile app has correct configuration

### Realtime Data Not Syncing
- Check Firebase Realtime Database rules
- Verify database URL in environment variables
- Check network connectivity to Firebase

## Security Notes

- Service account file contains sensitive credentials
- Never commit `firebase-service-account.json` to version control
- Use environment variables for configuration
- Implement proper Firebase security rules
- Validate FCM tokens before sending notifications

## Mobile App Integration

The mobile app should:
1. Initialize Firebase with the client configuration
2. Register for FCM tokens on app start
3. Send FCM token to `/api/profile/fcm-token` endpoint
4. Listen for realtime database changes at `/tanks/{tankId}`
5. Handle push notification data payloads

## Support

For Firebase-related issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Review Firebase Console for error messages
3. Test individual components using artisan tinker
4. Verify mobile app Firebase configuration matches server setup