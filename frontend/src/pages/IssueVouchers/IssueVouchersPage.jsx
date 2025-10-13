import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Sidebar, Navbar, IssueVoucherForm } from '../../components/organisms';
import { DataTable } from '../../components/molecules';
import { Button, Badge, Card } from '../../components/atoms';
import { Plus, FileText, TrendingUp, CheckCircle, Clock, Eye } from 'lucide-react';

const IssueVouchersPage = () => {
  const navigate = useNavigate();
  const [vouchers, setVouchers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [totalItems, setTotalItems] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(10);
  const [sortField, setSortField] = useState('created_at');
  const [sortDirection, setSortDirection] = useState('desc');
  const [filters, setFilters] = useState({
    search: '',
    status: ''
  });
  const [showForm, setShowForm] = useState(false);
  const [editingVoucher, setEditingVoucher] = useState(null);
  const [deleteId, setDeleteId] = useState(null);

  // Statistics
  const [stats, setStats] = useState({
    totalVouchers: 0,
    todayVouchers: 0,
    totalAmount: 0,
    pendingVouchers: 0
  });

  // Mock data for demonstration
  const mockVouchers = [
    {
      id: 1,
      voucher_number: 'ISS-2024-001',
      customer_name: 'أحمد محمد علي',
      date: '2024-01-15',
      total_amount: 5500,
      status: 'completed',
      items_count: 3,
      created_at: '2024-01-15 10:30:00'
    },
    {
      id: 2,
      voucher_number: 'ISS-2024-002',
      customer_name: 'فاطمة حسن',
      date: '2024-01-15',
      total_amount: 2300,
      status: 'pending',
      items_count: 2,
      created_at: '2024-01-15 11:15:00'
    },
    {
      id: 3,
      voucher_number: 'ISS-2024-003',
      customer_name: 'محمود السيد',
      date: '2024-01-14',
      total_amount: 8750,
      status: 'completed',
      items_count: 5,
      created_at: '2024-01-14 14:20:00'
    },
    {
      id: 4,
      voucher_number: 'ISS-2024-004',
      customer_name: 'نور الدين',
      date: '2024-01-14',
      total_amount: 1200,
      status: 'cancelled',
      items_count: 1,
      created_at: '2024-01-14 16:45:00'
    },
    {
      id: 5,
      voucher_number: 'ISS-2024-005',
      customer_name: 'سارة أحمد',
      date: '2024-01-13',
      total_amount: 4500,
      status: 'completed',
      items_count: 4,
      created_at: '2024-01-13 09:30:00'
    }
  ];

  useEffect(() => {
    fetchVouchers();
    fetchStats();
  }, [currentPage, sortField, sortDirection, filters]);

  const fetchVouchers = async () => {
    setLoading(true);
    try {
      const apiClient = (await import('../../utils/axios')).default;
      const response = await apiClient.get('/issue-vouchers', {
        params: {
          page: currentPage,
          per_page: itemsPerPage,
          sort_by: sortField,
          sort_dir: sortDirection,
          search: filters.search || undefined,
          status: filters.status || undefined
        }
      });
      
      console.log('✅ Issue Vouchers API Response:', response.data);
      
      if (response.data && response.data.data) {
        setVouchers(response.data.data);
        setTotalItems(response.data.meta?.total || response.data.data.length);
      } else {
        console.warn('⚠️ No vouchers returned from API');
        setVouchers([]);
        setTotalItems(0);
      }
      
    } catch (error) {
      console.error('❌ Error fetching vouchers:', error);
      
      // Fallback to mock data
      setTimeout(() => {
        setVouchers(mockVouchers);
        setTotalItems(mockVouchers.length);
      }, 100);
    } finally {
      setLoading(false);
    }
  };

  const fetchStats = async () => {
    try {
      // TODO: Replace with actual API call
      // const response = await apiClient.get('/issue-vouchers/stats');
      // setStats(response.data);
      
      // Mock stats
      setStats({
        totalVouchers: 245,
        todayVouchers: 12,
        totalAmount: 125000,
        pendingVouchers: 8
      });
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  const handleSort = (field, direction) => {
    setSortField(field);
    setSortDirection(direction);
  };

  const handleFilter = (filterData) => {
    setFilters(prev => ({ ...prev, ...filterData }));
    setCurrentPage(1);
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
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
    navigate(`/vouchers/issue/${voucher.id}`);
  };

  const handleDelete = async (id) => {
    if (window.confirm('هل أنت متأكد من حذف هذا الإذن؟')) {
      try {
        // TODO: Replace with actual API call
        // await apiClient.delete(`/issue-vouchers/${id}`);
        console.log('Delete voucher:', id);
        fetchVouchers();
        fetchStats();
      } catch (error) {
        console.error('Error deleting voucher:', error);
        alert('حدث خطأ أثناء حذف الإذن');
      }
    }
  };

  const handleFormSubmit = async (data) => {
    try {
      const apiClient = (await import('../../utils/axios')).default;
      
      // Prepare payload to match backend expectations
      const payload = {
        customer_id: data.customer_id || null,
        customer_name: data.customer_id ? undefined : 'عميل نقدي',
        branch_id: 1, // TODO: Get from user context/branch selector
        issue_date: data.date, // Rename 'date' to 'issue_date'
        notes: data.notes || '',
        discount_type: data.discount_type || null,
        discount_value: data.discount_value || 0,
        items: data.items || []
      };
      
      console.log('📤 Submitting voucher payload:', payload);
      
      if (editingVoucher) {
        const response = await apiClient.put(`/issue-vouchers/${editingVoucher.id}`, payload);
        console.log('✅ Voucher updated:', response.data);
        alert('تم تحديث إذن الصرف بنجاح');
      } else {
        const response = await apiClient.post('/issue-vouchers', payload);
        console.log('✅ Voucher created:', response.data);
        alert('تم إنشاء إذن الصرف بنجاح');
      }
      
      setShowForm(false);
      setEditingVoucher(null);
      fetchVouchers();
      fetchStats();
    } catch (error) {
      console.error('Error saving voucher:', error);
      
      if (error.response?.data?.errors) {
        // Show validation errors
        const errorMessages = Object.values(error.response.data.errors).flat().join('\n');
        alert('أخطاء في البيانات:\n' + errorMessages);
      } else if (error.response?.data?.message) {
        alert(error.response.data.message);
      } else {
        alert('حدث خطأ أثناء حفظ الإذن. تأكد من الاتصال بالخادم.');
      }
    }
  };

  const handlePrint = (voucher) => {
    // TODO: Implement PDF printing
    console.log('Print voucher:', voucher);
    window.open(`/issue-vouchers/${voucher.id}/print`, '_blank');
  };

  const getStatusBadge = (status) => {
    const statusConfig = {
      completed: { label: 'مكتمل', variant: 'success' },
      pending: { label: 'قيد الانتظار', variant: 'warning' },
      cancelled: { label: 'ملغي', variant: 'danger' }
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
        <div className="font-semibold text-primary-600">
          {voucher.voucher_number}
        </div>
      )
    },
    {
      key: 'customer_name',
      title: 'اسم العميل',
      sortable: true,
      render: (value, voucher) => (
        <div>
          <div className="font-medium">{voucher.customer_name}</div>
        </div>
      )
    },
    {
      key: 'date',
      title: 'التاريخ',
      sortable: true,
      render: (value, voucher) => (
        <div className="text-gray-600">
          {new Date(voucher.date).toLocaleDateString('ar-EG')}
        </div>
      )
    },
    {
      key: 'items_count',
      title: 'عدد الأصناف',
      sortable: true,
      render: (value, voucher) => (
        <div className="text-center">
          <Badge variant="info">{voucher.items_count}</Badge>
        </div>
      )
    },
    {
      key: 'total_amount',
      title: 'المبلغ الإجمالي',
      sortable: true,
      render: (value, voucher) => (
        <div className="font-semibold text-gray-900">
          {voucher.total_amount.toLocaleString('ar-EG')} جنيه
        </div>
      )
    },
    {
      key: 'status',
      title: 'الحالة',
      sortable: true,
      render: (value, voucher) => getStatusBadge(voucher.status)
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

  const filterOptions = [
    {
      field: 'status',
      label: 'الحالة',
      type: 'select',
      options: [
        { value: '', label: 'الكل' },
        { value: 'completed', label: 'مكتمل' },
        { value: 'pending', label: 'قيد الانتظار' },
        { value: 'cancelled', label: 'ملغي' }
      ]
    }
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      <Sidebar />
      
      <div className="lg:mr-64">
        <Navbar />
        
        <main className="min-h-screen p-4 md:p-6 pt-20">
          {/* Page Header */}
          <div className="mb-6">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h1 className="text-2xl font-bold text-gray-900">
                  إذونات الصرف
                </h1>
                <p className="text-gray-600 mt-1">
                  إدارة وتتبع جميع إذونات صرف المنتجات
                </p>
              </div>
              <div className="flex gap-3">
                <Button
                  variant="outline"
                  onClick={() => window.print()}
                >
                  <FileText className="w-5 h-5 ml-2" />
                  تصدير PDF
                </Button>
                <Button
                  variant="primary"
                  onClick={handleAdd}
                >
                  <Plus className="w-5 h-5 ml-2" />
                  إذن صرف جديد
                </Button>
              </div>
            </div>

            {/* Statistics Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
              <Card>
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-gray-600 mb-1">إجمالي الإذونات</p>
                    <p className="text-2xl font-bold text-gray-900">
                      {stats.totalVouchers}
                    </p>
                  </div>
                  <div className="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                    <FileText className="w-6 h-6 text-primary-600" />
                  </div>
                </div>
              </Card>

              <Card>
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-gray-600 mb-1">إذونات اليوم</p>
                    <p className="text-2xl font-bold text-gray-900">
                      {stats.todayVouchers}
                    </p>
                  </div>
                  <div className="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <Clock className="w-6 h-6 text-blue-600" />
                  </div>
                </div>
              </Card>

              <Card>
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-gray-600 mb-1">المبلغ الإجمالي</p>
                    <p className="text-2xl font-bold text-gray-900">
                      {stats.totalAmount.toLocaleString('ar-EG')}
                    </p>
                    <p className="text-xs text-gray-500">جنيه</p>
                  </div>
                  <div className="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                    <TrendingUp className="w-6 h-6 text-green-600" />
                  </div>
                </div>
              </Card>

              <Card>
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm text-gray-600 mb-1">قيد الانتظار</p>
                    <p className="text-2xl font-bold text-gray-900">
                      {stats.pendingVouchers}
                    </p>
                  </div>
                  <div className="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
                    <CheckCircle className="w-6 h-6 text-yellow-600" />
                  </div>
                </div>
              </Card>
            </div>
          </div>

          {/* Data Table */}
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
              searchable={true}
              searchPlaceholder="ابحث برقم الإذن أو اسم العميل..."
              filterable={true}
              filterOptions={filterOptions}
              emptyMessage="لا توجد إذونات صرف"
            />
          </Card>
        </main>
      </div>

      {/* Issue Voucher Form Modal */}
      {showForm && (
        <IssueVoucherForm
          voucher={editingVoucher}
          onSubmit={handleFormSubmit}
          onClose={() => setShowForm(false)}
        />
      )}
    </div>
  );
};

export default IssueVouchersPage;
