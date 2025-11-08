# Chenesa Mobile App - Complete Integration Guide

## Overview

The Chenesa Mobile App is a Flutter-based water tank monitoring system that provides real-time insights into water storage infrastructure. The app connects to IoT sensors (like DF555) through a Laravel backend API to monitor water levels, track consumption patterns, and provide intelligent alerts.

## Architecture

```
[DF555 Sensors] → [Data Bridge/Gateway] → [Laravel API] → [Mobile App]
                                      ↓
                                [Firebase Real-time] → [Push Notifications]
```

## Tank Management System

### Tank Enrollment Process

#### 1. Tank Registration
**Endpoint:** `POST /api/tanks`
**Headers:** `Authorization: Bearer {token}`

**Request Payload:**
```json
{
  "name": "Main Storage Tank",
  "location": "Building A - Rooftop",
  "latitude": -17.8292,
  "longitude": 31.0522,
  "capacity_liters": 5000,
  "height_mm": 2000,
  "diameter_mm": 1500,
  "shape": "cylindrical",
  "material": "plastic",
  "installation_height_mm": 100,
  "low_level_threshold": 25,
  "critical_level_threshold": 10,
  "refill_enabled": true,
  "auto_refill_threshold": 20
}
```

**Response:**
```json
{
  "data": {
    "id": "019955d9-f2e2-7170-814a-b197b9c340bc",
    "organization_id": "org_uuid",
    "name": "Main Storage Tank",
    "location": "Building A - Rooftop",
    "capacity_liters": 5000,
    "current_level": 0,
    "current_volume": 0,
    "status": "inactive",
    "sensor_id": null,
    "created_at": "2025-01-15T10:00:00.000000Z"
  }
}
```

#### 2. Tank Dimensions & Volume Calculation

The app supports multiple tank shapes with automatic volume calculations:

**Cylindrical Tanks:**
- Volume = π × (diameter/2)² × (height × level_percentage/100)
- Required: `diameter_mm`, `height_mm`

**Rectangular Tanks:**
- Volume = length × width × (height × level_percentage/100)
- Required: `length_mm`, `width_mm`, `height_mm`

**Custom Tanks:**
- Volume lookup table based on level percentage
- Required: `volume_table` JSON array

### Sensor Pairing System

#### 1. Sensor Registration
**Endpoint:** `POST /api/sensors`
**Headers:** `Authorization: Bearer {token}`

**Request Payload:**
```json
{
  "device_id": "DT001",
  "sensor_type": "DF555",
  "communication_type": "usb_serial",
  "installation_height_mm": 2100,
  "calibration_offset": 0,
  "max_range_mm": 4000,
  "min_range_mm": 50
}
```

**Response:**
```json
{
  "data": {
    "id": "sensor_uuid",
    "device_id": "DT001",
    "sensor_type": "DF555",
    "status": "offline",
    "battery_level": null,
    "signal_strength": null,
    "last_seen": null,
    "created_at": "2025-01-15T10:00:00.000000Z"
  }
}
```

#### 2. Tank-Sensor Pairing
**Endpoint:** `POST /api/tanks/{tank_id}/pair-sensor`
**Headers:** `Authorization: Bearer {token}`

**Request Payload:**
```json
{
  "sensor_id": "sensor_uuid",
  "installation_height_mm": 2100,
  "calibration_empty_distance": 2000,
  "calibration_full_distance": 100
}
```

**Response:**
```json
{
  "data": {
    "tank_id": "tank_uuid",
    "sensor_id": "sensor_uuid",
    "status": "paired",
    "calibration_status": "pending",
    "installation_height_mm": 2100,
    "empty_distance_mm": 2000,
    "full_distance_mm": 100
  }
}
```

## Sensor Data Flow

### 1. Sensor Reading Submission
**Endpoint:** `POST /api/sensors/{sensor_id}/readings`
**Headers:** `Authorization: Bearer {token}` or `X-API-Key: {sensor_api_key}`

**Request Payload:**
```json
{
  "distance_mm": 1250,
  "temperature_celsius": 23.4,
  "battery_voltage": 3.7,
  "signal_strength": 85,
  "timestamp": "2025-01-15T10:30:00Z"
}
```

**Response:**
```json
{
  "data": {
    "id": "reading_uuid",
    "sensor_id": "sensor_uuid",
    "tank_id": "tank_uuid",
    "distance_mm": 1250,
    "water_level_percentage": 75.5,
    "water_volume_liters": 3775,
    "temperature_celsius": 23.4,
    "battery_voltage": 3.7,
    "signal_strength": 85,
    "processed_at": "2025-01-15T10:30:05Z"
  }
}
```

### 2. Level Calculation Logic

```javascript
// Backend calculation
function calculateWaterLevel(distance_mm, tank, sensor) {
  const emptyDistance = sensor.calibration_empty_distance;
  const fullDistance = sensor.calibration_full_distance;
  
  // Calculate percentage
  const levelPercentage = Math.max(0, Math.min(100, 
    ((emptyDistance - distance_mm) / (emptyDistance - fullDistance)) * 100
  ));
  
  // Calculate volume based on tank shape
  const volume = calculateVolume(tank, levelPercentage);
  
  return {
    level_percentage: levelPercentage,
    volume_liters: volume,
    status: getStatus(levelPercentage, tank.thresholds)
  };
}
```

### 3. Real-time Updates via Firebase

**Firebase Structure:**
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
            "distance_mm": 1250,
            "temperature": 23.4,
            "battery_level": 85,
            "timestamp": "2025-01-15T10:30:00Z"
          },
          "sensor": {
            "status": "online",
            "signal_strength": 85,
            "last_seen": "2025-01-15T10:30:00Z"
          },
          "alerts": {
            "active": [],
            "last_alert": null
          }
        }
      }
    }
  }
}
```

## Required Backend API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `POST /api/auth/refresh` - Token refresh
- `GET /api/auth/user` - Get current user

### Tank Management
- `GET /api/tanks` - List user's tanks
- `POST /api/tanks` - Create new tank
- `GET /api/tanks/{id}` - Get tank details
- `PUT /api/tanks/{id}` - Update tank
- `DELETE /api/tanks/{id}` - Delete tank
- `GET /api/tanks/{id}/live-status` - Get real-time status

### Sensor Management
- `GET /api/sensors` - List sensors
- `POST /api/sensors` - Register new sensor
- `GET /api/sensors/{id}` - Get sensor details
- `PUT /api/sensors/{id}` - Update sensor
- `POST /api/sensors/{id}/calibrate` - Calibrate sensor
- `POST /api/tanks/{tank_id}/pair-sensor` - Pair sensor to tank
- `DELETE /api/tanks/{tank_id}/unpair-sensor` - Unpair sensor

### Sensor Readings
- `POST /api/sensors/{id}/readings` - Submit sensor reading
- `GET /api/tanks/{id}/readings` - Get tank readings history
- `GET /api/tanks/{id}/readings/latest` - Get latest reading
- `GET /api/tanks/{id}/analytics` - Get analytics data

### Dashboard & Analytics
- `GET /api/dashboard/overview` - Dashboard summary
- `GET /api/dashboard/consumption` - Consumption analytics
- `GET /api/dashboard/alerts` - Recent alerts

### Alerts & Notifications
- `GET /api/alerts` - List alerts
- `POST /api/alerts/{id}/acknowledge` - Acknowledge alert
- `PUT /api/user/notification-settings` - Update notification preferences
- `POST /api/user/fcm-token` - Update FCM token

### Water Orders (Optional)
- `GET /api/orders` - List water orders
- `POST /api/orders` - Create water order
- `GET /api/orders/{id}` - Get order details

## Mobile App Features

### ✅ Implemented Features

#### Core Functionality
- **User Authentication** - Login/Register with JWT tokens
- **Tank Dashboard** - Real-time tank status overview
- **Tank Management** - Add, edit, view tank details
- **Water Level Visualization** - Circular gauges and linear indicators
- **Analytics Dashboard** - Consumption patterns and trends
- **Alert System** - Low level and critical alerts
- **Profile Management** - User settings and preferences

#### UI Components
- **Tank Cards** - Comprehensive tank status display
- **Water Level Gauges** - Animated circular and linear indicators
- **Analytics Charts** - Historical data visualization
- **Responsive Design** - Works on various screen sizes
- **Dark/Light Theme** - Theme switching support

### 🔄 Partially Implemented

#### Real-time Features
- **Firebase Integration** - Configured but needs backend integration
- **Push Notifications** - FCM setup complete, needs backend triggers
- **Live Updates** - Real-time data sync via Firebase

### ❌ Missing Features

#### Sensor Management
- **Sensor Pairing UI** - Interface to pair sensors with tanks
- **Sensor Calibration** - Guided calibration process
- **Sensor Status Monitoring** - Battery, signal strength display
- **Sensor Configuration** - Settings and parameters

#### Advanced Tank Features
- **Tank Shape Configuration** - Support for different tank geometries
- **Volume Calculation Setup** - Custom volume tables
- **Multi-tank Views** - Grouped tank displays
- **Tank Location Mapping** - GPS coordinates and maps

#### Analytics & Reporting
- **Historical Data Export** - CSV/PDF export functionality
- **Advanced Analytics** - Predictive analytics and trends
- **Consumption Forecasting** - Usage prediction
- **Custom Reports** - User-defined reporting

#### Water Management
- **Water Ordering System** - Complete order management
- **Supplier Integration** - Water supplier connectivity
- **Delivery Tracking** - Order status and delivery updates
- **Inventory Management** - Water stock tracking

#### System Administration
- **Organization Management** - Multi-tenant support
- **User Role Management** - Admin, operator, viewer roles
- **System Settings** - Global configuration
- **Audit Logs** - System activity tracking

## Data Bridge Implementation

### DF555 Sensor Bridge
```python
# Example bridge for DF555 USB sensor
class DF555Bridge:
    def __init__(self, port, api_url, sensor_id):
        self.sensor = DF555Sensor(port)
        self.api_url = api_url
        self.sensor_id = sensor_id
    
    def start_monitoring(self):
        while True:
            # Read from DF555
            distance = self.sensor.get_distance()
            temp = self.sensor.get_temperature()
            battery = self.sensor.get_battery()
            
            # Send to API
            payload = {
                "distance_mm": distance,
                "temperature_celsius": temp,
                "battery_voltage": battery,
                "timestamp": datetime.now().isoformat()
            }
            
            response = requests.post(
                f"{self.api_url}/sensors/{self.sensor_id}/readings",
                json=payload,
                headers={"X-API-Key": "sensor_api_key"}
            )
            
            time.sleep(30)  # Read every 30 seconds
```

## Firebase Integration Requirements

### Backend Firebase Setup
1. **Real-time Database Rules**
2. **Cloud Functions** for data processing
3. **FCM Integration** for push notifications
4. **Authentication Integration**

### Required Firebase Services
- **Authentication** - User management
- **Realtime Database** - Live data sync
- **Cloud Messaging** - Push notifications
- **Cloud Functions** - Data processing triggers

## Security Considerations

### API Security
- **JWT Authentication** for user endpoints
- **API Keys** for sensor data submission
- **Rate Limiting** on all endpoints
- **Input Validation** and sanitization
- **HTTPS Only** communication

### Data Protection
- **Encrypted Storage** of sensitive data
- **Access Control** based on organization
- **Audit Logging** of all operations
- **Data Backup** and recovery procedures

## Testing & Validation

### API Testing
- **Unit Tests** for all endpoints
- **Integration Tests** for complete flows
- **Load Testing** for sensor data ingestion
- **Security Testing** for vulnerabilities

### Mobile App Testing
- **Unit Tests** for business logic
- **Widget Tests** for UI components
- **Integration Tests** for API connectivity
- **Device Testing** on various screen sizes

## Deployment Checklist

### Backend Requirements
- [ ] Laravel API with all endpoints implemented
- [ ] Firebase project configured
- [ ] Database migrations and seeders
- [ ] API documentation (Swagger/OpenAPI)
- [ ] Environment configuration
- [ ] SSL certificates
- [ ] Monitoring and logging

### Mobile App Requirements
- [ ] Firebase configuration files
- [ ] API endpoint configuration
- [ ] App signing certificates
- [ ] Store listing preparation
- [ ] Testing on physical devices
- [ ] Performance optimization

### Infrastructure
- [ ] Production server setup
- [ ] Database backup strategy
- [ ] CDN for static assets
- [ ] Monitoring and alerting
- [ ] Disaster recovery plan

This document provides the complete integration roadmap for the Chenesa mobile app, ensuring all components work together seamlessly for effective water tank monitoring and management.