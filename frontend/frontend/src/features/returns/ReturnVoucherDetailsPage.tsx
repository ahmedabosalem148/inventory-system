import { useState, useEffect } from 'react'
import { 
  ArrowLeft, 
  FileText, 
  Printer, 
  CheckCircle, 
  XCircle,
  User,
  Building2,
  Package
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { showToast } from '@/components/ui/toast'
import { Spinner } from '@/components/ui/spinner'
import { apiClient } from '@/services/api/client'

interface ReturnVoucherItem {
  id: number
  product: {
    id: number
    name: string
    code: string
    sku: string
  }
  quantity: number
  unit_price: number
  discount_type: string | null
  discount_value: number
  total: number
}

interface ReturnVoucherDetails {
  id: number
  voucher_number: string
  return_date: string
  customer?: {
    id: number
    name: string
    code: string
    phone?: string
  }
  customer_name?: string
  branch: {
    id: number
    name: string
  }
  subtotal: number
  discount_amount: number
  net_total: number
  total_amount: number
  status: 'draft' | 'approved'
  reason?: string
  notes?: string
  items: ReturnVoucherItem[]
  approved_at?: string
  approved_by?: string
  creator?: {
    name: string
  }
  created_at: string
}

export default function ReturnVoucherDetailsPage() {
  const [voucher, setVoucher] = useState<ReturnVoucherDetails | null>(null)
  const [loading, setLoading] = useState(true)
  const [approving, setApproving] = useState(false)

  // Extract ID from hash (e.g., #return-vouchers/123)
  const getId = () => {
    const hash = window.location.hash.slice(1) // Remove #
    const parts = hash.split('/')
    return parts[1] || null
  }

  useEffect(() => {
    const id = getId()
    if (id) {
      loadVoucher(id)
    }
  }, [])

  const loadVoucher = async (id: string) => {
    try {
      setLoading(true)
      const response = await apiClient.get<{ data: ReturnVoucherDetails }>(
        `/return-vouchers/${id}`
      )
      setVoucher(response.data.data || response.data)
    } catch (error) {
      showToast.error('فشل تحميل تفاصيل الإذن')
      window.location.hash = 'return-vouchers'
    } finally {
      setLoading(false)
    }
  }

  const handlePrint = async () => {
    const id = getId()
    if (!id) return

    try {
      const response = await apiClient.get(`/return-vouchers/${id}/print`, {
        responseType: 'blob'
      })
      
      const blob = new Blob([response.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      window.open(url, '_blank')
      
      showToast.success('تم فتح ملف PDF')
    } catch (error) {
      showToast.error('فشل طباعة الإذن')
    }
  }

  const handleApprove = async () => {
    const id = getId()
    if (!id) return

    if (!window.confirm('هل أنت متأكد من اعتماد هذا الإذن؟\nسيتم تحديث المخزون ودفتر العميل تلقائياً.')) {
      return
    }

    try {
      setApproving(true)
      await apiClient.post(`/return-vouchers/${id}/approve`)
      showToast.success('تم اعتماد الإذن بنجاح')
      loadVoucher(id)
    } catch (error: any) {
      const message = error.response?.data?.message || 'فشل اعتماد الإذن'
      showToast.error(message)
    } finally {
      setApproving(false)
    }
  }

  const getStatusBadge = (status: string) => {
    const badges = {
      draft: { label: 'مسودة', variant: 'warning' as const, icon: FileText },
      approved: { label: 'معتمد', variant: 'success' as const, icon: CheckCircle },
    }
    const badge = badges[status as keyof typeof badges] || { label: status, variant: 'default' as const, icon: FileText }
    const Icon = badge.icon
    return (
      <Badge variant={badge.variant} className="flex items-center gap-1">
        <Icon className="h-3 w-3" />
        {badge.label}
      </Badge>
    )
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Spinner size="lg" label="جاري التحميل..." />
      </div>
    )
  }

  if (!voucher) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <p className="text-gray-500">لم يتم العثور على الإذن</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-start">
        <div className="flex items-center gap-4">
          <Button
            variant="outline"
            size="sm"
            onClick={() => window.history.back()}
          >
            <ArrowLeft className="h-4 w-4 ml-2" />
            رجوع
          </Button>
          <div>
            <h1 className="text-3xl font-bold flex items-center gap-2">
              إذن إرجاع {voucher.voucher_number}
            </h1>
            <p className="text-gray-500 mt-1">تفاصيل إذن الإرجاع</p>
          </div>
        </div>
        
        <div className="flex gap-2">
          {voucher.status === 'draft' && (
            <Button
              onClick={handleApprove}
              disabled={approving}
              className="bg-green-600 hover:bg-green-700"
            >
              <CheckCircle className="h-4 w-4 ml-2" />
              {approving ? 'جاري الاعتماد...' : 'اعتماد الإذن'}
            </Button>
          )}
          <Button variant="outline" onClick={handlePrint}>
            <Printer className="h-4 w-4 ml-2" />
            طباعة
          </Button>
        </div>
      </div>

      {/* Main Info */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-6">
          <div className="flex items-center gap-3 mb-4">
            <div className="h-10 w-10 bg-orange-100 rounded-full flex items-center justify-center">
              <FileText className="h-5 w-5 text-orange-600" />
            </div>
            <div>
              <p className="text-sm text-gray-500">معلومات الإذن</p>
              <p className="font-bold text-lg">{voucher.voucher_number}</p>
            </div>
          </div>
          <div className="space-y-2 text-sm">
            <div className="flex justify-between">
              <span className="text-gray-500">التاريخ:</span>
              <span className="font-medium">
                {new Date(voucher.return_date).toLocaleDateString('ar-EG')}
              </span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-500">الحالة:</span>
              {getStatusBadge(voucher.status)}
            </div>
            {voucher.approved_at && (
              <div className="flex justify-between">
                <span className="text-gray-500">تاريخ الاعتماد:</span>
                <span className="font-medium text-xs">
                  {new Date(voucher.approved_at).toLocaleString('ar-EG')}
                </span>
              </div>
            )}
          </div>
        </Card>

        <Card className="p-6">
          <div className="flex items-center gap-3 mb-4">
            <div className="h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
              <User className="h-5 w-5 text-blue-600" />
            </div>
            <div>
              <p className="text-sm text-gray-500">معلومات العميل</p>
              <p className="font-bold text-lg">
                {voucher.customer?.name || voucher.customer_name || '-'}
              </p>
            </div>
          </div>
          <div className="space-y-2 text-sm">
            {voucher.customer && (
              <>
                <div className="flex justify-between">
                  <span className="text-gray-500">الكود:</span>
                  <span className="font-medium">{voucher.customer.code}</span>
                </div>
                {voucher.customer.phone && (
                  <div className="flex justify-between">
                    <span className="text-gray-500">الهاتف:</span>
                    <span className="font-medium">{voucher.customer.phone}</span>
                  </div>
                )}
              </>
            )}
          </div>
        </Card>

        <Card className="p-6">
          <div className="flex items-center gap-3 mb-4">
            <div className="h-10 w-10 bg-purple-100 rounded-full flex items-center justify-center">
              <Building2 className="h-5 w-5 text-purple-600" />
            </div>
            <div>
              <p className="text-sm text-gray-500">الفرع</p>
              <p className="font-bold text-lg">{voucher.branch.name}</p>
            </div>
          </div>
          <div className="space-y-2 text-sm">
            <div className="flex justify-between">
              <span className="text-gray-500">المسجل:</span>
              <span className="font-medium">{voucher.creator?.name || '-'}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-500">تاريخ الإنشاء:</span>
              <span className="font-medium text-xs">
                {new Date(voucher.created_at).toLocaleString('ar-EG')}
              </span>
            </div>
          </div>
        </Card>
      </div>

      {/* Reason (if exists) */}
      {voucher.reason && (
        <Card className="p-6 bg-yellow-50 border-yellow-200">
          <h3 className="font-bold text-lg mb-2 flex items-center gap-2">
            <XCircle className="h-5 w-5 text-yellow-600" />
            سبب الإرجاع
          </h3>
          <p className="text-gray-700">{voucher.reason}</p>
        </Card>
      )}

      {/* Items Table */}
      <Card>
        <div className="p-4 border-b">
          <h3 className="text-lg font-bold flex items-center gap-2">
            <Package className="h-5 w-5" />
            بنود الإذن ({voucher.items.length})
          </h3>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b">
              <tr>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">#</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المنتج</th>
                <th className="px-4 py-3 text-center text-xs font-medium text-gray-500">الكمية</th>
                <th className="px-4 py-3 text-center text-xs font-medium text-gray-500">السعر</th>
                {voucher.items.some(item => item.discount_value > 0) && (
                  <th className="px-4 py-3 text-center text-xs font-medium text-gray-500">الخصم</th>
                )}
                <th className="px-4 py-3 text-center text-xs font-medium text-gray-500">الإجمالي</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {voucher.items.map((item, index) => (
                <tr key={item.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-sm">{index + 1}</td>
                  <td className="px-4 py-3">
                    <div>
                      <p className="font-medium">{item.product.name}</p>
                      <p className="text-xs text-gray-500">
                        {item.product.sku || item.product.code}
                      </p>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-center font-medium">
                    {item.quantity}
                  </td>
                  <td className="px-4 py-3 text-center">
                    {item.unit_price.toFixed(2)} ر.س
                  </td>
                  {voucher.items.some(i => i.discount_value > 0) && (
                    <td className="px-4 py-3 text-center text-red-600">
                      {item.discount_value > 0 ? (
                        <span>
                          {item.discount_value.toFixed(2)} ر.س
                          {item.discount_type === 'percentage' && ' (%)'}
                        </span>
                      ) : '-'}
                    </td>
                  )}
                  <td className="px-4 py-3 text-center font-bold text-lg">
                    {item.total.toFixed(2)} ر.س
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Totals */}
        <div className="p-6 bg-gray-50 border-t">
          <div className="max-w-md mr-auto space-y-2">
            <div className="flex justify-between text-sm">
              <span className="text-gray-600">المجموع الفرعي:</span>
              <span className="font-medium">{voucher.subtotal.toFixed(2)} ر.س</span>
            </div>
            
            {voucher.discount_amount > 0 && (
              <div className="flex justify-between text-sm text-red-600">
                <span>الخصم:</span>
                <span className="font-medium">- {voucher.discount_amount.toFixed(2)} ر.س</span>
              </div>
            )}

            <div className="flex justify-between text-lg font-bold pt-2 border-t">
              <span>الإجمالي النهائي:</span>
              <span className="text-red-600">{voucher.total_amount.toFixed(2)} ر.س</span>
            </div>
          </div>
        </div>
      </Card>

      {/* Notes */}
      {voucher.notes && (
        <Card className="p-6">
          <h3 className="font-bold text-lg mb-2">ملاحظات</h3>
          <p className="text-gray-700">{voucher.notes}</p>
        </Card>
      )}
    </div>
  )
}
