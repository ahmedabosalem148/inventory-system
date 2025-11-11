/**
 * Customer Aging Report
 * Shows customer balances grouped by aging periods (30/60/90/120+ days)
 */

import { useState, useEffect } from 'react'
import { FileText, Download, Loader2, Calendar, Users, DollarSign } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { toast } from 'react-hot-toast'
import apiClient from '@/services/api/client'

interface AgingBucket {
  '0-30': number
  '31-60': number
  '61-90': number
  '91-120': number
  '120+': number
  total: number
}

interface CustomerAging {
  customer_id: number
  customer_name: string
  customer_code: string
  phone?: string
  aging: AgingBucket
}

interface AgingSummary {
  total_customers: number
  total_balance: number
  aging_totals: AgingBucket
}

export function CustomerAgingReport() {
  const [data, setData] = useState<CustomerAging[]>([])
  const [summary, setSummary] = useState<AgingSummary | null>(null)
  const [loading, setLoading] = useState(true)
  const [exporting, setExporting] = useState(false)
  const [asOfDate, setAsOfDate] = useState(new Date().toISOString().split('T')[0])

  useEffect(() => {
    loadReport()
  }, [asOfDate])

  const loadReport = async () => {
    try {
      setLoading(true)
      const response = await apiClient.get('/reports/customer-aging', {
        params: { as_of_date: asOfDate }
      })
      setData(response.data.data.customers || [])
      setSummary(response.data.data.summary || null)
    } catch (error: any) {
      console.error('Error loading aging report:', error)
      toast.error('فشل في تحميل تقرير أعمار الذمم')
    } finally {
      setLoading(false)
    }
  }

  const handleExportExcel = async () => {
    try {
      setExporting(true)
      const response = await apiClient.get('/reports/customer-aging/export', {
        params: { as_of_date: asOfDate, format: 'excel' },
        responseType: 'blob',
      })
      
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `customer-aging-${asOfDate}.xlsx`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      
      toast.success('تم تصدير التقرير بنجاح')
    } catch (error) {
      console.error('Error exporting report:', error)
      toast.error('فشل في تصدير التقرير')
    } finally {
      setExporting(false)
    }
  }

  const handleExportPDF = async () => {
    try {
      setExporting(true)
      const response = await apiClient.get('/reports/customer-aging/export', {
        params: { as_of_date: asOfDate, format: 'pdf' },
        responseType: 'blob',
      })
      
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `customer-aging-${asOfDate}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      
      toast.success('تم تصدير التقرير بنجاح')
    } catch (error) {
      console.error('Error exporting report:', error)
      toast.error('فشل في تصدير التقرير')
    } finally {
      setExporting(false)
    }
  }

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ar-EG', {
      style: 'currency',
      currency: 'EGP',
      minimumFractionDigits: 2,
    }).format(amount)
  }

  const getAgingColor = (days: string): string => {
    if (days === '0-30') return 'text-green-600'
    if (days === '31-60') return 'text-yellow-600'
    if (days === '61-90') return 'text-orange-600'
    if (days === '91-120') return 'text-red-600'
    return 'text-red-800 font-bold'
  }

  const columns = [
    {
      key: 'customer_code',
      header: 'كود العميل',
      sortable: true,
      render: (row: CustomerAging) => (
        <span className="font-mono text-sm">{row.customer_code}</span>
      ),
    },
    {
      key: 'customer_name',
      header: 'اسم العميل',
      sortable: true,
      render: (row: CustomerAging) => (
        <div>
          <div className="font-medium">{row.customer_name}</div>
          {row.phone && (
            <div className="text-xs text-gray-500">{row.phone}</div>
          )}
        </div>
      ),
    },
    {
      key: '0-30',
      header: '0-30 يوم',
      sortable: true,
      render: (row: CustomerAging) => (
        <span className={getAgingColor('0-30')}>
          {formatCurrency(row.aging['0-30'])}
        </span>
      ),
    },
    {
      key: '31-60',
      header: '31-60 يوم',
      sortable: true,
      render: (row: CustomerAging) => (
        <span className={getAgingColor('31-60')}>
          {formatCurrency(row.aging['31-60'])}
        </span>
      ),
    },
    {
      key: '61-90',
      header: '61-90 يوم',
      sortable: true,
      render: (row: CustomerAging) => (
        <span className={getAgingColor('61-90')}>
          {formatCurrency(row.aging['61-90'])}
        </span>
      ),
    },
    {
      key: '91-120',
      header: '91-120 يوم',
      sortable: true,
      render: (row: CustomerAging) => (
        <span className={getAgingColor('91-120')}>
          {formatCurrency(row.aging['91-120'])}
        </span>
      ),
    },
    {
      key: '120+',
      header: '120+ يوم',
      sortable: true,
      render: (row: CustomerAging) => (
        <span className={getAgingColor('120+')}>
          {formatCurrency(row.aging['120+'])}
        </span>
      ),
    },
    {
      key: 'total',
      header: 'الإجمالي',
      sortable: true,
      render: (row: CustomerAging) => (
        <span className="font-bold text-gray-900">
          {formatCurrency(row.aging.total)}
        </span>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <FileText className="h-8 w-8 text-blue-600" />
            تقرير أعمار الذمم
          </h1>
          <p className="text-gray-600 mt-1">
            تحليل أرصدة العملاء حسب فترات التقادم
          </p>
        </div>
        <div className="flex gap-2">
          <Button
            onClick={handleExportExcel}
            disabled={exporting || loading}
            variant="outline"
          >
            {exporting ? (
              <Loader2 className="w-4 h-4 ml-2 animate-spin" />
            ) : (
              <Download className="w-4 h-4 ml-2" />
            )}
            Excel
          </Button>
          <Button
            onClick={handleExportPDF}
            disabled={exporting || loading}
            variant="outline"
          >
            {exporting ? (
              <Loader2 className="w-4 h-4 ml-2 animate-spin" />
            ) : (
              <Download className="w-4 h-4 ml-2" />
            )}
            PDF
          </Button>
        </div>
      </div>

      {/* Date Filter */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex items-center gap-4">
            <Calendar className="w-5 h-5 text-gray-400" />
            <div className="flex items-center gap-2">
              <label className="text-sm font-medium text-gray-700">
                التقرير حتى تاريخ:
              </label>
              <input
                type="date"
                value={asOfDate}
                onChange={(e) => setAsOfDate(e.target.value)}
                className="px-3 py-2 border border-gray-300 rounded-md"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Summary Cards */}
      {summary && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">عدد العملاء</p>
                  <p className="text-2xl font-bold text-blue-600">
                    {summary.total_customers}
                  </p>
                </div>
                <Users className="w-8 h-8 text-blue-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">إجمالي الذمم</p>
                  <p className="text-2xl font-bold text-green-600">
                    {formatCurrency(summary.total_balance)}
                  </p>
                </div>
                <DollarSign className="w-8 h-8 text-green-600" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">المتأخرات (+90 يوم)</p>
                  <p className="text-2xl font-bold text-red-600">
                    {formatCurrency(
                      summary.aging_totals['91-120'] + summary.aging_totals['120+']
                    )}
                  </p>
                </div>
                <Badge variant="danger" className="text-lg px-3 py-1">
                  متأخر
                </Badge>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Aging Breakdown Chart */}
      {summary && (
        <Card>
          <CardHeader>
            <CardTitle>توزيع الذمم حسب الفترة</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {Object.entries(summary.aging_totals)
                .filter(([key]) => key !== 'total')
                .map(([period, amount]) => {
                  const percentage = summary.total_balance > 0
                    ? ((amount as number) / summary.total_balance) * 100
                    : 0
                  
                  return (
                    <div key={period} className="space-y-1">
                      <div className="flex items-center justify-between text-sm">
                        <span className="font-medium">{period} يوم</span>
                        <span className={getAgingColor(period)}>
                          {formatCurrency(amount as number)} ({percentage.toFixed(1)}%)
                        </span>
                      </div>
                      <div className="w-full bg-gray-200 rounded-full h-2">
                        <div
                          className={`h-2 rounded-full ${
                            period === '0-30'
                              ? 'bg-green-500'
                              : period === '31-60'
                              ? 'bg-yellow-500'
                              : period === '61-90'
                              ? 'bg-orange-500'
                              : 'bg-red-500'
                          }`}
                          style={{ width: `${percentage}%` }}
                        />
                      </div>
                    </div>
                  )
                })}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Data Table */}
      <Card>
        <DataTable
          columns={columns}
          data={data}
          loading={loading}
          emptyMessage="لا توجد ذمم على العملاء"
        />
      </Card>
    </div>
  )
}
