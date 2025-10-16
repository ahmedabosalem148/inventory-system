import type { ReactNode } from 'react'
import { useAuth } from '@/features/auth/AuthContext'
import { Spinner } from '@/components/ui/spinner'

interface ProtectedRouteProps {
  children: ReactNode
  requiredPermission?: string
  requiredRole?: 'super-admin' | 'accountant' | 'store-manager'
}

export function ProtectedRoute({ 
  children, 
  requiredPermission,
  requiredRole 
}: ProtectedRouteProps) {
  const { user, isAuthenticated, isLoading } = useAuth()

  // Show loading spinner while checking auth
  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <Spinner size="lg" color="primary" />
          <p className="mt-4 text-gray-600">جاري التحقق من الصلاحيات...</p>
        </div>
      </div>
    )
  }

  // Redirect to login if not authenticated
  if (!isAuthenticated) {
    window.location.href = '/login'
    return null
  }

  // Check role if required
  if (requiredRole && user?.role !== requiredRole) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 p-4">
        <div className="max-w-md text-center">
          <div className="text-6xl mb-4">🚫</div>
          <h1 className="text-2xl font-bold text-gray-900 mb-2">
            ليس لديك صلاحية الوصول
          </h1>
          <p className="text-gray-600 mb-6">
            هذه الصفحة متاحة فقط لـ <span className="font-semibold">{requiredRole}</span>
          </p>
          <button
            onClick={() => window.history.back()}
            className="text-blue-600 hover:text-blue-700 font-medium"
          >
            ← العودة للخلف
          </button>
        </div>
      </div>
    )
  }

  // Check permission if required
  if (requiredPermission && !user?.permissions?.some(p => p.name === requiredPermission)) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 p-4">
        <div className="max-w-md text-center">
          <div className="text-6xl mb-4">🔒</div>
          <h1 className="text-2xl font-bold text-gray-900 mb-2">
            صلاحية غير كافية
          </h1>
          <p className="text-gray-600 mb-6">
            تحتاج إلى صلاحية <span className="font-mono bg-gray-100 px-2 py-1 rounded">{requiredPermission}</span>
          </p>
          <button
            onClick={() => window.history.back()}
            className="text-blue-600 hover:text-blue-700 font-medium"
          >
            ← العودة للخلف
          </button>
        </div>
      </div>
    )
  }

  // All checks passed, render children
  return <>{children}</>
}
