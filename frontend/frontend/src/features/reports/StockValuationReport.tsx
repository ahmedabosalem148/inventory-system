/**
 * Stock Valuation Report
 * تقرير تقييم المخزون - عرض القيمة المالية للمخزون
 */

import { useState, useEffect } from 'react'
import { Filter, X, FileText, Download } from 'lucide-react'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Select } from '@/components/ui/select'
import { Spinner } from '@/components/ui/spinner'
import apiClient from '@/app/axios'
import type { ProductClassification } from '@/types'

interface StockValuationItem {
  id: number
  sku: string
  name: string
  category: string
  branch: string
  branch_id: number
  quantity: number
  unit: string
  cost: number
  total_value: number
}

interface StockValuationSummary {
  total_products: number
  total_quantity: number
  total_value: number
  average_value: number
}

interface Branch {
  id: number
  name: string
}

interface Category {
  id: number
  name: string
}

export function StockValuationReport() {
  const [data, setData] = useState<StockValuationItem[]>([])
  const [summary, setSummary] = useState<StockValuationSummary | null>(null)
  const [loading, setLoading] = useState(false)
  const [branches, setBranches] = useState<Branch[]>([])
  const [categories, setCategories] = useState<Category[]>([])
  
  // Filters
  const [branchId, setBranchId] = useState<string>('')
  const [categoryId, setCategoryId] = useState<string>('')
  const [classificationFilter, setClassificationFilter] = useState<ProductClassification | ''>('')

  useEffect(() => {
    fetchBranches()
    fetchCategories()
    fetchReport()
  }, [])

  const fetchBranches = async () => {
    try {
      const response = await apiClient.get('/branches')
      setBranches(response.data.data || [])
    } catch (error) {
      console.error('Failed to fetch branches:', error)
    }
  }

  const fetchCategories = async () => {
    try {
      const response = await apiClient.get('/categories')
      setCategories(response.data.data || [])
    } catch (error) {
      console.error('Failed to fetch categories:', error)
    }
  }

  const fetchReport = async () => {
    setLoading(true)
    try {
      const params: Record<string, string> = {}
      if (branchId) params.branch_id = branchId
      if (categoryId) params.category_id = categoryId
      if (classificationFilter) params.product_classification = classificationFilter

      const response = await apiClient.get('/reports/stock-valuation', { params })
      setData(response.data.data || [])
      setSummary(response.data.summary || null)
    } catch (error) {
      console.error('Failed to fetch report:', error)
      alert('فشل في تحميل التقرير')
    } finally {
      setLoading(false)
    }
  }

  const handleFilter = () => {
    fetchReport()
  }

  const handleReset = () => {
    setBranchId('')
    setCategoryId('')
    setClassificationFilter('')
    setTimeout(fetchReport, 0)
  }

  const handleExportPDF = async () => {
    try {
      const params: Record<string, string> = {}
      if (branchId) params.branch_id = branchId
      if (categoryId) params.category_id = categoryId

      const response = await apiClient.get('/reports/stock-valuation/pdf', {
        params,
        responseType: 'blob'
      })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', 'stock-valuation-report.pdf')
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (error) {
      console.error('Failed to export PDF:', error)
      alert('فشل في تصدير PDF')
    }
  }

  const handleExportExcel = async () => {
    try {
      const params: Record<string, string> = {}
      if (branchId) params.branch_id = branchId
      if (categoryId) params.category_id = categoryId

      const response = await apiClient.get('/reports/stock-valuation/excel', {
        params,
        responseType: 'blob'
      })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', 'stock-valuation-report.xlsx')
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (error) {
      console.error('Failed to export Excel:', error)
      alert('فشل في تصدير Excel')
    }
  }

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ar-EG', {
      style: 'currency',
      currency: 'EGP',
      minimumFractionDigits: 2,
    }).format(amount)
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 mb-2">
          تقرير تقييم المخزون
        </h1>
        <p className="text-gray-600">
          عرض القيمة المالية للمخزون الحالي لكل منتج
        </p>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-wrap items-end gap-3">
            {/* Branch Filter */}
            <div className="flex-1 min-w-[200px]">
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الفرع
              </label>
              <Select
                value={branchId}
                onValueChange={setBranchId}
                disabled={loading}
              >
                <option value="">جميع الفروع</option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id.toString()}>
                    {branch.name}
                  </option>
                ))}
              </Select>
            </div>

            {/* Category Filter */}
            <div className="flex-1 min-w-[200px]">
              <label className="block text-sm font-medium text-gray-700 mb-1">
                الفئة
              </label>
              <Select
                value={categoryId}
                onValueChange={setCategoryId}
                disabled={loading}
              >
                <option value="">جميع الفئات</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id.toString()}>
                    {category.name}
                  </option>
                ))}
              </Select>
            </div>

            {/* Classification Filter */}
            <div className="flex-1 min-w-[200px]">
              <label className="block text-sm font-medium text-gray-700 mb-1">
                نوع المنتج
              </label>
              <Select
                value={classificationFilter}
                onValueChange={(value) => setClassificationFilter(value as ProductClassification | '')}
                disabled={loading}
              >
                <option value="">جميع الأنواع</option>
                <option value="finished_product">منتج تام</option>
                <option value="semi_finished">منتج غير تام</option>
                <option value="parts">أجزاء</option>
                <option value="plastic_parts">بلاستيك</option>
                <option value="aluminum_parts">ألومنيوم</option>
                <option value="raw_material">مواد خام</option>
                <option value="other">أخرى</option>
              </Select>
            </div>

            {/* Action Buttons */}
            <div className="flex gap-2">
              <Button
                onClick={handleFilter}
                disabled={loading}
                className="flex items-center gap-2"
              >
                <Filter className="w-4 h-4" />
                {loading ? 'جاري التحميل...' : 'فلترة'}
              </Button>
              <Button
                variant="outline"
                onClick={handleReset}
                disabled={loading}
                className="flex items-center gap-2"
              >
                <X className="w-4 h-4" />
                إعادة
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Summary Cards */}
      {summary && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardContent className="pt-6">
              <div className="text-sm text-gray-600 mb-1">عدد المنتجات</div>
              <div className="text-2xl font-bold text-gray-900">
                {summary.total_products}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-sm text-gray-600 mb-1">إجمالي الكمية</div>
              <div className="text-2xl font-bold text-gray-900">
                {summary.total_quantity.toFixed(2)}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-sm text-gray-600 mb-1">إجمالي القيمة</div>
              <div className="text-2xl font-bold text-green-600">
                {formatCurrency(summary.total_value)}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-6">
              <div className="text-sm text-gray-600 mb-1">متوسط القيمة</div>
              <div className="text-2xl font-bold text-blue-600">
                {formatCurrency(summary.average_value)}
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Export Buttons */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex gap-3">
            <Button
              variant="outline"
              onClick={handleExportPDF}
              className="flex items-center gap-2"
            >
              <FileText className="w-4 h-4" />
              تصدير PDF
            </Button>
            <Button
              variant="outline"
              onClick={handleExportExcel}
              className="flex items-center gap-2"
            >
              <Download className="w-4 h-4" />
              تصدير Excel
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Data Table */}
      <Card>
        <CardHeader>
          <CardTitle>تفاصيل المخزون</CardTitle>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex justify-center items-center py-8">
              <Spinner />
            </div>
          ) : data.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      الرمز
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      اسم المنتج
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      الفئة
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      الفرع
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      الكمية
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      التكلفة
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      القيمة الإجمالية
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {data.map((item, index) => (
                    <tr key={index} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-sm text-gray-900">
                        {item.sku}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-900">
                        {item.name}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {item.category}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-600">
                        {item.branch}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-900">
                        {item.quantity.toFixed(2)} {item.unit}
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-900">
                        {formatCurrency(item.cost)}
                      </td>
                      <td className="px-4 py-3 text-sm font-medium text-green-600">
                        {formatCurrency(item.total_value)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500">
              لا توجد بيانات
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
