/**
 * Invoice Dialog
 * Create/Edit/View sales invoice with line items
 */

import { useState, useEffect } from 'react'
import { X, Plus, Trash2, Save } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from 'react-hot-toast'
import type { SalesInvoice, CreateSalesInvoiceInput, Product } from '@/types'
import { createInvoice, updateInvoice, getInvoice } from '@/services/api/invoices'
import { getProducts } from '@/services/api/products'

interface InvoiceDialogProps {
  invoice?: SalesInvoice | null
  onClose: (saved: boolean) => void
}

interface InvoiceLineItem {
  id: string // temp id for UI
  product_id: number
  product?: Product
  quantity: number
  unit_price: number
  discount_type: 'percentage' | 'fixed'
  discount_percentage: number
  discount_fixed: number
  discount_amount: number
  tax_percentage: number
  tax_amount: number
  total: number
}

export const InvoiceDialog = ({ invoice, onClose }: InvoiceDialogProps) => {
  const [loading, setLoading] = useState(false)
  const [products, setProducts] = useState<Product[]>([])

  // Form data
  const [customerId, setCustomerId] = useState<number>(0)
  const [branchId, setBranchId] = useState<number>(1)
  const [issueType, setIssueType] = useState<'SALE' | 'TRANSFER'>('SALE')
  const [targetBranchId, setTargetBranchId] = useState<number>(0)
  const [paymentType, setPaymentType] = useState<'CASH' | 'CREDIT'>('CASH')
  const [invoiceDate, setInvoiceDate] = useState(
    new Date().toISOString().split('T')[0]
  )
  const [dueDate, setDueDate] = useState('')
  const [invoiceDiscountType, setInvoiceDiscountType] = useState<'percentage' | 'fixed'>('percentage')
  const [discountPercentage, setDiscountPercentage] = useState(0)
  const [discountFixed, setDiscountFixed] = useState(0)
  const [taxPercentage, setTaxPercentage] = useState(0)
  const [notes, setNotes] = useState('')
  const [items, setItems] = useState<InvoiceLineItem[]>([])

  // Calculations
  const [subtotal, setSubtotal] = useState(0)
  const [discountAmount, setDiscountAmount] = useState(0)
  const [taxAmount, setTaxAmount] = useState(0)
  const [totalAmount, setTotalAmount] = useState(0)

  /**
   * Load products for autocomplete
   */
  useEffect(() => {
    const loadProducts = async () => {
      try {
        const response = await getProducts({ per_page: 100 })
        setProducts(response.data)
      } catch (error) {
        console.error('Error loading products:', error)
      }
    }
    loadProducts()
  }, [])

  /**
   * Load invoice data if editing
   */
  useEffect(() => {
    if (invoice) {
      const loadInvoiceData = async () => {
        try {
          const data = await getInvoice(invoice.id)
          setCustomerId(data.customer_id)
          setBranchId(data.branch_id)
          setInvoiceDate(data.issue_date || data.invoice_date || '')
          setDueDate(data.due_date || '')
          
          // Load discount based on type
          const discountType = data.discount_type?.toLowerCase() || 'percentage'
          setInvoiceDiscountType(discountType as 'percentage' | 'fixed')
          
          const discountValue = data.discount_value || data.discount_percentage || 0
          if (discountType === 'percentage') {
            setDiscountPercentage(discountValue)
            setDiscountFixed(0)
          } else {
            setDiscountFixed(discountValue)
            setDiscountPercentage(0)
          }
          
          setTaxPercentage(data.tax_percentage || 0)
          setNotes(data.notes || '')

          // Convert invoice items to line items
          if (data.items) {
            const lineItems: InvoiceLineItem[] = data.items.map((item) => {
              const itemDiscountType = item.discount_type?.toLowerCase() || 'percentage'
              const itemDiscountValue = item.discount_value || item.discount_percentage || 0
              
              return {
                id: `item-${item.id}`,
                product_id: item.product_id,
                product: item.product,
                quantity: item.quantity,
                unit_price: item.unit_price,
                discount_type: itemDiscountType as 'percentage' | 'fixed',
                discount_percentage: itemDiscountType === 'percentage' ? itemDiscountValue : 0,
                discount_fixed: itemDiscountType === 'fixed' ? itemDiscountValue : 0,
                discount_amount: item.discount_amount,
                tax_percentage: item.tax_percentage || 0,
                tax_amount: item.tax_amount || 0,
                total: item.net_price || item.total || 0,
              }
            })
            setItems(lineItems)
          }
        } catch (error) {
          console.error('Error loading invoice:', error)
          toast.error('فشل تحميل بيانات الفاتورة')
        }
      }
      loadInvoiceData()
    }
  }, [invoice])

  /**
   * Recalculate totals when items or discounts change
   */
  useEffect(() => {
    const newSubtotal = items.reduce((sum, item) => sum + item.total, 0)
    
    // Calculate invoice discount based on type
    const newDiscountAmount = invoiceDiscountType === 'percentage'
      ? (newSubtotal * discountPercentage) / 100
      : discountFixed
    
    const afterDiscount = newSubtotal - newDiscountAmount
    const newTaxAmount = (afterDiscount * taxPercentage) / 100
    const newTotal = afterDiscount + newTaxAmount

    setSubtotal(newSubtotal)
    setDiscountAmount(newDiscountAmount)
    setTaxAmount(newTaxAmount)
    setTotalAmount(newTotal)
  }, [items, invoiceDiscountType, discountPercentage, discountFixed, taxPercentage])

  /**
   * Add new line item
   */
  const handleAddItem = () => {
    const newItem: InvoiceLineItem = {
      id: `item-${Date.now()}`,
      product_id: 0,
      quantity: 1,
      unit_price: 0,
      discount_type: 'percentage',
      discount_percentage: 0,
      discount_fixed: 0,
      discount_amount: 0,
      tax_percentage: 0,
      tax_amount: 0,
      total: 0,
    }
    setItems([...items, newItem])
  }

  /**
   * Remove line item
   */
  const handleRemoveItem = (id: string) => {
    setItems(items.filter((item) => item.id !== id))
  }

  /**
   * Update line item
   */
  const handleItemChange = (id: string, field: string, value: any) => {
    setItems(
      items.map((item) => {
        if (item.id !== id) return item

        const updated = { ...item, [field]: value }

        // If product changed, update price
        if (field === 'product_id') {
          const product = products.find((p) => p.id === value)
          updated.product = product
          updated.unit_price = product?.price || 0
        }

        // Recalculate item total based on discount type
        const itemSubtotal = updated.quantity * updated.unit_price
        const itemDiscount = updated.discount_type === 'percentage'
          ? (itemSubtotal * updated.discount_percentage) / 100
          : updated.discount_fixed
        const itemAfterDiscount = itemSubtotal - itemDiscount
        const itemTax = (itemAfterDiscount * updated.tax_percentage) / 100
        updated.discount_amount = itemDiscount
        updated.tax_amount = itemTax
        updated.total = itemAfterDiscount + itemTax

        return updated
      })
    )
  }

  /**
   * Handle form submission
   */
  const handleSubmit = async () => {
    // Validation
    if (!customerId) {
      toast.error('يجب اختيار العميل')
      return
    }
    if (items.length === 0) {
      toast.error('يجب إضافة صنف واحد على الأقل')
      return
    }
    if (items.some((item) => !item.product_id || item.quantity <= 0)) {
      toast.error('يجب ملء بيانات جميع الأصناف')
      return
    }
    
    // Validate issue type specific fields
    if (issueType === 'TRANSFER') {
      if (!targetBranchId) {
        toast.error('يجب تحديد الفرع المستهدف للتحويل')
        return
      }
      if (targetBranchId === branchId) {
        toast.error('الفرع المستهدف يجب أن يكون مختلفاً عن الفرع الحالي')
        return
      }
    }
    
    if (issueType === 'SALE' && !paymentType) {
      toast.error('يجب تحديد طريقة الدفع')
      return
    }

    try {
      setLoading(true)

      // Determine invoice discount values
      const hasInvoiceDiscount = invoiceDiscountType === 'percentage' 
        ? discountPercentage > 0 
        : discountFixed > 0
      
      const invoiceDiscountValue = invoiceDiscountType === 'percentage'
        ? discountPercentage
        : discountFixed

      const data: CreateSalesInvoiceInput = {
        customer_id: customerId,
        branch_id: branchId,
        issue_type: issueType,
        target_branch_id: issueType === 'TRANSFER' ? targetBranchId : undefined,
        payment_type: issueType === 'SALE' ? paymentType : undefined,
        issue_date: invoiceDate, // Backend uses issue_date
        due_date: dueDate || undefined,
        discount_type: hasInvoiceDiscount ? invoiceDiscountType.toUpperCase() as 'PERCENTAGE' | 'FIXED' : undefined,
        discount_value: hasInvoiceDiscount ? invoiceDiscountValue : undefined,
        tax_percentage: taxPercentage,
        notes: notes || undefined,
        items: items.map((item) => {
          const hasItemDiscount = item.discount_type === 'percentage'
            ? item.discount_percentage > 0
            : item.discount_fixed > 0
          
          const itemDiscountValue = item.discount_type === 'percentage'
            ? item.discount_percentage
            : item.discount_fixed
          
          return {
            product_id: item.product_id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            discount_type: hasItemDiscount ? item.discount_type.toUpperCase() as 'PERCENTAGE' | 'FIXED' : undefined,
            discount_value: hasItemDiscount ? itemDiscountValue : undefined,
            tax_percentage: item.tax_percentage,
          }
        }),
      }

      if (invoice) {
        await updateInvoice(invoice.id, data)
        toast.success('تم تحديث الفاتورة بنجاح')
      } else {
        await createInvoice(data)
        toast.success('تم إنشاء الفاتورة بنجاح')
      }

      onClose(true)
    } catch (error) {
      console.error('Error saving invoice:', error)
      toast.error('فشل حفظ الفاتورة')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto">
      <div className="bg-white rounded-lg shadow-lg w-full max-w-5xl my-8 mx-4">
        {/* Header */}
        <div className="flex justify-between items-center p-6 border-b">
          <h2 className="text-2xl font-bold">
            {invoice ? 'تعديل الفاتورة' : 'فاتورة جديدة'}
          </h2>
          <button onClick={() => onClose(false)} className="text-gray-500 hover:text-gray-700">
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 max-h-[calc(100vh-200px)] overflow-y-auto">
          {/* Edit Mode Warning */}
          {invoice && (
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
              <div className="flex items-center gap-2">
                <span className="text-blue-700 font-medium">
                  ⚠️ وضع التعديل - فاتورة #{invoice.id}
                </span>
              </div>
              <p className="text-sm text-blue-600 mt-1">
                يمكنك تعديل تفاصيل الفاتورة هنا. سيتم حفظ التغييرات مباشرة.
              </p>
            </div>
          )}
          
          {/* Invoice Info */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <Label>العميل *</Label>
              <Input
                type="number"
                value={customerId || ''}
                onChange={(e) => setCustomerId(Number(e.target.value))}
                placeholder="رقم العميل"
              />
            </div>

            <div>
              <Label>الفرع *</Label>
              <div className="relative">
                <Input
                  type="number"
                  value={branchId}
                  onChange={(e) => setBranchId(Number(e.target.value))}
                  min="1"
                  className="pl-24"
                />
                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-500">
                  الفرع الافتراضي
                </span>
              </div>
            </div>

            <div>
              <Label>نوع الإذن *</Label>
              <select
                value={issueType}
                onChange={(e) => setIssueType(e.target.value as 'SALE' | 'TRANSFER')}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="SALE">بيع</option>
                <option value="TRANSFER">تحويل بين فروع</option>
              </select>
            </div>

            {issueType === 'TRANSFER' && (
              <div>
                <Label>الفرع المستهدف *</Label>
                <Input
                  type="number"
                  value={targetBranchId || ''}
                  onChange={(e) => setTargetBranchId(Number(e.target.value))}
                  placeholder="رقم الفرع المستهدف"
                  min="1"
                />
                <p className="text-xs text-gray-500 mt-1">
                  يجب أن يكون مختلفاً عن الفرع الحالي
                </p>
              </div>
            )}

            {issueType === 'SALE' && (
              <div>
                <Label>طريقة الدفع *</Label>
                <select
                  value={paymentType}
                  onChange={(e) => setPaymentType(e.target.value as 'CASH' | 'CREDIT')}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="CASH">نقدي</option>
                  <option value="CREDIT">آجل</option>
                </select>
              </div>
            )}

            <div>
              <Label>تاريخ الفاتورة *</Label>
              <Input
                type="date"
                value={invoiceDate}
                onChange={(e) => setInvoiceDate(e.target.value)}
              />
            </div>

            <div>
              <Label>تاريخ الاستحقاق</Label>
              <Input
                type="date"
                value={dueDate}
                onChange={(e) => setDueDate(e.target.value)}
              />
            </div>
          </div>

          {/* Line Items */}
          <div className="mb-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-bold">الأصناف</h3>
              <Button onClick={handleAddItem} size="sm">
                <Plus className="w-4 h-4 ml-2" />
                إضافة صنف
              </Button>
            </div>

            <div className="border rounded-lg overflow-hidden">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المنتج</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الكمية</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">السعر</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الخصم</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">ضريبة %</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجمالي</th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500"></th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {items.map((item) => (
                    <tr key={item.id}>
                      <td className="px-4 py-3">
                        <select
                          value={item.product_id}
                          onChange={(e) =>
                            handleItemChange(item.id, 'product_id', Number(e.target.value))
                          }
                          className="w-full border rounded px-2 py-1"
                        >
                          <option value={0}>اختر المنتج</option>
                          {products.map((product) => (
                            <option key={product.id} value={product.id}>
                              {product.name}
                            </option>
                          ))}
                        </select>
                      </td>
                      <td className="px-4 py-3">
                        <Input
                          type="number"
                          min="1"
                          value={item.quantity}
                          onChange={(e) =>
                            handleItemChange(item.id, 'quantity', Number(e.target.value))
                          }
                          className="w-20"
                        />
                      </td>
                      <td className="px-4 py-3">
                        <Input
                          type="number"
                          min="0"
                          step="0.01"
                          value={item.unit_price}
                          onChange={(e) =>
                            handleItemChange(item.id, 'unit_price', Number(e.target.value))
                          }
                          className="w-24"
                        />
                      </td>
                      <td className="px-4 py-3">
                        <div className="space-y-1">
                          <div className="flex gap-1 mb-1">
                            <button
                              type="button"
                              onClick={() => handleItemChange(item.id, 'discount_type', 'percentage')}
                              className={`px-2 py-0.5 text-xs rounded ${
                                item.discount_type === 'percentage'
                                  ? 'bg-blue-600 text-white'
                                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                              }`}
                            >
                              %
                            </button>
                            <button
                              type="button"
                              onClick={() => handleItemChange(item.id, 'discount_type', 'fixed')}
                              className={`px-2 py-0.5 text-xs rounded ${
                                item.discount_type === 'fixed'
                                  ? 'bg-blue-600 text-white'
                                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                              }`}
                            >
                              ج
                            </button>
                          </div>
                          {item.discount_type === 'percentage' ? (
                            <Input
                              type="number"
                              min="0"
                              max="100"
                              step="0.01"
                              value={item.discount_percentage}
                              onChange={(e) =>
                                handleItemChange(item.id, 'discount_percentage', Number(e.target.value))
                              }
                              className="w-20 text-sm"
                            />
                          ) : (
                            <Input
                              type="number"
                              min="0"
                              step="0.01"
                              value={item.discount_fixed}
                              onChange={(e) =>
                                handleItemChange(item.id, 'discount_fixed', Number(e.target.value))
                              }
                              className="w-20 text-sm"
                            />
                          )}
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <Input
                          type="number"
                          min="0"
                          max="100"
                          value={item.tax_percentage}
                          onChange={(e) =>
                            handleItemChange(item.id, 'tax_percentage', Number(e.target.value))
                          }
                          className="w-16"
                        />
                      </td>
                      <td className="px-4 py-3 font-bold">
                        {item.total.toFixed(2)} ج
                      </td>
                      <td className="px-4 py-3">
                        <button
                          onClick={() => handleRemoveItem(item.id)}
                          className="text-red-600 hover:text-red-800"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </td>
                    </tr>
                  ))}
                  {items.length === 0 && (
                    <tr>
                      <td colSpan={7} className="px-4 py-8 text-center text-gray-500">
                        لا توجد أصناف. اضغط "إضافة صنف" لإضافة صنف جديد
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>

          {/* Totals */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Discounts & Tax */}
            <div className="space-y-4">
              <div>
                <div className="flex items-center justify-between mb-2">
                  <Label>خصم إضافي</Label>
                  <div className="flex gap-2">
                    <button
                      type="button"
                      onClick={() => setInvoiceDiscountType('percentage')}
                      className={`px-3 py-1 text-xs font-medium rounded-md transition-colors ${
                        invoiceDiscountType === 'percentage'
                          ? 'bg-blue-600 text-white'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                      }`}
                    >
                      نسبة %
                    </button>
                    <button
                      type="button"
                      onClick={() => setInvoiceDiscountType('fixed')}
                      className={`px-3 py-1 text-xs font-medium rounded-md transition-colors ${
                        invoiceDiscountType === 'fixed'
                          ? 'bg-blue-600 text-white'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                      }`}
                    >
                      مبلغ ثابت
                    </button>
                  </div>
                </div>
                {invoiceDiscountType === 'percentage' ? (
                  <div className="relative">
                    <Input
                      type="number"
                      min="0"
                      max="100"
                      step="0.01"
                      value={discountPercentage}
                      onChange={(e) => setDiscountPercentage(Number(e.target.value))}
                      className="pl-8"
                    />
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">
                      %
                    </span>
                  </div>
                ) : (
                  <div className="relative">
                    <Input
                      type="number"
                      min="0"
                      step="0.01"
                      value={discountFixed}
                      onChange={(e) => setDiscountFixed(Number(e.target.value))}
                      className="pl-8"
                    />
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">
                      ج
                    </span>
                  </div>
                )}
              </div>
              <div>
                <Label>ضريبة إضافية %</Label>
                <Input
                  type="number"
                  min="0"
                  max="100"
                  value={taxPercentage}
                  onChange={(e) => setTaxPercentage(Number(e.target.value))}
                />
              </div>
              <div>
                <Label>ملاحظات</Label>
                <textarea
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  className="w-full border rounded-lg px-3 py-2"
                  rows={3}
                  placeholder="أضف ملاحظات (اختياري)"
                />
              </div>
            </div>

            {/* Summary */}
            <div className="bg-gray-50 rounded-lg p-4 space-y-3">
              <div className="flex justify-between">
                <span>المجموع الفرعي:</span>
                <span className="font-bold">{subtotal.toFixed(2)} ج</span>
              </div>
              <div className="flex justify-between text-red-600">
                <span>
                  الخصم {invoiceDiscountType === 'percentage' ? `(${discountPercentage}%)` : '(ثابت)'}:
                </span>
                <span className="font-bold">-{discountAmount.toFixed(2)} ج</span>
              </div>
              <div className="flex justify-between">
                <span>بعد الخصم:</span>
                <span className="font-bold">{(subtotal - discountAmount).toFixed(2)} ج</span>
              </div>
              <div className="flex justify-between text-blue-600">
                <span>الضريبة ({taxPercentage}%):</span>
                <span className="font-bold">+{taxAmount.toFixed(2)} ج</span>
              </div>
              <div className="flex justify-between text-xl font-bold border-t-2 pt-3">
                <span>الإجمالي:</span>
                <span className="text-green-600">{totalAmount.toFixed(2)} ج</span>
              </div>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="flex gap-3 p-6 border-t">
          <Button onClick={() => onClose(false)} variant="outline" className="flex-1">
            إلغاء
          </Button>
          <Button onClick={handleSubmit} disabled={loading} className="flex-1">
            <Save className="w-4 h-4 ml-2" />
            {loading 
              ? (invoice ? 'جاري التحديث...' : 'جاري الحفظ...')
              : (invoice ? 'تحديث الفاتورة' : 'حفظ الفاتورة')
            }
          </Button>
        </div>
      </div>
    </div>
  )
}
