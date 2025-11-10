/**
 * Customer Ledger Dialog
 * View customer account statement and add manual entries
 */

import { useState, useEffect } from 'react'
import { X, Plus, Download } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { toast } from 'react-hot-toast'
import type { Customer, CustomerLedger } from '@/types'
import { 
  getCustomerLedger, 
  createLedgerEntry,
  exportCustomerStatement 
} from '@/services/api/customers'
import { formatCurrency, formatDate } from '@/lib/utils'

interface CustomerLedgerDialogProps {
  customer: Customer
  onClose: () => void
}

export const CustomerLedgerDialog = ({ customer, onClose }: CustomerLedgerDialogProps) => {
  const [loading, setLoading] = useState(false)
  const [ledgerEntries, setLedgerEntries] = useState<CustomerLedger[]>([])
  const [showAddEntry, setShowAddEntry] = useState(false)
  const [dateFrom, setDateFrom] = useState('')
  const [dateTo, setDateTo] = useState('')

  // Add entry form
  const [entryType, setEntryType] = useState<'DEBIT' | 'CREDIT'>('DEBIT')
  const [entryAmount, setEntryAmount] = useState(0)
  const [entryDescription, setEntryDescription] = useState('')
  const [entryDate, setEntryDate] = useState(new Date().toISOString().split('T')[0])

  /**
   * Load ledger entries
   */
  const loadLedger = async () => {
    try {
      setLoading(true)
      const params: any = { per_page: 50 }
      if (dateFrom) params.date_from = dateFrom
      if (dateTo) params.date_to = dateTo

      const response = await getCustomerLedger(customer.id, params)
      setLedgerEntries(response.data)
    } catch (error) {
      console.error('Error loading ledger:', error)
      toast.error('فشل تحميل كشف الحساب')
    } finally {
      setLoading(false)
    }
  }

  /**
   * Load ledger on mount and when filters change
   */
  useEffect(() => {
    loadLedger()
  }, [customer.id, dateFrom, dateTo])

  /**
   * Handle add entry
   */
  const handleAddEntry = async () => {
    if (entryAmount <= 0) {
      toast.error('يجب إدخال المبلغ')
      return
    }
    if (!entryDescription.trim()) {
      toast.error('يجب إدخال الوصف')
      return
    }

    try {
      await createLedgerEntry({
        customer_id: customer.id,
        entry_date: entryDate,
        type: entryType,
        amount: entryAmount,
        description: entryDescription.trim(),
      })

      toast.success('تم إضافة القيد بنجاح')
      setShowAddEntry(false)
      setEntryAmount(0)
      setEntryDescription('')
      loadLedger()
    } catch (error) {
      console.error('Error adding entry:', error)
      toast.error('فشل إضافة القيد')
    }
  }

  /**
   * Handle export statement
   */
  const handleExport = async () => {
    try {
      const params: any = {}
      if (dateFrom) params.date_from = dateFrom
      if (dateTo) params.date_to = dateTo

      const blob = await exportCustomerStatement(customer.id, params)
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `customer_statement_${customer.name}_${new Date().toISOString().split('T')[0]}.pdf`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
      toast.success('تم تحميل كشف الحساب')
    } catch (error) {
      console.error('Error exporting statement:', error)
      toast.error('فشل تحميل كشف الحساب')
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto">
      <div className="bg-white rounded-lg shadow-lg w-full max-w-4xl my-8 mx-4">
        {/* Header */}
        <div className="flex justify-between items-center p-6 border-b">
          <div>
            <h2 className="text-2xl font-bold">كشف حساب عميل</h2>
            <p className="text-sm text-muted-foreground mt-1">
              {customer.name} | الرصيد الحالي: {formatCurrency(customer.balance || 0)}
            </p>
          </div>
          <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
            <X className="w-6 h-6" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 max-h-[calc(100vh-250px)] overflow-y-auto">
          {/* Filters & Actions */}
          <div className="flex flex-wrap gap-3 mb-6">
            <div className="flex-1 min-w-[150px]">
              <Label className="text-xs">من تاريخ</Label>
              <Input
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
              />
            </div>
            <div className="flex-1 min-w-[150px]">
              <Label className="text-xs">إلى تاريخ</Label>
              <Input
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
              />
            </div>
            <div className="flex gap-2 items-end">
              <Button onClick={() => setShowAddEntry(!showAddEntry)} size="sm">
                <Plus className="w-4 h-4 ml-1" />
                قيد يدوي
              </Button>
              <Button onClick={handleExport} variant="outline" size="sm">
                <Download className="w-4 h-4 ml-1" />
                تصدير PDF
              </Button>
            </div>
          </div>

          {/* Add Entry Form */}
          {showAddEntry && (
            <div className="bg-blue-50 rounded-lg p-4 mb-6">
              <h3 className="font-bold mb-4">إضافة قيد يدوي</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label>التاريخ</Label>
                  <Input
                    type="date"
                    value={entryDate}
                    onChange={(e) => setEntryDate(e.target.value)}
                  />
                </div>
                <div>
                  <Label>النوع</Label>
                  <select
                    value={entryType}
                    onChange={(e) => setEntryType(e.target.value as 'DEBIT' | 'CREDIT')}
                    className="w-full border rounded-lg px-3 py-2"
                  >
                    <option value="DEBIT">مدين (دفعة من العميل)</option>
                    <option value="CREDIT">دائن (مبلغ على العميل)</option>
                  </select>
                </div>
                <div>
                  <Label>المبلغ</Label>
                  <Input
                    type="number"
                    min="0"
                    step="0.01"
                    value={entryAmount}
                    onChange={(e) => setEntryAmount(Number(e.target.value))}
                    placeholder="0.00"
                  />
                </div>
                <div>
                  <Label>الوصف</Label>
                  <Input
                    value={entryDescription}
                    onChange={(e) => setEntryDescription(e.target.value)}
                    placeholder="وصف القيد"
                  />
                </div>
              </div>
              <div className="flex gap-2 mt-4">
                <Button onClick={handleAddEntry} size="sm">
                  إضافة
                </Button>
                <Button onClick={() => setShowAddEntry(false)} variant="outline" size="sm">
                  إلغاء
                </Button>
              </div>
            </div>
          )}

          {/* Ledger Entries Table */}
          <div className="border rounded-lg overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">النوع</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الوصف</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">مدين</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">دائن</th>
                  <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الرصيد</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {loading ? (
                  <tr>
                    <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                      جاري التحميل...
                    </td>
                  </tr>
                ) : ledgerEntries.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                      لا توجد حركات
                    </td>
                  </tr>
                ) : (
                  ledgerEntries.map((entry) => (
                    <tr key={entry.id}>
                      <td className="px-4 py-3 text-sm">{formatDate(entry.transaction_date)}</td>
                      <td className="px-4 py-3">
                        <Badge variant={entry.debit > 0 ? "success" : "danger"}>
                          {entry.transaction_type}
                        </Badge>
                      </td>
                      <td className="px-4 py-3 text-sm">{entry.reference_number}</td>
                      <td className="px-4 py-3 font-medium text-green-600">
                        {entry.debit > 0 ? formatCurrency(entry.debit) : '-'}
                      </td>
                      <td className="px-4 py-3 font-medium text-red-600">
                        {entry.credit > 0 ? formatCurrency(entry.credit) : '-'}
                      </td>
                      <td className="px-4 py-3 font-bold">
                        {formatCurrency(entry.balance)}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Footer */}
        <div className="flex justify-end p-6 border-t">
          <Button onClick={onClose} variant="outline">
            إغلاق
          </Button>
        </div>
      </div>
    </div>
  )
}
