/**
 * Purchase Order Filters Dialog
 * Advanced filtering for purchase orders
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
import type { PurchaseOrdersListParams } from '@/types'

interface PurchaseFiltersDialogProps {
  filters: PurchaseOrdersListParams
  onApply: (filters: PurchaseOrdersListParams) => void
  onClose: () => void
}

export const PurchaseFiltersDialog = ({
  filters,
  onApply,
  onClose,
}: PurchaseFiltersDialogProps) => {
  const [localFilters, setLocalFilters] = useState<PurchaseOrdersListParams>(filters)

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
          <h2 className="text-xl font-bold">تصفية أوامر الشراء</h2>
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
                <SelectItem value="PENDING">معلق</SelectItem>
                <SelectItem value="APPROVED">معتمد</SelectItem>
                <SelectItem value="RECEIVED">مستلم</SelectItem>
                <SelectItem value="PARTIALLY_RECEIVED">استلام جزئي</SelectItem>
                <SelectItem value="CANCELLED">ملغي</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {/* Receiving Status */}
          <div>
            <Label>حالة الاستلام</Label>
            <Select
              value={localFilters.receiving_status || ''}
              onValueChange={(value) =>
                setLocalFilters({ ...localFilters, receiving_status: value as any })
              }
            >
              <SelectTrigger>
                <SelectValue placeholder="اختر حالة الاستلام" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">الكل</SelectItem>
                <SelectItem value="NOT_RECEIVED">لم يستلم</SelectItem>
                <SelectItem value="PARTIALLY_RECEIVED">استلام جزئي</SelectItem>
                <SelectItem value="FULLY_RECEIVED">مستلم بالكامل</SelectItem>
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
