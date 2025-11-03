import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import ProtectedRoute from './components/ProtectedRoute';
import LoginPage from './pages/Login/LoginPage';
import DashboardPage from './pages/Dashboard/DashboardPage';
import ProductsPage from './pages/Products/ProductsPage';
import BranchesPage from './pages/Branches/BranchesPage';
import InventoryCountsPage from './pages/InventoryCounts/InventoryCountsPage';
import IssueVouchersPage from './pages/IssueVouchers/IssueVouchersPage';
import CustomersPage from './pages/Customers/CustomersPage';
import CustomerProfilePage from './pages/Customers/CustomerProfilePage';
import ReturnVouchersPage from './pages/ReturnVouchers/ReturnVouchersPage';
import IssueVoucherDetailsPage from './pages/Vouchers/IssueVoucherDetailsPage';
import ReturnVoucherDetailsPage from './pages/Vouchers/ReturnVoucherDetailsPage';
import StockValuationReport from './pages/Reports/StockValuationReport';
import ReportsPage from './pages/Reports/ReportsPage';

function App() {
  return (
    <Router>
      <AuthProvider>
        <Routes>
          {/* Public Routes */}
          <Route path="/login" element={<LoginPage />} />
          
          {/* Protected Routes */}
          <Route 
            path="/dashboard" 
            element={
              <ProtectedRoute>
                <DashboardPage />
              </ProtectedRoute>
            } 
          />
          
          <Route 
            path="/products" 
            element={
              <ProtectedRoute>
                <ProductsPage />
              </ProtectedRoute>
            } 
          />
          
          <Route 
            path="/branches" 
            element={
              <ProtectedRoute>
                <BranchesPage />
              </ProtectedRoute>
            } 
          />
          
          <Route 
            path="/inventory-counts" 
            element={
              <ProtectedRoute>
                <InventoryCountsPage />
              </ProtectedRoute>
            } 
          />
          
          <Route 
            path="/issue-vouchers" 
            element={
              <ProtectedRoute>
                <IssueVouchersPage />
              </ProtectedRoute>
            } 
          />
          
          <Route 
            path="/customers" 
            element={
              <ProtectedRoute>
                <CustomersPage />
              </ProtectedRoute>
            } 
          />
          
          <Route 
            path="/customers/:id/profile" 
            element={
              <ProtectedRoute>
                <CustomerProfilePage />
              </ProtectedRoute>
            } 
          />

          <Route 
            path="/vouchers/issue/:id" 
            element={
              <ProtectedRoute>
                <IssueVoucherDetailsPage />
              </ProtectedRoute>
            } 
          />

          <Route 
            path="/vouchers/return/:id" 
            element={
              <ProtectedRoute>
                <ReturnVoucherDetailsPage />
              </ProtectedRoute>
            } 
          />
          
          <Route 
            path="/return-vouchers" 
            element={
              <ProtectedRoute>
                <ReturnVouchersPage />
              </ProtectedRoute>
            } 
          />

          <Route 
            path="/reports" 
            element={
              <ProtectedRoute>
                <ReportsPage />
              </ProtectedRoute>
            } 
          />

          <Route 
            path="/reports/stock-valuation" 
            element={
              <ProtectedRoute>
                <StockValuationReport />
              </ProtectedRoute>
            } 
          />
          
          {/* Default Redirect */}
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </AuthProvider>
    </Router>
  );
}

export default App;
