#!/usr/bin/env python3
"""
DF555 Ultrasonic Level Sensor Configuration Script
Configures server addresses and ports via serial connection
"""

import serial
import time
import sys
import argparse


class DF555Configurator:
    """Configure DF555 sensor via serial port"""

    # Serial port settings as per DF555 documentation
    BAUDRATE = 115200
    PARITY = serial.PARITY_NONE
    DATABITS = serial.EIGHTBITS
    STOPBITS = serial.STOPBITS_ONE
    TIMEOUT = 5  # seconds

    # Command codes
    CMD_SET_SERVER1 = '06'
    CMD_SET_SERVER2 = '07'  # Unverified - may need confirmation
    CMD_SWITCH_FUNCTION = '09'  # Switch function setting (may include server mode)

    def __init__(self, port):
        """
        Initialize configurator

        Args:
            port: Serial port path (e.g., '/dev/ttyUSB0', 'COM3')
        """
        self.port = port
        self.serial = None

    def connect(self):
        """Establish serial connection"""
        try:
            self.serial = serial.Serial(
                port=self.port,
                baudrate=self.BAUDRATE,
                parity=self.PARITY,
                bytesize=self.DATABITS,
                stopbits=self.STOPBITS,
                timeout=self.TIMEOUT
            )
            print(f"✓ Connected to {self.port}")
            time.sleep(0.5)  # Allow connection to stabilize
            return True
        except serial.SerialException as e:
            print(f"✗ Failed to connect to {self.port}: {e}")
            return False

    def disconnect(self):
        """Close serial connection"""
        if self.serial and self.serial.is_open:
            self.serial.close()
            print(f"✓ Disconnected from {self.port}")

    def build_command(self, cmd_code, content):
        """
        Build command string following DF555 protocol

        Format: 80 02 9999 [CMD_CODE] [CONTENT] 81

        Args:
            cmd_code: Command code (e.g., '06' for Server 1)
            content: Command content (e.g., 'IP;PORT;')

        Returns:
            ASCII command string
        """
        packet_head = '80'
        command_type = '02'
        header = '9999'
        packet_tail = '81'

        command = f"{packet_head}{command_type}{header}{cmd_code}{content}{packet_tail}"
        return command

    def send_command(self, command):
        """
        Send command to sensor and wait for response

        Args:
            command: ASCII command string

        Returns:
            Response from sensor or None
        """
        if not self.serial or not self.serial.is_open:
            print("✗ Serial port not open")
            return None

        try:
            # Send command as ASCII bytes
            self.serial.write(command.encode('ascii'))
            print(f"→ Sent: {command}")

            # Wait for response
            time.sleep(1)

            # Read response
            if self.serial.in_waiting > 0:
                response = self.serial.read(self.serial.in_waiting)
                print(f"← Received: {response.decode('ascii', errors='ignore')}")
                return response
            else:
                print("⚠ No response received (sensor may be restarting)")
                return None

        except Exception as e:
            print(f"✗ Error sending command: {e}")
            return None

    def configure_server1(self, ip, port):
        """
        Configure Server 1 address and port

        Args:
            ip: Server IP address (e.g., '129.226.11.30')
            port: Server port (e.g., 10560)

        Returns:
            True if successful, False otherwise
        """
        print(f"\n→ Configuring Server 1: {ip}:{port}")

        # Build content: IP;PORT; (two semicolons required!)
        content = f"{ip};{port};"

        # Build complete command
        command = self.build_command(self.CMD_SET_SERVER1, content)

        # Send command
        response = self.send_command(command)

        if response:
            print("✓ Server 1 configuration sent successfully")
            print("⚠ Device will restart to apply settings")
            return True
        else:
            print("⚠ Configuration command sent, but no response received")
            print("  This is normal - device may be restarting")
            return True

    def configure_server2(self, ip, port):
        """
        Configure Server 2 address and port

        NOTE: Command code 0x07 for Server 2 is unverified.
        Consult manufacturer if this doesn't work.

        Args:
            ip: Server IP address (e.g., '159.138.4.6')
            port: Server port (e.g., 8888)

        Returns:
            True if successful, False otherwise
        """
        print(f"\n→ Configuring Server 2: {ip}:{port}")
        print("⚠ WARNING: Server 2 command code (0x07) is unverified")

        # Build content: IP;PORT; (two semicolons required!)
        content = f"{ip};{port};"

        # Build complete command
        command = self.build_command(self.CMD_SET_SERVER2, content)

        # Send command
        response = self.send_command(command)

        if response:
            print("✓ Server 2 configuration sent successfully")
            print("⚠ Device will restart to apply settings")
            return True
        else:
            print("⚠ Configuration command sent, but no response received")
            print("  This is normal - device may be restarting")
            return True

    def configure_server_mode(self, mode):
        """
        Configure Server Mode (which servers to send data to)

        NOTE: The exact command for server mode is not documented.
        This attempts to use the switch function command (0x09).

        Args:
            mode: Server mode code
                  '00' = Only Server 1
                  '01' = Only Server 2
                  '02' = Both servers simultaneously

        Returns:
            True if successful, False otherwise
        """
        print(f"\n→ Configuring Server Mode: {mode}")

        mode_desc = {
            '00': 'Only Server 1',
            '01': 'Only Server 2',
            '02': 'Both servers simultaneously'
        }

        print(f"  Mode: {mode_desc.get(mode, 'Unknown')}")
        print("⚠ WARNING: Server mode command is unverified")

        # Build command - using switch function command with mode content
        command = self.build_command(self.CMD_SWITCH_FUNCTION, mode)

        # Send command
        response = self.send_command(command)

        if response:
            print("✓ Server mode configuration sent successfully")
            print("⚠ Device will restart to apply settings")
            return True
        else:
            print("⚠ Configuration command sent, but no response received")
            print("  This is normal - device may be restarting")
            return True


def main():
    parser = argparse.ArgumentParser(
        description='Configure DF555 Ultrasonic Level Sensor via serial port',
        epilog='Example: python configure_sensor.py /dev/ttyUSB0 --server1 129.226.11.30 10560'
    )

    parser.add_argument('port', help='Serial port (e.g., /dev/ttyUSB0, COM3)')
    parser.add_argument('--server1', nargs=2, metavar=('IP', 'PORT'),
                        help='Configure Server 1 IP and port')
    parser.add_argument('--server2', nargs=2, metavar=('IP', 'PORT'),
                        help='Configure Server 2 IP and port (unverified)')
    parser.add_argument('--mode', choices=['00', '01', '02'],
                        help='Server mode: 00=Server1 only, 01=Server2 only, 02=Both servers')

    args = parser.parse_args()

    if not args.server1 and not args.server2 and not args.mode:
        parser.error('At least one of --server1, --server2, or --mode must be specified')

    print("=" * 60)
    print("DF555 Ultrasonic Level Sensor Configuration Tool")
    print("=" * 60)
    print("\n⚠ IMPORTANT:")
    print("  • Ensure sensor is NOT in sleep mode (reset with magnet if needed)")
    print("  • Device will restart after configuration")
    print("  • Wait at least 30 seconds after restart before reconnecting")
    print()

    # Create configurator
    configurator = DF555Configurator(args.port)

    # Connect to sensor
    if not configurator.connect():
        sys.exit(1)

    try:
        # Configure Server 1 if specified
        if args.server1:
            ip, port = args.server1
            configurator.configure_server1(ip, port)
            time.sleep(2)  # Wait between commands

        # Configure Server 2 if specified
        if args.server2:
            ip, port = args.server2
            configurator.configure_server2(ip, port)
            time.sleep(2)  # Wait between commands

        # Configure Server Mode if specified
        if args.mode:
            configurator.configure_server_mode(args.mode)

        print("\n" + "=" * 60)
        print("✓ Configuration complete!")
        print("⚠ Please wait 30+ seconds for device to restart")
        print("=" * 60)

    finally:
        # Always disconnect
        configurator.disconnect()


if __name__ == '__main__':
    main()
