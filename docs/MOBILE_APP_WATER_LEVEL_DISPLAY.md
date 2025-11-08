# Mobile App - Water Level Percentage Display Implementation Guide

## Overview

The backend API already provides all necessary data for displaying tank water level percentages. The `water_level_percentage` field is calculated server-side based on the DF555 sensor readings and tank dimensions.

## Key Changes

### ✅ No Backend Changes Required

The API already returns the `water_level_percentage` field in all tank endpoints. The mobile app just needs to display this data prominently.

---

## API Response Structure

### 1. GET /tanks - Tank List

The `current_level` field contains the water level percentage:

```json
{
  "data": [
    {
      "id": "019955d9-f2e2-7170-814a-b197b9c340bc",
      "name": "Main Storage Tank",
      "location": "Building A - Rooftop",

      // Physical Properties (needed for calculations)
      "capacity_liters": 5000,
      "height_mm": 2000,
      "diameter_mm": 1500,
      "shape": "cylindrical",
      "material": "plastic",

      // Current Water Level (MAIN FIELD TO DISPLAY)
      "current_level": 75.5,           // ← Water level percentage (0-100)
      "current_volume": 3775,          // ← Current volume in liters

      // Status & Thresholds
      "status": "normal",              // ← "normal", "low", or "critical"
      "low_level_threshold": 20,       // ← Low level alert threshold (%)
      "critical_level_threshold": 10,  // ← Critical level alert threshold (%)

      // Sensor Information
      "sensor": {
        "id": "019955d9-f2e2-7170-814a-b197b9c340bd",
        "device_id": "DT001",
        "status": "active",            // ← "active" or "inactive"
        "battery_level": 85,           // ← Sensor battery percentage
        "signal_strength": 75,         // ← Signal strength percentage
        "last_seen": "2025-09-17T11:25:00.000000Z"
      },

      "last_reading_at": "2025-09-17T11:25:00.000000Z",

      // User Permissions
      "permissions": {
        "can_order_water": true,
        "receive_alerts": true
      },

      "created_at": "2025-09-17T10:00:00.000000Z",
      "updated_at": "2025-09-17T11:25:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 5,
    "last_page": 1
  }
}
```

### 2. GET /tanks/{id} - Tank Details

Same structure as above, plus additional statistics:

```json
{
  "data": {
    // ... all fields from tank list above ...

    // Recent Readings (last 24 hours)
    "recent_readings": [
      {
        "timestamp": "2025-09-17T11:25:00.000000Z",
        "water_level_percentage": 75.5,  // ← Historical percentage
        "volume_liters": 3775,
        "temperature": 23.4
      }
      // ... more readings
    ],

    // Statistics (24-hour period)
    "statistics": {
      "avg_level_24h": 73.2,           // ← Average water level %
      "min_level_24h": 68.1,           // ← Minimum water level %
      "max_level_24h": 78.9,           // ← Maximum water level %
      "avg_level_7d": 71.5             // ← 7-day average
    }
  }
}
```

### 3. GET /tanks/{id}/history - Historical Data

Query parameters:
- `start_date` (optional): ISO date (e.g., "2025-09-10")
- `end_date` (optional): ISO date (e.g., "2025-09-17")
- `per_page` (optional): Default 50, max 100

Response:
```json
{
  "data": [
    {
      "id": "reading_uuid",
      "timestamp": "2025-09-17T11:25:00.000000Z",
      "distance_mm": 490,                    // ← Distance from sensor to water surface
      "water_level_mm": 1510,                // ← Water depth in mm
      "water_level_percentage": 75.5,        // ← Percentage (main field)
      "volume_liters": 3775.00,              // ← Calculated volume
      "temperature": 23.4,                   // ← Water temperature (if available)
      "battery_voltage": 3.7,                // ← Sensor battery voltage
      "signal_rssi": -65                     // ← Signal strength (dBm)
    }
    // ... more readings
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 144,
    "last_page": 3
  }
}
```

### 4. GET /tanks/{id}/live-status - Real-time Status

```json
{
  "data": {
    "tank_id": "019955d9-f2e2-7170-814a-b197b9c340bc",
    "tank_name": "Main Storage Tank",
    "last_updated": "2025-09-17T11:25:00.000000Z",
    "is_online": true,

    "water_level": {
      "percentage": 75.5,                // ← Current percentage
      "liters": 3775,                    // ← Current volume
      "status": "normal"                 // ← "normal", "low", or "critical"
    },

    "temperature": 23.4,
    "battery_level": 85,
    "active_alerts": 0,

    "refill_recommendation": {
      "recommended": false,
      "reason": "sufficient_water"
      // OR when low:
      // "recommended": true,
      // "priority": "urgent",
      // "reason": "critical_level",
      // "estimated_liters_needed": 2000
    }
  }
}
```

### 5. GET /tanks/{id}/analytics - Tank Analytics

Query parameters:
- `period` (optional): "24h", "7d", "30d" (default: "7d")

```json
{
  "data": {
    "period": "7d",
    "data_points": 1008,

    "water_level": {
      "current": 75.5,                   // ← Current percentage
      "average": 73.2,                   // ← Average percentage
      "minimum": 62.1,                   // ← Minimum percentage
      "maximum": 89.3                    // ← Maximum percentage
    },

    "volume": {
      "current_liters": 3775.00,
      "average_liters": 3660.00,
      "minimum_liters": 3105.00,
      "maximum_liters": 4465.00
    },

    "consumption": {
      "total_consumed": 8500,            // ← Total water consumed (L)
      "daily_average": 1214,             // ← Daily average consumption (L)
      "trend": "stable"                  // ← "increasing", "decreasing", "stable"
    },

    "temperature": {
      "current": 23.4,
      "average": 22.8,
      "minimum": 18.5,
      "maximum": 26.2
    },

    "trends": {
      "trend": "stable",                 // ← "increasing", "decreasing", "stable"
      "change_percentage": -2.3          // ← Percentage change
    },

    "alerts_count": 2
  }
}
```

---

## How Water Level Percentage is Calculated

### Backend Calculation Formula

The backend calculates the percentage using tank dimensions and sensor readings:

```
Given:
- H_total (tank height) = stored in tanks.height_mm
- H1 (distance from sensor to water surface) = from sensor reading (distance_mm)

Calculation:
1. H_liquid (water depth) = H_total - H1
2. water_level_percentage = (H_liquid / H_total) × 100

Example:
- Tank height = 2000 mm
- Sensor reading (distance to water) = 490 mm
- Water depth = 2000 - 490 = 1510 mm
- Percentage = (1510 / 2000) × 100 = 75.5%
```

### Volume Calculation (for reference)

For cylindrical tanks:
```
Volume (liters) = π × r² × h × 1000
Where:
- r = radius in meters (diameter_mm / 2000)
- h = water depth in meters (water_level_mm / 1000)
```

---

## Mobile App Implementation Recommendations

### 1. Tank List Screen

Display water level prominently for each tank:

```
┌─────────────────────────────────────────┐
│ 🏢 Main Storage Tank                   │
│ 📍 Building A - Rooftop                │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │  💧 75.5%  [████████░░] 3,775 L    │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ 🔋 Battery: 85%  📶 Signal: 75%        │
│ 🕐 Last update: 5 minutes ago          │
└─────────────────────────────────────────┘
```

**Color Coding:**
- 🟢 Green (≥90%): Full/Excellent
- 🔵 Blue (21-89%): Normal
- 🟠 Orange (11-20%): Low (matches `low_level_threshold`)
- 🔴 Red (≤10%): Critical (matches `critical_level_threshold`)

### 2. Tank Detail Screen

Show comprehensive water level information:

```
┌──────────── Tank Details ────────────┐
│                                      │
│          💧 Water Level              │
│         ┌──────────────┐             │
│         │    75.5%     │  ← Large   │
│         │   3,775 L    │    Display │
│         └──────────────┘             │
│                                      │
│  ┌────────────────────────────────┐  │
│  │ ░░░░░░░░░░░░░░░░                 │  │
│  │ ░░░░░░░░░░░░░░░░  ← 75.5%       │  │
│  │ ░░░░░░░░░░░░░░░░                 │  │
│  │ ░░░░░░░░░░░░░░░░  ← Visual      │  │
│  │ ░░░░░░░░░░░░░░░░    Tank        │  │
│  └────────────────────────────────┘  │
│                                      │
│  Capacity: 5,000 L                   │
│  Height: 2,000 mm                    │
│  Status: 🟢 Normal                   │
│                                      │
│  ⚠️ Thresholds:                      │
│  • Low Level: 20% (1,000 L)          │
│  • Critical: 10% (500 L)             │
│                                      │
│  📊 24-Hour Statistics               │
│  • Average: 73.2%                    │
│  • Min: 68.1% | Max: 78.9%          │
│                                      │
│  🔄 Last Reading                     │
│  5 minutes ago                       │
│                                      │
│  📈 [View History] [Order Water]     │
└──────────────────────────────────────┘
```

### 3. Water Level Graph/Chart

Use `recent_readings` or `/tanks/{id}/history` to plot:

```
Water Level Over Time (Last 7 Days)

100% ┤
 90% ┤     ╭─────╮
 80% ┤    ╭╯     ╰╮
 70% ┤───╯        ╰────╮
 60% ┤                 ╰─
 50% ┤
     └─────────────────────
     Mon Tue Wed Thu Fri Sat Sun
```

### 4. Dashboard/Overview Screen

Summary cards:

```
┌─────────────────────────────────────┐
│ 📊 Tank Overview                    │
├─────────────────────────────────────┤
│                                     │
│  🟢 Normal: 3 tanks                 │
│  🟠 Low: 1 tank                     │
│  🔴 Critical: 0 tanks               │
│                                     │
│  Average Level: 68.5%               │
│  Total Capacity: 25,000 L           │
│  Current Volume: 17,125 L           │
│                                     │
└─────────────────────────────────────┘
```

### 5. Alert Notifications

When water level crosses thresholds:

```
🔔 Low Water Level Alert

Tank: Main Storage Tank
Current Level: 18.5%
Volume: 925 L

The water level has dropped below your
low level threshold (20%).

[Order Water] [View Details] [Dismiss]
```

---

## Firebase Real-Time Updates

The backend syncs tank data to Firebase. Listen to:

```
/tanks/{tankId}
```

**Structure:**
```json
{
  "id": "tank_uuid",
  "name": "Main Storage Tank",
  "location": "Building A - Rooftop",
  "capacity_liters": 5000,
  "organization_id": "org_uuid",
  "organization_name": "Acme Corp",

  "latest_reading": {
    "id": "reading_uuid",
    "distance_mm": 490,
    "water_level_mm": 1510,
    "water_level_percentage": 75.5,    // ← Real-time percentage
    "volume_liters": 3775.00,
    "temperature": 23.4,
    "battery_voltage": 3.7,
    "timestamp": "2025-09-17T11:25:00.000000Z"
  },

  "sensor": {
    "id": "sensor_uuid",
    "device_id": "DT001",
    "status": "active"
  },

  "last_updated": "2025-09-17T11:25:00.000000Z"
}
```

**Implementation:**
```javascript
// React Native / Flutter example
const tankRef = firebase.database().ref(`tanks/${tankId}`);

tankRef.on('value', (snapshot) => {
  const tankData = snapshot.val();
  const percentage = tankData.latest_reading.water_level_percentage;
  const volume = tankData.latest_reading.volume_liters;

  // Update UI with new percentage
  updateWaterLevel(percentage, volume);
});
```

---

## Data Validation & Edge Cases

### Handle Missing/Invalid Data

```javascript
// Example data handling
const waterLevel = tank.current_level ?? 0;  // Default to 0 if null
const status = tank.status ?? 'unknown';     // Default status

// Show offline indicator if no recent reading
const isOffline = !tank.last_reading_at ||
                  (Date.now() - new Date(tank.last_reading_at)) > 3600000; // 1 hour
```

### Status Determination

The backend provides a `status` field, but you can also calculate it:

```javascript
function getTankStatus(percentage, lowThreshold, criticalThreshold) {
  if (percentage <= criticalThreshold) return 'critical';  // Red
  if (percentage <= lowThreshold) return 'low';            // Orange
  if (percentage >= 90) return 'full';                     // Green
  return 'normal';                                          // Blue
}

// Usage
const status = tank.status || getTankStatus(
  tank.current_level,
  tank.low_level_threshold,
  tank.critical_level_threshold
);
```

### Handle Sensor Offline

```javascript
const isSensorOffline = tank.sensor?.status !== 'active';

if (isSensorOffline) {
  // Show warning banner
  showWarning('Sensor offline - data may be outdated');
  // Disable real-time updates
  // Show last known reading with timestamp
}
```

---

## Key Features to Implement

### 1. ✅ Water Level Display
- Show `current_level` prominently as percentage
- Display `current_volume` in liters
- Use color coding based on status

### 2. ✅ Visual Tank Representation
- Animated water level indicator
- Color-coded based on percentage
- Show fill level visually

### 3. ✅ Historical Trends
- Line/area chart showing water level over time
- Use `/tanks/{id}/history` endpoint
- Allow date range selection (24h, 7d, 30d)

### 4. ✅ Status Indicators
- Normal, Low, Critical badges
- Sensor online/offline status
- Battery and signal strength

### 5. ✅ Alerts & Notifications
- Push notifications when thresholds crossed
- In-app alert list
- Alert resolution

### 6. ✅ Water Ordering Integration
- Show "Order Water" button when level is low
- Pre-fill order form with recommended volume
- Use `refill_recommendation` from `/tanks/{id}/live-status`

### 7. ✅ Real-time Updates
- Firebase listener for live data
- Auto-refresh on app resume
- Pull-to-refresh on tank list

---

## Testing Recommendations

### Test Different Water Levels

Use different scenarios:
1. **Full tank (95%)**: Should show green/success status
2. **Normal level (50%)**: Should show blue/normal status
3. **Low level (15%)**: Should show orange/warning status
4. **Critical level (5%)**: Should show red/danger status
5. **Empty tank (0%)**: Should show red/critical status

### Test Edge Cases

1. **No sensor data**: Handle null/missing readings gracefully
2. **Sensor offline**: Show appropriate message
3. **Stale data**: Show "last updated" timestamp
4. **Network errors**: Cache last known state

---

## API Endpoints Summary

| Endpoint | Purpose | Key Fields |
|----------|---------|------------|
| `GET /tanks` | List all tanks | `current_level`, `current_volume`, `status` |
| `GET /tanks/{id}` | Tank details + stats | `current_level`, `statistics`, `recent_readings` |
| `GET /tanks/{id}/history` | Historical readings | `water_level_percentage`, `timestamp` |
| `GET /tanks/{id}/live-status` | Real-time status | `water_level.percentage`, `refill_recommendation` |
| `GET /tanks/{id}/analytics` | Analytics & trends | `water_level`, `consumption`, `trends` |

---

## Questions or Issues?

If you encounter any API issues or need additional endpoints:
1. Check the full API documentation: `docs/MOBILE_API_DOCUMENTATION.md`
2. Test endpoints using the base URL: `https://chenesa-shy-grass-3201.fly.dev/api`
3. Contact the backend team for clarification

All calculations are handled server-side, so the mobile app only needs to display the provided `water_level_percentage` values!
