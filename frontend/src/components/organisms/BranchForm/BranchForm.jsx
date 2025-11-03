import { useState, useEffect } from 'react';
import { X, Save, Building2, AlertCircle } from 'lucide-react';
import { Button, Input, Card, Spinner, Alert } from '../../atoms';

/**
 * BranchForm - Add/Edit branch/warehouse form modal
 */
const BranchForm = ({ 
  branch = null, 
  onSubmit, 
  onClose, 
  loading = false 
}) => {
  const [formData, setFormData] = useState({
    name: '',
    code: '',
    phone: '',
    address: '',
    is_active: true
  });
  
  const [errors, setErrors] = useState({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [apiError, setApiError] = useState(null);

  // Initialize form data when branch changes
  useEffect(() => {
    if (branch) {
      setFormData({
        name: branch.name || '',
        code: branch.code || '',
        phone: branch.phone || '',
        address: branch.address || '',
        is_active: branch.is_active !== undefined ? branch.is_active : true
      });
    } else {
      // Reset form for new branch
      setFormData({
        name: '',
        code: '',
        phone: '',
        address: '',
        is_active: true
      });
    }
    setErrors({});
    setApiError(null);
  }, [branch]);

  // Handle input change
  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
    
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
    
    // Clear API error on any input
    if (apiError) {
      setApiError(null);
    }
  };

  // Validate form
  const validateForm = () => {
    const newErrors = {};

    // Name validation
    if (!formData.name.trim()) {
      newErrors.name = 'اسم الفرع/المخزن مطلوب';
    } else if (formData.name.length < 3) {
      newErrors.name = 'اسم الفرع يجب أن يكون على الأقل 3 أحرف';
    } else if (formData.name.length > 100) {
      newErrors.name = 'اسم الفرع لا يمكن أن يتجاوز 100 حرف';
    }

    // Code validation (optional)
    if (formData.code && formData.code.trim()) {
      if (formData.code.length > 50) {
        newErrors.code = 'كود الفرع لا يمكن أن يتجاوز 50 حرف';
      }
      // Only allow alphanumeric and underscores
      if (!/^[A-Z0-9_]+$/.test(formData.code)) {
        newErrors.code = 'كود الفرع يجب أن يحتوي على أحرف إنجليزية كبيرة وأرقام فقط';
      }
    }

    // Phone validation (optional but with format check)
    if (formData.phone && formData.phone.trim()) {
      // Remove spaces and dashes for validation
      const cleanPhone = formData.phone.replace(/[\s-]/g, '');
      
      if (cleanPhone.length < 10) {
        newErrors.phone = 'رقم الهاتف يجب أن يكون على الأقل 10 أرقام';
      } else if (cleanPhone.length > 15) {
        newErrors.phone = 'رقم الهاتف لا يمكن أن يتجاوز 15 رقم';
      } else if (!/^\+?[0-9]+$/.test(cleanPhone)) {
        newErrors.phone = 'رقم الهاتف يجب أن يحتوي على أرقام فقط';
      }
    }

    // Address validation (optional)
    if (formData.address && formData.address.length > 500) {
      newErrors.address = 'العنوان لا يمكن أن يتجاوز 500 حرف';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Handle form submission
  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!validateForm()) return;

    setIsSubmitting(true);
    setApiError(null);
    
    try {
      // Prepare data (remove empty optional fields)
      const submitData = {
        name: formData.name.trim(),
        is_active: formData.is_active
      };
      
      if (formData.code && formData.code.trim()) {
        submitData.code = formData.code.trim().toUpperCase();
      }
      
      if (formData.phone && formData.phone.trim()) {
        submitData.phone = formData.phone.trim();
      }
      
      if (formData.address && formData.address.trim()) {
        submitData.address = formData.address.trim();
      }

      await onSubmit(submitData);
      
      // Form will be closed by parent component on success
    } catch (error) {
      console.error('Error saving branch:', error);
      
      // Handle API errors
      if (error.response?.data) {
        const { message, errors: apiErrors } = error.response.data;
        
        if (apiErrors) {
          // Map API errors to form fields
          const mappedErrors = {};
          Object.keys(apiErrors).forEach(key => {
            mappedErrors[key] = Array.isArray(apiErrors[key]) 
              ? apiErrors[key][0] 
              : apiErrors[key];
          });
          setErrors(mappedErrors);
        } else if (message) {
          setApiError(message);
        }
      } else {
        setApiError('حدث خطأ أثناء حفظ البيانات. الرجاء المحاولة مرة أخرى.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" dir="rtl">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
              <Building2 className="w-5 h-5 text-blue-600" />
            </div>
            <h2 className="text-xl font-bold text-gray-900">
              {branch ? 'تعديل فرع/مخزن' : 'إضافة فرع/مخزن جديد'}
            </h2>
          </div>
          
          <Button
            variant="ghost"
            size="sm"
            onClick={onClose}
            disabled={isSubmitting || loading}
            className="w-8 h-8 p-0"
          >
            <X className="w-5 h-5" />
          </Button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit}>
          <div className="px-6 py-4 overflow-y-auto max-h-[calc(90vh-140px)]">
            {/* API Error Alert */}
            {apiError && (
              <Alert variant="error" className="mb-4">
                <div className="flex items-start gap-2">
                  <AlertCircle className="w-5 h-5 flex-shrink-0" />
                  <div>
                    <div className="font-medium">خطأ</div>
                    <div className="text-sm">{apiError}</div>
                  </div>
                </div>
              </Alert>
            )}

            <div className="space-y-4">
              {/* Name Field */}
              <div>
                <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                  اسم الفرع/المخزن <span className="text-red-500">*</span>
                </label>
                <Input
                  id="name"
                  name="name"
                  type="text"
                  value={formData.name}
                  onChange={handleChange}
                  error={errors.name}
                  placeholder="مثال: الفرع الرئيسي، مخزن الدقي، ..."
                  disabled={isSubmitting || loading}
                  autoFocus
                />
                {errors.name && (
                  <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                )}
              </div>

              {/* Code Field */}
              <div>
                <label htmlFor="code" className="block text-sm font-medium text-gray-700 mb-1">
                  كود الفرع
                  <span className="text-gray-500 text-xs mr-1">(اختياري)</span>
                </label>
                <Input
                  id="code"
                  name="code"
                  type="text"
                  value={formData.code}
                  onChange={handleChange}
                  error={errors.code}
                  placeholder="مثال: FAC, ATB, IMB"
                  disabled={isSubmitting || loading || (branch && ['FAC', 'ATB', 'IMB'].includes(branch.code))}
                  style={{ direction: 'ltr', textAlign: 'left' }}
                />
                {errors.code && (
                  <p className="mt-1 text-sm text-red-600">{errors.code}</p>
                )}
                {branch && ['FAC', 'ATB', 'IMB'].includes(branch.code) && (
                  <p className="mt-1 text-xs text-gray-500">
                    لا يمكن تعديل كود الفروع الأساسية
                  </p>
                )}
              </div>

              {/* Phone Field */}
              <div>
                <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1">
                  رقم الهاتف
                  <span className="text-gray-500 text-xs mr-1">(اختياري)</span>
                </label>
                <Input
                  id="phone"
                  name="phone"
                  type="tel"
                  value={formData.phone}
                  onChange={handleChange}
                  error={errors.phone}
                  placeholder="مثال: 01234567890 أو +201234567890"
                  disabled={isSubmitting || loading}
                  style={{ direction: 'ltr', textAlign: 'left' }}
                />
                {errors.phone && (
                  <p className="mt-1 text-sm text-red-600">{errors.phone}</p>
                )}
              </div>

              {/* Address Field */}
              <div>
                <label htmlFor="address" className="block text-sm font-medium text-gray-700 mb-1">
                  العنوان
                  <span className="text-gray-500 text-xs mr-1">(اختياري)</span>
                </label>
                <textarea
                  id="address"
                  name="address"
                  value={formData.address}
                  onChange={handleChange}
                  placeholder="مثال: 123 شارع الهرم، الجيزة، مصر"
                  disabled={isSubmitting || loading}
                  rows={3}
                  className={`
                    w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                    ${errors.address ? 'border-red-500' : 'border-gray-300'}
                    ${isSubmitting || loading ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'}
                  `}
                />
                {errors.address && (
                  <p className="mt-1 text-sm text-red-600">{errors.address}</p>
                )}
              </div>

              {/* Is Active Toggle */}
              <div className="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                <input
                  type="checkbox"
                  id="is_active"
                  name="is_active"
                  checked={formData.is_active}
                  onChange={handleChange}
                  disabled={isSubmitting || loading}
                  className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <div className="flex-1">
                  <label htmlFor="is_active" className="block text-sm font-medium text-gray-900 cursor-pointer">
                    فرع نشط
                  </label>
                  <p className="text-xs text-gray-600 mt-0.5">
                    الفروع غير النشطة لن تظهر في القوائم المنسدلة
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Footer */}
          <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <Button
              type="button"
              variant="outline"
              onClick={onClose}
              disabled={isSubmitting || loading}
            >
              إلغاء
            </Button>
            
            <Button
              type="submit"
              disabled={isSubmitting || loading}
              className="flex items-center gap-2"
            >
              {isSubmitting || loading ? (
                <>
                  <Spinner size="sm" />
                  <span>جاري الحفظ...</span>
                </>
              ) : (
                <>
                  <Save className="w-4 h-4" />
                  <span>{branch ? 'حفظ التغييرات' : 'إضافة الفرع'}</span>
                </>
              )}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default BranchForm;
