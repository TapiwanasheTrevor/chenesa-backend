#!/usr/bin/env python3
"""
Script to update Dingtek DF555 sensor configuration via SMS
Sends command to configure sensor to send data to TCP server
"""

import sys

def generate_config_command(phone_number, server, port):
    """
    Generate sensor configuration command

    Format: 8002<password><server>;<port>;81
    - 8002: Command prefix
    - 999906: Default password (6 digits)
    - server: Domain/IP to send data to
    - port: TCP port number
    - 81: Command suffix
    """
    password = "999906"  # Default Dingtek password
    command = f"8002{password}{server};{port};81"
    return command

def main():
    # Configuration
    if len(sys.argv) > 1:
        sensor_phone = sys.argv[1]
    else:
        print("Usage: python3 update_sensor_config.py <sensor_phone_number>")
        print("Example: python3 update_sensor_config.py +254712345678")
        sys.exit(1)

    server = "chenesa-shy-grass-3201.fly.dev"
    port = "8888"

    # Generate command
    command = generate_config_command(sensor_phone, server, port)

    print("\n" + "="*60)
    print("SENSOR CONFIGURATION COMMAND")
    print("="*60)
    print(f"Sensor Phone: {sensor_phone}")
    print(f"Server: {server}")
    print(f"Port: {port}")
    print(f"\nSMS Command to send:")
    print("-"*60)
    print(command)
    print("-"*60)
    print("\nInstructions:")
    print("1. Send the above command as SMS to the sensor phone number")
    print("2. The sensor should reply with a confirmation message")
    print("3. Wait 1-2 minutes for sensor to apply configuration")
    print("4. Monitor logs: fly logs --app chenesa-shy-grass-3201 | grep 'TCP:'")
    print("\nAlternative Commands:")
    print("-"*60)
    print(f"Query current config: 8001{999906}81")
    print(f"Reset to defaults: 8000{999906}81")
    print("="*60)

if __name__ == "__main__":
    main()