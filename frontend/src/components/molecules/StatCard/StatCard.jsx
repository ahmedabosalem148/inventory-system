import { Card, Badge } from '../../atoms';
import { TrendingUp, TrendingDown } from 'lucide-react';

/**
 * StatCard - Dashboard KPI widget with trend indicator
 */
const StatCard = ({ 
  title, 
  value, 
  icon: Icon,
  trend,
  trendValue,
  color = 'primary',
  loading = false
}) => {
  const colorClasses = {
    primary: 'bg-blue-50 text-blue-600',
    success: 'bg-green-50 text-green-600',
    warning: 'bg-yellow-50 text-yellow-600',
    error: 'bg-red-50 text-red-600',
    info: 'bg-purple-50 text-purple-600',
  };

  const trendColorClasses = {
    up: 'text-green-600',
    down: 'text-red-600',
  };

  return (
    <Card hover className="relative overflow-hidden">
      {/* Background Pattern */}
      <div className="absolute top-0 left-0 w-32 h-32 opacity-10">
        <div className={`w-full h-full rounded-full blur-3xl ${(colorClasses[color] || colorClasses.primary).split(' ')[0]}`} />
      </div>

      <div className="relative space-y-3">
        {/* Icon & Title */}
        <div className="flex items-center justify-between">
          <div className={`w-12 h-12 rounded-lg ${colorClasses[color] || colorClasses.primary} flex items-center justify-center`}>
            {Icon && <Icon className="w-6 h-6" />}
          </div>
          
          {trend && (
            <div className={`flex items-center gap-1 text-sm font-medium ${trendColorClasses[trend]}`}>
              {trend === 'up' ? (
                <TrendingUp className="w-4 h-4" />
              ) : (
                <TrendingDown className="w-4 h-4" />
              )}
              <span>{trendValue}</span>
            </div>
          )}
        </div>

        {/* Value & Title */}
        <div className="space-y-1">
          <p className="text-sm text-gray-600 font-medium">{title}</p>
          {loading ? (
            <div className="h-8 bg-gray-200 rounded animate-pulse w-24" />
          ) : (
            <p className="text-3xl font-bold text-gray-900">{value}</p>
          )}
        </div>
      </div>
    </Card>
  );
};

export default StatCard;
