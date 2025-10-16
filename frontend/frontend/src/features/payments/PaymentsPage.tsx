import { useState, useEffect } from 'react'
import { DollarSign, Plus, Calendar, Filter } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { showToast } from '@/components/ui/toast'
import { apiClient } from '@/services/api/client'
import PaymentDialog from './PaymentDialog'

interface Payment {
  id: number
  payment_date: string
  amount: number
  payment_method: 'cash' | 'cheque' | 'bank_transfer'
  customer: {
    id: number
    name: string
    code: string
  }
  cheque?: {
    id: number
    cheque_number: string
    bank_name: string
    status: string
  }
  notes?: string
  created_by?: string
}

interface PaymentsResponse {
  data: Payment[]
  meta: {
    current_page: number
    last_page: number
    total: number
  }
}

interface Statistics {
  total_payments: number
  total_amount: number
  cash_total: number
  cheque_total: number
  bank_transfer_total: number
}

export default function PaymentsPage() {
  const [payments, setPayments] = useState<Payment[]>([])
  const [statistics, setStatistics] = useState<Statistics>({
    total_payments: 0,
    total_amount: 0,
    cash_total: 0,
    cheque_total: 0,
    bank_transfer_total: 0,
  })
  const [loading, setLoading] = useState(true)
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [dialogOpen, setDialogOpen] = useState(false)
  
  // Filters
  const [search, setSearch] = useState('')
  const [paymentMethod, setPaymentMethod] = useState('')
  const [fromDate, setFromDate] = useState('')
  const [toDate, setToDate] = useState('')

  useEffect(() => {
    loadPayments()
  }, [currentPage, paymentMethod, fromDate, toDate])

  const loadPayments = async () => {
    try {
      setLoading(true)
      const params = new URLSearchParams({
        page: currentPage.toString(),
        per_page: '15',
      })

      if (paymentMethod) params.append('payment_method', paymentMethod)
      if (fromDate) params.append('from_date', fromDate)
      if (toDate) params.append('to_date', toDate)

      const response = await apiClient.get<PaymentsResponse>(
        `/payments?${params.toString()}`
      )

      setPayments(response.data.data)
      setCurrentPage(response.data.meta.current_page)
      setTotalPages(response.data.meta.last_page)

      // Calculate statistics
      calculateStatistics(response.data.data)
    } catch (error) {
      showToast.error('فشل تحميل المدفوعات')
    } finally {
      setLoading(false)
    }
  }

  const calculateStatistics = (data: Payment[]) => {
    const stats = {
      total_payments: data.length,
      total_amount: data.reduce((sum, p) => sum + p.amount, 0),
      cash_total: data.filter(p => p.payment_method === 'cash').reduce((sum, p) => sum + p.amount, 0),
      cheque_total: data.filter(p => p.payment_method === 'cheque').reduce((sum, p) => sum + p.amount, 0),
      bank_transfer_total: data.filter(p => p.payment_method === 'bank_transfer').reduce((sum, p) => sum + p.amount, 0),
    }
    setStatistics(stats)
  }

  const getPaymentMethodBadge = (method: string) => {
    const badges = {
      cash: { label: 'نقدي', variant: 'success' as const },
      cheque: { label: 'شيك', variant: 'warning' as const },
      bank_transfer: { label: 'تحويل بنكي', variant: 'info' as const },
    }
    const badge = badges[method as keyof typeof badges] || { label: method, variant: 'default' as const }
    return <Badge variant={badge.variant}>{badge.label}</Badge>
  }

  const filteredPayments = payments.filter(payment => {
    if (!search) return true
    return (
      payment.customer.name.toLowerCase().includes(search.toLowerCase()) ||
      payment.customer.code.toLowerCase().includes(search.toLowerCase()) ||
      payment.cheque?.cheque_number?.toLowerCase().includes(search.toLowerCase())
    )
  })

  const columns = [
    {
      key: 'payment_date',
      header: 'التاريخ',
      render: (payment: Payment) => (
        <span className="text-sm">
          {new Date(payment.payment_date).toLocaleDateString('ar-EG')}
        </span>
      ),
    },
    {
      key: 'customer',
      header: 'العميل',
      render: (payment: Payment) => (
        <div>
          <p className="font-bold">{payment.customer.name}</p>
          <p className="text-xs text-gray-500">{payment.customer.code}</p>
        </div>
      ),
    },
    {
      key: 'amount',
      header: 'المبلغ',
      render: (payment: Payment) => (
        <span className="text-xl font-bold text-green-600">
          {payment.amount.toFixed(2)} ر.س
        </span>
      ),
    },
    {
      key: 'payment_method',
      header: 'طريقة الدفع',
      render: (payment: Payment) => getPaymentMethodBadge(payment.payment_method),
    },
    {
      key: 'cheque',
      header: 'رقم الشيك',
      render: (payment: Payment) => (
        <div>
          {payment.cheque ? (
            <div>
              <p className="font-mono text-sm">{payment.cheque.cheque_number}</p>
              <p className="text-xs text-gray-500">{payment.cheque.bank_name}</p>
            </div>
          ) : (
            '-'
          )}
        </div>
      ),
    },
    {
      key: 'notes',
      header: 'ملاحظات',
      render: (payment: Payment) => (
        <span className="text-sm text-gray-500">{payment.notes || '-'}</span>
      ),
    },
  ]

  const handleAddPayment = () => {
    setDialogOpen(true)
  }

  const handleDialogClose = () => {
    setDialogOpen(false)
  }

  const handleDialogSuccess = () => {
    loadPayments()
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <DollarSign className="h-8 w-8 text-green-600" />
            المدفوعات
          </h1>
          <p className="text-gray-500 mt-1">إدارة مدفوعات العملاء</p>
        </div>
        <Button onClick={handleAddPayment}>
          <Plus className="h-4 w-4 ml-2" />
          تسجيل دفعة جديدة
        </Button>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">إجمالي المدفوعات</p>
              <p className="text-2xl font-bold mt-2">{statistics.total_payments}</p>
            </div>
            <div className="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
              <DollarSign className="h-6 w-6 text-blue-600" />
            </div>
          </div>
        </Card>

        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">إجمالي المبالغ</p>
              <p className="text-2xl font-bold text-green-600 mt-2">
                {statistics.total_amount.toFixed(2)} ر.س
              </p>
            </div>
          </div>
        </Card>

        <Card className="p-6">
          <div>
            <p className="text-sm text-gray-500">نقدي</p>
            <p className="text-xl font-bold text-green-600 mt-2">
              {statistics.cash_total.toFixed(2)} ر.س
            </p>
          </div>
        </Card>

        <Card className="p-6">
          <div>
            <p className="text-sm text-gray-500">شيكات</p>
            <p className="text-xl font-bold text-yellow-600 mt-2">
              {statistics.cheque_total.toFixed(2)} ر.س
            </p>
          </div>
        </Card>

        <Card className="p-6">
          <div>
            <p className="text-sm text-gray-500">تحويلات بنكية</p>
            <p className="text-xl font-bold text-blue-600 mt-2">
              {statistics.bank_transfer_total.toFixed(2)} ر.س
            </p>
          </div>
        </Card>
      </div>

      {/* Filters */}
      <Card className="p-4">
        <div className="flex items-center gap-2 mb-4">
          <Filter className="h-5 w-5 text-gray-500" />
          <h3 className="font-bold">الفلاتر</h3>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Input
            placeholder="بحث بالعميل أو رقم الشيك..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />

          <select
            value={paymentMethod}
            onChange={(e) => setPaymentMethod(e.target.value)}
            className="w-full px-4 py-2 border rounded-md"
          >
            <option value="">جميع طرق الدفع</option>
            <option value="cash">نقدي</option>
            <option value="cheque">شيك</option>
            <option value="bank_transfer">تحويل بنكي</option>
          </select>

          <div className="relative">
            <Calendar className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              type="date"
              value={fromDate}
              onChange={(e) => setFromDate(e.target.value)}
              placeholder="من تاريخ"
              className="w-full pr-10 pl-4 py-2 border rounded-md"
            />
          </div>

          <div className="relative">
            <Calendar className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              type="date"
              value={toDate}
              onChange={(e) => setToDate(e.target.value)}
              placeholder="إلى تاريخ"
              className="w-full pr-10 pl-4 py-2 border rounded-md"
            />
          </div>
        </div>
      </Card>

      {/* Payments Table */}
      <Card>
        <DataTable
          columns={columns}
          data={filteredPayments}
          loading={loading}
          emptyMessage="لا توجد مدفوعات"
        />

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex justify-center gap-2 p-4 border-t">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
              disabled={currentPage === 1}
            >
              السابق
            </Button>
            <span className="flex items-center px-4">
              صفحة {currentPage} من {totalPages}
            </span>
            <Button
              variant="outline"
              size="sm"
              onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
              disabled={currentPage === totalPages}
            >
              التالي
            </Button>
          </div>
        )}
      </Card>

      {/* Payment Dialog */}
      <PaymentDialog
        open={dialogOpen}
        onClose={handleDialogClose}
        onSuccess={handleDialogSuccess}
      />
    </div>
  )
}
