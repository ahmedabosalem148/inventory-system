/**
 * Customer Balances Report
 * Display all customer balances and outstanding amounts
 */

import { useState, useEffect } from 'react'
import { ArrowLeft, Download, Users, DollarSign, AlertCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { toast } from 'react-hot-toast'
import apiClient from '@/app/axios'

interface CustomerBalance {
  customer_id: number
  customer_name: string
  customer_code: string
  phone: string
  total_sales: number
  total_payments: number
  balance: number
  status: 'paid' | 'partial' | 'unpaid'
  last_transaction_date: string
}

interface BalanceData {
  customers: CustomerBalance[]
  summary: {
    total_customers: number
    total_outstanding: number
    customers_with_balance: number
    average_balance: number
  }
}

export function CustomerBalancesReport() {
  const [loading, setLoading] = useState(true)
  const [data, setData] = useState<BalanceData | null>(null)
  const [filterStatus, setFilterStatus] = useState<string>('all')
  const [exporting, setExporting] = useState(false)

  useEffect(() => {
    loadReport()
  }, [])

  const loadReport = async () => {
    try {
      setLoading(true)
      const response = await apiClient.get<{ data: BalanceData }>('/reports/customers/balances')
      setData(response.data.data)
    } catch (error: any) {
      console.error('Error loading customer balances:', error)
      toast.error(error.response?.data?.message || 'فشل تحميل التقرير')
    } finally {
      setLoading(false)
    }
  }

  const handleExport = async () => {
    try {
      setExporting(true)
      const response = await apiClient.get('/reports/customer-balances/excel', {
        responseType: 'blob',
      })
      
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `customer-balances-${new Date().toISOString().split('T')[0]}.xlsx`)
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

  const handleViewStatement = (customerId: number) => {
    window.location.hash = `#customers/${customerId}`
  }

  const filteredCustomers = data?.customers.filter(customer => {
    if (filterStatus === 'all') return true
    return customer.status === filterStatus
  }) || []

  const getStatusBadge = (status: CustomerBalance['status']) => {
    const badges = {
      paid: { variant: 'success' as const, label: 'مسدد' },
      partial: { variant: 'warning' as const, label: 'جزئي' },
      unpaid: { variant: 'danger' as const, label: 'غير مسدد' },
    }
    return badges[status]
  }

  const columns = [
    {
      key: 'customer_code',
      header: 'كود العميل',
      sortable: true,
      render: (row: CustomerBalance) => (
        <span className="font-mono text-sm">{row.customer_code}</span>
      ),
    },
    {
      key: 'customer_name',
      header: 'اسم العميل',
      sortable: true,
      render: (row: CustomerBalance) => (
        <div>
          <div className="font-medium">{row.customer_name}</div>
          <div className="text-sm text-gray-500">{row.phone}</div>
        </div>
      ),
    },
    {
      key: 'total_sales',
      header: 'إجمالي المبيعات',
      sortable: true,
      render: (row: CustomerBalance) => (
        <span className="font-medium">
          {row.total_sales.toLocaleString()} ر.س
        </span>
      ),
    },
    {
      key: 'total_payments',
      header: 'إجمالي المدفوعات',
      sortable: true,
      render: (row: CustomerBalance) => (
        <span className="text-green-600 font-medium">
          {row.total_payments.toLocaleString()} ر.س
        </span>
      ),
    },
    {
      key: 'balance',
      header: 'الرصيد المستحق',
      sortable: true,
      render: (row: CustomerBalance) => (
        <span className={`font-bold ${row.balance > 0 ? 'text-red-600' : 'text-green-600'}`}>
          {row.balance.toLocaleString()} ر.س
        </span>
      ),
    },
    {
      key: 'status',
      header: 'الحالة',
      sortable: true,
      render: (row: CustomerBalance) => {
        const badge = getStatusBadge(row.status)
        return <Badge variant={badge.variant}>{badge.label}</Badge>
      },
    },
    {
      key: 'last_transaction_date',
      header: 'آخر معاملة',
      sortable: true,
      render: (row: CustomerBalance) => (
        <span className="text-sm text-gray-600">
          {new Date(row.last_transaction_date).toLocaleDateString('ar-EG')}
        </span>
      ),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (row: CustomerBalance) => (
        <Button
          size="sm"
          variant="outline"
          onClick={() => handleViewStatement(row.customer_id)}
        >
          كشف حساب
        </Button>
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
            <h1 className="text-3xl font-bold">تقرير أرصدة العملاء</h1>
            <p className="text-gray-600 mt-1">
              عرض أرصدة جميع العملاء والمديونيات
            </p>
          </div>
        </div>
        <Button 
          size="sm" 
          variant="outline" 
          onClick={handleExport}
          disabled={exporting || loading}
        >
          <Download className="w-4 h-4 ml-2" />
          {exporting ? 'جاري التصدير...' : 'تصدير Excel'}
        </Button>
      </div>

      {/* Summary Cards */}
      {data && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                إجمالي العملاء
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-2">
                <Users className="w-5 h-5 text-blue-600" />
                <span className="text-2xl font-bold">{data.summary.total_customers}</span>
                <span className="text-sm text-gray-500">عميل</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                عملاء لديهم أرصدة
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-2">
                <AlertCircle className="w-5 h-5 text-orange-600" />
                <span className="text-2xl font-bold">{data.summary.customers_with_balance}</span>
                <span className="text-sm text-gray-500">عميل</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                إجمالي المديونيات
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-2">
                <DollarSign className="w-5 h-5 text-red-600" />
                <span className="text-2xl font-bold text-red-600">
                  {data.summary.total_outstanding.toLocaleString()}
                </span>
                <span className="text-sm text-gray-500">ر.س</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600">
                متوسط الرصيد
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center gap-2">
                <span className="text-2xl font-bold text-purple-600">
                  {data.summary.average_balance.toLocaleString()}
                </span>
                <span className="text-sm text-gray-500">ر.س</span>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Status Filter */}
      <Card>
        <CardContent className="p-4">
          <div className="flex items-center gap-4">
            <Users className="w-5 h-5 text-gray-400" />
            <label className="text-sm font-medium">فلترة حسب الحالة:</label>
            <div className="flex gap-2">
              <Button
                size="sm"
                variant={filterStatus === 'all' ? 'default' : 'outline'}
                onClick={() => setFilterStatus('all')}
              >
                الكل ({data?.customers.length || 0})
              </Button>
              <Button
                size="sm"
                variant={filterStatus === 'unpaid' ? 'default' : 'outline'}
                onClick={() => setFilterStatus('unpaid')}
              >
                غير مسدد ({data?.customers.filter(c => c.status === 'unpaid').length || 0})
              </Button>
              <Button
                size="sm"
                variant={filterStatus === 'partial' ? 'default' : 'outline'}
                onClick={() => setFilterStatus('partial')}
              >
                جزئي ({data?.customers.filter(c => c.status === 'partial').length || 0})
              </Button>
              <Button
                size="sm"
                variant={filterStatus === 'paid' ? 'default' : 'outline'}
                onClick={() => setFilterStatus('paid')}
              >
                مسدد ({data?.customers.filter(c => c.status === 'paid').length || 0})
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Data Table */}
      <Card>
        <CardContent className="p-6">
          <DataTable
            columns={columns}
            data={filteredCustomers}
            loading={loading}
            emptyMessage="لا توجد بيانات"
          />
        </CardContent>
      </Card>

      {/* Alert for Outstanding Balances */}
      {data && data.summary.total_outstanding > 0 && (
        <Card className="bg-red-50 border-red-200">
          <CardContent className="p-4">
            <div className="flex items-start gap-3">
              <AlertCircle className="w-5 h-5 text-red-600 mt-0.5" />
              <div>
                <h3 className="font-semibold text-red-900">تنبيه مهم</h3>
                <p className="text-sm text-red-800 mt-1">
                  إجمالي المديونيات المستحقة: <span className="font-bold">{data.summary.total_outstanding.toLocaleString()} ر.س</span>
                  {' '}من <span className="font-bold">{data.summary.customers_with_balance}</span> عميل.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
