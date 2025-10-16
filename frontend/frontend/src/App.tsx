import { useState, useEffect, useRef } from 'react'
import { useAuth } from '@/features/auth/AuthContext'
import { LoginPage } from '@/features/auth/LoginPage'
import { AppLayout } from '@/components/layout'
import { ErrorBoundary } from '@/components/ErrorBoundary'
import { Spinner } from '@/components/ui/spinner'
import { ManagerDashboard } from '@/features/dashboard/ManagerDashboard'
import { AccountantDashboard } from '@/features/dashboard/AccountantDashboard'
import { StoreManagerDashboard } from '@/features/dashboard/StoreManagerDashboard'
import { ProductsPage } from '@/features/products/ProductsPage'
import { SalesPage } from '@/features/sales/SalesPage'
import { PurchasesPage } from '@/features/purchases/PurchasesPage'
import CustomersPage from '@/features/customers/CustomersPage'
import CustomerDetailsPage from '@/features/customers/CustomerDetailsPage'
import { SuppliersPage } from '@/features/suppliers/SuppliersPage'
import { InventoryPage } from '@/features/inventory/InventoryPage'
import { ReportsPage } from '@/features/reports/ReportsPage'
import { SettingsPage } from '@/features/settings/SettingsPage'
import ReturnVouchersPage from '@/features/returns/ReturnVouchersPage'
import ReturnVoucherDetailsPage from '@/features/returns/ReturnVoucherDetailsPage'
import IssueVoucherDetailsPage from '@/features/sales/IssueVoucherDetailsPage'
import PaymentsPage from '@/features/payments/PaymentsPage'
import ChequesPage from '@/features/payments/ChequesPage'
import { BranchesPage } from '@/features/branches/BranchesPage'

function App() {
  const { isAuthenticated, isLoading, user } = useAuth()
  const hasInitialized = useRef(false)
  const [currentPage, setCurrentPage] = useState(() => {
    // Initialize from URL hash on first render
    // Handle both #products and /login#products cases
    const fullHash = window.location.hash
    const hash = fullHash.slice(1) || 'dashboard'
    console.log('🎯 Initial page:', hash, '| Full URL:', window.location.href)
    
    // Fix URL if it has /login or other paths - we're using hash routing only
    if (window.location.pathname !== '/') {
      console.log('⚠️ Wrong pathname detected:', window.location.pathname, '- fixing...')
      const newUrl = window.location.origin + '/' + window.location.hash
      window.history.replaceState(null, '', newUrl)
    }
    
    return hash
  })

  // Listen to hash changes for simple routing
  // Must be called before any conditional returns (Rules of Hooks)
  useEffect(() => {
    const handleHashChange = () => {
      const hash = window.location.hash.slice(1) || 'dashboard'
      console.log('🔄 Hash changed to:', hash)
      setCurrentPage(hash)
    }

    // Set up listener only once
    if (!hasInitialized.current) {
      hasInitialized.current = true
      window.addEventListener('hashchange', handleHashChange)
      console.log('✅ Hash listener registered')
    }
    
    // Don't cleanup - we want the listener to stay forever
    // The listener will be cleaned up when the component unmounts (app closes)
  }, []) // Empty dependency array - only run once
  
  // Log whenever currentPage or auth state changes
  useEffect(() => {
    console.log('📄 Render:', {
      currentPage,
      isLoading,
      isAuthenticated,
      currentHash: window.location.hash,
      hashMismatch: currentPage !== window.location.hash.slice(1)
    })
    
    // IMPORTANT: Don't reset currentPage when auth state changes!
    // Keep the user on their current page
  }, [currentPage, isLoading, isAuthenticated])

  // Early returns after all hooks
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
    // Handle both string array and object array formats
    const firstRole = user?.roles?.[0]
    const userRole = typeof firstRole === 'string' 
      ? firstRole.toLowerCase() 
      : firstRole?.name?.toLowerCase()
    
    console.log('👤 User Role:', userRole, '| Full user:', user)
    
    switch (userRole) {
      case 'accounting':
      case 'accountant':
        console.log('📊 Rendering AccountantDashboard')
        return <AccountantDashboard />
      case 'store_user':
      case 'store-manager':
        console.log('📦 Rendering StoreManagerDashboard')
        return <StoreManagerDashboard />
      case 'manager':
      case 'super-admin':
        console.log('👔 Rendering ManagerDashboard')
        return <ManagerDashboard />
      default:
        console.log('⚠️ Unknown role, defaulting to ManagerDashboard')
        return <ManagerDashboard />
    }
  }

  // Render current page
  const renderPage = () => {
    // Handle customers/:id route
    if (currentPage.startsWith('customers/')) {
      return <CustomerDetailsPage />
    }

    // Handle return-vouchers/:id route
    if (currentPage.startsWith('return-vouchers/') && currentPage !== 'return-vouchers/new') {
      return <ReturnVoucherDetailsPage />
    }

    // Handle invoices/:id route (issue vouchers details)
    if (currentPage.startsWith('invoices/')) {
      return <IssueVoucherDetailsPage />
    }

    switch (currentPage) {
      case 'products':
        return <ProductsPage />
      case 'sales':
      case 'issue-vouchers': // فواتير الصرف = المبيعات
        return <SalesPage />
      case 'purchases':
        return <PurchasesPage />
      case 'customers':
        return <CustomersPage />
      case 'suppliers':
        return <SuppliersPage />
      case 'inventory':
        return <InventoryPage />
      case 'reports':
        return <ReportsPage />
      case 'settings':
        return <SettingsPage />
      case 'return-vouchers':
        return <ReturnVouchersPage />
      case 'payments':
        return <PaymentsPage />
      case 'cheques':
        return <ChequesPage />
      case 'branches':
        return <BranchesPage />
      case 'dashboard':
      default:
        return getDashboard()
    }
  }

  return (
    <ErrorBoundary>
      <AppLayout>{renderPage()}</AppLayout>
    </ErrorBoundary>
  )
}

export default App