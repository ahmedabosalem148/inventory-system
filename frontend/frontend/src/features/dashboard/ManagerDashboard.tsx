import { KPICard } from '@/components/dashboard/KPICard'
import { SimpleBarChart, SimpleLineChart } from '@/components/dashboard/SimpleCharts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { 
  DollarSign, 
  Package, 
  Users, 
  TrendingUp,
  FileText,
  AlertTriangle 
} from 'lucide-react'
import type { KPICardData, InvoiceRow, ProductStockRow } from '@/types/dashboard'

// Mock Data
const kpiData: KPICardData[] = [
  {
    label: 'إجمالي المبيعات',
    value: '2.5M ج',
    change: { value: '+15% من الشهر الماضي', trend: 'up' },
    icon: DollarSign,
    color: 'green',
  },
  {
    label: 'إجمالي المنتجات',
    value: '2,450',
    change: { value: '+12% من الشهر الماضي', trend: 'up' },
    icon: Package,
    color: 'blue',
  },
  {
    label: 'العملاء النشطاء',
    value: '342',
    change: { value: '+8% من الشهر الماضي', trend: 'up' },
    icon: Users,
    color: 'purple',
  },
  {
    label: 'صافي الربح',
    value: '850K ج',
    change: { value: '+20% من الشهر الماضي', trend: 'up' },
    icon: TrendingUp,
    color: 'yellow',
  },
]

const monthlySalesData = [
  { name: 'يناير', value: 180000 },
  { name: 'فبراير', value: 220000 },
  { name: 'مارس', value: 195000 },
  { name: 'أبريل', value: 240000 },
  { name: 'مايو', value: 280000 },
  { name: 'يونيو', value: 310000 },
]

const topProductsData = [
  { name: 'منتج أ', value: 45000 },
  { name: 'منتج ب', value: 38000 },
  { name: 'منتج ج', value: 32000 },
  { name: 'منتج د', value: 28000 },
  { name: 'منتج هـ', value: 22000 },
]

const recentInvoices: InvoiceRow[] = [
  { id: 'INV-001', customer: 'أحمد محمد', amount: 15000, date: '2025-10-15', status: 'paid' },
  { id: 'INV-002', customer: 'سارة علي', amount: 8500, date: '2025-10-15', status: 'pending' },
  { id: 'INV-003', customer: 'محمود حسن', amount: 12000, date: '2025-10-14', status: 'paid' },
  { id: 'INV-004', customer: 'فاطمة أحمد', amount: 6500, date: '2025-10-14', status: 'overdue' },
  { id: 'INV-005', customer: 'خالد عمر', amount: 9200, date: '2025-10-13', status: 'paid' },
]

const lowStockProducts: ProductStockRow[] = [
  { id: '1', name: 'منتج أ', sku: 'SKU-001', currentStock: 5, minStock: 10, status: 'low' },
  { id: '2', name: 'منتج ب', sku: 'SKU-002', currentStock: 2, minStock: 10, status: 'critical' },
  { id: '3', name: 'منتج ج', sku: 'SKU-003', currentStock: 7, minStock: 15, status: 'low' },
  { id: '4', name: 'منتج د', sku: 'SKU-004', currentStock: 1, minStock: 10, status: 'critical' },
]

const statusColors = {
  paid: 'success',
  pending: 'warning',
  overdue: 'danger',
  low: 'warning',
  critical: 'danger',
  adequate: 'success',
} as const

const statusLabels = {
  paid: 'مدفوع',
  pending: 'معلق',
  overdue: 'متأخر',
  low: 'منخفض',
  critical: 'حرج',
  adequate: 'كافي',
}

export function ManagerDashboard() {
  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <div>
        <h1 className="text-2xl md:text-3xl font-bold mb-2">لوحة تحكم المدير</h1>
        <p className="text-gray-600">نظرة شاملة على أداء النظام</p>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {kpiData.map((kpi, index) => (
          <KPICard key={index} data={kpi} />
        ))}
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <SimpleLineChart
          title="المبيعات الشهرية"
          data={monthlySalesData}
          dataKeys={['value']}
          colors={['#10b981']}
        />
        <SimpleBarChart
          title="أفضل 5 منتجات مبيعاً"
          data={topProductsData}
          dataKey="value"
          color="bg-blue-500"
        />
      </div>

      {/* Tables Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Invoices */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="flex items-center gap-2">
              <FileText className="w-5 h-5" />
              آخر الفواتير
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {recentInvoices.map((invoice) => (
                <div
                  key={invoice.id}
                  className="flex items-center justify-between p-3 rounded-lg border hover:bg-gray-50 transition-colors"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      <span className="font-medium">{invoice.id}</span>
                      <Badge variant={statusColors[invoice.status]} size="sm">
                        {statusLabels[invoice.status]}
                      </Badge>
                    </div>
                    <p className="text-sm text-gray-600">{invoice.customer}</p>
                  </div>
                  <div className="text-left">
                    <p className="font-bold">{invoice.amount.toLocaleString()} ج</p>
                    <p className="text-xs text-gray-500">{invoice.date}</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

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
                      <span className="font-medium">{product.name}</span>
                      <Badge variant={statusColors[product.status]} size="sm">
                        {statusLabels[product.status]}
                      </Badge>
                    </div>
                    <p className="text-sm text-gray-600">{product.sku}</p>
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
      </div>
    </div>
  )
}
