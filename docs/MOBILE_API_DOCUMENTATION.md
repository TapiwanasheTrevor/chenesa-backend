# Mobile API Documentation

Complete API structure for Chenesa mobile app development.

**Base URL:** `https://chenesa-shy-grass-3201.fly.dev/api`

## Authentication Endpoints

### 1. Register User & Organization
**Endpoint:** `POST /auth/register`

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "organization_name": "Acme Water Corp",
  "country": "zimbabwe", // or "south_africa"
  "phone": "+263771234567" // optional
}
```

**Response (201):**
```json
{
  "user": {
    "id": "019955d9-f2e2-7170-814a-b197b9c340be",
    "organization_id": "019955d9-f2e2-7170-814a-b197b9c340bf",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+263771234567",
    "role": "admin",
    "is_active": true,
    "last_login": "2025-09-17T11:30:00.000000Z",
    "created_at": "2025-09-17T11:30:00.000000Z",
    "updated_at": "2025-09-17T11:30:00.000000Z"
  },
  "token": "1|abcdef123456789...",
  "refresh_token": "2|xyz789456123..."
}
```

### 2. Login
**Endpoint:** `POST /auth/login`

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "user": {
    "id": "019955d9-f2e2-7170-814a-b197b9c340be",
    "organization_id": "019955d9-f2e2-7170-814a-b197b9c340bf",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "full_name": "John Doe",
    "phone": "+263771234567",
    "role": "admin",
    "is_active": true,
    "last_login": "2025-09-17T11:30:00.000000Z",
    "created_at": "2025-09-17T11:30:00.000000Z",
    "updated_at": "2025-09-17T11:30:00.000000Z"
  },
  "token": "1|abcdef123456789...",
  "refresh_token": "2|xyz789456123..."
}
```

### 3. Logout
**Endpoint:** `POST /auth/logout`
**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "message": "Successfully logged out"
}
```

### 4. Refresh Token
**Endpoint:** `POST /auth/refresh`

**Request Body:**
```json
{
  "refresh_token": "2|xyz789456123..."
}
```

**Response (200):**
```json
{
  "token": "3|newtoken123456789...",
  "refresh_token": "4|newrefresh789456123..."
}
```

## Profile Endpoints

### 1. Get Profile
**Endpoint:** `GET /profile`
**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "data": {
    "id": "019955d9-f2e2-7170-814a-b197b9c340be",
    "organization_id": "019955d9-f2e2-7170-814a-b197b9c340bf",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "full_name": "John Doe",
    "phone": "+263771234567",
    "role": "admin",
    "is_active": true,
    "last_login": "2025-09-17T11:30:00.000000Z",
    "organization": {
      "id": "019955d9-f2e2-7170-814a-b197b9c340bf",
      "name": "Acme Water Corp",
      "type": "business",
      "address": "123 Main St",
      "city": "Harare",
      "country": "zimbabwe",
      "contact_email": "john@example.com",
      "contact_phone": "+263771234567",
      "subscription_status": "active"
    },
    "created_at": "2025-09-17T11:30:00.000000Z",
    "updated_at": "2025-09-17T11:30:00.000000Z"
  }
}
```

### 2. Update Profile
**Endpoint:** `PATCH /profile`
**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Smith",
  "phone": "+263771234567"
}
```

**Response (200):**
```json
{
  "message": "Profile updated successfully",
  "updated_at": "2025-09-17T11:30:00.000000Z"
}
```

### 3. Update FCM Token
**Endpoint:** `POST /profile/fcm-token`
**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "fcm_token": "fGxLN123456789abcdef..."
}
```

**Response (200):**
```json
{
  "message": "FCM token updated successfully",
  "updated_at": "2025-09-17T11:30:00.000000Z"
}
```

### 4. Change Password
**Endpoint:** `POST /profile/change-password`
**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "current_password": "oldpassword123",
  "new_password": "newpassword456",
  "new_password_confirmation": "newpassword456"
}
```

**Response (200):**
```json
{
  "message": "Password changed successfully"
}
```

## Tank Endpoints

### 1. Get All Tanks
**Endpoint:** `GET /tanks`
**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status` (optional): `critical`, `low`, `normal`
- `location` (optional): Filter by location
- `per_page` (optional): Items per page (max 50, default 10)
- `page` (optional): Page number

**Response (200):**
```json
{
  "data": [
    {
      "id": "019955d9-f2e2-7170-814a-b197b9c340bc",
      "name": "Main Storage Tank",
      "location": "Building A - Rooftop",
      "capacity_liters": 5000,
      "current_level": 75.5,
      "current_volume": 3775,
      "status": "active",
      "last_reading_at": "2025-09-17T11:25:00.000000Z",
      "sensor": {
        "id": "019955d9-f2e2-7170-814a-b197b9c340bd",
        "device_id": "DT001",
        "status": "online",
        "battery_level": 85,
        "signal_strength": 75,
        "last_seen": "2025-09-17T11:25:00.000000Z"
      },
      "settings": {
        "low_level_threshold": 25,
        "critical_level_threshold": 10,
        "refill_enabled": true,
        "auto_refill_threshold": 20
      },
      "created_at": "2025-09-17T10:00:00.000000Z",
      "updated_at": "2025-09-17T11:25:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 5,
    "last_page": 1,
    "from": 1,
    "to": 5
  }
}
```

### 2. Get Tank Details
**Endpoint:** `GET /tanks/{id}`
**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "data": {
    "id": "019955d9-f2e2-7170-814a-b197b9c340bc",
    "name": "Main Storage Tank",
    "location": "Building A - Rooftop",
    "capacity_liters": 5000,
    "current_level": 75.5,
    "current_volume": 3775,
    "status": "active",
    "last_reading_at": "2025-09-17T11:25:00.000000Z",
    "sensor": {
      "id": "019955d9-f2e2-7170-814a-b197b9c340bd",
      "device_id": "DT001",
      "status": "online",
      "battery_level": 85,
      "signal_strength": 75,
      "last_seen": "2025-09-17T11:25:00.000000Z"
    },
    "settings": {
      "low_level_threshold": 25,
      "critical_level_threshold": 10,
      "refill_enabled": true,
      "auto_refill_threshold": 20
    },
    "recent_readings": [
      {
        "id": "019955d9-f2e2-7170-814a-b197b9c340be",
        "water_level_percentage": 75.5,
        "volume_liters": 3775,
        "distance": 1.2,
        "temperature": 23.4,
        "created_at": "2025-09-17T11:25:00.000000Z"
      }
    ],
    "statistics": {
      "avg_level_24h": 73.2,
      "min_level_24h": 68.1,
      "max_level_24h": 78.9,
      "consumption_24h": 450,
      "readings_count_24h": 144
    },
    "created_at": "2025-09-17T10:00:00.000000Z",
    "updated_at": "2025-09-17T11:25:00.000000Z"
  }
}
```

### 3. Get Tank History
**Endpoint:** `GET /tanks/{id}/history`
**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `period` (optional): `hour`, `day`, `week`, `month` (default: `day`)
- `limit` (optional): Number of records (max 1000, default 100)

**Response (200):**
```json
{
  "data": [
    {
      "timestamp": "2025-09-17T11:25:00.000000Z",
      "water_level_percentage": 75.5,
      "volume_liters": 3775,
      "temperature": 23.4
    }
  ],
  "meta": {
    "period": "day",
    "count": 144,
    "tank_id": "019955d9-f2e2-7170-814a-b197b9c340bc"
  }
}
```

### 4. Get Live Tank Status
**Endpoint:** `GET /tanks/{id}/live-status`
**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "data": {
    "tank_id": "019955d9-f2e2-7170-814a-b197b9c340bc",
    "current_level": 75.5,
    "current_volume": 3775,
    "status": "normal",
    "sensor_status": "online",
    "last_reading_at": "2025-09-17T11:25:00.000000Z",
    "trend": "stable", // "rising", "falling", "stable"
    "estimated_time_to_empty": "48 hours",
    "alerts": []
  }
}
```

## Water Order Endpoints

### 1. Create Water Order
**Endpoint:** `POST /orders`
**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "tank_id": "019955d9-f2e2-7170-814a-b197b9c340bc",
  "volume_liters": 2000,
  "delivery_date": "2025-09-18",
  "delivery_time_slot": "morning", // "morning", "afternoon", "evening"
  "delivery_address": "123 Main St, Harare",
  "notes": "Please call before delivery"
}
```

**Response (201):**
```json
{
  "data": {
    "id": "019955d9-f2e2-7170-814a-b197b9c340bf",
    "order_number": "WO-20250917-001",
    "tank": {
      "id": "019955d9-f2e2-7170-814a-b197b9c340bc",
      "name": "Main Storage Tank"
    },
    "volume_liters": 2000,
    "price": 150.00,
    "status": "pending",
    "delivery_date": "2025-09-18",
    "delivery_time_slot": "morning",
    "delivery_address": "123 Main St, Harare",
    "notes": "Please call before delivery",
    "created_at": "2025-09-17T11:30:00.000000Z"
  }
}
```

### 2. Get Orders
**Endpoint:** `GET /orders`
**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `status` (optional): `pending`, `confirmed`, `in_transit`, `delivered`, `cancelled`
- `per_page` (optional): Items per page (max 50, default 10)

**Response (200):**
```json
{
  "data": [
    {
      "id": "019955d9-f2e2-7170-814a-b197b9c340bf",
      "order_number": "WO-20250917-001",
      "tank": {
        "id": "019955d9-f2e2-7170-814a-b197b9c340bc",
        "name": "Main Storage Tank"
      },
      "volume_liters": 2000,
      "price": 150.00,
      "status": "confirmed",
      "delivery_date": "2025-09-18",
      "delivery_time_slot": "morning",
      "estimated_delivery_time": "08:00 - 12:00",
      "created_at": "2025-09-17T11:30:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 3,
    "last_page": 1,
    "from": 1,
    "to": 3
  }
}
```

## Alert Endpoints

### 1. Get Alerts
**Endpoint:** `GET /alerts`
**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
- `severity` (optional): `low`, `medium`, `high`, `critical`
- `status` (optional): `active`, `resolved`
- `per_page` (optional): Items per page (max 50, default 10)

**Response (200):**
```json
{
  "data": [
    {
      "id": "019955d9-f2e2-7170-814a-b197b9c340bg",
      "tank": {
        "id": "019955d9-f2e2-7170-814a-b197b9c340bc",
        "name": "Main Storage Tank",
        "location": "Building A - Rooftop"
      },
      "type": "low_level",
      "severity": "medium",
      "title": "Low Water Level Alert",
      "message": "Tank 'Main Storage Tank' water level is below 25%",
      "is_resolved": false,
      "resolved_at": null,
      "created_at": "2025-09-17T11:20:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 2,
    "last_page": 1,
    "from": 1,
    "to": 2
  }
}
```

### 2. Resolve Alert
**Endpoint:** `PATCH /alerts/{id}/resolve`
**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "message": "Alert resolved successfully",
  "resolved_at": "2025-09-17T11:35:00.000000Z"
}
```

## Dashboard Endpoints

### 1. Dashboard Overview
**Endpoint:** `GET /dashboard/overview`
**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "data": {
    "summary": {
      "total_tanks": 5,
      "active_tanks": 4,
      "critical_alerts": 0,
      "pending_orders": 2
    },
    "tank_status": {
      "normal": 3,
      "low": 1,
      "critical": 0,
      "offline": 1
    },
    "consumption": {
      "today": 1250,
      "yesterday": 1180,
      "this_week": 8750,
      "last_week": 8200
    },
    "recent_alerts": [
      {
        "id": "019955d9-f2e2-7170-814a-b197b9c340bg",
        "title": "Low Water Level Alert",
        "tank_name": "Main Storage Tank",
        "severity": "medium",
        "created_at": "2025-09-17T11:20:00.000000Z"
      }
    ]
  }
}
```

## Error Response Format

All endpoints return errors in the following format:

**Validation Errors (422):**
```json
{
  "message": "The given data was invalid",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

**Authentication Errors (401):**
```json
{
  "message": "Unauthenticated."
}
```

**Authorization Errors (403):**
```json
{
  "message": "This action is unauthorized."
}
```

**Not Found Errors (404):**
```json
{
  "message": "Resource not found"
}
```

**Server Errors (500):**
```json
{
  "message": "Internal server error",
  "error": "A detailed error message"
}
```

## Firebase Integration

### Real-time Data Structure
Your app should listen to Firebase Realtime Database at:
```
/tanks/{tankId}
```

**Data Structure:**
```json
{
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
```

### Push Notification Data
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

## Headers Required

**All authenticated requests must include:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## Rate Limiting

- **Authentication endpoints**: 5 requests per minute
- **General API endpoints**: 60 requests per minute
- **Real-time endpoints**: 100 requests per minute

## Notes

1. All timestamps are in ISO 8601 format (UTC)
2. All monetary amounts are in USD
3. All volume measurements are in liters
4. All percentage values are decimals (e.g., 75.5 for 75.5%)
5. UUIDs are used for all resource identifiers
6. Pagination uses standard Laravel pagination structure