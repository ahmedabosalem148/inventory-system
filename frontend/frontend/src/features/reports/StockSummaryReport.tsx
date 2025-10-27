import { useState, useEffect } from 'react'
import { Download, Filter, Package, ChevronDown, ChevronRight, X, FileText } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Spinner } from '@/components/ui/spinner'
import { toast } from 'react-hot-toast'
import apiClient from '@/app/axios'

interface BranchStock {
  branch_id: number
  branch_name: string
  quantity: number
  min_stock: number
  status: 'normal' | 'low' | 'critical' | 'out_of_stock'
}

interface StockSummaryItem {
  product_id: number
  sku: string
  name: string
  category: string
  unit: string
  branches: BranchStock[]
  total_quantity: number
  total_branches: number
  has_low_stock: boolean
}

interface StockSummarySummary {
  total_products: number
  total_quantity: number
  low_stock_items: number
  out_of_stock_items: number
}

interface Branch {
  id: number
  name: string
}

interface Category {
  id: number
  name: string
}

export function StockSummaryReport() {
  const [data, setData] = useState<StockSummaryItem[]>([])
  const [summary, setSummary] = useState<StockSummarySummary | null>(null)
  const [loading, setLoading] = useState(false)
  const [branches, setBranches] = useState<Branch[]>([])
  const [categories, setCategories] = useState<Category[]>([])
  const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set())
  
  const [branchId, setBranchId] = useState<string>('')
  const [categoryId, setCategoryId] = useState<string>('')
  const [search, setSearch] = useState<string>('')

  useEffect(() => {
    fetchBranches()
    fetchCategories()
    fetchReport()
  }, [])

  const fetchBranches = async () => {
    try {
      const response = await apiClient.get('/branches')
      setBranches(response.data.data || [])
    } catch (error) {
      console.error('Failed to fetch branches:', error)
    }
  }

  const fetchCategories = async () => {
    try {
      const response = await apiClient.get('/categories')
      setCategories(response.data.data || [])
    } catch (error) {
      console.error('Failed to fetch categories:', error)
    }
  }

  const fetchReport = async () => {
    setLoading(true)
    try {
      const params: Record<string, string> = {}
      if (branchId) params.branch_id = branchId
      if (categoryId) params.category_id = categoryId
      if (search) params.search = search

      const response = await apiClient.get('/reports/stock-summary', { params })
      setData(response.data.data || [])
      setSummary(response.data.summary || null)
    } catch (error: any) {
      console.error('Failed to fetch report:', error)
      toast.error(error.response?.data?.message || 'فشل في تحميل التقرير')
    } finally {
      setLoading(false)
    }
  }

  const handleFilter = () => fetchReport()

  const handleReset = () => {
    setBranchId('')
    setCategoryId('')
    setSearch('')
    setTimeout(fetchReport, 0)
  }

  const toggleRow = (productId: number) => {
    const newExpanded = new Set(expandedRows)
    if (newExpanded.has(productId)) {
      newExpanded.delete(productId)
    } else {
      newExpanded.add(productId)
    }
    setExpandedRows(newExpanded)
  }

  const handleExportPDF = async () => {
    try {
      const params: Record<string, string> = {}
      if (branchId) params.branch_id = branchId
      if (categoryId) params.category_id = categoryId
      if (search) params.search = search

      const response = await apiClient.get('/reports/stock-summary/pdf', { params, responseType: 'blob' })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', 'stock-summary-report.pdf')
      document.body.appendChild(link)
      link.click()
      link.remove()
      toast.success('تم تصدير التقرير بنجاح')
    } catch (error) {
      toast.error('فشل في تصدير PDF')
    }
  }

  const handleExportExcel = async () => {
    try {
      const params: Record<string, string> = {}
      if (branchId) params.branch_id = branchId
      if (categoryId) params.category_id = categoryId
      if (search) params.search = search

      const response = await apiClient.get('/reports/stock-summary/excel', { params, responseType: 'blob' })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', 'stock-summary-report.xlsx')
      document.body.appendChild(link)
      link.click()
      link.remove()
      toast.success('تم تصدير التقرير بنجاح')
    } catch (error) {
      toast.error('فشل في تصدير Excel')
    }
  }

  const getStatusBadge = (status: string) => {
    const config: Record<string, { variant: any; label: string }> = {
      normal: { variant: 'default', label: 'عادي' },
      low: { variant: 'warning', label: 'منخفض' },
      critical: { variant: 'danger', label: 'حرج' },
      out_of_stock: { variant: 'danger', label: 'نفذ' },
    }
    const s = config[status] || config.normal
    return <Badge variant={s.variant}>{s.label}</Badge>
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900 mb-2">تقرير إجمالي المخزون</h1>
        <p className="text-gray-600">عرض المخزون الحالي لجميع المنتجات في جميع الفروع</p>
      </div>

      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الفرع</label>
              <select value={branchId} onChange={(e) => setBranchId(e.target.value)} disabled={loading} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                <option value="">جميع الفروع</option>
                {branches.map((b) => (<option key={b.id} value={b.id}>{b.name}</option>))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">الفئة</label>
              <select value={categoryId} onChange={(e) => setCategoryId(e.target.value)} disabled={loading} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                <option value="">جميع الفئات</option>
                {categories.map((c) => (<option key={c.id} value={c.id}>{c.name}</option>))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">بحث</label>
              <input type="text" placeholder="اسم أو رمز المنتج" value={search} onChange={(e) => setSearch(e.target.value)} disabled={loading} className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" />
            </div>
            <div className="flex items-end gap-2">
              <Button onClick={handleFilter} disabled={loading}><Filter className="w-4 h-4 ml-2" />{loading ? 'جاري...' : 'فلترة'}</Button>
              <Button variant="outline" onClick={handleReset} disabled={loading}><X className="w-4 h-4" /></Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {summary && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card><CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-gray-600">عدد المنتجات</CardTitle></CardHeader><CardContent><div className="flex items-center gap-2"><Package className="w-5 h-5 text-blue-600" /><span className="text-2xl font-bold">{summary.total_products}</span></div></CardContent></Card>
          <Card><CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-gray-600">إجمالي الكمية</CardTitle></CardHeader><CardContent><span className="text-2xl font-bold text-blue-600">{summary.total_quantity.toFixed(2)}</span></CardContent></Card>
          <Card><CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-gray-600">منتجات منخفضة</CardTitle></CardHeader><CardContent><Badge variant="warning" className="text-lg px-3 py-1">{summary.low_stock_items}</Badge></CardContent></Card>
          <Card><CardHeader className="pb-2"><CardTitle className="text-sm font-medium text-gray-600">منتجات نفذت</CardTitle></CardHeader><CardContent><Badge variant="danger" className="text-lg px-3 py-1">{summary.out_of_stock_items}</Badge></CardContent></Card>
        </div>
      )}

      <Card>
        <CardContent className="pt-6">
          <div className="flex gap-3">
            <Button variant="outline" onClick={handleExportPDF}><FileText className="w-4 h-4 ml-2" />تصدير PDF</Button>
            <Button variant="outline" onClick={handleExportExcel}><Download className="w-4 h-4 ml-2" />تصدير Excel</Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>تفاصيل المخزون</CardTitle></CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex justify-center items-center py-8"><Spinner /></div>
          ) : data.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-10"></th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الرمز</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">اسم المنتج</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الفئة</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الوحدة</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">إجمالي الكمية</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">عدد الفروع</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {data.map((item) => (
                    <>
                      <tr key={item.product_id} className="hover:bg-gray-50 cursor-pointer" onClick={() => toggleRow(item.product_id)}>
                        <td className="px-4 py-3">{expandedRows.has(item.product_id) ? (<ChevronDown className="w-4 h-4 text-gray-500" />) : (<ChevronRight className="w-4 h-4 text-gray-500" />)}</td>
                        <td className="px-4 py-3 text-sm text-gray-900">{item.sku}</td>
                        <td className="px-4 py-3 text-sm font-medium text-gray-900">{item.name}</td>
                        <td className="px-4 py-3 text-sm text-gray-600">{item.category}</td>
                        <td className="px-4 py-3 text-sm text-gray-600">{item.unit}</td>
                        <td className="px-4 py-3 text-sm font-medium text-blue-600">{item.total_quantity.toFixed(2)}</td>
                        <td className="px-4 py-3 text-sm text-gray-600">{item.total_branches}</td>
                        <td className="px-4 py-3 text-sm">{item.has_low_stock ? (<Badge variant="warning">منخفض</Badge>) : (<Badge variant="default">عادي</Badge>)}</td>
                      </tr>
                      {expandedRows.has(item.product_id) && (
                        <tr>
                          <td colSpan={8} className="px-4 py-3 bg-gray-50">
                            <div className="ml-8">
                              <h4 className="text-sm font-medium text-gray-700 mb-2">تفاصيل الفروع:</h4>
                              <table className="min-w-full text-sm">
                                <thead><tr className="border-b border-gray-200"><th className="text-right py-2 px-3 font-medium text-gray-600">الفرع</th><th className="text-right py-2 px-3 font-medium text-gray-600">الكمية</th><th className="text-right py-2 px-3 font-medium text-gray-600">الحد الأدنى</th><th className="text-right py-2 px-3 font-medium text-gray-600">الحالة</th></tr></thead>
                                <tbody>{item.branches.map((b, i) => (<tr key={i} className="border-b border-gray-100"><td className="py-2 px-3 text-gray-900">{b.branch_name}</td><td className="py-2 px-3 font-medium text-gray-900">{b.quantity.toFixed(2)}</td><td className="py-2 px-3 text-gray-600">{b.min_stock.toFixed(2)}</td><td className="py-2 px-3">{getStatusBadge(b.status)}</td></tr>))}</tbody>
                              </table>
                            </div>
                          </td>
                        </tr>
                      )}
                    </>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500">لا توجد بيانات</div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
