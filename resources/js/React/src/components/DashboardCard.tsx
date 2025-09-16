import React from 'react';

interface DashboardCardProps {
  title: string;
  children: React.ReactNode;
  className?: string;
}

export const DashboardCard: React.FC<DashboardCardProps> = ({
  title,
  children,
  className = '',
}) => {
  return (
    <div className={`bg-white rounded-lg shadow-md p-4 ${className}`}>
      <h2 className="text-lg font-semibold mb-4">{title}</h2>
      {children}
    </div>
  );
};