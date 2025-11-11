/**
 * Password Change Dialog
 * Allow users to change their password with strength indicator
 */

import { useState } from 'react'
import { Lock, Eye, EyeOff, CheckCircle, XCircle, Loader2 } from 'lucide-react'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { toast } from 'react-hot-toast'
import apiClient from '@/services/api/client'

interface PasswordChangeDialogProps {
  isOpen: boolean
  onClose: () => void
  userId?: number // If provided, admin is resetting another user's password
  userName?: string
}

interface PasswordRequirement {
  label: string
  test: (password: string) => boolean
}

const passwordRequirements: PasswordRequirement[] = [
  { label: 'على الأقل 8 أحرف', test: (pwd) => pwd.length >= 8 },
  { label: 'يحتوي على حرف كبير', test: (pwd) => /[A-Z]/.test(pwd) },
  { label: 'يحتوي على حرف صغير', test: (pwd) => /[a-z]/.test(pwd) },
  { label: 'يحتوي على رقم', test: (pwd) => /\d/.test(pwd) },
  { label: 'يحتوي على رمز خاص', test: (pwd) => /[!@#$%^&*(),.?":{}|<>]/.test(pwd) },
]

export function PasswordChangeDialog({
  isOpen,
  onClose,
  userId,
  userName,
}: PasswordChangeDialogProps) {
  const [currentPassword, setCurrentPassword] = useState('')
  const [newPassword, setNewPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [showCurrentPassword, setShowCurrentPassword] = useState(false)
  const [showNewPassword, setShowNewPassword] = useState(false)
  const [showConfirmPassword, setShowConfirmPassword] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)

  const isAdminReset = !!userId

  const getPasswordStrength = (password: string): number => {
    const passed = passwordRequirements.filter((req) => req.test(password)).length
    return (passed / passwordRequirements.length) * 100
  }

  const getStrengthColor = (strength: number): string => {
    if (strength < 40) return 'bg-red-500'
    if (strength < 70) return 'bg-yellow-500'
    return 'bg-green-500'
  }

  const getStrengthLabel = (strength: number): string => {
    if (strength < 40) return 'ضعيفة'
    if (strength < 70) return 'متوسطة'
    return 'قوية'
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    // Validation
    if (!isAdminReset && !currentPassword) {
      toast.error('الرجاء إدخال كلمة المرور الحالية')
      return
    }

    if (!newPassword) {
      toast.error('الرجاء إدخال كلمة المرور الجديدة')
      return
    }

    if (newPassword.length < 8) {
      toast.error('كلمة المرور يجب أن تكون 8 أحرف على الأقل')
      return
    }

    if (newPassword !== confirmPassword) {
      toast.error('كلمة المرور الجديدة وتأكيد كلمة المرور غير متطابقين')
      return
    }

    const passwordStrength = getPasswordStrength(newPassword)
    if (passwordStrength < 40) {
      toast.error('كلمة المرور ضعيفة جداً، الرجاء اختيار كلمة مرور أقوى')
      return
    }

    try {
      setIsSubmitting(true)

      const endpoint = isAdminReset
        ? `/users/${userId}/change-password`
        : '/profile/change-password'

      const payload = isAdminReset
        ? {
            password: newPassword,
            password_confirmation: confirmPassword,
          }
        : {
            current_password: currentPassword,
            password: newPassword,
            password_confirmation: confirmPassword,
          }

      await apiClient.post(endpoint, payload)

      toast.success(
        isAdminReset
          ? `تم تغيير كلمة مرور ${userName} بنجاح`
          : 'تم تغيير كلمة المرور بنجاح'
      )

      handleClose()
    } catch (error: any) {
      console.error('Error changing password:', error)
      const message =
        error.response?.data?.message || 'فشل في تغيير كلمة المرور'
      toast.error(message)
    } finally {
      setIsSubmitting(false)
    }
  }

  const handleClose = () => {
    setCurrentPassword('')
    setNewPassword('')
    setConfirmPassword('')
    setShowCurrentPassword(false)
    setShowNewPassword(false)
    setShowConfirmPassword(false)
    onClose()
  }

  const passwordStrength = getPasswordStrength(newPassword)

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Lock className="h-5 w-5 text-blue-600" />
            {isAdminReset ? `تغيير كلمة مرور ${userName}` : 'تغيير كلمة المرور'}
          </DialogTitle>
          <DialogDescription>
            {isAdminReset
              ? 'سيتم تغيير كلمة مرور هذا المستخدم'
              : 'الرجاء إدخال كلمة المرور الحالية والجديدة'}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit}>
          <div className="space-y-4 py-4">
            {/* Current Password (only for self-change) */}
            {!isAdminReset && (
              <div className="space-y-2">
                <Label htmlFor="currentPassword">كلمة المرور الحالية *</Label>
                <div className="relative">
                  <Input
                    id="currentPassword"
                    type={showCurrentPassword ? 'text' : 'password'}
                    value={currentPassword}
                    onChange={(e) => setCurrentPassword(e.target.value)}
                    placeholder="أدخل كلمة المرور الحالية"
                    className="pr-10"
                  />
                  <button
                    type="button"
                    onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                    className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    {showCurrentPassword ? (
                      <EyeOff className="h-4 w-4" />
                    ) : (
                      <Eye className="h-4 w-4" />
                    )}
                  </button>
                </div>
              </div>
            )}

            {/* New Password */}
            <div className="space-y-2">
              <Label htmlFor="newPassword">كلمة المرور الجديدة *</Label>
              <div className="relative">
                <Input
                  id="newPassword"
                  type={showNewPassword ? 'text' : 'password'}
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  placeholder="أدخل كلمة المرور الجديدة"
                  className="pr-10"
                />
                <button
                  type="button"
                  onClick={() => setShowNewPassword(!showNewPassword)}
                  className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                  {showNewPassword ? (
                    <EyeOff className="h-4 w-4" />
                  ) : (
                    <Eye className="h-4 w-4" />
                  )}
                </button>
              </div>

              {/* Password Strength Indicator */}
              {newPassword && (
                <div className="space-y-2">
                  <div className="flex items-center justify-between text-sm">
                    <span className="text-gray-600">قوة كلمة المرور:</span>
                    <span
                      className={`font-medium ${
                        passwordStrength < 40
                          ? 'text-red-600'
                          : passwordStrength < 70
                          ? 'text-yellow-600'
                          : 'text-green-600'
                      }`}
                    >
                      {getStrengthLabel(passwordStrength)}
                    </span>
                  </div>
                  <div className="w-full bg-gray-200 rounded-full h-2">
                    <div
                      className={`h-2 rounded-full transition-all duration-300 ${getStrengthColor(
                        passwordStrength
                      )}`}
                      style={{ width: `${passwordStrength}%` }}
                    />
                  </div>

                  {/* Requirements Checklist */}
                  <div className="space-y-1 mt-3">
                    {passwordRequirements.map((req, index) => {
                      const isPassed = req.test(newPassword)
                      return (
                        <div
                          key={index}
                          className="flex items-center gap-2 text-sm"
                        >
                          {isPassed ? (
                            <CheckCircle className="h-4 w-4 text-green-600" />
                          ) : (
                            <XCircle className="h-4 w-4 text-gray-300" />
                          )}
                          <span
                            className={
                              isPassed ? 'text-green-600' : 'text-gray-500'
                            }
                          >
                            {req.label}
                          </span>
                        </div>
                      )
                    })}
                  </div>
                </div>
              )}
            </div>

            {/* Confirm Password */}
            <div className="space-y-2">
              <Label htmlFor="confirmPassword">تأكيد كلمة المرور *</Label>
              <div className="relative">
                <Input
                  id="confirmPassword"
                  type={showConfirmPassword ? 'text' : 'password'}
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  placeholder="أعد إدخال كلمة المرور الجديدة"
                  className="pr-10"
                />
                <button
                  type="button"
                  onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                  className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                  {showConfirmPassword ? (
                    <EyeOff className="h-4 w-4" />
                  ) : (
                    <Eye className="h-4 w-4" />
                  )}
                </button>
              </div>
              {confirmPassword && newPassword !== confirmPassword && (
                <p className="text-sm text-red-600">
                  كلمتا المرور غير متطابقتين
                </p>
              )}
            </div>
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={handleClose}
              disabled={isSubmitting}
            >
              إلغاء
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? (
                <>
                  <Loader2 className="ml-2 h-4 w-4 animate-spin" />
                  جاري الحفظ...
                </>
              ) : (
                'تغيير كلمة المرور'
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
