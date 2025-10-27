/**
 * Low Stock Report
 * Display products that have reached or are approaching minimum stock levels
 */

import { useState, useEffect } from 'react'
import { ArrowLeft, Download, AlertTriangle, Package } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { toast } from 'react-hot-toast'
import apiClient from '@/app/axios'

interface LowStockItem {
  product_id: number
  name: string
  sku: string
  unit: string
  category: string
  branch_id: number
  branch_name: string
  quantity: number
  min_stock: number
  deficit: number
  percentage: number
  status: 'out_of_stock' | 'critical' | 'low'
  reorder_suggestion: number
}

interface LowStockSummary {
  total_items: number
  out_of_stock: number
  critical: number
  low: number
}

export function LowStockReport() {
  const [loading, setLoading] = useState(true)
  const [data, setData] = useState<LowStockItem[]>([])
  const [summary, setSummary] = useState<LowStockSummary | null>(null)
  const [branchFilter, setBranchFilter] = useState<string>('all')

  useEffect(() => {
    loadReport()
  }, [branchFilter])

  const loadReport = async () => {
    try {
      setLoading(true)
      const params = branchFilter !== 'all' ? { branch_id: branchFilter } : {}
      const response = await apiClient.get('/reports/low-stock', { params })
      setData(response.data.data || [])
      setSummary(response.data.summary || null)
    } catch (error: any) {
      console.error('Error loading low stock report:', error)
      toast.error(error.response?.data?.message || 'فشل تحميل التقرير')
    } finally {
      setLoading(false)
    }
  }

  const handleExport = () => {
    toast.success('سيتم تصدير التقرير قريباً...')
    // TODO: Implement export functionality
  }

  const columns = [
    {
      key: 'sku',
      header: 'كود المنتج',
      sortable: true,
      render: (row: LowStockItem) => row.sku,
    },
    {
      key: 'name',
      header: 'اسم المنتج',
      sortable: true,
      render: (row: LowStockItem) => (
        <div className="font-medium">
          {row.name}
          <Badge 
            variant={row.status === 'out_of_stock' || row.status === 'critical' ? 'danger' : 'warning'} 
            className="mr-2 text-xs"
          >
            {row.status === 'out_of_stock' ? 'نفذ' : row.status === 'critical' ? 'حرج' : 'منخفض'}
          </Badge>
        </div>
      ),
    },
    {
      key: 'branch_name',
      header: 'الفرع',
      sortable: true,
      render: (row: LowStockItem) => row.branch_name,
    },
    {
      key: 'quantity',
      header: 'المخزون الحالي',
      sortable: true,
      render: (row: LowStockItem) => (
        <span className={`font-bold ${row.status === 'out_of_stock' || row.status === 'critical' ? 'text-red-600' : 'text-orange-600'}`}>
          {row.quantity} {row.unit}
        </span>
      ),
    },
    {
      key: 'min_stock',
      header: 'الحد الأدنى',
      sortable: true,
      render: (row: LowStockItem) => (
        <span className="text-gray-600">
          {row.min_stock} {row.unit}
        </span>
      ),
    },
    {
      key: 'deficit',
      header: 'النقص',
      sortable: true,
      render: (row: LowStockItem) => (
        <div className="flex items-center gap-2">
          <AlertTriangle className={`w-4 h-4 ${row.status === 'out_of_stock' || row.status === 'critical' ? 'text-red-600' : 'text-orange-600'}`} />
          <span className="font-bold">
            {row.deficit} {row.unit}
          </span>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="outline"
            size="sm"
            onClick={() => window.location.hash = '#reports'}
          >
            <ArrowLeft className="w-4 h-4 ml-2" />
            رجوع
          </Button>
          <div>
            <h1 className="text-3xl font-bold">تقرير منخفض المخزون</h1>
            <p className="text-gray-600 mt-1">
              المنتجات التي وصلت أو قاربت الحد الأدنى
            </p>
          </div>
        </div>
        <Button size="sm" variant="outline" onClick={handleExport}>
          <Download className="w-4 h-4 ml-2" />
          تصدير Excel
        </Button>
      </div>

      {/* Summary Cards */}
      {summary && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                إجمالي الأصناف
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-2">
                <AlertTriangle className="w-5 h-5 text-orange-600" />
                <span className="text-2xl font-bold">{summary.total_items}</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                نفذ من المخزون
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Badge variant="danger" className="text-lg px-3 py-1">
                {summary.out_of_stock}
              </Badge>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                حالة حرجة
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Badge variant="danger" className="text-lg px-3 py-1">
                {summary.critical}
              </Badge>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                منخفض
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Badge variant="warning" className="text-lg px-3 py-1">
                {summary.low}
              </Badge>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Branch Filter */}
      <Card>
        <CardContent className="p-4">
          <div className="flex items-center gap-4">
            <Package className="w-5 h-5 text-gray-400" />
            <label className="text-sm font-medium">الفرع:</label>
            <select
              value={branchFilter}
              onChange={(e) => setBranchFilter(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm min-w-[200px] focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">جميع الفروع</option>
              <option value="factory">المصنع</option>
              <option value="ataba">العتبة</option>
              <option value="imbaba">إمبابة</option>
            </select>
          </div>
        </CardContent>
      </Card>

      {/* Data Table */}
      <Card>
        <CardContent className="p-6">
          <DataTable
            columns={columns}
            data={data || []}
            loading={loading}
            emptyMessage="لا توجد منتجات منخفضة المخزون"
          />
        </CardContent>
      </Card>

      {/* Alert Message */}
      {summary && summary.total_items > 0 && (
        <Card className="bg-orange-50 border-orange-200">
          <CardContent className="p-4">
            <div className="flex items-start gap-3">
              <AlertTriangle className="w-5 h-5 text-orange-600 mt-0.5" />
              <div>
                <h3 className="font-semibold text-orange-900">تنبيه مهم</h3>
                <p className="text-sm text-orange-800 mt-1">
                  يوجد <span className="font-bold">{summary.total_items}</span> منتج يحتاج إلى إعادة طلب.
                  {summary.out_of_stock > 0 && (
                    <> منهم <span className="font-bold text-red-600">{summary.out_of_stock}</span> نفذ من المخزون.</>
                  )}
                  {summary.critical > 0 && (
                    <> و <span className="font-bold text-red-600">{summary.critical}</span> في حالة حرجة.</>
                  )}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
