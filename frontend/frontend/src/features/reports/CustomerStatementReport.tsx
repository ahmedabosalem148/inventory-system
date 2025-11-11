/**
 * Customer Statement Report
 * تقرير كشف حساب عميل - عرض حركات حساب عميل معين
 */

import { useState } from 'react'
import { ArrowLeft, Download, FileText, Calendar } from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import { toast } from 'react-hot-toast'
import apiClient from '@/app/axios'
import { CustomerSearchSelect } from '@/components/CustomerSearchSelect'

interface LedgerEntry {
  id: number
  date: string
  description: string
  debit: number
  credit: number
  balance: number
  voucher_type: string
  created_by: string | null
}

interface CustomerInfo {
  id: number
  code: string
  name: string
  type: string
}

interface StatementData {
  customer: CustomerInfo
  data: LedgerEntry[]
  summary: {
    total_debits: number
    total_credits: number
    current_balance: number
  }
}

export function CustomerStatementReport() {
  const [loading, setLoading] = useState(false)
  const [data, setData] = useState<StatementData | null>(null)
  const [selectedCustomerId, setSelectedCustomerId] = useState<number | null>(null)
  const [fromDate, setFromDate] = useState('')
  const [toDate, setToDate] = useState('')

  const fetchReport = async () => {
    if (!selectedCustomerId) {
      toast.error('الرجاء اختيار عميل')
      return
    }

    setLoading(true)
    try {
      const params: Record<string, string> = {}
      if (fromDate) params.from_date = fromDate
      if (toDate) params.to_date = toDate

      const response = await apiClient.get(`/customers/${selectedCustomerId}/statement`, { params })
      setData(response.data)
    } catch (error: any) {
      console.error('Failed to fetch report:', error)
      toast.error(error.response?.data?.message || 'فشل في تحميل التقرير')
    } finally {
      setLoading(false)
    }
  }

  const handleReset = () => {
    setSelectedCustomerId(null)
    setFromDate('')
    setToDate('')
    setData(null)
  }

  const handleExportPDF = async () => {
    if (!selectedCustomerId) {
      toast.error('الرجاء اختيار عميل')
      return
    }

    try {
      const params: Record<string, string> = {}
      if (fromDate) params.from_date = fromDate
      if (toDate) params.to_date = toDate

      const response = await apiClient.get(`/customers/${selectedCustomerId}/statement/pdf`, {
        params,
        responseType: 'blob'
      })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `customer-statement-${selectedCustomerId}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (error) {
      console.error('Failed to export PDF:', error)
      toast.error('فشل في تصدير PDF')
    }
  }

  const handleExportExcel = async () => {
    if (!selectedCustomerId) {
      toast.error('الرجاء اختيار عميل')
      return
    }

    try {
      const params: Record<string, string> = {}
      if (fromDate) params.from_date = fromDate
      if (toDate) params.to_date = toDate

      const response = await apiClient.get(`/customers/${selectedCustomerId}/statement/excel`, {
        params,
        responseType: 'blob'
      })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `customer-statement-${selectedCustomerId}.xlsx`)
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

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('ar-EG')
  }

  const getVoucherTypeLabel = (type: string) => {
    const types: Record<string, string> = {
      'sale': 'فاتورة بيع',
      'payment': 'سداد',
      'return': 'مرتجع',
      'opening_balance': 'رصيد افتتاحي',
    }
    return types[type] || type
  }

  return (
    <div className="space-y-6">{/* Header */}
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
            <h1 className="text-2xl font-bold text-gray-900">كشف حساب عميل</h1>
            <p className="text-gray-600 mt-1">
              عرض حركات حساب عميل معين خلال فترة زمنية
            </p>
          </div>
        </div>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            {/* Customer Selector */}
            <div className="md:col-span-2">
              <CustomerSearchSelect
                value={selectedCustomerId}
                onChange={(customerId) => setSelectedCustomerId(customerId)}
                label="العميل"
                placeholder="ابحث عن العميل بالاسم أو الكود..."
                required
              />
            </div>

            {/* From Date */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                من تاريخ
              </label>
              <input
                type="date"
                value={fromDate}
                onChange={(e) => setFromDate(e.target.value)}
                disabled={loading}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            {/* To Date */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                إلى تاريخ
              </label>
              <input
                type="date"
                value={toDate}
                onChange={(e) => setToDate(e.target.value)}
                disabled={loading}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          {/* Action Buttons */}
          <div className="flex gap-3 mt-4">
            <Button
              onClick={fetchReport}
              disabled={loading || !selectedCustomerId}
              className="flex items-center gap-2"
            >
              {loading ? <Spinner className="w-4 h-4" /> : <Calendar className="w-4 h-4" />}
              {loading ? 'جاري التحميل...' : 'عرض الكشف'}
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

      {/* Customer Info Card */}
      {data && (
        <Card>
          <CardContent className="pt-6">
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <div className="text-sm text-gray-600">كود العميل</div>
                <div className="text-lg font-semibold">{data.customer.code}</div>
              </div>
              <div>
                <div className="text-sm text-gray-600">اسم العميل</div>
                <div className="text-lg font-semibold">{data.customer.name}</div>
              </div>
              <div>
                <div className="text-sm text-gray-600">نوع العميل</div>
                <div className="text-lg font-semibold">
                  {data.customer.type === 'retail' ? 'قطاعي' : 'جملة'}
                </div>
              </div>
              <div>
                <div className="text-sm text-gray-600">الرصيد الحالي</div>
                <div className={`text-lg font-semibold ${
                  data.summary.current_balance > 0 ? 'text-green-600' : 
                  data.summary.current_balance < 0 ? 'text-red-600' : 
                  'text-gray-600'
                }`}>
                  {formatCurrency(Math.abs(data.summary.current_balance))}
                  {data.summary.current_balance > 0 ? ' (له)' : 
                   data.summary.current_balance < 0 ? ' (عليه)' : ''}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Summary Cards */}
      {data && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card>
            <CardContent className="pt-6">
              <div className="text-sm text-gray-600 mb-1">إجمالي المدين</div>
              <div className="text-2xl font-bold text-green-600">
                {formatCurrency(data.summary.total_debits)}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-sm text-gray-600 mb-1">إجمالي الدائن</div>
              <div className="text-2xl font-bold text-red-600">
                {formatCurrency(data.summary.total_credits)}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-sm text-gray-600 mb-1">عدد الحركات</div>
              <div className="text-2xl font-bold text-blue-600">
                {data.data.length}
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

      {/* Statement Table */}
      {data && (
        <Card>
          <CardHeader>
            <CardTitle>حركات الحساب</CardTitle>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="flex justify-center items-center py-8">
                <Spinner />
              </div>
            ) : data.data.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        التاريخ
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        البيان
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        نوع السند
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        مدين
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        دائن
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        الرصيد
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        المستخدم
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {data.data.map((entry) => (
                      <tr key={entry.id} className="hover:bg-gray-50">
                        <td className="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                          {formatDate(entry.date)}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-900">
                          {entry.description}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-600">
                          {getVoucherTypeLabel(entry.voucher_type)}
                        </td>
                        <td className="px-4 py-3 text-sm font-medium text-green-600">
                          {entry.debit > 0 ? formatCurrency(entry.debit) : '-'}
                        </td>
                        <td className="px-4 py-3 text-sm font-medium text-red-600">
                          {entry.credit > 0 ? formatCurrency(entry.credit) : '-'}
                        </td>
                        <td className={`px-4 py-3 text-sm font-semibold ${
                          entry.balance > 0 ? 'text-green-600' : 
                          entry.balance < 0 ? 'text-red-600' : 
                          'text-gray-600'
                        }`}>
                          {formatCurrency(Math.abs(entry.balance))}
                          {entry.balance > 0 ? ' له' : entry.balance < 0 ? ' عليه' : ''}
                        </td>
                        <td className="px-4 py-3 text-sm text-gray-600">
                          {entry.created_by || '-'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500">
                لا توجد حركات لهذا العميل في الفترة المحددة
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  )
}
