# Mobile App Updates for User-Tank Assignment System

## Overview
The backend now implements a user-tank assignment system with granular permissions. Users will only see tanks assigned to them (unless they're admins), and the API now returns permission flags for each tank.

---

## API Changes

### 1. Tank List Endpoint (`GET /api/tanks`)

**Behavior Changes:**
- Regular users now only receive tanks explicitly assigned to them
- Admin users continue to see all tanks in their organization

**New Response Fields:**
Each tank object now includes a `permissions` object:

```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Main Storage Tank",
      "location": "Building A",
      // ... other existing fields ...

      "permissions": {
        "can_order_water": true,
        "receive_alerts": true
      }
    }
  ],
  "pagination": { /* ... */ }
}
```

### 2. Tank Detail Endpoint (`GET /api/tanks/{id}`)

**Behavior Changes:**
- Returns `403 Forbidden` if user is not assigned to the tank (unless admin)
- Error message: `"You do not have access to this tank"`

**New Response Fields:**
Same `permissions` object as above:

```json
{
  "id": "uuid",
  "name": "Main Storage Tank",
  // ... other existing fields ...

  "permissions": {
    "can_order_water": true,
    "receive_alerts": false
  },

  "recent_readings": [ /* ... */ ],
  "statistics": { /* ... */ }
}
```

---

## Required Mobile App Changes

### 1. Update Tank Model

Add permission properties to your Tank model/data class:

```dart
// Flutter/Dart example
class Tank {
  final String id;
  final String name;
  final String location;
  // ... existing fields ...

  // NEW FIELDS
  final TankPermissions permissions;

  Tank.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        name = json['name'],
        // ... existing mappings ...
        permissions = TankPermissions.fromJson(json['permissions'] ?? {});
}

class TankPermissions {
  final bool canOrderWater;
  final bool receiveAlerts;

  TankPermissions({
    required this.canOrderWater,
    required this.receiveAlerts,
  });

  factory TankPermissions.fromJson(Map<String, dynamic> json) {
    return TankPermissions(
      canOrderWater: json['can_order_water'] ?? false,
      receiveAlerts: json['receive_alerts'] ?? false,
    );
  }
}
```

```kotlin
// Kotlin example
data class Tank(
    val id: String,
    val name: String,
    val location: String,
    // ... existing fields ...

    // NEW FIELD
    val permissions: TankPermissions
)

data class TankPermissions(
    @SerializedName("can_order_water")
    val canOrderWater: Boolean = false,

    @SerializedName("receive_alerts")
    val receiveAlerts: Boolean = false
)
```

### 2. Handle 403 Errors on Tank Detail

Update your tank detail API call to handle the new 403 response:

```dart
// Example
Future<Tank> getTankDetails(String tankId) async {
  try {
    final response = await http.get(
      Uri.parse('$baseUrl/api/tanks/$tankId'),
      headers: authHeaders,
    );

    if (response.statusCode == 200) {
      return Tank.fromJson(jsonDecode(response.body));
    } else if (response.statusCode == 403) {
      // User doesn't have access to this tank
      throw UnauthorizedAccessException(
        'You do not have access to this tank'
      );
    } else {
      throw Exception('Failed to load tank details');
    }
  } catch (e) {
    rethrow;
  }
}
```

Show appropriate error message to user when 403 is encountered.

### 3. Conditional UI for Water Ordering

Only show the "Order Water" button if the user has permission:

```dart
// Flutter example
Widget buildOrderWaterButton(Tank tank) {
  if (!tank.permissions.canOrderWater) {
    return SizedBox.shrink(); // Hide button
  }

  return ElevatedButton(
    onPressed: () => navigateToWaterOrder(tank),
    child: Text('Order Water'),
  );
}
```

Alternatively, show a disabled button with explanation:

```dart
Widget buildOrderWaterButton(Tank tank) {
  return ElevatedButton(
    onPressed: tank.permissions.canOrderWater
      ? () => navigateToWaterOrder(tank)
      : null, // Disabled
    child: Row(
      children: [
        Text('Order Water'),
        if (!tank.permissions.canOrderWater)
          Tooltip(
            message: 'You do not have permission to order water for this tank',
            child: Icon(Icons.info_outline, size: 16),
          ),
      ],
    ),
  );
}
```

### 4. Alert Preferences Handling

Store the `receive_alerts` permission for each tank. This can be used to:

1. **Filter which tanks trigger notifications:**
```dart
void handlePushNotification(Map<String, dynamic> data) {
  final tankId = data['tank_id'];
  final tank = getTankById(tankId);

  // Only show notification if user has receive_alerts enabled
  if (tank != null && tank.permissions.receiveAlerts) {
    showLocalNotification(data);
  }
}
```

2. **Show alert subscription status in UI:**
```dart
Widget buildAlertStatus(Tank tank) {
  return Row(
    children: [
      Icon(
        tank.permissions.receiveAlerts
          ? Icons.notifications_active
          : Icons.notifications_off,
      ),
      Text(
        tank.permissions.receiveAlerts
          ? 'Receiving alerts'
          : 'Not receiving alerts'
      ),
    ],
  );
}
```

### 5. Empty State Handling

Users might see an empty tank list if they haven't been assigned to any tanks:

```dart
Widget buildTankList(List<Tank> tanks) {
  if (tanks.isEmpty) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.inbox, size: 64, color: Colors.grey),
          SizedBox(height: 16),
          Text(
            'No tanks assigned',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
          ),
          SizedBox(height: 8),
          Text(
            'Contact your administrator to get access to tanks',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.grey),
          ),
        ],
      ),
    );
  }

  return ListView.builder(
    itemCount: tanks.length,
    itemBuilder: (context, index) => TankCard(tank: tanks[index]),
  );
}
```

---

## Testing Checklist

### User with No Tank Assignments
- [ ] App shows "No tanks assigned" empty state
- [ ] User cannot access any tank detail pages
- [ ] Dashboard shows appropriate message

### User with Tank Assignment (No Order Permission)
- [ ] User sees assigned tanks in list
- [ ] Can view tank details and sensor readings
- [ ] "Order Water" button is hidden or disabled
- [ ] Appropriate message shown when trying to order

### User with Full Permissions
- [ ] User sees assigned tanks
- [ ] Can view all tank details
- [ ] Can order water successfully
- [ ] Receives alerts for assigned tanks

### Admin User
- [ ] Sees all tanks in organization (regardless of assignment)
- [ ] Full permissions on all tanks
- [ ] Can perform all actions

### Edge Cases
- [ ] Handle 403 error gracefully when accessing unassigned tank
- [ ] Handle permission changes (e.g., permission revoked while viewing tank)
- [ ] Refresh tank list updates permissions correctly
- [ ] Offline mode handles permissions correctly

---

## Migration Strategy

### Option 1: Graceful Degradation (Recommended)
Default to allowing all actions if `permissions` field is missing (for backwards compatibility):

```dart
factory TankPermissions.fromJson(Map<String, dynamic>? json) {
  if (json == null) {
    // Default to all permissions if field is missing
    return TankPermissions(
      canOrderWater: true,
      receiveAlerts: true,
    );
  }

  return TankPermissions(
    canOrderWater: json['can_order_water'] ?? true,
    receiveAlerts: json['receive_alerts'] ?? true,
  );
}
```

### Option 2: Force Update
Require users to update to the new app version before the backend changes go live.

---

## Example Implementation (Flutter)

### Complete Tank Card Widget

```dart
class TankCard extends StatelessWidget {
  final Tank tank;

  const TankCard({required this.tank});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Tank header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        tank.name,
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      SizedBox(height: 4),
                      Text(
                        tank.location,
                        style: TextStyle(color: Colors.grey[600]),
                      ),
                    ],
                  ),
                ),

                // Alert status indicator
                if (tank.permissions.receiveAlerts)
                  Icon(
                    Icons.notifications_active,
                    color: Colors.blue,
                    size: 20,
                  ),
              ],
            ),

            SizedBox(height: 16),

            // Water level indicator
            WaterLevelIndicator(
              level: tank.currentLevel,
              capacity: tank.capacityLiters,
            ),

            SizedBox(height: 16),

            // Action buttons
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _viewDetails(context),
                    icon: Icon(Icons.info_outline),
                    label: Text('Details'),
                  ),
                ),

                SizedBox(width: 12),

                // Order water button (conditional)
                if (tank.permissions.canOrderWater)
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () => _orderWater(context),
                      icon: Icon(Icons.water_drop),
                      label: Text('Order'),
                    ),
                  ),
              ],
            ),

            // Permission info (optional, for debugging)
            if (!tank.permissions.canOrderWater)
              Padding(
                padding: EdgeInsets.only(top: 8),
                child: Text(
                  'Water ordering not enabled for this tank',
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[600],
                    fontStyle: FontStyle.italic,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  void _viewDetails(BuildContext context) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => TankDetailsPage(tankId: tank.id),
      ),
    );
  }

  void _orderWater(BuildContext context) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => WaterOrderPage(tank: tank),
      ),
    );
  }
}
```

---

## API Response Examples

### Successful Tank List Response
```json
{
  "data": [
    {
      "id": "9d5e8f2a-1234-5678-90ab-cdef12345678",
      "organization_id": "8c4d7e1b-1234-5678-90ab-cdef12345678",
      "sensor_id": "7b3c6d0a-1234-5678-90ab-cdef12345678",
      "name": "Main Storage Tank",
      "location": "Building A - Ground Floor",
      "latitude": -17.8252,
      "longitude": 31.0335,
      "capacity_liters": 10000,
      "height_mm": 2000,
      "diameter_mm": 1500,
      "shape": "cylindrical",
      "material": "plastic",
      "installation_height_mm": 100,
      "low_level_threshold": 20,
      "critical_level_threshold": 10,
      "refill_enabled": true,
      "auto_refill_threshold": 30,
      "created_at": "2025-09-15T10:30:00.000000Z",
      "updated_at": "2025-11-08T12:00:00.000000Z",
      "last_updated": "2025-11-08T12:00:00.000000Z",
      "current_level": 65.5,
      "current_volume": 6550,
      "sensor_status": "active",
      "status": "normal",
      "last_reading_at": "2025-11-08T11:55:00.000000Z",
      "sensor": {
        "id": "7b3c6d0a-1234-5678-90ab-cdef12345678",
        "device_id": "DF555-372702",
        "status": "active",
        "battery_level": 85,
        "signal_strength": 72,
        "last_seen": "2025-11-08T11:55:00.000000Z"
      },
      "permissions": {
        "can_order_water": true,
        "receive_alerts": true
      }
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

### 403 Error Response (Unauthorized Access)
```json
{
  "message": "You do not have access to this tank"
}
```

### Empty Tank List Response
```json
{
  "data": [],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 0,
    "last_page": 1,
    "from": null,
    "to": null
  }
}
```

---

## Additional Considerations

### 1. Local Storage/Caching
When caching tank data locally, ensure permissions are also cached and updated:

```dart
// Cache tank data including permissions
await _cacheService.saveTank(tank.id, tank.toJson());

// When loading from cache, check if permissions exist
final cachedData = await _cacheService.getTank(tank.id);
if (cachedData != null && cachedData['permissions'] == null) {
  // Force refresh from server if permissions are missing
  await _refreshTankFromServer(tank.id);
}
```

### 2. Real-time Updates
If using WebSockets or push notifications for real-time tank updates, ensure permission changes trigger a refresh:

```dart
void onWebSocketMessage(Map<String, dynamic> message) {
  if (message['type'] == 'tank_permission_updated') {
    final tankId = message['tank_id'];
    _refreshTankPermissions(tankId);
  }
}
```

### 3. Analytics/Tracking
Track permission-related events for debugging:

```dart
// Track when users encounter permission restrictions
if (!tank.permissions.canOrderWater) {
  analytics.logEvent(
    name: 'permission_restriction_encountered',
    parameters: {
      'restriction_type': 'order_water',
      'tank_id': tank.id,
      'user_role': currentUser.role,
    },
  );
}
```

### 4. User Feedback
Provide clear feedback when permissions prevent actions:

```dart
void attemptWaterOrder(Tank tank) {
  if (!tank.permissions.canOrderWater) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Permission Required'),
        content: Text(
          'You do not have permission to order water for this tank. '
          'Please contact your administrator if you need access.'
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('OK'),
          ),
          TextButton(
            onPressed: () => _contactSupport(),
            child: Text('Contact Support'),
          ),
        ],
      ),
    );
    return;
  }

  // Proceed with water order
  _showWaterOrderForm(tank);
}
```

---

## Questions to Address Before Implementation

1. **Should users see tanks they don't have access to as "locked" or completely hidden?**
   - Current implementation: Completely hidden (recommended)
   - Alternative: Show as locked/grayed out

2. **Should the app cache permissions or always fetch fresh?**
   - Recommended: Cache but refresh on app launch and pull-to-refresh

3. **How should permission changes be communicated to online users?**
   - Options: Push notification, WebSocket, or on next API call

4. **Should there be a way for users to request access to tanks?**
   - Could add a "Request Access" feature in future

5. **What analytics/metrics should be tracked?**
   - Permission denials
   - Empty state views
   - Feature usage by permission type

---

## Timeline Recommendation

1. **Day 1-2**: Update data models and API layer
2. **Day 3-4**: Implement UI changes and conditional logic
3. **Day 5**: Add empty states and error handling
4. **Day 6-7**: Testing and bug fixes
5. **Day 8**: Beta testing with select users
6. **Day 9-10**: Final adjustments and production release

---

## Support & Documentation

After implementing these changes, update:
- User documentation explaining tank assignments
- Admin guide for assigning users to tanks
- In-app help text for permission-related screens
- Support team knowledge base for common permission questions
