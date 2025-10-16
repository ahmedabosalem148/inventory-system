/**
 * Suppliers API Service
 * Handles all supplier-related API calls including ledger management
 */

import { apiClient } from './client'
import type {
  Supplier,
  PaginatedResponse,
  ApiResponse,
} from '@/types'

/**
 * Get paginated list of suppliers
 */
export const getSuppliers = async (params?: {
  page?: number
  per_page?: number
  search?: string
}): Promise<PaginatedResponse<Supplier>> => {
  const response = await apiClient.get<PaginatedResponse<Supplier>>('/suppliers', {
    params,
  })
  return response.data
}

/**
 * Get single supplier by ID
 */
export const getSupplier = async (id: number): Promise<Supplier> => {
  const response = await apiClient.get<ApiResponse<Supplier>>(`/suppliers/${id}`)
  return response.data.data
}

/**
 * Create new supplier
 */
export const createSupplier = async (data: {
  name: string
  phone?: string
  email?: string
  address?: string
  tax_number?: string
  notes?: string
}): Promise<Supplier> => {
  const response = await apiClient.post<ApiResponse<Supplier>>('/suppliers', data)
  return response.data.data
}

/**
 * Update supplier
 */
export const updateSupplier = async (
  id: number,
  data: Partial<{
    name: string
    phone?: string
    email?: string
    address?: string
    tax_number?: string
    notes?: string
  }>
): Promise<Supplier> => {
  const response = await apiClient.put<ApiResponse<Supplier>>(`/suppliers/${id}`, data)
  return response.data.data
}

/**
 * Delete supplier
 */
export const deleteSupplier = async (id: number): Promise<void> => {
  await apiClient.delete(`/suppliers/${id}`)
}

/**
 * Get supplier balance
 */
export const getSupplierBalance = async (supplierId: number): Promise<number> => {
  const response = await apiClient.get<ApiResponse<{ balance: number }>>(
    `/suppliers/${supplierId}/balance`
  )
  return response.data.data.balance
}

/**
 * Get suppliers with outstanding balances
 */
export const getSuppliersWithBalance = async (): Promise<Supplier[]> => {
  const response = await apiClient.get<ApiResponse<Supplier[]>>('/suppliers/with-balance')
  return response.data.data
}
