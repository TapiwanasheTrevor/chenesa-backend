<?php

namespace App\Console\Commands;

use App\Models\Sensor;
use App\Models\SensorReading;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SensorTcpServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensor:tcp-server {--port=8888}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start TCP server to receive Dingtek DF555 sensor data';

    private $socket;
    private $clients = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $port = $this->option('port');
        $host = '0.0.0.0'; // Listen on all interfaces

        // Create TCP socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($this->socket === false) {
            $this->error("Failed to create socket: " . socket_strerror(socket_last_error()));
            return 1;
        }

        // Set socket options
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

        // Bind socket to address and port
        if (socket_bind($this->socket, $host, $port) === false) {
            $this->error("Failed to bind socket: " . socket_strerror(socket_last_error($this->socket)));
            return 1;
        }

        // Listen for connections
        if (socket_listen($this->socket, 5) === false) {
            $this->error("Failed to listen on socket: " . socket_strerror(socket_last_error($this->socket)));
            return 1;
        }

        $this->info("TCP Server started on {$host}:{$port}");
        $this->info("Waiting for Dingtek sensor connections...");

        // Main server loop
        while (true) {
            // Accept incoming connections
            $client = @socket_accept($this->socket);

            if ($client === false) {
                continue;
            }

            // Get client info
            socket_getpeername($client, $clientIp, $clientPort);
            $this->info("New connection from {$clientIp}:{$clientPort}");
            Log::info("TCP: New sensor connection", ['ip' => $clientIp, 'port' => $clientPort]);

            // Read data from client (binary mode for Dingtek sensor)
            socket_set_nonblock($client);
            $data = '';
            $maxWait = 5; // 5 seconds timeout
            $start = microtime(true);

            while ((microtime(true) - $start) < $maxWait) {
                $buffer = @socket_read($client, 2048, PHP_BINARY_READ);
                if ($buffer === false) {
                    // No data available, wait a bit
                    usleep(50000); // 50ms
                    continue;
                }
                if ($buffer === '') {
                    break; // Connection closed
                }
                $data .= $buffer;

                // Check if we have a complete Dingtek packet (ends with 0x81)
                if (strlen($data) > 0 && ord($data[strlen($data) - 1]) === 0x81) {
                    break;
                }
            }

            if (!empty($data)) {
                $this->info("Received data: " . substr($data, 0, 200));
                Log::info("TCP: Received sensor data", [
                    'ip' => $clientIp,
                    'data_length' => strlen($data),
                    'data_preview' => substr($data, 0, 200)
                ]);

                // Parse and process the data
                $this->processSensorData($data, $clientIp);

                // Send acknowledgment
                $response = "OK\r\n";
                socket_write($client, $response, strlen($response));
            }

            // Close client connection
            socket_close($client);
            $this->info("Connection closed for {$clientIp}");
        }

        // Cleanup (never reached in normal operation)
        socket_close($this->socket);
        return 0;
    }

    /**
     * Process received sensor data
     */
    private function processSensorData(string $data, string $clientIp)
    {
        try {
            // Log raw data for debugging
            Log::info("TCP: Processing sensor data", [
                'raw_data' => $data,
                'hex_data' => bin2hex($data),
                'ip' => $clientIp
            ]);

            // Try to parse as ASCII/text format first
            $parsed = $this->parseAsciiFormat($data);

            if ($parsed) {
                // Forward to HTTP endpoint
                $this->forwardToHttpEndpoint($parsed);
                $this->info("Data processed successfully");
            } else {
                // Try binary format
                $parsed = $this->parseBinaryFormat($data);

                if ($parsed) {
                    $this->forwardToHttpEndpoint($parsed);
                    $this->info("Data processed successfully (binary)");
                } else {
                    $this->warn("Failed to parse sensor data format");
                    Log::warning("TCP: Unable to parse sensor data", ['data' => substr($data, 0, 500)]);
                }
            }

        } catch (\Exception $e) {
            $this->error("Error processing data: " . $e->getMessage());
            Log::error("TCP: Error processing sensor data", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Parse ASCII format data from Dingtek sensor
     */
    private function parseAsciiFormat(string $data): ?array
    {
        // Dingtek DF555 typically sends data in format like:
        // DeviceID,Distance,Temperature,Battery,RSSI
        // or as key-value pairs

        $lines = explode("\n", trim($data));
        $parsed = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Try comma-separated format
            if (strpos($line, ',') !== false) {
                $parts = explode(',', $line);
                if (count($parts) >= 2) {
                    $parsed['device_id'] = $parts[0] ?? 'unknown';
                    $parsed['distance'] = isset($parts[1]) ? (float)$parts[1] / 1000 : null; // Convert mm to meters
                    $parsed['temperature'] = isset($parts[2]) ? (float)$parts[2] : null;
                    $parsed['battery_level'] = isset($parts[3]) ? (int)$parts[3] : null;
                    $parsed['rssi'] = isset($parts[4]) ? (int)$parts[4] : null;
                    return $parsed;
                }
            }

            // Try key-value format
            if (strpos($line, ':') !== false || strpos($line, '=') !== false) {
                $kvPairs = preg_split('/[,;]/', $line);
                foreach ($kvPairs as $pair) {
                    if (preg_match('/(.+?)[:=](.+)/', trim($pair), $matches)) {
                        $key = strtolower(trim($matches[1]));
                        $value = trim($matches[2]);

                        if (strpos($key, 'device') !== false || strpos($key, 'id') !== false) {
                            $parsed['device_id'] = $value;
                        } elseif (strpos($key, 'distance') !== false || strpos($key, 'level') !== false) {
                            $parsed['distance'] = (float)$value / 1000;
                        } elseif (strpos($key, 'temp') !== false) {
                            $parsed['temperature'] = (float)$value;
                        } elseif (strpos($key, 'battery') !== false || strpos($key, 'bat') !== false) {
                            $parsed['battery_level'] = (int)$value;
                        } elseif (strpos($key, 'rssi') !== false || strpos($key, 'signal') !== false) {
                            $parsed['rssi'] = (int)$value;
                        }
                    }
                }
            }
        }

        return !empty($parsed) && isset($parsed['device_id']) ? $parsed : null;
    }

    /**
     * Parse binary format data from Dingtek sensor
     *
     * Packet Structure:
     * - Packet Head: 0x80 (1 byte)
     * - Forced Bit: 0x00 (1 byte)
     * - Device Type: 0x05 for DF555 (1 byte)
     * - Report Data Type: 0x01 (trigger), 0x02 (heartbeat), 0x03 (command reply) (1 byte)
     * - Packet Size: size of packet (1 byte)
     * - Payload: variable length (0-255 bytes)
     * - Packet Tail: 0x81 (1 byte)
     */
    private function parseBinaryFormat(string $data): ?array
    {
        $hexData = bin2hex($data);
        $length = strlen($data);

        Log::info("TCP: Attempting binary parse", [
            'hex' => $hexData,
            'length' => $length,
            'raw' => $data
        ]);

        // Check minimum packet size (header + tail = at least 6 bytes)
        if ($length < 6) {
            Log::warning("TCP: Packet too short", ['length' => $length]);
            return null;
        }

        // Check packet head (0x80)
        if (ord($data[0]) !== 0x80) {
            Log::warning("TCP: Invalid packet head", ['expected' => '0x80', 'got' => sprintf('0x%02X', ord($data[0]))]);
            return null;
        }

        // Check packet tail (0x81)
        if (ord($data[$length - 1]) !== 0x81) {
            Log::warning("TCP: Invalid packet tail", ['expected' => '0x81', 'got' => sprintf('0x%02X', ord($data[$length - 1]))]);
            return null;
        }

        // Parse header
        $forcedBit = ord($data[1]);
        $deviceType = ord($data[2]);
        $reportDataType = ord($data[3]);
        $packetSize = ord($data[4]);

        Log::info("TCP: Packet header parsed", [
            'forced_bit' => sprintf('0x%02X', $forcedBit),
            'device_type' => sprintf('0x%02X', $deviceType),
            'report_data_type' => sprintf('0x%02X', $reportDataType),
            'packet_size' => $packetSize
        ]);

        // Validate device type (0x05 for DF555)
        if ($deviceType !== 0x05) {
            Log::warning("TCP: Unexpected device type", ['expected' => '0x05', 'got' => sprintf('0x%02X', $deviceType)]);
        }

        // Extract payload (between header and tail)
        $payloadLength = $length - 6; // Total length minus header (5 bytes) and tail (1 byte)
        $payload = substr($data, 5, $payloadLength);

        // Parse payload based on report data type
        $parsed = $this->parsePayload($payload, $reportDataType);

        if ($parsed) {
            $parsed['report_type'] = $reportDataType;
            $parsed['report_type_name'] = $this->getReportTypeName($reportDataType);
        }

        return $parsed;
    }

    /**
     * Parse the payload section of Dingtek packet
     *
     * Payload structure for trigger/heartbeat (0x01, 0x02):
     * - Height: 2 bytes (mm)
     * - GPS selection: 1 byte (0x01 = has GPS, 0x00 = no GPS)
     * - Longitude: 4 bytes (float, IEEE-754) [if GPS selection = 0x01]
     * - Latitude: 4 bytes (float, IEEE-754) [if GPS selection = 0x01]
     * - Temperature: 1 byte (℃)
     * - Status (Full/Fire/Power): 2 bytes
     * - Battery Voltage: 2 bytes (unit: 10mV)
     * - RSRP: 4 bytes (float, IEEE-754)
     * - Frame Count: 2 bytes
     * - Timestamp: 4 bytes (Unix time)
     * - Device ID: 8 bytes (1 + IMEI)
     */
    private function parsePayload(string $payload, int $reportType): ?array
    {
        $length = strlen($payload);

        Log::info("TCP: Parsing payload", [
            'length' => $length,
            'hex' => bin2hex($payload),
            'report_type' => $reportType
        ]);

        // For trigger report (0x01) and heartbeat (0x02)
        if ($reportType === 0x01 || $reportType === 0x02) {
            $offset = 0;
            $parsed = [];

            // Height (2 bytes, mm) - convert to meters
            if ($offset + 2 <= $length) {
                $height = unpack('n', substr($payload, $offset, 2))[1];
                $parsed['distance'] = $height / 1000; // Convert mm to meters
                $parsed['height_mm'] = $height;
                $offset += 2;
            }

            // GPS selection (1 byte)
            if ($offset + 1 <= $length) {
                $gpsSelection = ord($payload[$offset]);
                $parsed['has_gps'] = $gpsSelection === 0x01;
                $offset += 1;

                // If GPS is included, parse longitude and latitude
                if ($gpsSelection === 0x01 && $offset + 8 <= $length) {
                    // Longitude (4 bytes, float)
                    $longitude = unpack('f', substr($payload, $offset, 4))[1];
                    $parsed['longitude'] = $longitude;
                    $offset += 4;

                    // Latitude (4 bytes, float)
                    $latitude = unpack('f', substr($payload, $offset, 4))[1];
                    $parsed['latitude'] = $latitude;
                    $offset += 4;
                }
            }

            // Temperature (1 byte, ℃)
            if ($offset + 1 <= $length) {
                $parsed['temperature'] = ord($payload[$offset]);
                $offset += 1;
            }

            // Status (2 bytes: Full/Fire/Power alarms)
            if ($offset + 2 <= $length) {
                $status = unpack('n', substr($payload, $offset, 2))[1];
                $parsed['status_full'] = ($status & 0x01) ? 1 : 0;
                $parsed['status_fire'] = ($status & 0x02) ? 1 : 0;
                $parsed['status_power'] = ($status & 0x04) ? 1 : 0;
                $offset += 2;
            }

            // Battery Voltage (2 bytes, unit: 10mV) - convert to V
            if ($offset + 2 <= $length) {
                $voltage = unpack('n', substr($payload, $offset, 2))[1];
                $parsed['battery_level'] = ($voltage * 10) / 1000; // Convert to volts
                $parsed['battery_voltage_mv'] = $voltage * 10;
                $offset += 2;
            }

            // RSRP (4 bytes, float)
            if ($offset + 4 <= $length) {
                $parsed['rsrp'] = unpack('f', substr($payload, $offset, 4))[1];
                $offset += 4;
            }

            // Frame Count (2 bytes)
            if ($offset + 2 <= $length) {
                $parsed['frame_count'] = unpack('n', substr($payload, $offset, 2))[1];
                $offset += 2;
            }

            // Timestamp (4 bytes, Unix time)
            if ($offset + 4 <= $length) {
                $timestamp = unpack('N', substr($payload, $offset, 4))[1];
                $parsed['timestamp'] = $timestamp;
                $parsed['timestamp_readable'] = date('Y-m-d H:i:s', $timestamp);
                $offset += 4;
            }

            // Device ID (8 bytes, 1 + IMEI)
            if ($offset + 8 <= $length) {
                $deviceIdHex = bin2hex(substr($payload, $offset, 8));
                $parsed['device_id'] = $deviceIdHex;
                $offset += 8;
            }

            Log::info("TCP: Payload parsed successfully", $parsed);

            return !empty($parsed) ? $parsed : null;
        }

        // For other report types, log and return null
        Log::warning("TCP: Unsupported report type", ['report_type' => $reportType]);
        return null;
    }

    /**
     * Get human-readable report type name
     */
    private function getReportTypeName(int $reportType): string
    {
        return match($reportType) {
            0x01 => 'Trigger Report',
            0x02 => 'Heartbeat',
            0x03 => 'Command Reply',
            default => 'Unknown'
        };
    }

    /**
     * Forward parsed data to HTTP endpoint
     */
    private function forwardToHttpEndpoint(array $data)
    {
        try {
            $url = config('app.url') . '/api/sensors/dingtek/data';

            $response = Http::post($url, $data);

            if ($response->successful()) {
                Log::info("TCP: Data forwarded to HTTP endpoint successfully", ['device_id' => $data['device_id'] ?? 'unknown']);
            } else {
                Log::error("TCP: Failed to forward data to HTTP endpoint", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            Log::error("TCP: Exception forwarding to HTTP endpoint", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
