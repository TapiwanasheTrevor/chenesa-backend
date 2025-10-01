# Dingtek DF555 Sensor Configuration Guide for Chenesa Production

## Production Server Details
- **Production URL**: https://chenesa-shy-grass-3201.fly.dev
- **API Endpoint**: https://chenesa-shy-grass-3201.fly.dev/api/sensors/dingtek/data
- **Port**: 443 (HTTPS)

## Sensor Configuration Steps

### 1. Physical Setup Requirements
- TTL tool and wires (complimentary accessory)
- Serial port software
- DF555 device
- Magnet for device reset

### 2. Connect TTL to Sensor
- Connect TTL tool to the debug interface of your DF555 device
- **CRUCIAL**: Connect GND_DF555 to GND_TTL
- Connect TTL's USB interface to your computer

### 3. Serial Port Software Configuration
Set the following parameters:
- **COM Num**: Check in Device Manager → Port
- **Baudrate**: 115200
- **Parity bit**: NONE
- **Data bit**: 8
- **Stop bit**: 1
- **Important**: DO NOT select "Receive as hex" option

### 4. Wake/Reset the DF555 Device
- Move a magnet down from the red mark on the device and remove it
- Success indicated by red LED on PCB board lighting up
- Device will immediately report one data packet

### 5. Configure Server Address

For HTTPS connection to fly.io:
```
8002999906chenesa-shy-grass-3201.fly.dev;443;81
```

**CRITICAL NOTES**:
- You MUST include two semicolons at the end (`;`)
- Command format: `8002999906[SERVER];[PORT];81`
- Send command in ASCII format through serial port software

### 6. Data Format Expected by API

The API endpoint accepts data in the following formats:

#### JSON Format (Recommended)
```json
{
  "device_id": "YOUR_SENSOR_ID",
  "level": 75.5,
  "distance": 2.5,
  "temperature": 25.3,
  "battery": 85,
  "timestamp": "2025-01-18T10:30:00Z"
}
```

#### Form Data Format
```
device_id=YOUR_SENSOR_ID&level=75.5&distance=2.5&temperature=25.3&battery=85
```

### 7. Authentication

Currently, the sensor authentication middleware supports:
- **IP Whitelist**: Can be configured via environment variables
- **API Key**: Can be enabled via environment variables

For production deployment, you may want to:
1. Enable API key authentication
2. Configure allowed IPs if sensors have static IPs

### 8. Testing Connection

#### Test with curl:
```bash
curl -X POST https://chenesa-shy-grass-3201.fly.dev/api/sensors/dingtek/data \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "test_sensor_001",
    "level": 50.0,
    "distance": 3.0,
    "temperature": 22.5,
    "battery": 90
  }'
```

Expected response:
```json
{
  "status": "success",
  "message": "Data received successfully",
  "timestamp": "2025-01-18T10:30:00.000Z"
}
```

### 9. Monitoring & Debugging

#### Check sensor status:
```bash
curl "https://chenesa-shy-grass-3201.fly.dev/api/sensors/status?device_id=YOUR_SENSOR_ID"
```

#### View logs on fly.io:
```bash
fly logs
```

## API Endpoint Details

### Primary Endpoint
- **URL**: `/api/sensors/dingtek/data`
- **Methods**: POST, PUT, PATCH
- **Content-Types**: application/json, application/x-www-form-urlencoded

### Status Endpoint
- **URL**: `/api/sensors/status`
- **Method**: GET
- **Parameters**: device_id (required)

## Important Notes

1. **Automatic Sensor Creation**: If a sensor with the provided device_id doesn't exist, the system will automatically create it and assign it to the first available tank.

2. **Firebase Integration**: All sensor readings are automatically synced with Firebase Realtime Database for mobile app access.

3. **Rate Limiting**: Maximum 1000 requests per hour per IP address.

4. **Data Persistence**: All sensor readings are stored in PostgreSQL and synced with Firebase.

## Troubleshooting

### Sensor not connecting:
1. Verify the server address configuration includes two semicolons
2. Check if HTTPS port 443 is accessible from sensor location
3. Review fly.io logs for connection attempts

### Data not appearing:
1. Check sensor status endpoint
2. Verify data format matches expected JSON/form structure
3. Review application logs for parsing errors

### Authentication issues:
1. Check if API key is required (contact admin)
2. Verify sensor IP is whitelisted if IP filtering is enabled

## Environment Variables (for admins)

Add these to fly.io secrets if needed:
```bash
# Enable sensor API key authentication
fly secrets set SENSOR_REQUIRE_API_KEY=true
fly secrets set SENSOR_API_KEY=your-secure-api-key

# Configure allowed IPs (comma-separated)
fly secrets set SENSOR_ALLOWED_IPS=ip1,ip2,ip3
```

## Contact Support

For issues with sensor configuration or API integration, check:
- Application logs: `fly logs`
- Database for sensor records
- Firebase Realtime Database for sync status