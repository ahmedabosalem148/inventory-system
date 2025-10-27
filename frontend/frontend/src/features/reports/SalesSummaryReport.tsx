/**
 * Sales Summary Report
 * تقرير ملخص المبيعات - عرض إحصائيات المبيعات خلال فترة زمنية
 */

import { useState, useEffect } from 'react'
import { ArrowLeft, Download, FileText, TrendingUp, DollarSign, ShoppingCart, Users, Percent } from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import { toast } from 'react-hot-toast'
import apiClient from '@/app/axios'

interface SalesSummary {
  total_vouchers: number
  total_sales: number
  total_discounts: number
  average_voucher_value: number
}

interface BranchSales {
  branch_id: number
  branch_name: string
  vouchers_count: number
  total_sales: number
}

interface ProductSales {
  product_id: number
  product_code: string
  product_name: string
  quantity_sold: number
  total_sales: number
}

interface SalesData {
  summary: SalesSummary
  sales_by_branch: BranchSales[]
  top_products: ProductSales[]
}

interface Branch {
  id: number
  name: string
}

interface Customer {
  id: number
  code: string
  name: string
}

export function SalesSummaryReport() {
  const [loading, setLoading] = useState(false)
  const [data, setData] = useState<SalesData | null>(null)
  const [branches, setBranches] = useState<Branch[]>([])
  const [customers, setCustomers] = useState<Customer[]>([])
  const [fromDate, setFromDate] = useState(() => {
    const date = new Date()
    date.setMonth(date.getMonth() - 1)
    return date.toISOString().split('T')[0]
  })
  const [toDate, setToDate] = useState(() => new Date().toISOString().split('T')[0])
  const [branchId, setBranchId] = useState<string>('')
  const [customerId, setCustomerId] = useState<string>('')

  useEffect(() => {
    fetchBranches()
    fetchCustomers()
  }, [])

  const fetchBranches = async () => {
    try {
      const response = await apiClient.get('/branches')
      setBranches(response.data.data || [])
    } catch (error) {
      console.error('Failed to fetch branches:', error)
    }
  }

  const fetchCustomers = async () => {
    try {
      const response = await apiClient.get('/customers')
      setCustomers(response.data.data || [])
    } catch (error) {
      console.error('Failed to fetch customers:', error)
    }
  }

  const fetchReport = async () => {
    if (!fromDate || !toDate) {
      toast.error('الرجاء اختيار الفترة الزمنية')
      return
    }

    setLoading(true)
    try {
      const params: Record<string, string> = {
        from_date: fromDate,
        to_date: toDate,
      }
      if (branchId) params.branch_id = branchId
      if (customerId) params.customer_id = customerId

      const response = await apiClient.get('/reports/sales-summary', { params })
      setData(response.data)
    } catch (error: any) {
      console.error('Failed to fetch report:', error)
      toast.error(error.response?.data?.message || 'فشل في تحميل التقرير')
    } finally {
      setLoading(false)
    }
  }

  const handleReset = () => {
    const date = new Date()
    date.setMonth(date.getMonth() - 1)
    setFromDate(date.toISOString().split('T')[0])
    setToDate(new Date().toISOString().split('T')[0])
    setBranchId('')
    setCustomerId('')
    setData(null)
  }

  const handleExportPDF = async () => {
    if (!fromDate || !toDate) {
      toast.error('الرجاء اختيار الفترة الزمنية')
      return
    }

    try {
      const params: Record<string, string> = {
        from_date: fromDate,
        to_date: toDate,
      }
      if (branchId) params.branch_id = branchId
      if (customerId) params.customer_id = customerId

      const response = await apiClient.get('/reports/sales-summary/pdf', {
        params,
        responseType: 'blob'
      })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', 'sales-summary-report.pdf')
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (error) {
      console.error('Failed to export PDF:', error)
      toast.error('فشل في تصدير PDF')
    }
  }

  const handleExportExcel = async () => {
    if (!fromDate || !toDate) {
      toast.error('الرجاء اختيار الفترة الزمنية')
      return
    }

    try {
      const params: Record<string, string> = {
        from_date: fromDate,
        to_date: toDate,
      }
      if (branchId) params.branch_id = branchId
      if (customerId) params.customer_id = customerId

      const response = await apiClient.get('/reports/sales-summary/excel', {
        params,
        responseType: 'blob'
      })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', 'sales-summary-report.xlsx')
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (error) {
      console.error('Failed to export Excel:', error)
      toast.error('فشل في تصدير Excel')
    }
  }

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ar-EG', {
      style: 'currency',
      currency: 'EGP',
      minimumFractionDigits: 2,
    }).format(amount)
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
            <h1 className="text-2xl font-bold text-gray-900">تقرير ملخص المبيعات</h1>
            <p className="text-gray-600 mt-1">
              عرض إحصائيات وتحليل المبيعات خلال فترة زمنية محددة
            </p>
          </div>
        </div>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                من تاريخ <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                value={fromDate}
                onChange={(e) => setFromDate(e.target.value)}
                disabled={loading}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                إلى تاريخ <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                value={toDate}
                onChange={(e) => setToDate(e.target.value)}
                disabled={loading}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                الفرع
              </label>
              <select
                value={branchId}
                onChange={(e) => setBranchId(e.target.value)}
                disabled={loading}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">جميع الفروع</option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id.toString()}>
                    {branch.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                العميل
              </label>
              <select
                value={customerId}
                onChange={(e) => setCustomerId(e.target.value)}
                disabled={loading}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">جميع العملاء</option>
                {customers.map((customer) => (
                  <option key={customer.id} value={customer.id.toString()}>
                    {customer.code} - {customer.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="flex gap-3 mt-4">
            <Button
              onClick={fetchReport}
              disabled={loading}
              className="flex items-center gap-2"
            >
              {loading ? <Spinner className="w-4 h-4" /> : <TrendingUp className="w-4 h-4" />}
              {loading ? 'جاري التحميل...' : 'عرض التقرير'}
            </Button>
            <Button
              variant="outline"
              onClick={handleReset}
              disabled={loading}
            >
              إعادة تعيين
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Summary Cards */}
      {data && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-sm text-gray-600 mb-1">عدد الفواتير</div>
                  <div className="text-2xl font-bold text-gray-900">
                    {data.summary.total_vouchers}
                  </div>
                </div>
                <ShoppingCart className="w-8 h-8 text-blue-500" />
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-sm text-gray-600 mb-1">إجمالي المبيعات</div>
                  <div className="text-2xl font-bold text-green-600">
                    {formatCurrency(data.summary.total_sales)}
                  </div>
                </div>
                <DollarSign className="w-8 h-8 text-green-500" />
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-sm text-gray-600 mb-1">إجمالي الخصومات</div>
                  <div className="text-2xl font-bold text-red-600">
                    {formatCurrency(data.summary.total_discounts)}
                  </div>
                </div>
                <Percent className="w-8 h-8 text-red-500" />
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-sm text-gray-600 mb-1">متوسط الفاتورة</div>
                  <div className="text-2xl font-bold text-blue-600">
                    {formatCurrency(data.summary.average_voucher_value)}
                  </div>
                </div>
                <TrendingUp className="w-8 h-8 text-blue-500" />
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Export Buttons */}
      {data && (
        <Card>
          <CardContent className="pt-6">
            <div className="flex gap-3">
              <Button
                variant="outline"
                onClick={handleExportPDF}
                className="flex items-center gap-2"
              >
                <FileText className="w-4 h-4" />
                تصدير PDF
              </Button>
              <Button
                variant="outline"
                onClick={handleExportExcel}
                className="flex items-center gap-2"
              >
                <Download className="w-4 h-4" />
                تصدير Excel
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Sales by Branch */}
      {data && data.sales_by_branch.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>المبيعات حسب الفرع</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      الفرع
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      عدد الفواتير
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      إجمالي المبيعات
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {data.sales_by_branch.map((branch) => (
                    <tr key={branch.branch_id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-sm font-medium text-gray-900">
                        {branch.branch_name}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {branch.vouchers_count}
                      </td>
                      <td className="px-4 py-3 text-sm font-semibold text-green-600">
                        {formatCurrency(branch.total_sales)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Top Products */}
      {data && data.top_products.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>أعلى 20 منتج مبيعاً</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      كود المنتج
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      اسم المنتج
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      الكمية المباعة
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                      إجمالي المبيعات
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {data.top_products.map((product) => (
                    <tr key={product.product_id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {product.product_code}
                      </td>
                      <td className="px-4 py-3 text-sm font-medium text-gray-900">
                        {product.product_name}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {product.quantity_sold}
                      </td>
                      <td className="px-4 py-3 text-sm font-semibold text-green-600">
                        {formatCurrency(product.total_sales)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      )}

      {/* No Data Message */}
      {data && data.summary.total_vouchers === 0 && (
        <Card>
          <CardContent className="py-12">
            <div className="text-center text-gray-500">
              <Users className="w-16 h-16 mx-auto mb-4 opacity-50" />
              <p className="text-lg">لا توجد مبيعات خلال الفترة المحددة</p>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
