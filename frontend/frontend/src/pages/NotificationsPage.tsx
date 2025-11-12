/**
 * Notifications Page Component
 * Full page view for all notifications with filtering, pagination, and bulk actions
 */

import { useState, useEffect } from 'react'
import { 
  Bell, 
  Check, 
  X, 
  Trash2, 
  Filter,
  RefreshCw,
  CheckCheck,
  AlertCircle
} from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Spinner } from '@/components/ui/spinner'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
  getNotifications,
  markAsRead,
  markAsUnread,
  markAllAsRead,
  deleteNotification,
  clearReadNotifications,
  getNotificationTypes,
  type Notification,
} from '@/services/api/notifications'
import { formatDistanceToNow } from 'date-fns'
import { ar } from 'date-fns/locale'

export default function NotificationsPage() {
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [loading, setLoading] = useState(true)
  const [actionLoading, setActionLoading] = useState<number | null>(null)
  const [filterType, setFilterType] = useState<string>('all')
  const [filterStatus, setFilterStatus] = useState<string>('all')
  const [notificationTypes, setNotificationTypes] = useState<Array<{ value: string; label: string }>>([])
  
  // Pagination
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [total, setTotal] = useState(0)
  const perPage = 20

  useEffect(() => {
    loadNotificationTypes()
  }, [])

  useEffect(() => {
    loadNotifications()
  }, [currentPage, filterType, filterStatus])

  const loadNotificationTypes = async () => {
    try {
      const response = await getNotificationTypes()
      if (response.success && response.data) {
        setNotificationTypes(response.data)
      }
    } catch (error) {
      console.error('Failed to load notification types:', error)
    }
  }

  const loadNotifications = async () => {
    setLoading(true)
    try {
      const params: any = {
        page: currentPage,
        per_page: perPage,
      }

      if (filterType !== 'all') {
        params.type = filterType
      }

      if (filterStatus === 'read') {
        params.is_read = true
      } else if (filterStatus === 'unread') {
        params.is_read = false
      }

      const response = await getNotifications(params)
      setNotifications(response.data)
      if (response.meta) {
        setCurrentPage(response.meta.current_page)
        setTotalPages(response.meta.last_page)
        setTotal(response.meta.total)
      }
    } catch (error) {
      console.error('Failed to load notifications:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleMarkAsRead = async (notification: Notification) => {
    if (notification.is_read) return

    setActionLoading(notification.id)
    try {
      await markAsRead(notification.id)
      setNotifications(prev =>
        prev.map(n => (n.id === notification.id ? { ...n, is_read: true } : n))
      )
    } catch (error) {
      console.error('Failed to mark as read:', error)
    } finally {
      setActionLoading(null)
    }
  }

  const handleMarkAsUnread = async (notification: Notification) => {
    if (!notification.is_read) return

    setActionLoading(notification.id)
    try {
      await markAsUnread(notification.id)
      setNotifications(prev =>
        prev.map(n => (n.id === notification.id ? { ...n, is_read: false } : n))
      )
    } catch (error) {
      console.error('Failed to mark as unread:', error)
    } finally {
      setActionLoading(null)
    }
  }

  const handleDelete = async (id: number) => {
    if (!confirm('هل أنت متأكد من حذف هذا الإشعار؟')) return

    setActionLoading(id)
    try {
      await deleteNotification(id)
      setNotifications(prev => prev.filter(n => n.id !== id))
      setTotal(prev => prev - 1)
    } catch (error) {
      console.error('Failed to delete notification:', error)
    } finally {
      setActionLoading(null)
    }
  }

  const handleMarkAllAsRead = async () => {
    if (!confirm('هل تريد تعليم جميع الإشعارات كمقروءة؟')) return

    setLoading(true)
    try {
      await markAllAsRead()
      await loadNotifications()
    } catch (error) {
      console.error('Failed to mark all as read:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleClearRead = async () => {
    if (!confirm('هل تريد حذف جميع الإشعارات المقروءة؟')) return

    setLoading(true)
    try {
      await clearReadNotifications()
      await loadNotifications()
    } catch (error) {
      console.error('Failed to clear read notifications:', error)
    } finally {
      setLoading(false)
    }
  }

  const handleNotificationClick = async (notification: Notification) => {
    // Mark as read if unread
    if (!notification.is_read) {
      await handleMarkAsRead(notification)
    }

    // Navigate to action URL
    if (notification.action_url) {
      window.location.hash = notification.action_url
    }
  }

  const getNotificationColor = (color: string) => {
    const colors: Record<string, string> = {
      blue: 'text-blue-500 bg-blue-50',
      green: 'text-green-500 bg-green-50',
      yellow: 'text-yellow-500 bg-yellow-50',
      orange: 'text-orange-500 bg-orange-50',
      red: 'text-red-500 bg-red-50',
      purple: 'text-purple-500 bg-purple-50',
    }
    return colors[color] || colors.blue
  }

  const unreadCount = notifications.filter(n => !n.is_read).length

  return (
    <div className="container mx-auto py-6 px-4">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">الإشعارات</h1>
          <p className="text-sm text-gray-500 mt-1">
            إجمالي {total} إشعار ({unreadCount} غير مقروء)
          </p>
        </div>

        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={loadNotifications}
            disabled={loading}
          >
            <RefreshCw className={`w-4 h-4 ml-2 ${loading ? 'animate-spin' : ''}`} />
            تحديث
          </Button>
        </div>
      </div>

      {/* Filters */}
      <Card className="p-4 mb-6">
        <div className="flex items-center gap-4 flex-wrap">
          <div className="flex items-center gap-2">
            <Filter className="w-4 h-4 text-gray-500" />
            <span className="text-sm font-medium text-gray-700">الفلاتر:</span>
          </div>

          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-600">النوع:</span>
            <Select value={filterType} onValueChange={setFilterType}>
              <SelectTrigger className="w-[180px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">جميع الأنواع</SelectItem>
                {notificationTypes.map(type => (
                  <SelectItem key={type.value} value={type.value}>
                    {type.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-600">الحالة:</span>
            <Select value={filterStatus} onValueChange={setFilterStatus}>
              <SelectTrigger className="w-[180px]">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">الكل</SelectItem>
                <SelectItem value="unread">غير مقروء</SelectItem>
                <SelectItem value="read">مقروء</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {/* Bulk Actions */}
          <div className="mr-auto flex gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={handleMarkAllAsRead}
              disabled={loading || unreadCount === 0}
            >
              <CheckCheck className="w-4 h-4 ml-2" />
              تعليم الكل كمقروء
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={handleClearRead}
              disabled={loading}
            >
              <Trash2 className="w-4 h-4 ml-2" />
              حذف المقروء
            </Button>
          </div>
        </div>
      </Card>

      {/* Notifications List */}
      {loading ? (
        <div className="flex justify-center items-center py-12">
          <Spinner size="lg" />
        </div>
      ) : notifications.length === 0 ? (
        <Card className="p-12">
          <div className="text-center">
            <Bell className="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-900 mb-2">
              لا توجد إشعارات
            </h3>
            <p className="text-sm text-gray-500">
              {filterType !== 'all' || filterStatus !== 'all'
                ? 'جرب تغيير الفلاتر'
                : 'سيتم عرض الإشعارات هنا عند توفرها'}
            </p>
          </div>
        </Card>
      ) : (
        <div className="space-y-2">
          {notifications.map(notification => (
            <Card
              key={notification.id}
              className={`p-4 transition-all hover:shadow-md cursor-pointer ${
                !notification.is_read ? 'bg-blue-50/50 border-blue-200' : ''
              }`}
              onClick={() => handleNotificationClick(notification)}
            >
              <div className="flex items-start gap-4">
                {/* Icon */}
                <div
                  className={`w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 ${getNotificationColor(
                    notification.color
                  )}`}
                >
                  <Bell className="w-5 h-5" />
                </div>

                {/* Content */}
                <div className="flex-1 min-w-0">
                  <div className="flex items-start justify-between gap-2 mb-1">
                    <h4 className="font-medium text-gray-900">
                      {notification.title}
                    </h4>
                    {!notification.is_read && (
                      <Badge variant="default" className="bg-blue-500 flex-shrink-0">
                        جديد
                      </Badge>
                    )}
                  </div>
                  <p className="text-sm text-gray-600 mb-2">
                    {notification.message}
                  </p>
                  <div className="flex items-center gap-4 text-xs text-gray-500">
                    <span>
                      {formatDistanceToNow(new Date(notification.created_at), {
                        addSuffix: true,
                        locale: ar,
                      })}
                    </span>
                    <Badge variant="outline" className="text-xs">
                      {notification.type}
                    </Badge>
                  </div>
                </div>

                {/* Actions */}
                <div className="flex items-center gap-1 flex-shrink-0">
                  {actionLoading === notification.id ? (
                    <Spinner size="sm" />
                  ) : (
                    <>
                      {notification.is_read ? (
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={(e) => {
                            e.stopPropagation()
                            handleMarkAsUnread(notification)
                          }}
                          title="تعليم كغير مقروء"
                        >
                          <AlertCircle className="w-4 h-4 text-gray-400" />
                        </Button>
                      ) : (
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={(e) => {
                            e.stopPropagation()
                            handleMarkAsRead(notification)
                          }}
                          title="تعليم كمقروء"
                        >
                          <Check className="w-4 h-4 text-green-600" />
                        </Button>
                      )}
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={(e) => {
                          e.stopPropagation()
                          handleDelete(notification.id)
                        }}
                        title="حذف"
                      >
                        <X className="w-4 h-4 text-red-600" />
                      </Button>
                    </>
                  )}
                </div>
              </div>
            </Card>
          ))}
        </div>
      )}

      {/* Pagination */}
      {!loading && notifications.length > 0 && totalPages > 1 && (
        <div className="mt-6 flex items-center justify-between">
          <p className="text-sm text-gray-600">
            عرض {(currentPage - 1) * perPage + 1} إلى{' '}
            {Math.min(currentPage * perPage, total)} من {total}
          </p>
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
              disabled={currentPage === 1}
            >
              السابق
            </Button>
            <div className="flex items-center gap-1">
              {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                let pageNum
                if (totalPages <= 5) {
                  pageNum = i + 1
                } else if (currentPage <= 3) {
                  pageNum = i + 1
                } else if (currentPage >= totalPages - 2) {
                  pageNum = totalPages - 4 + i
                } else {
                  pageNum = currentPage - 2 + i
                }

                return (
                  <Button
                    key={pageNum}
                    variant={currentPage === pageNum ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => setCurrentPage(pageNum)}
                  >
                    {pageNum}
                  </Button>
                )
              })}
            </div>
            <Button
              variant="outline"
              size="sm"
              onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
              disabled={currentPage === totalPages}
            >
              التالي
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}
