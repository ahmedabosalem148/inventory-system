/**
 * Purchase Orders API Service
 * Handles all purchase order-related API calls
 */

import { apiClient } from './client'
import type {
  PurchaseOrder,
  CreatePurchaseOrderInput,
  PurchaseOrdersListParams,
  PaginatedResponse,
  ApiResponse,
  ReceiveGoodsInput,
  Supplier,
} from '@/types'

/**
 * Get paginated list of purchase orders
 */
export const getPurchaseOrders = async (
  params?: PurchaseOrdersListParams
): Promise<PaginatedResponse<PurchaseOrder>> => {
  const response = await apiClient.get<PaginatedResponse<PurchaseOrder>>('/purchase-orders', {
    params,
  })
  return response.data
}

/**
 * Get single purchase order by ID
 */
export const getPurchaseOrder = async (id: number): Promise<PurchaseOrder> => {
  const response = await apiClient.get<ApiResponse<PurchaseOrder>>(`/purchase-orders/${id}`)
  return response.data.data
}

/**
 * Create new purchase order
 */
export const createPurchaseOrder = async (
  data: CreatePurchaseOrderInput
): Promise<PurchaseOrder> => {
  const response = await apiClient.post<ApiResponse<PurchaseOrder>>('/purchase-orders', data)
  return response.data.data
}

/**
 * Update existing purchase order
 */
export const updatePurchaseOrder = async (
  id: number,
  data: Partial<CreatePurchaseOrderInput>
): Promise<PurchaseOrder> => {
  const response = await apiClient.put<ApiResponse<PurchaseOrder>>(
    `/purchase-orders/${id}`,
    data
  )
  return response.data.data
}

/**
 * Delete purchase order
 */
export const deletePurchaseOrder = async (id: number): Promise<void> => {
  await apiClient.delete(`/purchase-orders/${id}`)
}

/**
 * Approve purchase order
 */
export const approvePurchaseOrder = async (id: number): Promise<PurchaseOrder> => {
  const response = await apiClient.post<ApiResponse<PurchaseOrder>>(
    `/purchase-orders/${id}/approve`
  )
  return response.data.data
}

/**
 * Cancel purchase order
 */
export const cancelPurchaseOrder = async (id: number, reason?: string): Promise<PurchaseOrder> => {
  const response = await apiClient.post<ApiResponse<PurchaseOrder>>(
    `/purchase-orders/${id}/cancel`,
    { reason }
  )
  return response.data.data
}

/**
 * Receive goods (partial or full)
 */
export const receiveGoods = async (data: ReceiveGoodsInput): Promise<PurchaseOrder> => {
  const response = await apiClient.post<ApiResponse<PurchaseOrder>>(
    `/purchase-orders/${data.purchase_order_id}/receive`,
    data
  )
  return response.data.data
}

/**
 * Print purchase order (get PDF)
 */
export const printPurchaseOrder = async (id: number): Promise<Blob> => {
  const response = await apiClient.get(`/purchase-orders/${id}/print`, {
    responseType: 'blob',
  })
  return response.data
}

/**
 * Export purchase orders to Excel
 */
export const exportPurchaseOrders = async (params?: PurchaseOrdersListParams): Promise<Blob> => {
  const response = await apiClient.get('/purchase-orders/export', {
    params,
    responseType: 'blob',
  })
  return response.data
}

/**
 * Get purchase order statistics
 */
export const getPurchaseOrderStats = async (params?: {
  date_from?: string
  date_to?: string
  branch_id?: number
}): Promise<{
  total_orders: number
  total_amount: number
  received_amount: number
  pending_amount: number
  by_status: Record<string, number>
  by_receiving_status: Record<string, number>
}> => {
  const response = await apiClient.get('/purchase-orders/stats', { params })
  return response.data.data
}

/**
 * Get list of suppliers
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
 * Get single supplier
 */
export const getSupplier = async (id: number): Promise<Supplier> => {
  const response = await apiClient.get<ApiResponse<Supplier>>(`/suppliers/${id}`)
  return response.data.data
}

/**
 * Create supplier
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
