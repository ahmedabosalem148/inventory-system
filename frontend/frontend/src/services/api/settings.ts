/**
 * Settings API Service
 * Handles all settings and configuration API calls
 */

import { apiClient } from './client'

/**
 * Get company settings
 */
export const getCompanySettings = async (): Promise<any> => {
  const response = await apiClient.get('/settings/company')
  return response.data.data
}

/**
 * Update company settings
 */
export const updateCompanySettings = async (data: any): Promise<any> => {
  const response = await apiClient.put('/settings/company', data)
  return response.data.data
}

/**
 * Get system settings
 */
export const getSystemSettings = async (): Promise<any> => {
  const response = await apiClient.get('/settings/system')
  return response.data.data
}

/**
 * Update system settings
 */
export const updateSystemSettings = async (data: any): Promise<any> => {
  const response = await apiClient.put('/settings/system', data)
  return response.data.data
}

/**
 * Get all users
 */
export const getUsers = async (): Promise<any[]> => {
  const response = await apiClient.get('/users')
  return response.data.data
}

/**
 * Create new user
 */
export const createUser = async (data: {
  name: string
  email: string
  password: string
  role: string
  permissions?: string[]
}): Promise<any> => {
  const response = await apiClient.post('/users', data)
  return response.data.data
}

/**
 * Update user
 */
export const updateUser = async (id: number, data: any): Promise<any> => {
  const response = await apiClient.put(`/users/${id}`, data)
  return response.data.data
}

/**
 * Delete user
 */
export const deleteUser = async (id: number): Promise<void> => {
  await apiClient.delete(`/users/${id}`)
}

/**
 * Get all roles
 */
export const getRoles = async (): Promise<any[]> => {
  const response = await apiClient.get('/roles')
  return response.data.data
}

/**
 * Get all permissions
 */
export const getPermissions = async (): Promise<any[]> => {
  const response = await apiClient.get('/permissions')
  return response.data.data
}

/**
 * Upload company logo
 */
export const uploadLogo = async (file: File): Promise<string> => {
  const formData = new FormData()
  formData.append('logo', file)
  const response = await apiClient.post('/settings/logo', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return response.data.data.logo_url
}

/**
 * Backup database
 */
export const backupDatabase = async (): Promise<Blob> => {
  const response = await apiClient.get('/settings/backup', {
    responseType: 'blob',
  })
  return response.data
}
