import { useState, useEffect, useCallback } from 'react';
import { Plus, Edit, Trash2, CheckCircle, XCircle, Send, Eye, AlertCircle } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import Sidebar from '../../components/organisms/Sidebar/Sidebar';
import Navbar from '../../components/organisms/Navbar/Navbar';
import DataTable from '../../components/molecules/DataTable/DataTable';
import { Button, Badge, Alert, Card } from '../../components/atoms';
import inventoryCountService from '../../services/inventoryCountService';

const InventoryCountsPage = () => {
  const navigate = useNavigate();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [counts, setCounts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [totalItems, setTotalItems] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(10);
  const [sortField, setSortField] = useState('created_at');
  const [sortDirection, setSortDirection] = useState('desc');
  const [filters, setFilters] = useState({});
  const [error, setError] = useState(null);
  
  // Action states
  const [deleteId, setDeleteId] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);

  // Table columns
  const columns = [
    {
      key: 'code',
      title: 'رقم الجرد',
      sortable: true,
      render: (value) => (
        <span className="font-mono text-sm font-medium text-gray-900">{value}</span>
      )
    },
    {
      key: 'branch',
      title: 'الفرع',
      render: (value) => (
        <span className="text-sm text-gray-900">{value?.name || '-'}</span>
      )
    },
    {
      key: 'count_date',
      title: 'تاريخ الجرد',
      sortable: true,
      render: (value) => (
        <span className="text-sm text-gray-600">
          {new Date(value).toLocaleDateString('ar-EG')}
        </span>
      )
    },
    {
      key: 'items_count',
      title: 'عدد الأصناف',
      sortable: true,
      render: (value) => (
        <span className="font-medium text-gray-900">{value || 0}</span>
      )
    },
    {
      key: 'status',
      title: 'الحالة',
      sortable: true,
      render: (value) => {
        const statusConfig = {
          DRAFT: { color: 'gray', label: 'مسودة' },
          PENDING: { color: 'warning', label: 'قيد المراجعة' },
          APPROVED: { color: 'success', label: 'معتمد' },
          REJECTED: { color: 'error', label: 'مرفوض' }
        };
        const config = statusConfig[value] || statusConfig.DRAFT;
        return <Badge color={config.color}>{config.label}</Badge>;
      }
    },
    {
      key: 'creator',
      title: 'المنشئ',
      render: (value) => (
        <span className="text-sm text-gray-600">{value?.name || '-'}</span>
      )
    },
    {
      key: 'actions',
      title: 'الإجراءات',
      render: (_, row) => (
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => navigate(`/inventory-counts/${row.id}`)}
            className="w-8 h-8 p-0"
            title="عرض"
          >
            <Eye className="w-4 h-4" />
          </Button>
          
          {row.status === 'DRAFT' && (
            <>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => navigate(`/inventory-counts/${row.id}/edit`)}
                className="w-8 h-8 p-0"
                title="تعديل"
              >
                <Edit className="w-4 h-4" />
              </Button>
              
              <Button
                variant="ghost"
                size="sm"
                onClick={() => handleSubmit(row.id)}
                className="w-8 h-8 p-0 text-blue-600"
                title="إرسال للاعتماد"
              >
                <Send className="w-4 h-4" />
              </Button>
              
              <Button
                variant="ghost"
                size="sm"
                onClick={() => setDeleteId(row.id)}
                className="w-8 h-8 p-0 text-red-600"
                title="حذف"
              >
                <Trash2 className="w-4 h-4" />
              </Button>
            </>
          )}
          
          {row.status === 'PENDING' && (
            <>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => handleApprove(row.id)}
                className="w-8 h-8 p-0 text-green-600"
                title="اعتماد"
              >
                <CheckCircle className="w-4 h-4" />
              </Button>
              
              <Button
                variant="ghost"
                size="sm"
                onClick={() => handleReject(row.id)}
                className="w-8 h-8 p-0 text-red-600"
                title="رفض"
              >
                <XCircle className="w-4 h-4" />
              </Button>
            </>
          )}
        </div>
      )
    }
  ];

  const fetchCounts = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      
      const params = {
        page: currentPage,
        per_page: itemsPerPage,
        sort_by: sortField,
        sort_direction: sortDirection,
        ...filters
      };

      const response = await inventoryCountService.getAll(params);
      setCounts(response.data || []);
      setTotalItems(response.meta?.total || 0);
    } catch (err) {
      console.error('Error fetching counts:', err);
      setError(err.response?.data?.message || 'حدث خطأ أثناء تحميل البيانات');
    } finally {
      setLoading(false);
    }
  }, [currentPage, itemsPerPage, sortField, sortDirection, filters]);

  useEffect(() => {
    fetchCounts();
  }, [fetchCounts]);

  const handleSubmit = async (id) => {
    if (!confirm('هل تريد إرسال هذا الجرد للاعتماد؟')) return;
    
    try {
      setActionLoading(true);
      await inventoryCountService.submit(id);
      fetchCounts();
    } catch (err) {
      setError(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setActionLoading(false);
    }
  };

  const handleApprove = async (id) => {
    if (!confirm('هل تريد اعتماد هذا الجرد؟ سيتم تسوية المخزون تلقائياً.')) return;
    
    try {
      setActionLoading(true);
      await inventoryCountService.approve(id);
      fetchCounts();
    } catch (err) {
      setError(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setActionLoading(false);
    }
  };

  const handleReject = async (id) => {
    const reason = prompt('الرجاء إدخال سبب الرفض:');
    if (!reason) return;
    
    try {
      setActionLoading(true);
      await inventoryCountService.reject(id, reason);
      fetchCounts();
    } catch (err) {
      setError(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteId) return;

    try {
      setActionLoading(true);
      await inventoryCountService.delete(deleteId);
      setDeleteId(null);
      fetchCounts();
    } catch (err) {
      setError(err.response?.data?.message || 'حدث خطأ أثناء الحذف');
    } finally {
      setActionLoading(false);
    }
  };

  return (
    <div className="flex h-screen bg-gray-50" dir="rtl">
      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />
      
      <div className="flex-1 flex flex-col overflow-hidden">
        <Navbar onMenuClick={() => setSidebarOpen(!sidebarOpen)} />
        
        <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50">
          <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div className="mb-6">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-2xl font-semibold text-gray-900">جرد المخزون</h1>
                  <p className="mt-1 text-sm text-gray-600">
                    إنشاء ومتابعة عمليات الجرد الدوري
                  </p>
                </div>
                <Button
                  onClick={() => navigate('/inventory-counts/create')}
                  className="flex items-center gap-2"
                >
                  <Plus className="w-4 h-4" />
                  جرد جديد
                </Button>
              </div>
            </div>

            {error && (
              <Alert variant="error" className="mb-6" onClose={() => setError(null)}>
                <div className="flex items-start gap-2">
                  <AlertCircle className="w-5 h-5 flex-shrink-0" />
                  <div>
                    <div className="font-medium">خطأ</div>
                    <div className="text-sm">{error}</div>
                  </div>
                </div>
              </Alert>
            )}

            <Card>
              <DataTable
                columns={columns}
                data={counts}
                loading={loading}
                sortField={sortField}
                sortDirection={sortDirection}
                onSort={(field) => {
                  if (sortField === field) {
                    setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
                  } else {
                    setSortField(field);
                    setSortDirection('asc');
                  }
                }}
                onFilterChange={setFilters}
                pagination={{
                  currentPage,
                  totalPages: Math.ceil(totalItems / itemsPerPage),
                  itemsPerPage,
                  totalItems,
                  onPageChange: setCurrentPage
                }}
                emptyMessage="لا توجد عمليات جرد"
                emptyDescription="ابدأ بإنشاء عملية جرد جديدة"
              />
            </Card>
          </div>
        </main>
      </div>

      {deleteId && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <Card className="max-w-md w-full mx-4">
            <div className="p-6">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                  <Trash2 className="w-6 h-6 text-red-600" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">تأكيد الحذف</h3>
                  <p className="text-sm text-gray-600">هل أنت متأكد من حذف هذا الجرد؟</p>
                </div>
              </div>

              <div className="flex gap-3 justify-end">
                <Button
                  variant="outline"
                  onClick={() => setDeleteId(null)}
                  disabled={actionLoading}
                >
                  إلغاء
                </Button>
                <Button
                  variant="danger"
                  onClick={handleDelete}
                  loading={actionLoading}
                >
                  نعم، احذف
                </Button>
              </div>
            </div>
          </Card>
        </div>
      )}
    </div>
  );
};

export default InventoryCountsPage;
