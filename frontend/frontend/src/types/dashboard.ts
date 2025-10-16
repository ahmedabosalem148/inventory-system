// Dashboard KPI Types
export interface KPICardData {
  label: string
  value: string | number
  change?: {
    value: string
    trend: 'up' | 'down' | 'neutral'
  }
  icon: React.ElementType
  color: 'blue' | 'green' | 'yellow' | 'purple' | 'red' | 'gray'
}

// Chart Data Types
export interface ChartDataPoint {
  name: string
  value: number
  [key: string]: string | number
}

// Table Row Types
export interface InvoiceRow {
  id: string
  customer: string
  amount: number
  date: string
  status: 'paid' | 'pending' | 'overdue'
}

export interface ProductStockRow {
  id: string
  name: string
  sku: string
  currentStock: number
  minStock: number
  status: 'low' | 'critical' | 'adequate'
}

export interface PaymentRow {
  id: string
  customer: string
  amount: number
  dueDate: string
  status: 'pending' | 'completed' | 'overdue'
}

export interface ChequeRow {
  id: string
  customer: string
  amount: number
  chequeNumber: string
  dueDate: string
  status: 'pending' | 'cleared' | 'bounced'
}

export interface StockMovementRow {
  id: string
  product: string
  type: 'in' | 'out'
  quantity: number
  date: string
  reference: string
}

// Dashboard Props
export interface DashboardProps {
  userRole: 'super-admin' | 'accountant' | 'store-manager'
}
