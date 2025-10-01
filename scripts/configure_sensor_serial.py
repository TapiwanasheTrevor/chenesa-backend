#!/usr/bin/env python3
"""
Script to configure Dingtek DF555 sensor via serial TTL connection
Requires: pip install pyserial

Hardware Setup:
1. Connect TTL tool USB to Mac
2. Connect TTL GND to sensor GND
3. Connect TTL TX to sensor RX
4. Connect TTL RX to sensor TX
5. Reset sensor with magnet before running script
"""

import serial
import serial.tools.list_ports
import time
import sys

def list_serial_ports():
    """List all available serial ports"""
    ports = serial.tools.list_ports.comports()
    available_ports = []

    print("\n" + "="*60)
    print("Available Serial Ports:")
    print("="*60)

    for i, port in enumerate(ports):
        print(f"{i+1}. {port.device} - {port.description}")
        available_ports.append(port.device)

    print("="*60 + "\n")
    return available_ports

def build_server_config_command(server_address, port):
    """
    Build the downlink command for setting Server1 address

    Format: 80029999<COMMAND_CODE><IP;PORT;>81
    - 80: Packet head
    - 02: Command type (Configure device parameters)
    - 9999: Payload header (password)
    - 06: Command code (Setting server1 address)
    - Content: IP;PORT; (must have two semicolons!)
    - 81: Packet tail
    """
    content = f"{server_address};{port};"
    command = f"8002999906{content}81"  # 06 = Command code for Server1
    return command

def send_command_to_sensor(port_device, command, timeout=10):
    """
    Send configuration command to sensor via serial connection

    Serial Settings:
    - Baudrate: 115200
    - Parity: None
    - Data bits: 8
    - Stop bits: 1
    """
    try:
        # Open serial port
        print(f"\nOpening serial port: {port_device}")
        ser = serial.Serial(
            port=port_device,
            baudrate=115200,
            parity=serial.PARITY_NONE,
            stopbits=serial.STOPBITS_ONE,
            bytesize=serial.EIGHTBITS,
            timeout=timeout
        )

        print(f"Port opened successfully: {ser.name}")
        print(f"Settings: {ser.baudrate} baud, {ser.bytesize} data bits, {ser.stopbits} stop bit(s), {ser.parity} parity")

        # Wait a moment for port to stabilize
        time.sleep(0.5)

        # Clear any existing data in buffer
        ser.reset_input_buffer()
        ser.reset_output_buffer()

        # Encode command to bytes (ASCII)
        command_bytes = command.encode('ascii')

        print(f"\n{'='*60}")
        print("SENDING COMMAND TO SENSOR")
        print(f"{'='*60}")
        print(f"Command (ASCII): {command}")
        print(f"Command (Bytes): {command_bytes.hex()}")
        print(f"Command (Length): {len(command_bytes)} bytes")
        print(f"{'='*60}\n")

        # Send command
        bytes_written = ser.write(command_bytes)
        ser.flush()  # Ensure all data is sent

        print(f"✓ Sent {bytes_written} bytes to sensor")
        print("\nWaiting for sensor response...")

        # Wait for response
        time.sleep(2)

        # Read response
        response_data = b''
        while ser.in_waiting > 0:
            response_data += ser.read(ser.in_waiting)
            time.sleep(0.1)

        if response_data:
            print(f"\n{'='*60}")
            print("SENSOR RESPONSE RECEIVED")
            print(f"{'='*60}")
            print(f"Response (Raw bytes): {response_data.hex()}")
            print(f"Response (ASCII): {response_data.decode('ascii', errors='ignore')}")
            print(f"Response (Length): {len(response_data)} bytes")
            print(f"{'='*60}\n")

            # Check if configuration was successful
            if b'Server1' in response_data or b'OK' in response_data:
                print("✓ Configuration appears successful!")
            else:
                print("⚠ Unexpected response - please verify sensor configuration")
        else:
            print("⚠ No response received from sensor")
            print("  This could mean:")
            print("  - Sensor is in sleep mode (reset with magnet)")
            print("  - Wrong serial port selected")
            print("  - Incorrect wiring (check TX/RX and GND connections)")

        # Close port
        ser.close()
        print("\nSerial port closed")

        return response_data

    except serial.SerialException as e:
        print(f"\n✗ Serial port error: {e}")
        print("\nTroubleshooting:")
        print("- Ensure TTL tool is connected to Mac")
        print("- Check that no other program is using the serial port")
        print("- Verify port permissions (may need sudo)")
        return None
    except Exception as e:
        print(f"\n✗ Unexpected error: {e}")
        return None

def main():
    print("\n" + "="*60)
    print("DINGTEK DF555 SERIAL CONFIGURATION TOOL")
    print("="*60)

    # Configuration
    server = "chenesa-shy-grass-3201.fly.dev"
    port = "8888"

    print(f"\nTarget Configuration:")
    print(f"  Server: {server}")
    print(f"  Port: {port}")

    # List available ports
    available_ports = list_serial_ports()

    if not available_ports:
        print("✗ No serial ports found!")
        print("\nPlease connect your TTL tool and try again.")
        sys.exit(1)

    # Get port selection
    if len(sys.argv) > 1:
        # Port provided as command line argument
        port_device = sys.argv[1]
    else:
        # Interactive selection
        try:
            selection = input("Select port number (or enter full device path): ").strip()
            if selection.isdigit():
                idx = int(selection) - 1
                if 0 <= idx < len(available_ports):
                    port_device = available_ports[idx]
                else:
                    print("Invalid selection")
                    sys.exit(1)
            else:
                port_device = selection
        except KeyboardInterrupt:
            print("\n\nCancelled by user")
            sys.exit(0)

    print(f"\nSelected port: {port_device}")

    # Build command
    command = build_server_config_command(server, port)

    # Confirm before sending
    print("\n" + "="*60)
    print("IMPORTANT: Sensor must be awake to receive commands!")
    print("="*60)
    print("Before proceeding:")
    print("1. Ensure TTL tool is properly connected to sensor")
    print("2. Reset sensor with magnet (red LED should light up)")
    print("3. Run this command within 2-3 seconds of reset")
    print("="*60)

    # Check for --yes flag to skip confirmation
    if '--yes' not in sys.argv:
        try:
            proceed = input("\nReady to send command? (yes/no): ").strip().lower()
            if proceed not in ['yes', 'y']:
                print("Cancelled")
                sys.exit(0)
        except EOFError:
            print("\n\n⚠ Running in non-interactive mode. Use --yes flag to proceed automatically.")
            sys.exit(1)
    else:
        print("\nProceeding automatically (--yes flag detected)...")

    # Send command
    response = send_command_to_sensor(port_device, command)

    if response:
        print("\n✓ Command sent successfully!")
        print("\nNext steps:")
        print("1. Sensor will use new configuration on next wake cycle")
        print("2. Monitor logs: fly logs --app chenesa-shy-grass-3201 | grep 'TCP:'")
        print("3. You should see TCP connection within 10 minutes")
    else:
        print("\n⚠ Command may not have been received")
        print("Please verify hardware connections and try again")

if __name__ == "__main__":
    main()
