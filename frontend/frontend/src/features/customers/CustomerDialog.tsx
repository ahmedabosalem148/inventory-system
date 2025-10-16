import { useState, useEffect } from 'react'
import { X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { showToast } from '@/components/ui/toast'
import { apiClient } from '@/services/api/client'

interface Customer {
  id?: number
  name: string
  phone?: string
  address?: string
  credit_limit?: number
  notes?: string
}

interface CustomerDialogProps {
  open: boolean
  onClose: () => void
  customer?: Customer | null
  onSuccess: () => void
}

export default function CustomerDialog({
  open,
  onClose,
  customer,
  onSuccess,
}: CustomerDialogProps) {
  const [formData, setFormData] = useState<Customer>({
    name: '',
    phone: '',
    address: '',
    credit_limit: 0,
    notes: '',
  })
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    if (customer) {
      setFormData(customer)
    } else {
      setFormData({
        name: '',
        phone: '',
        address: '',
        credit_limit: 0,
        notes: '',
      })
    }
  }, [customer, open])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!formData.name.trim()) {
      showToast.error('الرجاء إدخال اسم العميل')
      return
    }

    try {
      setLoading(true)

      if (customer?.id) {
        // Update existing customer
        await apiClient.put(`/customers/${customer.id}`, formData)
        showToast.success('تم تحديث بيانات العميل بنجاح')
      } else {
        // Create new customer
        await apiClient.post('/customers', formData)
        showToast.success('تم إضافة العميل بنجاح')
      }

      onSuccess()
      onClose()
    } catch (error: any) {
      const message = error.response?.data?.message || 'حدث خطأ أثناء حفظ البيانات'
      showToast.error(message)
    } finally {
      setLoading(false)
    }
  }

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>
  ) => {
    const { name, value } = e.target
    setFormData((prev) => ({
      ...prev,
      [name]:
        name === 'credit_limit' ? parseFloat(value) || 0 : value,
    }))
  }

  if (!open) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b sticky top-0 bg-white">
          <h2 className="text-2xl font-bold">
            {customer ? 'تعديل بيانات عميل' : 'إضافة عميل جديد'}
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
          {/* Name (Required) */}
          <div>
            <label className="block text-sm font-medium mb-2">
              اسم العميل <span className="text-red-600">*</span>
            </label>
            <Input
              name="name"
              value={formData.name}
              onChange={handleChange}
              placeholder="أدخل اسم العميل"
              required
              autoFocus
            />
          </div>

          {/* Phone */}
          <div>
            <label className="block text-sm font-medium mb-2">
              رقم الهاتف
            </label>
            <Input
              name="phone"
              value={formData.phone}
              onChange={handleChange}
              placeholder="05xxxxxxxx"
              type="tel"
            />
          </div>

          {/* Address */}
          <div>
            <label className="block text-sm font-medium mb-2">
              العنوان
            </label>
            <textarea
              name="address"
              value={formData.address}
              onChange={handleChange}
              placeholder="أدخل عنوان العميل"
              rows={3}
              className="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          {/* Credit Limit */}
          <div>
            <label className="block text-sm font-medium mb-2">
              حد الائتمان (ر.س)
            </label>
            <Input
              name="credit_limit"
              type="number"
              step="0.01"
              value={formData.credit_limit}
              onChange={handleChange}
              placeholder="0.00"
            />
            <p className="text-xs text-gray-500 mt-1">
              الحد الأقصى للمبلغ الذي يمكن للعميل شراؤه بالآجل
            </p>
          </div>

          {/* Notes */}
          <div>
            <label className="block text-sm font-medium mb-2">
              ملاحظات
            </label>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              placeholder="أدخل أي ملاحظات إضافية"
              rows={3}
              className="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          {/* Actions */}
          <div className="flex gap-3 pt-4 border-t">
            <Button
              type="submit"
              disabled={loading}
              className="flex-1"
            >
              {loading ? 'جاري الحفظ...' : customer ? 'تحديث' : 'إضافة'}
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
