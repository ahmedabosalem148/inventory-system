import React, { useState, useEffect, useRef } from 'react';
import { X, Plus, Trash2, User, Package, Calendar, Hash } from 'lucide-react';
import { Button, Input, Badge } from '../../atoms';
import { Autocomplete } from '../../molecules';
import apiClient from '../../../utils/axios';

/**
 * IssueVoucherForm Component
 * 
 * Form for creating and editing issue vouchers
 * Includes customer selection, product selection with stock validation,
 * and automatic total calculation
 */
const IssueVoucherForm = ({ voucher, onSubmit, onClose }) => {
  const isEdit = !!voucher;

  // Form State
  const [formData, setFormData] = useState({
    customer_id: '',
    customer_name: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
    status: 'pending'
  });

  const [items, setItems] = useState([]);
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  // Autocomplete States
  const [customers, setCustomers] = useState([]);
  const [products, setProducts] = useState([]);
  const [loadingCustomers, setLoadingCustomers] = useState(false);
  const [loadingProducts, setLoadingProducts] = useState(false);
  // Memoization caches
  const customersCache = useRef({});
  const productsCache = useRef({});
  // AbortControllers for cancellation
  const customersAbort = useRef(null);
  const productsAbort = useRef(null);

  // Mock data for customers (TODO: Replace with API)
  const mockCustomers = [
    { id: 1, name: 'أحمد محمد علي', phone: '01234567890', balance: 5000 },
    { id: 2, name: 'فاطمة حسن', phone: '01123456789', balance: 2000 },
    { id: 3, name: 'محمود السيد', phone: '01098765432', balance: 8000 },
    { id: 4, name: 'نور الدين', phone: '01156789012', balance: 1500 },
    { id: 5, name: 'سارة أحمد', phone: '01245678901', balance: 3000 }
  ];

  // Mock data for products (TODO: Replace with API)
  const mockProducts = [
    { id: 1, name: 'لابتوب Dell XPS', unit: 'قطعة', price: 25000, stock: 15 },
    { id: 2, name: 'قميص قطن', unit: 'قطعة', price: 150, stock: 50 },
    { id: 3, name: 'أرز بسمتي', unit: 'كيلوجرام', price: 25, stock: 200 },
    { id: 4, name: 'كتاب PHP', unit: 'قطعة', price: 120, stock: 30 },
    { id: 5, name: 'مفك كهربائي', unit: 'قطعة', price: 450, stock: 20 },
    { id: 6, name: 'آيفون 15', unit: 'قطعة', price: 45000, stock: 8 }
  ];

  useEffect(() => {
    if (voucher) {
      setFormData({
        customer_id: voucher.customer_id,
        customer_name: voucher.customer_name || '',
        date: voucher.date,
        notes: voucher.notes || '',
        status: voucher.status
      });
      setItems(voucher.items || []);
    }
  }, [voucher]);

  // Search customers with memoization & cancellation
  const handleSearchCustomers = (() => {
    let debounceTimer;
    return async (searchTerm) => {
      if (!searchTerm) {
        setCustomers([]);
        return;
      }
      // Memoization: return cached results if available
      if (customersCache.current[searchTerm]) {
        setCustomers(customersCache.current[searchTerm]);
        return;
      }
      // Debounce: clear previous timer
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = setTimeout(async () => {
        setLoadingCustomers(true);
        // Cancel previous request
        if (customersAbort.current) customersAbort.current.abort();
        customersAbort.current = new AbortController();
        try {
          const response = await apiClient.get('/customers', {
            params: { search: searchTerm, per_page: 20 },
            signal: customersAbort.current.signal
          });
          const result = response.data?.data || [];
          setCustomers(result);
          customersCache.current[searchTerm] = result;
        } catch (error) {
          if (error.code === 'ERR_CANCELED' || error.name === 'CanceledError') return;
          // Fallback to mock data
          const filtered = mockCustomers.filter(c =>
            c.name.includes(searchTerm) || c.phone.includes(searchTerm)
          );
          setCustomers(filtered);
          customersCache.current[searchTerm] = filtered;
        } finally {
          setLoadingCustomers(false);
        }
      }, 450); // Debounce 450ms
    };
  })();

  // Search products with memoization & cancellation
  const handleSearchProducts = (() => {
    let debounceTimer;
    return async (searchTerm) => {
      if (!searchTerm) {
        setProducts([]);
        return;
      }
      // Memoization: return cached results if available
      if (productsCache.current[searchTerm]) {
        setProducts(productsCache.current[searchTerm]);
        return;
      }
      // Debounce: clear previous timer
      if (debounceTimer) clearTimeout(debounceTimer);
      debounceTimer = setTimeout(async () => {
        setLoadingProducts(true);
        // Cancel previous request
        if (productsAbort.current) productsAbort.current.abort();
        productsAbort.current = new AbortController();
        try {
          const response = await apiClient.get('/products', {
            params: { search: searchTerm, per_page: 20 },
            signal: productsAbort.current.signal
          });
          const result = (response.data?.data || []).map(product => ({
            id: product.id,
            name: product.name,
            unit: product.unit,
            price: product.sale_price,
            stock: product.min_stock
          }));
          setProducts(result);
          productsCache.current[searchTerm] = result;
        } catch (error) {
          if (error.code === 'ERR_CANCELED' || error.name === 'CanceledError') return;
          // Fallback to mock data
          const filtered = mockProducts.filter(p =>
            p.name.includes(searchTerm)
          );
          setProducts(filtered);
          productsCache.current[searchTerm] = filtered;
        } finally {
          setLoadingProducts(false);
        }
      }, 450); // Debounce 450ms
    };
  })();

  // Add product to items
  const handleAddProduct = (product) => {
    // Check if already added
    const existingItem = items.find(item => item.product_id === product.id);
    if (existingItem) {
      alert('هذا المنتج موجود بالفعل في القائمة');
      return;
    }

    const newItem = {
      product_id: product.id,
      product_name: product.name,
      unit: product.unit,
      price: product.price,
      quantity: 1,
      available_stock: product.stock,
      total: product.price * 1
    };

    setItems([...items, newItem]);
  };

  // Update item quantity
  const handleQuantityChange = (index, quantity) => {
    const newItems = [...items];
    const item = newItems[index];
    const qty = parseFloat(quantity) || 0;

    // Validate against stock
    if (qty > item.available_stock) {
      alert(`الكمية المتاحة في المخزن: ${item.available_stock} ${item.unit}`);
      return;
    }

    item.quantity = qty;
    item.total = item.price * qty;
    setItems(newItems);
  };

  // Update item price
  const handlePriceChange = (index, price) => {
    const newItems = [...items];
    const item = newItems[index];
    const p = parseFloat(price) || 0;

    item.price = p;
    item.total = item.quantity * p;
    setItems(newItems);
  };

  // Remove item
  const handleRemoveItem = (index) => {
    setItems(items.filter((_, i) => i !== index));
  };

  // Calculate totals
  const calculateTotals = () => {
    const subtotal = items.reduce((sum, item) => sum + item.total, 0);
    return {
      subtotal,
      total: subtotal
    };
  };

  // Validate form
  const validateForm = () => {
    const newErrors = {};

    if (!formData.customer_id) {
      newErrors.customer_id = 'يجب اختيار العميل';
    }

    if (!formData.date) {
      newErrors.date = 'يجب تحديد التاريخ';
    }

    if (items.length === 0) {
      newErrors.items = 'يجب إضافة منتج واحد على الأقل';
    }

    // Validate each item
    items.forEach((item, index) => {
      if (item.quantity <= 0) {
        newErrors[`item_${index}_quantity`] = 'الكمية يجب أن تكون أكبر من صفر';
      }
      if (item.quantity > item.available_stock) {
        newErrors[`item_${index}_quantity`] = `الكمية تتجاوز المخزون المتاح (${item.available_stock})`;
      }
      if (item.price <= 0) {
        newErrors[`item_${index}_price`] = 'السعر يجب أن يكون أكبر من صفر';
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

    setLoading(true);

    try {
      const totals = calculateTotals();
      const data = {
        ...formData,
        items,
        total_amount: totals.total
      };

      await onSubmit(data);
    } catch (error) {
      console.error('Error submitting voucher:', error);
      alert('حدث خطأ أثناء حفظ الإذن');
    } finally {
      setLoading(false);
    }
  };

  const totals = calculateTotals();

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b">
          <h2 className="text-2xl font-bold text-gray-900">
            {isEdit ? 'تعديل إذن صرف' : 'إذن صرف جديد'}
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Form Content */}
        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto">
          <div className="p-6 space-y-6">
            {/* Voucher Info Section */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {/* Customer Selection */}
              <div>
                <Autocomplete
                  label="العميل"
                  placeholder="ابحث عن عميل..."
                  options={customers}
                  onSearch={handleSearchCustomers}
                  onSelect={(customer) => {
                    setFormData({ 
                      ...formData, 
                      customer_id: customer?.id || '', 
                      customer_name: customer?.name || '' 
                    });
                    setErrors({ ...errors, customer_id: '' });
                  }}
                  getOptionLabel={(customer) => customer.name}
                  getOptionValue={(customer) => customer.id}
                  value={formData.customer_id}
                  loading={loadingCustomers}
                  required
                  error={errors.customer_id}
                  renderOption={(customer) => (
                    <div className="flex items-center justify-between">
                      <div>
                        <div className="font-medium text-gray-900">{customer.name}</div>
                          <div className="text-sm text-gray-500">
                            {customer.phone || customer.code || 'لا يوجد رقم'}
                          </div>
                      </div>
                      <div className="text-sm">
                        <span className="text-gray-600">الرصيد: </span>
                        <span className="font-semibold text-primary-600">
                            {(customer.balance || 0).toLocaleString('ar-EG')} جنيه
                        </span>
                      </div>
                    </div>
                  )}
                />
              </div>

              {/* Date */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  التاريخ
                  <span className="text-red-500 mr-1">*</span>
                </label>
                <div className="relative">
                  <Calendar className="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    type="date"
                    value={formData.date}
                    onChange={(e) => setFormData({ ...formData, date: e.target.value })}
                    className={`w-full px-4 py-2 pr-10 border rounded-lg focus:outline-none focus:ring-2 ${
                      errors.date
                        ? 'border-red-300 focus:ring-red-500'
                        : 'border-gray-300 focus:ring-primary-500'
                    }`}
                  />
                </div>
                {errors.date && (
                  <p className="mt-1 text-sm text-red-600">{errors.date}</p>
                )}
              </div>
            </div>

            {/* Notes */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                ملاحظات
              </label>
              <textarea
                value={formData.notes}
                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                rows={3}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
                placeholder="أضف ملاحظات إضافية..."
              />
            </div>

            {/* Product Selection Section */}
            <div className="border-t pt-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <Package className="w-5 h-5 ml-2" />
                المنتجات
              </h3>

              {/* Product Autocomplete */}
              <div className="mb-4">
                <Autocomplete
                  placeholder="ابحث عن منتج لإضافته..."
                  options={products}
                  onSearch={handleSearchProducts}
                  onSelect={(product) => {
                    if (product) {
                      handleAddProduct(product);
                    }
                  }}
                  getOptionLabel={(product) => product.name}
                  getOptionValue={(product) => product.id}
                  loading={loadingProducts}
                  error={errors.items}
                  renderOption={(product) => (
                    <div className="flex items-center justify-between">
                      <div>
                        <div className="font-medium text-gray-900">{product.name}</div>
                        <div className="text-sm text-gray-500">
                            {product.unit || 'قطعة'} - {(product.price || 0).toLocaleString('ar-EG')} جنيه
                        </div>
                      </div>
                      <div>
                        <Badge
                            variant={(product.stock || 0) > 10 ? 'success' : (product.stock || 0) > 0 ? 'warning' : 'danger'}
                        >
                            المخزون: {product.stock || 0}
                        </Badge>
                      </div>
                    </div>
                  )}
                />
              </div>

              {/* Items Table */}
              {items.length > 0 && (
                <div className="border rounded-lg overflow-hidden">
                  <table className="w-full">
                    <thead className="bg-gray-50">
                      <tr>
                        <th className="px-4 py-3 text-right text-sm font-semibold text-gray-700">المنتج</th>
                        <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">الكمية</th>
                        <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">السعر</th>
                        <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">الإجمالي</th>
                        <th className="px-4 py-3 text-center text-sm font-semibold text-gray-700">إجراءات</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                      {items.map((item, index) => (
                        <tr key={index} className="hover:bg-gray-50">
                          <td className="px-4 py-3">
                            <div>
                              <div className="font-medium text-gray-900">{item.product_name}</div>
                              <div className="text-sm text-gray-500">
                                المخزون: {item.available_stock} {item.unit}
                              </div>
                            </div>
                          </td>
                          <td className="px-4 py-3">
                            <input
                              type="number"
                              value={item.quantity}
                              onChange={(e) => handleQuantityChange(index, e.target.value)}
                              min="0"
                              step="0.01"
                              max={item.available_stock}
                              className={`w-24 px-2 py-1 text-center border rounded ${
                                errors[`item_${index}_quantity`]
                                  ? 'border-red-300'
                                  : 'border-gray-300'
                              }`}
                            />
                            {errors[`item_${index}_quantity`] && (
                              <div className="text-xs text-red-600 mt-1">
                                {errors[`item_${index}_quantity`]}
                              </div>
                            )}
                          </td>
                          <td className="px-4 py-3">
                            <input
                              type="number"
                              value={item.price}
                              onChange={(e) => handlePriceChange(index, e.target.value)}
                              min="0"
                              step="0.01"
                              className={`w-28 px-2 py-1 text-center border rounded ${
                                errors[`item_${index}_price`]
                                  ? 'border-red-300'
                                  : 'border-gray-300'
                              }`}
                            />
                          </td>
                          <td className="px-4 py-3 text-center font-semibold text-gray-900">
                            {item.total.toLocaleString('ar-EG')} جنيه
                          </td>
                          <td className="px-4 py-3 text-center">
                            <Button
                              type="button"
                              variant="danger"
                              size="sm"
                              onClick={() => handleRemoveItem(index)}
                            >
                              <Trash2 className="w-4 h-4" />
                            </Button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}

              {items.length === 0 && (
                <div className="text-center py-8 text-gray-500 border-2 border-dashed rounded-lg">
                  <Package className="w-12 h-12 mx-auto mb-2 text-gray-400" />
                  <p>لم يتم إضافة أي منتجات بعد</p>
                  <p className="text-sm">استخدم البحث أعلاه لإضافة المنتجات</p>
                </div>
              )}
            </div>

            {/* Totals Section */}
            {items.length > 0 && (
              <div className="border-t pt-4">
                <div className="flex justify-end">
                  <div className="w-full md:w-1/3 space-y-2">
                    <div className="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t-2">
                      <span>الإجمالي:</span>
                      <span className="text-primary-600">
                        {totals.total.toLocaleString('ar-EG')} جنيه
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="flex items-center justify-end gap-3 p-6 border-t bg-gray-50">
            <Button
              type="button"
              variant="outline"
              onClick={onClose}
              disabled={loading}
            >
              إلغاء
            </Button>
            <Button
              type="submit"
              variant="primary"
              loading={loading}
              disabled={loading || items.length === 0}
            >
              {isEdit ? 'حفظ التعديلات' : 'إنشاء الإذن'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default IssueVoucherForm;
