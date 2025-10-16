/**
 * Branches Page
 * Manage warehouses and branches
 */

import { useState, useEffect } from 'react'
import { Building2, Plus, MapPin } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { toast } from 'react-hot-toast'
import { apiClient } from '@/services/api/client'

interface Branch {
  id: number
  name: string
  code: string
  address?: string
  phone?: string
  manager_name?: string
  is_main: boolean
  status: string
}

export const BranchesPage = () => {
  const [branches, setBranches] = useState<Branch[]>([])
  const [loading, setLoading] = useState(false)

  const loadBranches = async () => {
    try {
      setLoading(true)
      const response = await apiClient.get('/branches')
      setBranches(response.data.data || response.data || [])
    } catch (error) {
      console.error('Error loading branches:', error)
      toast.error('فشل تحميل المخازن')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    loadBranches()
  }, [])

  const columns = [
    {
      key: 'name',
      header: 'اسم المخزن',
      render: (row: Branch) => (
        <div>
          <div className="font-medium flex items-center gap-2">
            {row.name}
            {row.is_main && (
              <Badge variant="default" className="text-xs">رئيسي</Badge>
            )}
          </div>
          <div className="text-sm text-gray-500">{row.code}</div>
        </div>
      ),
    },
    {
      key: 'address',
      header: 'العنوان',
      render: (row: Branch) => (
        <div className="flex items-center gap-2">
          <MapPin className="h-4 w-4 text-gray-400" />
          {row.address || '-'}
        </div>
      ),
    },
    {
      key: 'phone',
      header: 'الهاتف',
      render: (row: Branch) => row.phone || '-',
    },
    {
      key: 'manager_name',
      header: 'المدير',
      render: (row: Branch) => row.manager_name || '-',
    },
    {
      key: 'status',
      header: 'الحالة',
      render: (row: Branch) => (
        <Badge variant={row.status === 'active' ? 'success' : 'danger'}>
          {row.status === 'active' ? 'نشط' : 'غير نشط'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: () => (
        <div className="flex gap-2">
          <Button size="sm" variant="ghost">عرض</Button>
          <Button size="sm" variant="ghost">تعديل</Button>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-2">
            <Building2 className="h-8 w-8" />
            المخازن والفروع
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            إدارة المخازن والفروع في النظام
          </p>
        </div>
        <Button>
          <Plus className="h-4 w-4 ml-2" />
          مخزن جديد
        </Button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div>
            <p className="text-sm text-gray-600 dark:text-gray-400">إجمالي المخازن</p>
            <p className="text-2xl font-bold">{branches.length}</p>
          </div>
        </Card>
        <Card className="p-4">
          <div>
            <p className="text-sm text-gray-600 dark:text-gray-400">المخازن النشطة</p>
            <p className="text-2xl font-bold">
              {branches.filter(b => b.status === 'active').length}
            </p>
          </div>
        </Card>
        <Card className="p-4">
          <div>
            <p className="text-sm text-gray-600 dark:text-gray-400">المخزن الرئيسي</p>
            <p className="text-lg font-bold">
              {branches.find(b => b.is_main)?.name || '-'}
            </p>
          </div>
        </Card>
        <Card className="p-4">
          <div>
            <p className="text-sm text-gray-600 dark:text-gray-400">المخازن الفرعية</p>
            <p className="text-2xl font-bold">
              {branches.filter(b => !b.is_main).length}
            </p>
          </div>
        </Card>
      </div>

      {/* Table */}
      <Card>
        <DataTable
          columns={columns}
          data={branches}
          loading={loading}
        />
      </Card>
    </div>
  )
}
