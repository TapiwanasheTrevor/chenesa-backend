import React from 'react';
import {LucideIcon} from "lucide-react";

interface StatCardProps {
    title: string;
    value: string | number;
    icon: LucideIcon;
    trend?: {
        value: number;
        isPositive: boolean;
    };
}

export const StatCard: React.FC<StatCardProps> = ({title, value, icon: Icon, trend}) => {
    return (
        <div className="bg-white rounded-lg shadow-md p-4">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-gray-600">{title}</p>
                    <p className="text-2xl font-semibold mt-1">{value}</p>
                    {trend && (
                        <p className={`text-sm mt-1 ${trend.isPositive ? 'text-green-500' : 'text-red-500'}`}>
                            {trend.isPositive ? '↑' : '↓'} {Math.abs(trend.value)}%
                        </p>
                    )}
                </div>
                <Icon className="text-blue-500" size={24}/>
            </div>
        </div>
    );
};
