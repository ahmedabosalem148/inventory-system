import os

content = """import { useState, useEffect } from 'react'
import { Download, Filter, AlertTriangle, X, FileText, Package } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Spinner } from '@/components/ui/spinner'
import { toast } from 'react-hot-toast'
import apiClient from '@/app/axios'

export function LowStockReport() {
  const [data, setData] = useState([])
  const [summary, setSummary] = useState(null)
  const [loading, setLoading] = useState(false)
  const [branches, setBranches] = useState([])
  const [categories, setCategories] = useState([])
  const [branchId, setBranchId] = useState('')
  const [categoryId, setCategoryId] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [search, setSearch] = useState('')

  useEffect(() => { fetchBranches(); fetchCategories(); fetchReport() }, [])

  const fetchBranches = async () => {
    try {
      const response = await apiClient.get('/branches')
      setBranches(response.data.data || [])
    } catch (error) { console.error('Failed:', error) }
  }

  const fetchCategories = async () => {
    try {
      const response = await apiClient.get('/categories')
      setCategories(response.data.data || [])
    } catch (error) { console.error('Failed:', error) }
  }

  const fetchReport = async () => {
    setLoading(true)
    try {
      const params = {}
      if (branchId) params.branch_id = branchId
      if (categoryId) params.category_id = categoryId
      if (statusFilter !== 'all') params.status = statusFilter
      if (search) params.search = search
      const response = await apiClient.get('/reports/low-stock', { params })
      setData(response.data.data || [])
      setSummary(response.data.summary || null)
    } catch (error) {
      toast.error('فشل في تحميل التقرير')
    } finally { setLoading(false) }
  }

  const handleFilter = () => fetchReport()
  const handleReset = () => { setBranchId(''); setCategoryId(''); setStatusFilter('all'); setSearch(''); setTimeout(fetchReport, 0) }

  return (<div className="space-y-6"><div><h1 className="text-2xl font-bold mb-2">تقرير المخزون المنخفض</h1></div><Card><CardContent className="pt-6"><div className="grid grid-cols-1 md:grid-cols-5 gap-3"><div><label className="block text-sm font-medium mb-1">الفرع</label><select value={branchId} onChange={(e) => setBranchId(e.target.value)} disabled={loading} className="w-full border rounded-md px-3 py-2 text-sm"><option value="">الكل</option>{branches.map((b) => <option key={b.id} value={b.id}>{b.name}</option>)}</select></div><div><label className="block text-sm font-medium mb-1">الفئة</label><select value={categoryId} onChange={(e) => setCategoryId(e.target.value)} disabled={loading} className="w-full border rounded-md px-3 py-2 text-sm"><option value="">الكل</option>{categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select></div><div><label className="block text-sm font-medium mb-1">الحالة</label><select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} disabled={loading} className="w-full border rounded-md px-3 py-2 text-sm"><option value="all">الكل</option><option value="out_of_stock">نفذ</option><option value="critical">حرج</option><option value="low">منخفض</option></select></div><div><label className="block text-sm font-medium mb-1">بحث</label><input type="text" placeholder="اسم المنتج" value={search} onChange={(e) => setSearch(e.target.value)} disabled={loading} className="w-full border rounded-md px-3 py-2 text-sm" /></div><div className="flex items-end gap-2"><Button onClick={handleFilter} disabled={loading}><Filter className="w-4 h-4 ml-2" />فلترة</Button><Button variant="outline" onClick={handleReset} disabled={loading}><X className="w-4 h-4" /></Button></div></div></CardContent></Card>{summary && <div className="grid grid-cols-4 gap-4"><Card><CardHeader className="pb-2"><CardTitle className="text-sm">إجمالي الأصناف</CardTitle></CardHeader><CardContent><span className="text-2xl font-bold">{summary.total_items}</span></CardContent></Card><Card><CardHeader className="pb-2"><CardTitle className="text-sm">نفذ</CardTitle></CardHeader><CardContent><Badge variant="danger" className="text-lg">{summary.out_of_stock}</Badge></CardContent></Card><Card><CardHeader className="pb-2"><CardTitle className="text-sm">حرج</CardTitle></CardHeader><CardContent><Badge variant="danger" className="text-lg">{summary.critical}</Badge></CardContent></Card><Card><CardHeader className="pb-2"><CardTitle className="text-sm">منخفض</CardTitle></CardHeader><CardContent><Badge variant="warning" className="text-lg">{summary.low}</Badge></CardContent></Card></div>}<Card><CardHeader><CardTitle>تفاصيل المخزون</CardTitle></CardHeader><CardContent>{loading ? <div className="flex justify-center py-8"><Spinner /></div> : data.length > 0 ? <div className="overflow-x-auto"><table className="min-w-full"><thead className="bg-gray-50"><tr><th className="px-4 py-3 text-right text-xs">الحالة</th><th className="px-4 py-3 text-right text-xs">الرمز</th><th className="px-4 py-3 text-right text-xs">المنتج</th><th className="px-4 py-3 text-right text-xs">الفرع</th><th className="px-4 py-3 text-right text-xs">الكمية</th><th className="px-4 py-3 text-right text-xs">الحد الأدنى</th><th className="px-4 py-3 text-right text-xs">النقص</th></tr></thead><tbody className="bg-white divide-y">{data.map((item, i) => <tr key={i} className="hover:bg-gray-50"><td className="px-4 py-3"><Badge variant={item.status === 'out_of_stock' || item.status === 'critical' ? 'danger' : 'warning'}>{item.status === 'out_of_stock' ? 'نفذ' : item.status === 'critical' ? 'حرج' : 'منخفض'}</Badge></td><td className="px-4 py-3 text-sm">{item.sku}</td><td className="px-4 py-3 text-sm font-medium">{item.name}</td><td className="px-4 py-3 text-sm">{item.branch_name}</td><td className="px-4 py-3 text-sm font-bold text-red-600">{item.quantity}</td><td className="px-4 py-3 text-sm">{item.min_stock}</td><td className="px-4 py-3 text-sm text-red-600">{item.deficit}</td></tr>)}</tbody></table></div> : <div className="text-center py-8"><Package className="w-12 h-12 text-gray-400 mx-auto mb-3" /><p className="text-gray-500">لا توجد بيانات</p></div>}</CardContent></Card></div>)
}
"""

file_path = r"frontend\frontend\src\features\reports\LowStockReport.tsx"
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print(f"File created: {file_path}")
