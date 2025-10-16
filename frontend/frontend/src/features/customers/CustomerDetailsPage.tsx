import { useState, useEffect } from 'react'
import { ArrowLeft, Printer, FileDown, Calendar } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { showToast } from '@/components/ui/toast'
import { apiClient } from '@/services/api/client'

interface Customer {
  id: number
  code: string
  name: string
  phone?: string
  address?: string
  balance: number
  status: 'debtor' | 'creditor' | 'zero'
  purchases_count: number
  purchases_total: number
  returns_count: number
  returns_total: number
  payments_total: number
}

interface LedgerEntry {
  id: number
  date: string
  description: string
  debit_aliah: number
  credit_lah: number
  running_balance: number
  reference_type: string
  reference_id: number
}

interface StatementResponse {
  customer: Customer
  opening_balance: number
  entries: LedgerEntry[]
  total_debit: number
  total_credit: number
  closing_balance: number
}

export default function CustomerDetailsPage() {
  // Get ID from hash: #customers/123
  const hash = window.location.hash.slice(1)
  const id = hash.split('/')[1]

  const [customer, setCustomer] = useState<Customer | null>(null)
  const [entries, setEntries] = useState<LedgerEntry[]>([])
  const [openingBalance, setOpeningBalance] = useState(0)
  const [totalDebit, setTotalDebit] = useState(0)
  const [totalCredit, setTotalCredit] = useState(0)
  const [closingBalance, setClosingBalance] = useState(0)
  const [loading, setLoading] = useState(true)

  // Filters
  const [fromDate, setFromDate] = useState(() => {
    const date = new Date()
    date.setMonth(date.getMonth() - 1)
    return date.toISOString().split('T')[0]
  })
  const [toDate, setToDate] = useState(() => {
    return new Date().toISOString().split('T')[0]
  })

  useEffect(() => {
    loadStatement()
  }, [id])

  const loadStatement = async () => {
    if (!id) return

    try {
      setLoading(true)
      const params = new URLSearchParams({
        from_date: fromDate,
        to_date: toDate,
      })

      const response = await apiClient.get<StatementResponse>(
        `/customers/${id}/statement?${params.toString()}`
      )

      setCustomer(response.data.customer)
      setEntries(response.data.entries)
      setOpeningBalance(response.data.opening_balance)
      setTotalDebit(response.data.total_debit)
      setTotalCredit(response.data.total_credit)
      setClosingBalance(response.data.closing_balance)
    } catch (error) {
      showToast.error('فشل تحميل كشف الحساب')
    } finally {
      setLoading(false)
    }
  }

  const handlePrintPDF = async () => {
    try {
      const params = new URLSearchParams({
        from_date: fromDate,
        to_date: toDate,
      })
      
      window.open(
        `${import.meta.env.VITE_API_URL}/customers/${id}/statement/pdf?${params.toString()}`,
        '_blank'
      )
    } catch (error) {
      showToast.error('فشل طباعة كشف الحساب')
    }
  }

  const handleExportExcel = async () => {
    try {
      const params = new URLSearchParams({
        from_date: fromDate,
        to_date: toDate,
      })
      
      window.open(
        `${import.meta.env.VITE_API_URL}/customers/${id}/statement/excel?${params.toString()}`,
        '_blank'
      )
    } catch (error) {
      showToast.error('فشل تصدير كشف الحساب')
    }
  }

  const getBalanceColor = (balance: number) => {
    if (balance > 0) return 'text-red-600'
    if (balance < 0) return 'text-green-600'
    return 'text-gray-600'
  }

  const getStatusBadge = (status: string) => {
    const statusMap = {
      debtor: { label: 'مدين', variant: 'danger' as const },
      creditor: { label: 'دائن', variant: 'success' as const },
      zero: { label: 'متوازن', variant: 'secondary' as const },
    }
    const statusInfo = statusMap[status as keyof typeof statusMap]
    return <Badge variant={statusInfo.variant}>{statusInfo.label}</Badge>
  }

  const columns = [
    {
      key: 'date',
      header: 'التاريخ',
      render: (entry: LedgerEntry) => (
        <span className="text-sm">
          {new Date(entry.date).toLocaleDateString('ar-EG')}
        </span>
      ),
    },
    {
      key: 'description',
      header: 'البيان',
      render: (entry: LedgerEntry) => (
        <div>
          <p className="font-medium">{entry.description}</p>
          <p className="text-xs text-gray-500">
            {entry.reference_type} #{entry.reference_id}
          </p>
        </div>
      ),
    },
    {
      key: 'debit',
      header: 'علية (Debit)',
      render: (entry: LedgerEntry) => (
        <span className="text-red-600 font-bold">
          {entry.debit_aliah > 0 ? entry.debit_aliah.toFixed(2) : '-'}
        </span>
      ),
    },
    {
      key: 'credit',
      header: 'له (Credit)',
      render: (entry: LedgerEntry) => (
        <span className="text-green-600 font-bold">
          {entry.credit_lah > 0 ? entry.credit_lah.toFixed(2) : '-'}
        </span>
      ),
    },
    {
      key: 'balance',
      header: 'الرصيد',
      render: (entry: LedgerEntry) => (
        <span className={`font-bold text-lg ${getBalanceColor(entry.running_balance)}`}>
          {Math.abs(entry.running_balance).toFixed(2)} ر.س
        </span>
      ),
    },
  ]

  if (!customer) {
    return <div className="text-center py-12">جاري التحميل...</div>
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-start">
        <div>
          <Button
            variant="outline"
            size="sm"
            onClick={() => window.location.hash = '#customers'}
            className="mb-4"
          >
            <ArrowLeft className="h-4 w-4 ml-2" />
            العودة للقائمة
          </Button>
          <h1 className="text-3xl font-bold">كشف حساب عميل</h1>
          <p className="text-gray-500 mt-1">
            {customer.name} ({customer.code})
          </p>
        </div>
      </div>

      {/* Customer Info Card */}
      <Card className="p-6">
        <h2 className="text-xl font-bold mb-4">معلومات العميل</h2>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div>
            <label className="text-sm text-gray-500">الاسم</label>
            <p className="font-bold text-lg mt-1">{customer.name}</p>
          </div>
          <div>
            <label className="text-sm text-gray-500">الكود</label>
            <p className="font-mono mt-1">{customer.code}</p>
          </div>
          <div>
            <label className="text-sm text-gray-500">الهاتف</label>
            <p className="mt-1">{customer.phone || '-'}</p>
          </div>
          <div>
            <label className="text-sm text-gray-500">الرصيد الحالي</label>
            <div className="mt-1">
              <p className={`text-3xl font-bold ${getBalanceColor(customer.balance)}`}>
                {Math.abs(customer.balance).toFixed(2)} ر.س
              </p>
              {getStatusBadge(customer.status)}
            </div>
          </div>
        </div>
      </Card>

      {/* Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <p className="text-sm text-gray-500">إجمالي المشتريات</p>
          <p className="text-2xl font-bold mt-2">{customer.purchases_count}</p>
          <p className="text-sm text-gray-600 mt-1">
            {customer.purchases_total.toFixed(2)} ر.س
          </p>
        </Card>

        <Card className="p-4">
          <p className="text-sm text-gray-500">إجمالي المرتجعات</p>
          <p className="text-2xl font-bold mt-2">{customer.returns_count}</p>
          <p className="text-sm text-gray-600 mt-1">
            {customer.returns_total.toFixed(2)} ر.س
          </p>
        </Card>

        <Card className="p-4">
          <p className="text-sm text-gray-500">إجمالي المدفوعات</p>
          <p className="text-2xl font-bold text-green-600 mt-2">
            {customer.payments_total.toFixed(2)} ر.س
          </p>
        </Card>

        <Card className="p-4">
          <p className="text-sm text-gray-500">صافي الرصيد</p>
          <p className={`text-2xl font-bold mt-2 ${getBalanceColor(customer.balance)}`}>
            {Math.abs(customer.balance).toFixed(2)} ر.س
          </p>
        </Card>
      </div>

      {/* Filters */}
      <Card className="p-4">
        <div className="flex gap-4 items-end">
          <div className="flex-1">
            <label className="text-sm font-medium mb-2 block">من تاريخ</label>
            <div className="relative">
              <Calendar className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                type="date"
                value={fromDate}
                onChange={(e) => setFromDate(e.target.value)}
                className="w-full pr-10 pl-4 py-2 border rounded-md"
              />
            </div>
          </div>

          <div className="flex-1">
            <label className="text-sm font-medium mb-2 block">إلى تاريخ</label>
            <div className="relative">
              <Calendar className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                type="date"
                value={toDate}
                onChange={(e) => setToDate(e.target.value)}
                className="w-full pr-10 pl-4 py-2 border rounded-md"
              />
            </div>
          </div>

          <Button onClick={loadStatement} disabled={loading}>
            عرض
          </Button>
        </div>
      </Card>

      {/* Summary */}
      <Card className="p-6 bg-gray-50">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div>
            <label className="text-sm text-gray-600">رصيد أول المدة</label>
            <p className="text-2xl font-bold mt-2">
              {openingBalance.toFixed(2)} ر.س
            </p>
          </div>
          <div>
            <label className="text-sm text-gray-600">إجمالي علية (Debit)</label>
            <p className="text-2xl font-bold text-red-600 mt-2">
              {totalDebit.toFixed(2)} ر.س
            </p>
          </div>
          <div>
            <label className="text-sm text-gray-600">إجمالي له (Credit)</label>
            <p className="text-2xl font-bold text-green-600 mt-2">
              {totalCredit.toFixed(2)} ر.س
            </p>
          </div>
          <div>
            <label className="text-sm text-gray-600">رصيد آخر المدة</label>
            <p className={`text-3xl font-bold mt-2 ${getBalanceColor(closingBalance)}`}>
              {Math.abs(closingBalance).toFixed(2)} ر.س
            </p>
          </div>
        </div>
      </Card>

      {/* Ledger Table */}
      <Card>
        <div className="p-4 border-b flex justify-between items-center">
          <h3 className="text-lg font-bold">كشف الحساب</h3>
          <div className="flex gap-2">
            <Button size="sm" onClick={handlePrintPDF}>
              <Printer className="h-4 w-4 ml-2" />
              طباعة PDF
            </Button>
            <Button size="sm" variant="outline" onClick={handleExportExcel}>
              <FileDown className="h-4 w-4 ml-2" />
              تصدير Excel
            </Button>
          </div>
        </div>

        <DataTable
          columns={columns}
          data={entries}
          loading={loading}
          emptyMessage="لا توجد معاملات في الفترة المحددة"
        />
      </Card>
    </div>
  )
}
