import { Card, CardContent } from '@/components/ui/card'
import { TrendingUp, TrendingDown, Minus } from 'lucide-react'
import { cn } from '@/lib/utils'
import type { KPICardData } from '@/types/dashboard'

interface KPICardProps {
  data: KPICardData
}

const colorClasses = {
  blue: 'bg-blue-100 text-blue-600',
  green: 'bg-green-100 text-green-600',
  yellow: 'bg-yellow-100 text-yellow-600',
  purple: 'bg-purple-100 text-purple-600',
  red: 'bg-red-100 text-red-600',
  gray: 'bg-gray-100 text-gray-600',
}

const trendClasses = {
  up: 'text-green-600',
  down: 'text-red-600',
  neutral: 'text-gray-600',
}

const TrendIcon = ({ trend }: { trend: 'up' | 'down' | 'neutral' }) => {
  const Icon = trend === 'up' ? TrendingUp : trend === 'down' ? TrendingDown : Minus
  return <Icon className="w-3 h-3" />
}

export function KPICard({ data }: KPICardProps) {
  const { label, value, change, icon: Icon, color } = data

  return (
    <Card>
      <CardContent className="pt-6">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm text-gray-500 mb-1">{label}</p>
            <p className="text-3xl font-bold">{value}</p>
            {change && (
              <p
                className={cn(
                  'text-xs flex items-center gap-1 mt-1',
                  trendClasses[change.trend]
                )}
              >
                <TrendIcon trend={change.trend} />
                {change.value}
              </p>
            )}
          </div>
          <div className={cn('p-3 rounded-lg', colorClasses[color])}>
            <Icon className="w-6 h-6" />
          </div>
        </div>
      </CardContent>
    </Card>
  )
}
