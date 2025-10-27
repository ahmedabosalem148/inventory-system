import React, { useState, useEffect } from 'react';
import { Sidebar, Navbar } from '../../components/organisms';
import { Card, Button } from '../../components/atoms';
import { FileText, Download, Filter, X } from 'lucide-react';
import apiClient from '../../utils/axios';

const StockValuationReport = () => {
  const [data, setData] = useState([]);
  const [summary, setSummary] = useState(null);
  const [loading, setLoading] = useState(false);
  const [branches, setBranches] = useState([]);
  const [categories, setCategories] = useState([]);
  
  // Filters
  const [branchId, setBranchId] = useState('');
  const [categoryId, setCategoryId] = useState('');

  useEffect(() => {
    fetchBranches();
    fetchCategories();
    fetchReport();
  }, []);

  const fetchBranches = async () => {
    try {
      const response = await apiClient.get('/branches');
      setBranches(response.data.data || []);
    } catch (error) {
      console.error('Failed to fetch branches:', error);
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await apiClient.get('/categories');
      setCategories(response.data.data || []);
    } catch (error) {
      console.error('Failed to fetch categories:', error);
    }
  };

  const fetchReport = async () => {
    setLoading(true);
    try {
      const params = {};
      if (branchId) params.branch_id = branchId;
      if (categoryId) params.category_id = categoryId;

      const response = await apiClient.get('/reports/stock-valuation', { params });
      setData(response.data.data || []);
      setSummary(response.data.summary || null);
    } catch (error) {
      console.error('Failed to fetch report:', error);
      alert('فشل في تحميل التقرير');
    } finally {
      setLoading(false);
    }
  };

  const handleFilter = () => {
    fetchReport();
  };

  const handleReset = () => {
    setBranchId('');
    setCategoryId('');
    fetchReport();
  };

  const handleExportPDF = async () => {
    try {
      const params = {};
      if (branchId) params.branch_id = branchId;
      if (categoryId) params.category_id = categoryId;

      const response = await apiClient.get('/reports/stock-valuation/pdf', {
        params,
        responseType: 'blob'
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'stock-valuation-report.pdf');
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Failed to export PDF:', error);
      alert('فشل في تصدير PDF');
    }
  };

  const handleExportExcel = async () => {
    try {
      const params = {};
      if (branchId) params.branch_id = branchId;
      if (categoryId) params.category_id = categoryId;

      const response = await apiClient.get('/reports/stock-valuation/excel', {
        params,
        responseType: 'blob'
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'stock-valuation-report.xlsx');
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Failed to export Excel:', error);
      alert('فشل في تصدير Excel');
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('ar-EG', {
      style: 'currency',
      currency: 'EGP',
      minimumFractionDigits: 2,
    }).format(amount);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <Sidebar />
      <Navbar />
      <main className="pt-16 lg:mr-64 p-4 md:p-6 min-h-screen">
        {/* Header */}
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-gray-900 mb-2">
            تقرير تقييم المخزون
          </h1>
          <p className="text-gray-600">
            عرض قيمة المخزون الحالية لكل منتج
          </p>
        </div>

        {/* Filters */}
        <Card className="mb-6">
          <div className="p-4">
            <div className="flex flex-wrap items-end gap-3">
              {/* Branch Filter */}
              <div className="flex-1 min-w-[200px]">
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  الفرع
                </label>
                <select
                  value={branchId}
                  onChange={(e) => setBranchId(e.target.value)}
                  className="w-full border rounded-md px-3 py-2"
                  disabled={loading}
                >
                  <option value="">جميع الفروع</option>
                  {branches.map((branch) => (
                    <option key={branch.id} value={branch.id}>
                      {branch.name}
                    </option>
                  ))}
                </select>
              </div>

              {/* Category Filter */}
              <div className="flex-1 min-w-[200px]">
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  الفئة
                </label>
                <select
                  value={categoryId}
                  onChange={(e) => setCategoryId(e.target.value)}
                  className="w-full border rounded-md px-3 py-2"
                  disabled={loading}
                >
                  <option value="">جميع الفئات</option>
                  {categories.map((category) => (
                    <option key={category.id} value={category.id}>
                      {category.name}
                    </option>
                  ))}
                </select>
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
          </div>
        </Card>

        {/* Summary Cards */}
        {summary && (
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <Card>
              <div className="p-4">
                <div className="text-sm text-gray-600 mb-1">عدد المنتجات</div>
                <div className="text-2xl font-bold text-gray-900">
                  {summary.total_products}
                </div>
              </div>
            </Card>
            <Card>
              <div className="p-4">
                <div className="text-sm text-gray-600 mb-1">إجمالي الكمية</div>
                <div className="text-2xl font-bold text-gray-900">
                  {summary.total_quantity.toFixed(2)}
                </div>
              </div>
            </Card>
            <Card>
              <div className="p-4">
                <div className="text-sm text-gray-600 mb-1">إجمالي القيمة</div>
                <div className="text-2xl font-bold text-green-600">
                  {formatCurrency(summary.total_value)}
                </div>
              </div>
            </Card>
            <Card>
              <div className="p-4">
                <div className="text-sm text-gray-600 mb-1">متوسط القيمة</div>
                <div className="text-2xl font-bold text-blue-600">
                  {formatCurrency(summary.average_value)}
                </div>
              </div>
            </Card>
          </div>
        )}

        {/* Export Buttons */}
        <Card className="mb-6">
          <div className="p-4 flex gap-3">
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
        </Card>

        {/* Data Table */}
        <Card>
          <div className="p-4">
            <h3 className="text-lg font-semibold mb-4">تفاصيل المخزون</h3>
            {loading ? (
              <div className="text-center py-8 text-gray-500">
                جاري التحميل...
              </div>
            ) : data.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        الرمز
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        اسم المنتج
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        الفئة
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        الفرع
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        الكمية
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                        التكلفة
                      </th>
                      <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
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
          </div>
        </Card>
      </main>
    </div>
  );
};

export default StockValuationReport;
