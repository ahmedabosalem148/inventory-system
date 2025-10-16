/**
 * Stock Adjustment Dialog Component
 * Form for adjusting stock quantities (increase/decrease)
 */

import { useState, useEffect } from 'react'
import { X, Plus, Minus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { createStockAdjustment } from '@/services/api/inventory'
import type { Product } from '@/types'

interface StockAdjustmentDialogProps {
  product?: Product | null
  onClose: (saved: boolean) => void
}

export const StockAdjustmentDialog = ({ product, onClose }: StockAdjustmentDialogProps) => {
  const [loading, setLoading] = useState(false)
  const [formData, setFormData] = useState({
    product_id: 0,
    warehouse_id: 1, // Default warehouse
    quantity: 0,
    type: 'increase' as 'increase' | 'decrease',
    reason: '',
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
    if (!formData.reason.trim()) {
      newErrors.reason = 'السبب مطلوب'
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!validate()) return

    try {
      setLoading(true)
      await createStockAdjustment(formData)
      onClose(true)
    } catch (error: any) {
      console.error('Error saving stock adjustment:', error)
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors)
      } else {
        alert('حدث خطأ أثناء حفظ التعديل')
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
            <h2 className="text-2xl font-bold">تعديل المخزون</h2>
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
                  الكمية الحالية: {(product as any).quantity || 0} {product.unit}
                </div>
              </div>
            )}

            {/* Type Selection */}
            <div>
              <label className="block text-sm font-medium mb-2">
                نوع التعديل <span className="text-red-500">*</span>
              </label>
              <div className="grid grid-cols-2 gap-3">
                <Button
                  type="button"
                  variant={formData.type === 'increase' ? 'default' : 'outline'}
                  className="justify-center"
                  onClick={() => setFormData({ ...formData, type: 'increase' })}
                >
                  <Plus className="h-4 w-4 ml-2" />
                  إضافة
                </Button>
                <Button
                  type="button"
                  variant={formData.type === 'decrease' ? 'default' : 'outline'}
                  className="justify-center"
                  onClick={() => setFormData({ ...formData, type: 'decrease' })}
                >
                  <Minus className="h-4 w-4 ml-2" />
                  خصم
                </Button>
              </div>
            </div>

            {/* Quantity */}
            <div>
              <label className="block text-sm font-medium mb-2">
                الكمية <span className="text-red-500">*</span>
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

            {/* Reason */}
            <div>
              <label className="block text-sm font-medium mb-2">
                السبب <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.reason}
                onChange={(e) => setFormData({ ...formData, reason: e.target.value })}
                placeholder="مثال: تلف، جرد، تصحيح، إلخ"
                className={`w-full px-4 py-2 border rounded-lg ${
                  errors.reason ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                } bg-white dark:bg-gray-800`}
                disabled={loading}
              />
              {errors.reason && (
                <p className="text-sm text-red-500 mt-1">{errors.reason}</p>
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
                {loading ? 'جاري الحفظ...' : 'حفظ التعديل'}
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
