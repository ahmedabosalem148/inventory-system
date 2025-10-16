/**
 * Invoice Filters Dialog
 * Advanced filtering for sales invoices
 */

import { useState } from 'react'
import { X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type { InvoicesListParams } from '@/types'

interface InvoiceFiltersDialogProps {
  filters: InvoicesListParams
  onApply: (filters: InvoicesListParams) => void
  onClose: () => void
}

export const InvoiceFiltersDialog = ({
  filters,
  onApply,
  onClose,
}: InvoiceFiltersDialogProps) => {
  const [localFilters, setLocalFilters] = useState<InvoicesListParams>(filters)

  const handleApply = () => {
    onApply(localFilters)
  }

  const handleReset = () => {
    setLocalFilters({})
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div className="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        {/* Header */}
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-xl font-bold">تصفية الفواتير</h2>
          <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Filters */}
        <div className="space-y-4">
          {/* Status */}
          <div>
            <Label>الحالة</Label>
            <Select
              value={localFilters.status || ''}
              onValueChange={(value) =>
                setLocalFilters({ ...localFilters, status: value as any })
              }
            >
              <SelectTrigger>
                <SelectValue placeholder="اختر الحالة" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">الكل</SelectItem>
                <SelectItem value="DRAFT">مسودة</SelectItem>
                <SelectItem value="PENDING">معلقة</SelectItem>
                <SelectItem value="PAID">مدفوعة</SelectItem>
                <SelectItem value="PARTIALLY_PAID">دفع جزئي</SelectItem>
                <SelectItem value="CANCELLED">ملغاة</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {/* Payment Status */}
          <div>
            <Label>حالة الدفع</Label>
            <Select
              value={localFilters.payment_status || ''}
              onValueChange={(value) =>
                setLocalFilters({ ...localFilters, payment_status: value as any })
              }
            >
              <SelectTrigger>
                <SelectValue placeholder="اختر حالة الدفع" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">الكل</SelectItem>
                <SelectItem value="UNPAID">غير مدفوعة</SelectItem>
                <SelectItem value="PARTIALLY_PAID">دفع جزئي</SelectItem>
                <SelectItem value="PAID">مدفوعة بالكامل</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {/* Date From */}
          <div>
            <Label>من تاريخ</Label>
            <Input
              type="date"
              value={localFilters.date_from || ''}
              onChange={(e) =>
                setLocalFilters({ ...localFilters, date_from: e.target.value })
              }
            />
          </div>

          {/* Date To */}
          <div>
            <Label>إلى تاريخ</Label>
            <Input
              type="date"
              value={localFilters.date_to || ''}
              onChange={(e) =>
                setLocalFilters({ ...localFilters, date_to: e.target.value })
              }
            />
          </div>
        </div>

        {/* Actions */}
        <div className="flex gap-3 mt-6">
          <Button onClick={handleReset} variant="outline" className="flex-1">
            إعادة تعيين
          </Button>
          <Button onClick={handleApply} className="flex-1">
            تطبيق
          </Button>
        </div>
      </div>
    </div>
  )
}
