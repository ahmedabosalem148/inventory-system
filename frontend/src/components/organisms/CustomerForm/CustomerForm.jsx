import React, { useState, useEffect } from 'react';
import { Button, Input, Badge } from '../../atoms';
import { X, Save, User } from 'lucide-react';
import apiClient from '../../../utils/axios';

/**
 * Customer Form Component
 * 
 * Form for creating/editing customers
 */
const CustomerForm = ({ isOpen, onClose, onSuccess, editingCustomer = null }) => {
  const [formData, setFormData] = useState({
    name: '',
    type: 'retail',
    phone: '',
    address: '',
    notes: '',
    is_active: true,
  });

  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  // Load data if editing
  useEffect(() => {
    if (editingCustomer) {
      setFormData({
        name: editingCustomer.name || '',
        type: editingCustomer.type || 'retail',
        phone: editingCustomer.phone || '',
        address: editingCustomer.address || '',
        notes: editingCustomer.notes || '',
        is_active: editingCustomer.is_active !== undefined ? editingCustomer.is_active : true,
      });
    }
  }, [editingCustomer]);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: null }));
    }
  };

  const validateForm = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'اسم العميل مطلوب';
    } else if (formData.name.length < 3) {
      newErrors.name = 'اسم العميل يجب أن يكون 3 أحرف على الأقل';
    }

    if (formData.phone && !/^01[0-9]{9}$/.test(formData.phone)) {
      newErrors.phone = 'رقم الهاتف غير صحيح (يجب أن يبدأ بـ 01 ويتكون من 11 رقم)';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validateForm()) {
      console.log('Validation failed:', errors);
      return;
    }

    console.log('Submitting customer data:', formData);
    setSaving(true);
    try {
      let response;
      if (editingCustomer) {
        console.log('Updating customer:', editingCustomer.id);
        response = await apiClient.put(`/customers/${editingCustomer.id}`, formData);
      } else {
        console.log('Creating new customer');
        response = await apiClient.post('/customers', formData);
      }
      
      console.log('Customer saved successfully:', response.data);
      
      onSuccess();
      handleClose();
    } catch (error) {
      console.error('Error saving customer:', error);
      if (error.response?.data?.errors) {
        console.error('Validation errors:', error.response.data.errors);
        setErrors(error.response.data.errors);
      } else {
        console.error('Server error:', error.response?.data);
        setErrors({ submit: 'حدث خطأ أثناء حفظ البيانات' });
      }
    } finally {
      setSaving(false);
    }
  };

  const handleClose = () => {
    setFormData({
      name: '',
      type: 'retail',
      phone: '',
      address: '',
      notes: '',
      is_active: true,
    });
    setErrors({});
    onClose();
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
              <User className="w-5 h-5 text-primary-600" />
            </div>
            <h2 className="text-2xl font-bold text-gray-900">
              {editingCustomer ? 'تعديل بيانات العميل' : 'إضافة عميل جديد'}
            </h2>
          </div>
          <button
            onClick={handleClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6">
          {/* Error message */}
          {errors.submit && (
            <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
              {errors.submit}
            </div>
          )}

          {/* Basic Information */}
          <div className="mb-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">المعلومات الأساسية</h3>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {/* Name */}
              <div className="md:col-span-2">
                <Input
                  label="اسم العميل"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  error={errors.name}
                  required
                  placeholder="أدخل اسم العميل الكامل"
                />
              </div>

              {/* Type */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  نوع العميل
                  <span className="text-red-500 mr-1">*</span>
                </label>
                <select
                  name="type"
                  value={formData.type}
                  onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                >
                  <option value="retail">قطاعي (Retail)</option>
                  <option value="wholesale">جملة (Wholesale)</option>
                </select>
              </div>

              {/* Phone */}
              <div>
                <Input
                  label="رقم الهاتف"
                  name="phone"
                  type="tel"
                  value={formData.phone}
                  onChange={handleChange}
                  error={errors.phone}
                  placeholder="01012345678"
                  dir="ltr"
                />
              </div>
            </div>
          </div>

          {/* Address */}
          <div className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              العنوان
            </label>
            <textarea
              name="address"
              value={formData.address}
              onChange={handleChange}
              rows="2"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
              placeholder="العنوان الكامل للعميل"
            />
          </div>

          {/* Notes */}
          <div className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              ملاحظات
            </label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              rows="3"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
              placeholder="أي ملاحظات إضافية عن العميل..."
            />
          </div>

          {/* Status */}
          <div className="mb-6">
            <label className="flex items-center gap-3 cursor-pointer">
              <input
                type="checkbox"
                name="is_active"
                checked={formData.is_active}
                onChange={handleChange}
                className="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
              />
              <span className="text-sm font-medium text-gray-700">
                العميل نشط
              </span>
            </label>
            <p className="mr-8 text-xs text-gray-500 mt-1">
              إذا تم إلغاء التفعيل، لن يظهر العميل في قوائم البحث الافتراضية
            </p>
          </div>

          {/* Actions */}
          <div className="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <Button
              type="button"
              variant="outline"
              onClick={handleClose}
              disabled={saving}
            >
              إلغاء
            </Button>
            <Button
              type="submit"
              disabled={saving}
              icon={saving ? undefined : Save}
            >
              {saving ? (
                <>
                  <span className="animate-spin rounded-full h-4 w-4 border-b-2 border-white ml-2"></span>
                  جاري الحفظ...
                </>
              ) : (
                editingCustomer ? 'تحديث البيانات' : 'حفظ العميل'
              )}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CustomerForm;
