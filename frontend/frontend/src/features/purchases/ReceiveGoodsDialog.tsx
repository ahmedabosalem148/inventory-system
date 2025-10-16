/**
 * Receive Goods Dialog
 * Handle receiving goods for purchase order (partial or full)
 */

import { useState, useEffect } from 'react'
import { X, Package, Save } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from 'react-hot-toast'
import type { PurchaseOrder, PurchaseOrderItem, ReceiveGoodsInput } from '@/types'
import { receiveGoods } from '@/services/api/purchases'
import { formatCurrency } from '@/lib/utils'

interface ReceiveGoodsDialogProps {
  order: PurchaseOrder
  onClose: (received: boolean) => void
}

interface ReceivingItem {
  purchase_order_item_id: number
  product_name: string
  quantity_ordered: number
  quantity_received: number
  quantity_remaining: number
  quantity_to_receive: number
}

export const ReceiveGoodsDialog = ({ order, onClose }: ReceiveGoodsDialogProps) => {
  const [loading, setLoading] = useState(false)
  const [notes, setNotes] = useState('')
  const [receivingItems, setReceivingItems] = useState<ReceivingItem[]>([])

  /**
   * Initialize receiving items from order items
   */
  useEffect(() => {
    if (order.items) {
      const items: ReceivingItem[] = order.items.map((item: PurchaseOrderItem) => ({
        purchase_order_item_id: item.id,
        product_name: item.product?.name || `Product ${item.product_id}`,
        quantity_ordered: item.quantity_ordered,
        quantity_received: item.quantity_received,
        quantity_remaining: item.quantity_ordered - item.quantity_received,
        quantity_to_receive: item.quantity_ordered - item.quantity_received, // Default to remaining
      }))
      setReceivingItems(items)
    }
  }, [order])

  /**
   * Update quantity to receive for an item
   */
  const handleQuantityChange = (itemId: number, value: number) => {
    setReceivingItems(
      receivingItems.map((item) => {
        if (item.purchase_order_item_id === itemId) {
          // Ensure quantity doesn't exceed remaining
          const maxQuantity = item.quantity_remaining
          return {
            ...item,
            quantity_to_receive: Math.min(Math.max(0, value), maxQuantity),
          }
        }
        return item
      })
    )
  }

  /**
   * Handle form submission
   */
  const handleSubmit = async () => {
    // Filter items with quantity to receive > 0
    const itemsToReceive = receivingItems.filter((item) => item.quantity_to_receive > 0)

    if (itemsToReceive.length === 0) {
      toast.error('يجب إدخال كمية الاستلام لصنف واحد على الأقل')
      return
    }

    try {
      setLoading(true)

      const data: ReceiveGoodsInput = {
        purchase_order_id: order.id,
        items: itemsToReceive.map((item) => ({
          purchase_order_item_id: item.purchase_order_item_id,
          quantity_received: item.quantity_to_receive,
        })),
        notes: notes || undefined,
      }

      await receiveGoods(data)
      
      const totalReceived = itemsToReceive.reduce((sum, item) => sum + item.quantity_to_receive, 0)
      toast.success(`تم استلام ${totalReceived} وحدة بنجاح`)
      onClose(true)
    } catch (error) {
      console.error('Error receiving goods:', error)
      toast.error('فشل استلام البضائع')
    } finally {
      setLoading(false)
    }
  }

  /**
   * Receive all remaining quantities
   */
  const handleReceiveAll = () => {
    setReceivingItems(
      receivingItems.map((item) => ({
        ...item,
        quantity_to_receive: item.quantity_remaining,
      }))
    )
  }

  /**
   * Clear all quantities
   */
  const handleClearAll = () => {
    setReceivingItems(
      receivingItems.map((item) => ({
        ...item,
        quantity_to_receive: 0,
      }))
    )
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto">
      <div className="bg-white rounded-lg shadow-lg w-full max-w-4xl my-8 mx-4">
        {/* Header */}
        <div className="flex justify-between items-center p-6 border-b">
          <div>
            <h2 className="text-2xl font-bold flex items-center gap-2">
              <Package className="w-6 h-6" />
              استلام بضائع
            </h2>
            <p className="text-sm text-muted-foreground mt-1">
              أمر الشراء: {order.order_number} | المورد: {order.supplier?.name}
            </p>
          </div>
          <button onClick={() => onClose(false)} className="text-gray-500 hover:text-gray-700">
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 max-h-[calc(100vh-250px)] overflow-y-auto">
          {/* Order Summary */}
          <div className="bg-blue-50 rounded-lg p-4 mb-6">
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div>
                <p className="text-gray-600">تاريخ الأمر</p>
                <p className="font-bold">{new Date(order.order_date).toLocaleDateString('ar-EG')}</p>
              </div>
              <div>
                <p className="text-gray-600">إجمالي الأمر</p>
                <p className="font-bold">{formatCurrency(order.total_amount)}</p>
              </div>
              <div>
                <p className="text-gray-600">حالة الاستلام</p>
                <p className="font-bold">
                  {order.receiving_status === 'NOT_RECEIVED' && 'لم يستلم'}
                  {order.receiving_status === 'PARTIALLY_RECEIVED' && 'استلام جزئي'}
                  {order.receiving_status === 'FULLY_RECEIVED' && 'مستلم بالكامل'}
                </p>
              </div>
              <div>
                <p className="text-gray-600">عدد الأصناف</p>
                <p className="font-bold">{order.items?.length || 0}</p>
              </div>
            </div>
          </div>

          {/* Quick Actions */}
          <div className="flex gap-3 mb-4">
            <Button onClick={handleReceiveAll} variant="outline" size="sm">
              استلام الكل
            </Button>
            <Button onClick={handleClearAll} variant="outline" size="sm">
              مسح الكل
            </Button>
          </div>

          {/* Receiving Items Table */}
          <div className="border rounded-lg overflow-hidden mb-6">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المنتج</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الكمية المطلوبة</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المستلم سابقاً</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المتبقي</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الاستلام الآن</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {receivingItems.map((item) => (
                  <tr key={item.purchase_order_item_id}>
                    <td className="px-4 py-3 font-medium">{item.product_name}</td>
                    <td className="px-4 py-3">{item.quantity_ordered}</td>
                    <td className="px-4 py-3 text-blue-600">{item.quantity_received}</td>
                    <td className="px-4 py-3 text-orange-600 font-bold">
                      {item.quantity_remaining}
                    </td>
                    <td className="px-4 py-3">
                      <Input
                        type="number"
                        min="0"
                        max={item.quantity_remaining}
                        value={item.quantity_to_receive}
                        onChange={(e) =>
                          handleQuantityChange(item.purchase_order_item_id, Number(e.target.value))
                        }
                        className="w-24"
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Notes */}
          <div>
            <Label>ملاحظات</Label>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              className="w-full border rounded-lg px-3 py-2"
              rows={3}
              placeholder="أضف ملاحظات حول الاستلام (اختياري)"
            />
          </div>

          {/* Summary */}
          <div className="bg-green-50 rounded-lg p-4 mt-4">
            <p className="text-sm font-medium">
              إجمالي الكميات المطلوب استلامها:{' '}
              <span className="text-lg font-bold text-green-600">
                {receivingItems.reduce((sum, item) => sum + item.quantity_to_receive, 0)} وحدة
              </span>
            </p>
          </div>
        </div>

        {/* Footer */}
        <div className="flex gap-3 p-6 border-t">
          <Button onClick={() => onClose(false)} variant="outline" className="flex-1">
            إلغاء
          </Button>
          <Button onClick={handleSubmit} disabled={loading} className="flex-1">
            <Save className="w-4 h-4 ml-2" />
            {loading ? 'جاري الاستلام...' : 'تأكيد الاستلام'}
          </Button>
        </div>
      </div>
    </div>
  )
}
