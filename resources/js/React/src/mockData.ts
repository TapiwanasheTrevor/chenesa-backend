import {WaterTank, Subscription, Delivery} from './types';
import {addDays, subDays} from 'date-fns';

export const mockTanks: WaterTank[] = [
    {
        id: 'tank1',
        name: 'Maposhere Homestead',
        currentLevel: 75,
        maxCapacity: 1000,
        location: {lat: -24.6282, lng: 25.9231}, // Gaborone coordinates
        sensor: {
            id: 'CNS-001',
            status: 'online',
            batteryVoltage: 3.8,
            rsrp: -85,
            lastUpdate: new Date().toISOString(),
        },
        alarms: {
            full: false,
            fire: false,
            lowBattery: false,
        },
    },
    {
        id: 'tank2',
        name: 'Kanzama Residence',
        currentLevel: 45,
        maxCapacity: 800,
        location: {lat: -24.6282, lng: 25.9231},
        sensor: {
            id: 'CNS-002',
            status: 'online',
            batteryVoltage: 3.2,
            rsrp: -92,
            lastUpdate: new Date().toISOString(),
        },
        alarms: {
            full: false,
            fire: false,
            lowBattery: true,
        },
    },
    {
        id: 'tank3',
        name: 'Chikengezha Homestead',
        currentLevel: 85,
        maxCapacity: 1200,
        location: {lat: -24.6282, lng: 25.9231},
        sensor: {
            id: 'CNS-003',
            status: 'online',
            batteryVoltage: 3.9,
            rsrp: -78,
            lastUpdate: new Date().toISOString(),
        },
        alarms: {
            full: false,
            fire: false,
            lowBattery: false,
        },
    },
];

export const mockSubscriptions: Subscription[] = [
    {
        id: 'sub1',
        userId: 'user1',
        status: 'active',
        plan: 'premium',
        startDate: subDays(new Date(), 30).toISOString(),
        endDate: addDays(new Date(), 335).toISOString(),
        amount: 299.99,
    },
    {
        id: 'sub2',
        userId: 'user2',
        status: 'active',
        plan: 'basic',
        startDate: subDays(new Date(), 60).toISOString(),
        endDate: addDays(new Date(), 305).toISOString(),
        amount: 99.99,
    },
    {
        id: 'sub3',
        userId: 'user3',
        status: 'pending',
        plan: 'premium',
        startDate: subDays(new Date(), 15).toISOString(),
        endDate: addDays(new Date(), 350).toISOString(),
        amount: 299.99,
    },
];

export const mockDeliveries: Delivery[] = [
    {
        id: 'del1',
        tankId: 'tank1',
        status: 'ongoing',
        scheduledDate: new Date().toISOString(),
    },
    {
        id: 'del2',
        tankId: 'tank2',
        status: 'completed',
        scheduledDate: subDays(new Date(), 1).toISOString(),
        completedDate: new Date().toISOString(),
        rating: 5,
        feedback: 'Excellent service, very professional delivery team',
    },
    {
        id: 'del3',
        tankId: 'tank3',
        status: 'pending',
        scheduledDate: addDays(new Date(), 1).toISOString(),
    },
];
