/**
 * Stock Summary Report
 * Display current stock levels for all products across all branches
 */

import { useState, useEffect } from 'react'
import { ArrowLeft, Download, Filter, Package } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { toast } from 'react-hot-toast'
import apiClient from '@/services/api/client'

interface StockItem {
  product_id: number
  product_name: string
  sku: string
  unit: string
  factory_stock: number
  ataba_stock: number
  imbaba_stock: number
  total_stock: number
  min_stock: number
  is_low: boolean
}

interface StockSummaryData {
  items: StockItem[]
  total_products: number
  total_stock_value: number
  low_stock_count: number
}

export function StockSummaryReport() {
  const navigate = useNavigate()
  const [loading, setLoading] = useState(true)
  const [data, setData] = useState<StockSummaryData | null>(null)
  const [showLowStockOnly, setShowLowStockOnly] = useState(false)

  useEffect(() => {
    loadReport()
  }, [])

  const loadReport = async () => {
    try {
      setLoading(true)
      const response = await apiClient.get<{ data: StockSummaryData }>('/reports/inventory/summary')
      setData(response.data.data)
    } catch (error: any) {
      console.error('Error loading stock summary:', error)
      toast.error(error.response?.data?.message || 'فشل تحميل التقرير')
    } finally {
      setLoading(false)
    }
  }

  const handleExport = () => {
    toast.success('سيتم تصدير التقرير قريباً...')
    // TODO: Implement export functionality
  }

  const filteredItems = data?.items.filter(item => 
    !showLowStockOnly || item.is_low
  ) || []

  const columns = [
    {
      key: 'sku',
      header: 'كود المنتج',
      sortable: true,
      render: (row: StockItem) => row.sku,
    },
    {
      key: 'product_name',
      header: 'اسم المنتج',
      sortable: true,
      render: (row: StockItem) => (
        <div className="font-medium">
          {row.product_name}
          {row.is_low && (
            <Badge variant="warning" className="mr-2 text-xs">
              منخفض
            </Badge>
          )}
        </div>
      ),
    },
    {
      key: 'unit',
      header: 'الوحدة',
      render: (row: StockItem) => row.unit,
    },
    {
      key: 'factory_stock',
      header: 'المصنع',
      sortable: true,
      render: (row: StockItem) => (
        <span className={row.factory_stock <= 0 ? 'text-red-600 font-bold' : ''}>
          {row.factory_stock}
        </span>
      ),
    },
    {
      key: 'ataba_stock',
      header: 'العتبة',
      sortable: true,
      render: (row: StockItem) => (
        <span className={row.ataba_stock <= 0 ? 'text-red-600 font-bold' : ''}>
          {row.ataba_stock}
        </span>
      ),
    },
    {
      key: 'imbaba_stock',
      header: 'إمبابة',
      sortable: true,
      render: (row: StockItem) => (
        <span className={row.imbaba_stock <= 0 ? 'text-red-600 font-bold' : ''}>
          {row.imbaba_stock}
        </span>
      ),
    },
    {
      key: 'total_stock',
      header: 'الإجمالي',
      sortable: true,
      render: (row: StockItem) => (
        <span className={`font-bold ${row.total_stock <= 0 ? 'text-red-600' : 'text-green-600'}`}>
          {row.total_stock}
        </span>
      ),
    },
    {
      key: 'min_stock',
      header: 'الحد الأدنى',
      sortable: true,
      render: (row: StockItem) => row.min_stock,
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
            onClick={() => navigate('/reports')}
          >
            <ArrowLeft className="w-4 h-4 ml-2" />
            رجوع
          </Button>
          <div>
            <h1 className="text-3xl font-bold">تقرير إجمالي المخزون</h1>
            <p className="text-gray-600 mt-1">
              عرض المخزون الحالي لجميع المنتجات في جميع الفروع
            </p>
          </div>
        </div>
        <Button size="sm" variant="outline" onClick={handleExport}>
          <Download className="w-4 h-4 ml-2" />
          تصدير Excel
        </Button>
      </div>

      {/* Summary Cards */}
      {data && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                إجمالي المنتجات
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-2">
                <Package className="w-5 h-5 text-blue-600" />
                <span className="text-2xl font-bold">{data.total_products}</span>
                <span className="text-sm text-gray-500">منتج</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                منتجات منخفضة المخزون
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-2">
                <Badge variant="warning" className="text-lg px-3 py-1">
                  {data.low_stock_count}
                </Badge>
                <span className="text-sm text-gray-500">منتج</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                قيمة المخزون التقديرية
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-2">
                <span className="text-2xl font-bold text-green-600">
                  {data.total_stock_value.toLocaleString()}
                </span>
                <span className="text-sm text-gray-500">ر.س</span>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Filter */}
      <Card>
        <CardContent className="p-4">
          <div className="flex items-center gap-4">
            <Filter className="w-5 h-5 text-gray-400" />
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={showLowStockOnly}
                onChange={(e) => setShowLowStockOnly(e.target.checked)}
                className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              />
              <span className="text-sm font-medium">
                عرض المنتجات منخفضة المخزون فقط
              </span>
            </label>
            {showLowStockOnly && (
              <Badge variant="warning">
                {filteredItems.length} منتج
              </Badge>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Data Table */}
      <Card>
        <CardContent className="p-6">
          <DataTable
            columns={columns}
            data={filteredItems}
            loading={loading}
            emptyMessage="لا توجد بيانات"
          />
        </CardContent>
      </Card>
    </div>
  )
}
