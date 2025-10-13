import { useState, useEffect } from 'react';
import { X, Save, Package } from 'lucide-react';
import { Button, Input, Card, Spinner, Alert } from '../../atoms';

/**
 * ProductForm - Add/Edit product form modal
 */
const ProductForm = ({ 
  product = null, 
  isOpen = false, 
  onClose, 
  onSave, 
  loading = false 
}) => {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    category_id: '',
    unit: '',
    purchase_price: '',
    sale_price: '',
    min_stock: '',
    pack_size: '',
    reorder_level: '',
    is_active: true
  });
  
  const [errors, setErrors] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Categories options
  const categories = [
    { value: '1', label: 'إلكترونيات' },
    { value: '2', label: 'ملابس' },
    { value: '3', label: 'مواد غذائية' },
    { value: '4', label: 'كتب' },
    { value: '5', label: 'أدوات' }
  ];

  // Units options (Arabic)
  const units = [
    { value: 'قطعة', label: 'قطعة' },
    { value: 'كيلوجرام', label: 'كيلوجرام' },
    { value: 'جرام', label: 'جرام' },
    { value: 'لتر', label: 'لتر' },
    { value: 'متر', label: 'متر' },
    { value: 'صندوق', label: 'صندوق' },
    { value: 'حزمة', label: 'حزمة' },
    { value: 'زجاجة', label: 'زجاجة' }
  ];

  // Initialize form data when product changes
  useEffect(() => {
    if (product) {
      setFormData({
        name: product.name || '',
        description: product.description || '',
        category_id: product.category_id || (product.category?.id ? product.category.id.toString() : ''),
        unit: product.unit || '',
        purchase_price: product.purchase_price || '',
        sale_price: product.sale_price || '',
        min_stock: product.min_stock || '',
        pack_size: product.pack_size || '',
        reorder_level: product.reorder_level || '',
        is_active: product.is_active !== undefined ? product.is_active : true
      });
    } else {
      // Reset form for new product
      setFormData({
        name: '',
        description: '',
        category_id: '',
        unit: '',
        purchase_price: '',
        sale_price: '',
        min_stock: '',
        pack_size: '1',
        reorder_level: '',
        is_active: true
      });
    }
    setErrors({});
  }, [product, isOpen]);

  // Handle input change
  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  // Validate form
  const validateForm = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'اسم المنتج مطلوب';
    }

    if (!formData.category_id) {
      newErrors.category_id = 'الفئة مطلوبة';
    }

    if (!formData.unit) {
      newErrors.unit = 'الوحدة مطلوبة';
    }

    if (!formData.purchase_price || parseFloat(formData.purchase_price) < 0) {
      newErrors.purchase_price = 'سعر الشراء مطلوب ويجب أن يكون أكبر من الصفر';
    }

    if (!formData.sale_price || parseFloat(formData.sale_price) < 0) {
      newErrors.sale_price = 'سعر البيع مطلوب ويجب أن يكون أكبر من الصفر';
    }

    if (parseFloat(formData.sale_price) < parseFloat(formData.purchase_price)) {
      newErrors.sale_price = 'سعر البيع يجب أن يكون أكبر من سعر الشراء';
    }

    if (formData.min_stock && parseFloat(formData.min_stock) < 0) {
      newErrors.min_stock = 'الحد الأدنى للمخزون يجب أن يكون أكبر من أو يساوي الصفر';
    }

    if (formData.pack_size && parseFloat(formData.pack_size) <= 0) {
      newErrors.pack_size = 'حجم الحزمة يجب أن يكون أكبر من الصفر';
    }

    if (formData.reorder_level && parseFloat(formData.reorder_level) < 0) {
      newErrors.reorder_level = 'مستوى إعادة الطلب يجب أن يكون أكبر من أو يساوي الصفر';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Handle form submission
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    setIsSubmitting(true);
    
    try {
      await onSave(formData);
      onClose();
    } catch (error) {
      console.error('Error saving product:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  // Don't render if not open
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <Package className="w-5 h-5 text-blue-600" />
            </div>
            <h2 className="text-xl font-bold text-gray-900">
              {product ? 'تعديل المنتج' : 'إضافة منتج جديد'}
            </h2>
          </div>
          
          <Button
            variant="ghost"
            size="sm"
            onClick={onClose}
            disabled={isSubmitting}
            className="w-8 h-8 p-0"
          >
            <X className="w-4 h-4" />
          </Button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="overflow-y-auto max-h-[calc(90vh-140px)]">
          <div className="p-6 space-y-6">
            {/* Basic Information */}
            <Card className="p-4">
              <h3 className="font-semibold text-gray-900 mb-4">المعلومات الأساسية</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    اسم المنتج *
                  </label>
                  <Input
                    name="name"
                    value={formData.name}
                    onChange={handleChange}
                    placeholder="أدخل اسم المنتج"
                    error={errors.name}
                    disabled={isSubmitting}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    الفئة *
                  </label>
                  <select
                    name="category_id"
                    value={formData.category_id}
                    onChange={handleChange}
                    className={`w-full px-3 py-2 border rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.category_id ? 'border-red-300' : 'border-gray-300'
                    }`}
                    disabled={isSubmitting}
                  >
                    <option value="">اختر الفئة</option>
                    {categories.map(cat => (
                      <option key={cat.value} value={cat.value}>{cat.label}</option>
                    ))}
                  </select>
                  {errors.category_id && (
                    <p className="mt-1 text-sm text-red-600">{errors.category_id}</p>
                  )}
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    الوحدة *
                  </label>
                  <select
                    name="unit"
                    value={formData.unit}
                    onChange={handleChange}
                    className={`w-full px-3 py-2 border rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                      errors.unit ? 'border-red-300' : 'border-gray-300'
                    }`}
                    disabled={isSubmitting}
                  >
                    <option value="">اختر الوحدة</option>
                    {units.map(unit => (
                      <option key={unit.value} value={unit.value}>{unit.label}</option>
                    ))}
                  </select>
                  {errors.unit && (
                    <p className="mt-1 text-sm text-red-600">{errors.unit}</p>
                  )}
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    حجم الحزمة
                  </label>
                  <Input
                    name="pack_size"
                    type="number"
                    min="1"
                    step="1"
                    value={formData.pack_size}
                    onChange={handleChange}
                    placeholder="1"
                    error={errors.pack_size}
                    disabled={isSubmitting}
                  />
                </div>
              </div>

              <div className="mt-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  الوصف
                </label>
                <textarea
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  rows={3}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="وصف تفصيلي للمنتج..."
                  disabled={isSubmitting}
                />
              </div>
            </Card>

            {/* Pricing */}
            <Card className="p-4">
              <h3 className="font-semibold text-gray-900 mb-4">التسعير</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    سعر الشراء *
                  </label>
                  <Input
                    name="purchase_price"
                    type="number"
                    min="0"
                    step="0.01"
                    value={formData.purchase_price}
                    onChange={handleChange}
                    placeholder="0.00"
                    error={errors.purchase_price}
                    disabled={isSubmitting}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    سعر البيع *
                  </label>
                  <Input
                    name="sale_price"
                    type="number"
                    min="0"
                    step="0.01"
                    value={formData.sale_price}
                    onChange={handleChange}
                    placeholder="0.00"
                    error={errors.sale_price}
                    disabled={isSubmitting}
                  />
                </div>
              </div>

              {/* Profit margin display */}
              {formData.purchase_price && formData.sale_price && (
                <div className="mt-4 p-3 bg-green-50 rounded-lg">
                  <div className="text-sm text-green-700">
                    هامش الربح: {((formData.sale_price - formData.purchase_price) / formData.purchase_price * 100).toFixed(2)}%
                    ({(formData.sale_price - formData.purchase_price).toFixed(2)} جنيه)
                  </div>
                </div>
              )}
            </Card>

            {/* Inventory */}
            <Card className="p-4">
              <h3 className="font-semibold text-gray-900 mb-4">إدارة المخزون</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    الحد الأدنى للمخزون
                  </label>
                  <Input
                    name="min_stock"
                    type="number"
                    min="0"
                    step="1"
                    value={formData.min_stock}
                    onChange={handleChange}
                    placeholder="0"
                    error={errors.min_stock}
                    disabled={isSubmitting}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    مستوى إعادة الطلب
                  </label>
                  <Input
                    name="reorder_level"
                    type="number"
                    min="0"
                    step="1"
                    value={formData.reorder_level}
                    onChange={handleChange}
                    placeholder="0"
                    error={errors.reorder_level}
                    disabled={isSubmitting}
                  />
                </div>
              </div>

              <div className="mt-4">
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    name="is_active"
                    checked={formData.is_active}
                    onChange={(e) => handleChange({
                      target: {
                        name: 'is_active',
                        value: e.target.checked
                      }
                    })}
                    className="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                    disabled={isSubmitting}
                  />
                  <span className="text-sm font-medium text-gray-700">
                    المنتج نشط ومتاح للبيع
                  </span>
                </label>
              </div>
            </Card>


          </div>

          {/* Footer */}
          <div className="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div className="flex items-center justify-end gap-3">
              <Button
                type="button"
                variant="outline"
                onClick={onClose}
                disabled={isSubmitting}
              >
                إلغاء
              </Button>
              
              <Button
                type="submit"
                disabled={isSubmitting}
                className="flex items-center gap-2"
              >
                {isSubmitting ? (
                  <>
                    <Spinner size="sm" />
                    جاري الحفظ...
                  </>
                ) : (
                  <>
                    <Save className="w-4 h-4" />
                    {product ? 'حفظ التعديلات' : 'إضافة المنتج'}
                  </>
                )}
              </Button>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ProductForm;