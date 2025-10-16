/**
 * Sales Invoices API Service
 * Handles all invoice-related API calls
 */

import { apiClient } from './client'
import type {
  SalesInvoice,
  CreateSalesInvoiceInput,
  InvoicesListParams,
  PaginatedResponse,
  ApiResponse,
  InvoicePayment,
} from '@/types'

/**
 * Get paginated list of sales invoices
 */
export const getInvoices = async (
  params?: InvoicesListParams
): Promise<PaginatedResponse<SalesInvoice>> => {
  const response = await apiClient.get<PaginatedResponse<SalesInvoice>>('/issue-vouchers', {
    params,
  })
  return response.data
}

/**
 * Get single invoice by ID
 */
export const getInvoice = async (id: number): Promise<SalesInvoice> => {
  const response = await apiClient.get<ApiResponse<SalesInvoice>>(`/issue-vouchers/${id}`)
  return response.data.data
}

/**
 * Create new sales invoice
 */
export const createInvoice = async (
  data: CreateSalesInvoiceInput
): Promise<SalesInvoice> => {
  const response = await apiClient.post<ApiResponse<SalesInvoice>>('/issue-vouchers', data)
  return response.data.data
}

/**
 * Update existing invoice
 */
export const updateInvoice = async (
  id: number,
  data: Partial<CreateSalesInvoiceInput>
): Promise<SalesInvoice> => {
  const response = await apiClient.put<ApiResponse<SalesInvoice>>(
    `/issue-vouchers/${id}`,
    data
  )
  return response.data.data
}

/**
 * Delete invoice
 */
export const deleteInvoice = async (id: number): Promise<void> => {
  await apiClient.delete(`/issue-vouchers/${id}`)
}

/**
 * Add payment to invoice
 */
export const addInvoicePayment = async (
  invoiceId: number,
  data: {
    amount: number
    payment_date: string
    payment_method: 'CASH' | 'CHEQUE' | 'BANK_TRANSFER'
    reference_number?: string
    notes?: string
  }
): Promise<InvoicePayment> => {
  const response = await apiClient.post<ApiResponse<InvoicePayment>>(
    `/issue-vouchers/${invoiceId}/payments`,
    data
  )
  return response.data.data
}

/**
 * Get invoice payments
 */
export const getInvoicePayments = async (invoiceId: number): Promise<InvoicePayment[]> => {
  const response = await apiClient.get<ApiResponse<InvoicePayment[]>>(
    `/issue-vouchers/${invoiceId}/payments`
  )
  return response.data.data
}

/**
 * Cancel invoice
 */
export const cancelInvoice = async (id: number, reason?: string): Promise<SalesInvoice> => {
  const response = await apiClient.post<ApiResponse<SalesInvoice>>(
    `/issue-vouchers/${id}/cancel`,
    { reason }
  )
  return response.data.data
}

/**
 * Print invoice (get PDF)
 */
export const printInvoice = async (id: number): Promise<Blob> => {
  const response = await apiClient.get(`/issue-vouchers/${id}/print`, {
    responseType: 'blob',
  })
  return response.data
}

/**
 * Export invoices to Excel
 */
export const exportInvoices = async (params?: InvoicesListParams): Promise<Blob> => {
  const response = await apiClient.get('/issue-vouchers/export', {
    params,
    responseType: 'blob',
  })
  return response.data
}

/**
 * Get invoice statistics
 */
export const getInvoiceStats = async (params?: {
  date_from?: string
  date_to?: string
  branch_id?: number
}): Promise<{
  total_invoices: number
  total_amount: number
  paid_amount: number
  pending_amount: number
  by_status: Record<string, number>
  by_payment_status: Record<string, number>
}> => {
  const response = await apiClient.get('/issue-vouchers-stats', { params })
  return response.data.data
}
