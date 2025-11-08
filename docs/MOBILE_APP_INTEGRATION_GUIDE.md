# Mobile App Integration Guide

## Overview

This guide explains how the Chenesa mobile app integrates with the backend API and Dingtek sensor system for real-time water tank monitoring.

## System Architecture

```
[Dingtek Sensors (DF555)]
        ↓
[Dingtek ThingsBoard Cloud]
        ↓
[Laravel Backend - Sync Service (Every 15 min)]
        ↓
[PostgreSQL Database + Firebase Realtime DB]
        ↓
[Mobile App (Flutter)] via REST API
```

## Integration Status

✅ **Fully Integrated Components:**

1. **Dingtek Sensor Integration**
   - 4 DF555 sensors synced from Dingtek cloud
   - Auto-sync every 15 minutes
   - Real-time telemetry data (water level, temperature, battery, signal)

2. **Backend API**
   - All mobile app endpoints implemented
   - Authentication with Laravel Sanctum
   - Real-time data via Firebase

3. **Database**
   - Sensor readings stored in PostgreSQL
   - Tanks auto-created for sensors
   - Historical data preserved

4. **Firebase Integration**
   - Configured and operational
   - Real-time database for mobile app
   - Push notifications ready

## Testing the Integration

### Quick Integration Test

Run the automated integration test:

```bash
php artisan mobile:test-integration
```

This will verify:
- Dingtek sensors are synced
- Tanks have sensor data
- API data structure is correct
- Firebase is configured
- User authentication works

### Manual Sync Test

Force a manual sync from Dingtek:

```bash
# Test connection only
php artisan dingtek:sync --test

# Full sync
php artisan dingtek:sync
```

## Mobile App API Endpoints

### Base URL
- **Production**: `https://chenesa-shy-grass-3201.fly.dev/api`
- **Local**: `http://localhost/api`

### Authentication

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "demo@chenesa.io",
  "password": "password"
}
```

**Response:**
```json
{
  "user": {
    "id": "uuid",
    "email": "demo@chenesa.io",
    "first_name": "Demo",
    "last_name": "User",
    "role": "admin"
  },
  "token": "1|access_token_here"
}
```

### Tank Management

#### Get All Tanks
```http
GET /api/tanks
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Tank for Sensor 79fbf0d0-d3f4-11ef",
      "location": "Unknown Location",
      "capacity_liters": 10000,
      "current_status": {
        "water_level_percentage": 100,
        "volume_liters": 3534.29,
        "last_updated": "2025-11-08T08:19:27Z",
        "sensor_online": true,
        "alert_status": "normal"
      },
      "sensor": {
        "id": "uuid",
        "device_id": "79fbf0d0-d3f4-11ef-b0cb-bdbed792c3b4",
        "model": "DF555",
        "battery_level": 53,
        "signal_strength": 74
      }
    }
  ]
}
```

#### Get Tank Details
```http
GET /api/tanks/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "id": "uuid",
    "name": "Tank for Sensor 79fbf0d0",
    "location": "Unknown Location",
    "capacity_liters": 10000,
    "height_mm": 2000,
    "diameter_mm": 1500,
    "shape": "cylindrical",
    "material": "plastic",
    "current_reading": {
      "water_level_percentage": 100.00,
      "volume_liters": 3534.29,
      "temperature": 17.00,
      "battery_level": 53,
      "timestamp": "2025-11-08T08:19:27.000000Z"
    },
    "sensor": {
      "id": "uuid",
      "device_id": "79fbf0d0-d3f4-11ef-b0cb-bdbed792c3b4",
      "model": "DF555",
      "status": "active",
      "battery_level": 53,
      "signal_strength": 74,
      "last_seen": "2025-11-08T08:19:27.000000Z"
    },
    "recent_readings": [
      {
        "timestamp": "2025-11-08T08:19:27Z",
        "water_level_percentage": 100.00,
        "volume_liters": 3534.29,
        "temperature": 17.00
      }
    ]
  }
}
```

#### Get Live Tank Status
```http
GET /api/tanks/{id}/live-status
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "tank_id": "uuid",
    "tank_name": "Tank for Sensor 79fbf0d0",
    "last_updated": "2025-11-08T08:19:27Z",
    "is_online": true,
    "water_level": {
      "percentage": 100.00,
      "liters": 3534.29,
      "status": "full"
    },
    "temperature": 17.00,
    "battery_level": 53,
    "active_alerts": 0,
    "refill_recommendation": {
      "recommended": false,
      "reason": "sufficient_water",
      "priority": "normal"
    }
  }
}
```

### Sensor Management

#### Get All Sensors
```http
GET /api/sensors
Authorization: Bearer {token}
```

#### Get Sensor Details
```http
GET /api/sensors/{id}
Authorization: Bearer {token}
```

### Dashboard

#### Get Overview
```http
GET /api/dashboard/overview
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "summary": {
      "total_tanks": 4,
      "active_tanks": 4,
      "total_capacity": 40000,
      "current_volume": 32547.16,
      "fill_percentage": 81.37,
      "active_alerts": 0
    },
    "tank_levels": {
      "tank_uuid_1": {
        "percentage": 100.00,
        "volume": 3534.29
      }
    }
  }
}
```

## Current Test Data

### Test User
- **Email**: `demo@chenesa.io`
- **Password**: `password`
- **Role**: admin
- **Organization**: Chenesa Demo Company

### Synced Sensors
Currently 4 Dingtek DF555 sensors synced:
1. **869080071372702** - Battery: 53%, Signal: 74%
2. **861556079063995** - Battery: 50%, Signal: 94%
3. **861556079065164** - Battery: 50%, Signal: 70%
4. **861556079064076** - Battery: 51%, Signal: 74%

### Sample Tank Data
- **Total Tanks**: 4
- **Average Fill Level**: ~87%
- **Temperature Range**: 17°C - 23°C
- **Last Sync**: Every 15 minutes automatically

## Firebase Real-time Integration

### Firebase Configuration
- **Database URL**: `https://chenesa-14844-default-rtdb.firebaseio.com/`
- **Project ID**: `chenesa-14844`

### Data Structure
```json
{
  "organizations": {
    "{org_id}": {
      "tanks": {
        "{tank_id}": {
          "id": "uuid",
          "name": "Tank Name",
          "current_level": 100.00,
          "current_volume": 3534.29,
          "status": "normal",
          "last_reading": {
            "distance_mm": 0,
            "temperature": 17.00,
            "battery_level": 53,
            "timestamp": "2025-11-08T08:19:27Z"
          },
          "sensor": {
            "status": "online",
            "signal_strength": 74,
            "last_seen": "2025-11-08T08:19:27Z"
          }
        }
      }
    }
  }
}
```

## Development Workflow

### 1. Start Local Server
```bash
php artisan serve
```

### 2. Test API Endpoints
```bash
# Using curl
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@chenesa.io","password":"password"}'

# Get tanks (use token from login)
curl -X GET http://localhost:8000/api/tanks \
  -H "Authorization: Bearer {token}"
```

### 3. Monitor Dingtek Sync
```bash
# Watch sync logs
tail -f storage/logs/dingtek-sync.log

# Check Laravel logs
tail -f storage/logs/laravel.log | grep Dingtek
```

### 4. Force Manual Sync
```bash
php artisan dingtek:sync
```

## Mobile App Configuration

### API Configuration (Flutter)
```dart
class ApiConfig {
  static const String baseUrl = 'https://chenesa-shy-grass-3201.fly.dev/api';
  static const String firebaseUrl = 'https://chenesa-14844-default-rtdb.firebaseio.com/';

  // For local development
  // static const String baseUrl = 'http://10.0.2.2:8000/api'; // Android emulator
  // static const String baseUrl = 'http://localhost:8000/api'; // iOS simulator
}
```

### Authentication Flow
```dart
1. User enters credentials
2. POST /api/auth/login
3. Store token in secure storage
4. Include token in all subsequent requests:
   headers: {'Authorization': 'Bearer $token'}
5. Handle 401 responses (token expired)
6. Refresh or re-login as needed
```

### Real-time Updates
```dart
1. Subscribe to Firebase path: `/organizations/{orgId}/tanks`
2. Listen for changes
3. Update UI when tank data changes
4. Also poll /api/tanks/{id}/live-status for fallback
```

## Troubleshooting

### No Sensor Data
```bash
# Check if sensors are synced
php artisan mobile:test-integration

# Force sync
php artisan dingtek:sync

# Check logs
tail -f storage/logs/laravel.log
```

### API Returns Empty Tanks
```bash
# Verify database has data
PGPASSWORD=123Bubblegums psql -h 127.0.0.1 -U postgres -d chenesa -c "SELECT COUNT(*) FROM tanks;"

# Check user's organization
php artisan mobile:test-integration --user-email=demo@chenesa.io
```

### Firebase Not Updating
```bash
# Check Firebase configuration
php artisan tinker
>>> app(\App\Services\FirebaseService::class)->isConfigured()
=> true
```

### Sync Not Running
```bash
# Check scheduler is running
php artisan schedule:run

# For production, ensure cron is set up:
* * * * * cd /path/to/chenesa && php artisan schedule:run >> /dev/null 2>&1
```

## Production Deployment

### Environment Variables
Ensure these are set in production `.env`:

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=chenesa
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Firebase
FIREBASE_DATABASE_URL=https://chenesa-14844-default-rtdb.firebaseio.com/
FIREBASE_PROJECT_ID=chenesa-14844

# Dingtek
DINGTEK_BASE_URL=https://cloud.dingtek.com
DINGTEK_USERNAME=tadiwaweb@gmail.com
DINGTEK_PASSWORD=adiwa00
```

### Fly.io Deployment
```bash
# Deploy
fly deploy

# Check status
fly status

# View logs
fly logs

# Run artisan commands
fly ssh console -C "php artisan dingtek:sync"
```

### Monitoring
```bash
# Check sync status
fly ssh console -C "php artisan mobile:test-integration"

# Monitor logs
fly logs --follow
```

## Performance Considerations

### Data Sync Frequency
- **Current**: Every 15 minutes
- **Recommendation**: Keep at 15 minutes to balance real-time needs and API limits

### API Response Times
- **Tanks list**: <500ms
- **Tank details**: <300ms
- **Dashboard overview**: <1s

### Caching Strategy
- Auth tokens: Cached for 23 hours
- Sensor readings: No cache (always fresh)
- Dashboard data: Can be cached for 5-10 minutes

## Security

### API Security
- ✅ JWT authentication (Laravel Sanctum)
- ✅ HTTPS only in production
- ✅ Rate limiting enabled
- ✅ Input validation on all endpoints

### Sensor Data Security
- ✅ Sensor authentication middleware
- ✅ Organization-based access control
- ✅ Encrypted database connections

## Support

### For Issues
1. Run integration test: `php artisan mobile:test-integration`
2. Check logs: `storage/logs/laravel.log`
3. Verify Dingtek sync: `php artisan dingtek:sync --test`

### Useful Commands
```bash
# Test mobile app integration
php artisan mobile:test-integration

# Test Dingtek connection
php artisan dingtek:sync --test

# Force sync
php artisan dingtek:sync

# Clear caches
php artisan cache:clear
php artisan config:clear
```

## Next Steps

1. **Customize Tank Names**: Update auto-created tank names in Filament admin
2. **Set Tank Locations**: Add GPS coordinates for tanks
3. **Configure Alerts**: Set custom thresholds for each tank
4. **Test Water Orders**: Implement water ordering workflow
5. **Add More Users**: Invite team members to the organization

---

**Last Updated**: November 8, 2025
**Status**: ✅ Fully Operational
**Sensors**: 4 Dingtek DF555 devices
**Sync**: Every 15 minutes
**Mobile App**: Ready for testing
