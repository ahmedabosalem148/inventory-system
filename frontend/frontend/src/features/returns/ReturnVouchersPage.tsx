/**
 * Return Vouchers Page
 * Manage product returns from customers
 */

import { useState, useEffect } from 'react'
import { RotateCcw, Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { toast } from 'react-hot-toast'
import { apiClient } from '@/services/api/client'
import { formatCurrency, formatDate } from '@/lib/utils'

interface ReturnVoucher {
  id: number
  voucher_number: string
  customer?: { name: string }
  return_date: string
  total_amount: number
  status: string
  items?: any[]
}

export const ReturnVouchersPage = () => {
  const [vouchers, setVouchers] = useState<ReturnVoucher[]>([])
  const [loading, setLoading] = useState(false)
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)

  const loadVouchers = async () => {
    try {
      setLoading(true)
      const response = await apiClient.get('/return-vouchers', {
        params: { page: currentPage, per_page: 10 }
      })
      setVouchers(response.data.data || [])
      setTotalPages(response.data.last_page || 1)
    } catch (error) {
      console.error('Error loading return vouchers:', error)
      toast.error('فشل تحميل المرتجعات')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadVouchers()
  }, [currentPage])

  const columns = [
    {
      key: 'voucher_number',
      header: 'رقم المرتجع',
      render: (row: ReturnVoucher) => (
        <div className="font-medium text-blue-600">{row.voucher_number}</div>
      ),
    },
    {
      key: 'return_date',
      header: 'التاريخ',
      render: (row: ReturnVoucher) => formatDate(row.return_date),
    },
    {
      key: 'customer',
      header: 'العميل',
      render: (row: ReturnVoucher) => row.customer?.name || '-',
    },
    {
      key: 'total_amount',
      header: 'المبلغ',
      render: (row: ReturnVoucher) => formatCurrency(row.total_amount),
    },
    {
      key: 'status',
      header: 'الحالة',
      render: (row: ReturnVoucher) => (
        <Badge variant={row.status === 'completed' ? 'success' : 'warning'}>
          {row.status === 'completed' ? 'مكتمل' : 'معلق'}
        </Badge>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <RotateCcw className="h-8 w-8" />
            المرتجعات
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            إدارة مرتجعات العملاء
          </p>
        </div>
        <Button>
          <Plus className="h-4 w-4 ml-2" />
          مرتجع جديد
        </Button>
      </div>

      {/* Table */}
      <Card>
        <DataTable
          columns={columns}
          data={vouchers}
          loading={loading}
        />
        
        {totalPages > 1 && (
          <div className="flex justify-between px-6 py-4 border-t">
            <Button
              size="sm"
              variant="outline"
              onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
              disabled={currentPage === 1}
            >
              السابق
            </Button>
            <span>صفحة {currentPage} من {totalPages}</span>
            <Button
              size="sm"
              variant="outline"
              onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
              disabled={currentPage === totalPages}
            >
              التالي
            </Button>
          </div>
        )}
      </Card>
    </div>
  )
}
