/**
 * Products API Service
 * Handles all product-related API calls
 */

import apiClient from './client'
import type { Product, CreateProductInput, UpdateProductInput, ProductFilters } from '@/types'

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export interface ProductsListParams extends ProductFilters {
  page?: number
  per_page?: number
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}

/**
 * Get paginated list of products
 */
export async function getProducts(params?: ProductsListParams): Promise<PaginatedResponse<Product>> {
  const response = await apiClient.get<PaginatedResponse<Product>>('/products', { params })
  return response.data
}

/**
 * Get single product by ID
 */
export async function getProduct(id: number): Promise<Product> {
  const response = await apiClient.get<{ data: Product }>(`/products/${id}`)
  return response.data.data
}

/**
 * Create new product
 */
export async function createProduct(data: CreateProductInput): Promise<Product> {
  const response = await apiClient.post<{ data: Product }>('/products', data)
  return response.data.data
}

/**
 * Update existing product
 */
export async function updateProduct(id: number, data: UpdateProductInput): Promise<Product> {
  const response = await apiClient.put<{ data: Product }>(`/products/${id}`, data)
  return response.data.data
}

/**
 * Delete product
 */
export async function deleteProduct(id: number): Promise<void> {
  await apiClient.delete(`/products/${id}`)
}

/**
 * Get low stock products
 */
export async function getLowStockProducts(): Promise<Product[]> {
  const response = await apiClient.get<{ data: Product[] }>('/products/low-stock')
  return response.data.data
}

/**
 * Bulk update products
 */
export async function bulkUpdateProducts(products: UpdateProductInput[]): Promise<Product[]> {
  const response = await apiClient.post<{ data: Product[] }>('/products/bulk-update', { products })
  return response.data.data
}

/**
 * Get product categories
 */
export async function getProductCategories(): Promise<Array<{id: number, name: string}>> {
  const response = await apiClient.get<{ data: Array<{id: number, name: string}> }>('/categories')
  return response.data.data
}

/**
 * Import products from Excel/CSV
 */
export async function importProducts(file: File): Promise<{ success: number; failed: number; errors: string[] }> {
  const formData = new FormData()
  formData.append('file', file)
  
  const response = await apiClient.post<{ data: { success: number; failed: number; errors: string[] } }>(
    '/products/import',
    formData,
    {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    }
  )
  return response.data.data
}

/**
 * Export products to Excel
 */
export async function exportProducts(filters?: ProductFilters): Promise<Blob> {
  const response = await apiClient.get('/products/export', {
    params: filters,
    responseType: 'blob'
  })
  return response.data
}

/**
 * Branch minimum stock management
 */

export interface BranchStock {
  branch_id: number
  branch_name: string
  current_stock: number
  min_qty: number
  is_low: boolean
}

export interface BranchMinStockResponse {
  product: {
    id: number
    name: string
  }
  branch_stocks: BranchStock[]
}

/**
 * Get minimum stock levels for a product across all branches
 */
export async function getProductBranchMinStock(productId: number): Promise<BranchMinStockResponse> {
  const response = await apiClient.get<BranchMinStockResponse>(`/products/${productId}/branch-min-stock`)
  return response.data
}

/**
 * Update minimum stock level for a product in a specific branch
 */
export async function updateProductBranchMinStock(
  productId: number,
  branchId: number,
  minQty: number
): Promise<{ message: string; min_qty: number }> {
  const response = await apiClient.put<{ message: string; min_qty: number }>(
    `/products/${productId}/branch-min-stock`,
    { branch_id: branchId, min_qty: minQty }
  )
  return response.data
}
