/**
 * Stock Transfer Dialog Component
 * Form for transferring stock between warehouses
 */

import { useState, useEffect } from 'react'
import { X, ArrowRightLeft } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { createStockTransfer } from '@/services/api/inventory'
import type { Product } from '@/types'

interface StockTransferDialogProps {
  product?: Product | null
  onClose: (saved: boolean) => void
}

export const StockTransferDialog = ({ product, onClose }: StockTransferDialogProps) => {
  const [loading, setLoading] = useState(false)
  const [formData, setFormData] = useState({
    product_id: 0,
    from_warehouse_id: 1,
    to_warehouse_id: 2,
    quantity: 0,
    notes: '',
  })
  const [errors, setErrors] = useState<Record<string, string>>({})

  useEffect(() => {
    if (product) {
      setFormData(prev => ({
        ...prev,
        product_id: product.id,
      }))
    }
  }, [product])

  const validate = () => {
    const newErrors: Record<string, string> = {}

    if (!formData.product_id) {
      newErrors.product_id = 'يجب اختيار المنتج'
    }
    if (!formData.quantity || formData.quantity <= 0) {
      newErrors.quantity = 'الكمية يجب أن تكون أكبر من صفر'
    }
    if (formData.from_warehouse_id === formData.to_warehouse_id) {
      newErrors.to_warehouse_id = 'يجب اختيار مخزن مختلف'
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!validate()) return

    try {
      setLoading(true)
      await createStockTransfer(formData)
      onClose(true)
    } catch (error: any) {
      console.error('Error saving stock transfer:', error)
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors)
      } else {
        alert('حدث خطأ أثناء نقل المخزون')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <Card className="w-full max-w-lg max-h-[90vh] overflow-y-auto m-4">
        <div className="p-6">
          {/* Header */}
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-2xl font-bold">نقل بين المخازن</h2>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => onClose(false)}
            >
              <X className="h-5 w-5" />
            </Button>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-4">
            {/* Product Info */}
            {product && (
              <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div className="font-medium">{product.name}</div>
                <div className="text-sm text-gray-500">{product.sku}</div>
                <div className="text-sm text-gray-500 mt-1">
                  الكمية المتاحة: {(product as any).quantity || 0} {product.unit}
                </div>
              </div>
            )}

            {/* From Warehouse */}
            <div>
              <label className="block text-sm font-medium mb-2">
                من المخزن <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.from_warehouse_id}
                onChange={(e) => setFormData({ ...formData, from_warehouse_id: parseInt(e.target.value) })}
                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
                disabled={loading}
              >
                <option value={1}>المخزن الرئيسي</option>
                <option value={2}>الفرع الأول</option>
                <option value={3}>الفرع الثاني</option>
              </select>
            </div>

            {/* Transfer Icon */}
            <div className="flex justify-center">
              <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-full">
                <ArrowRightLeft className="h-5 w-5 text-blue-600 dark:text-blue-400" />
              </div>
            </div>

            {/* To Warehouse */}
            <div>
              <label className="block text-sm font-medium mb-2">
                إلى المخزن <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.to_warehouse_id}
                onChange={(e) => setFormData({ ...formData, to_warehouse_id: parseInt(e.target.value) })}
                className={`w-full px-4 py-2 border rounded-lg ${
                  errors.to_warehouse_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                } bg-white dark:bg-gray-800`}
                disabled={loading}
              >
                <option value={1}>المخزن الرئيسي</option>
                <option value={2}>الفرع الأول</option>
                <option value={3}>الفرع الثاني</option>
              </select>
              {errors.to_warehouse_id && (
                <p className="text-sm text-red-500 mt-1">{errors.to_warehouse_id}</p>
              )}
            </div>

            {/* Quantity */}
            <div>
              <label className="block text-sm font-medium mb-2">
                الكمية المنقولة <span className="text-red-500">*</span>
              </label>
              <input
                type="number"
                min="0"
                step="0.01"
                value={formData.quantity}
                onChange={(e) => setFormData({ ...formData, quantity: parseFloat(e.target.value) || 0 })}
                className={`w-full px-4 py-2 border rounded-lg ${
                  errors.quantity ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                } bg-white dark:bg-gray-800`}
                disabled={loading}
              />
              {errors.quantity && (
                <p className="text-sm text-red-500 mt-1">{errors.quantity}</p>
              )}
            </div>

            {/* Notes */}
            <div>
              <label className="block text-sm font-medium mb-2">ملاحظات</label>
              <textarea
                value={formData.notes}
                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                rows={3}
                className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
                disabled={loading}
              />
            </div>

            {/* Actions */}
            <div className="flex gap-3 pt-4">
              <Button type="submit" disabled={loading} className="flex-1">
                {loading ? 'جاري النقل...' : 'نقل المخزون'}
              </Button>
              <Button
                type="button"
                variant="outline"
                onClick={() => onClose(false)}
                disabled={loading}
              >
                إلغاء
              </Button>
            </div>
          </form>
        </div>
      </Card>
    </div>
  )
}
