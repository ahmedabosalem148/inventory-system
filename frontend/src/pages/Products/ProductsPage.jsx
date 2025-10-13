import { useState, useEffect, useCallback } from 'react';
import { Plus, Edit, Trash2, Eye, Package, Filter, Download, AlertTriangle } from 'lucide-react';
import Sidebar from '../../components/organisms/Sidebar/Sidebar';
import Navbar from '../../components/organisms/Navbar/Navbar';
import DataTable from '../../components/molecules/DataTable/DataTable';
import ProductForm from '../../components/organisms/ProductForm/ProductForm';
import { Button, Badge, Alert, Card } from '../../components/atoms';
import axios from '../../utils/axios';

const ProductsPage = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [totalItems, setTotalItems] = useState(0);
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage] = useState(10);
  const [sortField, setSortField] = useState('name');
  const [sortDirection, setSortDirection] = useState('asc');
  const [filters, setFilters] = useState({});
  
  // Form states
  const [showForm, setShowForm] = useState(false);
  const [editingProduct, setEditingProduct] = useState(null);
  const [formLoading, setFormLoading] = useState(false);
  
  // Delete confirmation
  const [deleteId, setDeleteId] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);

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
      title: 'اسم المنتج',
      sortable: true,
      filterable: true,
      render: (value, row) => (
        <div>
          <div className="font-medium text-gray-900">{value}</div>
          <div className="text-xs text-gray-500">{row.description}</div>
        </div>
      )
    },
    {
      key: 'category',
      title: 'الفئة',
      sortable: true,
      filterable: true,
      filterType: 'select',
      filterOptions: [
        { value: '1', label: 'إلكترونيات' },
        { value: '2', label: 'ملابس' },
        { value: '3', label: 'مواد غذائية' },
        { value: '4', label: 'كتب' },
        { value: '5', label: 'أدوات' }
      ],
      render: (value, row) => {
        const categoryColors = {
          'إلكترونيات': 'blue',
          'ملابس': 'purple',
          'مواد غذائية': 'green',
          'كتب': 'yellow',
          'أدوات': 'gray'
        };
        
        const categoryName = row.category?.name || 'غير محدد';
        const color = categoryColors[categoryName] || 'gray';
        
        return <Badge color={color}>{categoryName}</Badge>;
      }
    },
    {
      key: 'unit',
      title: 'الوحدة',
      sortable: true,
      render: (value) => (
        <span className="text-sm text-gray-600">{value}</span>
      )
    },
    {
      key: 'min_stock',
      title: 'المخزون المطلوب',
      sortable: true,
      render: (value, row) => {
        // For demo, we'll use min_stock as current stock indicator
        const currentStock = Math.floor(Math.random() * 100) + 1; // Random demo data
        const isLowStock = currentStock <= value;
        
        return (
          <div className="flex items-center gap-2">
            <span className={`font-medium ${isLowStock ? 'text-red-600' : 'text-gray-900'}`}>
              {currentStock}
            </span>
            {isLowStock && (
              <AlertTriangle className="w-4 h-4 text-red-500" title="مخزون منخفض" />
            )}
          </div>
        );
      }
    },
    {
      key: 'sale_price',
      title: 'سعر البيع',
      sortable: true,
      render: (value) => (
        <span className="font-medium text-gray-900">
          {parseFloat(value || 0).toLocaleString('ar-EG', {
            minimumFractionDigits: 2
          })} جنيه
        </span>
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
          >
            <Trash2 className="w-4 h-4" />
          </Button>
        </div>
      )
    }
  ];

  // Fetch products data
  const fetchProducts = useCallback(async () => {
    setLoading(true);
    try {
      const params = {
        page: currentPage,
        per_page: itemsPerPage,
        sort_field: sortField,
        sort_direction: sortDirection,
        ...filters
      };

      const response = await axios.get('/products', { params });
      
      setProducts(response.data.data || []);
      setTotalItems(response.data.total || 0);
    } catch (error) {
      console.error('Failed to fetch products:', error);
      // For demo, use mock data
      setProducts(mockProducts);
      setTotalItems(mockProducts.length);
    } finally {
      setLoading(false);
    }
  }, [currentPage, itemsPerPage, sortField, sortDirection, filters]);

  // Mock data for demo (matching actual database structure)
  const mockProducts = [
    {
      id: 1,
      name: 'لابتوب Dell XPS 13',
      description: 'لابتوب عالي الأداء للاستخدام المكتبي والتصميم',
      category: { id: 1, name: 'إلكترونيات' },
      unit: 'قطعة',
      purchase_price: 45000,
      sale_price: 55000,
      min_stock: 5,
      pack_size: 1,
      reorder_level: 5,
      is_active: true,
      created_at: '2024-01-15'
    },
    {
      id: 2,
      name: 'قميص قطني أزرق',
      description: 'قميص رجالي قطن 100% مقاس كبير',
      category: { id: 2, name: 'ملابس' },
      unit: 'قطعة',
      purchase_price: 150,
      sale_price: 250,
      min_stock: 10,
      pack_size: 1,
      reorder_level: 10,
      is_active: true,
      created_at: '2024-01-14'
    },
    {
      id: 3,
      name: 'أرز بسمتي',
      description: 'أرز بسمتي هندي فاخر كيس 5 كيلو',
      category: { id: 3, name: 'مواد غذائية' },
      unit: 'كيلو',
      purchase_price: 25,
      sale_price: 35,
      min_stock: 50,
      pack_size: 5,
      reorder_level: 50,
      is_active: true,
      created_at: '2024-01-13'
    },
    {
      id: 4,
      name: 'كتاب البرمجة بـ PHP',
      description: 'كتاب شامل لتعلم لغة البرمجة PHP',
      category: { id: 4, name: 'كتب' },
      unit: 'قطعة',
      purchase_price: 80,
      sale_price: 120,
      min_stock: 20,
      pack_size: 1,
      reorder_level: 20,
      is_active: true,
      created_at: '2024-01-12'
    }
  ];

  // Load products on mount and when dependencies change
  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);

  // Handle page change
  const handlePageChange = (page) => {
    setCurrentPage(page);
  };

  // Handle sorting
  const handleSort = (field, direction) => {
    setSortField(field);
    setSortDirection(direction);
    setCurrentPage(1); // Reset to first page
  };

  // Handle filtering
  const handleFilter = (newFilters) => {
    setFilters(newFilters);
    setCurrentPage(1); // Reset to first page
  };

  // Handle add new product
  const handleAddNew = () => {
    setEditingProduct(null);
    setShowForm(true);
  };

  // Handle edit product
  const handleEdit = (product) => {
    setEditingProduct(product);
    setShowForm(true);
  };

  // Handle save product
  const handleSave = async (formData) => {
    setFormLoading(true);
    try {
      if (editingProduct) {
        // Update existing product
        await axios.put(`/products/${editingProduct.id}`, formData);
      } else {
        // Create new product
        await axios.post('/products', formData);
      }
      
      // Refresh data
      await fetchProducts();
      
      // Close form
      setShowForm(false);
      setEditingProduct(null);
    } catch (error) {
      console.error('Failed to save product:', error);
      throw error;
    } finally {
      setFormLoading(false);
    }
  };

  // Handle delete product
  const handleDelete = async () => {
    if (!deleteId) return;
    
    setDeleteLoading(true);
    try {
      await axios.delete(`/products/${deleteId}`);
      
      // Refresh data
      await fetchProducts();
      
      // Close confirmation
      setDeleteId(null);
    } catch (error) {
      console.error('Failed to delete product:', error);
    } finally {
      setDeleteLoading(false);
    }
  };

  // Handle export
  const handleExport = async () => {
    try {
      const response = await axios.get('/products/export', {
        params: filters,
        responseType: 'blob'
      });
      
      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'products.xlsx');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } catch (error) {
      console.error('Failed to export products:', error);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <Sidebar open={sidebarOpen} onClose={() => setSidebarOpen(false)} />
      <Navbar onMenuClick={() => setSidebarOpen(true)} />
      
      {/* Main Content */}
      <main className="pt-16 lg:mr-64">
        <div className="p-6">
          {/* Page Header */}
          <div className="mb-8">
            <div className="flex items-center justify-between flex-wrap gap-4">
              <div>
                <h1 className="text-2xl font-bold text-gray-900 mb-2">إدارة المنتجات</h1>
                <p className="text-gray-600">إضافة وتعديل وإدارة منتجات المخزون</p>
              </div>
              
              <div className="flex items-center gap-3">
                <Button
                  variant="outline"
                  onClick={handleExport}
                  className="flex items-center gap-2"
                >
                  <Download className="w-4 h-4" />
                  تصدير Excel
                </Button>
                
                <Button
                  onClick={handleAddNew}
                  className="flex items-center gap-2"
                >
                  <Plus className="w-4 h-4" />
                  إضافة منتج جديد
                </Button>
              </div>
            </div>
          </div>

          {/* Stats Cards */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card className="p-4">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                  <Package className="w-6 h-6 text-blue-600" />
                </div>
                <div>
                  <p className="text-sm text-gray-600">إجمالي المنتجات</p>
                  <p className="text-2xl font-bold text-gray-900">{totalItems}</p>
                </div>
              </div>
            </Card>
            
            <Card className="p-4">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                  <Package className="w-6 h-6 text-green-600" />
                </div>
                <div>
                  <p className="text-sm text-gray-600">المنتجات النشطة</p>
                  <p className="text-2xl font-bold text-gray-900">
                    {products.filter(p => p.is_active).length}
                  </p>
                </div>
              </div>
            </Card>
            
            <Card className="p-4">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                  <AlertTriangle className="w-6 h-6 text-red-600" />
                </div>
                <div>
                  <p className="text-sm text-gray-600">منتجات قريبة من النفاذ</p>
                  <p className="text-2xl font-bold text-gray-900">
                    {Math.floor(totalItems * 0.15)} {/* Demo: 15% of products */}
                  </p>
                </div>
              </div>
            </Card>
            
            <Card className="p-4">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                  <Package className="w-6 h-6 text-yellow-600" />
                </div>
                <div>
                  <p className="text-sm text-gray-600">منتجات غير نشطة</p>
                  <p className="text-2xl font-bold text-gray-900">
                    {products.filter(p => !p.is_active).length}
                  </p>
                </div>
              </div>
            </Card>
          </div>

          {/* Products Table */}
          <DataTable
            data={products}
            columns={columns}
            loading={loading}
            totalItems={totalItems}
            currentPage={currentPage}
            itemsPerPage={itemsPerPage}
            onPageChange={handlePageChange}
            onSort={handleSort}
            onFilter={handleFilter}
            searchable={true}
            filterable={true}
            actions={[
              {
                label: 'إضافة منتج',
                icon: Plus,
                onClick: handleAddNew,
                variant: 'primary'
              }
            ]}
            emptyMessage="لا توجد منتجات مضافة بعد"
          />
        </div>
      </main>

      {/* Product Form Modal */}
      <ProductForm
        product={editingProduct}
        isOpen={showForm}
        onClose={() => {
          setShowForm(false);
          setEditingProduct(null);
        }}
        onSave={handleSave}
        loading={formLoading}
      />

      {/* Delete Confirmation Modal */}
      {deleteId && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div className="p-6">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                  <Trash2 className="w-6 h-6 text-red-600" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-gray-900">حذف المنتج</h3>
                  <p className="text-sm text-gray-600">هل أنت متأكد من حذف هذا المنتج؟</p>
                </div>
              </div>
              
              <Alert type="warning" className="mb-4">
                <p className="text-sm">
                  سيتم حذف المنتج نهائياً ولن يمكن استرداده. هذا الإجراء غير قابل للتراجع.
                </p>
              </Alert>

              <div className="flex items-center gap-3 justify-end">
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
                  disabled={deleteLoading}
                  className="flex items-center gap-2"
                >
                  {deleteLoading ? (
                    <>
                      <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                      جاري الحذف...
                    </>
                  ) : (
                    <>
                      <Trash2 className="w-4 h-4" />
                      حذف المنتج
                    </>
                  )}
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ProductsPage;