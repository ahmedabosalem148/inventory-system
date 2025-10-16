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
  role?: UserRole
  branch_id?: number | null
  branch?: Branch
  // Can be either string array or Role object array (Laravel API returns both formats)
  roles?: (Role | string)[]
  permissions?: (Permission | string)[]
  all_permissions?: (Permission | string)[]
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

export interface ProductCategory {
  id: number
  name: string
}

export interface Product {
  id: number
  name: string
  brand?: string
  description?: string
  sku: string
  unit: string
  pack_size: number
  min_stock_level: number
  price: number
  cost?: number
  category?: ProductCategory | string
  barcode?: string
  image_url?: string
  is_active?: boolean
  created_at: string
  updated_at: string
  inventory?: Inventory[]
  total_stock?: number
  low_stock?: boolean
}

export interface ProductFilters {
  search?: string
  brand?: string
  category?: string
  is_active?: boolean
  low_stock?: boolean
  branch_id?: number
}

export interface CreateProductInput {
  name: string
  brand?: string
  description?: string
  sku: string
  unit: string
  pack_size: number
  min_stock_level: number
  price: number
  cost?: number
  category?: string
  barcode?: string
  is_active?: boolean
}

export interface UpdateProductInput extends Partial<CreateProductInput> {
  id: number
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
// Sales Invoice (فاتورة مبيعات)
// ============================================================================

export type InvoiceStatus = 'DRAFT' | 'PENDING' | 'PAID' | 'PARTIALLY_PAID' | 'CANCELLED'
export type PaymentStatus = 'UNPAID' | 'PARTIALLY_PAID' | 'PAID'

export interface SalesInvoice {
  id: number
  voucher_number: string // Backend uses voucher_number instead of invoice_number
  invoice_number?: string // Alias for compatibility
  customer_id: number
  customer_name?: string // Backend has this field
  branch_id: number
  issue_date: string // Backend uses issue_date instead of invoice_date
  invoice_date?: string // Alias for compatibility
  voucher_type?: string // Backend field: type of voucher
  is_transfer?: boolean // Backend field: for branch transfers
  target_branch_id?: number // Backend field: target branch for transfers
  due_date?: string
  subtotal: number
  total_amount: number // Total before discount
  discount_type?: 'PERCENTAGE' | 'FIXED' // Backend field
  discount_value?: number // Backend field: can be percentage or fixed amount
  discount_percentage?: number // For compatibility
  discount_amount: number
  net_total: number // Backend field: final total after discount
  tax_percentage?: number // Frontend only
  tax_amount?: number // Frontend only
  paid_amount?: number // Frontend only
  remaining_amount?: number // Frontend only
  status: InvoiceStatus
  payment_status?: PaymentStatus // Frontend only
  approved_at?: string // Backend field
  approved_by?: number // Backend field
  notes?: string
  created_by: number
  customer?: Customer
  branch?: Branch
  target_branch?: Branch // Backend relation
  items?: SalesInvoiceItem[]
  payments?: InvoicePayment[]
  creator?: User
  approver?: User // Backend relation
  created_at: string
  updated_at: string
}

export interface SalesInvoiceItem {
  id: number
  issue_voucher_id: number // Backend uses issue_voucher_id
  sales_invoice_id?: number // Alias for compatibility
  product_id: number
  quantity: number
  unit_price: number
  total_price: number // Backend field: total before discount
  discount_type?: 'PERCENTAGE' | 'FIXED' // Backend field
  discount_value?: number // Backend field: can be percentage or fixed amount
  discount_percentage?: number // For compatibility
  discount_amount: number
  net_price: number // Backend field: final price after discount
  tax_percentage?: number // Frontend only
  tax_amount?: number // Frontend only
  total?: number // Alias for compatibility (maps to net_price)
  product?: Product
}

export interface InvoicePayment {
  id: number
  issue_voucher_id?: number // Backend relation through pivot
  sales_invoice_id?: number // Alias for compatibility
  voucher_id?: number // Backend pivot table field
  payment_id?: number // Backend pivot table field
  amount: number
  payment_date: string
  payment_method: PaymentMethod
  reference_number?: string
  notes?: string
  created_by: number
  creator?: User
  created_at: string
}

export interface CreateSalesInvoiceInput {
  customer_id: number
  customer_name?: string // Optional: can be stored if customer is new
  branch_id: number
  issue_date: string // Backend uses issue_date
  invoice_date?: string // Alias for compatibility
  voucher_type?: string // Optional: type of voucher
  is_transfer?: boolean // Optional: for branch transfers
  target_branch_id?: number // Optional: target branch for transfers
  due_date?: string
  discount_type?: 'PERCENTAGE' | 'FIXED' // Backend field
  discount_value?: number // Backend field: can be percentage or fixed amount
  discount_percentage?: number // For compatibility
  tax_percentage?: number
  notes?: string
  items: Array<{
    product_id: number
    quantity: number
    unit_price: number
    discount_percentage?: number
    tax_percentage?: number
  }>
}

export interface UpdateSalesInvoiceInput extends Partial<CreateSalesInvoiceInput> {
  id: number
}

export interface InvoicesListParams {
  page?: number
  per_page?: number
  search?: string
  customer_id?: number
  branch_id?: number
  status?: InvoiceStatus
  payment_status?: PaymentStatus
  date_from?: string
  date_to?: string
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
}

// ============================================================================
// Purchase Order (أمر شراء)
// ============================================================================

export type PurchaseOrderStatus = 'DRAFT' | 'PENDING' | 'APPROVED' | 'RECEIVED' | 'PARTIALLY_RECEIVED' | 'CANCELLED'
export type ReceivingStatus = 'NOT_RECEIVED' | 'PARTIALLY_RECEIVED' | 'FULLY_RECEIVED'

export interface Supplier {
  id: number
  name: string
  phone?: string
  email?: string
  address?: string
  tax_number?: string
  notes?: string
  created_at: string
  updated_at: string
  balance?: number // Calculated field
}

export interface PurchaseOrder {
  id: number
  order_number: string
  supplier_id: number
  branch_id: number
  order_date: string
  expected_delivery_date?: string
  received_date?: string
  subtotal: number
  discount_percentage: number
  discount_amount: number
  tax_percentage: number
  tax_amount: number
  total_amount: number
  paid_amount: number
  remaining_amount: number
  status: PurchaseOrderStatus
  receiving_status: ReceivingStatus
  payment_status: PaymentStatus
  notes?: string
  created_by: number
  approved_by?: number
  approved_at?: string
  supplier?: Supplier
  branch?: Branch
  items?: PurchaseOrderItem[]
  creator?: User
  approver?: User
  created_at: string
  updated_at: string
}

export interface PurchaseOrderItem {
  id: number
  purchase_order_id: number
  product_id: number
  quantity_ordered: number
  quantity_received: number
  unit_cost: number
  discount_percentage: number
  discount_amount: number
  tax_percentage: number
  tax_amount: number
  total: number
  product?: Product
}

export interface CreatePurchaseOrderInput {
  supplier_id: number
  branch_id: number
  order_date: string
  expected_delivery_date?: string
  discount_percentage?: number
  tax_percentage?: number
  notes?: string
  items: Array<{
    product_id: number
    quantity_ordered: number
    unit_cost: number
    discount_percentage?: number
    tax_percentage?: number
  }>
}

export interface UpdatePurchaseOrderInput extends Partial<CreatePurchaseOrderInput> {
  id: number
}

export interface PurchaseOrdersListParams {
  page?: number
  per_page?: number
  search?: string
  supplier_id?: number
  branch_id?: number
  status?: PurchaseOrderStatus
  receiving_status?: ReceivingStatus
  payment_status?: PaymentStatus
  date_from?: string
  date_to?: string
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
}

export interface ReceiveGoodsInput {
  purchase_order_id: number
  items: Array<{
    purchase_order_item_id: number
    quantity_received: number
  }>
  notes?: string
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
