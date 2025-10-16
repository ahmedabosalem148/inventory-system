/**
 * Product Dialog
 * Form dialog for creating/editing products
 */

import { useState, useEffect } from 'react'
import { X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Dialog } from '@/components/ui/dialog'
import { toast } from 'react-hot-toast'
import { createProduct, updateProduct, getProductCategories } from '@/services/api/products'
import type { Product, CreateProductInput } from '@/types'

interface ProductDialogProps {
  product: Product | null
  onClose: (saved: boolean) => void
}

export function ProductDialog({ product, onClose }: ProductDialogProps) {
  const [loading, setLoading] = useState(false)
  const [categories, setCategories] = useState<string[]>([])
  
  // Form state
  const [formData, setFormData] = useState<CreateProductInput>({
    name: '',
    brand: '',
    sku: '',
    description: '',
    unit: 'قطعة',
    pack_size: 1,
    min_stock_level: 10,
    price: 0,
    cost: 0,
    category: '',
    barcode: '',
    is_active: true,
  })

  /**
   * Load categories on mount
   */
  useEffect(() => {
    loadCategories()
  }, [])

  /**
   * Populate form with product data for editing
   */
  useEffect(() => {
    if (product) {
      // Handle category (can be string or object)
      const categoryValue = typeof product.category === 'string' 
        ? product.category 
        : product.category?.name || ''
      
      setFormData({
        name: product.name,
        brand: product.brand || '',
        sku: product.sku,
        description: product.description || '',
        unit: product.unit,
        pack_size: product.pack_size,
        min_stock_level: product.min_stock_level,
        price: product.price,
        cost: product.cost || 0,
        category: categoryValue,
        barcode: product.barcode || '',
        is_active: product.is_active !== false,
      })
    }
  }, [product])

  /**
   * Load available categories
   */
  const loadCategories = async () => {
    try {
      const data = await getProductCategories()
      setCategories(data)
    } catch (error) {
      console.error('Error loading categories:', error)
    }
  }

  /**
   * Handle form field change
   */
  const handleChange = (field: keyof CreateProductInput, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }))
  }

  /**
   * Validate form data
   */
  const validateForm = (): boolean => {
    if (!formData.name.trim()) {
      toast.error('اسم المنتج مطلوب')
      return false
    }
    if (!formData.sku.trim()) {
      toast.error('كود المنتج مطلوب')
      return false
    }
    if (formData.price <= 0) {
      toast.error('السعر يجب أن يكون أكبر من صفر')
      return false
    }
    if (formData.pack_size <= 0) {
      toast.error('حجم العبوة يجب أن يكون أكبر من صفر')
      return false
    }
    return true
  }

  /**
   * Handle form submission
   */
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    
    if (!validateForm()) {
      return
    }

    try {
      setLoading(true)
      
      if (product) {
        await updateProduct(product.id, { ...formData, id: product.id })
        toast.success('تم تحديث المنتج بنجاح')
      } else {
        await createProduct(formData)
        toast.success('تم إضافة المنتج بنجاح')
      }
      
      onClose(true)
    } catch (error: any) {
      console.error('Error saving product:', error)
      const message = error.response?.data?.message || 'فشل حفظ المنتج'
      toast.error(message)
    } finally {
      setLoading(false)
    }
  }

  const units = ['قطعة', 'كرتونة', 'كيس', 'علبة', 'زجاجة', 'عبوة']

  return (
    <Dialog open onOpenChange={(open) => !open && onClose(false)}>
      <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b">
            <h2 className="text-2xl font-bold">
              {product ? 'تعديل منتج' : 'منتج جديد'}
            </h2>
            <button
              onClick={() => onClose(false)}
              className="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <X className="w-6 h-6" />
            </button>
          </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
          <div className="space-y-4">
            {/* Product Name */}
            <Input
              label="اسم المنتج"
              value={formData.name}
              onChange={(e) => handleChange('name', e.target.value)}
              placeholder="أدخل اسم المنتج"
              required
            />

            {/* Brand */}
            <Input
              label="الماركة / العلامة التجارية"
              value={formData.brand || ''}
              onChange={(e) => handleChange('brand', e.target.value)}
              placeholder="مثال: Samsung, LG, Philips (اختياري)"
            />

            {/* SKU & Barcode */}
            <div className="grid grid-cols-2 gap-4">
              <Input
                label="كود المنتج (SKU)"
                value={formData.sku}
                onChange={(e) => handleChange('sku', e.target.value)}
                placeholder="مثال: PRD-001"
                required
              />
              <Input
                label="الباركود"
                value={formData.barcode}
                onChange={(e) => handleChange('barcode', e.target.value)}
                placeholder="اختياري"
              />
            </div>

            {/* Description */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الوصف
              </label>
              <textarea
                value={formData.description}
                onChange={(e) => handleChange('description', e.target.value)}
                placeholder="أدخل وصف المنتج..."
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                rows={3}
              />
            </div>

            {/* Category & Unit */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  الفئة
                </label>
                <input
                  list="categories"
                  value={formData.category}
                  onChange={(e) => handleChange('category', e.target.value)}
                  placeholder="اختر أو أدخل فئة جديدة"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <datalist id="categories">
                  {categories.map((cat) => (
                    <option key={cat} value={cat} />
                  ))}
                </datalist>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  الوحدة
                </label>
                <select
                  value={formData.unit}
                  onChange={(e) => handleChange('unit', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  {units.map((u) => (
                    <option key={u} value={u}>{u}</option>
                  ))}
                </select>
              </div>
            </div>

            {/* Pack Size & Min Stock */}
            <div className="grid grid-cols-2 gap-4">
              <Input
                label="حجم العبوة"
                type="number"
                value={formData.pack_size}
                onChange={(e) => handleChange('pack_size', Number(e.target.value))}
                min="1"
                required
              />
              <Input
                label="الحد الأدنى للمخزون"
                type="number"
                value={formData.min_stock_level}
                onChange={(e) => handleChange('min_stock_level', Number(e.target.value))}
                min="0"
                required
              />
            </div>

            {/* Cost & Price */}
            <div className="grid grid-cols-2 gap-4">
              <Input
                label="التكلفة"
                type="number"
                step="0.01"
                value={formData.cost}
                onChange={(e) => handleChange('cost', Number(e.target.value))}
                min="0"
              />
              <Input
                label="سعر البيع"
                type="number"
                step="0.01"
                value={formData.price}
                onChange={(e) => handleChange('price', Number(e.target.value))}
                min="0"
                required
              />
            </div>

            {/* Active Status */}
            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="is_active"
                checked={formData.is_active}
                onChange={(e) => handleChange('is_active', e.target.checked)}
                className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              />
              <label htmlFor="is_active" className="text-sm font-medium text-gray-700">
                منتج نشط
              </label>
            </div>
          </div>
        </form>

        {/* Footer */}
        <div className="flex items-center justify-end gap-3 p-6 border-t bg-gray-50">
          <Button
            type="button"
            variant="outline"
            onClick={() => onClose(false)}
            disabled={loading}
          >
            إلغاء
          </Button>
          <Button
            type="submit"
            loading={loading}
            onClick={handleSubmit}
          >
            {product ? 'حفظ التغييرات' : 'إضافة المنتج'}
          </Button>
        </div>
        </div>
      </div>
    </Dialog>
  )
}
