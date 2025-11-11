import { useState, useEffect } from 'react'
import { X, Plus, Trash2, RotateCcw, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { showToast } from '@/components/ui/toast'
import { apiClient } from '@/services/api/client'
import { CustomerSearchSelect } from '@/components/CustomerSearchSelect'

interface Customer {
  id: number
  name: string
  code: string
}

interface Product {
  id: number
  name: string
  code: string
  selling_price: number
}

interface Branch {
  id: number
  name: string
  code: string
}

interface ReturnVoucherItem {
  id: string
  product_id: number | null
  product_name: string
  quantity: number
  unit_price: number
  discount: number
  total: number
}

interface ReturnVoucherFormData {
  voucher_number: string
  issue_voucher_id: number | null
  customer_id: number | null
  customer_name: string
  branch_id: number | null
  return_date: string
  reason: string
  reason_category: string
  notes: string
  items: ReturnVoucherItem[]
  status: 'draft' | 'approved'
}

interface ReturnVoucherDialogProps {
  open: boolean
  onClose: () => void
  onSuccess: () => void
  customerId?: number
}

export default function ReturnVoucherDialog({
  open,
  onClose,
  onSuccess,
  customerId,
}: ReturnVoucherDialogProps) {
  const [formData, setFormData] = useState<ReturnVoucherFormData>({
    voucher_number: '',
    issue_voucher_id: null,
    customer_id: customerId || null,
    customer_name: '',
    branch_id: null,
    return_date: new Date().toISOString().split('T')[0],
    reason: '',
    reason_category: '',
    notes: '',
    items: [],
    status: 'draft',
  })
  const [loading, setLoading] = useState(false)
  const [customers, setCustomers] = useState<Customer[]>([])
  const [products, setProducts] = useState<Product[]>([])
  const [branches, setBranches] = useState<Branch[]>([])
  const [searchProduct, setSearchProduct] = useState<{ [key: string]: string }>({})

  useEffect(() => {
    if (open) {
      loadCustomers()
      loadProducts()
      loadBranches()
      // Add initial empty item
      if (formData.items.length === 0) {
        addItem()
      }
    }
  }, [open])

  const loadCustomers = async () => {
    try {
      const response = await apiClient.get('/customers', {
        params: { per_page: 100 }
      })
      setCustomers(response.data.data || [])
    } catch (error) {
      console.error('Error loading customers:', error)
    }
  }

  const loadProducts = async () => {
    try {
      const response = await apiClient.get('/products', {
        params: { per_page: 500 }
      })
      setProducts(response.data.data || [])
    } catch (error) {
      console.error('Error loading products:', error)
    }
  }

  const loadBranches = async () => {
    try {
      const response = await apiClient.get('/branches', {
        params: { per_page: 100 }
      })
      setBranches(response.data.data || [])
      
      // Set first branch as default if available
      if (response.data.data.length > 0 && !formData.branch_id) {
        setFormData(prev => ({ ...prev, branch_id: response.data.data[0].id }))
      }
    } catch (error) {
      console.error('Error loading branches:', error)
    }
  }

  const addItem = () => {
    const newItem: ReturnVoucherItem = {
      id: Date.now().toString(),
      product_id: null,
      product_name: '',
      quantity: 1,
      unit_price: 0,
      discount: 0,
      total: 0,
    }
    setFormData((prev) => ({
      ...prev,
      items: [...prev.items, newItem],
    }))
  }

  const removeItem = (id: string) => {
    setFormData((prev) => ({
      ...prev,
      items: prev.items.filter((item) => item.id !== id),
    }))
  }

  const updateItem = (id: string, field: keyof ReturnVoucherItem, value: any) => {
    setFormData((prev) => ({
      ...prev,
      items: prev.items.map((item) => {
        if (item.id !== id) return item

        const updatedItem = { ...item, [field]: value }

        // Update product details when product is selected
        if (field === 'product_id') {
          const product = products.find((p) => p.id === value)
          if (product) {
            updatedItem.product_name = product.name
            updatedItem.unit_price = product.selling_price
          }
        }

        // Recalculate total
        const quantity = parseFloat(String(updatedItem.quantity)) || 0
        const unit_price = parseFloat(String(updatedItem.unit_price)) || 0
        const discount = parseFloat(String(updatedItem.discount)) || 0
        updatedItem.total = (quantity * unit_price) - discount

        return updatedItem
      }),
    }))
  }

  const handleSubmit = async (saveAsDraft: boolean = false) => {
    // Validation
    if (!formData.branch_id) {
      showToast.error('الرجاء اختيار الفرع')
      return
    }

    if (!formData.customer_id && !formData.customer_name) {
      showToast.error('الرجاء اختيار العميل أو إدخال اسمه')
      return
    }

    if (!formData.reason.trim()) {
      showToast.error('الرجاء إدخال سبب الإرجاع')
      return
    }

    if (formData.items.length === 0) {
      showToast.error('الرجاء إضافة صنف واحد على الأقل')
      return
    }

    // Validate items
    for (const item of formData.items) {
      if (!item.product_id) {
        showToast.error('الرجاء اختيار صنف لجميع البنود')
        return
      }
      if (item.quantity <= 0) {
        showToast.error('الرجاء إدخال كمية صحيحة لجميع البنود')
        return
      }
      if (item.unit_price < 0) {
        showToast.error('سعر الوحدة يجب أن يكون أكبر من أو يساوي صفر')
        return
      }
    }

    try {
      setLoading(true)

      // Prepare request data
      const requestData = {
        customer_id: formData.customer_id,
        customer_name: formData.customer_name || customers.find(c => c.id === formData.customer_id)?.name || '',
        branch_id: formData.branch_id,
        return_date: formData.return_date,
        reason: formData.reason,
        notes: formData.notes || null,
        items: formData.items.map((item) => ({
          product_id: item.product_id,
          quantity: parseFloat(String(item.quantity)),
          unit_price: parseFloat(String(item.unit_price)),
          discount: parseFloat(String(item.discount)) || 0,
        })),
        status: saveAsDraft ? 'draft' : 'approved',
      }

      const response = await apiClient.post('/return-vouchers', requestData)

      showToast.success(
        saveAsDraft 
          ? 'تم حفظ إذن الإرجاع كمسودة بنجاح' 
          : 'تم إنشاء إذن الإرجاع بنجاح'
      )
      
      onSuccess()
      
      // Redirect to details page
      if (response.data.data?.id) {
        window.location.hash = `return-vouchers/${response.data.data.id}`
      }
      
      onClose()
    } catch (error: any) {
      const message = error.response?.data?.message || 'حدث خطأ أثناء إنشاء إذن الإرجاع'
      showToast.error(message)
    } finally {
      setLoading(false)
    }
  }

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>
  ) => {
    const { name, value } = e.target
    setFormData((prev) => ({
      ...prev,
      [name]: name === 'customer_id' || name === 'branch_id' 
        ? (value ? parseInt(value) : null)
        : value,
    }))
  }

  const calculateTotals = () => {
    const subtotal = formData.items.reduce((sum, item) => {
      const itemSubtotal = (parseFloat(String(item.quantity)) || 0) * (parseFloat(String(item.unit_price)) || 0)
      return sum + itemSubtotal
    }, 0)
    
    const totalDiscount = formData.items.reduce((sum, item) => {
      return sum + (parseFloat(String(item.discount)) || 0)
    }, 0)

    const grandTotal = subtotal - totalDiscount

    return { subtotal, totalDiscount, grandTotal }
  }

  const totals = calculateTotals()

  const getFilteredProducts = (itemId: string) => {
    const searchTerm = searchProduct[itemId] || ''
    return products.filter(product =>
      product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      product.code.toLowerCase().includes(searchTerm.toLowerCase())
    )
  }

  if (!open) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b sticky top-0 bg-white z-10">
          <h2 className="text-2xl font-bold flex items-center gap-2">
            <RotateCcw className="h-6 w-6 text-red-600" />
            إنشاء إذن إرجاع جديد
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X className="h-6 w-6" />
          </button>
        </div>

        {/* Form */}
        <div className="p-6 space-y-6">
          {/* Basic Info - Row 1 */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {/* Voucher Number */}
            <div>
              <label className="block text-sm font-medium mb-2">
                رقم الإذن <span className="text-red-600">*</span>
              </label>
              <Input
                type="text"
                name="voucher_number"
                placeholder="RV-000001"
                value={formData.voucher_number || ''}
                onChange={handleChange}
                required
              />
            </div>

            {/* Issue Voucher */}
            <div>
              <label className="block text-sm font-medium mb-2">
                إذن الصرف المرجع <span className="text-red-600">*</span>
              </label>
              <Input
                type="number"
                name="issue_voucher_id"
                placeholder="رقم إذن الصرف"
                value={formData.issue_voucher_id || ''}
                onChange={handleChange}
                required
              />
            </div>

            {/* Customer */}
            {!customerId ? (
              <CustomerSearchSelect
                value={formData.customer_id}
                onChange={(customerId) => {
                  setFormData({ 
                    ...formData, 
                    customer_id: customerId,
                    customer_name: customerId ? '' : formData.customer_name 
                  })
                }}
                label="العميل"
                placeholder="ابحث عن العميل بالاسم أو الكود أو رقم الهاتف..."
                required={!formData.customer_name}
                error={!formData.customer_id && !formData.customer_name && loading ? 'الرجاء اختيار العميل أو إدخال اسمه' : ''}
              />
            ) : (
              <div>
                <label className="block text-sm font-medium mb-2">
                  العميل <span className="text-red-600">*</span>
                </label>
                <p className="text-sm py-2">
                  {customers.find(c => c.id === customerId)?.name || 'جاري التحميل...'}
                </p>
              </div>
            )}

            {/* Alternative: Customer Name Input */}
            {!customerId && !formData.customer_id && (
              <div>
                <label className="block text-sm font-medium mb-2">
                  أو أدخل اسم عميل جديد
                </label>
                <Input
                  type="text"
                  name="customer_name"
                  placeholder="اسم العميل"
                  value={formData.customer_name || ''}
                  onChange={handleChange}
                />
              </div>
            )}
          </div>

          {/* Basic Info - Row 2 */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* Branch */}
            <div>
              <label className="block text-sm font-medium mb-2">
                الفرع <span className="text-red-600">*</span>
              </label>
              <select
                name="branch_id"
                value={formData.branch_id || ''}
                onChange={handleChange}
                className="w-full px-4 py-2 border rounded-md"
                required
              >
                <option value="">اختر الفرع</option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name} ({branch.code})
                  </option>
                ))}
              </select>
            </div>

            {/* Return Date */}
            <div>
              <label className="block text-sm font-medium mb-2">
                تاريخ الإرجاع <span className="text-red-600">*</span>
              </label>
              <Input
                type="date"
                name="return_date"
                value={formData.return_date}
                onChange={handleChange}
                required
              />
            </div>
          </div>

          {/* Reason */}
          <div>
            <label className="block text-sm font-medium mb-2">
              سبب الإرجاع <span className="text-red-600">*</span>
            </label>
            <textarea
              name="reason"
              value={formData.reason}
              onChange={handleChange}
              placeholder="اذكر سبب الإرجاع..."
              rows={2}
              maxLength={500}
              className="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
            <p className="text-xs text-gray-500 mt-1">
              {formData.reason.length}/500 حرف
            </p>
          </div>

          {/* Reason Category */}
          <div>
            <label className="block text-sm font-medium mb-2">
              تصنيف السبب
            </label>
            <select
              name="reason_category"
              value={formData.reason_category || ''}
              onChange={handleChange}
              className="w-full px-4 py-2 border rounded-md"
            >
              <option value="">اختر التصنيف (اختياري)</option>
              <option value="damaged">تالف</option>
              <option value="defective">معيب</option>
              <option value="customer_request">طلب العميل</option>
              <option value="wrong_item">منتج خاطئ</option>
              <option value="other">أخرى</option>
            </select>
          </div>

          {/* Items Table */}
          <div className="border rounded-lg overflow-hidden">
            <div className="bg-gray-50 px-4 py-3 border-b">
              <h3 className="font-bold text-lg">الأصناف المرتجعة</h3>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-100">
                  <tr>
                    <th className="px-3 py-2 text-right text-sm font-semibold">#</th>
                    <th className="px-3 py-2 text-right text-sm font-semibold">الصنف *</th>
                    <th className="px-3 py-2 text-right text-sm font-semibold">الكمية *</th>
                    <th className="px-3 py-2 text-right text-sm font-semibold">سعر الوحدة *</th>
                    <th className="px-3 py-2 text-right text-sm font-semibold">الخصم</th>
                    <th className="px-3 py-2 text-right text-sm font-semibold">الإجمالي</th>
                    <th className="px-3 py-2 text-center text-sm font-semibold">إجراء</th>
                  </tr>
                </thead>
                <tbody>
                  {formData.items.map((item, index) => (
                    <tr key={item.id} className="border-b hover:bg-gray-50">
                      <td className="px-3 py-2 text-sm">{index + 1}</td>
                      <td className="px-3 py-2">
                        <Input
                          type="text"
                          placeholder="بحث..."
                          value={searchProduct[item.id] || ''}
                          onChange={(e) =>
                            setSearchProduct((prev) => ({
                              ...prev,
                              [item.id]: e.target.value,
                            }))
                          }
                          className="mb-1 text-sm"
                        />
                        <select
                          value={item.product_id || ''}
                          onChange={(e) =>
                            updateItem(item.id, 'product_id', parseInt(e.target.value))
                          }
                          className="w-full px-2 py-1 border rounded text-sm"
                          required
                        >
                          <option value="">اختر الصنف</option>
                          {getFilteredProducts(item.id).map((product) => (
                            <option key={product.id} value={product.id}>
                              {product.name} ({product.code})
                            </option>
                          ))}
                        </select>
                      </td>
                      <td className="px-3 py-2">
                        <Input
                          type="number"
                          step="0.01"
                          min="0.01"
                          value={item.quantity}
                          onChange={(e) =>
                            updateItem(item.id, 'quantity', parseFloat(e.target.value) || 0)
                          }
                          className="w-24 text-sm"
                          required
                        />
                      </td>
                      <td className="px-3 py-2">
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          value={item.unit_price}
                          onChange={(e) =>
                            updateItem(item.id, 'unit_price', parseFloat(e.target.value) || 0)
                          }
                          className="w-24 text-sm"
                          required
                        />
                      </td>
                      <td className="px-3 py-2">
                        <Input
                          type="number"
                          step="0.01"
                          min="0"
                          value={item.discount}
                          onChange={(e) =>
                            updateItem(item.id, 'discount', parseFloat(e.target.value) || 0)
                          }
                          className="w-24 text-sm"
                        />
                      </td>
                      <td className="px-3 py-2 font-semibold text-sm">
                        {item.total.toFixed(2)} ر.س
                      </td>
                      <td className="px-3 py-2 text-center">
                        <button
                          type="button"
                          onClick={() => removeItem(item.id)}
                          className="text-red-600 hover:text-red-800 disabled:opacity-50"
                          disabled={formData.items.length === 1}
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="p-3 border-t bg-gray-50">
              <button
                type="button"
                onClick={addItem}
                className="flex items-center gap-2 text-blue-600 hover:text-blue-800 font-medium"
              >
                <Plus className="h-4 w-4" />
                إضافة صنف
              </button>
            </div>
          </div>

          {/* Totals */}
          <div className="bg-gray-50 p-4 rounded-lg space-y-2">
            <div className="flex justify-between text-sm">
              <span>الإجمالي الفرعي:</span>
              <span className="font-semibold">{totals.subtotal.toFixed(2)} ر.س</span>
            </div>
            <div className="flex justify-between text-sm">
              <span>إجمالي الخصم:</span>
              <span className="font-semibold text-red-600">
                -{totals.totalDiscount.toFixed(2)} ر.س
              </span>
            </div>
            <div className="flex justify-between text-lg font-bold border-t pt-2">
              <span>الإجمالي النهائي:</span>
              <span className="text-green-600">{totals.grandTotal.toFixed(2)} ر.س</span>
            </div>
          </div>

          {/* Notes */}
          <div>
            <label className="block text-sm font-medium mb-2">ملاحظات</label>
            <textarea
              name="notes"
              value={formData.notes || ''}
              onChange={handleChange}
              placeholder="ملاحظات إضافية..."
              rows={3}
              className="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          {/* Actions */}
          <div className="flex gap-3 pt-4 border-t">
            <Button
              onClick={() => handleSubmit(false)}
              disabled={loading}
              className="flex-1"
            >
              {loading && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
              {loading ? 'جاري الحفظ...' : 'حفظ واعتماد'}
            </Button>
            <Button
              onClick={() => handleSubmit(true)}
              disabled={loading}
              variant="outline"
              className="flex-1"
            >
              {loading && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
              {loading ? 'جاري الحفظ...' : 'حفظ كمسودة'}
            </Button>
            <Button
              type="button"
              variant="outline"
              onClick={onClose}
              disabled={loading}
            >
              إلغاء
            </Button>
          </div>
        </div>
      </div>
    </div>
  )
}
