import { useState } from 'react'
import type { FormEvent } from 'react'
import { useAuth } from './AuthContext'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { showToast } from '@/components/ui/toast'
import { LogIn, Package } from 'lucide-react'

export function LoginPage() {
  const { login } = useAuth()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [errors, setErrors] = useState<{ email?: string; password?: string }>({})

  const autofill = (role: 'manager' | 'accountant' | 'store') => {
    const presets: Record<typeof role, string> = {
      manager: 'manager@inventory.local',
      accountant: 'accounting@inventory.local',
      store: 'store1@inventory.local',
    }

    setEmail(presets[role])
    setPassword('password')
    showToast.success('تم ملء البيانات تلقائيًا')
  }

  const validate = () => {
    const newErrors: { email?: string; password?: string } = {}

    if (!email) {
      newErrors.email = 'البريد الإلكتروني مطلوب'
    } else if (!/\S+@\S+\.\S+/.test(email)) {
      newErrors.email = 'البريد الإلكتروني غير صحيح'
    }

    if (!password) {
      newErrors.password = 'كلمة المرور مطلوبة'
    } else if (password.length < 6) {
      newErrors.password = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل'
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault()

    if (!validate()) {
      return
    }

    setLoading(true)
    setErrors({})

    try {
      await login(email, password)
      showToast.success('تم تسجيل الدخول بنجاح! 🎉')
      // User state will be updated automatically, no need for redirect
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'فشل تسجيل الدخول'
      showToast.error(message)
      setErrors({ email: ' ', password: message })
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50 p-4">
      <div className="w-full max-w-md">
        {/* Logo & Title */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl mb-4">
            <Package className="w-8 h-8 text-white" />
          </div>
          <h1 className="text-3xl font-bold text-gray-900 mb-2">نظام إدارة المخزون</h1>
          <p className="text-gray-600">قم بتسجيل الدخول للمتابعة</p>
        </div>

        {/* Login Card */}
        <Card className="shadow-xl border-0">
          <CardHeader className="space-y-1 pb-4">
            <CardTitle className="text-2xl text-center">تسجيل الدخول</CardTitle>
            <CardDescription className="text-center">
              أدخل بياناتك للوصول إلى حسابك
            </CardDescription>
          </CardHeader>

          <CardContent>
            {/* Autofill quick actions */}
            <div className="flex flex-wrap gap-2 justify-center mb-4">
              <Button type="button" variant="secondary" size="sm" onClick={() => autofill('manager')} disabled={loading}>
                ملء تلقائي (مدير)
              </Button>
              <Button type="button" variant="secondary" size="sm" onClick={() => autofill('accountant')} disabled={loading}>
                ملء تلقائي (محاسب)
              </Button>
              <Button type="button" variant="secondary" size="sm" onClick={() => autofill('store')} disabled={loading}>
                ملء تلقائي (مخزن)
              </Button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
              {/* Email Field */}
              <div className="space-y-2">
                <label htmlFor="email" className="text-sm font-medium text-gray-700">
                  البريد الإلكتروني
                </label>
                <Input
                  id="email"
                  type="email"
                  placeholder="example@company.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  error={errors.email}
                  disabled={loading}
                  autoFocus
                />
              </div>

              {/* Password Field */}
              <div className="space-y-2">
                <label htmlFor="password" className="text-sm font-medium text-gray-700">
                  كلمة المرور
                </label>
                <Input
                  id="password"
                  type="password"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  error={errors.password}
                  disabled={loading}
                />
              </div>

              {/* Remember Me & Forgot Password */}
              <div className="flex items-center justify-between">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  />
                  <span className="text-sm text-gray-600">تذكرني</span>
                </label>
                <button
                  type="button"
                  className="text-sm text-blue-600 hover:text-blue-700 font-medium"
                  disabled={loading}
                >
                  نسيت كلمة المرور؟
                </button>
              </div>

              {/* Submit Button */}
              <Button
                type="submit"
                variant="default"
                size="lg"
                className="w-full"
                loading={loading}
                disabled={loading}
              >
                <LogIn className="w-4 h-4 ml-2" />
                {loading ? 'جاري تسجيل الدخول...' : 'تسجيل الدخول'}
              </Button>
            </form>

            {/* Demo Credentials */}
            <div className="mt-6 p-4 bg-blue-50 rounded-lg">
              <p className="text-xs font-semibold text-blue-900 mb-2">✨ حسابات تجريبية:</p>
              <div className="space-y-1 text-xs text-blue-800">
                <p>👨‍💼 مدير: <span className="font-mono bg-white px-2 py-0.5 rounded">manager@inventory.local</span></p>
                <p>💰 محاسب: <span className="font-mono bg-white px-2 py-0.5 rounded">accounting@inventory.local</span></p>
                <p>📦 مخزن: <span className="font-mono bg-white px-2 py-0.5 rounded">store1@inventory.local</span></p>
                <p className="text-xs text-blue-600 mt-2">🔑 كلمة المرور لكل الحسابات: <span className="font-mono font-bold">password</span></p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Footer */}
        <p className="text-center text-sm text-gray-500 mt-6">
          © 2025 نظام إدارة المخزون. جميع الحقوق محفوظة.
        </p>
      </div>
    </div>
  )
}
