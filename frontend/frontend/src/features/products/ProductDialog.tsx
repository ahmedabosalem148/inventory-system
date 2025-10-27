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
import type { Product, CreateProductInput, ProductClassification } from '@/types'

interface ProductDialogProps {
  product: Product | null
  onClose: (saved: boolean) => void
}

export function ProductDialog({ product, onClose }: ProductDialogProps) {
  const [loading, setLoading] = useState(false)
  const [categories, setCategories] = useState<any[]>([])
  
  // Form state
  const [formData, setFormData] = useState<CreateProductInput>({
    name: '',
    brand: '',
    description: '',
    product_classification: 'finished_product' as ProductClassification,
    category_id: 0,
    unit: 'قطعة',
    pack_size: null,
    purchase_price: 0,
    sale_price: 0,
    min_stock: 10,
    reorder_level: 5,
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
      setFormData({
        name: product.name,
        brand: product.brand || '',
        description: product.description || '',
        product_classification: product.product_classification || 'finished_product',
        category_id: product.category_id || 0,
        unit: product.unit,
        pack_size: product.pack_size,
        purchase_price: product.purchase_price || product.cost || 0,
        sale_price: product.sale_price || product.price || 0,
        min_stock: product.min_stock_level || 10,
        reorder_level: product.reorder_level || 5,
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
    if (!formData.category_id || formData.category_id === 0) {
      toast.error('يجب اختيار التصنيف')
      return false
    }
    if (!formData.product_classification) {
      toast.error('يجب اختيار نوع المنتج')
      return false
    }
    if (formData.purchase_price <= 0) {
      toast.error('سعر الشراء يجب أن يكون أكبر من صفر')
      return false
    }
    if (formData.sale_price <= 0) {
      toast.error('سعر البيع يجب أن يكون أكبر من صفر')
      return false
    }
    
    // Validation للمنتجات التامة: sale_price >= purchase_price
    if (formData.product_classification === 'finished_product' && formData.sale_price < formData.purchase_price) {
      toast.error('سعر البيع يجب أن يكون أكبر من أو يساوي سعر الشراء للمنتجات التامة')
      return false
    }
    
    // Validation للأجزاء والبلاستيك والألومنيوم: pack_size required
    const requiresPackSize = ['parts', 'plastic_parts', 'aluminum_parts'].includes(formData.product_classification)
    if (requiresPackSize && (!formData.pack_size || formData.pack_size <= 0)) {
      toast.error('حجم العبوة مطلوب لهذا النوع من المنتجات')
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

  const units = ['قطعة', 'كرتونة', 'كيس', 'علبة', 'زجاجة', 'عبوة', 'كجم', 'جرام', 'لتر', 'متر']
  
  const classificationOptions = [
    { value: 'finished_product', label: 'منتج تام' },
    { value: 'semi_finished', label: 'منتج غير تام' },
    { value: 'parts', label: 'أجزاء' },
    { value: 'plastic_parts', label: 'بلاستيك' },
    { value: 'aluminum_parts', label: 'ألومنيوم' },
    { value: 'raw_material', label: 'مواد خام' },
    { value: 'other', label: 'أخرى' },
  ]
  
  // Check if pack_size is required based on classification
  const requiresPackSize = ['parts', 'plastic_parts', 'aluminum_parts'].includes(formData.product_classification)

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

            {/* Product Classification & Category */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  نوع المنتج <span className="text-red-500">*</span>
                </label>
                <select
                  value={formData.product_classification}
                  onChange={(e) => handleChange('product_classification', e.target.value as ProductClassification)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required
                >
                  {classificationOptions.map((opt) => (
                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  التصنيف <span className="text-red-500">*</span>
                </label>
                <select
                  value={formData.category_id}
                  onChange={(e) => handleChange('category_id', Number(e.target.value))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required
                >
                  <option value={0}>اختر التصنيف</option>
                  {categories.map((cat) => (
                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                  ))}
                </select>
              </div>
            </div>

            {/* Description */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الوصف
              </label>
              <textarea
                value={formData.description || ''}
                onChange={(e) => handleChange('description', e.target.value)}
                placeholder="أدخل وصف المنتج..."
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                rows={3}
              />
            </div>

            {/* Unit & Pack Size */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  الوحدة <span className="text-red-500">*</span>
                </label>
                <select
                  value={formData.unit}
                  onChange={(e) => handleChange('unit', e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required
                >
                  {units.map((u) => (
                    <option key={u} value={u}>{u}</option>
                  ))}
                </select>
              </div>
              <Input
                label={`حجم العبوة ${requiresPackSize ? ' *' : ' (اختياري)'}`}
                type="number"
                value={formData.pack_size || ''}
                onChange={(e) => handleChange('pack_size', e.target.value ? Number(e.target.value) : null)}
                min="1"
                required={requiresPackSize}
                placeholder={requiresPackSize ? 'مطلوب' : 'اختياري'}
              />
            </div>

            {/* Purchase Price & Sale Price */}
            <div className="grid grid-cols-2 gap-4">
              <Input
                label="سعر الشراء"
                type="number"
                step="0.01"
                value={formData.purchase_price}
                onChange={(e) => handleChange('purchase_price', Number(e.target.value))}
                min="0"
                required
              />
              <Input
                label="سعر البيع"
                type="number"
                step="0.01"
                value={formData.sale_price}
                onChange={(e) => handleChange('sale_price', Number(e.target.value))}
                min="0"
                required
              />
            </div>

            {/* Min Stock & Reorder Level */}
            <div className="grid grid-cols-2 gap-4">
              <Input
                label="الحد الأدنى للمخزون"
                type="number"
                value={formData.min_stock || 0}
                onChange={(e) => handleChange('min_stock', Number(e.target.value))}
                min="0"
              />
              <Input
                label="مستوى إعادة الطلب"
                type="number"
                value={formData.reorder_level || 0}
                onChange={(e) => handleChange('reorder_level', Number(e.target.value))}
                min="0"
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
            
            {/* SKU Auto-generation Notice */}
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
              <p className="text-sm text-blue-700">
                ℹ️ سيتم توليد كود المنتج (SKU) تلقائياً بناءً على نوع المنتج
              </p>
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
