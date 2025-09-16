import React from 'react';
import { Battery, Wifi } from 'lucide-react';

interface WaterLevelGaugeProps {
  tank: {
    name: string;
    currentLevel: number;
    maxCapacity: number;
    sensor: {
      status: 'online' | 'offline';
      batteryVoltage: number;
      rsrp: number;
    };
  };
}

export const WaterLevelGauge: React.FC<WaterLevelGaugeProps> = ({ tank }) => {
  const percentage = (tank.currentLevel / tank.maxCapacity) * 100;
  const getBatteryColor = (voltage: number) => {
    if (voltage >= 3.7) return 'text-green-500';
    if (voltage >= 3.3) return 'text-yellow-500';
    return 'text-red-500';
  };

  const getSignalColor = (rsrp: number) => {
    if (rsrp >= -85) return 'text-green-500';
    if (rsrp >= -95) return 'text-yellow-500';
    return 'text-red-500';
  };

  return (
    <div className="flex flex-col space-y-2">
      <div className="flex justify-between items-center">
        <span className="font-medium">{tank.name}</span>
        <div className="flex items-center space-x-2">
          <Battery className={getBatteryColor(tank.sensor.batteryVoltage)} size={16} />
          <Wifi className={getSignalColor(tank.sensor.rsrp)} size={16} />
          <span className={tank.sensor.status === 'online' ? 'text-green-500' : 'text-red-500'}>
            •
          </span>
        </div>
      </div>
      <div className="relative h-4 bg-gray-200 rounded-full overflow-hidden">
        <div
          className="absolute h-full bg-blue-500 transition-all duration-500"
          style={{ width: `${percentage}%` }}
        />
      </div>
      <div className="flex justify-between text-sm text-gray-600">
        <span>{tank.currentLevel}mm</span>
        <span>{percentage.toFixed(1)}%</span>
      </div>
    </div>
  );
};