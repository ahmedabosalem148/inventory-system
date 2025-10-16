/**
 * Sales Invoices Management Page
 * Main page for managing sales invoices with CRUD operations
 */

import { useState, useEffect } from 'react'
import { Plus, Filter, FileText, DollarSign, Clock, CheckCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { Card } from '@/components/ui/card'
import { toast } from 'react-hot-toast'
import type { SalesInvoice, InvoicesListParams } from '@/types'
import { getInvoices, cancelInvoice } from '@/services/api/invoices'
import { InvoiceDialog } from './InvoiceDialog'
import { InvoiceFiltersDialog } from './InvoiceFiltersDialog'
import { formatCurrency, formatDate } from '@/lib/utils'

export const SalesPage = () => {
  const [invoices, setInvoices] = useState<SalesInvoice[]>([])
  const [loading, setLoading] = useState(false)
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [totalItems, setTotalItems] = useState(0)
  const [perPage] = useState(10)
  const [searchQuery, setSearchQuery] = useState('')
  const [showInvoiceDialog, setShowInvoiceDialog] = useState(false)
  const [showFiltersDialog, setShowFiltersDialog] = useState(false)
  const [selectedInvoice, setSelectedInvoice] = useState<SalesInvoice | null>(null)
  const [filters, setFilters] = useState<InvoicesListParams>({})

  // Stats
  const [stats, setStats] = useState({
    total: 0,
    totalAmount: 0,
    paidAmount: 0,
    pendingAmount: 0,
  })

  /**
   * Load invoices from API
   */
  const loadInvoices = async () => {
    try {
      setLoading(true)
      const params: InvoicesListParams = {
        page: currentPage,
        per_page: perPage,
        search: searchQuery || undefined,
        ...filters,
      }

      const response = await getInvoices(params)
      setInvoices(response.data)
      setTotalPages(response.last_page)
      setTotalItems(response.total)

      // Calculate stats (use net_total as it's the final amount after discount)
      const totalAmount = response.data.reduce((sum, inv) => sum + (inv.net_total || inv.total_amount), 0)
      const paidAmount = response.data.reduce((sum, inv) => sum + (inv.paid_amount || 0), 0)
      setStats({
        total: response.total,
        totalAmount,
        paidAmount,
        pendingAmount: totalAmount - paidAmount,
      })
    } catch (error) {
      console.error('Error loading invoices:', error)
      toast.error('فشل تحميل الفواتير')
    } finally {
      setLoading(false)
    }
  }

  /**
   * Load invoices on mount and when dependencies change
   */
  useEffect(() => {
    loadInvoices()
  }, [currentPage, searchQuery, filters])

  /**
   * Handle invoice cancellation
   */
  const handleCancel = async (invoice: SalesInvoice) => {
    const reason = prompt('سبب الإلغاء (اختياري):')
    if (reason === null) return // User clicked cancel

    try {
      await cancelInvoice(invoice.id, reason)
      toast.success('تم إلغاء الفاتورة بنجاح')
      loadInvoices()
    } catch (error) {
      console.error('Error cancelling invoice:', error)
      toast.error('فشل إلغاء الفاتورة')
    }
  }

  /**
   * Handle edit click
   */
  const handleEdit = (invoice: SalesInvoice) => {
    setSelectedInvoice(invoice)
    setShowInvoiceDialog(true)
  }

  /**
   * Handle view invoice details
   */
  const handleView = (invoice: SalesInvoice) => {
    // Navigate to details page
    window.location.hash = `invoices/${invoice.id}`
  }

  /**
   * Handle dialog close
   */
  const handleDialogClose = (saved: boolean) => {
    setShowInvoiceDialog(false)
    setSelectedInvoice(null)
    if (saved) {
      loadInvoices()
    }
  }

  /**
   * Handle filters apply
   */
  const handleFiltersApply = (newFilters: InvoicesListParams) => {
    setFilters(newFilters)
    setCurrentPage(1)
    setShowFiltersDialog(false)
  }

  /**
   * Get status badge
   */
  const getStatusBadge = (status: string) => {
    const variants: Record<string, 'default' | 'success' | 'warning' | 'danger'> = {
      DRAFT: 'default',
      PENDING: 'warning',
      PAID: 'success',
      PARTIALLY_PAID: 'warning',
      CANCELLED: 'danger',
    }
    const labels: Record<string, string> = {
      DRAFT: 'مسودة',
      PENDING: 'معلقة',
      PAID: 'مدفوعة',
      PARTIALLY_PAID: 'دفع جزئي',
      CANCELLED: 'ملغاة',
    }
    return <Badge variant={variants[status] || 'default'}>{labels[status] || status}</Badge>
  }

  /**
   * Get payment status badge
   */
  const getPaymentStatusBadge = (status: string) => {
    const variants: Record<string, 'default' | 'success' | 'warning' | 'danger'> = {
      UNPAID: 'danger',
      PARTIALLY_PAID: 'warning',
      PAID: 'success',
    }
    const labels: Record<string, string> = {
      UNPAID: 'غير مدفوعة',
      PARTIALLY_PAID: 'دفع جزئي',
      PAID: 'مدفوعة بالكامل',
    }
    return <Badge variant={variants[status] || 'default'}>{labels[status] || status}</Badge>
  }

  /**
   * DataTable columns configuration
   */
  const columns = [
    {
      key: 'voucher_number',
      header: 'رقم الفاتورة',
      sortable: true,
      render: (row: SalesInvoice) => (
        <div className="font-medium text-blue-600">
          {row.voucher_number || row.invoice_number || '-'}
        </div>
      ),
    },
    {
      key: 'issue_date',
      header: 'التاريخ',
      sortable: true,
      render: (row: SalesInvoice) => formatDate(row.issue_date || row.invoice_date || ''),
    },
    {
      key: 'customer',
      header: 'العميل',
      sortable: true,
      render: (row: SalesInvoice) => row.customer?.name || '-',
    },
    {
      key: 'total_amount',
      header: 'الإجمالي',
      sortable: true,
      render: (row: SalesInvoice) => (
        <div className="font-bold">{formatCurrency(row.total_amount)}</div>
      ),
    },
    {
      key: 'paid_amount',
      header: 'المدفوع',
      render: (row: SalesInvoice) => formatCurrency(row.paid_amount || 0),
    },
    {
      key: 'remaining_amount',
      header: 'المتبقي',
      render: (row: SalesInvoice) => (
        <span className={(row.remaining_amount || 0) > 0 ? 'text-red-600 font-bold' : ''}>
          {formatCurrency(row.remaining_amount || 0)}
        </span>
      ),
    },
    {
      key: 'payment_status',
      header: 'حالة الدفع',
      render: (row: SalesInvoice) => 
        row.payment_status ? getPaymentStatusBadge(row.payment_status) : '-',
    },
    {
      key: 'status',
      header: 'الحالة',
      render: (row: SalesInvoice) => getStatusBadge(row.status),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (row: SalesInvoice) => (
        <div className="flex gap-2">
          <Button size="sm" variant="ghost" onClick={() => handleView(row)}>
            عرض
          </Button>
          {row.status !== 'CANCELLED' && (
            <>
              <Button size="sm" variant="ghost" onClick={() => handleEdit(row)}>
                تعديل
              </Button>
              <Button
                size="sm"
                variant="ghost"
                className="text-red-600"
                onClick={() => handleCancel(row)}
              >
                إلغاء
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
          <h1 className="text-2xl font-bold">فواتير المبيعات</h1>
          <p className="text-muted-foreground">إدارة فواتير المبيعات والمدفوعات</p>
        </div>
        <div className="flex gap-3">
          <Button variant="outline" onClick={() => setShowFiltersDialog(true)}>
            <Filter className="w-4 h-4 ml-2" />
            تصفية
          </Button>
          <Button onClick={() => setShowInvoiceDialog(true)}>
            <Plus className="w-4 h-4 ml-2" />
            فاتورة جديدة
          </Button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-muted-foreground">إجمالي الفواتير</p>
              <p className="text-2xl font-bold">{stats.total}</p>
            </div>
            <FileText className="w-8 h-8 text-blue-500" />
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-muted-foreground">إجمالي المبلغ</p>
              <p className="text-2xl font-bold">{formatCurrency(stats.totalAmount)}</p>
            </div>
            <DollarSign className="w-8 h-8 text-green-500" />
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-muted-foreground">المدفوع</p>
              <p className="text-2xl font-bold text-green-600">
                {formatCurrency(stats.paidAmount)}
              </p>
            </div>
            <CheckCircle className="w-8 h-8 text-green-500" />
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-muted-foreground">المتبقي</p>
              <p className="text-2xl font-bold text-red-600">
                {formatCurrency(stats.pendingAmount)}
              </p>
            </div>
            <Clock className="w-8 h-8 text-red-500" />
          </div>
        </Card>
      </div>

      {/* DataTable */}
      <Card>
        <div className="p-4">
          {/* Search */}
          <div className="mb-4">
            <Input
              type="search"
              placeholder="بحث في الفواتير..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="max-w-sm"
            />
          </div>

          {/* Table */}
          <DataTable columns={columns} data={invoices} loading={loading} />

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="mt-4 flex items-center justify-between">
              <div className="text-sm text-muted-foreground">
                عرض {(currentPage - 1) * perPage + 1} إلى{' '}
                {Math.min(currentPage * perPage, totalItems)} من {totalItems}
              </div>
              <div className="flex gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={currentPage === 1}
                  onClick={() => setCurrentPage(currentPage - 1)}
                >
                  السابق
                </Button>
                <span className="px-4 py-2 text-sm">
                  صفحة {currentPage} من {totalPages}
                </span>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={currentPage === totalPages}
                  onClick={() => setCurrentPage(currentPage + 1)}
                >
                  التالي
                </Button>
              </div>
            </div>
          )}
        </div>
      </Card>

      {/* Dialogs */}
      {showInvoiceDialog && (
        <InvoiceDialog
          invoice={selectedInvoice}
          onClose={handleDialogClose}
        />
      )}

      {showFiltersDialog && (
        <InvoiceFiltersDialog
          filters={filters}
          onApply={handleFiltersApply}
          onClose={() => setShowFiltersDialog(false)}
        />
      )}
    </div>
  )
}
