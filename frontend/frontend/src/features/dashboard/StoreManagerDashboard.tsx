import { KPICard } from '@/components/dashboard/KPICard'
import { SimpleBarChart, SimpleLineChart } from '@/components/dashboard/SimpleCharts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { QuickActions } from '@/components/QuickActions'
import { 
  Package, 
  TrendingUp,
  AlertTriangle,
  BarChart3,
  ArrowDown,
  ArrowUp
} from 'lucide-react'
import type { KPICardData, ProductStockRow, StockMovementRow } from '@/types/dashboard'

// Mock Data
const kpiData: KPICardData[] = [
  {
    label: 'إجمالي المنتجات',
    value: '2,450',
    change: { value: '+12% من الشهر الماضي', trend: 'up' },
    icon: Package,
    color: 'blue',
  },
  {
    label: 'منتجات ناقصة',
    value: '45',
    change: { value: '+5 من الأسبوع الماضي', trend: 'up' },
    icon: AlertTriangle,
    color: 'yellow',
  },
  {
    label: 'منتجات راكدة',
    value: '12',
    change: { value: '-3 من الشهر الماضي', trend: 'down' },
    icon: TrendingUp,
    color: 'gray',
  },
  {
    label: 'حركات اليوم',
    value: '128',
    change: { value: '+18 من الأمس', trend: 'up' },
    icon: BarChart3,
    color: 'green',
  },
]

const stockMovementData = [
  { name: 'الأحد', value: 85 },
  { name: 'الاثنين', value: 92 },
  { name: 'الثلاثاء', value: 78 },
  { name: 'الأربعاء', value: 105 },
  { name: 'الخميس', value: 115 },
  { name: 'الجمعة', value: 65 },
  { name: 'السبت', value: 48 },
]

const categoryStockData = [
  { name: 'إلكترونيات', value: 450 },
  { name: 'أجهزة منزلية', value: 320 },
  { name: 'ملابس', value: 280 },
  { name: 'أحذية', value: 195 },
  { name: 'مستحضرات', value: 145 },
]

const lowStockProducts: ProductStockRow[] = [
  { id: '1', name: 'منتج أ - لاب توب HP', sku: 'ELEC-001', currentStock: 3, minStock: 10, status: 'critical' },
  { id: '2', name: 'منتج ب - ثلاجة سامسونج', sku: 'HOME-002', currentStock: 5, minStock: 10, status: 'low' },
  { id: '3', name: 'منتج ج - تلفزيون LG', sku: 'ELEC-003', currentStock: 7, minStock: 15, status: 'low' },
  { id: '4', name: 'منتج د - غسالة توشيبا', sku: 'HOME-004', currentStock: 2, minStock: 10, status: 'critical' },
  { id: '5', name: 'منتج هـ - مكيف كاريير', sku: 'HOME-005', currentStock: 4, minStock: 10, status: 'low' },
]

const recentMovements: StockMovementRow[] = [
  { id: '1', product: 'لاب توب HP', type: 'out', quantity: 5, date: '2025-10-16 14:30', reference: 'INV-001' },
  { id: '2', product: 'ثلاجة سامسونج', type: 'in', quantity: 10, date: '2025-10-16 12:15', reference: 'PO-045' },
  { id: '3', product: 'تلفزيون LG', type: 'out', quantity: 3, date: '2025-10-16 11:20', reference: 'INV-002' },
  { id: '4', product: 'غسالة توشيبا', type: 'in', quantity: 8, date: '2025-10-16 09:45', reference: 'PO-046' },
  { id: '5', product: 'مكيف كاريير', type: 'out', quantity: 2, date: '2025-10-16 08:30', reference: 'INV-003' },
]

const statusColors = {
  low: 'warning',
  critical: 'danger',
  adequate: 'success',
  in: 'success',
  out: 'info',
} as const

const statusLabels = {
  low: 'منخفض',
  critical: 'حرج',
  adequate: 'كافي',
  in: 'وارد',
  out: 'صادر',
}

export function StoreManagerDashboard() {
  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <div>
        <h1 className="text-2xl md:text-3xl font-bold mb-2">لوحة تحكم أمين المخزن</h1>
        <p className="text-gray-600">إدارة المخزون والحركات اليومية</p>
      </div>

      {/* Quick Actions */}
      <QuickActions userRole="store_user" />

      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {kpiData.map((kpi, index) => (
          <KPICard key={index} data={kpi} />
        ))}
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <SimpleLineChart
          title="حركة المخزون الأسبوعية"
          data={stockMovementData}
          dataKeys={['value']}
          colors={['#3b82f6']}
        />
        <SimpleBarChart
          title="المخزون حسب الفئة"
          data={categoryStockData}
          dataKey="value"
          color="bg-green-500"
        />
      </div>

      {/* Tables Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Low Stock Products */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="flex items-center gap-2">
              <AlertTriangle className="w-5 h-5 text-yellow-600" />
              المنتجات الناقصة
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {lowStockProducts.map((product) => (
                <div
                  key={product.id}
                  className="flex items-center justify-between p-3 rounded-lg border hover:bg-gray-50 transition-colors"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      <span className="font-medium text-sm">{product.name}</span>
                      <Badge variant={statusColors[product.status]} size="sm">
                        {statusLabels[product.status]}
                      </Badge>
                    </div>
                    <p className="text-xs text-gray-600">{product.sku}</p>
                  </div>
                  <div className="text-left">
                    <p className="font-bold">
                      {product.currentStock} / {product.minStock}
                    </p>
                    <p className="text-xs text-gray-500">الحالي / الأدنى</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Recent Movements */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="flex items-center gap-2">
              <BarChart3 className="w-5 h-5" />
              آخر الحركات
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {recentMovements.map((movement) => (
                <div
                  key={movement.id}
                  className="flex items-center justify-between p-3 rounded-lg border hover:bg-gray-50 transition-colors"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      {movement.type === 'in' ? (
                        <ArrowDown className="w-4 h-4 text-green-600" />
                      ) : (
                        <ArrowUp className="w-4 h-4 text-blue-600" />
                      )}
                      <span className="font-medium text-sm">{movement.product}</span>
                    </div>
                    <p className="text-xs text-gray-600">{movement.reference}</p>
                  </div>
                  <div className="text-left">
                    <p className="font-bold flex items-center gap-1">
                      {movement.type === 'in' ? '+' : '-'}
                      {movement.quantity}
                    </p>
                    <p className="text-xs text-gray-500">{movement.date}</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
