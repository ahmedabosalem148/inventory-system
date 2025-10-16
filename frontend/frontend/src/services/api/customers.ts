/**
 * Customers API Service
 * Handles all customer-related API calls
 */

import { apiClient } from './client'
import type {
  Customer,
  CustomerLedger,
  CreateLedgerEntryInput,
  PaginatedResponse,
  ApiResponse,
} from '@/types'

/**
 * Get paginated list of customers
 */
export const getCustomers = async (params?: {
  page?: number
  per_page?: number
  search?: string
}): Promise<PaginatedResponse<Customer>> => {
  const response = await apiClient.get<PaginatedResponse<Customer>>('/customers', {
    params,
  })
  return response.data
}

/**
 * Get single customer by ID
 */
export const getCustomer = async (id: number): Promise<Customer> => {
  const response = await apiClient.get<ApiResponse<Customer>>(`/customers/${id}`)
  return response.data.data
}

/**
 * Create new customer
 */
export const createCustomer = async (data: {
  name: string
  phone?: string
  address?: string
  credit_limit?: number
  notes?: string
}): Promise<Customer> => {
  const response = await apiClient.post<ApiResponse<Customer>>('/customers', data)
  return response.data.data
}

/**
 * Update customer
 */
export const updateCustomer = async (
  id: number,
  data: Partial<{
    name: string
    phone?: string
    address?: string
    credit_limit?: number
    notes?: string
  }>
): Promise<Customer> => {
  const response = await apiClient.put<ApiResponse<Customer>>(`/customers/${id}`, data)
  return response.data.data
}

/**
 * Delete customer
 */
export const deleteCustomer = async (id: number): Promise<void> => {
  await apiClient.delete(`/customers/${id}`)
}

/**
 * Get customer ledger (account statement)
 */
export const getCustomerLedger = async (
  customerId: number,
  params?: {
    date_from?: string
    date_to?: string
    page?: number
    per_page?: number
  }
): Promise<PaginatedResponse<CustomerLedger>> => {
  const response = await apiClient.get<PaginatedResponse<CustomerLedger>>(
    `/customers/${customerId}/ledger`,
    { params }
  )
  return response.data
}

/**
 * Create ledger entry (manual debit/credit)
 */
export const createLedgerEntry = async (
  data: CreateLedgerEntryInput
): Promise<CustomerLedger> => {
  const response = await apiClient.post<ApiResponse<CustomerLedger>>(
    `/customers/${data.customer_id}/ledger`,
    data
  )
  return response.data.data
}

/**
 * Get customer balance
 */
export const getCustomerBalance = async (customerId: number): Promise<number> => {
  const response = await apiClient.get<ApiResponse<{ balance: number }>>(
    `/customers/${customerId}/balance`
  )
  return response.data.data.balance
}

/**
 * Get customer statement (summary)
 */
export const getCustomerStatement = async (
  customerId: number,
  params?: {
    date_from?: string
    date_to?: string
  }
): Promise<{
  customer: Customer
  opening_balance: number
  total_debits: number
  total_credits: number
  closing_balance: number
  entries: CustomerLedger[]
}> => {
  const response = await apiClient.get(`/customers/${customerId}/statement`, {
    params,
  })
  return response.data.data
}

/**
 * Export customer statement to PDF
 */
export const exportCustomerStatement = async (
  customerId: number,
  params?: {
    date_from?: string
    date_to?: string
  }
): Promise<Blob> => {
  const response = await apiClient.get(`/customers/${customerId}/statement/export`, {
    params,
    responseType: 'blob',
  })
  return response.data
}

/**
 * Get customers with outstanding balances
 */
export const getCustomersWithBalance = async (): Promise<Customer[]> => {
  const response = await apiClient.get<ApiResponse<Customer[]>>('/customers/with-balance')
  return response.data.data
}
