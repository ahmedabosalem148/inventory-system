import { useState, useEffect } from 'react';
import Sidebar from '../../components/organisms/Sidebar/Sidebar';
import Navbar from '../../components/organisms/Navbar/Navbar';
import StatCard from '../../components/molecules/StatCard/StatCard';
import { Package, FileText, RotateCcw, AlertTriangle, Users, TrendingUp } from 'lucide-react';
import apiClient from '../../services/api';

const DashboardPage = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [stats, setStats] = useState(null);
  const [lowStock, setLowStock] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      
      // Fetch dashboard stats
      const statsResponse = await apiClient.get('/dashboard');
      setStats(statsResponse.data.data);
      
      // Fetch low stock products
      const lowStockResponse = await apiClient.get('/dashboard/low-stock');
      setLowStock(lowStockResponse.data.data.slice(0, 5)); // Top 5
      
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50">
        <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />
        <Navbar onMenuClick={() => setSidebarOpen(true)} />
        <main className="pt-16 lg:mr-64">
          <div className="p-6 flex items-center justify-center min-h-screen">
            <div className="text-center">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
              <p className="text-gray-600">جاري تحميل البيانات...</p>
            </div>
          </div>
        </main>
      </div>
    );
  }

  const statCards = [
    {
      title: 'إجمالي المنتجات',
      value: stats?.total_products?.toString() || '0',
      trendValue: `${stats?.total_branches || 0} فرع`,
      trend: 'neutral',
      icon: Package,
      color: 'primary'
    },
    {
      title: 'العملاء النشطين',
      value: stats?.total_customers?.toString() || '0',
      trendValue: `${stats?.customers_with_credit || 0} له رصيد`,
      trend: 'up',
      icon: Users,
      color: 'success'
    },
    {
      title: 'أذونات اليوم',
      value: stats?.today_vouchers_count?.toString() || '0',
      trendValue: `${parseFloat(stats?.today_sales || 0).toFixed(2)} ج.م`,
      trend: 'up',
      icon: FileText,
      color: 'info'
    },
    {
      title: 'منتجات قاربت النفاذ',
      value: stats?.low_stock_count?.toString() || '0',
      trendValue: `${stats?.out_of_stock_count || 0} نفذ تماماً`,
      trend: 'down',
      icon: AlertTriangle,
      color: 'error'
    }
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />
      <Navbar onMenuClick={() => setSidebarOpen(true)} />

      {/* Main Content */}
      <main className="pt-16 lg:mr-64">
        <div className="p-6">
          {/* Page Header */}
          <div className="mb-8">
            <h1 className="text-2xl font-bold text-gray-900 mb-2">لوحة التحكم</h1>
            <p className="text-gray-600">نظرة عامة على نظام إدارة المخزون</p>
          </div>

          {/* Stats Grid */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {statCards.map((stat, index) => (
              <StatCard key={index} {...stat} />
            ))}
          </div>

          {/* Financial Summary */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
              <h3 className="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <TrendingUp className="w-5 h-5 text-green-600" />
                الملخص المالي
              </h3>
              <div className="space-y-4">
                <div className="flex justify-between items-center pb-3 border-b border-gray-100">
                  <span className="text-sm text-gray-600">إجمالي المستحقات (له)</span>
                  <span className="text-lg font-bold text-green-600">
                    {parseFloat(stats?.total_receivables || 0).toFixed(2)} ج.م
                  </span>
                </div>
                <div className="flex justify-between items-center pb-3 border-b border-gray-100">
                  <span className="text-sm text-gray-600">إجمالي المدفوعات (علية)</span>
                  <span className="text-lg font-bold text-red-600">
                    {parseFloat(stats?.total_payables || 0).toFixed(2)} ج.م
                  </span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">قيمة المخزون الإجمالية</span>
                  <span className="text-lg font-bold text-blue-600">
                    {parseFloat(stats?.total_stock_value || 0).toFixed(2)} ج.م
                  </span>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
              <h3 className="text-lg font-bold text-gray-900 mb-4">معلومات الفرع</h3>
              <div className="space-y-4">
                <div className="flex items-start gap-3 pb-4 border-b border-gray-100">
                  <div className="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                  <div className="flex-1">
                    <p className="text-sm font-medium text-gray-900">{stats?.branch_name || 'جميع الفروع'}</p>
                    <p className="text-xs text-gray-500">الفرع النشط</p>
                  </div>
                </div>
                <div className="flex items-start gap-3 pb-4 border-b border-gray-100">
                  <div className="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                  <div className="flex-1">
                    <p className="text-sm font-medium text-gray-900">
                      {stats?.today_vouchers_count || 0} أذونات
                    </p>
                    <p className="text-xs text-gray-500">صرف وإرجاع اليوم</p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <div className="w-2 h-2 bg-orange-500 rounded-full mt-2"></div>
                  <div className="flex-1">
                    <p className="text-sm font-medium text-gray-900">
                      {stats?.low_stock_count || 0} منتج
                    </p>
                    <p className="text-xs text-gray-500">يحتاج إعادة طلب</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Low Stock Alert */}
          <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-bold text-gray-900">تنبيهات المخزون</h3>
              <button 
                onClick={() => window.location.href = '/products'}
                className="text-sm text-blue-600 hover:text-blue-700 font-medium"
              >
                عرض الكل
              </button>
            </div>
            
            {lowStock.length === 0 ? (
              <div className="text-center py-8">
                <Package className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                <p className="text-gray-500">لا توجد منتجات قاربت النفاذ</p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-gray-200">
                      <th className="text-right py-3 px-4 text-sm font-medium text-gray-700">المنتج</th>
                      <th className="text-right py-3 px-4 text-sm font-medium text-gray-700">الكمية المتاحة</th>
                      <th className="text-right py-3 px-4 text-sm font-medium text-gray-700">الحد الأدنى</th>
                      <th className="text-right py-3 px-4 text-sm font-medium text-gray-700">الحالة</th>
                    </tr>
                  </thead>
                  <tbody>
                    {lowStock.map((product, index) => (
                      <tr key={index} className="border-b border-gray-100 hover:bg-gray-50">
                        <td className="py-3 px-4 text-sm text-gray-900">{product.name}</td>
                        <td className="py-3 px-4 text-sm text-gray-900">{product.current_stock || 0}</td>
                        <td className="py-3 px-4 text-sm text-gray-900">{product.min_stock || 0}</td>
                        <td className="py-3 px-4">
                          <span className={`inline-flex px-2 py-1 text-xs font-medium rounded-full ${
                            product.current_stock === 0 
                              ? 'bg-red-100 text-red-700' 
                              : 'bg-orange-100 text-orange-700'
                          }`}>
                            {product.current_stock === 0 ? 'نفذ تماماً' : 'منخفض'}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      </main>
    </div>
  );
};

export default DashboardPage;
