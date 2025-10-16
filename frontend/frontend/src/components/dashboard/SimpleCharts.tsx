import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import type { ChartDataPoint } from '@/types/dashboard'

interface SimpleBarChartProps {
  title: string
  data: ChartDataPoint[]
  dataKey: string
  color?: string
}

export function SimpleBarChart({ 
  title, 
  data, 
  dataKey,
  color = 'bg-blue-500' 
}: SimpleBarChartProps) {
  const maxValue = Math.max(...data.map((d) => Number(d[dataKey])))

  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          {data.map((item, index) => {
            const value = Number(item[dataKey])
            const percentage = (value / maxValue) * 100

            return (
              <div key={index}>
                <div className="flex items-center justify-between mb-1">
                  <span className="text-sm font-medium">{item.name}</span>
                  <span className="text-sm text-gray-600">{value.toLocaleString()}</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2.5">
                  <div
                    className={`h-2.5 rounded-full ${color} transition-all duration-300`}
                    style={{ width: `${percentage}%` }}
                  />
                </div>
              </div>
            )
          })}
        </div>
      </CardContent>
    </Card>
  )
}

interface SimpleLineChartProps {
  title: string
  data: ChartDataPoint[]
  dataKeys: string[]
  colors?: string[]
}

export function SimpleLineChart({ 
  title, 
  data, 
  dataKeys,
  colors = ['#3b82f6', '#10b981'] 
}: SimpleLineChartProps) {
  const maxValue = Math.max(
    ...data.flatMap((d) => dataKeys.map((key) => Number(d[key])))
  )

  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent>
        {/* Legend */}
        <div className="flex gap-4 mb-4">
          {dataKeys.map((key, index) => (
            <div key={key} className="flex items-center gap-2">
              <div
                className="w-3 h-3 rounded-full"
                style={{ backgroundColor: colors[index] }}
              />
              <span className="text-sm">{key}</span>
            </div>
          ))}
        </div>

        {/* Chart */}
        <div className="h-48 flex items-end justify-between gap-2">
          {data.map((item, index) => (
            <div key={index} className="flex-1 flex flex-col items-center gap-2">
              <div className="relative w-full h-40 flex items-end justify-center gap-1">
                {dataKeys.map((key, keyIndex) => {
                  const value = Number(item[key])
                  const height = (value / maxValue) * 100

                  return (
                    <div
                      key={key}
                      className="w-full rounded-t transition-all duration-300"
                      style={{
                        height: `${height}%`,
                        backgroundColor: colors[keyIndex],
                      }}
                      title={`${key}: ${value.toLocaleString()}`}
                    />
                  )
                })}
              </div>
              <span className="text-xs text-gray-600">{item.name}</span>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  )
}
