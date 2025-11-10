import { useState, useEffect } from 'react'
import { FileText, ArrowLeft, Printer, Check } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { showToast } from '@/components/ui/toast'
import { apiClient } from '@/services/api/client'

interface Product {
  id: number
  name: string
  code: string
}

interface IssueVoucherItem {
  id: number
  product: Product
  quantity: number
  unit_price: number
  discount_type: 'percentage' | 'fixed' | null
  discount_value: number
  discount_amount: number
  subtotal: number
  net_total: number
}

interface Customer {
  id: number
  name: string
  code: string
  phone?: string
}

interface Branch {
  id: number
  name: string
  code: string
}

interface User {
  id: number
  name: string
}

interface IssueVoucher {
  id: number
  voucher_number: string
  issue_date: string
  customer?: Customer
  customer_name?: string
  branch: Branch
  target_branch?: Branch
  voucher_type: 'sale' | 'transfer'
  is_transfer: boolean
  items: IssueVoucherItem[]
  total_amount: number
  discount_type: 'percentage' | 'fixed' | null
  discount_value: number
  discount_amount: number
  subtotal: number
  net_total: number
  status: 'draft' | 'approved'
  notes?: string
  created_by: number
  creator?: User
  approved_by?: number
  approver?: User
  approved_at?: string
  created_at: string
}

export default function IssueVoucherDetailsPage() {
  const [voucher, setVoucher] = useState<IssueVoucher | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const id = getId()
    if (id) {
      loadVoucher(id)
    } else {
      showToast.error('معرف الإذن غير صالح')
      window.location.hash = 'invoices'
    }
  }, [])

  const getId = (): number | null => {
    const hash = window.location.hash.slice(1)
    const parts = hash.split('/')
    if (parts.length === 2 && parts[0] === 'invoices') {
      const id = parseInt(parts[1])
      return isNaN(id) ? null : id
    }
    return null
  }

  const loadVoucher = async (id: number) => {
    try {
      setLoading(true)
      const response = await apiClient.get(`/issue-vouchers/${id}`)
      setVoucher(response.data.data)
    } catch (error: any) {
      const message = error.response?.data?.message || 'فشل تحميل بيانات الإذن'
      showToast.error(message)
      window.location.hash = 'invoices'
    } finally {
      setLoading(false)
    }
  }

  const handlePrint = async () => {
    const id = getId()
    if (!id) return

    try {
      const response = await apiClient.get(`/issue-vouchers/${id}/print`, {
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

    if (!confirm('هل أنت متأكد من اعتماد هذا الإذن؟ سيتم خصم الكميات من المخزون وتسجيل القيد في حساب العميل.')) {
      return
    }

    try {
      await apiClient.post(`/issue-vouchers/${id}/approve`)
      showToast.success('تم اعتماد الإذن بنجاح')
      loadVoucher(id)
    } catch (error: any) {
      const message = error.response?.data?.message || 'فشل اعتماد الإذن'
      showToast.error(message)
    }
  }

  const getStatusBadge = (status: string) => {
    if (status === 'draft') {
      return <Badge variant="warning">مسودة</Badge>
    }
    return <Badge variant="success">معتمد</Badge>
  }

  const getVoucherTypeBadge = (isTransfer: boolean) => {
    if (isTransfer) {
      return <Badge variant="info">تحويل بين فروع</Badge>
    }
    return <Badge variant="default">بيع</Badge>
  }

  const getDiscountText = (type: string | null, value: number) => {
    if (!type || value === 0) return '-'
    if (type === 'percentage') return `${value}%`
    return `${value.toFixed(2)} ر.س`
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">جاري التحميل...</p>
        </div>
      </div>
    )
  }

  if (!voucher) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">لم يتم العثور على الإذن</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-start">
        <div>
          <div className="flex items-center gap-3 mb-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => window.location.hash = 'invoices'}
            >
              <ArrowLeft className="h-4 w-4 ml-2" />
              رجوع
            </Button>
            <h1 className="text-3xl font-bold flex items-center gap-2">
              <FileText className="h-8 w-8 text-blue-600" />
              تفاصيل إذن الصرف
            </h1>
          </div>
          <p className="text-gray-500">رقم الإذن: {voucher.voucher_number}</p>
        </div>
        <div className="flex gap-2">
          <Button onClick={handlePrint} variant="outline">
            <Printer className="h-4 w-4 ml-2" />
            طباعة
          </Button>
          {voucher.status === 'draft' && (
            <Button onClick={handleApprove}>
              <Check className="h-4 w-4 ml-2" />
              اعتماد الإذن
            </Button>
          )}
        </div>
      </div>

      {/* Voucher Info Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {/* Voucher Card */}
        <Card className="p-6">
          <h3 className="font-bold text-lg mb-4 flex items-center gap-2">
            <FileText className="h-5 w-5 text-blue-600" />
            معلومات الإذن
          </h3>
          <div className="space-y-3">
            <div>
              <p className="text-sm text-gray-500">رقم الإذن</p>
              <p className="font-mono font-bold text-blue-600">{voucher.voucher_number}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">التاريخ</p>
              <p className="font-semibold">
                {new Date(voucher.issue_date).toLocaleDateString('ar-EG')}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-500">النوع</p>
              <div className="mt-1">
                {getVoucherTypeBadge(voucher.is_transfer)}
              </div>
            </div>
            <div>
              <p className="text-sm text-gray-500">الحالة</p>
              <div className="mt-1">{getStatusBadge(voucher.status)}</div>
            </div>
            {voucher.status === 'approved' && voucher.approved_at && (
              <div>
                <p className="text-sm text-gray-500">تاريخ الاعتماد</p>
                <p className="text-sm">
                  {new Date(voucher.approved_at).toLocaleDateString('ar-EG')}
                </p>
                {voucher.approver && (
                  <p className="text-xs text-gray-600">بواسطة: {voucher.approver.name}</p>
                )}
              </div>
            )}
          </div>
        </Card>

        {/* Customer/Target Branch Card */}
        <Card className="p-6">
          <h3 className="font-bold text-lg mb-4">
            {voucher.is_transfer ? 'الفرع المستهدف' : 'العميل'}
          </h3>
          <div className="space-y-3">
            {voucher.is_transfer && voucher.target_branch ? (
              <>
                <div>
                  <p className="text-sm text-gray-500">اسم الفرع</p>
                  <p className="font-semibold">{voucher.target_branch.name}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-500">كود الفرع</p>
                  <p className="font-mono">{voucher.target_branch.code}</p>
                </div>
              </>
            ) : (
              <>
                {voucher.customer ? (
                  <>
                    <div>
                      <p className="text-sm text-gray-500">اسم العميل</p>
                      <p className="font-semibold">{voucher.customer.name}</p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">كود العميل</p>
                      <p className="font-mono">{voucher.customer.code}</p>
                    </div>
                    {voucher.customer.phone && (
                      <div>
                        <p className="text-sm text-gray-500">الهاتف</p>
                        <p className="font-mono">{voucher.customer.phone}</p>
                      </div>
                    )}
                  </>
                ) : (
                  <div>
                    <p className="text-sm text-gray-500">اسم العميل</p>
                    <p className="font-semibold">{voucher.customer_name || '-'}</p>
                  </div>
                )}
              </>
            )}
          </div>
        </Card>

        {/* Branch Card */}
        <Card className="p-6">
          <h3 className="font-bold text-lg mb-4">الفرع المصدر</h3>
          <div className="space-y-3">
            <div>
              <p className="text-sm text-gray-500">اسم الفرع</p>
              <p className="font-semibold">{voucher.branch.name}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">كود الفرع</p>
              <p className="font-mono">{voucher.branch.code}</p>
            </div>
            {voucher.creator && (
              <div>
                <p className="text-sm text-gray-500">أنشأ بواسطة</p>
                <p className="text-sm">{voucher.creator.name}</p>
                <p className="text-xs text-gray-600">
                  {new Date(voucher.created_at).toLocaleDateString('ar-EG')}
                </p>
              </div>
            )}
          </div>
        </Card>
      </div>

      {/* Items Table */}
      <Card className="p-6">
        <h3 className="font-bold text-lg mb-4">الأصناف</h3>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-right text-sm font-semibold">#</th>
                <th className="px-4 py-3 text-right text-sm font-semibold">الصنف</th>
                <th className="px-4 py-3 text-right text-sm font-semibold">الكمية</th>
                <th className="px-4 py-3 text-right text-sm font-semibold">سعر الوحدة</th>
                <th className="px-4 py-3 text-right text-sm font-semibold">الإجمالي الفرعي</th>
                <th className="px-4 py-3 text-right text-sm font-semibold">خصم البند</th>
                <th className="px-4 py-3 text-right text-sm font-semibold">الصافي</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {voucher.items.map((item, index) => (
                <tr key={item.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-sm">{index + 1}</td>
                  <td className="px-4 py-3">
                    <p className="font-semibold">{item.product.name}</p>
                    <p className="text-xs text-gray-500 font-mono">{item.product.code}</p>
                  </td>
                  <td className="px-4 py-3 font-mono">{item.quantity}</td>
                  <td className="px-4 py-3 font-mono">{Number(item.unit_price).toFixed(2)} ر.س</td>
                  <td className="px-4 py-3 font-mono font-semibold">
                    {Number(item.subtotal).toFixed(2)} ر.س
                  </td>
                  <td className="px-4 py-3">
                    <div className="text-sm">
                      <p className="font-mono text-red-600">
                        {getDiscountText(item.discount_type, item.discount_value)}
                      </p>
                      {item.discount_amount > 0 && (
                        <p className="text-xs text-red-600">
                          (-{Number(item.discount_amount).toFixed(2)} ر.س)
                        </p>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3 font-mono font-bold text-green-600">
                    {Number(item.net_total).toFixed(2)} ر.س
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>

      {/* Totals Section */}
      <Card className="p-6">
        <h3 className="font-bold text-lg mb-4">الإجماليات</h3>
        <div className="space-y-3 max-w-md mr-auto">
          <div className="flex justify-between text-lg">
            <span>الإجمالي الفرعي:</span>
            <span className="font-mono font-semibold">{Number(voucher.subtotal).toFixed(2)} ر.س</span>
          </div>
          
          {voucher.discount_amount > 0 && (
            <>
              <div className="flex justify-between text-sm text-gray-600">
                <span>خصم الفاتورة:</span>
                <span className="font-mono">
                  {getDiscountText(voucher.discount_type, voucher.discount_value)}
                </span>
              </div>
              <div className="flex justify-between text-lg text-red-600">
                <span>قيمة الخصم:</span>
                <span className="font-mono font-semibold">
                  -{Number(voucher.discount_amount).toFixed(2)} ر.س
                </span>
              </div>
            </>
          )}
          
          <div className="border-t-2 pt-3">
            <div className="flex justify-between text-2xl font-bold">
              <span>الصافي النهائي:</span>
              <span className="font-mono text-green-600">
                {Number(voucher.net_total).toFixed(2)} ر.س
              </span>
            </div>
          </div>
        </div>
      </Card>

      {/* Notes Section */}
      {voucher.notes && (
        <Card className="p-6">
          <h3 className="font-bold text-lg mb-3">ملاحظات</h3>
          <p className="text-gray-700 whitespace-pre-wrap">{voucher.notes}</p>
        </Card>
      )}
    </div>
  )
}
