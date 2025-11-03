import { useState, useEffect, useCallback } from 'react';
import { Plus, Edit, Trash2, MapPin, Package, Building2, AlertCircle } from 'lucide-react';
import Sidebar from '../../components/organisms/Sidebar/Sidebar';
import Navbar from '../../components/organisms/Navbar/Navbar';
import DataTable from '../../components/molecules/DataTable/DataTable';
import BranchForm from '../../components/organisms/BranchForm/BranchForm';
import { Button, Badge, Alert, Card } from '../../components/atoms';
import branchService from '../../services/branchService';

const BranchesPage = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [branches, setBranches] = useState([]);
  const [loading, setLoading] = useState(true);
  const [totalItems, setTotalItems] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(10);
  const [sortField, setSortField] = useState('name');
  const [sortDirection, setSortDirection] = useState('asc');
  const [filters, setFilters] = useState({});
  
  // Form states
  const [showForm, setShowForm] = useState(false);
  const [editingBranch, setEditingBranch] = useState(null);
  const [formLoading, setFormLoading] = useState(false);
  
  // Delete confirmation
  const [deleteId, setDeleteId] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  
  // Error handling
  const [error, setError] = useState(null);

  // Table columns configuration
  const columns = [
    {
      key: 'id',
      title: 'المعرف',
      sortable: true,
      render: (value) => (
        <span className="font-mono text-sm font-medium text-gray-900">#{value}</span>
      )
    },
    {
      key: 'name',
      title: 'اسم الفرع/المخزن',
      sortable: true,
      filterable: true,
      render: (value, row) => (
        <div className="flex items-center gap-2">
          <Building2 className="w-4 h-4 text-gray-400" />
          <div>
            <div className="font-medium text-gray-900">{value}</div>
            {row.code && (
              <div className="text-xs text-gray-500 font-mono">#{row.code}</div>
            )}
          </div>
        </div>
      )
    },
    {
      key: 'code',
      title: 'الكود',
      sortable: true,
      filterable: true,
      render: (value) => (
        <span className="font-mono text-sm font-medium text-gray-700 bg-gray-100 px-2 py-1 rounded">
          {value || '-'}
        </span>
      )
    },
    {
      key: 'phone',
      title: 'الهاتف',
      sortable: true,
      render: (value) => (
        <span className="text-sm text-gray-600 font-mono" dir="ltr">
          {value || '-'}
        </span>
      )
    },
    {
      key: 'address',
      title: 'العنوان',
      sortable: true,
      filterable: true,
      render: (value) => (
        <div className="flex items-start gap-2">
          {value && <MapPin className="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" />}
          <span className="text-sm text-gray-600 line-clamp-2">
            {value || '-'}
          </span>
        </div>
      )
    },
    {
      key: 'product_stocks_count',
      title: 'عدد الأصناف',
      sortable: true,
      render: (value) => (
        <div className="flex items-center gap-2">
          <Package className="w-4 h-4 text-blue-500" />
          <span className="font-medium text-gray-900">
            {value || 0}
          </span>
        </div>
      )
    },
    {
      key: 'is_active',
      title: 'الحالة',
      sortable: true,
      render: (value) => (
        <Badge color={value ? 'success' : 'error'}>
          {value ? 'نشط' : 'غير نشط'}
        </Badge>
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
            onClick={() => handleEdit(row)}
            className="w-8 h-8 p-0"
            title="تعديل"
          >
            <Edit className="w-4 h-4" />
          </Button>
          
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setDeleteId(row.id)}
            className="w-8 h-8 p-0 text-red-600 hover:text-red-700 hover:bg-red-50"
            title="حذف"
            disabled={row.code && ['FAC', 'ATB', 'IMB'].includes(row.code)}
          >
            <Trash2 className="w-4 h-4" />
          </Button>
        </div>
      )
    }
  ];

  /**
   * Fetch branches from API
   */
  const fetchBranches = useCallback(async () => {
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

      const response = await branchService.getAll(params);
      
      setBranches(response.data || []);
      setTotalItems(response.meta?.total || 0);
    } catch (err) {
      console.error('Error fetching branches:', err);
      setError(err.response?.data?.message || 'حدث خطأ أثناء تحميل البيانات');
    } finally {
      setLoading(false);
    }
  }, [currentPage, itemsPerPage, sortField, sortDirection, filters]);

  // Fetch branches on mount and when dependencies change
  useEffect(() => {
    fetchBranches();
  }, [fetchBranches]);

  /**
   * Handle edit button click
   */
  const handleEdit = (branch) => {
    setEditingBranch(branch);
    setShowForm(true);
  };

  /**
   * Handle add new branch
   */
  const handleAdd = () => {
    setEditingBranch(null);
    setShowForm(true);
  };

  /**
   * Handle form submission
   */
  const handleFormSubmit = async (data) => {
    try {
      setFormLoading(true);
      setError(null);

      if (editingBranch) {
        await branchService.update(editingBranch.id, data);
      } else {
        await branchService.create(data);
      }

      setShowForm(false);
      setEditingBranch(null);
      fetchBranches();
    } catch (err) {
      console.error('Error saving branch:', err);
      throw err; // Re-throw to let form handle the error
    } finally {
      setFormLoading(false);
    }
  };

  /**
   * Handle form close
   */
  const handleFormClose = () => {
    setShowForm(false);
    setEditingBranch(null);
  };

  /**
   * Handle delete confirmation
   */
  const handleDelete = async () => {
    if (!deleteId) return;

    try {
      setDeleteLoading(true);
      setError(null);
      
      await branchService.delete(deleteId);
      
      setDeleteId(null);
      fetchBranches();
    } catch (err) {
      console.error('Error deleting branch:', err);
      setError(err.response?.data?.message || 'حدث خطأ أثناء حذف الفرع');
    } finally {
      setDeleteLoading(false);
    }
  };

  /**
   * Handle sort change
   */
  const handleSort = (field) => {
    if (sortField === field) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortDirection('asc');
    }
  };

  /**
   * Handle filter change
   */
  const handleFilterChange = (newFilters) => {
    setFilters(newFilters);
    setCurrentPage(1); // Reset to first page on filter change
  };

  /**
   * Handle page change
   */
  const handlePageChange = (page) => {
    setCurrentPage(page);
  };

  return (
    <div className="flex h-screen bg-gray-50" dir="rtl">
      {/* Sidebar */}
      <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />
      
      {/* Main Content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Navbar */}
        <Navbar onMenuClick={() => setSidebarOpen(!sidebarOpen)} />
        
        {/* Page Content */}
        <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50">
          <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
            {/* Page Header */}
            <div className="mb-6">
              <div className="flex items-center justify-between">
                <div>
                  <h1 className="text-2xl font-semibold text-gray-900">إدارة المخازن/الفروع</h1>
                  <p className="mt-1 text-sm text-gray-600">
                    عرض وإدارة جميع الفروع والمخازن في النظام
                  </p>
                </div>
                <Button
                  onClick={handleAdd}
                  className="flex items-center gap-2"
                >
                  <Plus className="w-4 h-4" />
                  إضافة فرع جديد
                </Button>
              </div>
            </div>

            {/* Error Alert */}
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

            {/* Data Table */}
            <Card>
              <DataTable
                columns={columns}
                data={branches}
                loading={loading}
                sortField={sortField}
                sortDirection={sortDirection}
                onSort={handleSort}
                onFilterChange={handleFilterChange}
                pagination={{
                  currentPage,
                  totalPages: Math.ceil(totalItems / itemsPerPage),
                  itemsPerPage,
                  totalItems,
                  onPageChange: handlePageChange
                }}
                emptyMessage="لا توجد فروع/مخازن"
                emptyDescription="ابدأ بإضافة فرع أو مخزن جديد"
              />
            </Card>
          </div>
        </main>
      </div>

      {/* Branch Form Modal */}
      {showForm && (
        <BranchForm
          branch={editingBranch}
          onSubmit={handleFormSubmit}
          onClose={handleFormClose}
          loading={formLoading}
        />
      )}

      {/* Delete Confirmation Modal */}
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
                  <p className="text-sm text-gray-600">هل أنت متأكد من حذف هذا الفرع؟</p>
                </div>
              </div>

              <Alert variant="warning" className="mb-4">
                <AlertCircle className="w-4 h-4" />
                <span className="text-sm">
                  سيتم حذف الفرع نهائياً ولن يمكن استرجاعه. تأكد من عدم وجود مخزون أو حركات مرتبطة به.
                </span>
              </Alert>

              <div className="flex gap-3 justify-end">
                <Button
                  variant="outline"
                  onClick={() => setDeleteId(null)}
                  disabled={deleteLoading}
                >
                  إلغاء
                </Button>
                <Button
                  variant="danger"
                  onClick={handleDelete}
                  loading={deleteLoading}
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

export default BranchesPage;
