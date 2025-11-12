import apiClient from './client'

export interface Notification {
  id: number
  user_id: number
  type: string
  title: string
  message: string
  icon?: string
  color: string
  data?: Record<string, unknown>
  action_url?: string
  is_read: boolean
  read_at?: string
  created_at: string
  updated_at: string
}

export interface NotificationsResponse {
  success: boolean
  data: Notification[]
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface UnreadCountResponse {
  success: boolean
  data: {
    count: number
  }
}

/**
 * Get all notifications
 */
export async function getNotifications(params?: {
  type?: string
  is_read?: boolean
  page?: number
  per_page?: number
}) {
  const response = await apiClient.get<NotificationsResponse>('/notifications', { params })
  return response.data
}

/**
 * Get unread count
 */
export async function getUnreadCount() {
  const response = await apiClient.get<UnreadCountResponse>('/notifications/unread-count')
  return response.data
}

/**
 * Get recent notifications (for bell dropdown)
 */
export async function getRecentNotifications() {
  const response = await apiClient.get<{ success: boolean; data: Notification[] }>('/notifications/recent')
  return response.data
}

/**
 * Mark notification as read
 */
export async function markAsRead(id: number) {
  const response = await apiClient.post(`/notifications/${id}/mark-read`)
  return response.data
}

/**
 * Mark notification as unread
 */
export async function markAsUnread(id: number) {
  const response = await apiClient.post(`/notifications/${id}/mark-unread`)
  return response.data
}

/**
 * Mark all notifications as read
 */
export async function markAllAsRead() {
  const response = await apiClient.post('/notifications/mark-all-read')
  return response.data
}

/**
 * Delete notification
 */
export async function deleteNotification(id: number) {
  const response = await apiClient.delete(`/notifications/${id}`)
  return response.data
}

/**
 * Clear all read notifications
 */
export async function clearReadNotifications() {
  const response = await apiClient.post('/notifications/clear-read')
  return response.data
}

/**
 * Get notification types
 */
export async function getNotificationTypes() {
  const response = await apiClient.get<{ success: boolean; data: Array<{ value: string; label: string }> }>('/notifications/types')
  return response.data
}
