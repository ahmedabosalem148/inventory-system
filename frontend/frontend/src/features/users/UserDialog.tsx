/**
 * User Dialog
 * Create and edit user with role and permissions
 */

import { useState, useEffect } from 'react'
import { X, Save, Loader2 } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { toast } from 'react-hot-toast'
import apiClient from '@/services/api/client'

interface User {
  id: number
  name: string
  email: string
  phone?: string
  role: string
  branch_id?: number
  is_active: boolean
}

interface Branch {
  id: number
  name: string
  code: string
}

interface UserDialogProps {
  user: User | null
  onClose: (saved: boolean) => void
}

interface FormData {
  name: string
  email: string
  phone: string
  password: string
  password_confirmation: string
  role: string
  branch_id: string
  is_active: boolean
}

interface FormErrors {
  name?: string
  email?: string
  phone?: string
  password?: string
  password_confirmation?: string
  role?: string
  branch_id?: string
}

export function UserDialog({ user, onClose }: UserDialogProps) {
  const isEdit = !!user

  const [formData, setFormData] = useState<FormData>({
    name: user?.name || '',
    email: user?.email || '',
    phone: user?.phone || '',
    password: '',
    password_confirmation: '',
    role: user?.role || 'store_user',
    branch_id: user?.branch_id?.toString() || '',
    is_active: user?.is_active ?? true,
  })

  const [branches, setBranches] = useState<Branch[]>([])
  const [errors, setErrors] = useState<FormErrors>({})
  const [loading, setLoading] = useState(false)
  const [loadingBranches, setLoadingBranches] = useState(true)

  useEffect(() => {
    loadBranches()
  }, [])

  const loadBranches = async () => {
    try {
      setLoadingBranches(true)
      const response = await apiClient.get<{ data: Branch[] }>('/branches')
      setBranches(response.data.data || response.data)
    } catch (error) {
      console.error('Error loading branches:', error)
      toast.error('فشل تحميل الفروع')
    } finally {
      setLoadingBranches(false)
    }
  }

  const validateForm = (): boolean => {
    const newErrors: FormErrors = {}

    if (!formData.name.trim()) {
      newErrors.name = 'الاسم مطلوب'
    }

    if (!formData.email.trim()) {
      newErrors.email = 'البريد الإلكتروني مطلوب'
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = 'صيغة البريد الإلكتروني غير صحيحة'
    }

    if (!isEdit) {
      if (!formData.password) {
        newErrors.password = 'كلمة المرور مطلوبة'
      } else if (formData.password.length < 6) {
        newErrors.password = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل'
      }

      if (formData.password !== formData.password_confirmation) {
        newErrors.password_confirmation = 'كلمة المرور غير متطابقة'
      }
    } else if (formData.password && formData.password !== formData.password_confirmation) {
      newErrors.password_confirmation = 'كلمة المرور غير متطابقة'
    }

    if (!formData.role) {
      newErrors.role = 'الدور الوظيفي مطلوب'
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!validateForm()) {
      return
    }

    try {
      setLoading(true)

      const payload: any = {
        name: formData.name.trim(),
        email: formData.email.trim(),
        phone: formData.phone.trim() || null,
        role: formData.role,
        branch_id: formData.branch_id ? parseInt(formData.branch_id) : null,
        is_active: formData.is_active,
      }

      if (!isEdit || formData.password) {
        payload.password = formData.password
        payload.password_confirmation = formData.password_confirmation
      }

      if (isEdit) {
        await apiClient.put(`/users/${user.id}`, payload)
        toast.success('تم تحديث المستخدم بنجاح')
      } else {
        await apiClient.post('/users', payload)
        toast.success('تم إضافة المستخدم بنجاح')
      }

      onClose(true)
    } catch (error: any) {
      console.error('Error saving user:', error)
      if (error.response?.data?.errors) {
        setErrors(error.response.data.errors)
      }
      toast.error(error.response?.data?.message || 'فشل حفظ المستخدم')
    } finally {
      setLoading(false)
    }
  }

  const handleChange = (field: keyof FormData, value: any) => {
    setFormData(prev => ({ ...prev, [field]: value }))
    if (errors[field as keyof FormErrors]) {
      setErrors(prev => ({ ...prev, [field]: undefined }))
    }
  }

  return (
    <Dialog open onOpenChange={() => onClose(false)}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {isEdit ? 'تعديل المستخدم' : 'إضافة مستخدم جديد'}
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4 mt-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* Name */}
            <div>
              <label className="block text-sm font-medium mb-1">
                الاسم <span className="text-red-500">*</span>
              </label>
              <Input
                type="text"
                value={formData.name}
                onChange={(e) => handleChange('name', e.target.value)}
                placeholder="أدخل اسم المستخدم"
                className={errors.name ? 'border-red-500' : ''}
              />
              {errors.name && (
                <p className="text-red-500 text-sm mt-1">{errors.name}</p>
              )}
            </div>

            {/* Email */}
            <div>
              <label className="block text-sm font-medium mb-1">
                البريد الإلكتروني <span className="text-red-500">*</span>
              </label>
              <Input
                type="email"
                value={formData.email}
                onChange={(e) => handleChange('email', e.target.value)}
                placeholder="example@domain.com"
                className={errors.email ? 'border-red-500' : ''}
              />
              {errors.email && (
                <p className="text-red-500 text-sm mt-1">{errors.email}</p>
              )}
            </div>

            {/* Phone */}
            <div>
              <label className="block text-sm font-medium mb-1">
                رقم الهاتف
              </label>
              <Input
                type="tel"
                value={formData.phone}
                onChange={(e) => handleChange('phone', e.target.value)}
                placeholder="01012345678"
                className={errors.phone ? 'border-red-500' : ''}
              />
              {errors.phone && (
                <p className="text-red-500 text-sm mt-1">{errors.phone}</p>
              )}
            </div>

            {/* Role */}
            <div>
              <label className="block text-sm font-medium mb-1">
                الدور الوظيفي <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.role}
                onChange={(e) => handleChange('role', e.target.value)}
                className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                  errors.role ? 'border-red-500' : 'border-gray-300'
                }`}
              >
                <option value="">اختر الدور</option>
                <option value="admin">مدير نظام</option>
                <option value="manager">مدير</option>
                <option value="accountant">محاسب</option>
                <option value="store_user">مستخدم مخزن</option>
              </select>
              {errors.role && (
                <p className="text-red-500 text-sm mt-1">{errors.role}</p>
              )}
            </div>

            {/* Branch */}
            <div>
              <label className="block text-sm font-medium mb-1">
                الفرع
              </label>
              <select
                value={formData.branch_id}
                onChange={(e) => handleChange('branch_id', e.target.value)}
                disabled={loadingBranches}
                className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
                  errors.branch_id ? 'border-red-500' : 'border-gray-300'
                }`}
              >
                <option value="">اختر الفرع</option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name} ({branch.code})
                  </option>
                ))}
              </select>
              {errors.branch_id && (
                <p className="text-red-500 text-sm mt-1">{errors.branch_id}</p>
              )}
            </div>

            {/* Status */}
            <div>
              <label className="block text-sm font-medium mb-1">
                حالة المستخدم
              </label>
              <div className="flex items-center gap-4 mt-2">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="radio"
                    checked={formData.is_active}
                    onChange={() => handleChange('is_active', true)}
                    className="w-4 h-4"
                  />
                  <span>نشط</span>
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="radio"
                    checked={!formData.is_active}
                    onChange={() => handleChange('is_active', false)}
                    className="w-4 h-4"
                  />
                  <span>غير نشط</span>
                </label>
              </div>
            </div>
          </div>

          {/* Password Section */}
          <div className="border-t pt-4 mt-4">
            <h3 className="text-sm font-medium mb-3">
              {isEdit ? 'تغيير كلمة المرور (اتركها فارغة إذا لم ترد تغييرها)' : 'كلمة المرور'}
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {/* Password */}
              <div>
                <label className="block text-sm font-medium mb-1">
                  كلمة المرور {!isEdit && <span className="text-red-500">*</span>}
                </label>
                <Input
                  type="password"
                  value={formData.password}
                  onChange={(e) => handleChange('password', e.target.value)}
                  placeholder="أدخل كلمة المرور"
                  className={errors.password ? 'border-red-500' : ''}
                />
                {errors.password && (
                  <p className="text-red-500 text-sm mt-1">{errors.password}</p>
                )}
              </div>

              {/* Confirm Password */}
              <div>
                <label className="block text-sm font-medium mb-1">
                  تأكيد كلمة المرور {!isEdit && <span className="text-red-500">*</span>}
                </label>
                <Input
                  type="password"
                  value={formData.password_confirmation}
                  onChange={(e) => handleChange('password_confirmation', e.target.value)}
                  placeholder="أعد إدخال كلمة المرور"
                  className={errors.password_confirmation ? 'border-red-500' : ''}
                />
                {errors.password_confirmation && (
                  <p className="text-red-500 text-sm mt-1">{errors.password_confirmation}</p>
                )}
              </div>
            </div>
          </div>

          {/* Actions */}
          <div className="flex justify-end gap-2 pt-4 border-t">
            <Button
              type="button"
              variant="outline"
              onClick={() => onClose(false)}
              disabled={loading}
            >
              <X className="w-4 h-4 ml-2" />
              إلغاء
            </Button>
            <Button type="submit" disabled={loading}>
              {loading ? (
                <>
                  <Loader2 className="w-4 h-4 ml-2 animate-spin" />
                  جاري الحفظ...
                </>
              ) : (
                <>
                  <Save className="w-4 h-4 ml-2" />
                  {isEdit ? 'تحديث' : 'حفظ'}
                </>
              )}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  )
}
