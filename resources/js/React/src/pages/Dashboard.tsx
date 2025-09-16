import { DashboardCard } from '../components/DashboardCard';
import { WaterLevelGauge } from '../components/WaterLevelGauge';
import { StatCard } from '../components/StatCard';
import { mockTanks, mockSubscriptions, mockDeliveries } from '../mockData';
import { DropletIcon, Users as UsersIcon, TruckIcon, BellIcon } from 'lucide-react';

function Dashboard() {
  return (
    <>
      <header className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Dashboard Overview</h1>
        <p className="text-gray-600">Monitor your water tank system in real-time</p>
      </header>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <StatCard
          title="Active Tanks"
          value={mockTanks.length}
          icon={DropletIcon}
          trend={{ value: 12, isPositive: true }}
        />
        <StatCard
          title="Active Subscriptions"
          value={mockSubscriptions.filter((s) => s.status === 'active').length}
          icon={UsersIcon}
          trend={{ value: 8, isPositive: true }}
        />
        <StatCard
          title="Pending Deliveries"
          value={mockDeliveries.filter((d) => d.status === 'pending').length}
          icon={TruckIcon}
        />
        <StatCard
          title="Active Alerts"
          value={2}
          icon={BellIcon}
          trend={{ value: 5, isPositive: false }}
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <DashboardCard title="Water Levels">
          <div className="space-y-6">
            {mockTanks.map((tank) => (
              <WaterLevelGauge key={tank.id} tank={tank} />
            ))}
          </div>
        </DashboardCard>

        <DashboardCard title="Recent Activity">
          <div className="space-y-4">
            {mockDeliveries.map((delivery) => (
              <div
                key={delivery.id}
                className="flex items-center justify-between py-2 border-b last:border-0"
              >
                <div>
                  <p className="font-medium">
                    Delivery to {mockTanks.find((t) => t.id === delivery.tankId)?.name}
                  </p>
                  <p className="text-sm text-gray-600">
                    {new Date(delivery.scheduledDate).toLocaleDateString()}
                  </p>
                </div>
                <span
                  className={`px-2 py-1 rounded-full text-sm ${
                    delivery.status === 'completed'
                      ? 'bg-green-100 text-green-800'
                      : delivery.status === 'ongoing'
                      ? 'bg-yellow-100 text-yellow-800'
                      : 'bg-gray-100 text-gray-800'
                  }`}
                >
                  {delivery.status}
                </span>
              </div>
            ))}
          </div>
        </DashboardCard>
      </div>
    </>
  );
}

export default Dashboard;
