/**
 * Users Page
 * Manage system users with roles and permissions
 */

import { useState, useEffect } from 'react'
import { Users, Plus, Search, Edit, Trash2, Shield, Key } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { toast } from 'react-hot-toast'
import apiClient from '@/services/api/client'
import { UserDialog } from './UserDialog'
import { PasswordChangeDialog } from '@/components/PasswordChangeDialog'

interface User {
  id: number
  name: string
  email: string
  phone?: string
  role: string
  branch_name?: string
  is_active: boolean
  created_at: string
}

interface UsersListParams {
  search?: string
  role?: string
  is_active?: boolean
  page?: number
  per_page?: number
}

interface PaginatedResponse {
  data: User[]
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  current_page?: number
  last_page?: number
  per_page?: number
  total?: number
}

export function UsersPage() {
  const [users, setUsers] = useState<User[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [roleFilter, setRoleFilter] = useState<string>('all')
  const [statusFilter, setStatusFilter] = useState<string>('all')
  // @ts-ignore - TODO: Implement pagination
  const [currentPage, setCurrentPage] = useState(1)
  // @ts-ignore - TODO: Implement pagination
  const [totalPages, setTotalPages] = useState(1)
  const [showUserDialog, setShowUserDialog] = useState(false)
  const [selectedUser, setSelectedUser] = useState<User | null>(null)
  const [showPasswordDialog, setShowPasswordDialog] = useState(false)
  const [passwordResetUser, setPasswordResetUser] = useState<User | null>(null)

  useEffect(() => {
    loadUsers()
  }, [searchTerm, roleFilter, statusFilter, currentPage])

  const loadUsers = async () => {
    try {
      setLoading(true)
      const params: UsersListParams = {
        page: currentPage,
        per_page: 15,
      }

      if (searchTerm) params.search = searchTerm
      if (roleFilter !== 'all') params.role = roleFilter
      if (statusFilter !== 'all') params.is_active = statusFilter === 'active'

      const response = await apiClient.get<PaginatedResponse>('/users', { params })
      setUsers(response.data.data)
      setTotalPages(response.data.meta?.last_page || response.data.last_page || 1)
    } catch (error: any) {
      console.error('Error loading users:', error)
      toast.error('فشل في تحميل المستخدمين')
    } finally {
      setLoading(false)
    }
  }

  const handleAddNew = () => {
    setSelectedUser(null)
    setShowUserDialog(true)
  }

  const handleEdit = (user: User) => {
    setSelectedUser(user)
    setShowUserDialog(true)
  }

  const handleDelete = async (user: User) => {
    if (!confirm(`هل أنت متأكد من حذف المستخدم "${user.name}"؟`)) {
      return
    }

    try {
      await apiClient.delete(`/users/${user.id}`)
      toast.success('تم حذف المستخدم بنجاح')
      loadUsers()
    } catch (error: any) {
      console.error('Error deleting user:', error)
      toast.error(error.response?.data?.message || 'فشل حذف المستخدم')
    }
  }

  const handleChangePassword = (user: User) => {
    setPasswordResetUser(user)
    setShowPasswordDialog(true)
  }

  const handlePasswordDialogClose = () => {
    setShowPasswordDialog(false)
    setPasswordResetUser(null)
  }

  const handleDialogClose = (saved: boolean) => {
    setShowUserDialog(false)
    setSelectedUser(null)
    if (saved) {
      loadUsers()
    }
  }

  const getRoleBadge = (role: string) => {
    const roles: Record<string, { label: string; variant: 'default' | 'success' | 'warning' | 'info' }> = {
      admin: { label: 'مدير نظام', variant: 'success' },
      manager: { label: 'مدير', variant: 'info' },
      accountant: { label: 'محاسب', variant: 'warning' },
      store_user: { label: 'مستخدم مخزن', variant: 'default' },
    }
    return roles[role] || { label: role, variant: 'default' }
  }

  const columns = [
    {
      key: 'name',
      header: 'الاسم',
      sortable: true,
      render: (row: User) => (
        <div>
          <div className="font-medium">{row.name}</div>
          <div className="text-sm text-gray-500">{row.email}</div>
        </div>
      ),
    },
    {
      key: 'phone',
      header: 'الهاتف',
      render: (row: User) => (
        <span className="text-sm">{row.phone || '-'}</span>
      ),
    },
    {
      key: 'role',
      header: 'الدور الوظيفي',
      sortable: true,
      render: (row: User) => {
        const badge = getRoleBadge(row.role)
        return (
          <Badge variant={badge.variant} className="flex items-center gap-1 w-fit">
            <Shield className="w-3 h-3" />
            {badge.label}
          </Badge>
        )
      },
    },
    {
      key: 'branch_name',
      header: 'الفرع',
      sortable: true,
      render: (row: User) => (
        <span className="text-sm">{row.branch_name || '-'}</span>
      ),
    },
    {
      key: 'is_active',
      header: 'الحالة',
      sortable: true,
      render: (row: User) => (
        <Badge variant={row.is_active ? 'success' : 'default'}>
          {row.is_active ? 'نشط' : 'غير نشط'}
        </Badge>
      ),
    },
    {
      key: 'created_at',
      header: 'تاريخ الإنشاء',
      sortable: true,
      render: (row: User) => (
        <span className="text-sm text-gray-600">
          {new Date(row.created_at).toLocaleDateString('ar-EG')}
        </span>
      ),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (row: User) => (
        <div className="flex gap-2">
          <Button
            size="sm"
            variant="outline"
            onClick={() => handleEdit(row)}
          >
            <Edit className="w-4 h-4 ml-1" />
            تعديل
          </Button>
          <Button
            size="sm"
            variant="outline"
            onClick={() => handleChangePassword(row)}
            className="text-blue-600 hover:text-blue-700"
          >
            <Key className="w-4 h-4 ml-1" />
            كلمة المرور
          </Button>
          <Button
            size="sm"
            variant="outline"
            onClick={() => handleDelete(row)}
            className="text-red-600 hover:text-red-700"
          >
            <Trash2 className="w-4 h-4" />
          </Button>
        </div>
      ),
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold">إدارة المستخدمين</h1>
          <p className="text-gray-600 mt-1">
            إدارة مستخدمي النظام وصلاحياتهم
          </p>
        </div>
        <Button onClick={handleAddNew}>
          <Plus className="w-4 h-4 ml-2" />
          مستخدم جديد
        </Button>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="p-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            {/* Search */}
            <div className="md:col-span-2">
              <div className="relative">
                <Search className="absolute right-3 top-3 w-5 h-5 text-gray-400" />
                <Input
                  type="text"
                  placeholder="ابحث بالاسم أو البريد الإلكتروني..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pr-10"
                />
              </div>
            </div>

            {/* Role Filter */}
            <div>
              <select
                value={roleFilter}
                onChange={(e) => setRoleFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="all">جميع الأدوار</option>
                <option value="admin">مدير نظام</option>
                <option value="manager">مدير</option>
                <option value="accountant">محاسب</option>
                <option value="store_user">مستخدم مخزن</option>
              </select>
            </div>

            {/* Status Filter */}
            <div>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="all">جميع الحالات</option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">إجمالي المستخدمين</p>
                <p className="text-2xl font-bold">{users.length}</p>
              </div>
              <Users className="w-8 h-8 text-blue-600" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">المستخدمون النشطون</p>
                <p className="text-2xl font-bold text-green-600">
                  {users.filter(u => u.is_active).length}
                </p>
              </div>
              <Shield className="w-8 h-8 text-green-600" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">المديرون</p>
                <p className="text-2xl font-bold text-purple-600">
                  {users.filter(u => u.role === 'admin' || u.role === 'manager').length}
                </p>
              </div>
              <Shield className="w-8 h-8 text-purple-600" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">مستخدمو المخازن</p>
                <p className="text-2xl font-bold text-orange-600">
                  {users.filter(u => u.role === 'store_user').length}
                </p>
              </div>
              <Users className="w-8 h-8 text-orange-600" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Data Table */}
      <Card>
        <CardContent className="p-6">
          <DataTable
            columns={columns}
            data={users}
            loading={loading}
            emptyMessage="لا توجد مستخدمين"
          />
        </CardContent>
      </Card>

      {/* User Dialog */}
      {showUserDialog && (
        <UserDialog
          user={selectedUser}
          onClose={handleDialogClose}
        />
      )}

      {/* Password Change Dialog */}
      {showPasswordDialog && passwordResetUser && (
        <PasswordChangeDialog
          isOpen={showPasswordDialog}
          onClose={handlePasswordDialogClose}
          userId={passwordResetUser.id}
          userName={passwordResetUser.name}
        />
      )}
    </div>
  )
}
