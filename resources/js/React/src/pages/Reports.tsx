import { DashboardCard } from '../components/DashboardCard';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import { mockTanks } from '../mockData';

function Reports() {
  // Generate mock data for the past 7 days
  const generateWaterLevelData = () => {
    const data = [];
    for (let i = 6; i >= 0; i--) {
      const date = new Date();
      date.setDate(date.getDate() - i);
      data.push({
        date: date.toLocaleDateString(),
        ...mockTanks.reduce((acc, tank) => ({
          ...acc,
          [tank.name]: Math.floor(Math.random() * (tank.maxCapacity * 0.5) + tank.maxCapacity * 0.3),
        }), {}),
      });
    }
    return data;
  };

  const waterLevelData = generateWaterLevelData();

  return (
    <>
      <header className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900">Reports</h1>
        <p className="text-gray-600">Analytics and historical data</p>
      </header>

      <DashboardCard title="Water Level Trends">
        <div className="h-[400px]">
          <ResponsiveContainer width="100%" height="100%">
            <LineChart data={waterLevelData}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="date" />
              <YAxis />
              <Tooltip />
              <Legend />
              {mockTanks.map((tank, index) => (
                <Line
                  key={tank.id}
                  type="monotone"
                  dataKey={tank.name}
                  stroke={`hsl(${index * 120}, 70%, 50%)`}
                  strokeWidth={2}
                />
              ))}
            </LineChart>
          </ResponsiveContainer>
        </div>
      </DashboardCard>
    </>
  );
}

export default Reports;
