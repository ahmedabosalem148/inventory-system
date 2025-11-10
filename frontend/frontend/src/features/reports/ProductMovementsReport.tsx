/**
 * Product Movements Report
 * Track all movements for a specific product (issue, return, transfer)
 */

import { useState, useEffect } from 'react'
import { ArrowLeft, Download, Search, Calendar, TrendingUp, TrendingDown, ArrowRight } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { toast } from 'react-hot-toast'
import apiClient from '@/app/axios'
import { getProducts } from '@/services/api/products'
import type { Product, ProductClassification } from '@/types'

interface Movement {
  id: number
  date: string
  type: 'issue' | 'return' | 'transfer_out' | 'transfer_in'
  voucher_number: string
  branch_name: string
  quantity: number
  running_balance: number
  reference: string
}

interface MovementData {
  product: {
    id: number
    name: string
    sku: string
    unit: string
  }
  movements: Movement[]
  summary: {
    total_issued: number
    total_returned: number
    total_transferred_out: number
    total_transferred_in: number
    current_balance: number
  }
}

export function ProductMovementsReport() {
  const [loading, setLoading] = useState(false)
  const [data, setData] = useState<MovementData | null>(null)
  const [products, setProducts] = useState<Product[]>([])
  const [searchTerm, setSearchTerm] = useState('')
  const [selectedProduct, setSelectedProduct] = useState<number | null>(null)
  const [classificationFilter, setClassificationFilter] = useState<ProductClassification | ''>('')
  const [dateFrom, setDateFrom] = useState(() => {
    const date = new Date()
    date.setMonth(date.getMonth() - 1)
    return date.toISOString().split('T')[0]
  })
  const [dateTo, setDateTo] = useState(() => new Date().toISOString().split('T')[0])

  useEffect(() => {
    loadProducts()
  }, [classificationFilter])

  const loadProducts = async () => {
    try {
      const params: any = { per_page: 100 }
      if (classificationFilter) {
        params.product_classification = classificationFilter
      }
      const response = await getProducts(params)
      setProducts(response.data)
    } catch (error) {
      console.error('Error loading products:', error)
    }
  }

  const loadReport = async () => {
    if (!selectedProduct) {
      toast.error('الرجاء اختيار منتج')
      return
    }

    try {
      setLoading(true)
      const params = {
        product_id: selectedProduct,
        date_from: dateFrom,
        date_to: dateTo,
      }
      const response = await apiClient.get<{ data: MovementData }>('/reports/product-movement', { params })
      setData(response.data.data)
    } catch (error: any) {
      console.error('Error loading movements:', error)
      toast.error(error.response?.data?.message || 'فشل تحميل التقرير')
    } finally {
      setLoading(false)
    }
  }

  const handleExport = () => {
    toast.success('سيتم تصدير التقرير قريباً...')
    // TODO: Implement export functionality
  }

  const getMovementBadge = (type: Movement['type']) => {
    const badges = {
      issue: { variant: 'danger' as const, label: 'صرف', icon: <TrendingDown className="w-3 h-3" /> },
      return: { variant: 'success' as const, label: 'إرجاع', icon: <TrendingUp className="w-3 h-3" /> },
      transfer_out: { variant: 'warning' as const, label: 'تحويل خارج', icon: <ArrowRight className="w-3 h-3" /> },
      transfer_in: { variant: 'info' as const, label: 'تحويل داخل', icon: <ArrowRight className="w-3 h-3" /> },
    }
    return badges[type]
  }

  const filteredProducts = products.filter(p => 
    p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    p.sku?.toLowerCase().includes(searchTerm.toLowerCase())
  )

  const columns = [
    {
      key: 'date',
      header: 'التاريخ',
      sortable: true,
      render: (row: Movement) => new Date(row.date).toLocaleDateString('ar-EG'),
    },
    {
      key: 'type',
      header: 'نوع الحركة',
      render: (row: Movement) => {
        const badge = getMovementBadge(row.type)
        return (
          <Badge variant={badge.variant} className="flex items-center gap-1 w-fit">
            {badge.icon}
            {badge.label}
          </Badge>
        )
      },
    },
    {
      key: 'voucher_number',
      header: 'رقم السند',
      sortable: true,
      render: (row: Movement) => (
        <span className="font-mono text-sm">{row.voucher_number}</span>
      ),
    },
    {
      key: 'branch_name',
      header: 'الفرع',
      sortable: true,
      render: (row: Movement) => row.branch_name,
    },
    {
      key: 'quantity',
      header: 'الكمية',
      sortable: true,
      render: (row: Movement) => (
        <span className={`font-bold ${
          row.type === 'issue' || row.type === 'transfer_out' ? 'text-red-600' : 'text-green-600'
        }`}>
          {row.type === 'issue' || row.type === 'transfer_out' ? '-' : '+'}{row.quantity}
        </span>
      ),
    },
    {
      key: 'running_balance',
      header: 'الرصيد الجاري',
      sortable: true,
      render: (row: Movement) => (
        <span className="font-bold text-blue-600">{row.running_balance}</span>
      ),
    },
    {
      key: 'reference',
      header: 'المرجع',
      render: (row: Movement) => (
        <span className="text-sm text-gray-600">{row.reference || '-'}</span>
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
            <h1 className="text-3xl font-bold">تقرير حركة صنف</h1>
            <p className="text-gray-600 mt-1">
              تتبع حركات منتج محدد (صرف، إرجاع، تحويل)
            </p>
          </div>
        </div>
        {data && (
          <Button size="sm" variant="outline" onClick={handleExport}>
            <Download className="w-4 h-4 ml-2" />
            تصدير Excel
          </Button>
        )}
      </div>

      {/* Search & Filters */}
      <Card>
        <CardContent className="p-6">
          <div className="space-y-4">
            {/* Classification Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                تصفية حسب نوع المنتج
              </label>
              <select
                value={classificationFilter}
                onChange={(e) => setClassificationFilter(e.target.value as ProductClassification | '')}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">جميع الأنواع</option>
                <option value="finished_product">منتج تام</option>
                <option value="semi_finished">منتج غير تام</option>
                <option value="parts">أجزاء</option>
                <option value="plastic_parts">بلاستيك</option>
                <option value="aluminum_parts">ألومنيوم</option>
                <option value="raw_material">مواد خام</option>
                <option value="other">أخرى</option>
              </select>
            </div>
            
            {/* Product Search */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                اختر المنتج
              </label>
              <div className="relative">
                <Search className="absolute right-3 top-3 w-5 h-5 text-gray-400" />
                <input
                  type="text"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  placeholder="ابحث باسم المنتج أو الكود..."
                  className="w-full pr-10 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              {searchTerm && (
                <div className="mt-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg">
                  {filteredProducts.map(product => (
                    <div
                      key={product.id}
                      onClick={() => {
                        setSelectedProduct(product.id)
                        setSearchTerm(`${product.name} (${product.sku})`)
                      }}
                      className={`p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0 ${
                        selectedProduct === product.id ? 'bg-blue-50' : ''
                      }`}
                    >
                      <div className="font-medium">{product.name}</div>
                      <div className="text-sm text-gray-600">{product.sku}</div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Date Range */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  من تاريخ
                </label>
                <Input
                  type="date"
                  value={dateFrom}
                  onChange={(e) => setDateFrom(e.target.value)}
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  إلى تاريخ
                </label>
                <Input
                  type="date"
                  value={dateTo}
                  onChange={(e) => setDateTo(e.target.value)}
                />
              </div>
              <Button onClick={loadReport} disabled={!selectedProduct || loading}>
                <Search className="w-4 h-4 ml-2" />
                عرض التقرير
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Summary Cards */}
      {data && (
        <>
          <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-600">
                  إجمالي الصرف
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-2">
                  <TrendingDown className="w-5 h-5 text-red-600" />
                  <span className="text-2xl font-bold text-red-600">
                    {data.summary.total_issued}
                  </span>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-600">
                  إجمالي الإرجاع
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-2">
                  <TrendingUp className="w-5 h-5 text-green-600" />
                  <span className="text-2xl font-bold text-green-600">
                    {data.summary.total_returned}
                  </span>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-600">
                  تحويل خارج
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-2">
                  <ArrowRight className="w-5 h-5 text-orange-600" />
                  <span className="text-2xl font-bold text-orange-600">
                    {data.summary.total_transferred_out}
                  </span>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-gray-600">
                  تحويل داخل
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-2">
                  <ArrowRight className="w-5 h-5 text-blue-600 rotate-180" />
                  <span className="text-2xl font-bold text-blue-600">
                    {data.summary.total_transferred_in}
                  </span>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-blue-50">
              <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-blue-900">
                  الرصيد الحالي
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-2">
                  <span className="text-2xl font-bold text-blue-600">
                    {data.summary.current_balance}
                  </span>
                  <span className="text-sm text-blue-600">{data.product.unit}</span>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Product Info */}
          <Card className="bg-gray-50">
            <CardContent className="p-4">
              <div className="flex items-center gap-4">
                <div className="text-sm">
                  <span className="text-gray-600">المنتج: </span>
                  <span className="font-bold">{data.product.name}</span>
                </div>
                <div className="text-sm">
                  <span className="text-gray-600">الكود: </span>
                  <span className="font-mono">{data.product.sku}</span>
                </div>
                <div className="text-sm">
                  <span className="text-gray-600">الوحدة: </span>
                  <span>{data.product.unit}</span>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Data Table */}
          <Card>
            <CardContent className="p-6">
              <DataTable
                columns={columns}
                data={data.movements}
                loading={loading}
                emptyMessage="لا توجد حركات للمنتج المحدد"
              />
            </CardContent>
          </Card>
        </>
      )}

      {/* Empty State */}
      {!data && !loading && (
        <Card>
          <CardContent className="p-12 text-center">
            <Calendar className="w-16 h-16 mx-auto text-gray-300 mb-4" />
            <h3 className="text-lg font-semibold text-gray-600 mb-2">
              اختر منتجاً لعرض حركاته
            </h3>
            <p className="text-gray-500">
              استخدم البحث أعلاه لاختيار المنتج المراد عرض حركاته
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
