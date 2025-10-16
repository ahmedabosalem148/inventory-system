import { useState, useEffect } from 'react'
import { CreditCard, CheckCircle, XCircle, Clock, AlertCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { showToast } from '@/components/ui/toast'
import { apiClient } from '@/services/api/client'

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
    cheque_date: string
    due_date: string
    status: 'pending' | 'cleared' | 'bounced'
    cleared_at?: string
    return_reason?: string
  }
  notes?: string
  created_by?: string
}

interface PaymentsResponse {
  data: Payment[]
  meta?: {
    current_page: number
    last_page: number
    total: number
  }
}

interface ChequeDisplay {
  id: number
  cheque_number: string
  bank_name: string
  cheque_date: string
  due_date: string
  amount: number
  status: 'pending' | 'cleared' | 'bounced'
  customer: {
    id: number
    name: string
    code: string
  }
  payment_id: number
  payment_date: string
  cleared_at?: string
  return_reason?: string
  notes?: string
}

interface Statistics {
  total_cheques: number
  pending_count: number
  cleared_count: number
  bounced_count: number
  total_amount: number
  pending_amount: number
  cleared_amount: number
  bounced_amount: number
}

export default function ChequesPage() {
  const [cheques, setCheques] = useState<ChequeDisplay[]>([])
  const [statistics, setStatistics] = useState<Statistics>({
    total_cheques: 0,
    pending_count: 0,
    cleared_count: 0,
    bounced_count: 0,
    total_amount: 0,
    pending_amount: 0,
    cleared_amount: 0,
    bounced_amount: 0,
  })
  const [loading, setLoading] = useState(true)
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [selectedCheque, setSelectedCheque] = useState<ChequeDisplay | null>(null)
  const [statusDialogOpen, setStatusDialogOpen] = useState(false)

  useEffect(() => {
    loadCheques()
  }, [statusFilter])

  const loadCheques = async () => {
    try {
      setLoading(true)
      
      // Load cheques based on status filter
      let endpoint = '/payments'
      const params = new URLSearchParams({
        payment_method: 'cheque',
        per_page: '100',
      })

      const response = await apiClient.get<PaymentsResponse>(
        `${endpoint}?${params.toString()}`
      )

      // Extract cheques from payments
      const chequesData: ChequeDisplay[] = response.data.data
        .filter(payment => payment.cheque)
        .map(payment => ({
          id: payment.cheque!.id,
          cheque_number: payment.cheque!.cheque_number,
          bank_name: payment.cheque!.bank_name,
          cheque_date: payment.cheque!.cheque_date,
          due_date: payment.cheque!.due_date,
          amount: payment.amount,
          status: payment.cheque!.status,
          customer: payment.customer,
          payment_id: payment.id,
          payment_date: payment.payment_date,
          cleared_at: payment.cheque!.cleared_at,
          return_reason: payment.cheque!.return_reason,
          notes: payment.notes,
        }))

      setCheques(chequesData)
      calculateStatistics(chequesData)
    } catch (error) {
      showToast.error('فشل تحميل الشيكات')
    } finally {
      setLoading(false)
    }
  }

  const calculateStatistics = (data: ChequeDisplay[]) => {
    const stats = {
      total_cheques: data.length,
      pending_count: data.filter(c => c.status === 'pending').length,
      cleared_count: data.filter(c => c.status === 'cleared').length,
      bounced_count: data.filter(c => c.status === 'bounced').length,
      total_amount: data.reduce((sum, c) => sum + c.amount, 0),
      pending_amount: data.filter(c => c.status === 'pending').reduce((sum, c) => sum + c.amount, 0),
      cleared_amount: data.filter(c => c.status === 'cleared').reduce((sum, c) => sum + c.amount, 0),
      bounced_amount: data.filter(c => c.status === 'bounced').reduce((sum, c) => sum + c.amount, 0),
    }
    setStatistics(stats)
  }

  const getStatusBadge = (status: string) => {
    const badges = {
      pending: { label: 'معلق', variant: 'warning' as const, icon: Clock },
      cleared: { label: 'محصّل', variant: 'success' as const, icon: CheckCircle },
      bounced: { label: 'مرتد', variant: 'danger' as const, icon: XCircle },
    }
    const badge = badges[status as keyof typeof badges] || { label: status, variant: 'default' as const, icon: AlertCircle }
    const Icon = badge.icon
    return (
      <Badge variant={badge.variant} className="flex items-center gap-1">
        <Icon className="h-3 w-3" />
        {badge.label}
      </Badge>
    )
  }

  const handleChangeStatus = (cheque: ChequeDisplay) => {
    setSelectedCheque(cheque)
    setStatusDialogOpen(true)
  }

  const updateChequeStatus = async (status: 'pending' | 'cleared' | 'bounced', notes?: string) => {
    if (!selectedCheque) return

    try {
      await apiClient.put(`/cheques/${selectedCheque.id}/status`, {
        status,
        notes,
      })

      showToast.success('تم تحديث حالة الشيك بنجاح')
      setStatusDialogOpen(false)
      setSelectedCheque(null)
      loadCheques()
    } catch (error: any) {
      const message = error.response?.data?.message || 'فشل تحديث حالة الشيك'
      showToast.error(message)
    }
  }

  const filteredCheques = statusFilter === 'all' 
    ? cheques 
    : cheques.filter(c => c.status === statusFilter)

  const isOverdue = (dueDate: string) => {
    return new Date(dueDate) < new Date() && new Date(dueDate).toDateString() !== new Date().toDateString()
  }

  const columns = [
    {
      key: 'cheque_number',
      header: 'رقم الشيك',
      render: (cheque: ChequeDisplay) => (
        <div>
          <p className="font-mono font-bold">{cheque.cheque_number}</p>
          <p className="text-xs text-gray-500">{cheque.bank_name}</p>
        </div>
      ),
    },
    {
      key: 'customer',
      header: 'العميل',
      render: (cheque: ChequeDisplay) => (
        <div>
          <p className="font-bold">{cheque.customer.name}</p>
          <p className="text-xs text-gray-500">{cheque.customer.code}</p>
        </div>
      ),
    },
    {
      key: 'amount',
      header: 'المبلغ',
      render: (cheque: ChequeDisplay) => (
        <span className="text-xl font-bold text-blue-600">
          {cheque.amount.toFixed(2)} ر.س
        </span>
      ),
    },
    {
      key: 'cheque_date',
      header: 'تاريخ الشيك',
      render: (cheque: ChequeDisplay) => (
        <span className="text-sm">
          {new Date(cheque.cheque_date).toLocaleDateString('ar-EG')}
        </span>
      ),
    },
    {
      key: 'due_date',
      header: 'تاريخ الاستحقاق',
      render: (cheque: ChequeDisplay) => (
        <div>
          <p className={`text-sm font-medium ${
            isOverdue(cheque.due_date) && cheque.status === 'pending' 
              ? 'text-red-600' 
              : ''
          }`}>
            {new Date(cheque.due_date).toLocaleDateString('ar-EG')}
          </p>
          {isOverdue(cheque.due_date) && cheque.status === 'pending' && (
            <Badge variant="danger" className="text-xs mt-1">متأخر</Badge>
          )}
        </div>
      ),
    },
    {
      key: 'status',
      header: 'الحالة',
      render: (cheque: ChequeDisplay) => getStatusBadge(cheque.status),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (cheque: ChequeDisplay) => (
        <div className="flex gap-2">
          {cheque.status === 'pending' && (
            <>
              <Button
                size="sm"
                variant="outline"
                onClick={() => {
                  setSelectedCheque(cheque)
                  updateChequeStatus('cleared')
                }}
              >
                <CheckCircle className="h-4 w-4 ml-1" />
                تحصيل
              </Button>
              <Button
                size="sm"
                variant="outline"
                onClick={() => handleChangeStatus(cheque)}
              >
                <XCircle className="h-4 w-4 ml-1" />
                إرجاع
              </Button>
            </>
          )}
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <CreditCard className="h-8 w-8 text-yellow-600" />
            إدارة الشيكات
          </h1>
          <p className="text-gray-500 mt-1">متابعة وتحصيل الشيكات</p>
        </div>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">إجمالي الشيكات</p>
              <p className="text-2xl font-bold mt-2">{statistics.total_cheques}</p>
              <p className="text-sm text-gray-600 mt-1">
                {statistics.total_amount.toFixed(2)} ر.س
              </p>
            </div>
            <div className="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
              <CreditCard className="h-6 w-6 text-blue-600" />
            </div>
          </div>
        </Card>

        <Card 
          className={`p-6 cursor-pointer hover:shadow-md transition-shadow ${
            statusFilter === 'pending' ? 'ring-2 ring-yellow-500' : ''
          }`}
          onClick={() => setStatusFilter(statusFilter === 'pending' ? 'all' : 'pending')}
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">شيكات معلقة</p>
              <p className="text-2xl font-bold text-yellow-600 mt-2">
                {statistics.pending_count}
              </p>
              <p className="text-sm text-gray-600 mt-1">
                {statistics.pending_amount.toFixed(2)} ر.س
              </p>
            </div>
            <div className="h-12 w-12 bg-yellow-100 rounded-full flex items-center justify-center">
              <Clock className="h-6 w-6 text-yellow-600" />
            </div>
          </div>
        </Card>

        <Card 
          className={`p-6 cursor-pointer hover:shadow-md transition-shadow ${
            statusFilter === 'cleared' ? 'ring-2 ring-green-500' : ''
          }`}
          onClick={() => setStatusFilter(statusFilter === 'cleared' ? 'all' : 'cleared')}
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">شيكات محصّلة</p>
              <p className="text-2xl font-bold text-green-600 mt-2">
                {statistics.cleared_count}
              </p>
              <p className="text-sm text-gray-600 mt-1">
                {statistics.cleared_amount.toFixed(2)} ر.س
              </p>
            </div>
            <div className="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center">
              <CheckCircle className="h-6 w-6 text-green-600" />
            </div>
          </div>
        </Card>

        <Card 
          className={`p-6 cursor-pointer hover:shadow-md transition-shadow ${
            statusFilter === 'bounced' ? 'ring-2 ring-red-500' : ''
          }`}
          onClick={() => setStatusFilter(statusFilter === 'bounced' ? 'all' : 'bounced')}
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">شيكات مرتدة</p>
              <p className="text-2xl font-bold text-red-600 mt-2">
                {statistics.bounced_count}
              </p>
              <p className="text-sm text-gray-600 mt-1">
                {statistics.bounced_amount.toFixed(2)} ر.س
              </p>
            </div>
            <div className="h-12 w-12 bg-red-100 rounded-full flex items-center justify-center">
              <XCircle className="h-6 w-6 text-red-600" />
            </div>
          </div>
        </Card>
      </div>

      {/* Cheques Table */}
      <Card>
        <div className="p-4 border-b flex justify-between items-center">
          <h3 className="text-lg font-bold">
            {statusFilter === 'all' && 'جميع الشيكات'}
            {statusFilter === 'pending' && 'الشيكات المعلقة'}
            {statusFilter === 'cleared' && 'الشيكات المحصّلة'}
            {statusFilter === 'bounced' && 'الشيكات المرتدة'}
            <span className="text-gray-500 mr-2">({filteredCheques.length})</span>
          </h3>
          {statusFilter !== 'all' && (
            <Button variant="outline" size="sm" onClick={() => setStatusFilter('all')}>
              إظهار الكل
            </Button>
          )}
        </div>

        <DataTable
          columns={columns}
          data={filteredCheques}
          loading={loading}
          emptyMessage="لا توجد شيكات"
        />
      </Card>

      {/* Status Change Dialog */}
      {statusDialogOpen && selectedCheque && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 className="text-xl font-bold mb-4">تحديث حالة الشيك</h3>
            <p className="text-gray-600 mb-4">
              شيك رقم: <span className="font-mono font-bold">{selectedCheque.cheque_number}</span>
            </p>
            <div className="space-y-3">
              <Button
                className="w-full"
                variant="outline"
                onClick={() => updateChequeStatus('bounced', 'تم إرجاع الشيك')}
              >
                <XCircle className="h-4 w-4 ml-2" />
                شيك مرتد
              </Button>
              <Button
                className="w-full"
                variant="outline"
                onClick={() => setStatusDialogOpen(false)}
              >
                إلغاء
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
