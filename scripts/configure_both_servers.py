#!/usr/bin/env python3
"""
Configure both Server 1 and Server 2 for Dingtek DF555 sensor
Sends commands via serial TTL connection

IMPORTANT: Reset sensor with magnet before running!
"""

import serial
import serial.tools.list_ports
import time
import sys

# Configuration
SERVER_IP = "66.241.124.67"  # Fly.io IP address
SERVER_PORT = "8888"

def list_serial_ports():
    """List all available serial ports"""
    ports = serial.tools.list_ports.comports()
    print("\n" + "="*60)
    print("Available Serial Ports:")
    print("="*60)
    for i, port in enumerate(ports):
        print(f"{i+1}. {port.device} - {port.description}")
    print("="*60 + "\n")
    return ports

def build_server_config_command(server_num, ip_address, port):
    """
    Build downlink command for setting server address

    Command codes:
    - 06: Server 1 (IP1 and PORT1)
    - 07: Server 2 (IP2 and PORT2)

    Format: 80029999<CMD><IP;PORT;>81
    """
    command_code = "06" if server_num == 1 else "07"
    content = f"{ip_address};{port};"
    command = f"80029999{command_code}{content}81"
    return command

def send_command(ser, command, server_num):
    """Send command to sensor and wait for response"""
    print(f"\n{'='*60}")
    print(f"CONFIGURING SERVER {server_num}")
    print(f"{'='*60}")
    print(f"Command: {command}")
    print(f"Length: {len(command)} bytes")
    print(f"{'='*60}\n")

    # Clear buffers
    ser.reset_input_buffer()
    ser.reset_output_buffer()

    # Send command
    command_bytes = command.encode('ascii')
    bytes_written = ser.write(command_bytes)
    ser.flush()

    print(f"✓ Sent {bytes_written} bytes")
    print("Waiting for response...")

    # Wait for response
    time.sleep(2)

    response = b""
    while ser.in_waiting > 0:
        response += ser.read(ser.in_waiting)
        time.sleep(0.1)

    if response:
        try:
            response_str = response.decode('ascii', errors='ignore')
            print(f"\n✓ Response received:")
            print("-" * 60)
            print(response_str)
            print("-" * 60)
            return True
        except Exception as e:
            print(f"✗ Error decoding response: {e}")
            print(f"Raw response: {response.hex()}")
            return False
    else:
        print("⚠ No response received")
        return False

def main():
    print("\n" + "="*60)
    print("DINGTEK DF555 DUAL SERVER CONFIGURATION")
    print("="*60)
    print(f"Server IP: {SERVER_IP}")
    print(f"Server Port: {SERVER_PORT}")
    print("="*60)

    # List ports
    ports = list_serial_ports()
    if not ports:
        print("✗ No serial ports found!")
        sys.exit(1)

    # Select port
    auto_proceed = "--yes" in sys.argv
    if len(sys.argv) > 1 and sys.argv[1] != "--yes":
        selected_port = sys.argv[1]
    else:
        try:
            selection = input("Select port number: ").strip()
            selected_port = ports[int(selection)-1].device
        except (ValueError, IndexError):
            print("✗ Invalid selection")
            sys.exit(1)

    print(f"\nSelected port: {selected_port}")

    # Important warning
    print("\n" + "="*60)
    print("⚠ IMPORTANT: Sensor must be awake!")
    print("="*60)
    print("Before proceeding:")
    print("1. Ensure TTL tool is connected to sensor")
    print("2. Reset sensor with magnet (red LED lights up)")
    print("3. Sensor will report data immediately after reset")
    print("4. Run this command within 2-3 seconds of reset")
    print("="*60)

    if not auto_proceed:
        input("\nPress Enter when sensor is reset and ready...")
    else:
        print("\nAuto-proceeding in 2 seconds...")
        time.sleep(2)

    try:
        # Open serial connection
        print(f"\nOpening serial port...")
        ser = serial.Serial(
            port=selected_port,
            baudrate=115200,
            parity=serial.PARITY_NONE,
            stopbits=serial.STOPBITS_ONE,
            bytesize=serial.EIGHTBITS,
            timeout=10
        )

        print(f"✓ Port opened: {ser.name}")
        time.sleep(0.5)

        # Build commands for both servers
        server1_cmd = build_server_config_command(1, SERVER_IP, SERVER_PORT)
        server2_cmd = build_server_config_command(2, SERVER_IP, SERVER_PORT)

        # Send Server 1 configuration
        success1 = send_command(ser, server1_cmd, 1)

        # Wait between commands
        time.sleep(1)

        # Send Server 2 configuration
        success2 = send_command(ser, server2_cmd, 2)

        # Close connection
        ser.close()

        # Summary
        print("\n" + "="*60)
        print("CONFIGURATION SUMMARY")
        print("="*60)
        print(f"Server 1: {'✓ SUCCESS' if success1 else '✗ FAILED'}")
        print(f"Server 2: {'✓ SUCCESS' if success2 else '✗ FAILED'}")
        print(f"Target: {SERVER_IP}:{SERVER_PORT}")
        print("="*60)

        if success1 and success2:
            print("\n✓ Both servers configured successfully!")
            print("\nNext steps:")
            print("1. Sensor should connect within 1-10 minutes")
            print("2. Monitor logs: fly logs --app chenesa-shy-grass-3201")
            print("3. Look for 'TCP: New connection' messages")
        else:
            print("\n⚠ Configuration incomplete")
            print("\nTroubleshooting:")
            print("1. Verify sensor is awake (reset with magnet)")
            print("2. Check TTL connections (TX↔RX, GND↔GND)")
            print("3. Try running script immediately after reset")

    except serial.SerialException as e:
        print(f"\n✗ Serial port error: {e}")
        sys.exit(1)
    except KeyboardInterrupt:
        print("\n\n✗ Configuration cancelled by user")
        sys.exit(1)

if __name__ == "__main__":
    main()