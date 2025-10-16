/**
 * Product Filters Dialog
 * Advanced filters for products list
 */

import { useState, useEffect } from 'react'
import { X, Filter } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Dialog } from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'
import { getProductCategories } from '@/services/api/products'
import type { ProductsListParams } from '@/services/api/products'

interface ProductFiltersDialogProps {
  filters: ProductsListParams
  onApply: (filters: ProductsListParams) => void
  onClose: () => void
}

export function ProductFiltersDialog({ filters, onApply, onClose }: ProductFiltersDialogProps) {
  const [categories, setCategories] = useState<string[]>([])
  const [localFilters, setLocalFilters] = useState<ProductsListParams>(filters)

  useEffect(() => {
    loadCategories()
  }, [])

  const loadCategories = async () => {
    try {
      const data = await getProductCategories()
      setCategories(data)
    } catch (error) {
      console.error('Error loading categories:', error)
    }
  }

  const handleApply = () => {
    onApply(localFilters)
  }

  const handleReset = () => {
    setLocalFilters({})
  }

  const activeFiltersCount = Object.keys(localFilters).filter(
    key => localFilters[key as keyof ProductsListParams] !== undefined
  ).length

  return (
    <Dialog open onOpenChange={(open) => !open && onClose()}>
      <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b">
            <div className="flex items-center gap-2">
              <Filter className="w-5 h-5" />
              <h2 className="text-xl font-bold">فلترة المنتجات</h2>
              {activeFiltersCount > 0 && (
                <Badge variant="default">{activeFiltersCount}</Badge>
              )}
            </div>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <X className="w-6 h-6" />
            </button>
          </div>

          {/* Filters */}
          <div className="p-6 space-y-4">
            {/* Category Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                الفئة
              </label>
              <select
                value={localFilters.category || ''}
                onChange={(e) => setLocalFilters(prev => ({ ...prev, category: e.target.value || undefined }))}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">جميع الفئات</option>
                {categories.map((cat) => (
                  <option key={cat} value={cat}>{cat}</option>
                ))}
              </select>
            </div>

            {/* Status Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                حالة المنتج
              </label>
              <select
                value={localFilters.is_active === undefined ? '' : localFilters.is_active ? 'true' : 'false'}
                onChange={(e) => {
                  const value = e.target.value
                  setLocalFilters(prev => ({
                    ...prev,
                    is_active: value === '' ? undefined : value === 'true'
                  }))
                }}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">الكل</option>
                <option value="true">نشط</option>
                <option value="false">غير نشط</option>
              </select>
            </div>

            {/* Stock Status Filter */}
            <div>
              <label className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={localFilters.low_stock || false}
                  onChange={(e) => setLocalFilters(prev => ({ ...prev, low_stock: e.target.checked || undefined }))}
                  className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <span className="text-sm font-medium text-gray-700">
                  مخزون منخفض فقط
                </span>
              </label>
            </div>
          </div>

          {/* Footer */}
          <div className="flex items-center justify-between gap-3 p-6 border-t bg-gray-50">
            <Button
              type="button"
              variant="outline"
              onClick={handleReset}
            >
              إعادة تعيين
            </Button>
            <div className="flex gap-2">
              <Button
                type="button"
                variant="outline"
                onClick={onClose}
              >
                إلغاء
              </Button>
              <Button
                type="button"
                onClick={handleApply}
              >
                تطبيق
              </Button>
            </div>
          </div>
        </div>
      </div>
    </Dialog>
  )
}
