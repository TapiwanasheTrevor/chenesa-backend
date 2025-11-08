# Chenesa Mobile App - Production Configuration Guide

## Production Environment Details

### Base URL
```
https://chenesa-shy-grass-3201.fly.dev
```

### API Endpoints
All API endpoints are prefixed with `/api`:
```
https://chenesa-shy-grass-3201.fly.dev/api
```

## Authentication

### Test User Credentials
```json
{
  "email": "demo@chenesa.io",
  "password": "password"
}
```

### Login Endpoint
```
POST https://chenesa-shy-grass-3201.fly.dev/api/auth/login
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
    "id": "user_uuid",
    "email": "demo@chenesa.io",
    "first_name": "Demo",
    "last_name": "User",
    "role": "user",
    "organization_id": "org_uuid"
  },
  "token": "your_auth_token_here"
}
```

## Live Sensor Data

### Available Sensors
The system currently has **4 Dingtek DF555 sensors** synced and operational:

1. **DF555-1-70011475-A7-98-DE-01-C4-C1**
2. **DF555-1-70011457-A7-98-DE-01-BC-42**
3. **DF555-1-70011469-A7-98-DE-01-C1-AF**
4. **DF555-1-70011454-A7-98-DE-01-BB-45**

### Data Sync Schedule
- Sensors sync automatically every **15 minutes**
- Manual sync available via: `php artisan dingtek:sync`
- Data is synced from Dingtek ThingsBoard cloud platform

## Key API Endpoints

### Tank Management
```
GET    /api/tanks                    - List all tanks
POST   /api/tanks                    - Create new tank
GET    /api/tanks/{id}               - Get tank details
PUT    /api/tanks/{id}               - Update tank
DELETE /api/tanks/{id}               - Delete tank
GET    /api/tanks/{id}/live-status   - Get real-time tank status
GET    /api/tanks/{id}/analytics     - Get tank analytics
GET    /api/tanks/{id}/history       - Get historical readings
```

### Sensor Management
```
GET    /api/sensors                  - List all sensors
POST   /api/sensors                  - Register new sensor
GET    /api/sensors/{id}             - Get sensor details
PUT    /api/sensors/{id}             - Update sensor
POST   /api/sensors/{id}/readings    - Submit sensor reading
```

### Dashboard
```
GET    /api/dashboard/overview       - Dashboard summary
GET    /api/dashboard/consumption    - Consumption analytics
GET    /api/dashboard/alerts         - Recent alerts
```

### Water Orders
```
GET    /api/orders                   - List water orders
POST   /api/orders                   - Create water order
GET    /api/orders/{id}              - Get order details
```

### Alerts
```
GET    /api/alerts                   - List alerts
POST   /api/alerts/{id}/acknowledge  - Acknowledge alert
```

### User Profile
```
GET    /api/user                     - Get current user
PUT    /api/user                     - Update user profile
POST   /api/user/fcm-token           - Update FCM token for notifications
```

## Sample API Requests

### 1. Login and Get Token
```bash
curl -X POST https://chenesa-shy-grass-3201.fly.dev/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@chenesa.io","password":"password"}'
```

### 2. Get All Tanks
```bash
curl https://chenesa-shy-grass-3201.fly.dev/api/tanks \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 3. Get Tank Details with Live Data
```bash
curl https://chenesa-shy-grass-3201.fly.dev/api/tanks/TANK_UUID \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Response Example:**
```json
{
  "data": {
    "id": "019955d9-f2e2-7170-814a-b197b9c340bc",
    "name": "DF555-1-70011475 Tank",
    "location": "Dingtek Sensor Location",
    "latitude": null,
    "longitude": null,
    "capacity_liters": 5000,
    "height_mm": 2000,
    "current_level": 75.5,
    "current_volume": 3775,
    "status": "normal",
    "sensor": {
      "id": "sensor_uuid",
      "device_id": "DF555-1-70011475-A7-98-DE-01-C4-C1",
      "model": "DF555",
      "status": "active",
      "battery_level": 85,
      "signal_strength": -65,
      "last_seen": "2025-01-15T10:30:00.000000Z"
    },
    "latest_reading": {
      "water_level_percentage": 75.5,
      "volume_liters": 3775,
      "temperature": 23.4,
      "created_at": "2025-01-15T10:30:00.000000Z"
    }
  }
}
```

### 4. Get Dashboard Overview
```bash
curl https://chenesa-shy-grass-3201.fly.dev/api/dashboard/overview \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Firebase Real-time Integration

### Firebase Database URL
```
https://chenesa-14844-default-rtdb.firebaseio.com/
```

### Firebase Structure
Real-time tank data is synced to Firebase at:
```
/organizations/{org_id}/tanks/{tank_id}
```

**Example Firebase Data:**
```json
{
  "organizations": {
    "org_uuid": {
      "tanks": {
        "tank_uuid": {
          "id": "tank_uuid",
          "name": "Main Storage Tank",
          "current_level": 75.5,
          "current_volume": 3775,
          "status": "normal",
          "last_reading": {
            "temperature": 23.4,
            "battery_level": 85,
            "timestamp": "2025-01-15T10:30:00Z"
          }
        }
      }
    }
  }
}
```

## Mobile App Configuration

### Flutter/Dart Configuration
```dart
class ApiConfig {
  static const String baseUrl = 'https://chenesa-shy-grass-3201.fly.dev';
  static const String apiUrl = '$baseUrl/api';

  // Firebase
  static const String firebaseDbUrl = 'https://chenesa-14844-default-rtdb.firebaseio.com/';

  // Endpoints
  static const String login = '$apiUrl/auth/login';
  static const String tanks = '$apiUrl/tanks';
  static const String sensors = '$apiUrl/sensors';
  static const String dashboard = '$apiUrl/dashboard/overview';
}
```

### React Native Configuration
```javascript
export const API_CONFIG = {
  BASE_URL: 'https://chenesa-shy-grass-3201.fly.dev',
  API_URL: 'https://chenesa-shy-grass-3201.fly.dev/api',
  FIREBASE_DB_URL: 'https://chenesa-14844-default-rtdb.firebaseio.com/',

  ENDPOINTS: {
    LOGIN: '/auth/login',
    TANKS: '/tanks',
    SENSORS: '/sensors',
    DASHBOARD: '/dashboard/overview'
  }
};
```

## Testing the Integration

### Step 1: Test Authentication
```bash
TOKEN=$(curl -s -X POST https://chenesa-shy-grass-3201.fly.dev/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@chenesa.io","password":"password"}' \
  | jq -r '.token')

echo "Token: $TOKEN"
```

### Step 2: Test Tank Listing
```bash
curl -s https://chenesa-shy-grass-3201.fly.dev/api/tanks \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.'
```

### Step 3: Test Dashboard
```bash
curl -s https://chenesa-shy-grass-3201.fly.dev/api/dashboard/overview \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.'
```

### Step 4: Test Mobile Integration Command
You can run the comprehensive integration test:
```bash
php artisan mobile:test-integration --user-email=demo@chenesa.io
```

## System Health

### Health Check Endpoint
```
GET https://chenesa-shy-grass-3201.fly.dev/api/health
```

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2025-01-15T10:00:00.000000Z",
  "version": "1.0.0",
  "environment": "production",
  "checks": {
    "database": {"status": "healthy"},
    "cache": {"status": "healthy"},
    "sensors": {"status": "healthy", "total_sensors": 4, "online_sensors": 4},
    "recent_data": {"status": "healthy"}
  }
}
```

## Data Models

### Tank Model
```json
{
  "id": "uuid",
  "organization_id": "uuid",
  "sensor_id": "uuid",
  "name": "string",
  "location": "string",
  "latitude": "decimal",
  "longitude": "decimal",
  "capacity_liters": "integer",
  "height_mm": "integer",
  "diameter_mm": "integer",
  "shape": "cylindrical|rectangular|custom",
  "material": "plastic|concrete|steel",
  "current_level": "decimal (0-100)",
  "current_volume": "integer (liters)",
  "status": "normal|low|critical|offline",
  "low_level_threshold": "integer",
  "critical_level_threshold": "integer",
  "refill_enabled": "boolean",
  "auto_refill_threshold": "integer"
}
```

### Sensor Model
```json
{
  "id": "uuid",
  "device_id": "string",
  "imei": "string",
  "sim_number": "string",
  "model": "DF555",
  "firmware_version": "string",
  "status": "active|inactive|offline",
  "last_seen": "datetime",
  "battery_level": "integer (0-100)",
  "signal_strength": "integer (dBm)"
}
```

### Sensor Reading Model
```json
{
  "id": "uuid",
  "sensor_id": "uuid",
  "tank_id": "uuid",
  "distance_mm": "integer",
  "water_level_percentage": "decimal",
  "volume_liters": "decimal",
  "temperature": "decimal",
  "battery_level": "integer",
  "signal_strength": "integer",
  "created_at": "datetime"
}
```

## Error Handling

### Common Error Responses

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

#### 404 Not Found
```json
{
  "message": "Resource not found"
}
```

#### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

#### 500 Server Error
```json
{
  "message": "Server Error",
  "error": "Internal server error details"
}
```

## Rate Limiting

- API requests are rate-limited to **60 requests per minute** per user
- Sensor data submission: **100 requests per minute** per sensor

## Support and Monitoring

### Logs
Production logs are available via Fly.io:
```bash
fly logs -a chenesa-shy-grass-3201
```

### Database Access
```bash
fly ssh console -a chenesa-shy-grass-3201
psql $DATABASE_URL
```

### Run Commands
```bash
fly ssh console -a chenesa-shy-grass-3201 -C "php artisan [command]"
```

## Production Notes

1. **SSL/HTTPS**: All API communication is encrypted via HTTPS
2. **Database**: PostgreSQL with automatic backups
3. **Caching**: Database-backed caching for improved performance
4. **Sessions**: Secure cookie-based sessions
5. **Queue**: Database queue for background jobs
6. **Monitoring**: Real-time health checks and system monitoring

## Mobile App Checklist

- [ ] Configure base URL in app config
- [ ] Implement JWT token storage and refresh
- [ ] Set up Firebase Realtime Database listeners
- [ ] Configure FCM for push notifications
- [ ] Test authentication flow
- [ ] Test tank data fetching
- [ ] Test sensor data display
- [ ] Test real-time updates
- [ ] Implement error handling
- [ ] Test offline functionality
- [ ] Test on physical devices

## Next Steps

1. **Complete Mobile App Integration**: Use the endpoints and credentials provided
2. **Test Real-time Updates**: Verify Firebase integration works correctly
3. **Configure Push Notifications**: Set up FCM tokens for alerts
4. **Production Testing**: Test all user flows end-to-end
5. **Performance Optimization**: Monitor and optimize API response times
6. **Security Review**: Audit authentication and data access patterns

## Contact & Support

For technical support or questions about the API:
- Review [DINGTEK_INTEGRATION.md](./DINGTEK_INTEGRATION.md) for sensor integration details
- Review [MOBILE_APP_INTEGRATION_GUIDE.md](./MOBILE_APP_INTEGRATION_GUIDE.md) for API documentation
- Check system health at: https://chenesa-shy-grass-3201.fly.dev/api/health

---

**Last Updated**: November 8, 2025
**Production Version**: 36
**Active Sensors**: 4 Dingtek DF555 sensors
