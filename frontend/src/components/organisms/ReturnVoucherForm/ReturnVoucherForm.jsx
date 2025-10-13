import React, { useState, useEffect } from 'react';
import { Button, Input, Badge } from '../../atoms';
import { Autocomplete } from '../../molecules';
import { X, Save, Package, AlertCircle, RotateCcw } from 'lucide-react';
import apiClient from '../../../utils/axios';

/**
 * Return Voucher Form Component
 * 
 * Form for creating/editing return vouchers
 * - Customer selection with autocomplete
 * - Product selection with autocomplete
 * - Multiple product items with quantity
 * - Real-time total calculation
 */
const ReturnVoucherForm = ({ isOpen, onClose, onSuccess, editingVoucher = null }) => {
  const [formData, setFormData] = useState({
    customer_id: null,
    return_date: new Date().toISOString().split('T')[0],
    notes: '',
    items: [],
  });

  const [selectedCustomer, setSelectedCustomer] = useState(null);
  const [customers, setCustomers] = useState([]);
  const [loadingCustomers, setLoadingCustomers] = useState(false);

  const [products, setProducts] = useState([]);
  const [loadingProducts, setLoadingProducts] = useState(false);

  const [errors, setErrors] = useState({});
  const [saving, setSaving] = useState(false);

  // Load initial data if editing
  useEffect(() => {
    if (editingVoucher) {
      setFormData({
        customer_id: editingVoucher.customer_id,
        return_date: editingVoucher.return_date,
        notes: editingVoucher.notes || '',
        items: editingVoucher.items || [],
      });
      setSelectedCustomer(editingVoucher.customer);
    }
  }, [editingVoucher]);

  // Search customers
  const handleCustomerSearch = async (query) => {
    if (!query || query.length < 2) {
      setCustomers([]);
      return;
    }

    setLoadingCustomers(true);
    try {
      const response = await apiClient.get('/customers', {
        params: { search: query, per_page: 10 }
      });
      setCustomers(response.data.data || []);
    } catch (error) {
      console.error('Error searching customers:', error);
      setCustomers([]);
    } finally {
      setLoadingCustomers(false);
    }
  };

  // Search products
  const handleProductSearch = async (query) => {
    if (!query || query.length < 2) {
      setProducts([]);
      return;
    }

    setLoadingProducts(true);
    try {
      const response = await apiClient.get('/products', {
        params: { search: query, per_page: 10, is_active: 1 }
      });
      setProducts(response.data.data || []);
    } catch (error) {
      console.error('Error searching products:', error);
      setProducts([]);
    } finally {
      setLoadingProducts(false);
    }
  };

  // Handle customer selection
  const handleCustomerSelect = (customer) => {
    setSelectedCustomer(customer);
    setFormData(prev => ({ ...prev, customer_id: customer.id }));
    setErrors(prev => ({ ...prev, customer_id: null }));
  };

  // Add product to items
  const handleProductSelect = (product) => {
    // Check if product already in list
    const existingItem = formData.items.find(item => item.product_id === product.id);
    if (existingItem) {
      // Increase quantity
      const newItems = formData.items.map(item =>
        item.product_id === product.id
          ? { ...item, quantity: item.quantity + 1 }
          : item
      );
      setFormData(prev => ({ ...prev, items: newItems }));
    } else {
      // Add new item
      const newItem = {
        product_id: product.id,
        product_name: product.name,
        unit: product.unit,
        unit_price: parseFloat(product.sale_price) || 0,
        quantity: 1,
      };
      setFormData(prev => ({
        ...prev,
        items: [...prev.items, newItem]
      }));
    }
    setErrors(prev => ({ ...prev, items: null }));
  };

  // Update item quantity
  const handleQuantityChange = (index, quantity) => {
    const newQuantity = parseInt(quantity) || 0;
    
    if (newQuantity < 0) {
      setErrors(prev => ({
        ...prev,
        [`item_${index}_quantity`]: 'الكمية يجب أن تكون رقم موجب'
      }));
      return;
    }

    setErrors(prev => ({
      ...prev,
      [`item_${index}_quantity`]: null
    }));

    const newItems = formData.items.map((item, i) =>
      i === index ? { ...item, quantity: newQuantity } : item
    );
    setFormData(prev => ({ ...prev, items: newItems }));
  };

  // Update item price
  const handlePriceChange = (index, price) => {
    const newPrice = parseFloat(price) || 0;
    const newItems = formData.items.map((item, i) =>
      i === index ? { ...item, unit_price: newPrice } : item
    );
    setFormData(prev => ({ ...prev, items: newItems }));
  };

  // Remove item
  const handleRemoveItem = (index) => {
    const newItems = formData.items.filter((_, i) => i !== index);
    setFormData(prev => ({ ...prev, items: newItems }));
  };

  // Calculate totals
  const calculateTotal = () => {
    return formData.items.reduce((sum, item) => {
      return sum + (item.quantity * item.unit_price);
    }, 0);
  };

  // Validate form
  const validateForm = () => {
    const newErrors = {};

    if (!formData.customer_id) {
      newErrors.customer_id = 'يجب اختيار العميل';
    }

    if (!formData.return_date) {
      newErrors.return_date = 'يجب إدخال تاريخ الإرجاع';
    }

    if (formData.items.length === 0) {
      newErrors.items = 'يجب إضافة منتج واحد على الأقل';
    }

    // Validate each item quantity
    formData.items.forEach((item, index) => {
      if (item.quantity <= 0) {
        newErrors[`item_${index}_quantity`] = 'الكمية يجب أن تكون أكبر من صفر';
      }
    });

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  // Handle submit
  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    setSaving(true);
    try {
      const payload = {
        customer_id: formData.customer_id,
        customer_name: formData.customer_id ? undefined : 'عميل نقدي',
        branch_id: 1, // TODO: Get from user context
        return_date: formData.return_date,
        notes: formData.notes,
        items: formData.items.map(item => ({
          product_id: item.product_id,
          quantity: item.quantity,
          unit_price: item.unit_price,
          total_price: item.quantity * item.unit_price,
        })),
        total_amount: calculateTotal(),
      };

      if (editingVoucher) {
        await apiClient.put(`/return-vouchers/${editingVoucher.id}`, payload);
        alert('تم تحديث إذن الإرجاع بنجاح');
      } else {
        await apiClient.post('/return-vouchers', payload);
        alert('تم إنشاء إذن الإرجاع بنجاح');
      }

      onSuccess();
      handleClose();
    } catch (error) {
      console.error('Error saving return voucher:', error);
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors);
        const errorMessages = Object.values(error.response.data.errors).flat().join('\n');
        alert('أخطاء في البيانات:\n' + errorMessages);
      } else if (error.response?.data?.message) {
        alert(error.response.data.message);
      } else {
        alert('حدث خطأ أثناء حفظ الإذن. تأكد من الاتصال بالخادم.');
      }
    } finally {
      setSaving(false);
    }
  };

  // Handle close
  const handleClose = () => {
    setFormData({
      customer_id: null,
      return_date: new Date().toISOString().split('T')[0],
      notes: '',
      items: [],
    });
    setSelectedCustomer(null);
    setErrors({});
    onClose();
  };

  if (!isOpen) return null;

  const total = calculateTotal();

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-5xl w-full max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-orange-100">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center">
              <RotateCcw className="w-6 h-6 text-white" />
            </div>
            <div>
              <h2 className="text-2xl font-bold text-gray-900">
                {editingVoucher ? 'تعديل إذن الإرجاع' : 'إذن إرجاع جديد'}
              </h2>
              <p className="text-sm text-gray-600">إضافة منتجات مرتجعة من العميل</p>
            </div>
          </div>
          <button
            onClick={handleClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 overflow-y-auto max-h-[calc(90vh-180px)]">
          {/* Customer & Date Section */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            {/* Customer Autocomplete */}
            <div>
              <Autocomplete
                label="العميل"
                placeholder="ابحث عن العميل..."
                options={customers}
                onSearch={handleCustomerSearch}
                onSelect={handleCustomerSelect}
                value={selectedCustomer ? selectedCustomer.name : ''}
                loading={loadingCustomers}
                required
                error={errors.customer_id}
                getOptionLabel={(customer) => customer.name}
                getOptionValue={(customer) => customer.id}
                renderOption={(customer) => (
                  <div className="flex flex-col">
                    <span className="font-medium">{customer.name}</span>
                    {customer.phone && (
                      <span className="text-sm text-gray-500">{customer.phone}</span>
                    )}
                  </div>
                )}
                emptyMessage="لا يوجد عملاء"
                minChars={2}
              />
            </div>

            {/* Return Date */}
            <div>
              <Input
                label="تاريخ الإرجاع"
                type="date"
                value={formData.return_date}
                onChange={(e) => setFormData(prev => ({ ...prev, return_date: e.target.value }))}
                required
                error={errors.return_date}
              />
            </div>
          </div>

          {/* Product Selection */}
          <div className="mb-6">
            <Autocomplete
              label="إضافة منتج مُرتجع"
              placeholder="ابحث عن المنتج..."
              options={products}
              onSearch={handleProductSearch}
              onSelect={handleProductSelect}
              value=""
              loading={loadingProducts}
              error={errors.items}
              getOptionLabel={(product) => product.name}
              getOptionValue={(product) => product.id}
              renderOption={(product) => (
                <div className="flex justify-between items-center">
                  <div className="flex flex-col">
                    <span className="font-medium">{product.name}</span>
                    <span className="text-sm text-gray-500">
                      {product.unit} | {parseFloat(product.sale_price).toFixed(2)} جنيه
                    </span>
                  </div>
                </div>
              )}
              emptyMessage="لا يوجد منتجات"
              minChars={2}
            />
          </div>

          {/* Items Table */}
          {formData.items.length > 0 && (
            <div className="mb-6 border border-gray-200 rounded-lg overflow-hidden">
              <div className="bg-orange-50 px-4 py-2 border-b border-orange-200">
                <h3 className="text-sm font-semibold text-orange-900 flex items-center gap-2">
                  <Package className="w-4 h-4" />
                  المنتجات المُرتجعة
                </h3>
              </div>
              
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        المنتج
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        الوحدة
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        الكمية
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        السعر
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        الإجمالي
                      </th>
                      <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                        إجراءات
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {formData.items.map((item, index) => (
                      <tr key={index} className="hover:bg-gray-50">
                        <td className="px-4 py-3">
                          <div className="flex items-center">
                            <RotateCcw className="w-4 h-4 text-orange-500 ml-2" />
                            <span className="font-medium text-gray-900">{item.product_name}</span>
                          </div>
                        </td>
                        <td className="px-4 py-3 text-gray-700">
                          {item.unit}
                        </td>
                        <td className="px-4 py-3">
                          <Input
                            type="number"
                            min="1"
                            value={item.quantity}
                            onChange={(e) => handleQuantityChange(index, e.target.value)}
                            error={errors[`item_${index}_quantity`]}
                            className="w-24"
                          />
                        </td>
                        <td className="px-4 py-3">
                          <Input
                            type="number"
                            step="0.01"
                            min="0"
                            value={item.unit_price}
                            onChange={(e) => handlePriceChange(index, e.target.value)}
                            className="w-28"
                          />
                        </td>
                        <td className="px-4 py-3 font-semibold text-gray-900">
                          {(item.quantity * item.unit_price).toFixed(2)} جنيه
                        </td>
                        <td className="px-4 py-3 text-center">
                          <button
                            type="button"
                            onClick={() => handleRemoveItem(index)}
                            className="text-red-600 hover:text-red-800 transition-colors"
                          >
                            <X className="w-5 h-5" />
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* Total */}
              <div className="bg-orange-50 px-4 py-3 border-t border-orange-200">
                <div className="flex justify-between items-center">
                  <span className="text-lg font-semibold text-gray-700">إجمالي المرتجع:</span>
                  <span className="text-2xl font-bold text-orange-600">
                    {total.toFixed(2)} جنيه
                  </span>
                </div>
              </div>
            </div>
          )}

          {/* Notes */}
          <div className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-1">
              ملاحظات
            </label>
            <textarea
              value={formData.notes}
              onChange={(e) => setFormData(prev => ({ ...prev, notes: e.target.value }))}
              rows="3"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors"
              placeholder="سبب الإرجاع أو ملاحظات إضافية..."
            />
          </div>

          {/* Info Box */}
          <div className="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start">
            <AlertCircle className="w-5 h-5 text-blue-600 ml-2 mt-0.5 flex-shrink-0" />
            <div className="text-sm text-blue-800">
              <p className="font-medium mb-1">ملاحظة هامة:</p>
              <p>سيتم إضافة كمية المنتجات المرتجعة تلقائياً إلى المخزون بعد حفظ الإذن.</p>
            </div>
          </div>
        </form>

        {/* Footer */}
        <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
          <Button
            variant="outline"
            onClick={handleClose}
            disabled={saving}
          >
            إلغاء
          </Button>
          <Button
            onClick={handleSubmit}
            disabled={saving || formData.items.length === 0}
            icon={saving ? undefined : Save}
            className="bg-orange-600 hover:bg-orange-700"
          >
            {saving ? (
              <>
                <span className="animate-spin rounded-full h-4 w-4 border-b-2 border-white ml-2"></span>
                جاري الحفظ...
              </>
            ) : (
              editingVoucher ? 'تحديث الإذن' : 'حفظ الإذن'
            )}
          </Button>
        </div>
      </div>
    </div>
  );
};

export default ReturnVoucherForm;
