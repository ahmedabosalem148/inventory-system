/**
 * Activity Log Page
 * View system activity logs for auditing
 */

import { useState, useEffect } from 'react'
import { FileText, User, Filter, Download, Eye } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { DataTable } from '@/components/ui/data-table'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { toast } from 'react-hot-toast'
import apiClient from '@/services/api/client'

interface ActivityLog {
  id: number
  log_name: string
  description: string
  subject_type: string
  subject_id: number
  causer_id: number
  causer_name: string
  properties: any
  created_at: string
}

interface PaginatedResponse {
  data: ActivityLog[]
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export function ActivityLogPage() {
  const [logs, setLogs] = useState<ActivityLog[]>([])
  const [loading, setLoading] = useState(true)
  const [searchTerm, setSearchTerm] = useState('')
  const [logNameFilter, setLogNameFilter] = useState<string>('all')
  const [subjectTypeFilter, setSubjectTypeFilter] = useState<string>('all')
  const [fromDate, setFromDate] = useState('')
  const [toDate, setToDate] = useState('')
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [logNames, setLogNames] = useState<Array<{value: string, label: string}>>([])
  const [subjectTypes, setSubjectTypes] = useState<Array<{value: string, label: string}>>([])

  useEffect(() => {
    loadFilters()
  }, [])

  useEffect(() => {
    loadLogs()
  }, [searchTerm, logNameFilter, subjectTypeFilter, fromDate, toDate, currentPage])

  const loadFilters = async () => {
    try {
      const [logNamesRes, subjectTypesRes] = await Promise.all([
        apiClient.get('/activity-logs/log-names'),
        apiClient.get('/activity-logs/subject-types'),
      ])
      setLogNames(logNamesRes.data.data || [])
      setSubjectTypes(subjectTypesRes.data.data || [])
    } catch (error) {
      console.error('Error loading filters:', error)
    }
  }

  const loadLogs = async () => {
    try {
      setLoading(true)
      const params: any = {
        page: currentPage,
        per_page: 50,
      }

      if (searchTerm) params.search = searchTerm
      if (logNameFilter !== 'all') params.log_name = logNameFilter
      if (subjectTypeFilter !== 'all') params.subject_type = subjectTypeFilter
      if (fromDate) params.from_date = fromDate
      if (toDate) params.to_date = toDate

      const response = await apiClient.get<PaginatedResponse>('/activity-logs', { params })
      setLogs(response.data.data)
      setTotalPages(response.data.meta?.last_page || 1)
    } catch (error: any) {
      console.error('Error loading logs:', error)
      toast.error('فشل في تحميل سجل الأنشطة')
    } finally {
      setLoading(false)
    }
  }

  const handleReset = () => {
    setSearchTerm('')
    setLogNameFilter('all')
    setSubjectTypeFilter('all')
    setFromDate('')
    setToDate('')
    setCurrentPage(1)
  }

  const handleExport = async () => {
    toast.success('جاري تصدير البيانات...')
    // TODO: Implement export functionality
  }

  const getLogNameBadge = (logName: string) => {
    const badges: Record<string, { variant: 'default' | 'success' | 'warning' | 'danger' | 'info' }> = {
      'created': { variant: 'success' },
      'updated': { variant: 'info' },
      'deleted': { variant: 'danger' },
      'login': { variant: 'success' },
      'logout': { variant: 'default' },
      'approved': { variant: 'success' },
      'cancelled': { variant: 'warning' },
    }
    return badges[logName] || { variant: 'default' }
  }

  const columns = [
    {
      key: 'created_at',
      header: 'التاريخ والوقت',
      sortable: true,
      render: (row: ActivityLog) => (
        <div className="text-sm">
          <div className="font-medium">
            {new Date(row.created_at).toLocaleDateString('ar-EG')}
          </div>
          <div className="text-gray-500 text-xs">
            {new Date(row.created_at).toLocaleTimeString('ar-EG')}
          </div>
        </div>
      ),
    },
    {
      key: 'causer_name',
      header: 'المستخدم',
      sortable: true,
      render: (row: ActivityLog) => (
        <div className="flex items-center gap-2">
          <User className="w-4 h-4 text-gray-400" />
          <span className="font-medium">{row.causer_name}</span>
        </div>
      ),
    },
    {
      key: 'log_name',
      header: 'الإجراء',
      sortable: true,
      render: (row: ActivityLog) => {
        const badge = getLogNameBadge(row.log_name)
        return (
          <Badge variant={badge.variant}>
            {row.description}
          </Badge>
        )
      },
    },
    {
      key: 'subject_type',
      header: 'النوع',
      sortable: true,
      render: (row: ActivityLog) => (
        <span className="text-sm text-gray-600">{row.subject_type}</span>
      ),
    },
    {
      key: 'subject_id',
      header: 'المعرّف',
      sortable: true,
      render: (row: ActivityLog) => (
        <span className="text-sm font-mono text-gray-500">#{row.subject_id}</span>
      ),
    },
    {
      key: 'actions',
      header: 'التفاصيل',
      render: (row: ActivityLog) => (
        <Button
          size="sm"
          variant="outline"
          onClick={() => handleViewDetails(row)}
        >
          <Eye className="w-4 h-4 ml-1" />
          عرض
        </Button>
      ),
    },
  ]

  const handleViewDetails = (log: ActivityLog) => {
    // Show details in a dialog or navigate to details page
    toast.success(`عرض تفاصيل النشاط #${log.id}`)
    console.log('Activity details:', log)
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <FileText className="h-8 w-8 text-blue-600" />
            سجل الأنشطة
          </h1>
          <p className="text-gray-600 mt-1">
            تتبع جميع العمليات والأنشطة في النظام
          </p>
        </div>
        <Button onClick={handleExport} variant="outline">
          <Download className="w-4 h-4 ml-2" />
          تصدير
        </Button>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
            {/* Search */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                بحث
              </label>
              <Input
                type="text"
                placeholder="ابحث في السجل..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>

            {/* Log Name Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                نوع الإجراء
              </label>
              <select
                value={logNameFilter}
                onChange={(e) => setLogNameFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md"
              >
                <option value="all">الكل</option>
                {logNames.map((item) => (
                  <option key={item.value} value={item.value}>
                    {item.label}
                  </option>
                ))}
              </select>
            </div>

            {/* Subject Type Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                نوع العنصر
              </label>
              <select
                value={subjectTypeFilter}
                onChange={(e) => setSubjectTypeFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md"
              >
                <option value="all">الكل</option>
                {subjectTypes.map((item) => (
                  <option key={item.value} value={item.value}>
                    {item.label}
                  </option>
                ))}
              </select>
            </div>

            {/* From Date */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                من تاريخ
              </label>
              <Input
                type="date"
                value={fromDate}
                onChange={(e) => setFromDate(e.target.value)}
              />
            </div>

            {/* To Date */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                إلى تاريخ
              </label>
              <Input
                type="date"
                value={toDate}
                onChange={(e) => setToDate(e.target.value)}
              />
            </div>
          </div>

          {/* Reset Button */}
          <div className="mt-4 flex justify-end">
            <Button onClick={handleReset} variant="outline" size="sm">
              <Filter className="w-4 h-4 ml-2" />
              إعادة تعيين الفلاتر
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Activity Logs Table */}
      <Card>
        <DataTable
          columns={columns}
          data={logs}
          loading={loading}
          emptyMessage="لا توجد أنشطة مسجلة"
        />
        
        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between px-6 py-4 border-t">
            <div className="text-sm text-gray-600">
              صفحة {currentPage} من {totalPages}
            </div>
            <div className="flex gap-2">
              <Button
                size="sm"
                variant="outline"
                onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                disabled={currentPage === 1 || loading}
              >
                السابق
              </Button>
              <Button
                size="sm"
                variant="outline"
                onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                disabled={currentPage === totalPages || loading}
              >
                التالي
              </Button>
            </div>
          </div>
        )}
      </Card>
    </div>
  )
}
