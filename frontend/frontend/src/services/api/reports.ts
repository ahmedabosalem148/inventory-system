/**
 * Reports API Service
 * Handles all reporting and analytics API calls
 */

import { apiClient } from './client'

/**
 * Get sales report
 */
export const getSalesReport = async (params: {
  date_from: string
  date_to: string
  group_by?: 'day' | 'week' | 'month'
}): Promise<any> => {
  const response = await apiClient.get('/reports/sales', { params })
  return response.data.data
}

/**
 * Get purchases report
 */
export const getPurchasesReport = async (params: {
  date_from: string
  date_to: string
  group_by?: 'day' | 'week' | 'month'
}): Promise<any> => {
  const response = await apiClient.get('/reports/purchases', { params })
  return response.data.data
}

/**
 * Get inventory report
 */
export const getInventoryReport = async (params?: {
  warehouse_id?: number
  category_id?: number
  low_stock?: boolean
}): Promise<any> => {
  const response = await apiClient.get('/reports/inventory', { params })
  return response.data.data
}

/**
 * Get profit/loss report
 */
export const getProfitLossReport = async (params: {
  date_from: string
  date_to: string
}): Promise<{
  total_sales: number
  total_purchases: number
  total_expenses: number
  gross_profit: number
  net_profit: number
  profit_margin: number
}> => {
  const response = await apiClient.get('/reports/profit-loss', { params })
  return response.data.data
}

/**
 * Get top selling products
 */
export const getTopSellingProducts = async (params: {
  date_from: string
  date_to: string
  limit?: number
}): Promise<any[]> => {
  const response = await apiClient.get('/reports/top-products', { params })
  return response.data.data
}

/**
 * Get customer aging report
 */
export const getCustomerAgingReport = async (): Promise<any> => {
  const response = await apiClient.get('/reports/customer-aging')
  return response.data.data
}

/**
 * Get supplier aging report
 */
export const getSupplierAgingReport = async (): Promise<any> => {
  const response = await apiClient.get('/reports/supplier-aging')
  return response.data.data
}

/**
 * Export report to PDF/Excel
 */
export const exportReport = async (
  reportType: 'sales' | 'purchases' | 'inventory' | 'profit-loss',
  params: any,
  format: 'pdf' | 'excel' = 'pdf'
): Promise<Blob> => {
  const response = await apiClient.get(`/reports/${reportType}/export`, {
    params: { ...params, format },
    responseType: 'blob',
  })
  return response.data
}

/**
 * Get dashboard analytics
 */
export const getDashboardAnalytics = async (params?: {
  date_from?: string
  date_to?: string
}): Promise<{
  total_sales: number
  total_purchases: number
  total_profit: number
  total_customers: number
  sales_trend: any[]
  top_products: any[]
  low_stock_items: any[]
}> => {
  const response = await apiClient.get('/reports/analytics', { params })
  return response.data.data
}
