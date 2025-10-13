import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Sidebar, Navbar, CustomerForm } from '../../components/organisms';
import { DataTable } from '../../components/molecules';
import { Button, Badge, Card } from '../../components/atoms';
import { Plus, Users, UserCheck, UserX, TrendingUp, Eye } from 'lucide-react';
import apiClient from '../../utils/axios';

const CustomersPage = () => {
  const navigate = useNavigate();
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [totalItems, setTotalItems] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(10);
  const [sortField, setSortField] = useState('name');
  const [sortDirection, setSortDirection] = useState('asc');
  const [filters, setFilters] = useState({
    search: '',
    is_active: '',
    type: ''
  });
  const [showForm, setShowForm] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState(null);
  const [deleteId, setDeleteId] = useState(null);

  // Statistics
  const [stats, setStats] = useState({
    totalCustomers: 0,
    activeCustomers: 0,
    inactiveCustomers: 0,
    wholesaleCustomers: 0
  });

  useEffect(() => {
    fetchCustomers();
  }, [currentPage, sortField, sortDirection, filters]);

  const fetchCustomers = async () => {
    setLoading(true);
    
    // Debug: Check auth token
    const token = localStorage.getItem('token');
    console.log('Token exists:', !!token);
    console.log('Token:', token ? token.substring(0, 20) + '...' : 'NO TOKEN');
    
    try {
      // Clean filters - only send non-empty values
      const cleanFilters = Object.entries(filters).reduce((acc, [key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
          acc[key] = value;
        }
        return acc;
      }, {});

      console.log('🔍 Sending params:', {
        page: currentPage,
        per_page: itemsPerPage,
        sort_by: sortField,
        sort_order: sortDirection,
        ...cleanFilters
      });

      const response = await apiClient.get('/customers', {
        params: {
          page: currentPage,
          per_page: itemsPerPage,
          sort_by: sortField,
          sort_order: sortDirection,
          ...cleanFilters
        }
      });

      console.log('Customers API Response:', response.data);
      console.log('Data array:', response.data.data);
      console.log('Data length:', response.data.data?.length);
      
      if (!response.data.data || response.data.data.length === 0) {
        console.warn('⚠️ No customers returned from API');
        console.log('Full response:', JSON.stringify(response.data, null, 2));
      }
      
      setCustomers(response.data.data);
      setTotalItems(response.data.meta?.total || response.data.data.length);
      
      // Calculate stats
      const total = response.data.meta?.total || response.data.data.length;
      const active = response.data.data.filter(c => c.is_active).length;
      const wholesale = response.data.data.filter(c => c.type === 'wholesale').length;
      
      setStats({
        totalCustomers: total,
        activeCustomers: active,
        inactiveCustomers: total - active,
        wholesaleCustomers: wholesale
      });
    } catch (error) {
      console.error('Error fetching customers:', error);
      if (error.response) {
        console.error('Response data:', error.response.data);
        console.error('Response status:', error.response.status);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleAdd = () => {
    setEditingCustomer(null);
    setShowForm(true);
  };

  const handleEdit = (customer) => {
    setEditingCustomer(customer);
    setShowForm(true);
  };

  const handleViewProfile = (customer) => {
    navigate(`/customers/${customer.id}/profile`);
  };

  const handleDelete = async (id) => {
    if (!window.confirm('هل أنت متأكد من حذف هذا العميل؟')) {
      return;
    }

    try {
      await apiClient.delete(`/customers/${id}`);
      fetchCustomers();
    } catch (error) {
      console.error('Error deleting customer:', error);
      alert('حدث خطأ أثناء حذف العميل');
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

  const getTypeBadge = (type) => {
    return type === 'wholesale' ? (
      <Badge variant="info">جملة</Badge>
    ) : (
      <Badge variant="default">قطاعي</Badge>
    );
  };

  const getStatusBadge = (isActive) => {
    return isActive ? (
      <Badge variant="success">نشط</Badge>
    ) : (
      <Badge variant="error">غير نشط</Badge>
    );
  };

  const getBalanceBadge = (balance) => {
    if (balance > 0) {
      return <Badge variant="success">+{balance.toFixed(2)} جنيه</Badge>;
    } else if (balance < 0) {
      return <Badge variant="error">{balance.toFixed(2)} جنيه</Badge>;
    } else {
      return <Badge variant="default">0.00 جنيه</Badge>;
    }
  };

  const columns = [
    {
      key: 'code',
      title: 'الكود',
      sortable: true,
      render: (value) => (
        <span className="font-mono text-sm font-medium text-gray-900">{value}</span>
      )
    },
    {
      key: 'name',
      title: 'اسم العميل',
      sortable: true,
      render: (value, row) => (
        <div>
          <div className="font-medium text-gray-900">{value}</div>
          {row.phone && (
            <div className="text-xs text-gray-500" dir="ltr">{row.phone}</div>
          )}
        </div>
      )
    },
    {
      key: 'type',
      title: 'النوع',
      sortable: true,
      render: (value) => getTypeBadge(value)
    },
    {
      key: 'address',
      title: 'العنوان',
      render: (value) => (
        <span className="text-sm text-gray-600">{value || '-'}</span>
      )
    },
    {
      key: 'balance',
      title: 'الرصيد',
      sortable: true,
      render: (value) => getBalanceBadge(value || 0)
    },
    {
      key: 'is_active',
      title: 'الحالة',
      sortable: true,
      render: (value) => getStatusBadge(value)
    },
    {
      key: 'actions',
      title: 'الإجراءات',
      render: (value, row) => (
        <div className="flex gap-2 justify-end">
          <Button
            variant="primary"
            size="sm"
            onClick={() => handleViewProfile(row)}
            className="flex items-center gap-1"
          >
            <Eye className="w-4 h-4" />
            عرض الملف
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => handleEdit(row)}
          >
            تعديل
          </Button>
          <Button
            variant="danger"
            size="sm"
            onClick={() => handleDelete(row.id)}
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
            <h1 className="text-3xl font-bold text-gray-900 mb-2">إدارة العملاء</h1>
            <p className="text-gray-600">إضافة وتعديل وعرض بيانات العملاء</p>
          </div>

          {/* Statistics Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <Card>
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">إجمالي العملاء</p>
                  <p className="text-2xl font-bold text-gray-900 mt-1">{stats.totalCustomers}</p>
                </div>
                <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                  <Users className="w-6 h-6 text-blue-600" />
                </div>
              </div>
            </Card>

            <Card>
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">العملاء النشطون</p>
                  <p className="text-2xl font-bold text-green-600 mt-1">{stats.activeCustomers}</p>
                </div>
                <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                  <UserCheck className="w-6 h-6 text-green-600" />
                </div>
              </div>
            </Card>

            <Card>
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">العملاء غير النشطين</p>
                  <p className="text-2xl font-bold text-red-600 mt-1">{stats.inactiveCustomers}</p>
                </div>
                <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                  <UserX className="w-6 h-6 text-red-600" />
                </div>
              </div>
            </Card>

            <Card>
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">عملاء الجملة</p>
                  <p className="text-2xl font-bold text-purple-600 mt-1">{stats.wholesaleCustomers}</p>
                </div>
                <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                  <TrendingUp className="w-6 h-6 text-purple-600" />
                </div>
              </div>
            </Card>
          </div>

          {/* Actions Bar */}
          <div className="mb-6 flex items-center justify-between">
            <div className="flex gap-3">
              <select
                value={filters.is_active}
                onChange={(e) => handleFilter('is_active', e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="">كل الحالات</option>
                <option value="1">نشط</option>
                <option value="0">غير نشط</option>
              </select>

              <select
                value={filters.type}
                onChange={(e) => handleFilter('type', e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="">كل الأنواع</option>
                <option value="retail">قطاعي</option>
                <option value="wholesale">جملة</option>
              </select>
            </div>

            <Button
              onClick={handleAdd}
              icon={Plus}
            >
              إضافة عميل جديد
            </Button>
          </div>

          {/* Customers Table */}
          <Card>
            <DataTable
              data={customers}
              columns={columns}
              loading={loading}
              totalItems={totalItems}
              currentPage={currentPage}
              itemsPerPage={itemsPerPage}
              onPageChange={handlePageChange}
              onSort={handleSort}
              onFilter={handleFilter}
              searchable
              searchPlaceholder="ابحث عن عميل..."
              emptyMessage="لا يوجد عملاء"
            />
          </Card>
        </main>
      </div>

      {/* Customer Form Modal */}
      <CustomerForm
        isOpen={showForm}
        onClose={() => {
          setShowForm(false);
          setEditingCustomer(null);
        }}
        onSuccess={fetchCustomers}
        editingCustomer={editingCustomer}
      />
    </div>
  );
};

export default CustomersPage;
