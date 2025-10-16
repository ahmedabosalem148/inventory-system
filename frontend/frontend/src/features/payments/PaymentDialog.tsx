import { useState, useEffect } from 'react'
import { X, DollarSign, CreditCard } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { showToast } from '@/components/ui/toast'
import { apiClient } from '@/services/api/client'

interface Customer {
  id: number
  name: string
  code: string
}

interface PaymentFormData {
  customer_id: number | null
  payment_date: string
  amount: number
  payment_method: 'cash' | 'cheque' | 'bank_transfer'
  notes: string
  // Cheque fields
  cheque_number: string
  cheque_date: string
  cheque_due_date: string
  bank_name: string
}

interface PaymentDialogProps {
  open: boolean
  onClose: () => void
  onSuccess: () => void
  customerId?: number
}

export default function PaymentDialog({
  open,
  onClose,
  onSuccess,
  customerId,
}: PaymentDialogProps) {
  const [formData, setFormData] = useState<PaymentFormData>({
    customer_id: customerId || null,
    payment_date: new Date().toISOString().split('T')[0],
    amount: 0,
    payment_method: 'cash',
    notes: '',
    cheque_number: '',
    cheque_date: new Date().toISOString().split('T')[0],
    cheque_due_date: '',
    bank_name: '',
  })
  const [loading, setLoading] = useState(false)
  const [customers, setCustomers] = useState<Customer[]>([])
  const [searchCustomer, setSearchCustomer] = useState('')

  useEffect(() => {
    if (open) {
      loadCustomers()
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

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!formData.customer_id) {
      showToast.error('الرجاء اختيار العميل')
      return
    }

    if (formData.amount <= 0) {
      showToast.error('الرجاء إدخال مبلغ صحيح')
      return
    }

    if (formData.payment_method === 'cheque') {
      if (!formData.cheque_number || !formData.cheque_due_date || !formData.bank_name) {
        showToast.error('الرجاء إدخال جميع بيانات الشيك')
        return
      }
    }

    try {
      setLoading(true)

      await apiClient.post('/payments', formData)

      showToast.success('تم تسجيل الدفعة بنجاح')
      onSuccess()
      onClose()
    } catch (error: any) {
      const message = error.response?.data?.message || 'حدث خطأ أثناء تسجيل الدفعة'
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
      [name]:
        name === 'amount' || name === 'customer_id'
          ? parseFloat(value) || 0
          : value,
    }))
  }

  const filteredCustomers = customers.filter(customer =>
    customer.name.toLowerCase().includes(searchCustomer.toLowerCase()) ||
    customer.code.toLowerCase().includes(searchCustomer.toLowerCase())
  )

  if (!open) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b sticky top-0 bg-white">
          <h2 className="text-2xl font-bold flex items-center gap-2">
            <DollarSign className="h-6 w-6 text-green-600" />
            تسجيل دفعة جديدة
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <X className="h-6 w-6" />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 space-y-6">
          {/* Customer Selection */}
          {!customerId && (
            <div>
              <label className="block text-sm font-medium mb-2">
                العميل <span className="text-red-600">*</span>
              </label>
              <Input
                type="text"
                placeholder="بحث عن العميل..."
                value={searchCustomer}
                onChange={(e) => setSearchCustomer(e.target.value)}
                className="mb-2"
              />
              <select
                name="customer_id"
                value={formData.customer_id || ''}
                onChange={handleChange}
                className="w-full px-4 py-2 border rounded-md"
                required
              >
                <option value="">اختر العميل</option>
                {filteredCustomers.map((customer) => (
                  <option key={customer.id} value={customer.id}>
                    {customer.name} ({customer.code})
                  </option>
                ))}
              </select>
            </div>
          )}

          {/* Payment Date */}
          <div>
            <label className="block text-sm font-medium mb-2">
              تاريخ الدفعة <span className="text-red-600">*</span>
            </label>
            <Input
              type="date"
              name="payment_date"
              value={formData.payment_date}
              onChange={handleChange}
              required
            />
          </div>

          {/* Amount */}
          <div>
            <label className="block text-sm font-medium mb-2">
              المبلغ (ر.س) <span className="text-red-600">*</span>
            </label>
            <Input
              type="number"
              name="amount"
              step="0.01"
              min="0.01"
              value={formData.amount}
              onChange={handleChange}
              placeholder="0.00"
              required
            />
          </div>

          {/* Payment Method */}
          <div>
            <label className="block text-sm font-medium mb-2">
              طريقة الدفع <span className="text-red-600">*</span>
            </label>
            <select
              name="payment_method"
              value={formData.payment_method}
              onChange={handleChange}
              className="w-full px-4 py-2 border rounded-md"
              required
            >
              <option value="cash">نقدي</option>
              <option value="cheque">شيك</option>
              <option value="bank_transfer">تحويل بنكي</option>
            </select>
          </div>

          {/* Cheque Details (shown only if payment_method is cheque) */}
          {formData.payment_method === 'cheque' && (
            <div className="border-t pt-6 space-y-4">
              <h3 className="text-lg font-bold flex items-center gap-2">
                <CreditCard className="h-5 w-5" />
                بيانات الشيك
              </h3>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-2">
                    رقم الشيك <span className="text-red-600">*</span>
                  </label>
                  <Input
                    type="text"
                    name="cheque_number"
                    value={formData.cheque_number}
                    onChange={handleChange}
                    placeholder="رقم الشيك"
                    required={formData.payment_method === 'cheque'}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-2">
                    اسم البنك <span className="text-red-600">*</span>
                  </label>
                  <Input
                    type="text"
                    name="bank_name"
                    value={formData.bank_name}
                    onChange={handleChange}
                    placeholder="اسم البنك"
                    required={formData.payment_method === 'cheque'}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-2">
                    تاريخ الشيك <span className="text-red-600">*</span>
                  </label>
                  <Input
                    type="date"
                    name="cheque_date"
                    value={formData.cheque_date}
                    onChange={handleChange}
                    required={formData.payment_method === 'cheque'}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-2">
                    تاريخ الاستحقاق <span className="text-red-600">*</span>
                  </label>
                  <Input
                    type="date"
                    name="cheque_due_date"
                    value={formData.cheque_due_date}
                    onChange={handleChange}
                    required={formData.payment_method === 'cheque'}
                  />
                </div>
              </div>
            </div>
          )}

          {/* Notes */}
          <div>
            <label className="block text-sm font-medium mb-2">ملاحظات</label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              placeholder="ملاحظات إضافية..."
              rows={3}
              className="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          {/* Actions */}
          <div className="flex gap-3 pt-4 border-t">
            <Button type="submit" disabled={loading} className="flex-1">
              {loading ? 'جاري الحفظ...' : 'تسجيل الدفعة'}
            </Button>
            <Button
              type="button"
              variant="outline"
              onClick={onClose}
              disabled={loading}
              className="flex-1"
            >
              إلغاء
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}
