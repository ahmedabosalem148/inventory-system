import { useAuth } from '@/features/auth/AuthContext'
import { LoginPage } from '@/features/auth/LoginPage'
import { AppLayout } from '@/components/layout'
import { ErrorBoundary } from '@/components/ErrorBoundary'
import { Spinner } from '@/components/ui/spinner'
import { ManagerDashboard } from '@/features/dashboard/ManagerDashboard'
import { AccountantDashboard } from '@/features/dashboard/AccountantDashboard'
import { StoreManagerDashboard } from '@/features/dashboard/StoreManagerDashboard'

function App() {
  const { isAuthenticated, isLoading, user } = useAuth()

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <Spinner size="lg" color="primary" />
          <p className="mt-4 text-gray-600">جاري التحميل...</p>
        </div>
      </div>
    )
  }

  if (!isAuthenticated) {
    return <LoginPage />
  }

  // Determine dashboard based on user role
  const getDashboard = () => {
    const userRole = user?.roles?.[0]?.name?.toLowerCase()
    
    switch (userRole) {
      case 'accountant':
        return <AccountantDashboard />
      case 'store-manager':
        return <StoreManagerDashboard />
      case 'super-admin':
      default:
        return <ManagerDashboard />
    }
  }

  return (
    <ErrorBoundary>
      <AppLayout>{getDashboard()}</AppLayout>
    </ErrorBoundary>
  )
}

export default App