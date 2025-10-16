import { useState, useEffect } from 'react'
import { Plus, Users, TrendingUp, TrendingDown, Minus, FileText } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { showToast } from '@/components/ui/toast'
import { apiClient } from '@/services/api/client'
import CustomerDialog from './CustomerDialog'

interface Customer {
  id: number
  code: string
  name: string
  phone?: string
  address?: string
  balance: number
  status: 'debtor' | 'creditor' | 'zero'
  last_activity_at?: string
  purchases_count: number
  purchases_total: number
  returns_count: number
  returns_total: number
  payments_total: number
}

interface CustomersResponse {
  customers: Customer[]
  statistics: {
    total_customers: number
    debtors_count: number
    creditors_count: number
    zero_balance_count: number
  }
}

export default function CustomersPage() {
  const [customers, setCustomers] = useState<Customer[]>([])
  const [statistics, setStatistics] = useState({
    total_customers: 0,
    debtors_count: 0,
    creditors_count: 0,
    zero_balance_count: 0,
  })
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [onlyWithBalance, setOnlyWithBalance] = useState(false)
  const [sortBy, setSortBy] = useState('name')
  const [dialogOpen, setDialogOpen] = useState(false)
  const [selectedCustomer, setSelectedCustomer] = useState<Customer | null>(null)

  useEffect(() => {
    loadCustomers()
  }, [search, onlyWithBalance, sortBy])

  const loadCustomers = async () => {
    try {
      setLoading(true)
      const params = new URLSearchParams()
      if (search) params.append('search', search)
      if (onlyWithBalance) params.append('only_with_balance', '1')
      params.append('sort_by', sortBy)

      const response = await apiClient.get<CustomersResponse>(
        `/customers-balances?${params.toString()}`
      )
      
      setCustomers(response.data.customers)
      setStatistics(response.data.statistics)
    } catch (error) {
      showToast.error('فشل تحميل بيانات العملاء')
    } finally {
      setLoading(false)
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

  const handleAddCustomer = () => {
    setSelectedCustomer(null)
    setDialogOpen(true)
  }

  const handleDialogClose = () => {
    setDialogOpen(false)
    setSelectedCustomer(null)
  }

  const handleDialogSuccess = () => {
    loadCustomers()
  }

  const columns = [
    {
      key: 'code',
      header: 'الكود',
      render: (customer: Customer) => (
        <span className="font-mono text-sm">{customer.code}</span>
      ),
    },
    {
      key: 'name',
      header: 'اسم العميل',
      render: (customer: Customer) => (
        <div>
          <p className="font-bold">{customer.name}</p>
          {customer.phone && (
            <p className="text-sm text-gray-500">{customer.phone}</p>
          )}
        </div>
      ),
    },
    {
      key: 'balance',
      header: 'الرصيد',
      render: (customer: Customer) => (
        <div className="text-left">
          <p className={`text-xl font-bold ${getBalanceColor(customer.balance)}`}>
            {Math.abs(customer.balance).toFixed(2)} ر.س
          </p>
          {getStatusBadge(customer.status)}
        </div>
      ),
    },
    {
      key: 'purchases',
      header: 'المشتريات',
      render: (customer: Customer) => (
        <div className="text-sm">
          <p className="font-bold">{customer.purchases_count} معاملة</p>
          <p className="text-gray-500">{customer.purchases_total.toFixed(2)} ر.س</p>
        </div>
      ),
    },
    {
      key: 'last_activity',
      header: 'آخر نشاط',
      render: (customer: Customer) => (
        <span className="text-sm text-gray-500">
          {customer.last_activity_at
            ? new Date(customer.last_activity_at).toLocaleDateString('ar-EG')
            : '-'}
        </span>
      ),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (customer: Customer) => (
        <div className="flex gap-2">
          <Button
            size="sm"
            variant="outline"
            onClick={() => window.location.hash = `#customers/${customer.id}`}
          >
            <FileText className="h-4 w-4 ml-2" />
            كشف حساب
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold">دفتر العملاء</h1>
          <p className="text-gray-500 mt-1">إدارة العملاء وأرصدتهم</p>
        </div>
        <Button onClick={handleAddCustomer}>
          <Plus className="h-4 w-4 ml-2" />
          إضافة عميل جديد
        </Button>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">إجمالي العملاء</p>
              <p className="text-3xl font-bold mt-2">{statistics.total_customers}</p>
            </div>
            <div className="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
              <Users className="h-6 w-6 text-blue-600" />
            </div>
          </div>
        </Card>

        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">عملاء مدينون</p>
              <p className="text-3xl font-bold text-red-600 mt-2">
                {statistics.debtors_count}
              </p>
            </div>
            <div className="h-12 w-12 bg-red-100 rounded-full flex items-center justify-center">
              <TrendingUp className="h-6 w-6 text-red-600" />
            </div>
          </div>
        </Card>

        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">عملاء دائنون</p>
              <p className="text-3xl font-bold text-green-600 mt-2">
                {statistics.creditors_count}
              </p>
            </div>
            <div className="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center">
              <TrendingDown className="h-6 w-6 text-green-600" />
            </div>
          </div>
        </Card>

        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">عملاء متوازنون</p>
              <p className="text-3xl font-bold text-gray-600 mt-2">
                {statistics.zero_balance_count}
              </p>
            </div>
            <div className="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center">
              <Minus className="h-6 w-6 text-gray-600" />
            </div>
          </div>
        </Card>
      </div>

      {/* Filters */}
      <Card className="p-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Input
            placeholder="بحث بالاسم أو الكود أو الهاتف..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />

          <select
            value={sortBy}
            onChange={(e) => setSortBy(e.target.value)}
            className="w-full px-4 py-2 border rounded-md"
          >
            <option value="name">ترتيب حسب الاسم</option>
            <option value="balance">ترتيب حسب الرصيد</option>
            <option value="last_activity">ترتيب حسب آخر نشاط</option>
          </select>

          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="onlyWithBalance"
              checked={onlyWithBalance}
              onChange={(e) => setOnlyWithBalance(e.target.checked)}
              className="h-4 w-4 rounded border-gray-300"
            />
            <label htmlFor="onlyWithBalance" className="text-sm">
              إظهار العملاء بأرصدة فقط
            </label>
          </div>
        </div>
      </Card>

      {/* Customers Table */}
      <Card>
        <DataTable
          columns={columns}
          data={customers}
          loading={loading}
          emptyMessage="لا توجد بيانات عملاء"
        />
      </Card>

      {/* Customer Dialog */}
      <CustomerDialog
        open={dialogOpen}
        onClose={handleDialogClose}
        customer={selectedCustomer}
        onSuccess={handleDialogSuccess}
      />
    </div>
  )
}
