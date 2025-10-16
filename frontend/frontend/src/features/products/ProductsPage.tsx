/**
 * Products Page
 * Main products management interface with DataTable
 */

import { useState, useEffect } from 'react'
import { Plus, Filter, Download, Upload, AlertTriangle, Settings } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { DataTable } from '@/components/ui/data-table'
import { SearchInput } from '@/components/ui/search-input'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { toast } from 'react-hot-toast'
import { getProducts, deleteProduct, type ProductsListParams } from '@/services/api/products'
import type { Product } from '@/types'
import { ProductDialog } from './ProductDialog'
import { ProductFiltersDialog } from './ProductFiltersDialog'
import { BranchMinStockDialog } from './BranchMinStockDialog'

export function ProductsPage() {
  const [products, setProducts] = useState<Product[]>([])
  const [loading, setLoading] = useState(true)
  const [searchQuery, setSearchQuery] = useState('')
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [totalItems, setTotalItems] = useState(0)
  const [perPage] = useState(10)
  
  // Dialogs state
  const [showProductDialog, setShowProductDialog] = useState(false)
  const [showFiltersDialog, setShowFiltersDialog] = useState(false)
  const [showMinStockDialog, setShowMinStockDialog] = useState(false)
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null)
  
  // Filters state
  const [filters, setFilters] = useState<ProductsListParams>({})

  /**
   * Load products from API
   */
  const loadProducts = async () => {
    try {
      setLoading(true)
      const params: ProductsListParams = {
        page: currentPage,
        per_page: perPage,
        search: searchQuery || undefined,
        ...filters
      }
      
      const response = await getProducts(params)
      setProducts(response.data)
      setTotalPages(response.last_page)
      setTotalItems(response.total)
    } catch (error) {
      console.error('Error loading products:', error)
      toast.error('فشل تحميل المنتجات')
    } finally {
      setLoading(false)
    }
  }

  /**
   * Load products on mount and when dependencies change
   */
  useEffect(() => {
    loadProducts()
  }, [currentPage, searchQuery, filters])

  /**
   * Handle product deletion
   */
  const handleDelete = async (product: Product) => {
    if (!confirm(`هل تريد حذف المنتج "${product.name}"؟`)) {
      return
    }

    try {
      await deleteProduct(product.id)
      toast.success('تم حذف المنتج بنجاح')
      loadProducts()
    } catch (error) {
      console.error('Error deleting product:', error)
      toast.error('فشل حذف المنتج')
    }
  }

  /**
   * Handle edit click
   */
  const handleEdit = (product: Product) => {
    setSelectedProduct(product)
    setShowProductDialog(true)
  }

  /**
   * Handle add new click
   */
  const handleAddNew = () => {
    setSelectedProduct(null)
    setShowProductDialog(true)
  }

  /**
   * Handle dialog close
   */
  const handleDialogClose = (saved: boolean) => {
    setShowProductDialog(false)
    setSelectedProduct(null)
    if (saved) {
      loadProducts()
    }
  }

  /**
   * Handle manage min stock click
   */
  const handleManageMinStock = (product: Product) => {
    setSelectedProduct(product)
    setShowMinStockDialog(true)
  }

  /**
   * Handle filters apply
   */
  const handleFiltersApply = (newFilters: ProductsListParams) => {
    setFilters(newFilters)
    setCurrentPage(1)
    setShowFiltersDialog(false)
  }

  /**
   * DataTable columns configuration
   */
  const columns = [
    {
      key: 'sku',
      header: 'كود المنتج',
      sortable: true,
      render: (row: Product) => row.sku || '-',
    },
    {
      key: 'name',
      header: 'اسم المنتج',
      sortable: true,
      render: (row: Product) => (
        <div className="font-medium">
          {row.name}
          {row.low_stock && (
            <Badge variant="warning" className="mr-2">
              <AlertTriangle className="w-3 h-3 ml-1" />
              مخزون منخفض
            </Badge>
          )}
        </div>
      ),
    },
    {
      key: 'category',
      header: 'الفئة',
      sortable: true,
      render: (row: Product) => {
        if (!row.category) return '-'
        return typeof row.category === 'string' ? row.category : row.category.name
      },
    },
    {
      key: 'unit',
      header: 'الوحدة',
      render: (row: Product) => row.unit || '-',
    },
    {
      key: 'pack_size',
      header: 'حجم العبوة',
      render: (row: Product) => `${row.pack_size} وحدة`,
    },
    {
      key: 'price',
      header: 'السعر',
      sortable: true,
      render: (row: Product) => `${(row.price || 0).toFixed(2)} ج`,
    },
    {
      key: 'total_stock',
      header: 'المخزون',
      sortable: true,
      render: (row: Product) => (
        <span className={(row.total_stock || 0) <= 0 ? 'text-red-600 font-bold' : ''}>
          {row.total_stock || 0}
        </span>
      ),
    },
    {
      key: 'is_active',
      header: 'الحالة',
      render: (row: Product) => (
        <Badge variant={row.is_active ? 'success' : 'default'}>
          {row.is_active ? 'نشط' : 'غير نشط'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (row: Product) => (
        <div className="flex gap-2">
          <Button
            size="sm"
            variant="outline"
            onClick={() => handleManageMinStock(row)}
            title="إدارة الحد الأدنى للمخزون"
          >
            <Settings className="w-4 h-4" />
          </Button>
          <Button
            size="sm"
            variant="outline"
            onClick={() => handleEdit(row)}
          >
            تعديل
          </Button>
          <Button
            size="sm"
            variant="destructive"
            onClick={() => handleDelete(row)}
          >
            حذف
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold">إدارة المنتجات</h1>
          <p className="text-gray-600 mt-1">
            عرض وإدارة جميع المنتجات في النظام
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" size="sm">
            <Upload className="w-4 h-4 ml-2" />
            استيراد
          </Button>
          <Button variant="outline" size="sm">
            <Download className="w-4 h-4 ml-2" />
            تصدير
          </Button>
          <Button onClick={handleAddNew}>
            <Plus className="w-4 h-4 ml-2" />
            منتج جديد
          </Button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent className="pt-6">
            <div className="text-sm text-gray-600">إجمالي المنتجات</div>
            <div className="text-2xl font-bold">{totalItems}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-sm text-gray-600">المنتجات النشطة</div>
            <div className="text-2xl font-bold text-green-600">
              {products.filter(p => p.is_active).length}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-sm text-gray-600">مخزون منخفض</div>
            <div className="text-2xl font-bold text-red-600">
              {products.filter(p => p.low_stock).length}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="pt-6">
            <div className="text-sm text-gray-600">الفئات</div>
            <div className="text-2xl font-bold">
              {new Set(products.map(p => p.category).filter(Boolean)).size}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Search and Filters */}
      <Card>
        <CardHeader>
          <div className="flex flex-col md:flex-row gap-4">
            <div className="flex-1">
              <SearchInput
                placeholder="ابحث عن منتج بالاسم أو الكود..."
                onSearch={setSearchQuery}
              />
            </div>
            <Button
              variant="outline"
              onClick={() => setShowFiltersDialog(true)}
            >
              <Filter className="w-4 h-4 ml-2" />
              فلترة
              {Object.keys(filters).length > 0 && (
                <Badge variant="default" className="mr-2">
                  {Object.keys(filters).length}
                </Badge>
              )}
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex justify-center items-center py-12">
              <Spinner size="lg" />
            </div>
          ) : (
            <>
              <DataTable
                columns={columns}
                data={products}
                emptyMessage="لا توجد منتجات"
              />
              
              {/* Pagination */}
              {totalPages > 1 && (
                <div className="flex justify-center items-center gap-2 mt-4">
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={currentPage === 1}
                    onClick={() => setCurrentPage(currentPage - 1)}
                  >
                    السابق
                  </Button>
                  <span className="text-sm text-gray-600">
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
              )}
            </>
          )}
        </CardContent>
      </Card>

      {/* Dialogs */}
      {showProductDialog && (
        <ProductDialog
          product={selectedProduct}
          onClose={handleDialogClose}
        />
      )}
      
      {showFiltersDialog && (
        <ProductFiltersDialog
          filters={filters}
          onApply={handleFiltersApply}
          onClose={() => setShowFiltersDialog(false)}
        />
      )}

      {showMinStockDialog && selectedProduct && (
        <BranchMinStockDialog
          product={selectedProduct}
          onClose={(updated) => {
            setShowMinStockDialog(false)
            setSelectedProduct(null)
            if (updated) {
              loadProducts()
            }
          }}
        />
      )}
    </div>
  )
}
