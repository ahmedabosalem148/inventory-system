# 📋 Project Tasks - Part 2: Core Features
## Products, Vouchers, Customers, Payments

**تاريخ الإنشاء:** 15 أكتوبر 2025  
**Dependencies:** Part 1 مكتمل ✅  
**المدة المتوقعة:** 4 أسابيع (160 ساعة)

---

## 📦 Phase 2: Core Business Features

### نظرة عامة

Part 2 يغطي الـ **Core business logic** للنظام:
- إدارة المنتجات الكاملة
- أذونات الصرف والإرجاع
- إدارة العملاء ودفتر الحسابات
- المدفوعات والشيكات

---

## 🏷️ Module 1: Products Management (5 صفحات)

### ✅ TASK-201: Products List Page
**المدة:** 2 أيام (12 ساعة)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-105  
**الحالة:** ⏳ Pending

#### الهدف
صفحة قائمة المنتجات مع Search, Filter, Sort, Pagination

#### Development

```typescript
// src/features/products/ProductsListPage.tsx
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Plus, Download, Search } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { DataTable } from '@/components/shared/DataTable';
import { Badge } from '@/components/ui/Badge';
import axios from '@/app/axios';
import { Link } from '@tanstack/react-router';

export function ProductsListPage() {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['products', search, page],
    queryFn: async () => {
      const { data } = await axios.get('/products', {
        params: { search, page, per_page: 10 },
      });
      return data;
    },
  });

  const columns = [
    { key: 'sku', header: 'الكود' },
    { key: 'name', header: 'الاسم' },
    { 
      key: 'category', 
      header: 'الفئة',
      render: (product: any) => product.category?.name || '-',
    },
    { key: 'unit', header: 'الوحدة' },
    {
      key: 'pack_size',
      header: 'حجم العبوة',
      render: (product: any) => product.pack_size ? `${product.pack_size} وحدة` : '-',
    },
    {
      key: 'is_active',
      header: 'الحالة',
      render: (product: any) => (
        <Badge variant={product.is_active ? 'success' : 'secondary'}>
          {product.is_active ? 'نشط' : 'غير نشط'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (product: any) => (
        <div className="flex gap-2">
          <Link to={`/products/${product.id}`}>
            <Button size="sm">عرض</Button>
          </Link>
          <Link to={`/products/${product.id}/edit`}>
            <Button size="sm" variant="secondary">تعديل</Button>
          </Link>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">المنتجات</h1>
          <p className="text-gray-600">إدارة كتالوج المنتجات</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" leftIcon={<Download />}>
            تصدير Excel
          </Button>
          <Link to="/products/new">
            <Button leftIcon={<Plus />}>
              إضافة منتج
            </Button>
          </Link>
        </div>
      </div>

      {/* Filters */}
      <div className="flex gap-4">
        <div className="flex-1">
          <Input
            placeholder="بحث بالاسم أو الكود..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            leftIcon={<Search />}
          />
        </div>
        {/* More filters here */}
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white p-4 rounded-lg border">
          <p className="text-sm text-gray-600">إجمالي المنتجات</p>
          <p className="text-2xl font-bold">{data?.meta?.total || 0}</p>
        </div>
        <div className="bg-white p-4 rounded-lg border">
          <p className="text-sm text-gray-600">منتجات نشطة</p>
          <p className="text-2xl font-bold text-green-600">
            {data?.stats?.active || 0}
          </p>
        </div>
        <div className="bg-white p-4 rounded-lg border">
          <p className="text-sm text-gray-600">مخزون منخفض</p>
          <p className="text-2xl font-bold text-red-600">
            {data?.stats?.low_stock || 0}
          </p>
        </div>
        <div className="bg-white p-4 rounded-lg border">
          <p className="text-sm text-gray-600">غير نشطة</p>
          <p className="text-2xl font-bold text-gray-600">
            {data?.stats?.inactive || 0}
          </p>
        </div>
      </div>

      {/* Table */}
      <DataTable
        data={data?.data || []}
        columns={columns}
        loading={isLoading}
      />

      {/* Pagination */}
      {data?.meta && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-gray-600">
            عرض {data.meta.from} - {data.meta.to} من {data.meta.total}
          </p>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="secondary"
              disabled={page === 1}
              onClick={() => setPage(page - 1)}
            >
              السابق
            </Button>
            <Button
              size="sm"
              variant="secondary"
              disabled={page === data.meta.last_page}
              onClick={() => setPage(page + 1)}
            >
              التالي
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
```

#### Unit Testing

```typescript
// src/features/products/__tests__/ProductsListPage.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/app/queryClient';
import { ProductsListPage } from '../ProductsListPage';
import axios from '@/app/axios';

vi.mock('@/app/axios');
vi.mock('@tanstack/react-router', () => ({
  Link: ({ children }: any) => <div>{children}</div>,
}));

describe('ProductsListPage', () => {
  it('should render products list', async () => {
    vi.mocked(axios.get).mockResolvedValue({
      data: {
        data: [
          { id: 1, sku: 'PRD001', name: 'لمبة LED 10W', is_active: true },
          { id: 2, sku: 'PRD002', name: 'سلك 2.5 مم', is_active: true },
        ],
        meta: { total: 2, from: 1, to: 2, last_page: 1 },
        stats: { active: 2, inactive: 0, low_stock: 0 },
      },
    });

    render(
      <QueryClientProvider client={queryClient}>
        <ProductsListPage />
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('لمبة LED 10W')).toBeInTheDocument();
      expect(screen.getByText('سلك 2.5 مم')).toBeInTheDocument();
    });
  });

  it('should display stats correctly', async () => {
    vi.mocked(axios.get).mockResolvedValue({
      data: {
        data: [],
        meta: { total: 523 },
        stats: { active: 500, inactive: 23, low_stock: 15 },
      },
    });

    render(
      <QueryClientProvider client={queryClient}>
        <ProductsListPage />
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('523')).toBeInTheDocument();
      expect(screen.getByText('500')).toBeInTheDocument();
    });
  });
});
```

```bash
npm run test -- src/features/products/__tests__/ProductsListPage
# Expected: 5+ tests PASS ✅
```

#### Integration Testing

```typescript
// src/test/integration/products-list.test.tsx
describe('Products List Integration', () => {
  it('should handle search and pagination', async () => {
    // Test search functionality
    // Test pagination
    // Test filters
    // Expected: All working ✅
  });
});
```

#### User Testing

**Scenarios:**
1. ✅ قائمة المنتجات تظهر
2. ✅ Search يشتغل (real-time)
3. ✅ Pagination تشتغل
4. ✅ Stats cards صحيحة
5. ✅ View/Edit buttons تشتغل
6. ✅ Excel export يشتغل

#### Exit Criteria
- ✅ Products list تعرض صح
- ✅ Search/Filter/Pagination تشتغل
- ✅ 5+ tests passing
- ✅ User scenarios تعمل
- ✅ Performance < 500ms load

---

### ✅ TASK-202: Product Create/Edit Form
**المدة:** 2 أيام (12 ساعة)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-201  
**الحالة:** ⏳ Pending

#### الهدف
نموذج إضافة/تعديل منتج مع validation كامل

#### Development

```typescript
// src/features/products/ProductFormPage.tsx
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from '@tanstack/react-router';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { Card } from '@/components/ui/Card';
import axios from '@/app/axios';
import toast from 'react-hot-toast';

const productSchema = z.object({
  sku: z.string().min(3, 'الكود يجب أن يكون 3 أحرف على الأقل'),
  name: z.string().min(3, 'الاسم يجب أن يكون 3 أحرف على الأقل'),
  brand: z.string().optional(),
  category_id: z.number().optional(),
  unit: z.string().default('pcs'),
  pack_size: z.number().min(1).optional(),
  min_qty_default: z.number().min(0).optional(),
  is_active: z.boolean().default(true),
});

type ProductFormData = z.infer<typeof productSchema>;

export function ProductFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const isEdit = !!id;

  // Fetch product if editing
  const { data: product } = useQuery({
    queryKey: ['product', id],
    queryFn: async () => {
      const { data } = await axios.get(`/products/${id}`);
      return data.data;
    },
    enabled: isEdit,
  });

  // Fetch categories
  const { data: categories } = useQuery({
    queryKey: ['categories'],
    queryFn: async () => {
      const { data } = await axios.get('/categories');
      return data.data;
    },
  });

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
  } = useForm<ProductFormData>({
    resolver: zodResolver(productSchema),
    defaultValues: product,
  });

  const mutation = useMutation({
    mutationFn: async (data: ProductFormData) => {
      if (isEdit) {
        return axios.patch(`/products/${id}`, data);
      }
      return axios.post('/products', data);
    },
    onSuccess: () => {
      toast.success(isEdit ? 'تم تحديث المنتج' : 'تم إضافة المنتج');
      queryClient.invalidateQueries({ queryKey: ['products'] });
      navigate({ to: '/products' });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'فشلت العملية');
    },
  });

  const onSubmit = (data: ProductFormData) => {
    mutation.mutate(data);
  };

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold">
          {isEdit ? 'تعديل منتج' : 'إضافة منتج جديد'}
        </h1>
        <p className="text-gray-600">
          {isEdit ? 'تحديث بيانات المنتج' : 'إضافة منتج جديد للكتالوج'}
        </p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <Card className="p-6 space-y-6">
          {/* Basic Info */}
          <div className="space-y-4">
            <h3 className="font-semibold border-b pb-2">المعلومات الأساسية</h3>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">
                  كود المنتج (SKU) *
                </label>
                <Input
                  {...register('sku')}
                  placeholder="PRD001"
                  error={errors.sku?.message}
                />
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">
                  اسم المنتج *
                </label>
                <Input
                  {...register('name')}
                  placeholder="لمبة LED 10W"
                  error={errors.name?.message}
                />
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">
                  الماركة
                </label>
                <Input
                  {...register('brand')}
                  placeholder="Philips"
                />
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">
                  الفئة
                </label>
                <Select {...register('category_id', { valueAsNumber: true })}>
                  <option value="">اختر الفئة</option>
                  {categories?.map((cat: any) => (
                    <option key={cat.id} value={cat.id}>
                      {cat.name}
                    </option>
                  ))}
                </Select>
              </div>
            </div>
          </div>

          {/* Unit & Pack Size */}
          <div className="space-y-4">
            <h3 className="font-semibold border-b pb-2">وحدة القياس والتعبئة</h3>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium mb-1">
                  وحدة القياس
                </label>
                <Select {...register('unit')}>
                  <option value="pcs">قطعة</option>
                  <option value="box">علبة</option>
                  <option value="kg">كيلو</option>
                  <option value="meter">متر</option>
                  <option value="liter">لتر</option>
                </Select>
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">
                  حجم العبوة/الكرتونة
                </label>
                <Input
                  type="number"
                  {...register('pack_size', { valueAsNumber: true })}
                  placeholder="20"
                />
                <p className="text-xs text-gray-500 mt-1">
                  عدد الوحدات في الكرتونة الواحدة
                </p>
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">
                  الحد الأدنى (افتراضي)
                </label>
                <Input
                  type="number"
                  {...register('min_qty_default', { valueAsNumber: true })}
                  placeholder="50"
                />
              </div>
            </div>
          </div>

          {/* Status */}
          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="is_active"
              {...register('is_active')}
              className="w-4 h-4"
            />
            <label htmlFor="is_active" className="text-sm font-medium">
              منتج نشط
            </label>
          </div>

          {/* Actions */}
          <div className="flex gap-3 pt-4 border-t">
            <Button
              type="submit"
              loading={mutation.isPending}
            >
              {isEdit ? 'تحديث' : 'حفظ'}
            </Button>
            <Button
              type="button"
              variant="secondary"
              onClick={() => navigate({ to: '/products' })}
            >
              إلغاء
            </Button>
          </div>
        </Card>
      </form>
    </div>
  );
}
```

#### Unit Testing

```typescript
// src/features/products/__tests__/ProductFormPage.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/app/queryClient';
import { ProductFormPage } from '../ProductFormPage';

describe('ProductFormPage', () => {
  it('should render form fields', () => {
    render(
      <QueryClientProvider client={queryClient}>
        <ProductFormPage />
      </QueryClientProvider>
    );

    expect(screen.getByLabelText(/كود المنتج/)).toBeInTheDocument();
    expect(screen.getByLabelText(/اسم المنتج/)).toBeInTheDocument();
  });

  it('should validate required fields', async () => {
    render(
      <QueryClientProvider client={queryClient}>
        <ProductFormPage />
      </QueryClientProvider>
    );

    fireEvent.click(screen.getByText('حفظ'));

    await waitFor(() => {
      expect(screen.getByText(/الكود يجب أن يكون 3 أحرف على الأقل/)).toBeInTheDocument();
    });
  });

  it('should submit form with valid data', async () => {
    render(
      <QueryClientProvider client={queryClient}>
        <ProductFormPage />
      </QueryClientProvider>
    );

    fireEvent.change(screen.getByLabelText(/كود المنتج/), {
      target: { value: 'PRD001' },
    });
    fireEvent.change(screen.getByLabelText(/اسم المنتج/), {
      target: { value: 'لمبة LED' },
    });

    fireEvent.click(screen.getByText('حفظ'));

    await waitFor(() => {
      // Verify API called
    });
  });
});
```

```bash
npm run test -- src/features/products/__tests__/ProductFormPage
# Expected: 8+ tests PASS ✅
```

#### Integration Testing

```typescript
// Test: Full CRUD cycle
describe('Product CRUD Integration', () => {
  it('should create, read, update, delete product', async () => {
    // 1. Create
    // 2. List (verify exists)
    // 3. Edit
    // 4. Delete
    // Expected: All steps working ✅
  });
});
```

#### User Testing

**Scenarios:**
1. **Create New Product:**
   - املأ كل الحقول
   - اضغط "حفظ"
   - ✅ يجب: Success message + redirect للقائمة

2. **Validation:**
   - اترك الحقول فارغة
   - اضغط "حفظ"
   - ✅ يجب: error messages تحت كل حقل

3. **Edit Product:**
   - افتح منتج موجود
   - عدّل الاسم
   - احفظ
   - ✅ يجب: تحديث صحيح

4. **Pack Size Calculation:**
   - أدخل pack_size = 20
   - ✅ يجب: tooltip يظهر شرح

#### Exit Criteria
- ✅ Form يشتغل (create + edit)
- ✅ Validation صحيحة (Zod)
- ✅ 8+ tests passing
- ✅ All scenarios تعمل
- ✅ Good UX (loading, errors, success)

---

### ✅ TASK-203: Product Details Page
**المدة:** 1 يوم (6 ساعات)  
**الأولوية:** 🟠 HIGH  
**Dependencies:** TASK-202  
**الحالة:** ⏳ Pending

#### الهدف
صفحة تفاصيل منتج مع مخزون كل فرع + حركات

#### Development

```typescript
// src/features/products/ProductDetailsPage.tsx
import { useQuery } from '@tanstack/react-query';
import { useParams, Link } from '@tanstack/react-router';
import { Edit, ArrowLeft, Package, TrendingUp } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { DataTable } from '@/components/shared/DataTable';
import axios from '@/app/axios';

export function ProductDetailsPage() {
  const { id } = useParams();

  const { data: product } = useQuery({
    queryKey: ['product', id],
    queryFn: async () => {
      const { data } = await axios.get(`/products/${id}`);
      return data.data;
    },
  });

  const { data: branchStocks } = useQuery({
    queryKey: ['product-stocks', id],
    queryFn: async () => {
      const { data } = await axios.get(`/products/${id}/branch-stocks`);
      return data.data;
    },
  });

  const { data: movements } = useQuery({
    queryKey: ['product-movements', id],
    queryFn: async () => {
      const { data } = await axios.get(`/products/${id}/movements`, {
        params: { limit: 10 },
      });
      return data.data;
    },
  });

  if (!product) return <div>جاري التحميل...</div>;

  const stockColumns = [
    { key: 'branch_name', header: 'الفرع' },
    { 
      key: 'current_qty', 
      header: 'الكمية الحالية',
      render: (stock: any) => (
        <span className="font-semibold">{stock.current_qty} {product.unit}</span>
      ),
    },
    { key: 'min_qty', header: 'الحد الأدنى' },
    {
      key: 'status',
      header: 'الحالة',
      render: (stock: any) => {
        if (stock.current_qty < stock.min_qty) {
          return <Badge variant="danger">منخفض</Badge>;
        }
        return <Badge variant="success">جيد</Badge>;
      },
    },
  ];

  const movementColumns = [
    { key: 'created_at', header: 'التاريخ' },
    { key: 'branch_name', header: 'الفرع' },
    { key: 'movement_type', header: 'النوع' },
    { key: 'qty_units', header: 'الكمية' },
    { key: 'ref_type', header: 'المرجع' },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link to="/products">
            <Button variant="ghost" size="sm" leftIcon={<ArrowLeft />}>
              رجوع
            </Button>
          </Link>
          <div>
            <h1 className="text-2xl font-bold">{product.name}</h1>
            <p className="text-gray-600">كود: {product.sku}</p>
          </div>
        </div>
        <Link to={`/products/${id}/edit`}>
          <Button leftIcon={<Edit />}>
            تعديل
          </Button>
        </Link>
      </div>

      {/* Product Info */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card className="lg:col-span-2 p-6">
          <h3 className="font-semibold mb-4">معلومات المنتج</h3>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-600">الماركة</p>
              <p className="font-medium">{product.brand || '-'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">الفئة</p>
              <p className="font-medium">{product.category?.name || '-'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">وحدة القياس</p>
              <p className="font-medium">{product.unit}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">حجم العبوة</p>
              <p className="font-medium">
                {product.pack_size ? `${product.pack_size} وحدة` : '-'}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-600">الحد الأدنى الافتراضي</p>
              <p className="font-medium">{product.min_qty_default || '-'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">الحالة</p>
              <Badge variant={product.is_active ? 'success' : 'secondary'}>
                {product.is_active ? 'نشط' : 'غير نشط'}
              </Badge>
            </div>
          </div>
        </Card>

        {/* Stock Summary */}
        <Card className="p-6">
          <h3 className="font-semibold mb-4 flex items-center gap-2">
            <Package className="w-5 h-5" />
            إجمالي المخزون
          </h3>
          <div className="text-center py-6">
            <p className="text-4xl font-bold text-blue-600">
              {branchStocks?.reduce((sum: number, s: any) => sum + s.current_qty, 0) || 0}
            </p>
            <p className="text-gray-600 mt-2">{product.unit}</p>
          </div>
        </Card>
      </div>

      {/* Branch Stocks */}
      <Card className="p-6">
        <h3 className="font-semibold mb-4">المخزون بالفروع</h3>
        <DataTable
          data={branchStocks || []}
          columns={stockColumns}
        />
      </Card>

      {/* Recent Movements */}
      <Card className="p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold flex items-center gap-2">
            <TrendingUp className="w-5 h-5" />
            آخر الحركات
          </h3>
          <Link to={`/reports/product-movements?product_id=${id}`}>
            <Button variant="ghost" size="sm">
              عرض الكل
            </Button>
          </Link>
        </div>
        <DataTable
          data={movements || []}
          columns={movementColumns}
        />
      </Card>
    </div>
  );
}
```

#### Unit Testing

```typescript
// src/features/products/__tests__/ProductDetailsPage.test.tsx
describe('ProductDetailsPage', () => {
  it('should render product details', async () => {
    // Mock data
    // Render
    // Verify all sections displayed
    // Expected: Details + stocks + movements ✅
  });

  it('should show low stock warning', async () => {
    // Mock low stock
    // Verify badge = danger
    // Expected: Warning displayed ✅
  });
});
```

```bash
npm run test -- src/features/products/__tests__/ProductDetailsPage
# Expected: 5+ tests PASS ✅
```

#### User Testing

**Scenarios:**
1. ✅ Product details تظهر صح
2. ✅ Branch stocks table يظهر
3. ✅ Low stock badge يظهر صح
4. ✅ Recent movements تظهر
5. ✅ Edit button ينقل للتعديل

#### Exit Criteria
- ✅ Details page complete
- ✅ All data displayed correctly
- ✅ 5+ tests passing
- ✅ Good UI/UX

---

### ✅ TASK-204: Product Delete & Deactivate
**المدة:** 4 ساعات  
**الأولوية:** 🟡 MEDIUM  
**Dependencies:** TASK-203  
**الحالة:** ⏳ Pending

#### الهدف
حذف/تعطيل منتج مع validation (لا يمكن حذف منتج له حركات)

#### Development

```typescript
// في ProductsListPage: add delete handler
const deleteMutation = useMutation({
  mutationFn: async (id: number) => {
    return axios.delete(`/products/${id}`);
  },
  onSuccess: () => {
    toast.success('تم حذف المنتج');
    queryClient.invalidateQueries({ queryKey: ['products'] });
  },
  onError: (error: any) => {
    toast.error(error.response?.data?.message || 'لا يمكن حذف المنتج');
  },
});

const handleDelete = (product: any) => {
  if (confirm(`هل أنت متأكد من حذف المنتج "${product.name}"؟`)) {
    deleteMutation.mutate(product.id);
  }
};
```

#### Testing

```typescript
describe('Product Delete', () => {
  it('should delete product successfully', async () => {
    // Mock success response
    // Call delete
    // Expected: Product deleted ✅
  });

  it('should prevent delete if product has movements', async () => {
    // Mock error (has movements)
    // Call delete
    // Expected: Error message ✅
  });
});
```

#### User Testing
1. ✅ Delete product → success
2. ✅ Delete product with movements → error
3. ✅ Deactivate instead of delete → works

---

### ✅ TASK-205: Product Import from Excel
**المدة:** 1 يوم (6 ساعات)  
**الأولوية:** 🟡 MEDIUM  
**Dependencies:** TASK-204  
**الحالة:** ⏳ Pending

#### الهدف
استيراد منتجات من Excel مع Preview + Validation

#### Development

```typescript
// src/features/products/ProductImportPage.tsx
import { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { Upload, Download, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import axios from '@/app/axios';
import toast from 'react-hot-toast';
import * as XLSX from 'xlsx';

export function ProductImportPage() {
  const [file, setFile] = useState<File | null>(null);
  const [preview, setPreview] = useState<any[]>([]);
  const [errors, setErrors] = useState<any[]>([]);

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const selectedFile = e.target.files?.[0];
    if (!selectedFile) return;

    setFile(selectedFile);

    // Read and preview
    const data = await readExcel(selectedFile);
    setPreview(data);
  };

  const readExcel = async (file: File): Promise<any[]> => {
    const buffer = await file.arrayBuffer();
    const workbook = XLSX.read(buffer);
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    return XLSX.utils.sheet_to_json(sheet);
  };

  const importMutation = useMutation({
    mutationFn: async (data: any[]) => {
      return axios.post('/products/import', { products: data });
    },
    onSuccess: (response) => {
      toast.success(`تم استيراد ${response.data.imported} منتج`);
      setFile(null);
      setPreview([]);
    },
    onError: (error: any) => {
      setErrors(error.response?.data?.errors || []);
      toast.error('فشل الاستيراد');
    },
  });

  const handleImport = () => {
    importMutation.mutate(preview);
  };

  const downloadTemplate = () => {
    // Download Excel template
    const template = [
      { sku: 'PRD001', name: 'منتج تجريبي', brand: 'Philips', unit: 'pcs', pack_size: 20 },
    ];
    const ws = XLSX.utils.json_to_sheet(template);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Products');
    XLSX.writeFile(wb, 'products_template.xlsx');
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold">استيراد منتجات من Excel</h1>
        <p className="text-gray-600">قم برفع ملف Excel يحتوي على المنتجات</p>
      </div>

      {/* Template Download */}
      <Card className="p-6">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="font-semibold">قالب Excel</h3>
            <p className="text-sm text-gray-600">
              قم بتحميل القالب واملأ البيانات ثم ارفعه
            </p>
          </div>
          <Button
            variant="secondary"
            leftIcon={<Download />}
            onClick={downloadTemplate}
          >
            تحميل القالب
          </Button>
        </div>
      </Card>

      {/* File Upload */}
      <Card className="p-6">
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-2">
              اختر ملف Excel
            </label>
            <input
              type="file"
              accept=".xlsx,.xls"
              onChange={handleFileChange}
              className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
          </div>

          {file && (
            <div className="flex items-center gap-2 text-sm">
              <Upload className="w-4 h-4 text-green-600" />
              <span>{file.name}</span>
              <span className="text-gray-500">({preview.length} صف)</span>
            </div>
          )}
        </div>
      </Card>

      {/* Preview */}
      {preview.length > 0 && (
        <Card className="p-6">
          <h3 className="font-semibold mb-4">معاينة ({preview.length} منتج)</h3>
          <div className="overflow-x-auto max-h-96">
            <table className="min-w-full divide-y divide-gray-200">
              <thead>
                <tr>
                  <th className="px-4 py-2 text-right text-xs font-medium text-gray-500">الكود</th>
                  <th className="px-4 py-2 text-right text-xs font-medium text-gray-500">الاسم</th>
                  <th className="px-4 py-2 text-right text-xs font-medium text-gray-500">الماركة</th>
                  <th className="px-4 py-2 text-right text-xs font-medium text-gray-500">الوحدة</th>
                </tr>
              </thead>
              <tbody>
                {preview.slice(0, 10).map((row, i) => (
                  <tr key={i} className="hover:bg-gray-50">
                    <td className="px-4 py-2 text-sm">{row.sku}</td>
                    <td className="px-4 py-2 text-sm">{row.name}</td>
                    <td className="px-4 py-2 text-sm">{row.brand}</td>
                    <td className="px-4 py-2 text-sm">{row.unit}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {preview.length > 10 && (
            <p className="text-sm text-gray-500 mt-2">
              + {preview.length - 10} صف آخر...
            </p>
          )}
        </Card>
      )}

      {/* Errors */}
      {errors.length > 0 && (
        <Card className="p-6 bg-red-50 border-red-200">
          <div className="flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-red-600 mt-0.5" />
            <div className="flex-1">
              <h3 className="font-semibold text-red-900 mb-2">أخطاء الاستيراد</h3>
              <ul className="space-y-1 text-sm text-red-700">
                {errors.map((err, i) => (
                  <li key={i}>صف {err.row}: {err.message}</li>
                ))}
              </ul>
            </div>
          </div>
        </Card>
      )}

      {/* Actions */}
      {preview.length > 0 && (
        <div className="flex gap-3">
          <Button
            onClick={handleImport}
            loading={importMutation.isPending}
            disabled={errors.length > 0}
          >
            استيراد {preview.length} منتج
          </Button>
          <Button
            variant="secondary"
            onClick={() => {
              setFile(null);
              setPreview([]);
              setErrors([]);
            }}
          >
            إلغاء
          </Button>
        </div>
      )}
    </div>
  );
}
```

#### Testing

```typescript
describe('Product Import', () => {
  it('should preview Excel file', async () => {
    // Upload mock file
    // Verify preview displayed
    // Expected: Preview table shown ✅
  });

  it('should validate data before import', async () => {
    // Upload file with errors
    // Verify errors shown
    // Expected: Errors displayed, import disabled ✅
  });

  it('should import valid data', async () => {
    // Upload valid file
    // Click import
    // Expected: Success + products created ✅
  });
});
```

#### User Testing
1. ✅ Download template
2. ✅ Upload Excel → preview يظهر
3. ✅ Validation errors تظهر
4. ✅ Import success → products تُضاف

---

## 🏷️ Module 1 Summary

**Products Management Complete!** ✅

| Task | Status | Tests | Duration |
|------|--------|-------|----------|
| TASK-201 | ✅ | 5+ | 12h |
| TASK-202 | ✅ | 8+ | 12h |
| TASK-203 | ✅ | 5+ | 6h |
| TASK-204 | ✅ | 3+ | 4h |
| TASK-205 | ✅ | 5+ | 6h |

**Total:** 40 hours (5 days)  
**Total Tests:** 26+

---

## 📋 Module 2: Issue Vouchers Management

### ✅ TASK-301: Issue Vouchers List Page
**المدة:** 2 أيام (14 ساعة)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-105  
**الحالة:** ⏳ Pending

#### الهدف
صفحة قائمة أذونات الصرف مع Status filter + Branch filter + Date range

#### Development

```typescript
// src/features/issue-vouchers/IssueVouchersListPage.tsx
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Plus, Printer, Download, Search, Filter } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { DataTable } from '@/components/shared/DataTable';
import { Badge } from '@/components/ui/Badge';
import { DateRangePicker } from '@/components/ui/DateRangePicker';
import axios from '@/app/axios';
import { Link } from '@tanstack/react-router';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';

type VoucherStatus = 'pending' | 'approved' | 'rejected';

export function IssueVouchersListPage() {
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState<VoucherStatus | ''>('');
  const [branchId, setBranchId] = useState('');
  const [dateRange, setDateRange] = useState({ from: '', to: '' });
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['issue-vouchers', search, status, branchId, dateRange, page],
    queryFn: async () => {
      const { data } = await axios.get('/issue-vouchers', {
        params: { 
          search, 
          status, 
          branch_id: branchId,
          date_from: dateRange.from,
          date_to: dateRange.to,
          page, 
          per_page: 10,
        },
      });
      return data;
    },
  });

  const { data: branches } = useQuery({
    queryKey: ['branches'],
    queryFn: async () => {
      const { data } = await axios.get('/branches');
      return data.data;
    },
  });

  const statusBadge = (status: VoucherStatus) => {
    const variants = {
      pending: { variant: 'warning' as const, label: 'معلق' },
      approved: { variant: 'success' as const, label: 'معتمد' },
      rejected: { variant: 'danger' as const, label: 'مرفوض' },
    };
    const { variant, label } = variants[status];
    return <Badge variant={variant}>{label}</Badge>;
  };

  const columns = [
    { 
      key: 'voucher_number', 
      header: 'رقم الأذن',
      render: (v: any) => (
        <Link 
          to={`/issue-vouchers/${v.id}`}
          className="text-blue-600 hover:underline font-medium"
        >
          {v.voucher_number}
        </Link>
      ),
    },
    { 
      key: 'issue_date', 
      header: 'التاريخ',
      render: (v: any) => format(new Date(v.issue_date), 'dd MMM yyyy', { locale: ar }),
    },
    { key: 'branch_name', header: 'الفرع' },
    { 
      key: 'customer_name', 
      header: 'العميل',
      render: (v: any) => v.customer?.name || '-',
    },
    { 
      key: 'total_packs', 
      header: 'الكراتين',
      render: (v: any) => <span className="font-semibold">{v.total_packs}</span>,
    },
    { 
      key: 'total_units', 
      header: 'الوحدات',
      render: (v: any) => <span className="font-semibold">{v.total_units}</span>,
    },
    { 
      key: 'status', 
      header: 'الحالة',
      render: (v: any) => statusBadge(v.status),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (v: any) => (
        <div className="flex gap-2">
          <Link to={`/issue-vouchers/${v.id}`}>
            <Button size="sm" variant="ghost">عرض</Button>
          </Link>
          {v.status === 'approved' && (
            <Button 
              size="sm" 
              variant="secondary"
              leftIcon={<Printer className="w-4 h-4" />}
              onClick={() => window.open(`/api/issue-vouchers/${v.id}/print`, '_blank')}
            >
              طباعة
            </Button>
          )}
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">أذونات الصرف</h1>
          <p className="text-gray-600">إدارة أذونات صرف المنتجات</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" leftIcon={<Download />}>
            تصدير Excel
          </Button>
          <Link to="/issue-vouchers/new">
            <Button leftIcon={<Plus />}>
              أذن صرف جديد
            </Button>
          </Link>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
          <p className="text-sm text-yellow-800">معلق</p>
          <p className="text-2xl font-bold text-yellow-900">
            {data?.stats?.pending || 0}
          </p>
        </div>
        <div className="bg-green-50 border border-green-200 p-4 rounded-lg">
          <p className="text-sm text-green-800">معتمد</p>
          <p className="text-2xl font-bold text-green-900">
            {data?.stats?.approved || 0}
          </p>
        </div>
        <div className="bg-red-50 border border-red-200 p-4 rounded-lg">
          <p className="text-sm text-red-800">مرفوض</p>
          <p className="text-2xl font-bold text-red-900">
            {data?.stats?.rejected || 0}
          </p>
        </div>
        <div className="bg-blue-50 border border-blue-200 p-4 rounded-lg">
          <p className="text-sm text-blue-800">إجمالي الشهر</p>
          <p className="text-2xl font-bold text-blue-900">
            {data?.stats?.this_month || 0}
          </p>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg border space-y-4">
        <div className="flex items-center gap-2 text-sm font-medium">
          <Filter className="w-4 h-4" />
          <span>تصفية</span>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <Input
              placeholder="بحث برقم الأذن أو العميل..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              leftIcon={<Search />}
            />
          </div>
          <div>
            <Select value={status} onChange={(e) => setStatus(e.target.value as any)}>
              <option value="">كل الحالات</option>
              <option value="pending">معلق</option>
              <option value="approved">معتمد</option>
              <option value="rejected">مرفوض</option>
            </Select>
          </div>
          <div>
            <Select value={branchId} onChange={(e) => setBranchId(e.target.value)}>
              <option value="">كل الفروع</option>
              {branches?.map((branch: any) => (
                <option key={branch.id} value={branch.id}>
                  {branch.name}
                </option>
              ))}
            </Select>
          </div>
          <div>
            <DateRangePicker
              value={dateRange}
              onChange={setDateRange}
            />
          </div>
        </div>
      </div>

      {/* Table */}
      <DataTable
        data={data?.data || []}
        columns={columns}
        loading={isLoading}
      />

      {/* Pagination */}
      {data?.meta && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-gray-600">
            عرض {data.meta.from} - {data.meta.to} من {data.meta.total}
          </p>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="secondary"
              disabled={page === 1}
              onClick={() => setPage(page - 1)}
            >
              السابق
            </Button>
            <Button
              size="sm"
              variant="secondary"
              disabled={page === data.meta.last_page}
              onClick={() => setPage(page + 1)}
            >
              التالي
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
```

#### Unit Testing

```typescript
// src/features/issue-vouchers/__tests__/IssueVouchersListPage.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/app/queryClient';
import { IssueVouchersListPage } from '../IssueVouchersListPage';
import axios from '@/app/axios';

vi.mock('@/app/axios');
vi.mock('@tanstack/react-router', () => ({
  Link: ({ children, to }: any) => <a href={to}>{children}</a>,
}));

describe('IssueVouchersListPage', () => {
  it('should render vouchers list', async () => {
    vi.mocked(axios.get).mockResolvedValue({
      data: {
        data: [
          {
            id: 1,
            voucher_number: 'IV-2025-001',
            issue_date: '2025-10-15',
            branch_name: 'فرع المنصورة',
            customer: { name: 'أحمد محمد' },
            total_packs: 10,
            total_units: 200,
            status: 'pending',
          },
        ],
        meta: { total: 1, from: 1, to: 1, last_page: 1 },
        stats: { pending: 5, approved: 20, rejected: 2, this_month: 27 },
      },
    });

    render(
      <QueryClientProvider client={queryClient}>
        <IssueVouchersListPage />
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('IV-2025-001')).toBeInTheDocument();
      expect(screen.getByText('أحمد محمد')).toBeInTheDocument();
      expect(screen.getByText('معلق')).toBeInTheDocument();
    });
  });

  it('should filter by status', async () => {
    render(
      <QueryClientProvider client={queryClient}>
        <IssueVouchersListPage />
      </QueryClientProvider>
    );

    const statusSelect = screen.getByRole('combobox');
    fireEvent.change(statusSelect, { target: { value: 'approved' } });

    await waitFor(() => {
      expect(axios.get).toHaveBeenCalledWith(
        expect.any(String),
        expect.objectContaining({
          params: expect.objectContaining({ status: 'approved' }),
        })
      );
    });
  });

  it('should display stats correctly', async () => {
    vi.mocked(axios.get).mockResolvedValue({
      data: {
        data: [],
        stats: { pending: 12, approved: 45, rejected: 3, this_month: 60 },
      },
    });

    render(
      <QueryClientProvider client={queryClient}>
        <IssueVouchersListPage />
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('12')).toBeInTheDocument();
      expect(screen.getByText('45')).toBeInTheDocument();
    });
  });
});
```

```bash
npm run test -- src/features/issue-vouchers/__tests__/IssueVouchersListPage
# Expected: 8+ tests PASS ✅
```

#### Integration Testing

```typescript
// src/test/integration/issue-vouchers-list.test.tsx
describe('Issue Vouchers List Integration', () => {
  it('should handle all filters together', async () => {
    // Test: search + status + branch + date range
    // Expected: Correct API calls with all params ✅
  });

  it('should handle print action', async () => {
    // Click print button
    // Verify window.open called with correct URL
    // Expected: Print dialog opens ✅
  });
});
```

#### User Testing

**Scenarios:**
1. ✅ قائمة الأذونات تظهر
2. ✅ Search يشتغل
3. ✅ Status filter يشتغل
4. ✅ Branch filter يشتغل
5. ✅ Date range يشتغل
6. ✅ Stats cards صحيحة
7. ✅ Print button يطبع (approved فقط)
8. ✅ Pagination تشتغل

#### Exit Criteria
- ✅ Vouchers list complete
- ✅ All filters working
- ✅ 8+ tests passing
- ✅ Print functionality working
- ✅ Good performance < 500ms

---

### ✅ TASK-302: Issue Voucher Creation Form
**المدة:** 3 أيام (20 ساعة)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-301  
**الحالة:** ⏳ Pending

#### الهدف
نموذج إنشاء أذن صرف مع:
- إضافة منتجات (product selector)
- حساب كراتين/وحدات تلقائي
- Validation (stock availability)
- Draft saving

#### Development

```typescript
// src/features/issue-vouchers/IssueVoucherFormPage.tsx
import { useState, useEffect } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from '@tanstack/react-router';
import { Plus, Trash2, Save, Send, AlertTriangle } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { Card } from '@/components/ui/Card';
import { ProductSelector } from '@/components/shared/ProductSelector';
import axios from '@/app/axios';
import toast from 'react-hot-toast';

const voucherSchema = z.object({
  issue_date: z.string(),
  branch_id: z.number().min(1, 'اختر الفرع'),
  customer_id: z.number().optional(),
  notes: z.string().optional(),
  items: z.array(z.object({
    product_id: z.number().min(1),
    qty_packs: z.number().min(0),
    qty_units: z.number().min(0),
  })).min(1, 'أضف منتج واحد على الأقل'),
});

type VoucherFormData = z.infer<typeof voucherSchema>;

export function IssueVoucherFormPage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [selectedBranch, setSelectedBranch] = useState<number | null>(null);

  const {
    register,
    control,
    handleSubmit,
    watch,
    formState: { errors },
    setValue,
  } = useForm<VoucherFormData>({
    resolver: zodResolver(voucherSchema),
    defaultValues: {
      issue_date: new Date().toISOString().split('T')[0],
      items: [],
    },
  });

  const { fields, append, remove } = useFieldArray({
    control,
    name: 'items',
  });

  const { data: branches } = useQuery({
    queryKey: ['branches'],
    queryFn: async () => {
      const { data } = await axios.get('/branches');
      return data.data;
    },
  });

  const { data: customers } = useQuery({
    queryKey: ['customers'],
    queryFn: async () => {
      const { data } = await axios.get('/customers');
      return data.data;
    },
  });

  const { data: branchStocks } = useQuery({
    queryKey: ['branch-stocks', selectedBranch],
    queryFn: async () => {
      const { data } = await axios.get(`/branches/${selectedBranch}/stocks`);
      return data.data;
    },
    enabled: !!selectedBranch,
  });

  const createMutation = useMutation({
    mutationFn: async (data: VoucherFormData) => {
      return axios.post('/issue-vouchers', data);
    },
    onSuccess: () => {
      toast.success('تم إنشاء أذن الصرف');
      queryClient.invalidateQueries({ queryKey: ['issue-vouchers'] });
      navigate({ to: '/issue-vouchers' });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'فشلت العملية');
    },
  });

  const saveDraftMutation = useMutation({
    mutationFn: async (data: VoucherFormData) => {
      return axios.post('/issue-vouchers/draft', data);
    },
    onSuccess: () => {
      toast.success('تم حفظ المسودة');
    },
  });

  const onSubmit = (data: VoucherFormData) => {
    createMutation.mutate(data);
  };

  const saveDraft = () => {
    const data = watch();
    saveDraftMutation.mutate(data as VoucherFormData);
  };

  const handleAddProduct = (product: any) => {
    append({
      product_id: product.id,
      qty_packs: 0,
      qty_units: 0,
    });
  };

  const calculateTotalUnits = (index: number) => {
    const item = watch(`items.${index}`);
    const product = branchStocks?.find((s: any) => s.product_id === item.product_id);
    const packSize = product?.product?.pack_size || 1;
    return (item.qty_packs * packSize) + item.qty_units;
  };

  const getAvailableStock = (productId: number) => {
    return branchStocks?.find((s: any) => s.product_id === productId)?.current_qty || 0;
  };

  const isStockSufficient = (index: number) => {
    const item = watch(`items.${index}`);
    const totalUnits = calculateTotalUnits(index);
    const available = getAvailableStock(item.product_id);
    return totalUnits <= available;
  };

  return (
    <div className="max-w-5xl mx-auto space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">إنشاء أذن صرف جديد</h1>
          <p className="text-gray-600">صرف منتجات من المخزن</p>
        </div>
        <Button
          variant="secondary"
          leftIcon={<Save />}
          onClick={saveDraft}
          loading={saveDraftMutation.isPending}
        >
          حفظ كمسودة
        </Button>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        {/* Header Info */}
        <Card className="p-6 space-y-4">
          <h3 className="font-semibold border-b pb-2">معلومات الأذن</h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium mb-1">
                التاريخ *
              </label>
              <Input
                type="date"
                {...register('issue_date')}
                error={errors.issue_date?.message}
              />
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">
                الفرع *
              </label>
              <Select
                {...register('branch_id', { 
                  valueAsNumber: true,
                  onChange: (e) => setSelectedBranch(Number(e.target.value)),
                })}
                error={errors.branch_id?.message}
              >
                <option value="">اختر الفرع</option>
                {branches?.map((branch: any) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name}
                  </option>
                ))}
              </Select>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">
                العميل (اختياري)
              </label>
              <Select {...register('customer_id', { valueAsNumber: true })}>
                <option value="">بدون عميل</option>
                {customers?.map((customer: any) => (
                  <option key={customer.id} value={customer.id}>
                    {customer.name}
                  </option>
                ))}
              </Select>
            </div>
          </div>
        </Card>

        {/* Items */}
        <Card className="p-6 space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="font-semibold">الأصناف</h3>
            {selectedBranch && (
              <ProductSelector
                onSelect={handleAddProduct}
                branchId={selectedBranch}
              />
            )}
          </div>

          {!selectedBranch && (
            <div className="text-center py-8 text-gray-500">
              اختر الفرع أولاً لإضافة الأصناف
            </div>
          )}

          {fields.length === 0 && selectedBranch && (
            <div className="text-center py-8 text-gray-500">
              لم تتم إضافة أي أصناف بعد
            </div>
          )}

          {fields.length > 0 && (
            <div className="space-y-4">
              {fields.map((field, index) => {
                const product = branchStocks?.find(
                  (s: any) => s.product_id === watch(`items.${index}.product_id`)
                );
                const totalUnits = calculateTotalUnits(index);
                const available = getAvailableStock(watch(`items.${index}.product_id`));
                const sufficient = isStockSufficient(index);

                return (
                  <div key={field.id} className="border rounded-lg p-4 space-y-3">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <p className="font-semibold">{product?.product?.name}</p>
                        <p className="text-sm text-gray-600">
                          كود: {product?.product?.sku}
                        </p>
                        <p className="text-sm text-gray-600">
                          المتاح: <span className="font-semibold">{available}</span> {product?.product?.unit}
                        </p>
                      </div>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => remove(index)}
                      >
                        <Trash2 className="w-4 h-4 text-red-600" />
                      </Button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div>
                        <label className="block text-sm font-medium mb-1">
                          الكراتين
                        </label>
                        <Input
                          type="number"
                          min="0"
                          {...register(`items.${index}.qty_packs`, { valueAsNumber: true })}
                          placeholder="0"
                        />
                        {product?.product?.pack_size && (
                          <p className="text-xs text-gray-500 mt-1">
                            الكرتونة = {product.product.pack_size} {product.product.unit}
                          </p>
                        )}
                      </div>

                      <div>
                        <label className="block text-sm font-medium mb-1">
                          الوحدات
                        </label>
                        <Input
                          type="number"
                          min="0"
                          {...register(`items.${index}.qty_units`, { valueAsNumber: true })}
                          placeholder="0"
                        />
                      </div>

                      <div>
                        <label className="block text-sm font-medium mb-1">
                          الإجمالي
                        </label>
                        <div className={`p-2 rounded-lg border-2 ${
                          sufficient ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'
                        }`}>
                          <p className={`text-lg font-bold ${
                            sufficient ? 'text-green-700' : 'text-red-700'
                          }`}>
                            {totalUnits} {product?.product?.unit}
                          </p>
                          {!sufficient && (
                            <p className="text-xs text-red-600 flex items-center gap-1 mt-1">
                              <AlertTriangle className="w-3 h-3" />
                              يتجاوز المتاح
                            </p>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          {errors.items && (
            <p className="text-sm text-red-600">{errors.items.message}</p>
          )}
        </Card>

        {/* Notes */}
        <Card className="p-6">
          <label className="block text-sm font-medium mb-2">
            ملاحظات
          </label>
          <textarea
            {...register('notes')}
            rows={3}
            className="w-full border rounded-lg p-2"
            placeholder="ملاحظات إضافية..."
          />
        </Card>

        {/* Actions */}
        <div className="flex gap-3">
          <Button
            type="submit"
            leftIcon={<Send />}
            loading={createMutation.isPending}
            disabled={fields.length === 0}
          >
            إنشاء الأذن
          </Button>
          <Button
            type="button"
            variant="secondary"
            onClick={() => navigate({ to: '/issue-vouchers' })}
          >
            إلغاء
          </Button>
        </div>
      </form>
    </div>
  );
}
```

```typescript
// src/components/shared/ProductSelector.tsx
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Plus, Search } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Modal } from '@/components/ui/Modal';
import axios from '@/app/axios';

interface ProductSelectorProps {
  onSelect: (product: any) => void;
  branchId: number;
}

export function ProductSelector({ onSelect, branchId }: ProductSelectorProps) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');

  const { data: products } = useQuery({
    queryKey: ['branch-products', branchId, search],
    queryFn: async () => {
      const { data } = await axios.get(`/branches/${branchId}/products`, {
        params: { search },
      });
      return data.data;
    },
    enabled: open,
  });

  const handleSelect = (product: any) => {
    onSelect(product);
    setOpen(false);
    setSearch('');
  };

  return (
    <>
      <Button
        type="button"
        variant="secondary"
        size="sm"
        leftIcon={<Plus />}
        onClick={() => setOpen(true)}
      >
        إضافة صنف
      </Button>

      <Modal open={open} onClose={() => setOpen(false)} title="اختر المنتج">
        <div className="space-y-4">
          <Input
            placeholder="بحث..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            leftIcon={<Search />}
          />

          <div className="max-h-96 overflow-y-auto space-y-2">
            {products?.map((product: any) => (
              <div
                key={product.id}
                className="p-3 border rounded-lg hover:bg-gray-50 cursor-pointer"
                onClick={() => handleSelect(product)}
              >
                <p className="font-medium">{product.name}</p>
                <p className="text-sm text-gray-600">
                  كود: {product.sku} | المتاح: {product.current_qty} {product.unit}
                </p>
              </div>
            ))}
          </div>
        </div>
      </Modal>
    </>
  );
}
```

#### Unit Testing

```typescript
// src/features/issue-vouchers/__tests__/IssueVoucherFormPage.test.tsx
describe('IssueVoucherFormPage', () => {
  it('should add product to items', async () => {
    // Select branch
    // Open product selector
    // Select product
    // Verify added to table
    // Expected: Product in items ✅
  });

  it('should calculate total units correctly', async () => {
    // Add product with pack_size = 20
    // Enter qty_packs = 5
    // Enter qty_units = 10
    // Expected: Total = 110 units ✅
  });

  it('should validate stock availability', async () => {
    // Add product (available = 100)
    // Enter qty > 100
    // Expected: Warning shown ✅
  });

  it('should save draft', async () => {
    // Fill form
    // Click "حفظ كمسودة"
    // Expected: API called, toast shown ✅
  });
});
```

```bash
npm run test -- src/features/issue-vouchers/__tests__/IssueVoucherFormPage
# Expected: 12+ tests PASS ✅
```

#### Integration Testing

```typescript
describe('Issue Voucher Creation Integration', () => {
  it('should create voucher with multiple items', async () => {
    // Complete flow
    // Expected: Voucher created ✅
  });
});
```

#### User Testing

**Scenarios:**
1. **إنشاء أذن كامل:**
   - اختر فرع
   - أضف 3 منتجات
   - أدخل كميات
   - احفظ
   - ✅ يجب: Success + redirect

2. **Stock Validation:**
   - أضف منتج (متاح 50)
   - أدخل 60
   - ✅ يجب: تحذير بالأحمر

3. **Pack Calculation:**
   - منتج pack_size = 20
   - أدخل 3 كراتين + 5 وحدات
   - ✅ يجب: الإجمالي = 65

4. **Save Draft:**
   - املأ نصف النموذج
   - اضغط "حفظ كمسودة"
   - ✅ يجب: saved successfully

#### Exit Criteria
- ✅ Form complete & working
- ✅ Product selector working
- ✅ Calculations correct
- ✅ Stock validation working
- ✅ 12+ tests passing
- ✅ Great UX (real-time feedback)

---

### ✅ TASK-303: Issue Voucher Details & Approval
**المدة:** 1.5 يوم (10 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-302  
**الحالة:** ⏳ Pending

#### الهدف
صفحة تفاصيل أذن الصرف + Approve/Reject workflow

#### Development

```typescript
// src/features/issue-vouchers/IssueVoucherDetailsPage.tsx
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useParams, useNavigate } from '@tanstack/react-router';
import { ArrowLeft, CheckCircle, XCircle, Printer, Edit } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Badge } from '@/components/ui/Badge';
import { DataTable } from '@/components/shared/DataTable';
import axios from '@/app/axios';
import toast from 'react-hot-toast';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';
import { useAuth } from '@/features/auth/AuthContext';

export function IssueVoucherDetailsPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { user } = useAuth();

  const { data: voucher } = useQuery({
    queryKey: ['issue-voucher', id],
    queryFn: async () => {
      const { data } = await axios.get(`/issue-vouchers/${id}`);
      return data.data;
    },
  });

  const approveMutation = useMutation({
    mutationFn: async () => {
      return axios.post(`/issue-vouchers/${id}/approve`);
    },
    onSuccess: () => {
      toast.success('تم اعتماد الأذن');
      queryClient.invalidateQueries({ queryKey: ['issue-voucher', id] });
      queryClient.invalidateQueries({ queryKey: ['issue-vouchers'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'فشل الاعتماد');
    },
  });

  const rejectMutation = useMutation({
    mutationFn: async (reason: string) => {
      return axios.post(`/issue-vouchers/${id}/reject`, { reason });
    },
    onSuccess: () => {
      toast.success('تم رفض الأذن');
      queryClient.invalidateQueries({ queryKey: ['issue-voucher', id] });
      queryClient.invalidateQueries({ queryKey: ['issue-vouchers'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'فشل الرفض');
    },
  });

  const handleApprove = () => {
    if (confirm('هل أنت متأكد من اعتماد هذا الأذن؟')) {
      approveMutation.mutate();
    }
  };

  const handleReject = () => {
    const reason = prompt('سبب الرفض:');
    if (reason) {
      rejectMutation.mutate(reason);
    }
  };

  const handlePrint = () => {
    window.open(`/api/issue-vouchers/${id}/print`, '_blank');
  };

  if (!voucher) return <div>جاري التحميل...</div>;

  const canApprove = user?.role === 'manager' && voucher.status === 'pending';

  const itemsColumns = [
    { key: 'product_name', header: 'المنتج' },
    { key: 'product_sku', header: 'الكود' },
    { key: 'qty_packs', header: 'الكراتين' },
    { key: 'qty_units', header: 'الوحدات' },
    { 
      key: 'total_units', 
      header: 'الإجمالي',
      render: (item: any) => (
        <span className="font-semibold">
          {item.total_units} {item.product_unit}
        </span>
      ),
    },
  ];

  const statusBadge = {
    pending: { variant: 'warning' as const, label: 'معلق' },
    approved: { variant: 'success' as const, label: 'معتمد' },
    rejected: { variant: 'danger' as const, label: 'مرفوض' },
  }[voucher.status];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="sm"
            leftIcon={<ArrowLeft />}
            onClick={() => navigate({ to: '/issue-vouchers' })}
          >
            رجوع
          </Button>
          <div>
            <h1 className="text-2xl font-bold">أذن صرف #{voucher.voucher_number}</h1>
            <p className="text-gray-600">
              {format(new Date(voucher.issue_date), 'dd MMMM yyyy', { locale: ar })}
            </p>
          </div>
        </div>

        <div className="flex gap-2">
          {voucher.status === 'approved' && (
            <Button
              variant="secondary"
              leftIcon={<Printer />}
              onClick={handlePrint}
            >
              طباعة
            </Button>
          )}
          {voucher.status === 'pending' && (
            <Button
              variant="secondary"
              leftIcon={<Edit />}
              onClick={() => navigate({ to: `/issue-vouchers/${id}/edit` })}
            >
              تعديل
            </Button>
          )}
        </div>
      </div>

      {/* Status Alert */}
      <Card className={`p-4 ${
        voucher.status === 'approved' ? 'bg-green-50 border-green-200' :
        voucher.status === 'rejected' ? 'bg-red-50 border-red-200' :
        'bg-yellow-50 border-yellow-200'
      }`}>
        <div className="flex items-center gap-3">
          <Badge variant={statusBadge.variant}>{statusBadge.label}</Badge>
          {voucher.status === 'rejected' && voucher.rejection_reason && (
            <p className="text-sm text-red-700">
              سبب الرفض: {voucher.rejection_reason}
            </p>
          )}
          {voucher.status === 'approved' && voucher.approved_by && (
            <p className="text-sm text-green-700">
              تم الاعتماد بواسطة: {voucher.approved_by.name}
            </p>
          )}
        </div>
      </Card>

      {/* Voucher Info */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card className="p-6 lg:col-span-2">
          <h3 className="font-semibold mb-4">معلومات الأذن</h3>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-600">رقم الأذن</p>
              <p className="font-medium">{voucher.voucher_number}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">التاريخ</p>
              <p className="font-medium">
                {format(new Date(voucher.issue_date), 'dd/MM/yyyy')}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-600">الفرع</p>
              <p className="font-medium">{voucher.branch.name}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">العميل</p>
              <p className="font-medium">{voucher.customer?.name || '-'}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">تم الإنشاء بواسطة</p>
              <p className="font-medium">{voucher.created_by.name}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">تاريخ الإنشاء</p>
              <p className="font-medium">
                {format(new Date(voucher.created_at), 'dd/MM/yyyy HH:mm')}
              </p>
            </div>
          </div>

          {voucher.notes && (
            <div className="mt-4 pt-4 border-t">
              <p className="text-sm text-gray-600 mb-1">ملاحظات</p>
              <p className="text-sm">{voucher.notes}</p>
            </div>
          )}
        </Card>

        {/* Summary */}
        <Card className="p-6">
          <h3 className="font-semibold mb-4">الملخص</h3>
          <div className="space-y-3">
            <div className="flex justify-between items-center">
              <span className="text-gray-600">عدد الأصناف</span>
              <span className="font-bold">{voucher.items.length}</span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">إجمالي الكراتين</span>
              <span className="font-bold text-blue-600">{voucher.total_packs}</span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">إجمالي الوحدات</span>
              <span className="font-bold text-blue-600">{voucher.total_units}</span>
            </div>
          </div>
        </Card>
      </div>

      {/* Items Table */}
      <Card className="p-6">
        <h3 className="font-semibold mb-4">الأصناف المصروفة</h3>
        <DataTable
          data={voucher.items}
          columns={itemsColumns}
        />
      </Card>

      {/* Approval Actions */}
      {canApprove && (
        <Card className="p-6">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="font-semibold">اعتماد الأذن</h3>
              <p className="text-sm text-gray-600">
                مراجعة الكميات والموافقة على الصرف من المخزن
              </p>
            </div>
            <div className="flex gap-3">
              <Button
                variant="danger"
                leftIcon={<XCircle />}
                onClick={handleReject}
                loading={rejectMutation.isPending}
              >
                رفض
              </Button>
              <Button
                leftIcon={<CheckCircle />}
                onClick={handleApprove}
                loading={approveMutation.isPending}
              >
                اعتماد
              </Button>
            </div>
          </div>
        </Card>
      )}
    </div>
  );
}
```

#### Unit Testing

```typescript
// src/features/issue-vouchers/__tests__/IssueVoucherDetailsPage.test.tsx
describe('IssueVoucherDetailsPage', () => {
  it('should render voucher details', async () => {
    // Mock voucher data
    // Render
    // Verify all details shown
    // Expected: Complete details ✅
  });

  it('should show approve/reject buttons for manager', async () => {
    // Mock user = manager
    // Mock voucher status = pending
    // Render
    // Expected: Buttons visible ✅
  });

  it('should handle approve action', async () => {
    // Click approve
    // Confirm dialog
    // Expected: API called, voucher updated ✅
  });

  it('should handle reject action', async () => {
    // Click reject
    // Enter reason
    // Expected: API called with reason ✅
  });
});
```

```bash
npm run test -- src/features/issue-vouchers/__tests__/IssueVoucherDetailsPage
# Expected: 10+ tests PASS ✅
```

#### User Testing

**Scenarios:**
1. **View Details (any user):**
   - افتح أذن
   - ✅ يجب: كل التفاصيل تظهر

2. **Approve (manager only):**
   - افتح أذن pending
   - اضغط "اعتماد"
   - أكد
   - ✅ يجب: Success + status = approved

3. **Reject (manager only):**
   - اضغط "رفض"
   - أدخل سبب
   - ✅ يجب: Rejected with reason

4. **Print (approved only):**
   - أذن معتمد
   - اضغط "طباعة"
   - ✅ يجب: PDF يفتح

#### Exit Criteria
- ✅ Details page complete
- ✅ Approve/reject working
- ✅ Role-based permissions correct
- ✅ 10+ tests passing

---

### ✅ TASK-304: Issue Voucher Print Template
**المدة:** 1 يوم (6 ساعات)  
**الأولوية:** 🟡 MEDIUM  
**Dependencies:** TASK-303  
**الحالة:** ⏳ Pending

#### الهدف
PDF template احترافي لطباعة أذن الصرف

#### Development

```typescript
// Backend: resources/views/vouchers/issue-voucher-print.blade.php
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أذن صرف - {{ $voucher->voucher_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }
        
        body {
            padding: 20px;
            font-size: 14px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #2563eb;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .info-box {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }
        
        .info-box label {
            display: block;
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .info-box .value {
            font-weight: 600;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table thead {
            background-color: #f3f4f6;
        }
        
        table th,
        table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        
        table th {
            font-weight: 600;
        }
        
        .totals {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .totals .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .totals .row.total {
            border-top: 2px solid #2563eb;
            padding-top: 10px;
            font-weight: 700;
            font-size: 16px;
            color: #2563eb;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 50px;
        }
        
        .signature-box {
            text-align: center;
            padding-top: 40px;
            border-top: 2px solid #000;
        }
        
        .signature-box label {
            display: block;
            margin-top: 10px;
            font-weight: 600;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            @page {
                margin: 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>أذن صرف منتجات</h1>
        <p>{{ config('app.name') }}</p>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <label>رقم الأذن</label>
            <div class="value">{{ $voucher->voucher_number }}</div>
        </div>
        <div class="info-box">
            <label>التاريخ</label>
            <div class="value">{{ $voucher->issue_date->format('d/m/Y') }}</div>
        </div>
        <div class="info-box">
            <label>الفرع</label>
            <div class="value">{{ $voucher->branch->name }}</div>
        </div>
        <div class="info-box">
            <label>العميل</label>
            <div class="value">{{ $voucher->customer?->name ?? '-' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="40">#</th>
                <th>كود المنتج</th>
                <th>اسم المنتج</th>
                <th>الكراتين</th>
                <th>الوحدات</th>
                <th>الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($voucher->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product->sku }}</td>
                <td style="text-align: right">{{ $item->product->name }}</td>
                <td>{{ $item->qty_packs }}</td>
                <td>{{ $item->qty_units }}</td>
                <td><strong>{{ $item->total_units }} {{ $item->product->unit }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row">
            <span>عدد الأصناف:</span>
            <strong>{{ $voucher->items->count() }}</strong>
        </div>
        <div class="row">
            <span>إجمالي الكراتين:</span>
            <strong>{{ $voucher->total_packs }}</strong>
        </div>
        <div class="row total">
            <span>إجمالي الوحدات:</span>
            <strong>{{ $voucher->total_units }}</strong>
        </div>
    </div>

    @if($voucher->notes)
    <div style="margin-bottom: 30px; padding: 15px; background: #f9fafb; border-radius: 5px;">
        <strong>ملاحظات:</strong>
        <p style="margin-top: 10px;">{{ $voucher->notes }}</p>
    </div>
    @endif

    <div class="signatures">
        <div class="signature-box">
            <label>أمين المخزن</label>
        </div>
        <div class="signature-box">
            <label>المدير</label>
        </div>
        <div class="signature-box">
            <label>المستلم</label>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px; color: #999; font-size: 12px;">
        تم الطباعة في {{ now()->format('d/m/Y h:i A') }}
    </div>
</body>
</html>
```

```php
// Backend: app/Http/Controllers/IssueVoucherController.php
public function print($id)
{
    $voucher = IssueVoucher::with(['branch', 'customer', 'items.product'])
        ->findOrFail($id);

    // Only approved vouchers can be printed
    if ($voucher->status !== 'approved') {
        abort(403, 'Only approved vouchers can be printed');
    }

    $pdf = Pdf::loadView('vouchers.issue-voucher-print', compact('voucher'));
    return $pdf->stream("issue-voucher-{$voucher->voucher_number}.pdf");
}
```

#### Testing

```typescript
describe('Issue Voucher Print', () => {
  it('should generate PDF for approved voucher', async () => {
    // Call print endpoint
    // Verify PDF generated
    // Expected: PDF with correct data ✅
  });

  it('should prevent print for pending voucher', async () => {
    // Try print pending voucher
    // Expected: 403 error ✅
  });
});
```

#### User Testing
1. ✅ اطبع أذن معتمد → PDF يفتح
2. ✅ PDF format صحيح ومحترف
3. ✅ كل البيانات موجودة
4. ✅ Signatures boxes موجودة

---

### ✅ TASK-305: Issue Voucher Edit & Delete
**المدة:** 4 ساعات  
**الأولوية:** 🟡 MEDIUM  
**Dependencies:** TASK-304  
**الحالة:** ⏳ Pending

#### الهدف
تعديل/حذف أذن صرف (pending only)

#### Development

```typescript
// Edit: Use same form as create, but load existing data
// Delete: Only if status = pending

const deleteMutation = useMutation({
  mutationFn: async (id: number) => {
    return axios.delete(`/issue-vouchers/${id}`);
  },
  onSuccess: () => {
    toast.success('تم حذف الأذن');
    navigate({ to: '/issue-vouchers' });
  },
  onError: (error: any) => {
    toast.error('لا يمكن حذف أذن معتمد');
  },
});
```

#### Testing

```typescript
describe('Issue Voucher Edit/Delete', () => {
  it('should edit pending voucher', async () => {
    // Edit form
    // Submit changes
    // Expected: Updated ✅
  });

  it('should delete pending voucher', async () => {
    // Delete
    // Expected: Success ✅
  });

  it('should prevent delete approved voucher', async () => {
    // Try delete approved
    // Expected: Error ✅
  });
});
```

---

## 🏷️ Module 2 Summary

**Issue Vouchers Complete!** ✅

| Task | Status | Tests | Duration |
|------|--------|-------|----------|
| TASK-301 | ✅ | 8+ | 14h |
| TASK-302 | ✅ | 12+ | 20h |
| TASK-303 | ✅ | 10+ | 10h |
| TASK-304 | ✅ | 3+ | 6h |
| TASK-305 | ✅ | 4+ | 4h |

**Total:** 54 hours (6.75 days)  
**Total Tests:** 37+

---

## 📤 Module 3: Return Vouchers Management

### ✅ TASK-401: Return Vouchers List Page
**المدة:** 1.5 يوم (10 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-305  
**الحالة:** ⏳ Pending

#### الهدف
قائمة أذونات الإرجاع (مشابه للـ Issue مع Return Reasons)

#### Development

```typescript
// src/features/return-vouchers/ReturnVouchersListPage.tsx
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Plus, Printer, Download, Search, Filter, RotateCcw } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { DataTable } from '@/components/shared/DataTable';
import { Badge } from '@/components/ui/Badge';
import { DateRangePicker } from '@/components/ui/DateRangePicker';
import axios from '@/app/axios';
import { Link } from '@tanstack/react-router';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';

type VoucherStatus = 'pending' | 'approved' | 'rejected';

export function ReturnVouchersListPage() {
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState<VoucherStatus | ''>('');
  const [branchId, setBranchId] = useState('');
  const [dateRange, setDateRange] = useState({ from: '', to: '' });
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['return-vouchers', search, status, branchId, dateRange, page],
    queryFn: async () => {
      const { data } = await axios.get('/return-vouchers', {
        params: { 
          search, 
          status, 
          branch_id: branchId,
          date_from: dateRange.from,
          date_to: dateRange.to,
          page, 
          per_page: 10,
        },
      });
      return data;
    },
  });

  const { data: branches } = useQuery({
    queryKey: ['branches'],
    queryFn: async () => {
      const { data } = await axios.get('/branches');
      return data.data;
    },
  });

  const statusBadge = (status: VoucherStatus) => {
    const variants = {
      pending: { variant: 'warning' as const, label: 'معلق' },
      approved: { variant: 'success' as const, label: 'معتمد' },
      rejected: { variant: 'danger' as const, label: 'مرفوض' },
    };
    const { variant, label } = variants[status];
    return <Badge variant={variant}>{label}</Badge>;
  };

  const returnReasonBadge = (reason: string) => {
    const colors: Record<string, string> = {
      defect: 'bg-red-100 text-red-800',
      wrong_item: 'bg-orange-100 text-orange-800',
      excess: 'bg-blue-100 text-blue-800',
      expired: 'bg-purple-100 text-purple-800',
    };
    const labels: Record<string, string> = {
      defect: 'معيب',
      wrong_item: 'خطأ',
      excess: 'زيادة',
      expired: 'منتهي',
    };
    return (
      <span className={`px-2 py-1 rounded text-xs ${colors[reason]}`}>
        {labels[reason] || reason}
      </span>
    );
  };

  const columns = [
    { 
      key: 'voucher_number', 
      header: 'رقم الأذن',
      render: (v: any) => (
        <Link 
          to={`/return-vouchers/${v.id}`}
          className="text-blue-600 hover:underline font-medium flex items-center gap-2"
        >
          <RotateCcw className="w-4 h-4" />
          {v.voucher_number}
        </Link>
      ),
    },
    { 
      key: 'return_date', 
      header: 'التاريخ',
      render: (v: any) => format(new Date(v.return_date), 'dd MMM yyyy', { locale: ar }),
    },
    { key: 'branch_name', header: 'الفرع' },
    { 
      key: 'customer_name', 
      header: 'العميل',
      render: (v: any) => v.customer?.name || '-',
    },
    {
      key: 'return_reason',
      header: 'سبب الإرجاع',
      render: (v: any) => returnReasonBadge(v.return_reason),
    },
    { 
      key: 'total_packs', 
      header: 'الكراتين',
      render: (v: any) => <span className="font-semibold">{v.total_packs}</span>,
    },
    { 
      key: 'total_units', 
      header: 'الوحدات',
      render: (v: any) => <span className="font-semibold">{v.total_units}</span>,
    },
    { 
      key: 'status', 
      header: 'الحالة',
      render: (v: any) => statusBadge(v.status),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (v: any) => (
        <div className="flex gap-2">
          <Link to={`/return-vouchers/${v.id}`}>
            <Button size="sm" variant="ghost">عرض</Button>
          </Link>
          {v.status === 'approved' && (
            <Button 
              size="sm" 
              variant="secondary"
              leftIcon={<Printer className="w-4 h-4" />}
              onClick={() => window.open(`/api/return-vouchers/${v.id}/print`, '_blank')}
            >
              طباعة
            </Button>
          )}
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">أذونات الإرجاع</h1>
          <p className="text-gray-600">إدارة أذونات إرجاع المنتجات</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" leftIcon={<Download />}>
            تصدير Excel
          </Button>
          <Link to="/return-vouchers/new">
            <Button leftIcon={<Plus />}>
              أذن إرجاع جديد
            </Button>
          </Link>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
          <p className="text-sm text-yellow-800">معلق</p>
          <p className="text-2xl font-bold text-yellow-900">
            {data?.stats?.pending || 0}
          </p>
        </div>
        <div className="bg-green-50 border border-green-200 p-4 rounded-lg">
          <p className="text-sm text-green-800">معتمد</p>
          <p className="text-2xl font-bold text-green-900">
            {data?.stats?.approved || 0}
          </p>
        </div>
        <div className="bg-red-50 border border-red-200 p-4 rounded-lg">
          <p className="text-sm text-red-800">مرفوض</p>
          <p className="text-2xl font-bold text-red-900">
            {data?.stats?.rejected || 0}
          </p>
        </div>
        <div className="bg-purple-50 border border-purple-200 p-4 rounded-lg">
          <p className="text-sm text-purple-800">إجمالي الشهر</p>
          <p className="text-2xl font-bold text-purple-900">
            {data?.stats?.this_month || 0}
          </p>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg border space-y-4">
        <div className="flex items-center gap-2 text-sm font-medium">
          <Filter className="w-4 h-4" />
          <span>تصفية</span>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Input
            placeholder="بحث برقم الأذن أو العميل..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            leftIcon={<Search />}
          />
          <Select value={status} onChange={(e) => setStatus(e.target.value as any)}>
            <option value="">كل الحالات</option>
            <option value="pending">معلق</option>
            <option value="approved">معتمد</option>
            <option value="rejected">مرفوض</option>
          </Select>
          <Select value={branchId} onChange={(e) => setBranchId(e.target.value)}>
            <option value="">كل الفروع</option>
            {branches?.map((branch: any) => (
              <option key={branch.id} value={branch.id}>
                {branch.name}
              </option>
            ))}
          </Select>
          <DateRangePicker value={dateRange} onChange={setDateRange} />
        </div>
      </div>

      {/* Table */}
      <DataTable data={data?.data || []} columns={columns} loading={isLoading} />

      {/* Pagination */}
      {data?.meta && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-gray-600">
            عرض {data.meta.from} - {data.meta.to} من {data.meta.total}
          </p>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="secondary"
              disabled={page === 1}
              onClick={() => setPage(page - 1)}
            >
              السابق
            </Button>
            <Button
              size="sm"
              variant="secondary"
              disabled={page === data.meta.last_page}
              onClick={() => setPage(page + 1)}
            >
              التالي
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
```

#### Unit Testing

```typescript
// src/features/return-vouchers/__tests__/ReturnVouchersListPage.test.tsx
describe('ReturnVouchersListPage', () => {
  it('should render return vouchers list', async () => {
    vi.mocked(axios.get).mockResolvedValue({
      data: {
        data: [
          {
            id: 1,
            voucher_number: 'RV-2025-001',
            return_date: '2025-10-15',
            return_reason: 'defect',
            status: 'pending',
          },
        ],
        stats: { pending: 3, approved: 15, rejected: 1 },
      },
    });

    render(
      <QueryClientProvider client={queryClient}>
        <ReturnVouchersListPage />
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('RV-2025-001')).toBeInTheDocument();
      expect(screen.getByText('معيب')).toBeInTheDocument();
    });
  });
});
```

#### Exit Criteria
- ✅ Return vouchers list working
- ✅ Return reason badges shown
- ✅ 6+ tests passing

---

### ✅ TASK-402: Return Voucher Creation Form
**المدة:** 2 أيام (14 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-401  
**الحالة:** ⏳ Pending

#### الهدف
نموذج إرجاع مع Return Reason + اختيار أذن الصرف الأصلي (optional)

#### Development

```typescript
// src/features/return-vouchers/ReturnVoucherFormPage.tsx
// Similar to IssueVoucherFormPage but with:
// 1. Return reason select (defect, wrong_item, excess, expired)
// 2. Optional: Select original issue voucher
// 3. Items added return to stock

const returnReasonOptions = [
  { value: 'defect', label: 'منتج معيب' },
  { value: 'wrong_item', label: 'صنف خاطئ' },
  { value: 'excess', label: 'كمية زائدة' },
  { value: 'expired', label: 'منتج منتهي الصلاحية' },
];

// Form includes:
<Select {...register('return_reason')}>
  {returnReasonOptions.map(opt => (
    <option key={opt.value} value={opt.value}>{opt.label}</option>
  ))}
</Select>
```

#### Testing & Exit Criteria
- ✅ Form working with return reason
- ✅ 10+ tests passing
- ✅ Similar to Issue Voucher form

---

### ✅ TASK-403: Return Voucher Details & Approval
**المدة:** 1 يوم (8 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-402

#### Development
```typescript
// Similar to Issue Voucher Details but shows:
// - Return reason prominently
// - Link to original issue voucher (if exists)
// - Same approve/reject workflow
```

#### Exit Criteria
- ✅ Details page complete
- ✅ 8+ tests passing

---

### ✅ TASK-404: Return Voucher Print Template
**المدة:** 4 ساعات  
**الأولوية:** 🟡 MEDIUM  
**Dependencies:** TASK-403

#### Development
```blade
<!-- Similar to issue voucher print but with "أذن إرجاع" header -->
<!-- Shows return reason prominently -->
```

---

### ✅ TASK-405: Return Voucher Edit & Delete
**المدة:** 3 ساعات  
**الأولوية:** 🟡 MEDIUM  
**Dependencies:** TASK-404

---

## 🏷️ Module 3 Summary

**Return Vouchers Complete!** ✅

| Task | Status | Tests | Duration |
|------|--------|-------|----------|
| TASK-401 | ✅ | 6+ | 10h |
| TASK-402 | ✅ | 10+ | 14h |
| TASK-403 | ✅ | 8+ | 8h |
| TASK-404 | ✅ | 3+ | 4h |
| TASK-405 | ✅ | 3+ | 3h |

**Total:** 39 hours (4.875 days)  
**Total Tests:** 30+

---

## 👥 Module 4: Customers & Ledger Management

### ✅ TASK-501: Customers List Page
**المدة:** 1.5 يوم (10 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-105  
**الحالة:** ⏳ Pending

#### الهدف
قائمة العملاء مع Balance + Search + Filter

#### Development

```typescript
// src/features/customers/CustomersListPage.tsx
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Plus, Download, Search, DollarSign, TrendingUp } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { DataTable } from '@/components/shared/DataTable';
import { Badge } from '@/components/ui/Badge';
import axios from '@/app/axios';
import { Link } from '@tanstack/react-router';

export function CustomersListPage() {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['customers', search, page],
    queryFn: async () => {
      const { data } = await axios.get('/customers', {
        params: { search, page, per_page: 10 },
      });
      return data;
    },
  });

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ar-EG', {
      style: 'currency',
      currency: 'EGP',
    }).format(amount);
  };

  const columns = [
    { 
      key: 'name', 
      header: 'اسم العميل',
      render: (c: any) => (
        <Link 
          to={`/customers/${c.id}`}
          className="text-blue-600 hover:underline font-medium"
        >
          {c.name}
        </Link>
      ),
    },
    { key: 'phone', header: 'الهاتف' },
    { key: 'email', header: 'البريد الإلكتروني' },
    { 
      key: 'address', 
      header: 'العنوان',
      render: (c: any) => c.address || '-',
    },
    {
      key: 'balance',
      header: 'الرصيد',
      render: (c: any) => {
        const balance = c.balance || 0;
        const color = balance > 0 ? 'text-red-600' : balance < 0 ? 'text-green-600' : 'text-gray-600';
        return (
          <span className={`font-bold ${color}`}>
            {formatCurrency(Math.abs(balance))}
            {balance > 0 && ' دائن'}
            {balance < 0 && ' مدين'}
          </span>
        );
      },
    },
    {
      key: 'is_active',
      header: 'الحالة',
      render: (c: any) => (
        <Badge variant={c.is_active ? 'success' : 'secondary'}>
          {c.is_active ? 'نشط' : 'غير نشط'}
        </Badge>
      ),
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (c: any) => (
        <div className="flex gap-2">
          <Link to={`/customers/${c.id}`}>
            <Button size="sm" variant="ghost">عرض</Button>
          </Link>
          <Link to={`/customers/${c.id}/ledger`}>
            <Button size="sm" variant="secondary">كشف الحساب</Button>
          </Link>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">العملاء</h1>
          <p className="text-gray-600">إدارة بيانات العملاء وحساباتهم</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" leftIcon={<Download />}>
            تصدير Excel
          </Button>
          <Link to="/customers/new">
            <Button leftIcon={<Plus />}>
              إضافة عميل
            </Button>
          </Link>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white p-4 rounded-lg border">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-blue-100 rounded-lg">
              <DollarSign className="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <p className="text-sm text-gray-600">إجمالي العملاء</p>
              <p className="text-2xl font-bold">{data?.meta?.total || 0}</p>
            </div>
          </div>
        </div>
        <div className="bg-green-50 border border-green-200 p-4 rounded-lg">
          <p className="text-sm text-green-800">عملاء نشطون</p>
          <p className="text-2xl font-bold text-green-900">
            {data?.stats?.active || 0}
          </p>
        </div>
        <div className="bg-red-50 border border-red-200 p-4 rounded-lg">
          <p className="text-sm text-red-800">رصيد دائن</p>
          <p className="text-xl font-bold text-red-900">
            {formatCurrency(data?.stats?.total_credit || 0)}
          </p>
        </div>
        <div className="bg-blue-50 border border-blue-200 p-4 rounded-lg">
          <p className="text-sm text-blue-800">رصيد مدين</p>
          <p className="text-xl font-bold text-blue-900">
            {formatCurrency(data?.stats?.total_debit || 0)}
          </p>
        </div>
      </div>

      {/* Search */}
      <div className="flex gap-4">
        <div className="flex-1">
          <Input
            placeholder="بحث بالاسم أو الهاتف أو البريد..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            leftIcon={<Search />}
          />
        </div>
      </div>

      {/* Table */}
      <DataTable data={data?.data || []} columns={columns} loading={isLoading} />

      {/* Pagination */}
      {data?.meta && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-gray-600">
            عرض {data.meta.from} - {data.meta.to} من {data.meta.total}
          </p>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="secondary"
              disabled={page === 1}
              onClick={() => setPage(page - 1)}
            >
              السابق
            </Button>
            <Button
              size="sm"
              variant="secondary"
              disabled={page === data.meta.last_page}
              onClick={() => setPage(page + 1)}
            >
              التالي
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
```

#### Unit Testing

```typescript
describe('CustomersListPage', () => {
  it('should render customers list', async () => {
    vi.mocked(axios.get).mockResolvedValue({
      data: {
        data: [
          { id: 1, name: 'أحمد محمد', phone: '01012345678', balance: 5000 },
        ],
        stats: { active: 120, total_credit: 150000, total_debit: 80000 },
      },
    });

    render(
      <QueryClientProvider client={queryClient}>
        <CustomersListPage />
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('أحمد محمد')).toBeInTheDocument();
    });
  });

  it('should display balance correctly', async () => {
    // Test credit (positive) and debit (negative) balances
    // Expected: Correct colors and labels ✅
  });
});
```

#### Exit Criteria
- ✅ Customers list working
- ✅ Balance display correct
- ✅ 6+ tests passing

---

### ✅ TASK-502: Customer Form (Create/Edit)
**المدة:** 1 يوم (6 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-501

#### Development

```typescript
// src/features/customers/CustomerFormPage.tsx
const customerSchema = z.object({
  name: z.string().min(3, 'الاسم يجب أن يكون 3 أحرف على الأقل'),
  phone: z.string().min(11, 'رقم الهاتف غير صحيح'),
  email: z.string().email('البريد الإلكتروني غير صحيح').optional(),
  address: z.string().optional(),
  tax_id: z.string().optional(),
  is_active: z.boolean().default(true),
});

// Simple form with validation
```

#### Exit Criteria
- ✅ Form working
- ✅ 6+ tests passing

---

### ✅ TASK-503: Customer Details Page
**المدة:** 1 يوم (6 ساعات)  
**الأولوية:** 🟠 HIGH  
**Dependencies:** TASK-502

#### Development

```typescript
// src/features/customers/CustomerDetailsPage.tsx
// Shows:
// - Customer info
// - Current balance (big number)
// - Recent transactions (10 latest)
// - Quick links: Ledger, New Payment, Edit
```

#### Exit Criteria
- ✅ Details page complete
- ✅ 5+ tests passing

---

### ✅ TASK-504: Customer Ledger (كشف الحساب)
**المدة:** 2 أيام (12 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-503

#### الهدف
كشف حساب كامل مع Opening Balance + Transactions + Running Balance

#### Development

```typescript
// src/features/customers/CustomerLedgerPage.tsx
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useParams } from '@tanstack/react-router';
import { Printer, Download, Calendar } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { DateRangePicker } from '@/components/ui/DateRangePicker';
import axios from '@/app/axios';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';

export function CustomerLedgerPage() {
  const { id } = useParams();
  const [dateRange, setDateRange] = useState({ from: '', to: '' });

  const { data: customer } = useQuery({
    queryKey: ['customer', id],
    queryFn: async () => {
      const { data } = await axios.get(`/customers/${id}`);
      return data.data;
    },
  });

  const { data: ledger } = useQuery({
    queryKey: ['customer-ledger', id, dateRange],
    queryFn: async () => {
      const { data } = await axios.get(`/customers/${id}/ledger`, {
        params: {
          date_from: dateRange.from,
          date_to: dateRange.to,
        },
      });
      return data.data;
    },
  });

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ar-EG', {
      style: 'currency',
      currency: 'EGP',
    }).format(amount);
  };

  const handlePrint = () => {
    window.open(`/api/customers/${id}/ledger/print?from=${dateRange.from}&to=${dateRange.to}`, '_blank');
  };

  if (!customer || !ledger) return <div>جاري التحميل...</div>;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">كشف حساب: {customer.name}</h1>
          <p className="text-gray-600">{customer.phone}</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" leftIcon={<Download />}>
            تصدير Excel
          </Button>
          <Button variant="secondary" leftIcon={<Printer />} onClick={handlePrint}>
            طباعة
          </Button>
        </div>
      </div>

      {/* Date Range Filter */}
      <Card className="p-4">
        <div className="flex items-center gap-4">
          <Calendar className="w-5 h-5 text-gray-600" />
          <span className="text-sm font-medium">الفترة:</span>
          <DateRangePicker value={dateRange} onChange={setDateRange} />
        </div>
      </Card>

      {/* Summary */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-6 bg-blue-50 border-blue-200">
          <p className="text-sm text-blue-800 mb-1">الرصيد الافتتاحي</p>
          <p className="text-2xl font-bold text-blue-900">
            {formatCurrency(ledger.opening_balance)}
          </p>
        </Card>
        <Card className="p-6 bg-green-50 border-green-200">
          <p className="text-sm text-green-800 mb-1">إجمالي المدين</p>
          <p className="text-2xl font-bold text-green-900">
            {formatCurrency(ledger.total_debit)}
          </p>
        </Card>
        <Card className="p-6 bg-red-50 border-red-200">
          <p className="text-sm text-red-800 mb-1">إجمالي الدائن</p>
          <p className="text-2xl font-bold text-red-900">
            {formatCurrency(ledger.total_credit)}
          </p>
        </Card>
        <Card className="p-6 bg-purple-50 border-purple-200">
          <p className="text-sm text-purple-800 mb-1">الرصيد الختامي</p>
          <p className="text-2xl font-bold text-purple-900">
            {formatCurrency(ledger.closing_balance)}
          </p>
        </Card>
      </div>

      {/* Transactions Table */}
      <Card className="p-6">
        <h3 className="font-semibold mb-4">الحركات</h3>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">التاريخ</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">البيان</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">المرجع</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">مدين</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">دائن</th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500">الرصيد</th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {/* Opening Balance Row */}
              <tr className="bg-blue-50">
                <td className="px-4 py-3 text-sm">
                  {dateRange.from ? format(new Date(dateRange.from), 'dd/MM/yyyy') : '-'}
                </td>
                <td className="px-4 py-3 text-sm font-medium" colSpan={2}>
                  رصيد افتتاحي
                </td>
                <td className="px-4 py-3 text-sm">-</td>
                <td className="px-4 py-3 text-sm">-</td>
                <td className="px-4 py-3 text-sm font-bold text-blue-900">
                  {formatCurrency(ledger.opening_balance)}
                </td>
              </tr>

              {/* Transactions */}
              {ledger.transactions.map((txn: any) => (
                <tr key={txn.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 text-sm whitespace-nowrap">
                    {format(new Date(txn.date), 'dd/MM/yyyy')}
                  </td>
                  <td className="px-4 py-3 text-sm">{txn.description}</td>
                  <td className="px-4 py-3 text-sm">
                    <span className="text-blue-600">{txn.reference}</span>
                  </td>
                  <td className="px-4 py-3 text-sm font-semibold text-green-700">
                    {txn.debit > 0 ? formatCurrency(txn.debit) : '-'}
                  </td>
                  <td className="px-4 py-3 text-sm font-semibold text-red-700">
                    {txn.credit > 0 ? formatCurrency(txn.credit) : '-'}
                  </td>
                  <td className="px-4 py-3 text-sm font-bold">
                    {formatCurrency(txn.running_balance)}
                  </td>
                </tr>
              ))}

              {/* Closing Balance Row */}
              <tr className="bg-purple-50 font-bold">
                <td className="px-4 py-3 text-sm" colSpan={3}>
                  الرصيد الختامي
                </td>
                <td className="px-4 py-3 text-sm text-green-900">
                  {formatCurrency(ledger.total_debit)}
                </td>
                <td className="px-4 py-3 text-sm text-red-900">
                  {formatCurrency(ledger.total_credit)}
                </td>
                <td className="px-4 py-3 text-sm text-purple-900">
                  {formatCurrency(ledger.closing_balance)}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  );
}
```

#### Unit Testing

```typescript
describe('CustomerLedgerPage', () => {
  it('should render ledger with opening balance', async () => {
    // Mock ledger data
    // Verify opening balance row
    // Expected: Displayed correctly ✅
  });

  it('should calculate running balance correctly', async () => {
    // Mock transactions
    // Verify running balance in each row
    // Expected: Math correct ✅
  });

  it('should show closing balance', async () => {
    // Verify totals row
    // Expected: Correct calculation ✅
  });
});
```

#### Exit Criteria
- ✅ Ledger complete
- ✅ Running balance correct
- ✅ Print working
- ✅ 8+ tests passing

---

### ✅ TASK-505: Customer Payments Registration
**المدة:** 1 يوم (8 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-504

#### الهدف
تسجيل دفعة من العميل (نقدي أو شيك)

#### Development

```typescript
// src/features/customers/CustomerPaymentFormPage.tsx
const paymentSchema = z.object({
  customer_id: z.number().min(1),
  payment_date: z.string(),
  amount: z.number().min(0.01, 'المبلغ يجب أن يكون أكبر من صفر'),
  payment_method: z.enum(['cash', 'cheque']),
  // If cheque:
  cheque_number: z.string().optional(),
  cheque_date: z.string().optional(),
  bank_name: z.string().optional(),
  notes: z.string().optional(),
});

// Form with conditional fields (show cheque fields only if method = cheque)
```

#### Exit Criteria
- ✅ Payment form working
- ✅ Cheque fields conditional
- ✅ 6+ tests passing

---

### ✅ TASK-506: Customer Delete & Deactivate
**المدة:** 3 ساعات  
**الأولوية:** 🟡 MEDIUM  
**Dependencies:** TASK-505

#### Development
```typescript
// Cannot delete if has transactions
// Can deactivate instead
```

---

### ✅ TASK-507: Customer Import from Excel
**المدة:** 4 ساعات  
**الأولوية:** 🟡 MEDIUM  
**Dependencies:** TASK-506

#### Development
```typescript
// Similar to Product Import
// Template: name, phone, email, address, tax_id
```

---

## 🏷️ Module 4 Summary

**Customers & Ledger Complete!** ✅

| Task | Status | Tests | Duration |
|------|--------|-------|----------|
| TASK-501 | ✅ | 6+ | 10h |
| TASK-502 | ✅ | 6+ | 6h |
| TASK-503 | ✅ | 5+ | 6h |
| TASK-504 | ✅ | 8+ | 12h |
| TASK-505 | ✅ | 6+ | 8h |
| TASK-506 | ✅ | 3+ | 3h |
| TASK-507 | ✅ | 4+ | 4h |

**Total:** 49 hours (6.125 days)  
**Total Tests:** 38+

---

## 💰 Module 5: Payments & Cheques Management

### ✅ TASK-601: Payments List Page
**المدة:** 1.5 يوم (10 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-505  
**الحالة:** ⏳ Pending

#### الهدف
قائمة كل المدفوعات (نقدي + شيكات) مع Status للشيكات

#### Development

```typescript
// src/features/payments/PaymentsListPage.tsx
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Plus, Download, Search, Filter, DollarSign, FileText } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { DataTable } from '@/components/shared/DataTable';
import { Badge } from '@/components/ui/Badge';
import { DateRangePicker } from '@/components/ui/DateRangePicker';
import axios from '@/app/axios';
import { Link } from '@tanstack/react-router';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';

type PaymentMethod = 'cash' | 'cheque';
type ChequeStatus = 'pending' | 'collected' | 'bounced';

export function PaymentsListPage() {
  const [search, setSearch] = useState('');
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod | ''>('');
  const [chequeStatus, setChequeStatus] = useState<ChequeStatus | ''>('');
  const [dateRange, setDateRange] = useState({ from: '', to: '' });
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['payments', search, paymentMethod, chequeStatus, dateRange, page],
    queryFn: async () => {
      const { data } = await axios.get('/payments', {
        params: { 
          search, 
          payment_method: paymentMethod,
          cheque_status: chequeStatus,
          date_from: dateRange.from,
          date_to: dateRange.to,
          page, 
          per_page: 10,
        },
      });
      return data;
    },
  });

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('ar-EG', {
      style: 'currency',
      currency: 'EGP',
    }).format(amount);
  };

  const paymentMethodBadge = (method: PaymentMethod) => {
    return method === 'cash' ? (
      <Badge variant="success">
        <DollarSign className="w-3 h-3 mr-1" />
        نقدي
      </Badge>
    ) : (
      <Badge variant="info">
        <FileText className="w-3 h-3 mr-1" />
        شيك
      </Badge>
    );
  };

  const chequeStatusBadge = (status: ChequeStatus) => {
    const variants = {
      pending: { variant: 'warning' as const, label: 'معلق' },
      collected: { variant: 'success' as const, label: 'محصّل' },
      bounced: { variant: 'danger' as const, label: 'مرتد' },
    };
    const { variant, label } = variants[status];
    return <Badge variant={variant}>{label}</Badge>;
  };

  const columns = [
    { 
      key: 'payment_date', 
      header: 'التاريخ',
      render: (p: any) => format(new Date(p.payment_date), 'dd MMM yyyy', { locale: ar }),
    },
    { 
      key: 'customer_name', 
      header: 'العميل',
      render: (p: any) => (
        <Link 
          to={`/customers/${p.customer_id}`}
          className="text-blue-600 hover:underline"
        >
          {p.customer.name}
        </Link>
      ),
    },
    { 
      key: 'amount', 
      header: 'المبلغ',
      render: (p: any) => (
        <span className="font-bold text-green-700">
          {formatCurrency(p.amount)}
        </span>
      ),
    },
    { 
      key: 'payment_method', 
      header: 'الطريقة',
      render: (p: any) => paymentMethodBadge(p.payment_method),
    },
    {
      key: 'cheque_info',
      header: 'معلومات الشيك',
      render: (p: any) => {
        if (p.payment_method === 'cash') return '-';
        return (
          <div className="text-sm">
            <p className="font-medium">#{p.cheque_number}</p>
            <p className="text-gray-600">{p.bank_name}</p>
            <p className="text-xs text-gray-500">
              استحقاق: {format(new Date(p.cheque_date), 'dd/MM/yyyy')}
            </p>
          </div>
        );
      },
    },
    {
      key: 'cheque_status',
      header: 'حالة الشيك',
      render: (p: any) => {
        if (p.payment_method === 'cash') return '-';
        return chequeStatusBadge(p.cheque_status);
      },
    },
    {
      key: 'actions',
      header: 'الإجراءات',
      render: (p: any) => (
        <div className="flex gap-2">
          <Link to={`/payments/${p.id}`}>
            <Button size="sm" variant="ghost">عرض</Button>
          </Link>
          {p.payment_method === 'cheque' && p.cheque_status === 'pending' && (
            <Button size="sm" variant="secondary">
              تحصيل
            </Button>
          )}
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">المدفوعات</h1>
          <p className="text-gray-600">إدارة المدفوعات النقدية والشيكات</p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" leftIcon={<Download />}>
            تصدير Excel
          </Button>
          <Link to="/payments/new">
            <Button leftIcon={<Plus />}>
              تسجيل دفعة
            </Button>
          </Link>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div className="bg-blue-50 border border-blue-200 p-4 rounded-lg">
          <p className="text-sm text-blue-800">إجمالي المدفوعات</p>
          <p className="text-xl font-bold text-blue-900">
            {formatCurrency(data?.stats?.total_amount || 0)}
          </p>
        </div>
        <div className="bg-green-50 border border-green-200 p-4 rounded-lg">
          <p className="text-sm text-green-800">نقدي</p>
          <p className="text-xl font-bold text-green-900">
            {formatCurrency(data?.stats?.cash_amount || 0)}
          </p>
        </div>
        <div className="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
          <p className="text-sm text-yellow-800">شيكات معلقة</p>
          <p className="text-xl font-bold text-yellow-900">
            {formatCurrency(data?.stats?.pending_cheques || 0)}
          </p>
          <p className="text-xs text-yellow-700 mt-1">
            ({data?.stats?.pending_cheques_count || 0} شيك)
          </p>
        </div>
        <div className="bg-teal-50 border border-teal-200 p-4 rounded-lg">
          <p className="text-sm text-teal-800">شيكات محصّلة</p>
          <p className="text-xl font-bold text-teal-900">
            {formatCurrency(data?.stats?.collected_cheques || 0)}
          </p>
        </div>
        <div className="bg-red-50 border border-red-200 p-4 rounded-lg">
          <p className="text-sm text-red-800">شيكات مرتدة</p>
          <p className="text-xl font-bold text-red-900">
            {formatCurrency(data?.stats?.bounced_cheques || 0)}
          </p>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg border space-y-4">
        <div className="flex items-center gap-2 text-sm font-medium">
          <Filter className="w-4 h-4" />
          <span>تصفية</span>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Input
            placeholder="بحث بالعميل أو رقم الشيك..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            leftIcon={<Search />}
          />
          <Select value={paymentMethod} onChange={(e) => setPaymentMethod(e.target.value as any)}>
            <option value="">كل الطرق</option>
            <option value="cash">نقدي</option>
            <option value="cheque">شيك</option>
          </Select>
          <Select value={chequeStatus} onChange={(e) => setChequeStatus(e.target.value as any)}>
            <option value="">كل حالات الشيكات</option>
            <option value="pending">معلق</option>
            <option value="collected">محصّل</option>
            <option value="bounced">مرتد</option>
          </Select>
          <DateRangePicker value={dateRange} onChange={setDateRange} />
        </div>
      </div>

      {/* Table */}
      <DataTable data={data?.data || []} columns={columns} loading={isLoading} />

      {/* Pagination */}
      {data?.meta && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-gray-600">
            عرض {data.meta.from} - {data.meta.to} من {data.meta.total}
          </p>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="secondary"
              disabled={page === 1}
              onClick={() => setPage(page - 1)}
            >
              السابق
            </Button>
            <Button
              size="sm"
              variant="secondary"
              disabled={page === data.meta.last_page}
              onClick={() => setPage(page + 1)}
            >
              التالي
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
```

#### Unit Testing

```typescript
describe('PaymentsListPage', () => {
  it('should render payments list', async () => {
    vi.mocked(axios.get).mockResolvedValue({
      data: {
        data: [
          {
            id: 1,
            payment_date: '2025-10-15',
            customer: { name: 'أحمد محمد' },
            amount: 5000,
            payment_method: 'cash',
          },
          {
            id: 2,
            payment_method: 'cheque',
            cheque_number: 'CH-123',
            cheque_status: 'pending',
          },
        ],
        stats: {
          total_amount: 50000,
          cash_amount: 30000,
          pending_cheques: 20000,
        },
      },
    });

    render(
      <QueryClientProvider client={queryClient}>
        <PaymentsListPage />
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('أحمد محمد')).toBeInTheDocument();
      expect(screen.getByText('نقدي')).toBeInTheDocument();
      expect(screen.getByText('شيك')).toBeInTheDocument();
    });
  });

  it('should display stats correctly', async () => {
    // Test all 5 stat cards
    // Expected: All amounts correct ✅
  });
});
```

#### Exit Criteria
- ✅ Payments list working
- ✅ Cash/Cheque badges
- ✅ Cheque status badges
- ✅ 8+ tests passing

---

### ✅ TASK-602: Payment Registration Form
**المدة:** 1 يوم (8 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-601

#### الهدف
نموذج تسجيل دفعة مع Conditional fields للشيك

#### Development

```typescript
// src/features/payments/PaymentFormPage.tsx
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from '@tanstack/react-router';
import { DollarSign, FileText } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Select } from '@/components/ui/Select';
import { Card } from '@/components/ui/Card';
import axios from '@/app/axios';
import toast from 'react-hot-toast';

const paymentSchema = z.object({
  customer_id: z.number().min(1, 'اختر العميل'),
  payment_date: z.string(),
  amount: z.number().min(0.01, 'المبلغ يجب أن يكون أكبر من صفر'),
  payment_method: z.enum(['cash', 'cheque']),
  // Cheque fields (required if payment_method = cheque)
  cheque_number: z.string().optional(),
  cheque_date: z.string().optional(),
  bank_name: z.string().optional(),
  notes: z.string().optional(),
}).refine(
  (data) => {
    if (data.payment_method === 'cheque') {
      return data.cheque_number && data.cheque_date && data.bank_name;
    }
    return true;
  },
  {
    message: 'بيانات الشيك مطلوبة',
    path: ['cheque_number'],
  }
);

type PaymentFormData = z.infer<typeof paymentSchema>;

export function PaymentFormPage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm<PaymentFormData>({
    resolver: zodResolver(paymentSchema),
    defaultValues: {
      payment_date: new Date().toISOString().split('T')[0],
      payment_method: 'cash',
    },
  });

  const paymentMethod = watch('payment_method');

  const { data: customers } = useQuery({
    queryKey: ['customers'],
    queryFn: async () => {
      const { data } = await axios.get('/customers');
      return data.data;
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: PaymentFormData) => {
      return axios.post('/payments', data);
    },
    onSuccess: () => {
      toast.success('تم تسجيل الدفعة');
      queryClient.invalidateQueries({ queryKey: ['payments'] });
      queryClient.invalidateQueries({ queryKey: ['customers'] });
      navigate({ to: '/payments' });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'فشلت العملية');
    },
  });

  const onSubmit = (data: PaymentFormData) => {
    createMutation.mutate(data);
  };

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold">تسجيل دفعة جديدة</h1>
        <p className="text-gray-600">استلام دفعة نقدية أو شيك من عميل</p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        {/* Basic Info */}
        <Card className="p-6 space-y-6">
          <h3 className="font-semibold border-b pb-2">معلومات الدفعة</h3>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium mb-1">
                العميل *
              </label>
              <Select
                {...register('customer_id', { valueAsNumber: true })}
                error={errors.customer_id?.message}
              >
                <option value="">اختر العميل</option>
                {customers?.map((customer: any) => (
                  <option key={customer.id} value={customer.id}>
                    {customer.name} - رصيد: {customer.balance}
                  </option>
                ))}
              </Select>
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                تاريخ الدفع *
              </label>
              <Input
                type="date"
                {...register('payment_date')}
                error={errors.payment_date?.message}
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                المبلغ *
              </label>
              <Input
                type="number"
                step="0.01"
                {...register('amount', { valueAsNumber: true })}
                placeholder="0.00"
                error={errors.amount?.message}
              />
            </div>

            <div>
              <label className="block text-sm font-medium mb-1">
                طريقة الدفع *
              </label>
              <div className="grid grid-cols-2 gap-2">
                <label className={`flex items-center gap-2 p-3 border rounded-lg cursor-pointer ${
                  paymentMethod === 'cash' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'
                }`}>
                  <input
                    type="radio"
                    value="cash"
                    {...register('payment_method')}
                    className="w-4 h-4"
                  />
                  <DollarSign className="w-5 h-5 text-green-600" />
                  <span className="font-medium">نقدي</span>
                </label>
                <label className={`flex items-center gap-2 p-3 border rounded-lg cursor-pointer ${
                  paymentMethod === 'cheque' ? 'border-blue-500 bg-blue-50' : 'border-gray-300'
                }`}>
                  <input
                    type="radio"
                    value="cheque"
                    {...register('payment_method')}
                    className="w-4 h-4"
                  />
                  <FileText className="w-5 h-5 text-blue-600" />
                  <span className="font-medium">شيك</span>
                </label>
              </div>
            </div>
          </div>

          {/* Cheque Details (conditional) */}
          {paymentMethod === 'cheque' && (
            <div className="pt-4 border-t">
              <h4 className="font-semibold mb-4 flex items-center gap-2">
                <FileText className="w-5 h-5 text-blue-600" />
                معلومات الشيك
              </h4>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-1">
                    رقم الشيك *
                  </label>
                  <Input
                    {...register('cheque_number')}
                    placeholder="CH-123456"
                    error={errors.cheque_number?.message}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">
                    تاريخ الاستحقاق *
                  </label>
                  <Input
                    type="date"
                    {...register('cheque_date')}
                    error={errors.cheque_date?.message}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium mb-1">
                    اسم البنك *
                  </label>
                  <Input
                    {...register('bank_name')}
                    placeholder="البنك الأهلي المصري"
                    error={errors.bank_name?.message}
                  />
                </div>
              </div>
            </div>
          )}

          {/* Notes */}
          <div>
            <label className="block text-sm font-medium mb-1">
              ملاحظات
            </label>
            <textarea
              {...register('notes')}
              rows={3}
              className="w-full border rounded-lg p-2"
              placeholder="ملاحظات إضافية..."
            />
          </div>

          {/* Actions */}
          <div className="flex gap-3 pt-4 border-t">
            <Button
              type="submit"
              loading={createMutation.isPending}
            >
              حفظ الدفعة
            </Button>
            <Button
              type="button"
              variant="secondary"
              onClick={() => navigate({ to: '/payments' })}
            >
              إلغاء
            </Button>
          </div>
        </Card>
      </form>
    </div>
  );
}
```

#### Unit Testing

```typescript
describe('PaymentFormPage', () => {
  it('should show cheque fields when method=cheque', async () => {
    render(
      <QueryClientProvider client={queryClient}>
        <PaymentFormPage />
      </QueryClientProvider>
    );

    // Select cheque
    fireEvent.click(screen.getByLabelText(/شيك/));

    // Verify cheque fields appear
    await waitFor(() => {
      expect(screen.getByLabelText(/رقم الشيك/)).toBeInTheDocument();
      expect(screen.getByLabelText(/تاريخ الاستحقاق/)).toBeInTheDocument();
    });
  });

  it('should validate cheque fields when required', async () => {
    // Select cheque but don't fill fields
    // Submit
    // Expected: Validation errors ✅
  });
});
```

#### Exit Criteria
- ✅ Form working
- ✅ Conditional fields
- ✅ 8+ tests passing

---

### ✅ TASK-603: Cheque Collection (تحصيل الشيك)
**المدة:** 1 يوم (6 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-602

#### الهدف
تحصيل شيك معلق → تحديث الحالة إلى "محصّل"

#### Development

```typescript
// src/features/payments/ChequeCollectionPage.tsx
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useParams, useNavigate } from '@tanstack/react-router';
import { CheckCircle, XCircle, Calendar } from 'lucide-react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Input } from '@/components/ui/Input';
import axios from '@/app/axios';
import toast from 'react-hot-toast';
import { format } from 'date-fns';
import { ar } from 'date-fns/locale';

export function ChequeCollectionPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const { data: payment } = useQuery({
    queryKey: ['payment', id],
    queryFn: async () => {
      const { data } = await axios.get(`/payments/${id}`);
      return data.data;
    },
  });

  const collectMutation = useMutation({
    mutationFn: async (collectionDate: string) => {
      return axios.post(`/payments/${id}/collect`, { collection_date: collectionDate });
    },
    onSuccess: () => {
      toast.success('تم تحصيل الشيك');
      queryClient.invalidateQueries({ queryKey: ['payment', id] });
      queryClient.invalidateQueries({ queryKey: ['payments'] });
      navigate({ to: '/payments' });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'فشل التحصيل');
    },
  });

  const bounceMutation = useMutation({
    mutationFn: async (reason: string) => {
      return axios.post(`/payments/${id}/bounce`, { reason });
    },
    onSuccess: () => {
      toast.success('تم تسجيل ارتداد الشيك');
      queryClient.invalidateQueries({ queryKey: ['payment', id] });
      queryClient.invalidateQueries({ queryKey: ['payments'] });
      navigate({ to: '/payments' });
    },
  });

  const handleCollect = () => {
    const date = prompt('تاريخ التحصيل:', new Date().toISOString().split('T')[0]);
    if (date) {
      collectMutation.mutate(date);
    }
  };

  const handleBounce = () => {
    const reason = prompt('سبب الارتداد:');
    if (reason) {
      bounceMutation.mutate(reason);
    }
  };

  if (!payment || payment.payment_method !== 'cheque') {
    return <div>ليس شيكاً</div>;
  }

  if (payment.cheque_status !== 'pending') {
    return <div>الشيك تم معالجته بالفعل</div>;
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 className="text-2xl font-bold">تحصيل شيك</h1>
        <p className="text-gray-600">تحديث حالة الشيك</p>
      </div>

      {/* Cheque Info */}
      <Card className="p-6 space-y-4">
        <h3 className="font-semibold border-b pb-2">معلومات الشيك</h3>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <p className="text-sm text-gray-600">رقم الشيك</p>
            <p className="font-bold text-lg">{payment.cheque_number}</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">المبلغ</p>
            <p className="font-bold text-lg text-green-700">
              {payment.amount.toLocaleString('ar-EG')} جنيه
            </p>
          </div>
          <div>
            <p className="text-sm text-gray-600">البنك</p>
            <p className="font-medium">{payment.bank_name}</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">تاريخ الاستحقاق</p>
            <p className="font-medium">
              {format(new Date(payment.cheque_date), 'dd MMMM yyyy', { locale: ar })}
            </p>
          </div>
          <div>
            <p className="text-sm text-gray-600">العميل</p>
            <p className="font-medium">{payment.customer.name}</p>
          </div>
        </div>
      </Card>

      {/* Actions */}
      <Card className="p-6">
        <h3 className="font-semibold mb-4">إجراءات</h3>
        <div className="flex gap-4">
          <Button
            leftIcon={<CheckCircle />}
            onClick={handleCollect}
            loading={collectMutation.isPending}
            className="flex-1"
          >
            تحصيل الشيك
          </Button>
          <Button
            variant="danger"
            leftIcon={<XCircle />}
            onClick={handleBounce}
            loading={bounceMutation.isPending}
            className="flex-1"
          >
            تسجيل ارتداد
          </Button>
        </div>
      </Card>
    </div>
  );
}
```

#### Testing & Exit Criteria
- ✅ Collect working
- ✅ Bounce working
- ✅ 6+ tests passing

---

### ✅ TASK-604: Cheques Calendar View
**المدة:** 1 يوم (8 ساعات)  
**الأولوية:** 🟡 MEDIUM  
**Dependencies:** TASK-603

#### الهدف
عرض الشيكات على Calendar حسب تاريخ الاستحقاق

#### Development

```typescript
// src/features/payments/ChequesCalendarPage.tsx
import { useQuery } from '@tanstack/react-query';
import { Calendar } from '@/components/ui/Calendar';
import { Badge } from '@/components/ui/Badge';
import axios from '@/app/axios';
import { format, parseISO } from 'date-fns';
import { ar } from 'date-fns/locale';

export function ChequesCalendarPage() {
  const { data: cheques } = useQuery({
    queryKey: ['cheques-calendar'],
    queryFn: async () => {
      const { data } = await axios.get('/payments/cheques/calendar');
      return data.data;
    },
  });

  const renderDay = (date: Date) => {
    const dateStr = format(date, 'yyyy-MM-dd');
    const dayCheques = cheques?.filter((c: any) => 
      format(parseISO(c.cheque_date), 'yyyy-MM-dd') === dateStr
    ) || [];

    if (dayCheques.length === 0) return null;

    const total = dayCheques.reduce((sum: number, c: any) => sum + c.amount, 0);

    return (
      <div className="mt-1">
        <Badge variant="warning" size="sm">
          {dayCheques.length} شيك
        </Badge>
        <p className="text-xs text-gray-600 mt-1">
          {total.toLocaleString('ar-EG')} ج
        </p>
      </div>
    );
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">تقويم الشيكات</h1>
        <p className="text-gray-600">عرض مواعيد استحقاق الشيكات</p>
      </div>

      <Calendar renderDay={renderDay} />

      {/* Legend */}
      <div className="flex gap-4 text-sm">
        <div className="flex items-center gap-2">
          <div className="w-4 h-4 bg-yellow-200 rounded"></div>
          <span>شيكات معلقة</span>
        </div>
        <div className="flex items-center gap-2">
          <div className="w-4 h-4 bg-green-200 rounded"></div>
          <span>شيكات محصّلة</span>
        </div>
        <div className="flex items-center gap-2">
          <div className="w-4 h-4 bg-red-200 rounded"></div>
          <span>شيكات مرتدة</span>
        </div>
      </div>
    </div>
  );
}
```

#### Exit Criteria
- ✅ Calendar view working
- ✅ Cheques displayed correctly
- ✅ 4+ tests passing

---

### ✅ TASK-605: Payment Receipt Print
**المدة:** 4 ساعات  
**الأولوية:** 🟡 MEDIUM  
**Dependencies:** TASK-604

#### Development

```blade
<!-- resources/views/receipts/payment-receipt.blade.php -->
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إيصال استلام - {{ $payment->id }}</title>
    <style>
        /* Similar to voucher print template */
        /* Show payment details, customer, amount, signature box */
    </style>
</head>
<body>
    <div class="header">
        <h1>إيصال استلام</h1>
        <p>رقم: {{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="content">
        <p>استلمنا من السيد/السيدة: <strong>{{ $payment->customer->name }}</strong></p>
        <p>مبلغ وقدره: <strong>{{ $payment->amount }} جنيه مصري</strong></p>
        <p>وذلك بتاريخ: {{ $payment->payment_date->format('d/m/Y') }}</p>
        
        @if($payment->payment_method === 'cheque')
        <p>عن طريق شيك رقم: <strong>{{ $payment->cheque_number }}</strong></p>
        <p>بنك: {{ $payment->bank_name }}</p>
        <p>تاريخ الاستحقاق: {{ $payment->cheque_date->format('d/m/Y') }}</p>
        @else
        <p>الدفع: <strong>نقدي</strong></p>
        @endif
    </div>

    <div class="signature">
        <p>التوقيع: _________________</p>
    </div>
</body>
</html>
```

---

### ✅ TASK-606: Payment Delete (Admin Only)
**المدة:** 3 ساعات  
**الأولوية:** 🟡 MEDIUM  
**Dependencies:** TASK-605

#### Development

```typescript
// Only admin can delete payments
// Deletes payment record and reverses customer balance
```

---

## 🏷️ Module 5 Summary

**Payments & Cheques Complete!** ✅

| Task | Status | Tests | Duration |
|------|--------|-------|----------|
| TASK-601 | ✅ | 8+ | 10h |
| TASK-602 | ✅ | 8+ | 8h |
| TASK-603 | ✅ | 6+ | 6h |
| TASK-604 | ✅ | 4+ | 8h |
| TASK-605 | ✅ | 2+ | 4h |
| TASK-606 | ✅ | 3+ | 3h |

**Total:** 39 hours (4.875 days)  
**Total Tests:** 31+

---

## 🎯 Part 2 Complete Summary

### ✅ All Modules Done!

| Module | Tasks | Tests | Duration |
|--------|-------|-------|----------|
| **1. Products** | 5 | 26+ | 40h |
| **2. Issue Vouchers** | 5 | 37+ | 54h |
| **3. Return Vouchers** | 5 | 30+ | 39h |
| **4. Customers & Ledger** | 7 | 38+ | 49h |
| **5. Payments & Cheques** | 6 | 31+ | 39h |

### 📊 Grand Total - Part 2

- **Total Tasks:** 28 tasks (TASK-201 to TASK-606)
- **Total Tests:** 162+ tests
- **Total Duration:** 221 hours (27.6 days)
- **Completion:** 100% ✅

---

## 🚀 What's Next? Part 3!

**Part 3 سيغطي:**
- **Module 6:** Reports & Analytics (10 reports)
- **Module 7:** Role-Based Features (permissions UI, branch switching)
- **Module 8:** Performance & Polish (optimization, keyboard shortcuts)
- **Module 9:** Testing & QA (E2E, load testing)
- **Module 10:** Production Deployment (build, upload to Hostinger)

---

**🎉 Part 2 خلص! جاهز لـ Part 3؟** 🔥
