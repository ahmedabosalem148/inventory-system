import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Sidebar, Navbar, ReturnVoucherForm } from '../../components/organisms';
import { DataTable } from '../../components/molecules';
import { Button, Badge, Card } from '../../components/atoms';
import { Plus, RotateCcw, TrendingUp, CheckCircle, Clock, FileText, Eye } from 'lucide-react';
import apiClient from '../../utils/axios';

const ReturnVouchersPage = () => {
  const navigate = useNavigate();
  const [vouchers, setVouchers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [totalItems, setTotalItems] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(10);
  const [sortField, setSortField] = useState('return_date');
  const [sortDirection, setSortDirection] = useState('desc');
  const [filters, setFilters] = useState({
    search: '',
    status: ''
  });
  const [showForm, setShowForm] = useState(false);
  const [editingVoucher, setEditingVoucher] = useState(null);

  // Statistics
  const [stats, setStats] = useState({
    totalVouchers: 0,
    todayVouchers: 0,
    totalAmount: 0,
    completedVouchers: 0
  });

  useEffect(() => {
    fetchVouchers();
  }, [currentPage, sortField, sortDirection, filters]);

  const fetchVouchers = async () => {
    setLoading(true);
    try {
      const response = await apiClient.get('/return-vouchers', {
        params: {
          page: currentPage,
          per_page: itemsPerPage,
          sort_by: sortField,
          sort_dir: sortDirection,
          ...filters
        }
      });

      setVouchers(response.data.data || []);
      setTotalItems(response.data.meta?.total || response.data.data?.length || 0);
      
      // Calculate stats from data
      const data = response.data.data || [];
      const total = response.data.meta?.total || data.length;
      const today = new Date().toISOString().split('T')[0];
      const todayCount = data.filter(v => v.return_date === today).length;
      const totalAmt = data.reduce((sum, v) => sum + parseFloat(v.total_amount || 0), 0);
      const completed = data.filter(v => v.status === 'completed').length;
      
      setStats({
        totalVouchers: total,
        todayVouchers: todayCount,
        totalAmount: totalAmt,
        completedVouchers: completed
      });
    } catch (error) {
      console.error('Error fetching return vouchers:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleAdd = () => {
    setEditingVoucher(null);
    setShowForm(true);
  };

  const handleEdit = (voucher) => {
    setEditingVoucher(voucher);
    setShowForm(true);
  };

  const handleViewDetails = (voucher) => {
    navigate(`/vouchers/return/${voucher.id}`);
  };

  const handleDelete = async (id) => {
    if (!window.confirm('هل أنت متأكد من حذف إذن الإرجاع هذا؟')) {
      return;
    }

    try {
      await apiClient.delete(`/return-vouchers/${id}`);
      fetchVouchers();
    } catch (error) {
      console.error('Error deleting return voucher:', error);
      alert('حدث خطأ أثناء حذف الإذن');
    }
  };

  const handlePrint = async (voucher) => {
    try {
      const response = await apiClient.post(`/return-vouchers/${voucher.id}/print`, {}, {
        responseType: 'blob'
      });
      
      // Create blob link to download
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `return-voucher-${voucher.voucher_number}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Error printing voucher:', error);
      alert('حدث خطأ أثناء طباعة الإذن');
    }
  };

  const handleSort = (field, direction) => {
    setSortField(field);
    setSortDirection(direction);
  };

  const handleFilter = (key, value) => {
    setFilters(prev => ({ ...prev, [key]: value }));
    setCurrentPage(1);
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      completed: { label: 'مكتمل', variant: 'success' },
      cancelled: { label: 'ملغي', variant: 'error' }
    };
    
    const config = statusConfig[status] || { label: status, variant: 'default' };
    return <Badge variant={config.variant}>{config.label}</Badge>;
  };

  const columns = [
    {
      key: 'voucher_number',
      title: 'رقم الإذن',
      sortable: true,
      render: (value, voucher) => (
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
            <RotateCcw className="w-4 h-4 text-orange-600" />
          </div>
          <span className="font-semibold text-primary-600">{value}</span>
        </div>
      )
    },
    {
      key: 'customer_name',
      title: 'اسم العميل',
      sortable: true,
      render: (value, voucher) => (
        <div>
          <div className="font-medium">{voucher.customer?.name || value || 'عميل نقدي'}</div>
          {voucher.customer?.phone && (
            <div className="text-xs text-gray-500">{voucher.customer.phone}</div>
          )}
        </div>
      )
    },
    {
      key: 'return_date',
      title: 'تاريخ الإرجاع',
      sortable: true,
      render: (value) => (
        <div className="text-gray-600">
          {new Date(value).toLocaleDateString('ar-EG', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
          })}
        </div>
      )
    },
    {
      key: 'items_count',
      title: 'عدد الأصناف',
      render: (value, voucher) => (
        <div className="text-center">
          <Badge variant="info">{voucher.items?.length || 0}</Badge>
        </div>
      )
    },
    {
      key: 'total_amount',
      title: 'المبلغ الإجمالي',
      sortable: true,
      render: (value) => (
        <div className="font-semibold text-orange-600">
          {parseFloat(value || 0).toLocaleString('ar-EG', { 
            minimumFractionDigits: 2,
            maximumFractionDigits: 2 
          })} جنيه
        </div>
      )
    },
    {
      key: 'status',
      title: 'الحالة',
      sortable: true,
      render: (value) => getStatusBadge(value)
    },
    {
      key: 'actions',
      title: 'الإجراءات',
      render: (value, voucher) => (
        <div className="flex gap-2 justify-end">
          <Button
            variant="primary"
            size="sm"
            onClick={() => handleViewDetails(voucher)}
            className="flex items-center gap-1"
            title="عرض التفاصيل"
          >
            <Eye className="w-4 h-4" />
            تفاصيل
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => handlePrint(voucher)}
            title="طباعة"
          >
            <FileText className="w-4 h-4" />
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => handleEdit(voucher)}
          >
            تعديل
          </Button>
          <Button
            variant="danger"
            size="sm"
            onClick={() => handleDelete(voucher.id)}
          >
            حذف
          </Button>
        </div>
      )
    }
  ];

  return (
    <div className="min-h-screen bg-gray-100" dir="rtl">
      <Sidebar />
      
      <div className="lg:mr-64">
        <Navbar />
        
        <main className="min-h-screen p-4 md:p-6 pt-20">
          {/* Page Header */}
          <div className="mb-6">
            <div className="flex items-center gap-3 mb-2">
              <div className="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center">
                <RotateCcw className="w-6 h-6 text-white" />
              </div>
              <div>
                <h1 className="text-3xl font-bold text-gray-900">أذونات الإرجاع</h1>
                <p className="text-gray-600">إدارة وعرض أذونات إرجاع المنتجات</p>
              </div>
            </div>
          </div>

          {/* Statistics Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <Card>
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">إجمالي الأذونات</p>
                  <p className="text-2xl font-bold text-gray-900 mt-1">{stats.totalVouchers}</p>
                </div>
                <div className="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                  <RotateCcw className="w-6 h-6 text-orange-600" />
                </div>
              </div>
            </Card>

            <Card>
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">أذونات اليوم</p>
                  <p className="text-2xl font-bold text-blue-600 mt-1">{stats.todayVouchers}</p>
                </div>
                <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                  <Clock className="w-6 h-6 text-blue-600" />
                </div>
              </div>
            </Card>

            <Card>
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">إجمالي المبالغ</p>
                  <p className="text-2xl font-bold text-orange-600 mt-1">
                    {stats.totalAmount.toLocaleString('ar-EG', { maximumFractionDigits: 0 })}
                  </p>
                  <p className="text-xs text-gray-500">جنيه</p>
                </div>
                <div className="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                  <TrendingUp className="w-6 h-6 text-orange-600" />
                </div>
              </div>
            </Card>

            <Card>
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">الأذونات المكتملة</p>
                  <p className="text-2xl font-bold text-green-600 mt-1">{stats.completedVouchers}</p>
                </div>
                <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                  <CheckCircle className="w-6 h-6 text-green-600" />
                </div>
              </div>
            </Card>
          </div>

          {/* Actions Bar */}
          <div className="mb-6 flex items-center justify-between">
            <div className="flex gap-3">
              <select
                value={filters.status}
                onChange={(e) => handleFilter('status', e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
              >
                <option value="">كل الحالات</option>
                <option value="completed">مكتمل</option>
                <option value="cancelled">ملغي</option>
              </select>
            </div>

            <Button
              onClick={handleAdd}
              icon={Plus}
              className="bg-orange-600 hover:bg-orange-700"
            >
              إذن إرجاع جديد
            </Button>
          </div>

          {/* Vouchers Table */}
          <Card>
            <DataTable
              data={vouchers}
              columns={columns}
              loading={loading}
              totalItems={totalItems}
              currentPage={currentPage}
              itemsPerPage={itemsPerPage}
              onPageChange={handlePageChange}
              onSort={handleSort}
              onFilter={handleFilter}
              searchable
              searchPlaceholder="ابحث برقم الإذن..."
              emptyMessage="لا توجد أذونات إرجاع"
            />
          </Card>
        </main>
      </div>

      {/* Return Voucher Form Modal */}
      <ReturnVoucherForm
        isOpen={showForm}
        onClose={() => {
          setShowForm(false);
          setEditingVoucher(null);
        }}
        onSuccess={fetchVouchers}
        editingVoucher={editingVoucher}
      />
    </div>
  );
};

export default ReturnVouchersPage;
