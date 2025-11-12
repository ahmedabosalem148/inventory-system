/**
 * Inventory API Service
 * Handles all inventory-related API calls
 */

import { apiClient } from './client'
import type {
  Product,
  PaginatedResponse,
  ApiResponse,
} from '@/types'

/**
 * Get inventory/stock levels
 */
export const getInventory = async (params?: {
  page?: number
  per_page?: number
  search?: string
  warehouse_id?: number
  low_stock?: boolean
}): Promise<PaginatedResponse<Product>> => {
  const response = await apiClient.get<PaginatedResponse<Product>>('/products', {
    params,
  })
  return response.data
}

/**
 * Get stock alerts (low stock items)
 */
export const getStockAlerts = async (): Promise<Product[]> => {
  const response = await apiClient.get<ApiResponse<Product[]>>('/inventory-movements/reports/low-stock')
  return response.data.data
}

/**
 * Create stock adjustment
 */
export const createStockAdjustment = async (data: {
  product_id: number
  warehouse_id: number
  quantity: number
  type: 'increase' | 'decrease'
  reason: string
  notes?: string
}): Promise<any> => {
  const response = await apiClient.post('/inventory-movements/adjust', data)
  return response.data.data
}

/**
 * Get stock adjustment history
 */
export const getStockAdjustments = async (params?: {
  page?: number
  per_page?: number
  product_id?: number
  warehouse_id?: number
  date_from?: string
  date_to?: string
}): Promise<PaginatedResponse<any>> => {
  const response = await apiClient.get('/inventory-movements', { params })
  return response.data
}

/**
 * Create stock transfer between warehouses
 */
export const createStockTransfer = async (data: {
  product_id: number
  from_warehouse_id: number
  to_warehouse_id: number
  quantity: number
  notes?: string
}): Promise<any> => {
  const response = await apiClient.post('/inventory-movements/transfer', data)
  return response.data.data
}

/**
 * Get stock transfer history
 */
export const getStockTransfers = async (params?: {
  page?: number
  per_page?: number
  product_id?: number
  date_from?: string
  date_to?: string
}): Promise<PaginatedResponse<any>> => {
  const response = await apiClient.get('/inventory-movements', { params })
  return response.data
}

/**
 * Get inventory valuation
 */
export const getInventoryValuation = async (warehouse_id?: number): Promise<{
  total_value: number
  total_items: number
  total_quantity: number
}> => {
  const response = await apiClient.get('/inventory-movements/reports/summary', {
    params: warehouse_id ? { warehouse_id } : undefined,
  })
  return response.data.data
}

/**
 * Export inventory report
 */
export const exportInventoryReport = async (params?: {
  warehouse_id?: number
  format?: 'pdf' | 'excel'
}): Promise<Blob> => {
  const response = await apiClient.get('/products/export', {
    params,
    responseType: 'blob',
  })
  return response.data
}
