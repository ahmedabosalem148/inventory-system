import { useState, useEffect } from 'react'
import { Repeat, Plus, Filter, FileText } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { showToast } from '@/components/ui/toast'
import { apiClient } from '@/services/api/client'
import ReturnVoucherDialog from './ReturnVoucherDialog'

interface ReturnVoucher {
  id: number
  voucher_number: string
  return_date: string
  customer?: {
    id: number
    name: string
    code: string
  }
  customer_name?: string
  branch: {
    id: number
    name: string
  }
  subtotal: number
  discount_amount: number
  net_total: number
  total_amount: number
  status: 'draft' | 'approved'
  reason?: string
  notes?: string
  items: Array<{
    id: number
    product: {
      id: number
      name: string
      code: string
    }
    quantity: number
    unit_price: number
    total: number
  }>
  approved_at?: string
  approved_by?: string
  created_by?: string
}

interface ReturnVouchersResponse {
  data: ReturnVoucher[]
  meta: {
    current_page: number
    last_page: number
    total: number
    per_page: number
  }
}

interface Statistics {
  total_vouchers: number
  draft_count: number
  approved_count: number
  total_amount: number
  draft_amount: number
  approved_amount: number
}

export default function ReturnVouchersPage() {
  const [vouchers, setVouchers] = useState<ReturnVoucher[]>([])
  const [statistics, setStatistics] = useState<Statistics>({
    total_vouchers: 0,
    draft_count: 0,
    approved_count: 0,
    total_amount: 0,
    draft_amount: 0,
    approved_amount: 0,
  })
  const [loading, setLoading] = useState(true)
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [totalRecords, setTotalRecords] = useState(0)
  const [dialogOpen, setDialogOpen] = useState(false)
  
  // Filters
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('')
  const [fromDate, setFromDate] = useState('')
  const [toDate, setToDate] = useState('')

  useEffect(() => {
    loadVouchers()
  }, [currentPage, statusFilter, fromDate, toDate])

  const loadVouchers = async () => {
    try {
      setLoading(true)
      const params = new URLSearchParams({
        page: currentPage.toString(),
        per_page: '15',
      })

      if (search) params.append('search', search)
      if (statusFilter) params.append('status', statusFilter)
      if (fromDate) params.append('from_date', fromDate)
      if (toDate) params.append('to_date', toDate)

      const response = await apiClient.get<ReturnVouchersResponse>(
        `/return-vouchers?${params.toString()}`
      )

      setVouchers(response.data.data)
      setCurrentPage(response.data.meta.current_page)
      setTotalPages(response.data.meta.last_page)
      setTotalRecords(response.data.meta.total)
      calculateStatistics(response.data.data)
    } catch (error) {
      showToast.error('فشل تحميل أذون الإرجاع')
    } finally {
      setLoading(false)
    }
  }

  const calculateStatistics = (data: ReturnVoucher[]) => {
    const stats = {
      total_vouchers: totalRecords || data.length,
      draft_count: data.filter(v => v.status === 'draft').length,
      approved_count: data.filter(v => v.status === 'approved').length,
      total_amount: data.reduce((sum, v) => sum + v.total_amount, 0),
      draft_amount: data.filter(v => v.status === 'draft').reduce((sum, v) => sum + v.total_amount, 0),
      approved_amount: data.filter(v => v.status === 'approved').reduce((sum, v) => sum + v.total_amount, 0),
    }
    setStatistics(stats)
  }

  const handleSearch = () => {
    setCurrentPage(1)
    loadVouchers()
  }

  const getStatusBadge = (status: string) => {
    const badges = {
      draft: { label: 'مسودة', variant: 'warning' as const },
      approved: { label: 'معتمد', variant: 'success' as const },
    }
    const badge = badges[status as keyof typeof badges] || { label: status, variant: 'default' as const }
    return <Badge variant={badge.variant}>{badge.label}</Badge>
  }

  const handleViewDetails = (voucher: ReturnVoucher) => {
    window.location.hash = `return-vouchers/${voucher.id}`
  }

  const handlePrint = async (voucher: ReturnVoucher) => {
    try {
      const response = await apiClient.get(`/return-vouchers/${voucher.id}/print`, {
        responseType: 'blob'
      })
      
      const blob = new Blob([response.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      window.open(url, '_blank')
      
      showToast.success('تم فتح ملف PDF')
    } catch (error) {
      showToast.error('فشل طباعة الإذن')
    }
  }

  const columns = [
    {
      key: 'voucher_number',
      header: 'رقم الإذن',
      render: (voucher: ReturnVoucher) => (
        <div>
          <p className="font-mono font-bold text-orange-600">{voucher.voucher_number}</p>
          <p className="text-xs text-gray-500">
            {new Date(voucher.return_date).toLocaleDateString('ar-EG')}
          </p>
        </div>
      ),
    },
    {
      key: 'customer',
      header: 'العميل',
      render: (voucher: ReturnVoucher) => (
        <div>
          {voucher.customer ? (
            <>
              <p className="font-bold">{voucher.customer.name}</p>
              <p className="text-xs text-gray-500">{voucher.customer.code}</p>
            </>
          ) : (
            <p className="font-bold">{voucher.customer_name || '-'}</p>
          )}
        </div>
      ),
    },
    {
      key: 'branch',
      header: 'الفرع',
      render: (voucher: ReturnVoucher) => (
        <span className="text-sm">{voucher.branch.name}</span>
      ),
    },
    {
      key: 'items_count',
      header: 'عدد الأصناف',
      render: (voucher: ReturnVoucher) => (
        <span className="text-sm font-medium">{voucher.items.length}</span>
      ),
    },
    {
      key: 'total_amount',
      header: 'الإجمالي',
      render: (voucher: ReturnVoucher) => (
        <div>
          <p className="text-lg font-bold text-red-600">
            {voucher.total_amount.toFixed(2)} ر.س
          </p>
          {voucher.discount_amount > 0 && (
            <p className="text-xs text-gray-500">
              خصم: {voucher.discount_amount.toFixed(2)} ر.س
            </p>
          )}
        </div>
      ),
    },
    {
      key: 'status',
      header: 'الحالة',
      render: (voucher: ReturnVoucher) => getStatusBadge(voucher.status),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (voucher: ReturnVoucher) => (
        <div className="flex gap-2">
          <Button
            size="sm"
            variant="outline"
            onClick={() => handleViewDetails(voucher)}
          >
            <FileText className="h-4 w-4 ml-1" />
            عرض
          </Button>
          <Button
            size="sm"
            variant="outline"
            onClick={() => handlePrint(voucher)}
          >
            طباعة
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
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <Repeat className="h-8 w-8 text-orange-600" />
            أذون الإرجاع
          </h1>
          <p className="text-gray-500 mt-1">إدارة مرتجعات المبيعات</p>
        </div>
        <Button onClick={() => setDialogOpen(true)}>
          <Plus className="h-5 w-5 ml-2" />
          إذن إرجاع جديد
        </Button>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">إجمالي الأذون</p>
              <p className="text-2xl font-bold mt-2">{statistics.total_vouchers}</p>
              <p className="text-sm text-red-600 mt-1">
                {statistics.total_amount.toFixed(2)} ر.س
              </p>
            </div>
            <div className="h-12 w-12 bg-orange-100 rounded-full flex items-center justify-center">
              <Repeat className="h-6 w-6 text-orange-600" />
            </div>
          </div>
        </Card>

        <Card 
          className={`p-6 cursor-pointer hover:shadow-md transition-shadow ${
            statusFilter === 'draft' ? 'ring-2 ring-yellow-500' : ''
          }`}
          onClick={() => setStatusFilter(statusFilter === 'draft' ? '' : 'draft')}
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">مسودات</p>
              <p className="text-2xl font-bold text-yellow-600 mt-2">
                {statistics.draft_count}
              </p>
              <p className="text-sm text-gray-600 mt-1">
                {statistics.draft_amount.toFixed(2)} ر.س
              </p>
            </div>
            <div className="h-12 w-12 bg-yellow-100 rounded-full flex items-center justify-center">
              <FileText className="h-6 w-6 text-yellow-600" />
            </div>
          </div>
        </Card>

        <Card 
          className={`p-6 cursor-pointer hover:shadow-md transition-shadow ${
            statusFilter === 'approved' ? 'ring-2 ring-green-500' : ''
          }`}
          onClick={() => setStatusFilter(statusFilter === 'approved' ? '' : 'approved')}
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-500">معتمدة</p>
              <p className="text-2xl font-bold text-green-600 mt-2">
                {statistics.approved_count}
              </p>
              <p className="text-sm text-gray-600 mt-1">
                {statistics.approved_amount.toFixed(2)} ر.س
              </p>
            </div>
            <div className="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center">
              <FileText className="h-6 w-6 text-green-600" />
            </div>
          </div>
        </Card>
      </div>

      {/* Filters */}
      <Card className="p-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label className="block text-sm font-medium mb-2">بحث</label>
            <Input
              placeholder="رقم الإذن أو العميل"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">من تاريخ</label>
            <Input
              type="date"
              value={fromDate}
              onChange={(e) => setFromDate(e.target.value)}
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-2">إلى تاريخ</label>
            <Input
              type="date"
              value={toDate}
              onChange={(e) => setToDate(e.target.value)}
            />
          </div>

          <div className="flex items-end">
            <Button onClick={handleSearch} className="w-full">
              <Filter className="h-4 w-4 ml-2" />
              بحث
            </Button>
          </div>
        </div>
      </Card>

      {/* Vouchers Table */}
      <Card>
        <DataTable
          columns={columns}
          data={vouchers}
          loading={loading}
          emptyMessage="لا توجد أذون إرجاع"
        />

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="p-4 border-t flex justify-between items-center">
            <div className="text-sm text-gray-600">
              صفحة {currentPage} من {totalPages} ({totalRecords} إذن)
            </div>
            <div className="flex gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                disabled={currentPage === 1}
              >
                السابق
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
                disabled={currentPage === totalPages}
              >
                التالي
              </Button>
            </div>
          </div>
        )}
      </Card>

      {/* Return Voucher Dialog */}
      <ReturnVoucherDialog
        open={dialogOpen}
        onClose={() => setDialogOpen(false)}
        onSuccess={loadVouchers}
      />
    </div>
  )
}
