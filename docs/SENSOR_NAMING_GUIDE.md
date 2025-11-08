# Sensor Naming and Alias Management

## Overview

Sensors can now have user-friendly names (aliases) in addition to their technical device IDs. This makes it easier to identify sensors in the system, especially when managing multiple sensors.

## How Sensor Names Work

### Automatic Name Generation

When sensors are synced from Dingtek, the system automatically generates friendly names based on the sensor's IMEI:

**Format**: `{MODEL}-{LAST_6_DIGITS_OF_IMEI}`

**Examples**:
- IMEI `869080071372702` → `DF555-372702`
- IMEI `861556079063995` → `DF555-063995`
- IMEI `861556079065164` → `DF555-065164`

### Custom Aliases

You can set custom, descriptive names for any sensor to make them even easier to identify:

**Examples**:
- `Kitchen Tank Sensor`
- `Bathroom Tank Sensor`
- `Main Storage - Building A`
- `Backup Water Supply`

## Managing Sensor Aliases

### Command: `sensor:alias`

This command allows you to manage sensor names and aliases.

### Usage Examples

#### 1. List All Sensors
View all sensors with their current names/aliases:

```bash
php artisan sensor:alias --list
```

**Output**:
```
📡 All Sensors:

+-------------+--------------------------------------+----------------------+-------+--------+
| ID          | Device ID                            | Alias/Name           | Model | Status |
+-------------+--------------------------------------+----------------------+-------+--------+
| 019a6288... | 79fbf0d0-d3f4-11ef-b0cb-bdbed792c3b4 | Kitchen Tank Sensor  | DF555 | active |
| 019a6288... | 0e1320d0-6ea1-11f0-b0cb-bdbed792c3b4 | Bathroom Tank Sensor | DF555 | active |
| 019a6288... | 30e2df10-6ea1-11f0-b0cb-bdbed792c3b4 | DF555-065164         | DF555 | active |
| 019a6288... | b63429e0-6ea0-11f0-b0cb-bdbed792c3b4 | DF555-064076         | DF555 | active |
+-------------+--------------------------------------+----------------------+-------+--------+
```

#### 2. Auto-Generate Friendly Names
Automatically generate friendly names for all sensors that don't have one:

```bash
php artisan sensor:alias --auto
```

**Output**:
```
Auto-generating friendly names for 4 sensor(s)...

✓ 79fbf0d0-d3f4-11ef-b0cb-bdbed792c3b4 → DF555-372702
✓ 0e1320d0-6ea1-11f0-b0cb-bdbed792c3b4 → DF555-063995
✓ 30e2df10-6ea1-11f0-b0cb-bdbed792c3b4 → DF555-065164
✓ b63429e0-6ea0-11f0-b0cb-bdbed792c3b4 → DF555-064076

✅ Updated 4 sensor(s)
```

#### 3. Set Custom Alias (Direct)
Set a custom name for a specific sensor:

```bash
php artisan sensor:alias "DF555-372702" "Kitchen Tank Sensor"
```

**Output**:
```
✅ Sensor alias updated:
   Device ID: 79fbf0d0-d3f4-11ef-b0cb-bdbed792c3b4
   Old Name: DF555-372702
   New Name: Kitchen Tank Sensor
```

You can also use the current alias to update:
```bash
php artisan sensor:alias "Kitchen Tank Sensor" "Main Kitchen Tank"
```

#### 4. Interactive Mode
Run the command without arguments for an interactive experience:

```bash
php artisan sensor:alias
```

This will guide you through selecting a sensor and setting a new alias.

## API Integration

### Sensor Response with Aliases

When fetching sensor data via API, the `name` field contains the alias:

```json
{
  "id": "019a6288-7e89-727f-a684-4624eb581a33",
  "device_id": "79fbf0d0-d3f4-11ef-b0cb-bdbed792c3b4",
  "name": "Kitchen Tank Sensor",
  "imei": "869080071372702",
  "model": "DF555",
  "status": "active",
  "last_seen": "2025-11-08T09:15:00.000000Z",
  "battery_level": 85,
  "signal_strength": -65
}
```

### Display Name Attribute

The Sensor model includes a `display_name` attribute that returns:
- The custom alias if set
- The auto-generated name if no alias
- The device_id as fallback

```php
$sensor->display_name; // Returns alias or device_id
```

## Database Schema

### Sensors Table Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | UUID | Primary key |
| `device_id` | String | Dingtek device UUID |
| `name` | String | User-friendly alias/name (nullable) |
| `imei` | String | Sensor IMEI from Dingtek |
| `model` | String | Sensor model (e.g., DF555) |
| `status` | Enum | active, inactive, maintenance |

## Automatic Name Generation During Sync

When running `php artisan dingtek:sync`, the system:

1. Fetches sensors from Dingtek cloud
2. Stores/updates the IMEI from Dingtek's device name
3. Auto-generates a friendly name if one doesn't exist
4. Preserves any custom aliases that were manually set

This means:
- **New sensors**: Get auto-generated names
- **Existing sensors with custom aliases**: Keep their custom names
- **Existing sensors without names**: Get auto-generated names

## Best Practices

### Naming Conventions

1. **Be Descriptive**: Use names that describe the sensor's location or purpose
   - ✅ `Kitchen Tank Sensor`
   - ✅ `Building A - Roof Tank`
   - ❌ `Sensor 1`

2. **Include Location**: Help identify where the sensor is installed
   - ✅ `Main Floor Bathroom`
   - ✅ `Basement Storage Tank`

3. **Use Consistent Format**: Establish a naming pattern for your organization
   - `{Location} - {Purpose} Sensor`
   - `{Building} {Floor} {Room} Tank`

### When to Use Auto-Generated Names

Auto-generated names (e.g., `DF555-372702`) are useful when:
- You have many sensors and need quick identification
- You want to reference the IMEI quickly
- You haven't yet determined descriptive names

### When to Use Custom Aliases

Custom aliases are better when:
- You have a small number of sensors to manage
- Location-based identification is important
- You want names that are meaningful to end-users

## Mobile App Integration

The mobile app should display sensor names in the following priority:

1. **Custom Alias** (if set): `"Kitchen Tank Sensor"`
2. **Auto-Generated Name**: `"DF555-372702"`
3. **Device ID** (fallback): `"79fbf0d0-d3f4-11ef-b0cb-bdbed792c3b4"`

### Flutter Example

```dart
class SensorCard extends StatelessWidget {
  final Sensor sensor;

  String get displayName {
    return sensor.name ?? sensor.deviceId;
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        title: Text(displayName),
        subtitle: Text('IMEI: ${sensor.imei}'),
        trailing: Icon(
          sensor.status == 'active'
            ? Icons.check_circle
            : Icons.error
        ),
      ),
    );
  }
}
```

## Production Deployment

After deploying the sensor naming feature:

```bash
# 1. Run migrations
php artisan migrate

# 2. Auto-generate names for existing sensors
php artisan sensor:alias --auto

# 3. Optionally set custom aliases for key sensors
php artisan sensor:alias "DF555-372702" "Main Storage Tank"
```

## Troubleshooting

### Sensor Has No Name

If a sensor shows `(none)` or `null` for the name:

```bash
# Auto-generate a name
php artisan sensor:alias --auto

# Or manually set one
php artisan sensor:alias "{device_id_or_current_name}" "New Custom Name"
```

### Name Not Updating After Sync

Names are only auto-generated for sensors that don't already have a name. If you want to regenerate all names:

```bash
# Clear all names (use with caution!)
psql -c "UPDATE sensors SET name = NULL;"

# Then regenerate
php artisan sensor:alias --auto
```

### Finding a Sensor by IMEI

```sql
SELECT id, device_id, name, imei
FROM sensors
WHERE imei = '869080071372702';
```

Or use the command:
```bash
php artisan sensor:alias --list | grep 372702
```

## Summary

- ✅ Sensors automatically get friendly names based on IMEI
- ✅ Custom aliases can be set for easier identification
- ✅ Names are preserved during sync operations
- ✅ API returns the name field for mobile app display
- ✅ Command-line tools available for bulk management

For more details, see the [Mobile App Integration Guide](./MOBILE_APP_INTEGRATION_GUIDE.md).
