/**
 * Product Movement Report
 * Display movement history for a specific product
 */

import { useState, useEffect } from 'react'
import { ArrowLeft, Download, FileText, Search, Calendar } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Spinner } from '@/components/ui/spinner'
import { toast } from 'react-hot-toast'
import apiClient from '@/app/axios'

interface Movement {
  id: number
  product_id: number
  branch_id: number
  branch_name: string
  movement_type: 'issue' | 'return' | 'transfer_out' | 'transfer_in'
  quantity: number
  reference_number: string
  notes: string | null
  created_at: string
  user_name: string
}

interface Product {
  id: number
  name: string
  sku: string
  unit: string
}

interface Summary {
  total_movements: number
  total_issues: number
  total_returns: number
}

export function ProductMovementReport() {
  const [loading, setLoading] = useState(false)
  const [products, setProducts] = useState<Product[]>([])
  const [branches, setBranches] = useState<any[]>([])
  const [movements, setMovements] = useState<Movement[]>([])
  const [product, setProduct] = useState<Product | null>(null)
  const [summary, setSummary] = useState<Summary | null>(null)

  // Filters
  const [productId, setProductId] = useState<string>('')
  const [branchId, setBranchId] = useState<string>('')
  const [fromDate, setFromDate] = useState<string>('')
  const [toDate, setToDate] = useState<string>('')
  const [movementType, setMovementType] = useState<string>('')

  useEffect(() => {
    fetchProducts()
    fetchBranches()
  }, [])

  const fetchProducts = async () => {
    try {
      const response = await apiClient.get('/products')
      setProducts(response.data.data || [])
    } catch (error) {
      console.error('Failed to fetch products:', error)
    }
  }

  const fetchBranches = async () => {
    try {
      const response = await apiClient.get('/branches')
      setBranches(response.data.data || [])
    } catch (error) {
      console.error('Failed to fetch branches:', error)
    }
  }

  const fetchReport = async () => {
    if (!productId) {
      toast.error('يرجى اختيار منتج')
      return
    }

    setLoading(true)
    try {
      const params: Record<string, string> = { product_id: productId }
      if (branchId) params.branch_id = branchId
      if (fromDate) params.from_date = fromDate
      if (toDate) params.to_date = toDate
      if (movementType) params.movement_type = movementType

      const response = await apiClient.get('/reports/product-movement', { params })
      setMovements(response.data.data || [])
      setProduct(response.data.product || null)
      setSummary(response.data.summary || null)
    } catch (error: any) {
      console.error('Failed to fetch report:', error)
      toast.error(error.response?.data?.message || 'فشل في تحميل التقرير')
    } finally {
      setLoading(false)
    }
  }

  const handleExportPDF = async () => {
    if (!productId) {
      toast.error('يرجى اختيار منتج أولاً')
      return
    }

    try {
      const params: Record<string, string> = { product_id: productId }
      if (branchId) params.branch_id = branchId
      if (fromDate) params.from_date = fromDate
      if (toDate) params.to_date = toDate
      if (movementType) params.movement_type = movementType

      const response = await apiClient.get('/reports/product-movement/pdf', { params, responseType: 'blob' })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `product-movement-${productId}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      toast.success('تم تصدير التقرير بنجاح')
    } catch (error) {
      toast.error('فشل في تصدير PDF')
    }
  }

  const handleExportExcel = async () => {
    if (!productId) {
      toast.error('يرجى اختيار منتج أولاً')
      return
    }

    try {
      const params: Record<string, string> = { product_id: productId }
      if (branchId) params.branch_id = branchId
      if (fromDate) params.from_date = fromDate
      if (toDate) params.to_date = toDate
      if (movementType) params.movement_type = movementType

      const response = await apiClient.get('/reports/product-movement/excel', { params, responseType: 'blob' })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `product-movement-${productId}.xlsx`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      toast.success('تم تصدير التقرير بنجاح')
    } catch (error) {
      toast.error('فشل في تصدير Excel')
    }
  }

  const getMovementTypeBadge = (type: string) => {
    const config: Record<string, { variant: any; label: string }> = {
      issue: { variant: 'danger', label: 'صرف' },
      return: { variant: 'success', label: 'إرجاع' },
      transfer_out: { variant: 'warning', label: 'تحويل خارج' },
      transfer_in: { variant: 'info', label: 'تحويل داخل' },
    }
    const c = config[type] || { variant: 'default', label: type }
    return <Badge variant={c.variant}>{c.label}</Badge>
  }

  const handleReset = () => {
    setProductId('')
    setBranchId('')
    setFromDate('')
    setToDate('')
    setMovementType('')
    setMovements([])
    setProduct(null)
    setSummary(null)
  }

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
            <h1 className="text-3xl font-bold">تقرير حركة المنتج</h1>
            <p className="text-gray-600 mt-1">
              عرض جميع حركات منتج معين (صرف، إرجاع، تحويلات)
            </p>
          </div>
        </div>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-6 gap-3">
            <div className="md:col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">المنتج *</label>
              <select
                value={productId}
                onChange={(e) => setProductId(e.target.value)}
                disabled={loading}
                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
              >
                <option value="">اختر منتج...</option>
                {products.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.name} ({p.sku})
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الفرع</label>
              <select
                value={branchId}
                onChange={(e) => setBranchId(e.target.value)}
                disabled={loading}
                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
              >
                <option value="">جميع الفروع</option>
                {branches.map((b) => (
                  <option key={b.id} value={b.id}>{b.name}</option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">من تاريخ</label>
              <input
                type="date"
                value={fromDate}
                onChange={(e) => setFromDate(e.target.value)}
                disabled={loading}
                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">إلى تاريخ</label>
              <input
                type="date"
                value={toDate}
                onChange={(e) => setToDate(e.target.value)}
                disabled={loading}
                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">نوع الحركة</label>
              <select
                value={movementType}
                onChange={(e) => setMovementType(e.target.value)}
                disabled={loading}
                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
              >
                <option value="">الكل</option>
                <option value="issue">صرف</option>
                <option value="return">إرجاع</option>
                <option value="transfer_out">تحويل خارج</option>
                <option value="transfer_in">تحويل داخل</option>
              </select>
            </div>
          </div>

          <div className="flex gap-2 mt-4">
            <Button onClick={fetchReport} disabled={loading || !productId}>
              <Search className="w-4 h-4 ml-2" />
              {loading ? 'جاري البحث...' : 'عرض التقرير'}
            </Button>
            <Button variant="outline" onClick={handleReset} disabled={loading}>
              إعادة تعيين
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Product Info */}
      {product && (
        <Card className="bg-blue-50 border-blue-200">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="flex-1">
                <h3 className="font-bold text-lg">{product.name}</h3>
                <p className="text-sm text-gray-600">رمز المنتج: {product.sku} | الوحدة: {product.unit}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Summary Cards */}
      {summary && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">إجمالي الحركات</CardTitle>
            </CardHeader>
            <CardContent>
              <span className="text-2xl font-bold">{summary.total_movements}</span>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">إجمالي الصرف</CardTitle>
            </CardHeader>
            <CardContent>
              <Badge variant="danger" className="text-lg px-3 py-1">
                {summary.total_issues} {product?.unit}
              </Badge>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">إجمالي الإرجاع</CardTitle>
            </CardHeader>
            <CardContent>
              <Badge variant="success" className="text-lg px-3 py-1">
                {summary.total_returns} {product?.unit}
              </Badge>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Export Buttons */}
      {movements.length > 0 && (
        <Card>
          <CardContent className="pt-6">
            <div className="flex gap-3">
              <Button variant="outline" onClick={handleExportPDF}>
                <FileText className="w-4 h-4 ml-2" />
                تصدير PDF
              </Button>
              <Button variant="outline" onClick={handleExportExcel}>
                <Download className="w-4 h-4 ml-2" />
                تصدير Excel
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Movements Table */}
      <Card>
        <CardHeader>
          <CardTitle>تفاصيل الحركات</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex justify-center items-center py-8">
              <Spinner />
            </div>
          ) : movements.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">التاريخ</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">نوع الحركة</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الفرع</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الكمية</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">رقم المرجع</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">ملاحظات</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">المستخدم</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {movements.map((movement) => (
                    <tr key={movement.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-sm">
                        <div className="flex items-center gap-2">
                          <Calendar className="w-4 h-4 text-gray-400" />
                          {new Date(movement.created_at).toLocaleDateString('ar-EG')}
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        {getMovementTypeBadge(movement.movement_type)}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-900">{movement.branch_name}</td>
                      <td className="px-4 py-3 text-sm font-bold">
                        {movement.quantity} {product?.unit}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-600">{movement.reference_number}</td>
                      <td className="px-4 py-3 text-sm text-gray-600">{movement.notes || '-'}</td>
                      <td className="px-4 py-3 text-sm text-gray-600">{movement.user_name}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="text-center py-8">
              <p className="text-gray-500">
                {productId ? 'لا توجد حركات لهذا المنتج' : 'اختر منتج لعرض حركاته'}
              </p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
