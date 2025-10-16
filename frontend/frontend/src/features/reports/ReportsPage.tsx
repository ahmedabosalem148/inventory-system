/**
 * Reports & Analytics Page
 * View various business reports and analytics
 */

import { useState } from 'react'
import { 
  TrendingUp, 
  TrendingDown, 
  DollarSign, 
  ShoppingCart, 
  Package,
  Users,
  FileText,
  Calendar,
  Download
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'

type ReportType = 'sales' | 'purchases' | 'inventory' | 'profit-loss' | 'analytics'

export const ReportsPage = () => {
  const [selectedReport, setSelectedReport] = useState<ReportType>('analytics')
  const [dateFrom, setDateFrom] = useState(() => {
    const date = new Date()
    date.setMonth(date.getMonth() - 1)
    return date.toISOString().split('T')[0]
  })
  const [dateTo, setDateTo] = useState(() => new Date().toISOString().split('T')[0])

  const reportCards = [
    {
      type: 'analytics' as ReportType,
      title: 'لوحة التحليلات',
      description: 'نظرة عامة شاملة على الأداء',
      icon: TrendingUp,
      color: 'blue',
    },
    {
      type: 'sales' as ReportType,
      title: 'تقرير المبيعات',
      description: 'تحليل المبيعات والإيرادات',
      icon: ShoppingCart,
      color: 'green',
    },
    {
      type: 'purchases' as ReportType,
      title: 'تقرير المشتريات',
      description: 'تحليل المشتريات والتكاليف',
      icon: Package,
      color: 'orange',
    },
    {
      type: 'inventory' as ReportType,
      title: 'تقرير المخزون',
      description: 'حالة المخزون والتقييم',
      icon: FileText,
      color: 'purple',
    },
    {
      type: 'profit-loss' as ReportType,
      title: 'الأرباح والخسائر',
      description: 'تحليل الربحية والهوامش',
      icon: DollarSign,
      color: 'red',
    },
  ]

  const renderAnalyticsDashboard = () => (
    <div className="space-y-6">
      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">إجمالي المبيعات</p>
              <p className="text-2xl font-bold">125,430 ر.س</p>
              <div className="flex items-center gap-1 mt-1">
                <TrendingUp className="h-4 w-4 text-green-600" />
                <span className="text-sm text-green-600">+12.5%</span>
              </div>
            </div>
            <div className="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
              <DollarSign className="h-6 w-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">إجمالي المشتريات</p>
              <p className="text-2xl font-bold">87,250 ر.س</p>
              <div className="flex items-center gap-1 mt-1">
                <TrendingDown className="h-4 w-4 text-red-600" />
                <span className="text-sm text-red-600">-3.2%</span>
              </div>
            </div>
            <div className="p-3 bg-orange-100 dark:bg-orange-900 rounded-lg">
              <ShoppingCart className="h-6 w-6 text-orange-600 dark:text-orange-400" />
            </div>
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">صافي الربح</p>
              <p className="text-2xl font-bold">38,180 ر.س</p>
              <div className="flex items-center gap-1 mt-1">
                <TrendingUp className="h-4 w-4 text-green-600" />
                <span className="text-sm text-green-600">+8.3%</span>
              </div>
            </div>
            <div className="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
              <TrendingUp className="h-6 w-6 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">هامش الربح</p>
              <p className="text-2xl font-bold">30.4%</p>
              <div className="flex items-center gap-1 mt-1">
                <span className="text-sm text-gray-600">مستقر</span>
              </div>
            </div>
            <div className="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
              <Users className="h-6 w-6 text-purple-600 dark:text-purple-400" />
            </div>
          </div>
        </Card>
      </div>

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card className="p-6">
          <h3 className="text-lg font-bold mb-4">اتجاه المبيعات</h3>
          <div className="h-64 flex items-center justify-center text-gray-400">
            <div className="text-center">
              <BarChart3 className="h-12 w-12 mx-auto mb-2" />
              <p>سيتم عرض الرسم البياني هنا</p>
            </div>
          </div>
        </Card>

        <Card className="p-6">
          <h3 className="text-lg font-bold mb-4">أفضل المنتجات مبيعاً</h3>
          <div className="space-y-3">
            {[
              { name: 'منتج أ', sales: 15420, percentage: 85 },
              { name: 'منتج ب', sales: 12350, percentage: 68 },
              { name: 'منتج ج', sales: 9870, percentage: 54 },
              { name: 'منتج د', sales: 7650, percentage: 42 },
              { name: 'منتج هـ', sales: 5430, percentage: 30 },
            ].map((product, index) => (
              <div key={index} className="space-y-1">
                <div className="flex justify-between text-sm">
                  <span className="font-medium">{product.name}</span>
                  <span className="text-gray-600">{product.sales.toLocaleString()} ر.س</span>
                </div>
                <div className="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div 
                    className="h-full bg-blue-600" 
                    style={{ width: `${product.percentage}%` }}
                  />
                </div>
              </div>
            ))}
          </div>
        </Card>
      </div>

      {/* Recent Activity */}
      <Card className="p-6">
        <h3 className="text-lg font-bold mb-4">النشاط الأخير</h3>
        <div className="space-y-3">
          {[
            { type: 'sale', text: 'فاتورة مبيعات #1234', amount: '+2,450 ر.س', time: 'منذ ساعة' },
            { type: 'purchase', text: 'أمر شراء #5678', amount: '-1,200 ر.س', time: 'منذ 3 ساعات' },
            { type: 'payment', text: 'دفعة من عميل #101', amount: '+5,000 ر.س', time: 'منذ 5 ساعات' },
            { type: 'adjustment', text: 'تعديل مخزون - منتج أ', amount: '', time: 'منذ يوم' },
          ].map((activity, index) => (
            <div key={index} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
              <div className="flex items-center gap-3">
                <div className={`p-2 rounded-full ${
                  activity.type === 'sale' ? 'bg-green-100 dark:bg-green-900' :
                  activity.type === 'purchase' ? 'bg-orange-100 dark:bg-orange-900' :
                  activity.type === 'payment' ? 'bg-blue-100 dark:bg-blue-900' :
                  'bg-gray-100 dark:bg-gray-700'
                }`}>
                  <FileText className="h-4 w-4" />
                </div>
                <div>
                  <p className="font-medium">{activity.text}</p>
                  <p className="text-sm text-gray-500">{activity.time}</p>
                </div>
              </div>
              {activity.amount && (
                <Badge variant={activity.amount.startsWith('+') ? 'success' : 'danger'}>
                  {activity.amount}
                </Badge>
              )}
            </div>
          ))}
        </div>
      </Card>
    </div>
  )

  const renderSalesReport = () => (
    <Card className="p-6">
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-bold">تقرير المبيعات التفصيلي</h3>
          <Button size="sm" variant="outline">
            <Download className="h-4 w-4 ml-2" />
            تصدير PDF
          </Button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <p className="text-sm text-gray-600 dark:text-gray-400">عدد الفواتير</p>
            <p className="text-2xl font-bold">245</p>
          </div>
          <div className="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <p className="text-sm text-gray-600 dark:text-gray-400">إجمالي المبيعات</p>
            <p className="text-2xl font-bold">125,430 ر.س</p>
          </div>
          <div className="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <p className="text-sm text-gray-600 dark:text-gray-400">متوسط الفاتورة</p>
            <p className="text-2xl font-bold">512 ر.س</p>
          </div>
        </div>

        <div className="h-64 flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
          <div className="text-center text-gray-400">
            <ShoppingCart className="h-12 w-12 mx-auto mb-2" />
            <p>رسم بياني للمبيعات</p>
          </div>
        </div>
      </div>
    </Card>
  )

  const renderPurchasesReport = () => (
    <Card className="p-6">
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-bold">تقرير المشتريات التفصيلي</h3>
          <Button size="sm" variant="outline">
            <Download className="h-4 w-4 ml-2" />
            تصدير Excel
          </Button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <p className="text-sm text-gray-600 dark:text-gray-400">عدد أوامر الشراء</p>
            <p className="text-2xl font-bold">132</p>
          </div>
          <div className="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
            <p className="text-sm text-gray-600 dark:text-gray-400">إجمالي المشتريات</p>
            <p className="text-2xl font-bold">87,250 ر.س</p>
          </div>
          <div className="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <p className="text-sm text-gray-600 dark:text-gray-400">متوسط الطلب</p>
            <p className="text-2xl font-bold">661 ر.س</p>
          </div>
        </div>

        <div className="h-64 flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
          <div className="text-center text-gray-400">
            <Package className="h-12 w-12 mx-auto mb-2" />
            <p>رسم بياني للمشتريات</p>
          </div>
        </div>
      </div>
    </Card>
  )

  const renderInventoryReport = () => (
    <Card className="p-6">
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-bold">تقرير المخزون</h3>
          <Button size="sm" variant="outline">
            <Download className="h-4 w-4 ml-2" />
            تصدير
          </Button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
            <p className="text-sm text-gray-600 dark:text-gray-400">قيمة المخزون</p>
            <p className="text-2xl font-bold">342,850 ر.س</p>
          </div>
          <div className="p-4 bg-teal-50 dark:bg-teal-900/20 rounded-lg">
            <p className="text-sm text-gray-600 dark:text-gray-400">عدد المنتجات</p>
            <p className="text-2xl font-bold">1,245</p>
          </div>
          <div className="p-4 bg-pink-50 dark:bg-pink-900/20 rounded-lg">
            <p className="text-sm text-gray-600 dark:text-gray-400">تنبيهات المخزون</p>
            <p className="text-2xl font-bold text-red-600">23</p>
          </div>
        </div>

        <div className="space-y-2">
          <h4 className="font-semibold">المنتجات ذات المخزون المنخفض</h4>
          <div className="space-y-2">
            {[
              { name: 'منتج أ', current: 5, min: 20, unit: 'قطعة' },
              { name: 'منتج ب', current: 8, min: 25, unit: 'علبة' },
              { name: 'منتج ج', current: 3, min: 15, unit: 'كرتون' },
            ].map((item, index) => (
              <div key={index} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <span className="font-medium">{item.name}</span>
                <div className="flex items-center gap-3">
                  <Badge variant="danger">
                    {item.current} {item.unit}
                  </Badge>
                  <span className="text-sm text-gray-500">
                    الحد الأدنى: {item.min}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </Card>
  )

  const renderProfitLossReport = () => (
    <Card className="p-6">
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-bold">تقرير الأرباح والخسائر</h3>
          <Button size="sm" variant="outline">
            <Download className="h-4 w-4 ml-2" />
            تصدير PDF
          </Button>
        </div>

        <div className="space-y-4">
          <div className="flex justify-between items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <span className="font-semibold">الإيرادات</span>
            <span className="text-xl font-bold text-green-600">125,430 ر.س</span>
          </div>

          <div className="space-y-2 pr-4">
            <div className="flex justify-between text-gray-600">
              <span>تكلفة المبيعات</span>
              <span>-65,250 ر.س</span>
            </div>
            <div className="flex justify-between font-semibold border-t pt-2">
              <span>إجمالي الربح</span>
              <span className="text-blue-600">60,180 ر.س</span>
            </div>
          </div>

          <div className="space-y-2 pr-4">
            <div className="flex justify-between text-gray-600">
              <span>مصاريف تشغيلية</span>
              <span>-15,000 ر.س</span>
            </div>
            <div className="flex justify-between text-gray-600">
              <span>مصاريف أخرى</span>
              <span>-7,000 ر.س</span>
            </div>
            <div className="flex justify-between font-bold text-lg border-t-2 pt-2">
              <span>صافي الربح</span>
              <span className="text-green-600">38,180 ر.س</span>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4 pt-4">
            <div className="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
              <p className="text-sm text-gray-600 dark:text-gray-400">هامش الربح الإجمالي</p>
              <p className="text-2xl font-bold">47.9%</p>
            </div>
            <div className="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-center">
              <p className="text-sm text-gray-600 dark:text-gray-400">هامش الربح الصافي</p>
              <p className="text-2xl font-bold">30.4%</p>
            </div>
          </div>
        </div>
      </div>
    </Card>
  )

  const renderReportContent = () => {
    switch (selectedReport) {
      case 'analytics':
        return renderAnalyticsDashboard()
      case 'sales':
        return renderSalesReport()
      case 'purchases':
        return renderPurchasesReport()
      case 'inventory':
        return renderInventoryReport()
      case 'profit-loss':
        return renderProfitLossReport()
      default:
        return renderAnalyticsDashboard()
    }
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">التقارير والتحليلات</h1>
        <p className="text-gray-600 dark:text-gray-400">
          تحليل شامل لأداء الأعمال والمقاييس الرئيسية
        </p>
      </div>

      {/* Date Range Filter */}
      <Card className="p-4">
        <div className="flex flex-wrap gap-4 items-center">
          <div className="flex items-center gap-2">
            <Calendar className="h-5 w-5 text-gray-400" />
            <span className="text-sm font-medium">الفترة:</span>
          </div>
          <div className="flex gap-2 items-center">
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => setDateFrom(e.target.value)}
              className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm"
            />
            <span className="text-gray-500">إلى</span>
            <input
              type="date"
              value={dateTo}
              onChange={(e) => setDateTo(e.target.value)}
              className="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm"
            />
          </div>
          <Button size="sm">تطبيق</Button>
        </div>
      </Card>

      {/* Report Type Selection */}
      <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
        {reportCards.map((report) => {
          const Icon = report.icon
          const isSelected = selectedReport === report.type
          return (
            <Card
              key={report.type}
              className={`p-4 cursor-pointer transition-all hover:shadow-lg ${
                isSelected ? 'ring-2 ring-blue-500 shadow-lg' : ''
              }`}
              onClick={() => setSelectedReport(report.type)}
            >
              <div className={`p-3 rounded-lg mb-3 ${
                isSelected 
                  ? 'bg-blue-100 dark:bg-blue-900' 
                  : `bg-${report.color}-100 dark:bg-${report.color}-900`
              }`}>
                <Icon className={`h-6 w-6 ${
                  isSelected
                    ? 'text-blue-600 dark:text-blue-400'
                    : `text-${report.color}-600 dark:text-${report.color}-400`
                }`} />
              </div>
              <h3 className="font-bold mb-1">{report.title}</h3>
              <p className="text-sm text-gray-600 dark:text-gray-400">
                {report.description}
              </p>
            </Card>
          )
        })}
      </div>

      {/* Report Content */}
      {renderReportContent()}
    </div>
  )
}

// Import BarChart3 at top
import { BarChart3 } from 'lucide-react'
