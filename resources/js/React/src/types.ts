export interface WaterTank {
  id: string;
  name: string;
  currentLevel: number;
  maxCapacity: number;
  location: {
    lat: number;
    lng: number;
  };
  sensor: {
    id: string;
    status: 'online' | 'offline';
    batteryVoltage: number;
    rsrp: number;
    lastUpdate: string;
  };
  alarms: {
    full: boolean;
    fire: boolean;
    lowBattery: boolean;
  };
}

export interface Subscription {
  id: string;
  userId: string;
  status: 'active' | 'expired' | 'pending';
  plan: 'basic' | 'premium' | 'enterprise';
  startDate: string;
  endDate: string;
  amount: number;
}

export interface Delivery {
  id: string;
  tankId: string;
  status: 'pending' | 'ongoing' | 'completed';
  scheduledDate: string;
  completedDate?: string;
  rating?: number;
  feedback?: string;
}