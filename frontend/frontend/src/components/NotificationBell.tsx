/**
 * Notification Bell Component
 * Displays notifications icon with badge count and dropdown
 */

import { useState, useEffect, useRef } from 'react'
import { Bell, Check, X } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import {
  getUnreadCount,
  getRecentNotifications,
  markAsRead,
  markAllAsRead,
  deleteNotification,
  type Notification,
} from '@/services/api/notifications'
import { toast } from 'react-hot-toast'
import { formatDistanceToNow } from 'date-fns'
import { ar } from 'date-fns/locale'

export function NotificationBell() {
  const [isOpen, setIsOpen] = useState(false)
  const [unreadCount, setUnreadCount] = useState(0)
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [loading, setLoading] = useState(false)
  const dropdownRef = useRef<HTMLDivElement>(null)

  // Load unread count on mount and every 30 seconds
  useEffect(() => {
    loadUnreadCount()
    const interval = setInterval(loadUnreadCount, 30000) // 30 seconds
    return () => clearInterval(interval)
  }, [])

  // Load recent notifications when dropdown opens
  useEffect(() => {
    if (isOpen) {
      loadRecentNotifications()
    }
  }, [isOpen])

  // Close dropdown when clicking outside
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false)
      }
    }

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside)
      return () => document.removeEventListener('mousedown', handleClickOutside)
    }
  }, [isOpen])

  const loadUnreadCount = async () => {
    try {
      const response = await getUnreadCount()
      setUnreadCount(response.data.count)
    } catch (error) {
      console.error('Error loading unread count:', error)
    }
  }

  const loadRecentNotifications = async () => {
    try {
      setLoading(true)
      const response = await getRecentNotifications()
      setNotifications(response.data)
    } catch (error) {
      console.error('Error loading notifications:', error)
      toast.error('فشل في تحميل الإشعارات')
    } finally {
      setLoading(false)
    }
  }

  const handleMarkAsRead = async (notification: Notification) => {
    try {
      await markAsRead(notification.id)
      setNotifications(prev =>
        prev.map(n => (n.id === notification.id ? { ...n, is_read: true } : n))
      )
      setUnreadCount(prev => Math.max(0, prev - 1))
      
      // Navigate to action URL if exists
      if (notification.action_url) {
        window.location.hash = notification.action_url
        setIsOpen(false)
      }
    } catch (error) {
      console.error('Error marking as read:', error)
      toast.error('فشل في تحديث الإشعار')
    }
  }

  const handleMarkAllAsRead = async () => {
    try {
      await markAllAsRead()
      setNotifications(prev => prev.map(n => ({ ...n, is_read: true })))
      setUnreadCount(0)
      toast.success('تم تحديث جميع الإشعارات')
    } catch (error) {
      console.error('Error marking all as read:', error)
      toast.error('فشل في تحديث الإشعارات')
    }
  }

  const handleDelete = async (id: number) => {
    try {
      await deleteNotification(id)
      setNotifications(prev => prev.filter(n => n.id !== id))
      const notification = notifications.find(n => n.id === id)
      if (notification && !notification.is_read) {
        setUnreadCount(prev => Math.max(0, prev - 1))
      }
      toast.success('تم حذف الإشعار')
    } catch (error) {
      console.error('Error deleting notification:', error)
      toast.error('فشل في حذف الإشعار')
    }
  }

  const getNotificationColor = (color: string) => {
    const colors: Record<string, string> = {
      blue: 'bg-blue-100 text-blue-800',
      green: 'bg-green-100 text-green-800',
      yellow: 'bg-yellow-100 text-yellow-800',
      orange: 'bg-orange-100 text-orange-800',
      red: 'bg-red-100 text-red-800',
      purple: 'bg-purple-100 text-purple-800',
    }
    return colors[color] || colors.blue
  }

  return (
    <div className="relative" ref={dropdownRef}>
      {/* Bell Icon with Badge */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors"
        aria-label="الإشعارات"
      >
        <Bell className="w-6 h-6" />
        {unreadCount > 0 && (
          <Badge
            variant="danger"
            className="absolute -top-1 -right-1 min-w-[20px] h-5 flex items-center justify-center text-xs"
          >
            {unreadCount > 99 ? '99+' : unreadCount}
          </Badge>
        )}
      </button>

      {/* Dropdown */}
      {isOpen && (
        <div className="absolute left-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
          {/* Header */}
          <div className="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">الإشعارات</h3>
            {unreadCount > 0 && (
              <Button
                size="sm"
                variant="outline"
                onClick={handleMarkAllAsRead}
                className="text-xs"
              >
                <Check className="w-3 h-3 ml-1" />
                تحديد الكل كمقروء
              </Button>
            )}
          </div>

          {/* Notifications List */}
          <div className="max-h-96 overflow-y-auto">
            {loading ? (
              <div className="flex items-center justify-center p-8">
                <Spinner size="md" />
              </div>
            ) : notifications.length === 0 ? (
              <div className="text-center p-8 text-gray-500">
                <Bell className="w-12 h-12 mx-auto mb-2 text-gray-300" />
                <p>لا توجد إشعارات</p>
              </div>
            ) : (
              <div className="divide-y divide-gray-100">
                {notifications.map(notification => (
                  <div
                    key={notification.id}
                    className={`p-4 hover:bg-gray-50 transition-colors cursor-pointer ${
                      !notification.is_read ? 'bg-blue-50' : ''
                    }`}
                    onClick={() => handleMarkAsRead(notification)}
                  >
                    <div className="flex items-start gap-3">
                      {/* Icon */}
                      <div className={`flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center ${getNotificationColor(notification.color)}`}>
                        <Bell className="w-5 h-5" />
                      </div>

                      {/* Content */}
                      <div className="flex-1 min-w-0">
                        <div className="flex items-start justify-between gap-2">
                          <p className="font-medium text-gray-900 text-sm">
                            {notification.title}
                          </p>
                          {!notification.is_read && (
                            <div className="w-2 h-2 bg-blue-600 rounded-full flex-shrink-0 mt-1"></div>
                          )}
                        </div>
                        <p className="text-sm text-gray-600 mt-1 line-clamp-2">
                          {notification.message}
                        </p>
                        <p className="text-xs text-gray-400 mt-1">
                          {formatDistanceToNow(new Date(notification.created_at), {
                            addSuffix: true,
                            locale: ar,
                          })}
                        </p>
                      </div>

                      {/* Delete Button */}
                      <button
                        onClick={(e) => {
                          e.stopPropagation()
                          handleDelete(notification.id)
                        }}
                        className="text-gray-400 hover:text-red-600 transition-colors"
                        aria-label="حذف"
                      >
                        <X className="w-4 h-4" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Footer */}
          {notifications.length > 0 && (
            <div className="p-3 border-t border-gray-200">
              <Button
                variant="outline"
                size="sm"
                className="w-full"
                onClick={() => {
                  window.location.hash = '#notifications'
                  setIsOpen(false)
                }}
              >
                عرض جميع الإشعارات
              </Button>
            </div>
          )}
        </div>
      )}
    </div>
  )
}
