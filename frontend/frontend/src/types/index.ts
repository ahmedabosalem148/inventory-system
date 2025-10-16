/**
 * Core TypeScript types and interfaces
 * Based on Laravel backend API structure
 */

// ============================================================================
// User & Authentication
// ============================================================================

export type UserRole = 'super-admin' | 'accountant' | 'store-manager'

export interface Role {
  id: number
  name: string
  guard_name: string
  created_at: string
  updated_at: string
}

export interface Permission {
  id: number
  name: string
  guard_name: string
  created_at: string
  updated_at: string
}

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  branch_id: number | null
  branch?: Branch
  roles?: Role[]
  permissions?: Permission[]
  all_permissions?: Permission[]
  created_at: string
  updated_at: string
}

export interface AuthResponse {
  access_token: string
  token_type: string
  expires_in: number
  user: User
}

export interface LoginCredentials {
  email: string
  password: string
}

// ============================================================================
// Branch
// ============================================================================

export interface Branch {
  id: number
  name: string
  location: string
  phone?: string
  created_at: string
  updated_at: string
}

// ============================================================================
// Product & Inventory
// ============================================================================

export interface Product {
  id: number
  name: string
  description?: string
  sku: string
  unit: string
  pack_size: number
  min_stock_level: number
  price: number
  created_at: string
  updated_at: string
  inventory?: Inventory[]
}

export interface Inventory {
  id: number
  product_id: number
  branch_id: number
  quantity: number
  product?: Product
  branch?: Branch
  created_at: string
  updated_at: string
}

export interface InventoryMovement {
  id: number
  product_id: number
  branch_id: number
  type: 'IN' | 'OUT' | 'TRANSFER' | 'ADJUSTMENT'
  quantity: number
  reference_type?: string
  reference_id?: number
  notes?: string
  created_by: number
  product?: Product
  branch?: Branch
  user?: User
  created_at: string
}

// ============================================================================
// Issue Voucher (إذن صرف)
// ============================================================================

export type VoucherStatus = 'DRAFT' | 'PENDING' | 'APPROVED' | 'REJECTED'

export interface IssueVoucher {
  id: number
  voucher_number: string
  customer_id: number
  branch_id: number
  total_amount: number
  discount_percentage: number
  discount_amount: number
  net_amount: number
  status: VoucherStatus
  notes?: string
  created_by: number
  approved_by?: number
  approved_at?: string
  rejection_reason?: string
  customer?: Customer
  branch?: Branch
  items?: IssueVoucherItem[]
  creator?: User
  approver?: User
  created_at: string
  updated_at: string
}

export interface IssueVoucherItem {
  id: number
  issue_voucher_id: number
  product_id: number
  quantity: number
  pack_quantity: number
  unit_quantity: number
  price: number
  total: number
  product?: Product
}

export interface CreateIssueVoucherInput {
  customer_id: number
  branch_id: number
  discount_percentage?: number
  notes?: string
  items: Array<{
    product_id: number
    quantity: number
    pack_quantity?: number
    unit_quantity?: number
    price: number
  }>
}

// ============================================================================
// Return Voucher (إذن إرجاع)
// ============================================================================

export interface ReturnVoucher {
  id: number
  voucher_number: string
  issue_voucher_id?: number
  customer_id: number
  branch_id: number
  total_amount: number
  status: VoucherStatus
  reason?: string
  notes?: string
  created_by: number
  approved_by?: number
  approved_at?: string
  rejection_reason?: string
  customer?: Customer
  branch?: Branch
  issue_voucher?: IssueVoucher
  items?: ReturnVoucherItem[]
  creator?: User
  approver?: User
  created_at: string
  updated_at: string
}

export interface ReturnVoucherItem {
  id: number
  return_voucher_id: number
  product_id: number
  quantity: number
  pack_quantity: number
  unit_quantity: number
  price: number
  total: number
  product?: Product
}

export interface CreateReturnVoucherInput {
  customer_id: number
  branch_id: number
  issue_voucher_id?: number
  reason?: string
  notes?: string
  items: Array<{
    product_id: number
    quantity: number
    pack_quantity?: number
    unit_quantity?: number
    price: number
  }>
}

// ============================================================================
// Customer & Ledger
// ============================================================================

export interface Customer {
  id: number
  name: string
  phone?: string
  address?: string
  credit_limit?: number
  notes?: string
  created_at: string
  updated_at: string
  balance?: number // Calculated field
}

export interface CustomerLedger {
  id: number
  customer_id: number
  entry_date: string
  type: 'DEBIT' | 'CREDIT'
  amount: number
  description: string
  reference_type?: string
  reference_id?: number
  balance_after: number
  created_by: number
  customer?: Customer
  user?: User
  created_at: string
}

export interface CreateLedgerEntryInput {
  customer_id: number
  entry_date: string
  type: 'DEBIT' | 'CREDIT'
  amount: number
  description: string
}

// ============================================================================
// Payment & Cheque
// ============================================================================

export type PaymentMethod = 'CASH' | 'CHEQUE' | 'BANK_TRANSFER'
export type ChequeStatus = 'PENDING' | 'COLLECTED' | 'BOUNCED' | 'CANCELLED'

export interface Payment {
  id: number
  customer_id: number
  amount: number
  payment_date: string
  payment_method: PaymentMethod
  notes?: string
  created_by: number
  customer?: Customer
  cheque?: Cheque
  user?: User
  created_at: string
  updated_at: string
}

export interface Cheque {
  id: number
  payment_id: number
  cheque_number: string
  bank_name: string
  due_date: string
  amount: number
  status: ChequeStatus
  collected_at?: string
  bounced_at?: string
  bounce_reason?: string
  payment?: Payment
  created_at: string
  updated_at: string
}

export interface CreatePaymentInput {
  customer_id: number
  amount: number
  payment_date: string
  payment_method: PaymentMethod
  notes?: string
  cheque?: {
    cheque_number: string
    bank_name: string
    due_date: string
  }
}

// ============================================================================
// API Response Types
// ============================================================================

export interface ApiResponse<T> {
  data: T
  message?: string
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}

// ============================================================================
// UI State Types
// ============================================================================

export interface FilterState {
  search?: string
  branch_id?: number
  status?: string
  date_from?: string
  date_to?: string
  customer_id?: number
  [key: string]: string | number | undefined
}

export interface SortState {
  field: string
  direction: 'asc' | 'desc'
}

export interface TableState {
  page: number
  per_page: number
  filters: FilterState
  sort?: SortState
}

// ============================================================================
// Dashboard & Analytics
// ============================================================================

export interface DashboardStats {
  total_products: number
  total_customers: number
  pending_vouchers: number
  today_vouchers: number
  low_stock_count: number
  total_inventory_value: number
  pending_cheques: number
  today_sales: number
}

export interface SalesChartData {
  date: string
  amount: number
  count: number
}

export interface InventoryChartData {
  branch_name: string
  total_value: number
  product_count: number
}

export interface LowStockItem {
  product: Product
  branch: Branch
  current_quantity: number
  min_level: number
  shortage: number
}

// ============================================================================
// Form Types
// ============================================================================

export interface VoucherItemForm {
  id: string // client-side only
  product_id: number
  product?: Product
  pack_quantity: number
  unit_quantity: number
  quantity: number // calculated
  price: number
  total: number // calculated
}

export interface VoucherFormData {
  customer_id: number
  branch_id: number
  discount_percentage: number
  notes: string
  items: VoucherItemForm[]
}
