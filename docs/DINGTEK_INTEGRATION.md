# Dingtek ThingsBoard Cloud Integration

This document describes the integration between Chenesa and the Dingtek ThingsBoard cloud platform for retrieving sensor data.

## Overview

Chenesa integrates with Dingtek's ThingsBoard cloud platform to automatically fetch sensor readings from DF555 ultrasonic level sensors. The integration runs on a scheduled basis and syncs device telemetry data into the Chenesa database.

## Architecture

### Components

1. **DingtekThingsBoardService** (`app/Services/DingtekThingsBoardService.php`)
   - Handles authentication with Dingtek ThingsBoard API
   - Fetches device list and telemetry data
   - Maps Dingtek data format to Chenesa database schema
   - Auto-creates tanks for sensors without assignments

2. **SyncDingtekSensors Command** (`app/Console/Commands/SyncDingtekSensors.php`)
   - Artisan command for manual and scheduled syncing
   - Provides test mode for connection verification
   - Displays sync results and error reporting

3. **Scheduler** (`routes/console.php`)
   - Configured to run sync every 15 minutes
   - Prevents overlapping executions
   - Logs output to `storage/logs/dingtek-sync.log`

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
DINGTEK_BASE_URL=https://cloud.dingtek.com
DINGTEK_USERNAME=your_username@example.com
DINGTEK_PASSWORD=your_password
```

### Current Credentials

```
Username: tadiwaweb@gmail.com
Password: adiwa00
Base URL: https://cloud.dingtek.com
```

## API Endpoints Used

### 1. Authentication
- **Endpoint**: `POST /api/auth/login`
- **Purpose**: Obtain access token
- **Token Validity**: 24 hours (cached for 23 hours)

### 2. Get User Devices
- **Endpoint**: `GET /api/user/devices`
- **Purpose**: Retrieve list of all devices
- **Authentication**: Bearer token

### 3. Get Device Telemetry
- **Endpoint**: `GET /api/plugins/telemetry/DEVICE/{deviceId}/values/timeseries`
- **Purpose**: Get latest telemetry readings
- **Authentication**: Bearer token

### 4. Get Device Attributes
- **Endpoint**: `GET /api/plugins/telemetry/DEVICE/{deviceId}/values/attributes`
- **Purpose**: Get device configuration
- **Authentication**: Bearer token

## Telemetry Data Mapping

Dingtek sensors provide the following telemetry keys:

| Dingtek Key | Chenesa Field | Conversion | Description |
|-------------|---------------|------------|-------------|
| `level` | `water_level_mm` | cm → mm (× 10) | Water level in centimeters |
| `temperature` | `temperature` | Direct | Temperature in °C |
| `volt` | `battery_voltage` | ÷ 100 | Battery voltage (0.01V units) |
| `rsrp` | `signal_rssi` | ÷ 10 | Signal strength (0.1dBm units) |
| `alarmLevel` | - | Ignored | Alarm status |
| `alarmBattery` | - | Ignored | Battery alarm |
| `frameCounter` | - | Ignored | Frame counter |

### Calculations

**Battery Percentage:**
```
voltage = volt / 100  // Convert to volts
battery_level = ((voltage - 3.0) / 1.2) × 100
// Range: 3.0V (0%) to 4.2V (100%)
```

**Signal Strength:**
```
rssi_dbm = rsrp / 10  // Convert to dBm
signal_percentage = 2 × (rssi_dbm + 100)
// Typical range: -120dBm to -40dBm
```

**Water Level:**
```
water_level_mm = level × 10  // Convert cm to mm
distance_mm = tank_height_mm - water_level_mm
water_level_percentage = (water_level_mm / tank_height_mm) × 100
```

## Usage

### Manual Sync

Sync all devices and readings:
```bash
php artisan dingtek:sync
```

### Test Connection

Verify connectivity without storing data:
```bash
php artisan dingtek:sync --test
```

This will:
1. Authenticate with Dingtek
2. List all available devices
3. Show telemetry for the first device

### Automated Sync

The sync runs automatically every 15 minutes via Laravel's task scheduler.

To enable scheduled tasks in production:
```bash
# Add to crontab
* * * * * cd /path/to/chenesa && php artisan schedule:run >> /dev/null 2>&1
```

On Fly.io, add to `fly.toml`:
```toml
[processes]
web = "php artisan serve --host=0.0.0.0 --port=8080"
scheduler = "while true; do php artisan schedule:run; sleep 60; done"
```

## Device Auto-Creation

When syncing devices from Dingtek:

1. **Sensor Creation**: If a device doesn't exist locally, it's automatically created
2. **Tank Assignment**: Each sensor gets a default tank with:
   - Height: 2000mm (2 meters)
   - Diameter: 1500mm
   - Capacity: 10,000 liters
   - Shape: Cylindrical
   - Material: Plastic

3. **Organization**: Sensors are assigned to the first available organization or a default "Default Organization"

## Data Flow

```
1. Scheduler triggers every 15 minutes
        ↓
2. Authenticate with Dingtek API
        ↓
3. Fetch all user devices
        ↓
4. For each device:
   a. Get latest telemetry
   b. Create/update sensor in database
   c. Create tank if needed
   d. Store sensor reading
   e. Sync with Firebase
        ↓
5. Return sync results
```

## Monitoring

### Check Sync Logs
```bash
tail -f storage/logs/dingtek-sync.log
```

### View Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep Dingtek
```

### Database Queries

**Check sensors synced from Dingtek:**
```sql
SELECT device_id, battery_level, signal_strength, last_seen
FROM sensors
WHERE device_id LIKE '%-%'
ORDER BY last_seen DESC;
```

**Check latest readings:**
```sql
SELECT s.device_id, sr.water_level_percentage, sr.temperature, sr.created_at
FROM sensor_readings sr
JOIN sensors s ON s.id = sr.sensor_id
WHERE s.device_id LIKE '%-%'
ORDER BY sr.created_at DESC
LIMIT 10;
```

## Troubleshooting

### Authentication Failed

**Symptom**: "Authentication failed" error

**Solutions**:
1. Verify credentials in `.env`
2. Check if password contains special characters (may need escaping)
3. Verify network connectivity to cloud.dingtek.com
4. Clear auth token cache: `php artisan cache:forget dingtek_auth_token`

### Connection Timeout

**Symptom**: "Connection timed out" error

**Solutions**:
1. Check internet connection
2. Verify firewall allows outbound HTTPS
3. Increase timeout in `DingtekThingsBoardService.php`

### No Readings Stored

**Symptom**: Sensors created but no readings

**Possible Causes**:
1. Device has no recent telemetry data
2. Tank not assigned to sensor (should auto-create)
3. Check logs for specific errors

### Invalid Data Values

**Symptom**: Strange water levels or percentages

**Possible Causes**:
1. Default tank dimensions don't match actual tank
2. Sensor installation height differs from tank configuration
3. Need to update tank dimensions in database

**Solution**:
Update tank dimensions via Filament admin panel or direct database update.

## API Rate Limits

- No explicit rate limits documented
- Current sync interval: 15 minutes
- 4 API calls per sync (1 auth + 1 device list + 2-4 telemetry requests)
- Approximately 384 API calls per day

## Future Enhancements

1. **Configurable Sync Interval**: Allow per-device sync schedules
2. **Historical Data Import**: Fetch historical telemetry for backfill
3. **Alert Integration**: Subscribe to Dingtek alarms
4. **Device Provisioning**: Auto-configure new devices via API
5. **Custom Tank Mapping**: Map specific devices to specific tanks
6. **Batch Processing**: Optimize API calls with batch endpoints
7. **Webhook Support**: Receive push notifications instead of polling

## References

- Dingtek API Documentation: https://cloud.dingtek.com/swagger-ui/
- ThingsBoard REST API: https://thingsboard.io/docs/pe/reference/python-rest-client/
- ThingsBoard Python Client: https://github.com/thingsboard/thingsboard-python-rest-client

## Support

For issues with the Dingtek integration:
1. Check application logs
2. Run test command to verify connectivity
3. Verify credentials and network access
4. Contact Dingtek support if API issues persist
