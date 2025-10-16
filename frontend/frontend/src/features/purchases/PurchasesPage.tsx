/**
 * Purchase Orders Management Page
 * Main page for managing purchase orders with CRUD operations
 */

import { useState, useEffect } from 'react'
import { Plus, Filter, FileText, DollarSign, Package, CheckCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { Card } from '@/components/ui/card'
import { toast } from 'react-hot-toast'
import type { PurchaseOrder, PurchaseOrdersListParams } from '@/types'
import { getPurchaseOrders, cancelPurchaseOrder, approvePurchaseOrder } from '@/services/api/purchases'
import { PurchaseOrderDialog } from './PurchaseOrderDialog'
import { PurchaseFiltersDialog } from './PurchaseFiltersDialog'
import { ReceiveGoodsDialog } from './ReceiveGoodsDialog'
import { formatCurrency, formatDate } from '@/lib/utils'

export const PurchasesPage = () => {
  const [orders, setOrders] = useState<PurchaseOrder[]>([])
  const [loading, setLoading] = useState(false)
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [totalItems, setTotalItems] = useState(0)
  const [perPage] = useState(10)
  const [searchQuery, setSearchQuery] = useState('')
  const [showOrderDialog, setShowOrderDialog] = useState(false)
  const [showFiltersDialog, setShowFiltersDialog] = useState(false)
  const [showReceiveDialog, setShowReceiveDialog] = useState(false)
  const [selectedOrder, setSelectedOrder] = useState<PurchaseOrder | null>(null)
  const [filters, setFilters] = useState<PurchaseOrdersListParams>({})

  // Stats
  const [stats, setStats] = useState({
    total: 0,
    totalAmount: 0,
    receivedAmount: 0,
    pendingAmount: 0,
  })

  /**
   * Load purchase orders from API
   */
  const loadOrders = async () => {
    try {
      setLoading(true)
      const params: PurchaseOrdersListParams = {
        page: currentPage,
        per_page: perPage,
        search: searchQuery || undefined,
        ...filters,
      }

      const response = await getPurchaseOrders(params)
      setOrders(response.data)
      setTotalPages(response.last_page)
      setTotalItems(response.total)

      // Calculate stats
      const totalAmount = response.data.reduce((sum, order) => sum + order.total_amount, 0)
      const receivedOrders = response.data.filter(o => o.receiving_status === 'FULLY_RECEIVED')
      const receivedAmount = receivedOrders.reduce((sum, order) => sum + order.total_amount, 0)
      
      setStats({
        total: response.total,
        totalAmount,
        receivedAmount,
        pendingAmount: totalAmount - receivedAmount,
      })
    } catch (error) {
      console.error('Error loading purchase orders:', error)
      toast.error('فشل تحميل أوامر الشراء')
    } finally {
      setLoading(false)
    }
  }

  /**
   * Load orders on mount and when dependencies change
   */
  useEffect(() => {
    loadOrders()
  }, [currentPage, searchQuery, filters])

  /**
   * Handle order cancellation
   */
  const handleCancel = async (order: PurchaseOrder) => {
    const reason = prompt('سبب الإلغاء (اختياري):')
    if (reason === null) return // User clicked cancel

    try {
      await cancelPurchaseOrder(order.id, reason)
      toast.success('تم إلغاء أمر الشراء بنجاح')
      loadOrders()
    } catch (error) {
      console.error('Error cancelling order:', error)
      toast.error('فشل إلغاء أمر الشراء')
    }
  }

  /**
   * Handle order approval
   */
  const handleApprove = async (order: PurchaseOrder) => {
    if (!confirm(`هل تريد اعتماد أمر الشراء "${order.order_number}"؟`)) {
      return
    }

    try {
      await approvePurchaseOrder(order.id)
      toast.success('تم اعتماد أمر الشراء بنجاح')
      loadOrders()
    } catch (error) {
      console.error('Error approving order:', error)
      toast.error('فشل اعتماد أمر الشراء')
    }
  }

  /**
   * Handle receive goods
   */
  const handleReceive = (order: PurchaseOrder) => {
    setSelectedOrder(order)
    setShowReceiveDialog(true)
  }

  /**
   * Handle edit click
   */
  const handleEdit = (order: PurchaseOrder) => {
    setSelectedOrder(order)
    setShowOrderDialog(true)
  }

  /**
   * Handle view details
   */
  const handleView = (order: PurchaseOrder) => {
    setSelectedOrder(order)
    setShowOrderDialog(true)
  }

  /**
   * Handle dialog close
   */
  const handleDialogClose = (saved: boolean) => {
    setShowOrderDialog(false)
    setSelectedOrder(null)
    if (saved) {
      loadOrders()
    }
  }

  /**
   * Handle receive dialog close
   */
  const handleReceiveDialogClose = (received: boolean) => {
    setShowReceiveDialog(false)
    setSelectedOrder(null)
    if (received) {
      loadOrders()
    }
  }

  /**
   * Handle filters apply
   */
  const handleFiltersApply = (newFilters: PurchaseOrdersListParams) => {
    setFilters(newFilters)
    setCurrentPage(1)
    setShowFiltersDialog(false)
  }

  /**
   * Get status badge
   */
  const getStatusBadge = (status: string) => {
    const variants: Record<string, 'default' | 'success' | 'warning' | 'danger' | 'info'> = {
      DRAFT: 'default',
      PENDING: 'warning',
      APPROVED: 'info',
      RECEIVED: 'success',
      PARTIALLY_RECEIVED: 'warning',
      CANCELLED: 'danger',
    }
    const labels: Record<string, string> = {
      DRAFT: 'مسودة',
      PENDING: 'معلق',
      APPROVED: 'معتمد',
      RECEIVED: 'مستلم',
      PARTIALLY_RECEIVED: 'استلام جزئي',
      CANCELLED: 'ملغي',
    }
    return <Badge variant={variants[status] || 'default'}>{labels[status] || status}</Badge>
  }

  /**
   * Get receiving status badge
   */
  const getReceivingStatusBadge = (status: string) => {
    const variants: Record<string, 'default' | 'success' | 'warning' | 'danger'> = {
      NOT_RECEIVED: 'danger',
      PARTIALLY_RECEIVED: 'warning',
      FULLY_RECEIVED: 'success',
    }
    const labels: Record<string, string> = {
      NOT_RECEIVED: 'لم يستلم',
      PARTIALLY_RECEIVED: 'استلام جزئي',
      FULLY_RECEIVED: 'مستلم بالكامل',
    }
    return <Badge variant={variants[status] || 'default'}>{labels[status] || status}</Badge>
  }

  /**
   * DataTable columns configuration
   */
  const columns = [
    {
      key: 'order_number',
      header: 'رقم الأمر',
      sortable: true,
      render: (row: PurchaseOrder) => (
        <div className="font-medium text-blue-600">{row.order_number}</div>
      ),
    },
    {
      key: 'order_date',
      header: 'التاريخ',
      sortable: true,
      render: (row: PurchaseOrder) => formatDate(row.order_date),
    },
    {
      key: 'supplier',
      header: 'المورد',
      sortable: true,
      render: (row: PurchaseOrder) => row.supplier?.name || '-',
    },
    {
      key: 'total_amount',
      header: 'الإجمالي',
      sortable: true,
      render: (row: PurchaseOrder) => (
        <div className="font-bold">{formatCurrency(row.total_amount)}</div>
      ),
    },
    {
      key: 'receiving_status',
      header: 'حالة الاستلام',
      render: (row: PurchaseOrder) => getReceivingStatusBadge(row.receiving_status),
    },
    {
      key: 'status',
      header: 'الحالة',
      render: (row: PurchaseOrder) => getStatusBadge(row.status),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (row: PurchaseOrder) => (
        <div className="flex gap-2">
          <Button size="sm" variant="ghost" onClick={() => handleView(row)}>
            عرض
          </Button>
          {row.status === 'PENDING' && (
            <Button size="sm" variant="ghost" onClick={() => handleApprove(row)}>
              اعتماد
            </Button>
          )}
          {row.status === 'APPROVED' && row.receiving_status !== 'FULLY_RECEIVED' && (
            <Button size="sm" variant="ghost" className="text-green-600" onClick={() => handleReceive(row)}>
              استلام
            </Button>
          )}
          {row.status !== 'CANCELLED' && row.status !== 'RECEIVED' && (
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
          <h1 className="text-2xl font-bold">أوامر الشراء</h1>
          <p className="text-muted-foreground">إدارة أوامر الشراء واستلام البضائع</p>
        </div>
        <div className="flex gap-3">
          <Button variant="outline" onClick={() => setShowFiltersDialog(true)}>
            <Filter className="w-4 h-4 ml-2" />
            تصفية
          </Button>
          <Button onClick={() => setShowOrderDialog(true)}>
            <Plus className="w-4 h-4 ml-2" />
            أمر شراء جديد
          </Button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-muted-foreground">إجمالي الأوامر</p>
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
            <DollarSign className="w-8 h-8 text-purple-500" />
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-muted-foreground">المستلم</p>
              <p className="text-2xl font-bold text-green-600">
                {formatCurrency(stats.receivedAmount)}
              </p>
            </div>
            <CheckCircle className="w-8 h-8 text-green-500" />
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-muted-foreground">المعلق</p>
              <p className="text-2xl font-bold text-orange-600">
                {formatCurrency(stats.pendingAmount)}
              </p>
            </div>
            <Package className="w-8 h-8 text-orange-500" />
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
              placeholder="بحث في أوامر الشراء..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="max-w-sm"
            />
          </div>

          {/* Table */}
          <DataTable columns={columns} data={orders} loading={loading} />

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
      {showOrderDialog && (
        <PurchaseOrderDialog order={selectedOrder} onClose={handleDialogClose} />
      )}

      {showFiltersDialog && (
        <PurchaseFiltersDialog
          filters={filters}
          onApply={handleFiltersApply}
          onClose={() => setShowFiltersDialog(false)}
        />
      )}

      {showReceiveDialog && selectedOrder && (
        <ReceiveGoodsDialog order={selectedOrder} onClose={handleReceiveDialogClose} />
      )}
    </div>
  )
}
