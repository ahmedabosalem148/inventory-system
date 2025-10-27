/**
 * Reports Page
 * Main reports dashboard with links to all report types
 */

import { FileText, TrendingDown, Users, DollarSign, Package, BarChart3, FileBarChart } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'

interface ReportCard {
  id: string
  title: string
  description: string
  icon: React.ReactNode
  path: string
  color: string
}

export function ReportsPage() {
  const handleNavigate = (path: string) => {
    // Use hash navigation instead of React Router
    window.location.hash = path.replace('/reports/', 'reports/')
  }

  const inventoryReports: ReportCard[] = [
    {
      id: 'stock-summary',
      title: 'تقرير إجمالي المخزون',
      description: 'عرض المخزون الحالي لجميع المنتجات في جميع الفروع',
      icon: <Package className="w-6 h-6" />,
      path: '/reports/stock-summary',
      color: 'blue',
    },
    {
      id: 'low-stock',
      title: 'تقرير منخفض المخزون',
      description: 'المنتجات التي وصلت أو قاربت الحد الأدنى',
      icon: <TrendingDown className="w-6 h-6" />,
      path: '/reports/low-stock',
      color: 'red',
    },
    {
      id: 'product-movements',
      title: 'تقرير حركة صنف',
      description: 'تتبع حركات منتج محدد (صرف، إرجاع، تحويل)',
      icon: <BarChart3 className="w-6 h-6" />,
      path: '/reports/product-movements',
      color: 'purple',
    },
    {
      id: 'stock-valuation',
      title: 'تقرير تقييم المخزون',
      description: 'القيمة المالية للمخزون حسب أسعار الشراء',
      icon: <DollarSign className="w-6 h-6" />,
      path: '/reports/stock-valuation',
      color: 'green',
    },
  ]

  const customerReports: ReportCard[] = [
    {
      id: 'customer-balances',
      title: 'تقرير أرصدة العملاء',
      description: 'عرض أرصدة جميع العملاء والمديونيات',
      icon: <Users className="w-6 h-6" />,
      path: '/reports/customer-balances',
      color: 'indigo',
    },
    {
      id: 'customer-statement',
      title: 'كشف حساب عميل',
      description: 'تفاصيل حساب عميل محدد مع الرصيد الجاري',
      icon: <FileText className="w-6 h-6" />,
      path: '/reports/customer-statement',
      color: 'cyan',
    },
  ]

  const salesReports: ReportCard[] = [
    {
      id: 'sales-summary',
      title: 'تقرير المبيعات',
      description: 'ملخص المبيعات خلال فترة زمنية محددة',
      icon: <FileBarChart className="w-6 h-6" />,
      path: '/reports/sales-summary',
      color: 'orange',
    },
  ]

  const getColorClasses = (color: string) => {
    const colors: Record<string, { bg: string; hover: string; text: string }> = {
      blue: { bg: 'bg-blue-50', hover: 'hover:bg-blue-100', text: 'text-blue-600' },
      red: { bg: 'bg-red-50', hover: 'hover:bg-red-100', text: 'text-red-600' },
      purple: { bg: 'bg-purple-50', hover: 'hover:bg-purple-100', text: 'text-purple-600' },
      green: { bg: 'bg-green-50', hover: 'hover:bg-green-100', text: 'text-green-600' },
      indigo: { bg: 'bg-indigo-50', hover: 'hover:bg-indigo-100', text: 'text-indigo-600' },
      cyan: { bg: 'bg-cyan-50', hover: 'hover:bg-cyan-100', text: 'text-cyan-600' },
      orange: { bg: 'bg-orange-50', hover: 'hover:bg-orange-100', text: 'text-orange-600' },
    }
    return colors[color] || colors.blue
  }

  const renderReportCard = (report: ReportCard) => {
    const colors = getColorClasses(report.color)
    
    return (
      <Card
        key={report.id}
        className={`cursor-pointer transition-all duration-200 hover:shadow-lg ${colors.hover}`}
        onClick={() => handleNavigate(report.path)}
      >
        <CardContent className="p-6">
          <div className="flex items-start gap-4">
            <div className={`p-3 rounded-lg ${colors.bg}`}>
              <div className={colors.text}>{report.icon}</div>
            </div>
            <div className="flex-1">
              <h3 className="font-semibold text-lg mb-1">{report.title}</h3>
              <p className="text-sm text-gray-600">{report.description}</p>
            </div>
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">التقارير</h1>
        <p className="text-gray-600 mt-1">
          عرض وتصدير التقارير المختلفة للنظام
        </p>
      </div>

      {/* Inventory Reports */}
      <div>
        <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
          <Package className="w-5 h-5" />
          تقارير المخزون
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {inventoryReports.map(renderReportCard)}
        </div>
      </div>

      {/* Customer Reports */}
      <div>
        <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
          <Users className="w-5 h-5" />
          تقارير العملاء
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {customerReports.map(renderReportCard)}
        </div>
      </div>

      {/* Sales Reports */}
      <div>
        <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
          <FileBarChart className="w-5 h-5" />
          تقارير المبيعات
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {salesReports.map(renderReportCard)}
        </div>
      </div>
    </div>
  )
}
