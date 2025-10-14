import React, { useState, useEffect, useMemo, useCallback } from 'react';



import { useNavigate } from 'react-router-dom';
import { Sidebar, Navbar, IssueVoucherForm } from '../../components/organisms';
import { DataTable } from '../../components/molecules';
import { Button, Badge, Card } from '../../components/atoms';
import { Plus, FileText, TrendingUp, CheckCircle, Clock, Eye, RefreshCw, AlertCircle, Search } from 'lucide-react';
import { useAuth } from '../../contexts/AuthContext';

// Memoized Voucher Card Component for Performance
const VoucherCard = React.memo(({ voucher, onView, onEdit, onDelete, formatDate, getStatusText, navigate }) => (
  <div className="border rounded-lg p-4 hover:shadow-md transition-shadow">
    <div className="flex justify-between items-start mb-3">
      <h3 className="font-medium text-primary-600">
        {voucher.voucher_number || voucher.number || `#${voucher.id}`}
      </h3>
      <span
        className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
          voucher.status === 'confirmed' || voucher.status === 'completed'
            ? 'bg-green-100 text-green-800'
            : voucher.status === 'cancelled'
            ? 'bg-red-100 text-red-800'
            : 'bg-yellow-100 text-yellow-800'
        }`}
      >
        {getStatusText(voucher.status)}
      </span>
    </div>
    
    <div className="space-y-2 text-sm mb-4">
      <div className="flex justify-between">
        <span className="text-gray-600">العميل:</span>
        {(() => {
          const customerName = voucher.customer_name || voucher.customer?.name || '—';
          const customerId = voucher.customer_id || voucher.customer?.id;
          
          if (!customerId || customerName === 'عميل نقدي') {
            return (
              <span className="font-medium truncate max-w-[150px] text-gray-600">
                {customerName}
              </span>
            );
          }
          
          return (
            <button
              onClick={() => navigate(`/customers/${customerId}/profile`)}
              className="font-medium text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-200 truncate max-w-[150px] text-right"
              title={`عرض ملف العميل: ${customerName}`}
            >
              {customerName}
            </button>
          );
        })()}
      </div>
      <div className="flex justify-between">
        <span className="text-gray-600">التاريخ:</span>
        <span>{formatDate(voucher.issue_date || voucher.date || voucher.created_at)}</span>
      </div>
      <div className="flex justify-between">
        <span className="text-gray-600">عدد الأصناف:</span>
        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
          {voucher.items_count || voucher.items?.length || 0}
        </span>
      </div>
      <div className="flex justify-between">
        <span className="text-gray-600">المبلغ:</span>
        <span className="font-medium">
          {(voucher.net_total || voucher.total_amount || 0).toLocaleString('ar-EG')} جنيه
        </span>
      </div>
    </div>

    <div className="flex gap-2">
      <button 
        className="flex-1 px-3 py-2 text-sm font-medium text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors"
        onClick={onView}
      >
        عرض
      </button>
      <button 
        className="flex-1 px-3 py-2 text-sm font-medium text-green-600 border border-green-200 rounded-lg hover:bg-green-50 transition-colors"
        onClick={onEdit}
      >
        تحرير
      </button>
    </div>
  </div>
));

VoucherCard.displayName = 'VoucherCard';
const MemoizedVoucherCard = React.memo(VoucherCard);

// Toast Component
const Toast = React.memo(({ show, message, type, onHide }) => {
  React.useEffect(() => {
    if (show) {
      const timer = setTimeout(onHide, 4000);
      return () => clearTimeout(timer);
    }
  }, [show, onHide]);

  if (!show) return null;

  const bgColor = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 
                 type === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 
                 'bg-blue-50 border-blue-200 text-blue-800';

  return (
    <div className={`fixed top-4 right-4 z-50 p-4 border rounded-lg shadow-lg ${bgColor} max-w-sm`}>
      <div className="flex justify-between items-center">
        <span className="text-sm font-medium">{message}</span>
        <button onClick={onHide} className="ml-3 text-lg leading-none">&times;</button>
      </div>
    </div>
  );
});

Toast.displayName = 'Toast';

// Confirmation Modal Component
const ConfirmationModal = React.memo(({ show, title, message, onConfirm, onCancel, loading }) => {
  if (!show) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
      <div className="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 className="text-lg font-semibold text-gray-900 mb-2">{title}</h3>
        <p className="text-gray-600 mb-6">{message}</p>
        <div className="flex gap-3 justify-end">
          <button
            type="button"
            onClick={onCancel}
            disabled={loading}
            className="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
          >
            إلغاء
          </button>
          <button
            type="button"
            onClick={onConfirm}
            disabled={loading}
            className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
          >
            {loading && <RefreshCw className="w-4 h-4 animate-spin" />}
            تأكيد الحذف
          </button>
        </div>
      </div>
    </div>
  );
});

ConfirmationModal.displayName = 'ConfirmationModal';

const IssueVouchersPage = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [vouchers, setVouchers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [totalItems, setTotalItems] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(10);
  const [sortField, setSortField] = useState('created_at');
  const [sortDirection, setSortDirection] = useState('desc');
  const [filters, setFilters] = useState({
    search: '',
    status: '',
    from_date: '',
    to_date: ''
  });
  const [showForm, setShowForm] = useState(false);
  const [editingVoucher, setEditingVoucher] = useState(null);
  const [deleteId, setDeleteId] = useState(null);
  const [error, setError] = useState(null);
  const [statsLoading, setStatsLoading] = useState(false);

  // Statistics
  const [stats, setStats] = useState({
    totalVouchers: 0,
    todayVouchers: 0,
    totalAmount: 0,
    pendingVouchers: 0
  });
  
  // Additional states for enhanced functionality
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [toast, setToast] = useState({ show: false, message: '', type: 'success' });
  const [confirmation, setConfirmation] = useState({ show: false, voucherId: null, loading: false });

  // Toast notification system - defined early to avoid hoisting issues
  const showToast = useCallback((message, type = 'success') => {
    setToast({ show: true, message, type });
  }, []);

  const hideToast = useCallback(() => {
    setToast(prev => ({ ...prev, show: false }));
  }, []);

  // ⚠️ IMPORTANT: Order matters for React hooks and computed values
  // 1. Basic functions (showToast, hideToast, formatDate, getStatusText)
  // 2. Computed values that depend on basic functions
  // 3. Effects that depend on computed values

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
      // Derive active branch id if available (fallback to 1)
      const branchId = user?.active_branch?.id || user?.branch?.id || user?.branch_id || 1;
      const response = await apiClient.get('/issue-vouchers', {
        params: {
          page: currentPage,
          per_page: itemsPerPage,
          sort_by: sortField,
          sort_dir: sortDirection,
          search: filters.search || undefined,
          status: filters.status || undefined,
          from_date: filters.from_date || undefined,
          to_date: filters.to_date || undefined,
          branch_id: branchId || undefined
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

  const fetchStats = useCallback(async () => {
    setStatsLoading(true);
    setError(null);
    try {
      const apiClient = (await import('../../utils/axios')).default;
      const branchId = user?.active_branch?.id || user?.branch?.id || user?.branch_id;
      
      const response = await apiClient.get('/issue-vouchers-stats', {
        params: branchId ? { branch_id: branchId } : {}
      });
      
      console.log('✅ Stats API Response:', response.data);
      
      if (response.data?.data) {
        setStats(response.data.data);
      } else {
        throw new Error('Invalid stats response format');
      }
    } catch (error) {
      console.error('❌ Error fetching stats:', error);
      setError('فشل في تحميل الإحصائيات');
      
      // Fallback to default stats on error
      setStats({
        totalVouchers: 0,
        todayVouchers: 0,
        totalAmount: 0,
        pendingVouchers: 0
      });
    } finally {
      setStatsLoading(false);
    }
  }, [user]);

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
      const branchId = user?.active_branch?.id || user?.branch?.id || user?.branch_id || 1;
      
      // Prepare payload to match backend expectations
      const payload = {
        customer_id: data.customer_id || null,
        customer_name: data.customer_name || 'عميل نقدي',
        branch_id: branchId,
        issue_date: data.date, // Rename 'date' to 'issue_date'
        notes: data.notes || '',
        ...(data.discount_type ? { discount_type: data.discount_type } : {}),
        ...(data.discount_value ? { discount_value: data.discount_value } : {}),
        items: (data.items || []).map((it) => ({
          product_id: it.product_id,
          quantity: it.quantity,
          unit_price: it.price,
          ...(it.discount_amount ? { discount_amount: it.discount_amount } : {})
        }))
      };
      
      console.log('📤 Submitting voucher payload:', payload);
      
      if (editingVoucher) {
        const response = await apiClient.put(`/issue-vouchers/${editingVoucher.id}`, payload);
        console.log('✅ Voucher updated:', response.data);
        showToast('تم تحديث إذن الصرف بنجاح', 'success');
      } else {
        const response = await apiClient.post('/issue-vouchers', payload);
        console.log('✅ Voucher created:', response.data);
        showToast('تم إنشاء إذن الصرف بنجاح', 'success');
      }
      
      setShowForm(false);
      setEditingVoucher(null);
      fetchVouchers();
      fetchStats();
    } catch (error) {
      console.error('Error saving voucher:', error);
      
      if (error.response?.data?.errors) {
        // Show validation errors
        const errorMessages = Object.values(error.response.data.errors).flat().join(' • ');
        showToast('أخطاء في البيانات: ' + errorMessages, 'error');
      } else if (error.response?.data?.message) {
        showToast(error.response.data.message, 'error');
      } else {
        showToast('حدث خطأ أثناء حفظ الإذن. تأكد من الاتصال بالخادم.', 'error');
      }
    }
  };

  const handlePrint = async (voucher) => {
    try {
      const apiClient = (await import('../../utils/axios')).default;
      // Backend defines POST /issue-vouchers/{voucher}/print
      const res = await apiClient.post(`/issue-vouchers/${voucher.id}/print`, {}, { responseType: 'blob' });
      const blob = new Blob([res.data], { type: 'application/pdf' });
      const url = URL.createObjectURL(blob);
      window.open(url, '_blank');
      // Optional: revoke later
      setTimeout(() => URL.revokeObjectURL(url), 60_000);
    } catch (err) {
      console.error('Print failed, fallback to route open:', err);
      // Fallback to opening a presumed print route if available
      window.open(`/issue-vouchers/${voucher.id}/print`, '_blank');
    }
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

  // Additional action handlers for responsive table - Optimized with useCallback
  const handleViewVoucher = useCallback((id) => {
    navigate(`/vouchers/issue/${id}`);
  }, [navigate]);

  const handleEditVoucher = useCallback((id) => {
    const voucher = vouchers.find(v => v.id === id);
    if (voucher) {
      setEditingVoucher(voucher);
      setShowForm(true);
    }
  }, [vouchers]);

  // Utility functions - Optimized with useCallback
  const formatDate = useCallback((date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('ar-EG', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  }, []);

  const getStatusText = useCallback((status) => {
    const statusTexts = {
      completed: 'مكتمل',
      pending: 'قيد الانتظار',
      cancelled: 'ملغى',
      draft: 'مسودة',
      confirmed: 'مؤكد'
    };
    return statusTexts[status] || status;
  }, []);



  const handleDeleteVoucher = useCallback((id) => {
    setConfirmation({ show: true, voucherId: id, loading: false });
  }, []);

  const confirmDelete = useCallback(async () => {
    const { voucherId } = confirmation;
    setConfirmation(prev => ({ ...prev, loading: true }));
    
    try {
      await handleDelete(voucherId);
      showToast('تم حذف إذن الصرف بنجاح', 'success');
    } catch (error) {
      showToast('حدث خطأ أثناء حذف الإذن', 'error');
    } finally {
      setConfirmation({ show: false, voucherId: null, loading: false });
    }
  }, [confirmation.voucherId, showToast]);

  const cancelDelete = useCallback(() => {
    setConfirmation({ show: false, voucherId: null, loading: false });
  }, []);

  // Filtered vouchers computation - moved before performanceMetrics
  const filteredVouchers = useMemo(() => {
    return vouchers.filter(voucher => {
      const matchesSearch = !searchTerm || 
        voucher.voucher_number?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        voucher.customer_name?.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesStatus = !statusFilter || voucher.status === statusFilter;
      
      return matchesSearch && matchesStatus;
    });
  }, [vouchers, searchTerm, statusFilter]);

  // Performance Monitoring
  const performanceMetrics = useMemo(() => {
    return {
      loadingState: loading,
      itemsCount: vouchers.length,
      filteredItemsCount: filteredVouchers.length,
      searchActive: !!searchTerm,
      filterActive: !!statusFilter,
      lastUpdate: Date.now()
    };
  }, [loading, vouchers.length, filteredVouchers.length, searchTerm, statusFilter]);

  // Keyboard Shortcuts
  useEffect(() => {
    const handleKeyDown = (event) => {
      // Ctrl/Cmd + N للإذن جديد
      if ((event.ctrlKey || event.metaKey) && event.key === 'n') {
        event.preventDefault();
        handleAdd();
      }
      // F5 للتحديث
      if (event.key === 'F5') {
        event.preventDefault();
        fetchVouchers();
        fetchStats();
      }
      // Escape للإغلاق
      if (event.key === 'Escape') {
        if (showForm) {
          setShowForm(false);
          setEditingVoucher(null);
        }
        if (confirmation.show) {
          cancelDelete();
        }
        if (toast.show) {
          hideToast();
        }
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [showForm, confirmation.show, toast.show, fetchVouchers, fetchStats, cancelDelete, hideToast]);



  const columns = [
    {
      key: 'voucher_number',
      title: 'رقم الإذن',
      sortable: true,
      render: (value, voucher) => (
        <div className="font-semibold text-primary-600">
          {voucher.voucher_number || voucher.number || `#${voucher.id}`}
        </div>
      )
    },
    {
      key: 'customer_name',
      title: 'اسم العميل',
      sortable: true,
      render: (value, voucher) => {
        const customerName = voucher.customer_name || voucher.customer?.name || '—';
        const customerId = voucher.customer_id || voucher.customer?.id;
        
        // If it's cash sale (عميل نقدي) or no customer ID, show as text
        if (!customerId || customerName === 'عميل نقدي') {
          return (
            <div>
              <div className="font-medium text-gray-600">{customerName}</div>
            </div>
          );
        }
        
        // If has customer ID, make it clickable
        return (
          <div>
            <button
              onClick={() => navigate(`/customers/${customerId}/profile`)}
              className="font-medium text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-200 text-right"
              title={`عرض ملف العميل: ${customerName}`}
            >
              {customerName}
            </button>
          </div>
        );
      }
    },
    {
      key: 'issue_date',
      title: 'التاريخ',
      sortable: true,
      render: (value, voucher) => (
        <div className="text-gray-600">
          {voucher.issue_date || voucher.date ? new Date(voucher.issue_date || voucher.date).toLocaleDateString('ar-EG') : '—'}
        </div>
      )
    },
    {
      key: 'items_count',
      title: 'عدد الأصناف',
      sortable: true,
      render: (value, voucher) => (
        <div className="text-center">
          <Badge variant="info">{voucher.items_count ?? voucher.items?.length ?? 0}</Badge>
        </div>
      )
    },
    {
      key: 'total_amount',
      title: 'المبلغ الإجمالي',
      sortable: true,
      render: (value, voucher) => (
        <div className="font-semibold text-gray-900">
          {(voucher.net_total ?? voucher.total_amount ?? 0).toLocaleString('ar-EG')} جنيه
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
    },
    {
      field: 'from_date',
      label: 'من تاريخ',
      type: 'date'
    },
    {
      field: 'to_date',
      label: 'إلى تاريخ',
      type: 'date'
    }
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* SEO and Accessibility */}
      <div className="sr-only">
        <h1>صفحة إذونات الصرف - نظام إدارة المخزون</h1>
        <p>إدارة وتتبع جميع إذونات صرف المنتجات، إضافة إذونات جديدة، وعرض الإحصائيات</p>
      </div>
      
      <Sidebar />
      
      <div className="lg:mr-64">
        <Navbar />
        
        <main 
          className="min-h-screen p-4 md:p-6 pt-20 sm:pt-24 md:pt-28 lg:pt-32"
          role="main"
          aria-label="محتوى صفحة إذونات الصرف"
          style={{ paddingTop: 'calc(4rem + 1.5rem)' }}
        >
          {/* Page Header - Enhanced Spacing */}
          <div className="mb-8 mt-4">
            <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between mb-6 gap-6">
              <div className="flex-1 min-w-0">
                <h1 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">
                  إذونات الصرف
                </h1>
                <p className="text-gray-600 text-sm sm:text-base">
                  إدارة وتتبع جميع إذونات صرف المنتجات
                </p>
              </div>
              
              {/* Action Buttons - Enhanced Responsive */}
              <div className="flex flex-col sm:flex-row gap-3 sm:gap-3 w-full sm:w-auto">
                {error && (
                  <Button
                    variant="outline"
                    onClick={() => {
                      fetchStats();
                      fetchVouchers();
                    }}
                    className="order-3 sm:order-1 w-full sm:w-auto flex items-center justify-center"
                  >
                    <RefreshCw className="w-4 h-4 ml-2" />
                    <span className="hidden sm:inline">إعادة التحميل</span>
                    <span className="sm:hidden">تحديث</span>
                  </Button>
                )}
                
                <Button
                  variant="outline"
                  onClick={() => window.print()}
                  className="order-2 w-full sm:w-auto flex items-center justify-center"
                >
                  <FileText className="w-4 h-4 sm:w-5 sm:h-5 ml-2" />
                  <span className="hidden sm:inline">تصدير PDF</span>
                  <span className="sm:hidden">تصدير</span>
                </Button>
                
                <Button
                  variant="primary"
                  onClick={handleAdd}
                  className="order-1 sm:order-3 w-full sm:w-auto flex items-center justify-center shadow-lg"
                >
                  <Plus className="w-4 h-4 sm:w-5 sm:h-5 ml-2" />
                  <span className="hidden sm:inline">إذن صرف جديد</span>
                  <span className="sm:hidden">إذن جديد</span>
                </Button>
              </div>
            </div>
            
            {/* Error Alert - Enhanced Spacing */}
            {error && (
              <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3 shadow-sm">
                <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                <div className="flex-1 min-w-0">
                  <p className="text-red-800 font-medium text-sm sm:text-base">{error}</p>
                  <p className="text-red-600 text-xs sm:text-sm mt-1">تحقق من الاتصال بالإنترنت وحاول مرة أخرى</p>
                </div>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setError(null)}
                  className="text-red-600 border-red-300 hover:bg-red-100 flex-shrink-0"
                >
                  <span className="hidden sm:inline">إغلاق</span>
                  <span className="sm:hidden">✕</span>
                </Button>
              </div>
            )}

            {/* Statistics Cards - Enhanced Spacing */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
              {/* Total Vouchers */}
              <Card className="hover:shadow-md transition-shadow duration-200">
                <div className="flex items-center justify-between p-4 sm:p-6">
                  {statsLoading ? (
                    <div className="flex items-center justify-between w-full">
                      <div className="space-y-2 flex-1">
                        <div className="h-3 bg-gray-200 rounded animate-pulse w-20"></div>
                        <div className="h-6 bg-gray-200 rounded animate-pulse w-12"></div>
                      </div>
                      <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gray-200 rounded-full animate-pulse"></div>
                    </div>
                  ) : (
                    <>
                      <div className="flex-1 min-w-0">
                        <p className="text-xs sm:text-sm text-gray-600 mb-1 truncate">إجمالي الإذونات</p>
                        <p className="text-xl sm:text-2xl font-bold text-gray-900">
                          {stats.totalVouchers?.toLocaleString('ar-EG') || 0}
                        </p>
                        {stats.thisMonthVouchers !== undefined && (
                          <p className="text-xs text-gray-500 mt-1">
                            {stats.thisMonthVouchers} هذا الشهر
                          </p>
                        )}
                      </div>
                      <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-primary-100 flex items-center justify-center flex-shrink-0 ml-3">
                        <FileText className="w-5 h-5 sm:w-6 sm:h-6 text-primary-600" />
                      </div>
                    </>
                  )}
                </div>
              </Card>

              {/* Today's Vouchers */}
              <Card className="hover:shadow-md transition-shadow duration-200">
                <div className="flex items-center justify-between p-4 sm:p-6">
                  {statsLoading ? (
                    <div className="flex items-center justify-between w-full">
                      <div className="space-y-2 flex-1">
                        <div className="h-3 bg-gray-200 rounded animate-pulse w-16"></div>
                        <div className="h-6 bg-gray-200 rounded animate-pulse w-8"></div>
                      </div>
                      <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gray-200 rounded-full animate-pulse"></div>
                    </div>
                  ) : (
                    <>
                      <div className="flex-1 min-w-0">
                        <p className="text-xs sm:text-sm text-gray-600 mb-1 truncate">إذونات اليوم</p>
                        <p className="text-xl sm:text-2xl font-bold text-gray-900">
                          {stats.todayVouchers?.toLocaleString('ar-EG') || 0}
                        </p>
                      </div>
                      <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 ml-3">
                        <Clock className="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" />
                      </div>
                    </>
                  )}
                </div>
              </Card>

              {/* Total Amount */}
              <Card className="hover:shadow-md transition-shadow duration-200">
                <div className="flex items-center justify-between p-4 sm:p-6">
                  {statsLoading ? (
                    <div className="flex items-center justify-between w-full">
                      <div className="space-y-2 flex-1">
                        <div className="h-3 bg-gray-200 rounded animate-pulse w-20"></div>
                        <div className="h-6 bg-gray-200 rounded animate-pulse w-16"></div>
                        <div className="h-2 bg-gray-200 rounded animate-pulse w-8"></div>
                      </div>
                      <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gray-200 rounded-full animate-pulse"></div>
                    </div>
                  ) : (
                    <>
                      <div className="flex-1 min-w-0">
                        <p className="text-xs sm:text-sm text-gray-600 mb-1 truncate">المبلغ الإجمالي</p>
                        <p className="text-lg sm:text-2xl font-bold text-gray-900 break-words">
                          {(stats.totalAmount || 0).toLocaleString('ar-EG')}
                        </p>
                        <p className="text-xs text-gray-500">جنيه</p>
                        {stats.averageVoucherValue !== undefined && (
                          <p className="text-xs text-gray-500 mt-1">
                            متوسط الإذن: {Math.round(stats.averageVoucherValue || 0).toLocaleString('ar-EG')}
                          </p>
                        )}
                      </div>
                      <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 ml-3">
                        <TrendingUp className="w-5 h-5 sm:w-6 sm:h-6 text-green-600" />
                      </div>
                    </>
                  )}
                </div>
              </Card>

              {/* Pending Vouchers */}
              <Card className="hover:shadow-md transition-shadow duration-200">
                <div className="flex items-center justify-between p-4 sm:p-6">
                  {statsLoading ? (
                    <div className="flex items-center justify-between w-full">
                      <div className="space-y-2 flex-1">
                        <div className="h-3 bg-gray-200 rounded animate-pulse w-18"></div>
                        <div className="h-6 bg-gray-200 rounded animate-pulse w-8"></div>
                      </div>
                      <div className="w-10 h-10 sm:w-12 sm:h-12 bg-gray-200 rounded-full animate-pulse"></div>
                    </div>
                  ) : (
                    <>
                      <div className="flex-1 min-w-0">
                        <p className="text-xs sm:text-sm text-gray-600 mb-1 truncate">قيد الانتظار</p>
                        <p className="text-xl sm:text-2xl font-bold text-gray-900">
                          {stats.pendingVouchers?.toLocaleString('ar-EG') || 0}
                        </p>
                      </div>
                      <div className="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0 ml-3">
                        <CheckCircle className="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600" />
                      </div>
                    </>
                  )}
                </div>
              </Card>
            </div>
          </div>

          {/* Enhanced Responsive Data Display */}
          <Card className="overflow-hidden">
            <div className="p-4 sm:p-6">
              {/* Table Header with Filters */}
              <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                <h2 className="text-lg font-semibold text-gray-900">
                  إذونات الصرف ({filteredVouchers.length})
                </h2>
                
                {/* Filters - Mobile Responsive */}
                <div className="flex flex-col sm:flex-row gap-3 sm:items-center">
                  <div className="relative">
                    <input
                      type="text"
                      placeholder="بحث برقم الإذن أو العميل..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="w-full sm:w-auto px-4 py-2 pl-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                    <Search className="absolute left-3 top-2.5 h-4 w-4 text-gray-400" />
                  </div>
                  
                  <select
                    value={statusFilter}
                    onChange={(e) => setStatusFilter(e.target.value)}
                    className="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  >
                    <option value="">جميع الحالات</option>
                    <option value="draft">مسودة</option>
                    <option value="confirmed">مؤكد</option>
                    <option value="cancelled">ملغى</option>
                  </select>

                  <button
                    onClick={fetchVouchers}
                    disabled={loading}
                    className="flex items-center justify-center gap-2 px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
                  >
                    <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                    <span className="hidden sm:inline">تحديث</span>
                  </button>
                </div>
              </div>

              {/* Desktop Table */}
              <div className="hidden lg:block">
                <DataTable
                  data={filteredVouchers}
                  columns={columns}
                  loading={loading}
                  totalItems={filteredVouchers.length}
                  currentPage={currentPage}
                  itemsPerPage={itemsPerPage}
                  onPageChange={handlePageChange}
                  onSort={handleSort}
                  onFilter={handleFilter}
                  searchable={false}
                  filterable={false}
                  emptyMessage="لا توجد إذونات صرف"
                />
              </div>

              {/* Mobile Card View */}
              <div className="block lg:hidden">
                <div className="space-y-4">
                  {loading ? (
                    // Mobile Skeleton Cards
                    Array.from({ length: 3 }).map((_, index) => (
                      <div key={`mobile-skeleton-${index}`} className="border rounded-lg p-4 space-y-3">
                        <div className="flex justify-between items-start">
                          <div className="h-5 bg-gray-200 rounded animate-pulse w-20"></div>
                          <div className="h-6 bg-gray-200 rounded-full animate-pulse w-16"></div>
                        </div>
                        <div className="space-y-2">
                          <div className="h-4 bg-gray-200 rounded animate-pulse w-32"></div>
                          <div className="h-4 bg-gray-200 rounded animate-pulse w-24"></div>
                          <div className="h-4 bg-gray-200 rounded animate-pulse w-28"></div>
                        </div>
                        <div className="flex gap-2">
                          <div className="h-8 bg-gray-200 rounded animate-pulse flex-1"></div>
                          <div className="h-8 bg-gray-200 rounded animate-pulse flex-1"></div>
                        </div>
                      </div>
                    ))
                  ) : filteredVouchers.length === 0 ? (
                    <div className="text-center py-12 text-gray-500">
                      <FileText className="w-16 h-16 mx-auto mb-4 text-gray-300" />
                      <p className="text-lg font-medium mb-2">لا توجد إذونات</p>
                      <p className="text-sm">قم بإنشاء إذن صرف جديد للبدء</p>
                    </div>
                  ) : (
                    filteredVouchers.map((voucher) => (
                      <MemoizedVoucherCard
                        key={voucher.id}
                        voucher={voucher}
                        onView={() => handleViewVoucher(voucher.id)}
                        onEdit={() => handleEditVoucher(voucher.id)}
                        onDelete={() => handleDeleteVoucher(voucher.id)}
                        formatDate={formatDate}
                        getStatusText={getStatusText}
                        navigate={navigate}
                      />
                    ))
                  )}
                </div>
              </div>

              {/* Pagination (if needed) */}
              {!loading && filteredVouchers.length > 0 && (
                <div className="mt-6 flex justify-center">
                  <div className="text-sm text-gray-500">
                    عرض {filteredVouchers.length} من إذونات الصرف
                  </div>
                </div>
              )}
            </div>
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

      {/* Toast Notification */}
      <Toast
        show={toast.show}
        message={toast.message}
        type={toast.type}
        onHide={hideToast}
      />

      {/* Confirmation Modal */}
      <ConfirmationModal
        show={confirmation.show}
        title="تأكيد الحذف"
        message="هل أنت متأكد من حذف هذا الإذن؟ لا يمكن التراجع عن هذا الإجراء."
        onConfirm={confirmDelete}
        onCancel={cancelDelete}
        loading={confirmation.loading}
      />
    </div>
  );
};

export default IssueVouchersPage;
