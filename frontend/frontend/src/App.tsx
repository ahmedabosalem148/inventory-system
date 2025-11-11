import { useState, useEffect, useRef } from 'react'
import { useAuth } from '@/features/auth/AuthContext'
import { LoginPage } from '@/features/auth/LoginPage'
import { AppLayout } from '@/components/layout'
import { ErrorBoundary } from '@/components/ErrorBoundary'
import { Spinner } from '@/components/ui/spinner'
import { KeyboardShortcuts } from '@/components/KeyboardShortcuts'
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
import { 
  ReportsPage, 
  StockSummaryReport, 
  LowStockReport, 
  ProductMovementsReport,
  ProductMovementReport,
  CustomerBalancesReport,
  CustomerStatementReport,
  CustomerAgingReport,
  SalesSummaryReport,
  StockValuationReport
} from '@/features/reports'
import { SettingsPage } from '@/features/settings/SettingsPage'
import { UsersPage } from '@/features/users'
import { ActivityLogPage } from '@/features/activity/ActivityLogPage'
import { ProfilePage } from '@/features/profile/ProfilePage'
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
    
    // Fix URL if it has /login or other paths - we're using hash routing only
    if (window.location.pathname !== '/') {
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
      setCurrentPage(hash)
    }

    // Set up listener only once
    if (!hasInitialized.current) {
      hasInitialized.current = true
      window.addEventListener('hashchange', handleHashChange)
    }
    
    // Don't cleanup - we want the listener to stay forever
    // The listener will be cleaned up when the component unmounts (app closes)
  }, []) // Empty dependency array - only run once
  
  // Log whenever currentPage or auth state changes
  useEffect(() => {
    
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
    
    switch (userRole) {
      case 'accounting':
      case 'accountant':
        return <AccountantDashboard />
      case 'store_user':
      case 'store-manager':
        return <StoreManagerDashboard />
      case 'manager':
      case 'super-admin':
        return <ManagerDashboard />
      default:
        return <ManagerDashboard />
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

    // Handle reports routes
    if (currentPage.startsWith('reports/')) {
      const reportType = currentPage.split('/')[1]
      switch (reportType) {
        case 'stock-summary':
          return <StockSummaryReport />
        case 'low-stock':
          return <LowStockReport />
        case 'product-movements':
          return <ProductMovementsReport />
        case 'product-movement':
          return <ProductMovementReport />
        case 'customer-balances':
          return <CustomerBalancesReport />
        case 'customer-aging':
          return <CustomerAgingReport />
        case 'customer-statement':
          return <CustomerStatementReport />
        case 'sales-summary':
          return <SalesSummaryReport />
        case 'stock-valuation':
          return <StockValuationReport />
        default:
          return <ReportsPage />
      }
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
      case 'users':
        return <UsersPage />
      case 'activity-logs':
        return <ActivityLogPage />
      case 'profile':
        return <ProfilePage />
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
      <AppLayout>
        {renderPage()}
        <KeyboardShortcuts />
      </AppLayout>
    </ErrorBoundary>
  )
}

export default App