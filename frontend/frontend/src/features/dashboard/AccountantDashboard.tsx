import { KPICard } from '@/components/dashboard/KPICard'
import { SimpleLineChart, SimpleBarChart } from '@/components/dashboard/SimpleCharts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { QuickActions } from '@/components/QuickActions'
import { 
  DollarSign, 
  TrendingUp, 
  TrendingDown,
  CreditCard,
  Receipt,
  FileText
} from 'lucide-react'
import type { KPICardData, PaymentRow, ChequeRow } from '@/types/dashboard'

// Mock Data
const kpiData: KPICardData[] = [
  {
    label: 'إجمالي الإيرادات',
    value: '3.2M ج',
    change: { value: '+18% من الشهر الماضي', trend: 'up' },
    icon: TrendingUp,
    color: 'green',
  },
  {
    label: 'إجمالي المصروفات',
    value: '1.8M ج',
    change: { value: '+5% من الشهر الماضي', trend: 'up' },
    icon: TrendingDown,
    color: 'red',
  },
  {
    label: 'صافي الربح',
    value: '1.4M ج',
    change: { value: '+25% من الشهر الماضي', trend: 'up' },
    icon: DollarSign,
    color: 'blue',
  },
  {
    label: 'الشيكات المعلقة',
    value: '28',
    change: { value: '-12% من الشهر الماضي', trend: 'down' },
    icon: CreditCard,
    color: 'yellow',
  },
]

const revenueExpenseData = [
  { name: 'يناير', value: 280000, الإيرادات: 280000, المصروفات: 150000 },
  { name: 'فبراير', value: 320000, الإيرادات: 320000, المصروفات: 170000 },
  { name: 'مارس', value: 295000, الإيرادات: 295000, المصروفات: 160000 },
  { name: 'أبريل', value: 340000, الإيرادات: 340000, المصروفات: 180000 },
  { name: 'مايو', value: 380000, الإيرادات: 380000, المصروفات: 190000 },
  { name: 'يونيو', value: 410000, الإيرادات: 410000, المصروفات: 195000 },
]

const paymentMethodsData = [
  { name: 'نقدي', value: 650000 },
  { name: 'شيكات', value: 420000 },
  { name: 'تحويل بنكي', value: 280000 },
  { name: 'آجل', value: 150000 },
]

const pendingPayments: PaymentRow[] = [
  { id: 'PAY-001', customer: 'أحمد محمد', amount: 25000, dueDate: '2025-10-20', status: 'pending' },
  { id: 'PAY-002', customer: 'سارة علي', amount: 18500, dueDate: '2025-10-18', status: 'overdue' },
  { id: 'PAY-003', customer: 'محمود حسن', amount: 32000, dueDate: '2025-10-25', status: 'pending' },
  { id: 'PAY-004', customer: 'فاطمة أحمد', amount: 12500, dueDate: '2025-10-15', status: 'overdue' },
  { id: 'PAY-005', customer: 'خالد عمر', amount: 19200, dueDate: '2025-10-22', status: 'pending' },
]

const pendingCheques: ChequeRow[] = [
  { id: 'CHQ-001', customer: 'شركة النور', amount: 45000, chequeNumber: '123456', dueDate: '2025-10-25', status: 'pending' },
  { id: 'CHQ-002', customer: 'مؤسسة الأمل', amount: 28000, chequeNumber: '123457', dueDate: '2025-10-20', status: 'pending' },
  { id: 'CHQ-003', customer: 'شركة الفجر', amount: 62000, chequeNumber: '123458', dueDate: '2025-10-30', status: 'pending' },
  { id: 'CHQ-004', customer: 'مكتب السلام', amount: 18500, chequeNumber: '123459', dueDate: '2025-10-18', status: 'cleared' },
]

const statusColors = {
  pending: 'warning',
  completed: 'success',
  overdue: 'danger',
  cleared: 'success',
  bounced: 'danger',
} as const

const statusLabels = {
  pending: 'معلق',
  completed: 'مكتمل',
  overdue: 'متأخر',
  cleared: 'محصل',
  bounced: 'مرتد',
}

export function AccountantDashboard() {
  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <div>
        <h1 className="text-2xl md:text-3xl font-bold mb-2">لوحة تحكم المحاسب</h1>
        <p className="text-gray-600">إدارة الحسابات والمدفوعات والمالية</p>
      </div>

      {/* Quick Actions */}
      <QuickActions userRole="accounting" />

      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {kpiData.map((kpi, index) => (
          <KPICard key={index} data={kpi} />
        ))}
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <SimpleLineChart
          title="الإيرادات مقابل المصروفات"
          data={revenueExpenseData}
          dataKeys={['الإيرادات', 'المصروفات']}
          colors={['#10b981', '#ef4444']}
        />
        <SimpleBarChart
          title="طرق الدفع"
          data={paymentMethodsData}
          dataKey="value"
          color="bg-purple-500"
        />
      </div>

      {/* Tables Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Pending Payments */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="flex items-center gap-2">
              <Receipt className="w-5 h-5" />
              المدفوعات المعلقة
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {pendingPayments.map((payment) => (
                <div
                  key={payment.id}
                  className="flex items-center justify-between p-3 rounded-lg border hover:bg-gray-50 transition-colors"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      <span className="font-medium">{payment.id}</span>
                      <Badge variant={statusColors[payment.status]} size="sm">
                        {statusLabels[payment.status]}
                      </Badge>
                    </div>
                    <p className="text-sm text-gray-600">{payment.customer}</p>
                  </div>
                  <div className="text-left">
                    <p className="font-bold">{payment.amount.toLocaleString()} ج</p>
                    <p className="text-xs text-gray-500">{payment.dueDate}</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Pending Cheques */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="flex items-center gap-2">
              <FileText className="w-5 h-5" />
              الشيكات
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {pendingCheques.map((cheque) => (
                <div
                  key={cheque.id}
                  className="flex items-center justify-between p-3 rounded-lg border hover:bg-gray-50 transition-colors"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      <span className="font-medium">#{cheque.chequeNumber}</span>
                      <Badge variant={statusColors[cheque.status]} size="sm">
                        {statusLabels[cheque.status]}
                      </Badge>
                    </div>
                    <p className="text-sm text-gray-600">{cheque.customer}</p>
                  </div>
                  <div className="text-left">
                    <p className="font-bold">{cheque.amount.toLocaleString()} ج</p>
                    <p className="text-xs text-gray-500">{cheque.dueDate}</p>
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
