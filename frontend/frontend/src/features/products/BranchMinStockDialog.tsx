import { useState, useEffect } from 'react'
import { X } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { toast } from 'react-hot-toast'
import type { Product } from '@/types'
import { getProductBranchMinStock, updateProductBranchMinStock, type BranchStock } from '@/services/api/products'

interface BranchMinStockDialogProps {
  product: Product
  onClose: (updated: boolean) => void
}

export const BranchMinStockDialog = ({ product, onClose }: BranchMinStockDialogProps) => {
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [branchStocks, setBranchStocks] = useState<BranchStock[]>([])
  const [editedValues, setEditedValues] = useState<Record<number, number>>({})

  /**
   * Load branch stocks on mount
   */
  useEffect(() => {
    loadBranchStocks()
  }, [product.id])

  const loadBranchStocks = async () => {
    try {
      setLoading(true)
      const data = await getProductBranchMinStock(product.id)
      setBranchStocks(data.branch_stocks)
      
      // Initialize edited values with current min_qty
      const initialValues: Record<number, number> = {}
      data.branch_stocks.forEach(bs => {
        initialValues[bs.branch_id] = bs.min_qty
      })
      setEditedValues(initialValues)
    } catch (error) {
      console.error('Error loading branch stocks:', error)
      toast.error('فشل تحميل بيانات الفروع')
    } finally {
      setLoading(false)
    }
  }

  const handleMinQtyChange = (branchId: number, value: number) => {
    setEditedValues(prev => ({
      ...prev,
      [branchId]: value
    }))
  }

  const handleSave = async (branchId: number) => {
    const newMinQty = editedValues[branchId]
    
    if (newMinQty < 0) {
      toast.error('الحد الأدنى يجب أن يكون 0 أو أكثر')
      return
    }

    try {
      setSaving(true)
      await updateProductBranchMinStock(product.id, branchId, newMinQty)
      
      // Update local state
      setBranchStocks(prev => prev.map(bs => 
        bs.branch_id === branchId 
          ? { ...bs, min_qty: newMinQty, is_low: bs.current_stock < newMinQty }
          : bs
      ))
      
      toast.success('تم تحديث الحد الأدنى بنجاح')
    } catch (error) {
      console.error('Error updating min stock:', error)
      toast.error('فشل تحديث الحد الأدنى')
    } finally {
      setSaving(false)
    }
  }

  const handleSaveAll = async () => {
    try {
      setSaving(true)
      
      // Update all changed values
      const updates = branchStocks
        .filter(bs => editedValues[bs.branch_id] !== bs.min_qty)
        .map(bs => 
          updateProductBranchMinStock(product.id, bs.branch_id, editedValues[bs.branch_id])
        )
      
      if (updates.length === 0) {
        toast.success('لا توجد تغييرات للحفظ')
        return
      }

      await Promise.all(updates)
      
      // Reload data
      await loadBranchStocks()
      
      toast.success(`تم تحديث ${updates.length} فرع بنجاح`)
      onClose(true)
    } catch (error) {
      console.error('Error updating all min stocks:', error)
      toast.error('فشل تحديث بعض الفروع')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto">
      <div className="bg-white rounded-lg shadow-lg w-full max-w-3xl my-8 mx-4">
        {/* Header */}
        <div className="flex justify-between items-center p-6 border-b">
          <div>
            <h2 className="text-2xl font-bold">إدارة الحدود الدنيا للمخزون</h2>
            <p className="text-sm text-gray-600 mt-1">المنتج: {product.name}</p>
          </div>
          <button 
            onClick={() => onClose(false)} 
            className="text-gray-500 hover:text-gray-700"
          >
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6">
          {loading ? (
            <div className="text-center py-8">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
              <p className="text-gray-500 mt-4">جاري التحميل...</p>
            </div>
          ) : branchStocks.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <p>لا توجد بيانات فروع لهذا المنتج</p>
              <p className="text-sm mt-2">يجب أن يكون للمنتج مخزون في أحد الفروع أولاً</p>
            </div>
          ) : (
            <>
              {/* Info Box */}
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p className="text-sm text-blue-800">
                  💡 الحد الأدنى هو الكمية التي يجب أن يكون المخزون أعلى منها. عند الوصول إليها أو أقل، 
                  سيظهر المنتج في تنبيهات المخزون المنخفض.
                </p>
              </div>

              {/* Table */}
              <div className="border rounded-lg overflow-hidden">
                <table className="w-full">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الفرع</th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المخزون الحالي</th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الحد الأدنى</th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {branchStocks.map((bs) => (
                      <tr key={bs.branch_id} className={bs.is_low ? 'bg-red-50' : ''}>
                        <td className="px-4 py-3 font-medium">{bs.branch_name}</td>
                        <td className="px-4 py-3">
                          <span className={`font-bold ${bs.is_low ? 'text-red-600' : 'text-green-600'}`}>
                            {bs.current_stock} وحدة
                          </span>
                        </td>
                        <td className="px-4 py-3">
                          <Input
                            type="number"
                            min="0"
                            value={editedValues[bs.branch_id] ?? bs.min_qty}
                            onChange={(e) => handleMinQtyChange(bs.branch_id, Number(e.target.value))}
                            className="w-28"
                          />
                        </td>
                        <td className="px-4 py-3">
                          {bs.is_low ? (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                              منخفض
                            </span>
                          ) : (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                              جيد
                            </span>
                          )}
                        </td>
                        <td className="px-4 py-3">
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleSave(bs.branch_id)}
                            disabled={saving || editedValues[bs.branch_id] === bs.min_qty}
                          >
                            {saving ? 'جاري الحفظ...' : 'حفظ'}
                          </Button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* Summary */}
              <div className="mt-4 flex gap-4 text-sm">
                <div className="flex items-center gap-2">
                  <div className="w-3 h-3 rounded-full bg-green-500"></div>
                  <span>جيد: {branchStocks.filter(bs => !bs.is_low).length}</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-3 h-3 rounded-full bg-red-500"></div>
                  <span>منخفض: {branchStocks.filter(bs => bs.is_low).length}</span>
                </div>
              </div>
            </>
          )}
        </div>

        {/* Footer */}
        <div className="flex gap-3 p-6 border-t">
          <Button 
            onClick={() => onClose(false)} 
            variant="outline" 
            className="flex-1"
          >
            إلغاء
          </Button>
          <Button 
            onClick={handleSaveAll} 
            disabled={saving || loading || branchStocks.length === 0}
            className="flex-1"
          >
            {saving ? 'جاري الحفظ...' : 'حفظ الكل'}
          </Button>
        </div>
      </div>
    </div>
  )
}
