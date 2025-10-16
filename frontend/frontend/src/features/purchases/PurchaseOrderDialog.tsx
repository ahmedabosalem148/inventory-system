/**
 * Purchase Order Dialog
 * Create/Edit/View purchase order with line items
 */

import { useState, useEffect } from 'react'
import { X, Plus, Trash2, Save } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from 'react-hot-toast'
import type { PurchaseOrder, CreatePurchaseOrderInput, Product, Supplier } from '@/types'
import { createPurchaseOrder, updatePurchaseOrder, getPurchaseOrder, getSuppliers } from '@/services/api/purchases'
import { getProducts } from '@/services/api/products'

interface PurchaseOrderDialogProps {
  order?: PurchaseOrder | null
  onClose: (saved: boolean) => void
}

interface PurchaseOrderLineItem {
  id: string // temp id for UI
  product_id: number
  product?: Product
  quantity_ordered: number
  unit_cost: number
  discount_percentage: number
  discount_amount: number
  tax_percentage: number
  tax_amount: number
  total: number
}

export const PurchaseOrderDialog = ({ order, onClose }: PurchaseOrderDialogProps) => {
  const [loading, setLoading] = useState(false)
  const [products, setProducts] = useState<Product[]>([])
  const [suppliers, setSuppliers] = useState<Supplier[]>([])

  // Form data
  const [supplierId, setSupplierId] = useState<number>(0)
  const [branchId, setBranchId] = useState<number>(1)
  const [orderDate, setOrderDate] = useState(
    new Date().toISOString().split('T')[0]
  )
  const [expectedDeliveryDate, setExpectedDeliveryDate] = useState('')
  const [discountPercentage, setDiscountPercentage] = useState(0)
  const [taxPercentage, setTaxPercentage] = useState(0)
  const [notes, setNotes] = useState('')
  const [items, setItems] = useState<PurchaseOrderLineItem[]>([])

  // Calculations
  const [subtotal, setSubtotal] = useState(0)
  const [discountAmount, setDiscountAmount] = useState(0)
  const [taxAmount, setTaxAmount] = useState(0)
  const [totalAmount, setTotalAmount] = useState(0)

  /**
   * Load products and suppliers for dropdowns
   */
  useEffect(() => {
    const loadData = async () => {
      try {
        const [productsResponse, suppliersResponse] = await Promise.all([
          getProducts({ per_page: 100 }),
          getSuppliers({ per_page: 100 }),
        ])
        setProducts(productsResponse.data)
        setSuppliers(suppliersResponse.data)
      } catch (error) {
        console.error('Error loading data:', error)
      }
    }
    loadData()
  }, [])

  /**
   * Load order data if editing
   */
  useEffect(() => {
    if (order) {
      const loadOrderData = async () => {
        try {
          const data = await getPurchaseOrder(order.id)
          setSupplierId(data.supplier_id)
          setBranchId(data.branch_id)
          setOrderDate(data.order_date)
          setExpectedDeliveryDate(data.expected_delivery_date || '')
          setDiscountPercentage(data.discount_percentage)
          setTaxPercentage(data.tax_percentage)
          setNotes(data.notes || '')

          // Convert order items to line items
          if (data.items) {
            const lineItems: PurchaseOrderLineItem[] = data.items.map((item) => ({
              id: `item-${item.id}`,
              product_id: item.product_id,
              product: item.product,
              quantity_ordered: item.quantity_ordered,
              unit_cost: item.unit_cost,
              discount_percentage: item.discount_percentage,
              discount_amount: item.discount_amount,
              tax_percentage: item.tax_percentage,
              tax_amount: item.tax_amount,
              total: item.total,
            }))
            setItems(lineItems)
          }
        } catch (error) {
          console.error('Error loading order:', error)
          toast.error('فشل تحميل بيانات أمر الشراء')
        }
      }
      loadOrderData()
    }
  }, [order])

  /**
   * Recalculate totals when items or discounts change
   */
  useEffect(() => {
    const newSubtotal = items.reduce((sum, item) => sum + item.total, 0)
    const newDiscountAmount = (newSubtotal * discountPercentage) / 100
    const afterDiscount = newSubtotal - newDiscountAmount
    const newTaxAmount = (afterDiscount * taxPercentage) / 100
    const newTotal = afterDiscount + newTaxAmount

    setSubtotal(newSubtotal)
    setDiscountAmount(newDiscountAmount)
    setTaxAmount(newTaxAmount)
    setTotalAmount(newTotal)
  }, [items, discountPercentage, taxPercentage])

  /**
   * Add new line item
   */
  const handleAddItem = () => {
    const newItem: PurchaseOrderLineItem = {
      id: `item-${Date.now()}`,
      product_id: 0,
      quantity_ordered: 1,
      unit_cost: 0,
      discount_percentage: 0,
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

        // If product changed, update cost from product
        if (field === 'product_id') {
          const product = products.find((p) => p.id === value)
          updated.product = product
          updated.unit_cost = product?.cost || product?.price || 0
        }

        // Recalculate item total
        const itemSubtotal = updated.quantity_ordered * updated.unit_cost
        const itemDiscount = (itemSubtotal * updated.discount_percentage) / 100
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
    if (!supplierId) {
      toast.error('يجب اختيار المورد')
      return
    }
    if (items.length === 0) {
      toast.error('يجب إضافة صنف واحد على الأقل')
      return
    }
    if (items.some((item) => !item.product_id || item.quantity_ordered <= 0)) {
      toast.error('يجب ملء بيانات جميع الأصناف')
      return
    }

    try {
      setLoading(true)

      const data: CreatePurchaseOrderInput = {
        supplier_id: supplierId,
        branch_id: branchId,
        order_date: orderDate,
        expected_delivery_date: expectedDeliveryDate || undefined,
        discount_percentage: discountPercentage,
        tax_percentage: taxPercentage,
        notes: notes || undefined,
        items: items.map((item) => ({
          product_id: item.product_id,
          quantity_ordered: item.quantity_ordered,
          unit_cost: item.unit_cost,
          discount_percentage: item.discount_percentage,
          tax_percentage: item.tax_percentage,
        })),
      }

      if (order) {
        await updatePurchaseOrder(order.id, data)
        toast.success('تم تحديث أمر الشراء بنجاح')
      } else {
        await createPurchaseOrder(data)
        toast.success('تم إنشاء أمر الشراء بنجاح')
      }

      onClose(true)
    } catch (error) {
      console.error('Error saving order:', error)
      toast.error('فشل حفظ أمر الشراء')
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
            {order ? 'تعديل أمر الشراء' : 'أمر شراء جديد'}
          </h2>
          <button onClick={() => onClose(false)} className="text-gray-500 hover:text-gray-700">
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 max-h-[calc(100vh-200px)] overflow-y-auto">
          {/* Order Info */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <Label>المورد *</Label>
              <select
                value={supplierId}
                onChange={(e) => setSupplierId(Number(e.target.value))}
                className="w-full border rounded-lg px-3 py-2"
              >
                <option value={0}>اختر المورد</option>
                {suppliers.map((supplier) => (
                  <option key={supplier.id} value={supplier.id}>
                    {supplier.name}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <Label>الفرع</Label>
              <Input
                type="number"
                value={branchId}
                onChange={(e) => setBranchId(Number(e.target.value))}
              />
            </div>

            <div>
              <Label>تاريخ الأمر *</Label>
              <Input
                type="date"
                value={orderDate}
                onChange={(e) => setOrderDate(e.target.value)}
              />
            </div>

            <div>
              <Label>تاريخ التسليم المتوقع</Label>
              <Input
                type="date"
                value={expectedDeliveryDate}
                onChange={(e) => setExpectedDeliveryDate(e.target.value)}
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
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">خصم %</th>
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
                          value={item.quantity_ordered}
                          onChange={(e) =>
                            handleItemChange(item.id, 'quantity_ordered', Number(e.target.value))
                          }
                          className="w-20"
                        />
                      </td>
                      <td className="px-4 py-3">
                        <Input
                          type="number"
                          min="0"
                          step="0.01"
                          value={item.unit_cost}
                          onChange={(e) =>
                            handleItemChange(item.id, 'unit_cost', Number(e.target.value))
                          }
                          className="w-24"
                        />
                      </td>
                      <td className="px-4 py-3">
                        <Input
                          type="number"
                          min="0"
                          max="100"
                          value={item.discount_percentage}
                          onChange={(e) =>
                            handleItemChange(item.id, 'discount_percentage', Number(e.target.value))
                          }
                          className="w-16"
                        />
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
                <Label>خصم إضافي %</Label>
                <Input
                  type="number"
                  min="0"
                  max="100"
                  value={discountPercentage}
                  onChange={(e) => setDiscountPercentage(Number(e.target.value))}
                />
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
                <span>الخصم ({discountPercentage}%):</span>
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
                <span className="text-purple-600">{totalAmount.toFixed(2)} ج</span>
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
            {loading ? 'جاري الحفظ...' : 'حفظ الأمر'}
          </Button>
        </div>
      </div>
    </div>
  )
}
