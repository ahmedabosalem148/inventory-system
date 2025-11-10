/**
 * Inventory Management Page
 * View and manage stock levels across warehouses
 */

import { useState, useEffect } from 'react'
import { Package, AlertTriangle, Plus, FileDown, RefreshCw, ArrowRightLeft } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { Card } from '@/components/ui/card'
import { StockAdjustmentDialog } from './StockAdjustmentDialog'
import { StockTransferDialog } from './StockTransferDialog'
import { getInventory, getStockAlerts, getInventoryValuation } from '@/services/api/inventory'
import type { Product } from '@/types'

export const InventoryPage = () => {
  const [inventory, setInventory] = useState<Product[]>([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [showLowStock, setShowLowStock] = useState(false)
  const [isAdjustmentDialogOpen, setIsAdjustmentDialogOpen] = useState(false)
  const [isTransferDialogOpen, setIsTransferDialogOpen] = useState(false)
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null)

  // Stats
  const [stats, setStats] = useState({
    totalValue: 0,
    totalItems: 0,
    totalQuantity: 0,
    lowStockItems: 0,
  })

  useEffect(() => {
    loadInventory()
    loadStats()
  }, [page, search, showLowStock])

  const loadInventory = async () => {
    try {
      setLoading(true)
      const response = await getInventory({
        page,
        per_page: 10,
        search: search || undefined,
        low_stock: showLowStock || undefined,
      })
      setInventory(response.data)
      setTotalPages(response.meta?.last_page || response.last_page || 1)
    } catch (error) {
      console.error('Error loading inventory:', error)
    } finally {
      setLoading(false)
    }
  }

  const loadStats = async () => {
    try {
      const [valuation, alerts] = await Promise.all([
        getInventoryValuation(),
        getStockAlerts(),
      ])
      
      setStats({
        totalValue: valuation.total_value,
        totalItems: valuation.total_items,
        totalQuantity: valuation.total_quantity,
        lowStockItems: alerts.length,
      })
    } catch (error) {
      console.error('Error loading stats:', error)
    }
  }

  const handleAdjustStock = (product: Product) => {
    setSelectedProduct(product)
    setIsAdjustmentDialogOpen(true)
  }

  const handleTransferStock = (product: Product) => {
    setSelectedProduct(product)
    setIsTransferDialogOpen(true)
  }

  const handleDialogClose = (saved: boolean) => {
    setIsAdjustmentDialogOpen(false)
    setIsTransferDialogOpen(false)
    setSelectedProduct(null)
    if (saved) {
      loadInventory()
      loadStats()
    }
  }

  const getStockBadge = (product: Product) => {
    const quantity = product.total_stock || 0
    const minStock = product.min_stock_level || 0

    if (quantity === 0) {
      return <Badge variant="danger">نفذ</Badge>
    } else if (quantity <= minStock) {
      return <Badge variant="warning">منخفض</Badge>
    } else {
      return <Badge variant="success">متوفر</Badge>
    }
  }

  const columns = [
    {
      key: 'name',
      header: 'المنتج',
      render: (product: Product) => (
        <div>
          <div className="font-medium">{product.name}</div>
          <div className="text-sm text-gray-500">{product.sku}</div>
        </div>
      ),
    },
    {
      key: 'category',
      header: 'الفئة',
      render: (product: Product) => {
        const category = product.category
        return typeof category === 'string' ? category : category?.name || '-'
      },
    },
    {
      key: 'quantity',
      header: 'الكمية',
      render: (product: Product) => (
        <div className="flex items-center gap-2">
          <span className="font-bold">{product.total_stock || 0}</span>
          <span className="text-sm text-gray-500">{product.unit}</span>
        </div>
      ),
    },
    {
      key: 'min_stock',
      header: 'الحد الأدنى',
      render: (product: Product) => product.min_stock_level || 0,
    },
    {
      key: 'status',
      header: 'الحالة',
      render: (product: Product) => getStockBadge(product),
    },
    {
      key: 'value',
      header: 'القيمة',
      render: (product: Product) => {
        const value = (product.total_stock || 0) * (product.cost || 0)
        return `${value.toFixed(2)} ر.س`
      },
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (product: Product) => (
        <div className="flex gap-2">
          <Button
            size="sm"
            variant="ghost"
            onClick={() => handleAdjustStock(product)}
            title="تعديل الكمية"
          >
            <RefreshCw className="h-4 w-4" />
          </Button>
          <Button
            size="sm"
            variant="ghost"
            onClick={() => handleTransferStock(product)}
            title="نقل بين المخازن"
          >
            <ArrowRightLeft className="h-4 w-4" />
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">إدارة المخزون</h1>
          <p className="text-gray-600 dark:text-gray-400">
            عرض وإدارة مستويات المخزون عبر المخازن
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline">
            <FileDown className="h-4 w-4 ml-2" />
            تصدير
          </Button>
          <Button onClick={() => setIsAdjustmentDialogOpen(true)}>
            <Plus className="h-4 w-4 ml-2" />
            تعديل جديد
          </Button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">قيمة المخزون</p>
              <p className="text-2xl font-bold">{stats.totalValue.toFixed(2)} ر.س</p>
            </div>
            <div className="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
              <Package className="h-6 w-6 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">عدد المنتجات</p>
              <p className="text-2xl font-bold">{stats.totalItems}</p>
            </div>
            <div className="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
              <Package className="h-6 w-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">إجمالي الكمية</p>
              <p className="text-2xl font-bold">{stats.totalQuantity}</p>
            </div>
            <div className="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
              <Package className="h-6 w-6 text-purple-600 dark:text-purple-400" />
            </div>
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">تنبيهات المخزون</p>
              <p className="text-2xl font-bold">{stats.lowStockItems}</p>
            </div>
            <div className="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
              <AlertTriangle className="h-6 w-6 text-red-600 dark:text-red-400" />
            </div>
          </div>
        </Card>
      </div>

      {/* Filters */}
      <Card className="p-4">
        <div className="flex gap-4">
          <div className="flex-1 relative">
            <input
              type="text"
              placeholder="البحث عن منتج..."
              value={search}
              onChange={(e) => {
                setSearch(e.target.value)
                setPage(1)
              }}
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            />
          </div>
          <Button
            variant={showLowStock ? 'default' : 'outline'}
            onClick={() => {
              setShowLowStock(!showLowStock)
              setPage(1)
            }}
          >
            <AlertTriangle className="h-4 w-4 ml-2" />
            {showLowStock ? 'عرض الكل' : 'المخزون المنخفض'}
          </Button>
        </div>
      </Card>

      {/* Inventory Table */}
      <Card>
        <DataTable
          columns={columns}
          data={inventory}
          loading={loading}
        />
        
        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <div className="text-sm text-gray-600 dark:text-gray-400">
              صفحة {page} من {totalPages}
            </div>
            <div className="flex gap-2">
              <Button
                size="sm"
                variant="outline"
                onClick={() => setPage(p => Math.max(1, p - 1))}
                disabled={page === 1}
              >
                السابق
              </Button>
              <Button
                size="sm"
                variant="outline"
                onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                disabled={page === totalPages}
              >
                التالي
              </Button>
            </div>
          </div>
        )}
      </Card>

      {/* Dialogs */}
      {isAdjustmentDialogOpen && (
        <StockAdjustmentDialog
          product={selectedProduct}
          onClose={handleDialogClose}
        />
      )}

      {isTransferDialogOpen && (
        <StockTransferDialog
          product={selectedProduct}
          onClose={handleDialogClose}
        />
      )}
    </div>
  )
}
