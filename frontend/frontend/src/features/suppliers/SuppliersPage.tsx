/**
 * Suppliers Management Page
 * Main page for managing suppliers with CRUD operations
 */

import { useState, useEffect } from 'react'
import { Plus, Search, Edit2, Trash2, FileText } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { Card } from '@/components/ui/card'
import { SupplierDialog } from './SupplierDialog'
import { getSuppliers, deleteSupplier } from '@/services/api/suppliers'
import type { Supplier } from '@/types'

export const SuppliersPage = () => {
  const [suppliers, setSuppliers] = useState<Supplier[]>([])
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [isDialogOpen, setIsDialogOpen] = useState(false)
  const [editingSupplier, setEditingSupplier] = useState<Supplier | null>(null)

  // Stats
  const [stats, setStats] = useState({
    total: 0,
    active: 0,
    withBalance: 0,
  })

  useEffect(() => {
    loadSuppliers()
  }, [page, search])

  const loadSuppliers = async () => {
    try {
      setLoading(true)
      const response = await getSuppliers({
        page,
        per_page: 10,
        search: search || undefined,
      })
      setSuppliers(response.data)
      setTotalPages(response.meta?.last_page || response.last_page || 1)
      
      // Calculate stats
      setStats({
        total: response.meta?.total || response.total || 0,
        active: response.data.length,
        withBalance: response.data.filter(s => s.balance && s.balance > 0).length,
      })
    } catch (error) {
      console.error('Error loading suppliers:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleEdit = (supplier: Supplier) => {
    setEditingSupplier(supplier)
    setIsDialogOpen(true)
  }

  const handleDelete = async (supplier: Supplier) => {
    if (!window.confirm(`هل تريد حذف المورد "${supplier.name}"؟`)) return

    try {
      await deleteSupplier(supplier.id)
      loadSuppliers()
    } catch (error) {
      console.error('Error deleting supplier:', error)
      alert('حدث خطأ أثناء حذف المورد')
    }
  }

  const handleDialogClose = (saved: boolean) => {
    setIsDialogOpen(false)
    setEditingSupplier(null)
    if (saved) {
      loadSuppliers()
    }
  }

  const columns = [
    {
      key: 'name',
      header: 'اسم المورد',
      accessor: 'name' as const,
    },
    {
      key: 'phone',
      header: 'الهاتف',
      accessor: 'phone' as const,
      cell: (value: string) => value || '-',
    },
    {
      key: 'email',
      header: 'البريد الإلكتروني',
      accessor: 'email' as const,
      cell: (value: string) => value || '-',
    },
    {
      key: 'address',
      header: 'العنوان',
      accessor: 'address' as const,
      cell: (value: string) => value || '-',
    },
    {
      key: 'tax_number',
      header: 'الرقم الضريبي',
      accessor: 'tax_number' as const,
      cell: (value: string) => value || '-',
    },
    {
      key: 'balance',
      header: 'الرصيد',
      accessor: 'balance' as const,
      cell: (value: number) => {
        if (!value) return <Badge variant="default">0.00 ر.س</Badge>
        return value > 0 ? (
          <Badge variant="danger">{value.toFixed(2)} ر.س</Badge>
        ) : (
          <Badge variant="success">{Math.abs(value).toFixed(2)} ر.س</Badge>
        )
      },
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      accessor: 'id' as const,
      cell: (_: number, row: Supplier) => (
        <div className="flex gap-2">
          <Button
            size="sm"
            variant="ghost"
            onClick={() => handleEdit(row)}
          >
            <Edit2 className="h-4 w-4" />
          </Button>
          <Button
            size="sm"
            variant="ghost"
            onClick={() => handleDelete(row)}
          >
            <Trash2 className="h-4 w-4" />
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">إدارة الموردين</h1>
          <p className="text-gray-600 dark:text-gray-400">
            إدارة معلومات الموردين والحسابات
          </p>
        </div>
        <Button onClick={() => setIsDialogOpen(true)}>
          <Plus className="h-4 w-4 ml-2" />
          مورد جديد
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">إجمالي الموردين</p>
              <p className="text-2xl font-bold">{stats.total}</p>
            </div>
            <div className="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
              <FileText className="h-6 w-6 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">نشط</p>
              <p className="text-2xl font-bold">{stats.active}</p>
            </div>
            <div className="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
              <FileText className="h-6 w-6 text-green-600 dark:text-green-400" />
            </div>
          </div>
        </Card>

        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600 dark:text-gray-400">لديهم أرصدة</p>
              <p className="text-2xl font-bold">{stats.withBalance}</p>
            </div>
            <div className="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
              <FileText className="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
            </div>
          </div>
        </Card>
      </div>

      {/* Search and Filters */}
      <Card className="p-4">
        <div className="flex gap-4">
          <div className="flex-1 relative">
            <Search className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
            <input
              type="text"
              placeholder="البحث عن مورد..."
              value={search}
              onChange={(e) => {
                setSearch(e.target.value)
                setPage(1)
              }}
              className="w-full pr-10 pl-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800"
            />
          </div>
        </div>
      </Card>

      {/* Suppliers Table */}
      <Card>
        <DataTable
          columns={columns}
          data={suppliers}
          loading={loading}
        />
        
        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            <div className="text-sm text-gray-600 dark:text-gray-400">
              صفحة {page} من {totalPages}
            </div>
            <div className="flex gap-2">
              <Button
                size="sm"
                variant="outline"
                onClick={() => setPage(p => Math.max(1, p - 1))}
                disabled={page === 1}
              >
                السابق
              </Button>
              <Button
                size="sm"
                variant="outline"
                onClick={() => setPage(p => Math.min(totalPages, p + 1))}
                disabled={page === totalPages}
              >
                التالي
              </Button>
            </div>
          </div>
        )}
      </Card>

      {/* Supplier Dialog */}
      {isDialogOpen && (
        <SupplierDialog
          supplier={editingSupplier}
          onClose={handleDialogClose}
        />
      )}
    </div>
  )
}
