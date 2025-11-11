/**
 * Profile Page
 * View and edit user profile information
 */

import { useState, useEffect } from 'react'
import { User, Mail, Phone, Shield, Key, Save, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { toast } from 'react-hot-toast'
import apiClient from '@/services/api/client'
import { PasswordChangeDialog } from '@/components/PasswordChangeDialog'

interface ProfileData {
  id: number
  name: string
  email: string
  phone?: string
  role: string
  assigned_branch_id?: number
  current_branch_id?: number
  is_active: boolean
  created_at: string
}

export function ProfilePage() {
  const [profile, setProfile] = useState<ProfileData | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [editMode, setEditMode] = useState(false)
  const [showPasswordDialog, setShowPasswordDialog] = useState(false)

  const [formData, setFormData] = useState({
    name: '',
    phone: '',
  })

  useEffect(() => {
    loadProfile()
  }, [])

  const loadProfile = async () => {
    try {
      setLoading(true)
      const response = await apiClient.get<{ success: boolean; data: ProfileData }>('/profile')
      setProfile(response.data.data)
      setFormData({
        name: response.data.data.name,
        phone: response.data.data.phone || '',
      })
    } catch (error) {
      console.error('Error loading profile:', error)
      toast.error('فشل في تحميل بيانات الملف الشخصي')
    } finally {
      setLoading(false)
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!formData.name.trim()) {
      toast.error('الرجاء إدخال الاسم')
      return
    }

    try {
      setSaving(true)
      await apiClient.put('/profile', {
        name: formData.name,
        phone: formData.phone || null,
      })
      toast.success('تم تحديث البيانات بنجاح')
      setEditMode(false)
      loadProfile()
    } catch (error: any) {
      console.error('Error updating profile:', error)
      toast.error(error.response?.data?.message || 'فشل في تحديث البيانات')
    } finally {
      setSaving(false)
    }
  }

  const handleCancel = () => {
    if (profile) {
      setFormData({
        name: profile.name,
        phone: profile.phone || '',
      })
    }
    setEditMode(false)
  }

  const getRoleBadgeVariant = (role: string) => {
    const variants: Record<string, 'success' | 'info' | 'warning' | 'default'> = {
      admin: 'warning',
      manager: 'success',
      accounting: 'info',
      store_user: 'default',
    }
    return variants[role] || 'default'
  }

  const getRoleLabel = (role: string) => {
    const labels: Record<string, string> = {
      admin: 'مدير النظام',
      manager: 'مدير',
      accounting: 'محاسب',
      store_user: 'مستخدم مخزن',
    }
    return labels[role] || role
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader2 className="w-8 h-8 animate-spin text-blue-600" />
      </div>
    )
  }

  if (!profile) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">فشل في تحميل بيانات الملف الشخصي</p>
      </div>
    )
  }

  return (
    <div className="space-y-6 max-w-4xl mx-auto">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <User className="h-8 w-8 text-blue-600" />
          الملف الشخصي
        </h1>
        <p className="text-gray-600 mt-1">
          عرض وتعديل بياناتك الشخصية
        </p>
      </div>

      {/* Profile Information Card */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>معلومات الحساب</CardTitle>
          {!editMode && (
            <Button
              variant="outline"
              onClick={() => setEditMode(true)}
            >
              تعديل البيانات
            </Button>
          )}
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit}>
            <div className="space-y-6">
              {/* Name */}
              <div className="space-y-2">
                <Label htmlFor="name">الاسم الكامل *</Label>
                <div className="relative">
                  <User className="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <Input
                    id="name"
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    disabled={!editMode}
                    className="pr-10"
                    placeholder="أدخل الاسم الكامل"
                  />
                </div>
              </div>

              {/* Email (Read-only) */}
              <div className="space-y-2">
                <Label htmlFor="email">البريد الإلكتروني</Label>
                <div className="relative">
                  <Mail className="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <Input
                    id="email"
                    type="email"
                    value={profile.email}
                    disabled
                    className="pr-10 bg-gray-50"
                  />
                </div>
                <p className="text-xs text-gray-500">
                  لا يمكن تغيير البريد الإلكتروني
                </p>
              </div>

              {/* Phone */}
              <div className="space-y-2">
                <Label htmlFor="phone">رقم الهاتف</Label>
                <div className="relative">
                  <Phone className="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <Input
                    id="phone"
                    type="text"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    disabled={!editMode}
                    className="pr-10"
                    placeholder="أدخل رقم الهاتف (اختياري)"
                  />
                </div>
              </div>

              {/* Role (Read-only) */}
              <div className="space-y-2">
                <Label htmlFor="role">الدور الوظيفي</Label>
                <div className="relative">
                  <Shield className="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <div className="pr-10 py-2 px-3 border border-gray-300 rounded-md bg-gray-50 flex items-center gap-2">
                    <Badge variant={getRoleBadgeVariant(profile.role)}>
                      {getRoleLabel(profile.role)}
                    </Badge>
                  </div>
                </div>
                <p className="text-xs text-gray-500">
                  اتصل بالمدير لتغيير الدور الوظيفي
                </p>
              </div>

              {/* Account Status */}
              <div className="space-y-2">
                <Label>حالة الحساب</Label>
                <div className="flex items-center gap-2">
                  <Badge variant={profile.is_active ? 'success' : 'danger'}>
                    {profile.is_active ? 'نشط' : 'معطل'}
                  </Badge>
                </div>
              </div>

              {/* Created Date */}
              <div className="space-y-2">
                <Label>تاريخ إنشاء الحساب</Label>
                <p className="text-sm text-gray-600">
                  {new Date(profile.created_at).toLocaleDateString('ar-EG', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                  })}
                </p>
              </div>

              {/* Action Buttons */}
              {editMode && (
                <div className="flex gap-3 pt-4">
                  <Button
                    type="submit"
                    disabled={saving}
                  >
                    {saving ? (
                      <>
                        <Loader2 className="ml-2 h-4 w-4 animate-spin" />
                        جاري الحفظ...
                      </>
                    ) : (
                      <>
                        <Save className="ml-2 h-4 w-4" />
                        حفظ التغييرات
                      </>
                    )}
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={handleCancel}
                    disabled={saving}
                  >
                    إلغاء
                  </Button>
                </div>
              )}
            </div>
          </form>
        </CardContent>
      </Card>

      {/* Security Card */}
      <Card>
        <CardHeader>
          <CardTitle>الأمان وكلمة المرور</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div className="flex items-start justify-between p-4 border rounded-lg">
              <div className="flex items-start gap-3">
                <Key className="w-5 h-5 text-gray-600 mt-1" />
                <div>
                  <h3 className="font-medium text-gray-900">تغيير كلمة المرور</h3>
                  <p className="text-sm text-gray-600 mt-1">
                    قم بتحديث كلمة المرور الخاصة بك بشكل دوري للحفاظ على أمان حسابك
                  </p>
                </div>
              </div>
              <Button
                variant="outline"
                onClick={() => setShowPasswordDialog(true)}
              >
                تغيير
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Password Change Dialog */}
      <PasswordChangeDialog
        isOpen={showPasswordDialog}
        onClose={() => setShowPasswordDialog(false)}
      />
    </div>
  )
}
