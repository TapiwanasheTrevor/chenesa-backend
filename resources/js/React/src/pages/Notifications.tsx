import { DashboardCard } from '../components/DashboardCard';
import { mockTanks } from '../mockData';
import { AlertTriangle, Battery, Droplet } from 'lucide-react';

function Notifications() {
  const alerts = [
    {
      id: 1,
      type: 'low_battery',
      tank: mockTanks[1],
      message: 'Low battery warning',
      timestamp: new Date().toISOString(),
      severity: 'warning',
      icon: Battery,
    },
    {
      id: 2,
      type: 'low_water',
      tank: mockTanks[0],
      message: 'Water level critical',
      timestamp: new Date(Date.now() - 3600000).toISOString(),
      severity: 'critical',
      icon: Droplet,
    },
    {
      id: 3,
      type: 'sensor_offline',
      tank: mockTanks[2],
      message: 'Sensor connection lost',
      timestamp: new Date(Date.now() - 7200000).toISOString(),
      severity: 'warning',
      icon: AlertTriangle,
    },
  ];

  return (
    <>
      <header className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Notifications</h1>
        <p className="text-gray-600">System alerts and notifications</p>
      </header>

      <DashboardCard title="Active Alerts">
        <div className="space-y-4">
          {alerts.map((alert) => (
            <div
              key={alert.id}
              className={`p-4 rounded-lg border ${
                alert.severity === 'critical'
                  ? 'bg-red-50 border-red-200'
                  : 'bg-yellow-50 border-yellow-200'
              }`}
            >
              <div className="flex items-start gap-4">
                <div className={`p-2 rounded-full ${
                  alert.severity === 'critical'
                    ? 'bg-red-100'
                    : 'bg-yellow-100'
                }`}>
                  <alert.icon size={20} className={
                    alert.severity === 'critical'
                      ? 'text-red-600'
                      : 'text-yellow-600'
                  } />
                </div>
                <div className="flex-1">
                  <h3 className="font-medium text-gray-900">{alert.tank.name}</h3>
                  <p className={`text-sm ${
                    alert.severity === 'critical'
                      ? 'text-red-600'
                      : 'text-yellow-600'
                  }`}>
                    {alert.message}
                  </p>
                  <p className="text-sm text-gray-500 mt-1">
                    {new Date(alert.timestamp).toLocaleString()}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </DashboardCard>
    </>
  );
}

export default Notifications;
