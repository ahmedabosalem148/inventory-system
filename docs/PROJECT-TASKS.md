# 📋 Project Tasks - نظام إدارة المخزون
## خطة التنفيذ الكاملة من الألف إلى الياء

**تاريخ الإنشاء:** 14 أكتوبر 2025  
**Project Manager:** The GOAT 🐐  
**المنهجية:** Agile + TDD + Continuous Integration  
**المدة المتوقعة:** 12 أسبوع (60 يوم عمل)

---

## 🎯 نظرة عامة على المشروع

### الحالة الحالية (As-Is)
| المكون | النسبة المئوية | الحالة | التفاصيل |
|--------|-----------------|--------|----------|
| **Backend** | **100%** | ✅ مكتمل | 107/107 tests passing |
| **Frontend** | **35%** | ⏳ جزئي | React setup + بعض الصفحات |
| **Integration** | **20%** | ⏳ جزئي | API موجود لكن Frontend غير مكتمل |
| **Testing** | **Backend: 100%<br>Frontend: 0%** | ⚠️ | Frontend يحتاج tests |
| **Documentation** | **80%** | ⏳ | Backend موثّق، Frontend يحتاج |
| **Deployment** | **0%** | ❌ | لم يبدأ |

### الهدف النهائي (To-Be)
- ✅ Backend 100% (Already Done! 🎉)
- ✅ Frontend 100% (التركيز الرئيسي)
- ✅ Integration 100%
- ✅ Testing: Unit + Integration + E2E (100%)
- ✅ Documentation 100%
- ✅ Production Deployment على Hostinger

---

## 📊 هيكل العمل (Work Structure)

```
كل Task يمر بـ 5 مراحل:

1️⃣ Development (التطوير)
   └─ كتابة الكود الفعلي

2️⃣ Unit Testing (اختبارات الوحدة)
   └─ اختبار كل component/function لوحده

3️⃣ Integration Testing (اختبارات التكامل)
   └─ اختبار تكامل المكونات مع بعضها

4️⃣ User Testing (اختبارات المستخدم)
   └─ اختبار السيناريوهات الكاملة من المستخدم

5️⃣ Code Review & Merge (مراجعة ودمج)
   └─ مراجعة الكود ودمجه في main

✅ إذا نجحت كل المراحل → ننتقل للـ Task التالي
❌ إذا فشلت أي مرحلة → نرجع نصلح ونعيد الاختبار
```

---

## 🔍 Phase 0: Pre-Development Checklist

### ✅ TASK-000: Backend Verification (تحقق من الـ Backend)
**المدة:** يوم واحد (2 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**الحالة:** ⏳ Pending

#### الهدف
التأكد من أن الـ Backend 100% جاهز وكامل حسب المتطلبات

#### Checklist

**1. الأنظمة الأساسية (Core Systems)**
- [ ] ✅ Products Management (CRUD)
  - [ ] API: GET /api/v1/products
  - [ ] API: POST /api/v1/products
  - [ ] API: PATCH /api/v1/products/{id}
  - [ ] API: DELETE /api/v1/products/{id}
  - [ ] Pack size support (حجم الكرتونة)
  - [ ] Min stock per branch

- [ ] ✅ Branches Management
  - [ ] 3 branches seeded (المصنع، العتبة، إمبابة)
  - [ ] API: GET /api/v1/branches
  - [ ] Branch-scoped data

- [ ] ✅ Users & Roles
  - [ ] 3 roles: manager, accountant, store_user
  - [ ] Permissions configured
  - [ ] API: GET /api/v1/auth/me

**2. حركات المخزون (Inventory)**
- [ ] ✅ Inventory Movements (7/7 tests)
  - [ ] ADD, ISSUE, RETURN, TRANSFER_OUT, TRANSFER_IN
  - [ ] Running balance (product_branch.current_qty)
  - [ ] Ref linking (ref_table, ref_id)

- [ ] ✅ Negative Stock Prevention (7/7 tests)
  - [ ] Validation على كل العمليات
  - [ ] Transaction safety

**3. أذونات الصرف والإرجاع**
- [ ] ✅ Issue Vouchers (Tests?)
  - [ ] API: GET /api/v1/issue-vouchers
  - [ ] API: POST /api/v1/issue-vouchers
  - [ ] API: POST /api/v1/issue-vouchers/{id}/approve
  - [ ] Line + Header discounts
  - [ ] Sequencing (no gaps)

- [ ] ✅ Return Vouchers (20/20 tests)
  - [ ] API: GET /api/v1/return-vouchers
  - [ ] API: POST /api/v1/return-vouchers
  - [ ] API: POST /api/v1/return-vouchers/{id}/approve
  - [ ] Sequence range: 100001-125000

**4. التحويلات بين المخازن**
- [ ] ✅ Branch Transfers (16/16 tests)
  - [ ] API: POST /api/v1/transfers
  - [ ] Creates TRANSFER_OUT + TRANSFER_IN
  - [ ] Automatic linking

**5. العملاء ودفتر الحسابات**
- [ ] ✅ Customers (16/16 tests)
  - [ ] API: GET /api/v1/customers
  - [ ] API: POST /api/v1/customers
  - [ ] API: GET /api/v1/customers-balances
  - [ ] Auto customer code (CUS-XXXXX)

- [ ] ✅ Customer Ledger
  - [ ] API: GET /api/v1/customers/{id}/statement
  - [ ] API: GET /api/v1/customers/{id}/balance
  - [ ] API: GET /api/v1/customers/{id}/activity
  - [ ] Debit/Credit (علية/له)
  - [ ] Running balance

**6. المدفوعات والشيكات**
- [ ] ✅ Payments
  - [ ] API: POST /api/v1/payments

- [ ] ✅ Cheques (10/10 tests)
  - [ ] API: GET /api/v1/cheques
  - [ ] API: POST /api/v1/cheques
  - [ ] API: PATCH /api/v1/cheques/{id}/collect
  - [ ] State machine: PENDING → CLEARED/RETURNED

**7. التقارير**
- [ ] ✅ Inventory Reports (10/10 tests)
  - [ ] API: GET /api/v1/reports/stock-summary
  - [ ] API: GET /api/v1/reports/product-movements
  - [ ] API: GET /api/v1/reports/low-stock
  - [ ] API: GET /api/v1/reports/stock-by-branch

- [ ] ✅ Customer Reports
  - [ ] API: GET /api/v1/customers-statistics

**8. الترقيم والتسلسل**
- [ ] ✅ Sequencing (8/8 tests)
  - [ ] No gaps في الترقيم
  - [ ] Sequence per document type
  - [ ] Transaction safety with FOR UPDATE

**9. الطباعة والتصدير**
- [ ] ✅ PDF Generation
  - [ ] API: GET /api/v1/issue-vouchers/{id}/pdf
  - [ ] API: GET /api/v1/return-vouchers/{id}/pdf
  - [ ] Arabic RTL support

**10. الأمان والتدقيق**
- [ ] ✅ Authentication (JWT)
  - [ ] API: POST /api/v1/auth/login
  - [ ] API: POST /api/v1/auth/logout
  - [ ] API: GET /api/v1/auth/me

- [ ] ⏳ Activity Log (Optional)
  - [ ] تسجيل كل العمليات الحساسة

---

#### Testing

**Unit Tests:**
```bash
php artisan test --filter=ProductTest
php artisan test --filter=IssueVoucherTest
php artisan test --filter=ReturnVoucherTest
php artisan test --filter=InventoryMovementTest
php artisan test --filter=CustomerLedgerTest
php artisan test --filter=ChequeTest
php artisan test --filter=SequencerTest
php artisan test --filter=BranchTransferTest

# Expected: ALL PASS ✅
```

**Integration Tests:**
```bash
php artisan test tests/Integration/

# Expected: 107/107 PASS ✅
```

#### User Testing

**Manual Test Scenarios:**
1. إنشاء منتج جديد
2. إنشاء إذن صرف → اعتماد → تحديث المخزون
3. إنشاء إذن إرجاع → اعتماد → زيادة المخزون
4. تحويل بين فروع → تحديث المخزونين
5. دفتر عميل → قيود صحيحة
6. طباعة PDF

#### Exit Criteria
- ✅ كل الـ APIs تعمل بشكل صحيح
- ✅ 107/107 tests passing
- ✅ Manual scenarios تعمل بنجاح
- ✅ لا توجد Bugs حرجة

#### Output
- `BACKEND-VERIFICATION-REPORT.md` - تقرير كامل بالنتائج

---

## 📦 Phase 1: Frontend Foundation (الأساسات)

### ✅ TASK-101: Project Setup & Configuration
**المدة:** يوم واحد (6 ساعات)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-000  
**الحالة:** ⏳ Pending

#### الهدف
إعداد مشروع React كامل مع كل التقنيات المطلوبة

#### Development

**1. إنشاء المشروع:**
```bash
# من مجلد الـ project root
npm create vite@latest frontend -- --template react-ts
cd frontend
```

**2. تنصيب Dependencies الأساسية:**
```bash
# Core
npm install react@18 react-dom@18
npm install @tanstack/react-query@5
npm install @tanstack/react-router@1
npm install axios
npm install zod
npm install react-hook-form @hookform/resolvers

# UI & Styling
npm install tailwindcss postcss autoprefixer
npm install -D tailwindcss-rtl
npm install @radix-ui/react-dialog
npm install @radix-ui/react-dropdown-menu
npm install @radix-ui/react-select
npm install @radix-ui/react-toast
npm install @radix-ui/react-tooltip
npm install lucide-react

# State & Utils
npm install zustand
npm install date-fns
npm install react-hot-toast

# Dev Dependencies
npm install -D @types/node
npm install -D @types/react
npm install -D @types/react-dom
npm install -D eslint
npm install -D prettier
npm install -D vitest
npm install -D @testing-library/react
npm install -D @testing-library/jest-dom
npm install -D @testing-library/user-event
```

**3. إعداد Tailwind RTL:**
```bash
npx tailwindcss init -p
```

```javascript
// tailwind.config.js
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Cairo', 'Segoe UI', 'sans-serif'],
      },
    },
  },
  plugins: [
    require('tailwindcss-rtl'),
  ],
};
```

```css
/* src/index.css */
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap');

@tailwind base;
@tailwind components;
@tailwind utilities;

* {
  direction: rtl;
}

body {
  font-family: 'Cairo', 'Segoe UI', sans-serif;
}
```

**4. هيكل المجلدات:**
```
frontend/
├── src/
│   ├── app/
│   │   ├── router.tsx
│   │   ├── queryClient.ts
│   │   └── axios.ts
│   ├── components/
│   │   ├── ui/          # shadcn/ui primitives
│   │   ├── layout/      # Layout components
│   │   └── shared/      # Reusable components
│   ├── features/
│   │   ├── auth/
│   │   ├── products/
│   │   ├── vouchers/
│   │   ├── customers/
│   │   └── reports/
│   ├── hooks/
│   ├── lib/
│   ├── types/
│   ├── utils/
│   └── main.tsx
├── public/
├── index.html
├── package.json
├── tsconfig.json
├── vite.config.ts
└── tailwind.config.js
```

**5. إعداد Axios:**
```typescript
// src/app/axios.ts
import axios from 'axios';

const axiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor
axiosInstance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('access_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor
axiosInstance.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('access_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default axiosInstance;
```

**6. إعداد React Query:**
```typescript
// src/app/queryClient.ts
import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
      staleTime: 5 * 60 * 1000, // 5 minutes
    },
  },
});
```

**7. إعداد Environment Variables:**
```bash
# .env
VITE_API_URL=http://localhost:8000/api/v1
```

#### Unit Testing

```bash
# تنصيب Vitest
npm install -D vitest @vitest/ui
```

```typescript
// vitest.config.ts
import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: './src/test/setup.ts',
  },
});
```

```typescript
// src/test/setup.ts
import { expect, afterEach } from 'vitest';
import { cleanup } from '@testing-library/react';
import * as matchers from '@testing-library/jest-dom/matchers';

expect.extend(matchers);

afterEach(() => {
  cleanup();
});
```

**Test Case 1: Axios Configuration**
```typescript
// src/app/__tests__/axios.test.ts
import { describe, it, expect } from 'vitest';
import axiosInstance from '../axios';

describe('Axios Instance', () => {
  it('should have correct base URL', () => {
    expect(axiosInstance.defaults.baseURL).toBeDefined();
  });

  it('should have correct headers', () => {
    expect(axiosInstance.defaults.headers['Content-Type']).toBe('application/json');
  });
});
```

**Test Case 2: Query Client**
```typescript
// src/app/__tests__/queryClient.test.ts
import { describe, it, expect } from 'vitest';
import { queryClient } from '../queryClient';

describe('Query Client', () => {
  it('should be defined', () => {
    expect(queryClient).toBeDefined();
  });

  it('should have default options', () => {
    const options = queryClient.getDefaultOptions();
    expect(options.queries?.retry).toBe(1);
    expect(options.queries?.staleTime).toBe(5 * 60 * 1000);
  });
});
```

```bash
# Run tests
npm run test

# Expected: 2/2 PASS ✅
```

#### Integration Testing

**Test: Full Setup Works**
```typescript
// src/test/integration/setup.test.tsx
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '../../app/queryClient';

describe('Project Setup Integration', () => {
  it('should render with QueryClientProvider', () => {
    render(
      <QueryClientProvider client={queryClient}>
        <div>Test App</div>
      </QueryClientProvider>
    );
    
    expect(screen.getByText('Test App')).toBeInTheDocument();
  });
});
```

```bash
# Run integration tests
npm run test -- --run src/test/integration

# Expected: PASS ✅
```

#### User Testing

**Manual Test:**
1. `npm run dev` → يشتغل بدون أخطاء ✅
2. فتح `http://localhost:5173` → صفحة تظهر ✅
3. Console بدون errors ✅
4. RTL يشتغل (النص من اليمين) ✅

#### Code Review Checklist
- [ ] كل الملفات موجودة
- [ ] Dependencies مثبتة بنجاح
- [ ] Tailwind RTL يشتغل
- [ ] Axios configured صح
- [ ] React Query configured صح
- [ ] Tests تعدي
- [ ] Dev server يشتغل

#### Exit Criteria
- ✅ 2/2 unit tests passing
- ✅ 1/1 integration test passing
- ✅ Dev server يشتغل بدون errors
- ✅ Tailwind RTL يشتغل

#### Output
- Frontend project جاهز ✅
- `package.json` كامل
- Folder structure منظم

---

### ✅ TASK-102: Design System & UI Components
**المدة:** 2 أيام (12 ساعة)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-101  
**الحالة:** ⏳ Pending

#### الهدف
بناء مكتبة Components أساسية reusable (15+ component)

#### Development

**Components List:**
1. Button
2. Input
3. Select
4. Dialog (Modal)
5. Toast
6. Card
7. Badge
8. Spinner
9. Skeleton
10. Tooltip
11. DropdownMenu
12. Table (DataTable)
13. FormField
14. DatePicker
15. SearchInput

**مثال - Button Component:**
```typescript
// src/components/ui/Button.tsx
import { ButtonHTMLAttributes, forwardRef } from 'react';
import { Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'ghost';
  size?: 'sm' | 'md' | 'lg';
  loading?: boolean;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant = 'primary', size = 'md', loading, children, disabled, ...props }, ref) => {
    const baseStyles = 'inline-flex items-center justify-center rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none';
    
    const variants = {
      primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
      secondary: 'bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500',
      success: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
      danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
      ghost: 'hover:bg-gray-100 focus:ring-gray-500',
    };
    
    const sizes = {
      sm: 'h-8 px-3 text-sm',
      md: 'h-10 px-4',
      lg: 'h-12 px-6 text-lg',
    };
    
    return (
      <button
        ref={ref}
        className={cn(baseStyles, variants[variant], sizes[size], className)}
        disabled={loading || disabled}
        {...props}
      >
        {loading && <Loader2 className="ml-2 h-4 w-4 animate-spin" />}
        {children}
      </button>
    );
  }
);

Button.displayName = 'Button';
```

**مثال - Input Component:**
```typescript
// src/components/ui/Input.tsx
import { InputHTMLAttributes, forwardRef } from 'react';
import { cn } from '@/lib/utils';

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  error?: string;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ className, type, error, ...props }, ref) => {
    return (
      <div className="w-full">
        <input
          type={type}
          className={cn(
            'flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm',
            'placeholder:text-gray-400',
            'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
            'disabled:cursor-not-allowed disabled:opacity-50',
            error && 'border-red-500 focus:ring-red-500',
            className
          )}
          ref={ref}
          {...props}
        />
        {error && (
          <p className="mt-1 text-sm text-red-600">{error}</p>
        )}
      </div>
    );
  }
);

Input.displayName = 'Input';
```

**مثال - DataTable Component:**
```typescript
// src/components/shared/DataTable.tsx
import { ReactNode } from 'react';

interface Column<T> {
  key: keyof T | string;
  header: string;
  render?: (item: T) => ReactNode;
  sortable?: boolean;
}

interface DataTableProps<T> {
  data: T[];
  columns: Column<T>[];
  loading?: boolean;
  onRowClick?: (item: T) => void;
}

export function DataTable<T>({ data, columns, loading, onRowClick }: DataTableProps<T>) {
  if (loading) {
    return <div>جاري التحميل...</div>;
  }

  if (data.length === 0) {
    return <div className="text-center py-8 text-gray-500">لا توجد بيانات</div>;
  }

  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            {columns.map((column) => (
              <th
                key={String(column.key)}
                className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                {column.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {data.map((item, index) => (
            <tr
              key={index}
              onClick={() => onRowClick?.(item)}
              className={onRowClick ? 'hover:bg-gray-50 cursor-pointer' : ''}
            >
              {columns.map((column) => (
                <td key={String(column.key)} className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {column.render ? column.render(item) : String(item[column.key as keyof T])}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

**Utility - cn() function:**
```typescript
// src/lib/utils.ts
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}
```

#### Unit Testing

**Test Case: Button Component**
```typescript
// src/components/ui/__tests__/Button.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { Button } from '../Button';

describe('Button Component', () => {
  it('should render children', () => {
    render(<Button>Click Me</Button>);
    expect(screen.getByText('Click Me')).toBeInTheDocument();
  });

  it('should handle click events', () => {
    const handleClick = vi.fn();
    render(<Button onClick={handleClick}>Click Me</Button>);
    
    fireEvent.click(screen.getByText('Click Me'));
    expect(handleClick).toHaveBeenCalledOnce();
  });

  it('should show loading state', () => {
    render(<Button loading>Click Me</Button>);
    expect(screen.getByRole('button')).toBeDisabled();
  });

  it('should apply variant styles', () => {
    const { rerender } = render(<Button variant="primary">Primary</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-blue-600');
    
    rerender(<Button variant="danger">Danger</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-red-600');
  });
});
```

**Test Case: Input Component**
```typescript
// src/components/ui/__tests__/Input.test.tsx
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Input } from '../Input';

describe('Input Component', () => {
  it('should render input field', () => {
    render(<Input placeholder="Enter text" />);
    expect(screen.getByPlaceholderText('Enter text')).toBeInTheDocument();
  });

  it('should display error message', () => {
    render(<Input error="This field is required" />);
    expect(screen.getByText('This field is required')).toBeInTheDocument();
  });

  it('should apply error styles when error exists', () => {
    render(<Input error="Error" />);
    const input = screen.getByRole('textbox');
    expect(input).toHaveClass('border-red-500');
  });
});
```

**Test Case: DataTable Component**
```typescript
// src/components/shared/__tests__/DataTable.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { DataTable } from '../DataTable';

const mockData = [
  { id: 1, name: 'منتج 1', price: 100 },
  { id: 2, name: 'منتج 2', price: 200 },
];

const mockColumns = [
  { key: 'id', header: 'المعرف' },
  { key: 'name', header: 'الاسم' },
  { key: 'price', header: 'السعر' },
];

describe('DataTable Component', () => {
  it('should render table with data', () => {
    render(<DataTable data={mockData} columns={mockColumns} />);
    
    expect(screen.getByText('منتج 1')).toBeInTheDocument();
    expect(screen.getByText('منتج 2')).toBeInTheDocument();
  });

  it('should display empty state when no data', () => {
    render(<DataTable data={[]} columns={mockColumns} />);
    expect(screen.getByText('لا توجد بيانات')).toBeInTheDocument();
  });

  it('should handle row click', () => {
    const handleRowClick = vi.fn();
    render(<DataTable data={mockData} columns={mockColumns} onRowClick={handleRowClick} />);
    
    fireEvent.click(screen.getByText('منتج 1'));
    expect(handleRowClick).toHaveBeenCalledWith(mockData[0]);
  });

  it('should display loading state', () => {
    render(<DataTable data={[]} columns={mockColumns} loading />);
    expect(screen.getByText('جاري التحميل...')).toBeInTheDocument();
  });
});
```

```bash
# Run all component tests
npm run test -- src/components

# Expected: 10+ tests PASS ✅
```

#### Integration Testing

**Test: Components Work Together**
```typescript
// src/test/integration/components.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';

describe('Components Integration', () => {
  it('should render form with Button and Input', () => {
    const handleSubmit = vi.fn();
    
    render(
      <form onSubmit={handleSubmit}>
        <Input placeholder="الاسم" />
        <Button type="submit">حفظ</Button>
      </form>
    );
    
    expect(screen.getByPlaceholderText('الاسم')).toBeInTheDocument();
    expect(screen.getByText('حفظ')).toBeInTheDocument();
  });
});
```

#### User Testing

**Storybook (Optional but Recommended):**
```bash
npm install -D @storybook/react @storybook/react-vite
npx storybook@latest init
```

**Manual Test:**
1. إنشاء صفحة test في `/src/pages/ComponentsTest.tsx`
2. عرض كل الـ components
3. التأكد من:
   - ✅ Button بكل الـ variants
   - ✅ Input عادي + مع error
   - ✅ DataTable مع بيانات + فاضي
   - ✅ Loading states
   - ✅ RTL يشتغل صح

#### Code Review Checklist
- [ ] 15 components موجودة
- [ ] كل component له test
- [ ] TypeScript types صحيحة
- [ ] Tailwind classes صحيحة
- [ ] RTL يشتغل
- [ ] Accessible (a11y)

#### Exit Criteria
- ✅ 15+ components جاهزة
- ✅ 10+ unit tests passing
- ✅ Integration tests passing
- ✅ Components تشتغل بدون bugs
- ✅ Storybook جاهز (optional)

#### Output
- 15+ reusable components ✅
- Test coverage > 80%
- Component library جاهز للاستخدام

---

### ✅ TASK-103: Authentication System
**المدة:** 2 أيام (12 ساعة)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-102  
**الحالة:** ⏳ Pending

#### الهدف
بناء نظام تسجيل دخول كامل مع JWT + Protected Routes

#### Development

**1. Auth Context:**
```typescript
// src/features/auth/AuthContext.tsx
import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import axios from '@/app/axios';
import { User } from '@/types';

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Check if user is logged in
    const token = localStorage.getItem('access_token');
    if (token) {
      fetchUser();
    } else {
      setIsLoading(false);
    }
  }, []);

  const fetchUser = async () => {
    try {
      const { data } = await axios.get('/auth/me');
      setUser(data.data);
    } catch (error) {
      localStorage.removeItem('access_token');
    } finally {
      setIsLoading(false);
    }
  };

  const login = async (email: string, password: string) => {
    const { data } = await axios.post('/auth/login', { email, password });
    localStorage.setItem('access_token', data.access_token);
    setUser(data.user);
  };

  const logout = () => {
    localStorage.removeItem('access_token');
    setUser(null);
    window.location.href = '/login';
  };

  return (
    <AuthContext.Provider value={{ user, isAuthenticated: !!user, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) throw new Error('useAuth must be used within AuthProvider');
  return context;
};
```

**2. Login Page:**
```typescript
// src/features/auth/LoginPage.tsx
import { useState } from 'react';
import { useAuth } from './AuthContext';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Card } from '@/components/ui/Card';
import toast from 'react-hot-toast';

export function LoginPage() {
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);

    try {
      await login(email, password);
      toast.success('تم تسجيل الدخول بنجاح');
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'فشل تسجيل الدخول');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100">
      <Card className="w-full max-w-md p-8">
        <h1 className="text-2xl font-bold text-center mb-6">تسجيل الدخول</h1>
        
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium mb-1">البريد الإلكتروني</label>
            <Input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="example@email.com"
              required
            />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">كلمة المرور</label>
            <Input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••"
              required
            />
          </div>

          <Button type="submit" className="w-full" loading={loading}>
            دخول
          </Button>
        </form>
      </Card>
    </div>
  );
}
```

**3. Protected Route:**
```typescript
// src/components/ProtectedRoute.tsx
import { Navigate } from '@tanstack/react-router';
import { useAuth } from '@/features/auth/AuthContext';
import { ReactNode } from 'react';

interface ProtectedRouteProps {
  children: ReactNode;
  requiredPermission?: string;
}

export function ProtectedRoute({ children, requiredPermission }: ProtectedRouteProps) {
  const { isAuthenticated, isLoading, user } = useAuth();

  if (isLoading) {
    return <div>جاري التحميل...</div>;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" />;
  }

  if (requiredPermission && !user?.permissions.includes(requiredPermission)) {
    return <div>ليس لديك صلاحية للوصول لهذه الصفحة</div>;
  }

  return <>{children}</>;
}
```

**4. Types:**
```typescript
// src/types/index.ts
export interface User {
  id: number;
  name: string;
  email: string;
  role: 'manager' | 'accountant' | 'store_user';
  branch_id?: number;
  branch?: Branch;
  permissions: string[];
}

export interface Branch {
  id: number;
  code: string;
  name: string;
}
```

#### Unit Testing

```typescript
// src/features/auth/__tests__/AuthContext.test.tsx
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { renderHook, act, waitFor } from '@testing-library/react';
import { AuthProvider, useAuth } from '../AuthContext';
import axios from '@/app/axios';

vi.mock('@/app/axios');

describe('AuthContext', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.clearAllMocks();
  });

  it('should provide auth context', () => {
    const wrapper = ({ children }: any) => <AuthProvider>{children}</AuthProvider>;
    const { result } = renderHook(() => useAuth(), { wrapper });

    expect(result.current).toBeDefined();
    expect(result.current.isAuthenticated).toBe(false);
  });

  it('should login successfully', async () => {
    const mockUser = { id: 1, name: 'Test User', email: 'test@test.com' };
    vi.mocked(axios.post).mockResolvedValue({
      data: { access_token: 'fake-token', user: mockUser },
    });

    const wrapper = ({ children }: any) => <AuthProvider>{children}</AuthProvider>;
    const { result } = renderHook(() => useAuth(), { wrapper });

    await act(async () => {
      await result.current.login('test@test.com', 'password');
    });

    expect(result.current.isAuthenticated).toBe(true);
    expect(result.current.user).toEqual(mockUser);
    expect(localStorage.getItem('access_token')).toBe('fake-token');
  });

  it('should logout successfully', async () => {
    localStorage.setItem('access_token', 'fake-token');
    
    const wrapper = ({ children }: any) => <AuthProvider>{children}</AuthProvider>;
    const { result } = renderHook(() => useAuth(), { wrapper });

    act(() => {
      result.current.logout();
    });

    expect(result.current.isAuthenticated).toBe(false);
    expect(localStorage.getItem('access_token')).toBeNull();
  });
});
```

```typescript
// src/features/auth/__tests__/LoginPage.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { LoginPage } from '../LoginPage';
import { AuthProvider } from '../AuthContext';

vi.mock('@/app/axios');

describe('LoginPage', () => {
  it('should render login form', () => {
    render(
      <AuthProvider>
        <LoginPage />
      </AuthProvider>
    );

    expect(screen.getByText('تسجيل الدخول')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('example@email.com')).toBeInTheDocument();
  });

  it('should handle form submission', async () => {
    render(
      <AuthProvider>
        <LoginPage />
      </AuthProvider>
    );

    const emailInput = screen.getByPlaceholderText('example@email.com');
    const passwordInput = screen.getByPlaceholderText('••••••••');
    const submitButton = screen.getByRole('button', { name: 'دخول' });

    fireEvent.change(emailInput, { target: { value: 'test@test.com' } });
    fireEvent.change(passwordInput, { target: { value: 'password' } });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(submitButton).toBeDisabled();
    });
  });
});
```

```bash
# Run tests
npm run test -- src/features/auth

# Expected: 5+ tests PASS ✅
```

#### Integration Testing

```typescript
// src/test/integration/auth.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { AuthProvider } from '@/features/auth/AuthContext';
import { LoginPage } from '@/features/auth/LoginPage';
import axios from '@/app/axios';

vi.mock('@/app/axios');

describe('Authentication Integration', () => {
  it('should complete full login flow', async () => {
    const mockUser = {
      id: 1,
      name: 'أحمد',
      email: 'ahmed@test.com',
      role: 'manager',
      permissions: [],
    };

    vi.mocked(axios.post).mockResolvedValue({
      data: { access_token: 'test-token', user: mockUser },
    });

    render(
      <BrowserRouter>
        <AuthProvider>
          <LoginPage />
        </AuthProvider>
      </BrowserRouter>
    );

    // Fill form
    fireEvent.change(screen.getByPlaceholderText('example@email.com'), {
      target: { value: 'ahmed@test.com' },
    });
    fireEvent.change(screen.getByPlaceholderText('••••••••'), {
      target: { value: 'password123' },
    });

    // Submit
    fireEvent.click(screen.getByRole('button', { name: 'دخول' }));

    // Verify API called
    await waitFor(() => {
      expect(axios.post).toHaveBeenCalledWith('/auth/login', {
        email: 'ahmed@test.com',
        password: 'password123',
      });
    });

    // Verify token saved
    expect(localStorage.getItem('access_token')).toBe('test-token');
  });
});
```

```bash
# Run integration tests
npm run test -- src/test/integration/auth

# Expected: PASS ✅
```

#### User Testing

**Test Scenarios:**

1. **Happy Path - تسجيل دخول ناجح**
   - افتح `/login`
   - أدخل: `admin@inventory.com` / `password`
   - اضغط "دخول"
   - ✅ يجب: الانتقال للـ Dashboard

2. **Wrong Credentials - بيانات خاطئة**
   - افتح `/login`
   - أدخل: `wrong@email.com` / `wrongpass`
   - اضغط "دخول"
   - ✅ يجب: رسالة خطأ "فشل تسجيل الدخول"

3. **Protected Route - حماية الصفحات**
   - بدون login، افتح `/dashboard`
   - ✅ يجب: إعادة توجيه لـ `/login`

4. **Logout - تسجيل خروج**
   - بعد Login، اضغط "تسجيل الخروج"
   - ✅ يجب: حذف Token + العودة لـ Login

5. **Token Expiry - انتهاء الجلسة**
   - احذف Token من LocalStorage
   - حاول فتح `/dashboard`
   - ✅ يجب: إعادة توجيه لـ `/login`

#### Code Review Checklist
- [ ] AuthContext يشتغل صح
- [ ] Login page بتصميم حلو
- [ ] Protected routes تشتغل
- [ ] Token management صحيح
- [ ] Error handling موجود
- [ ] Tests تعدي
- [ ] No security issues

#### Exit Criteria
- ✅ 5+ unit tests passing
- ✅ Integration test passing
- ✅ All 5 user scenarios تعمل
- ✅ No console errors
- ✅ Code reviewed & approved

#### Output
- ✅ Authentication system كامل
- ✅ Login page جاهز
- ✅ Protected routes جاهزة
- ✅ JWT integration يشتغل

---

### ✅ TASK-104: Layout & Navigation System
**المدة:** 2 أيام (12 ساعة)  
**الأولوية:** 🔴 CRITICAL  
**Dependencies:** TASK-103  
**الحالة:** ⏳ Pending

#### الهدف
بناء Layout أساسي مع Sidebar + Navbar + Role-based navigation

#### Development

**1. App Layout:**
```typescript
// src/components/layout/AppLayout.tsx
import { ReactNode } from 'react';
import { Sidebar } from './Sidebar';
import { Navbar } from './Navbar';
import { useAuth } from '@/features/auth/AuthContext';

interface AppLayoutProps {
  children: ReactNode;
}

export function AppLayout({ children }: AppLayoutProps) {
  const { user } = useAuth();

  return (
    <div className="min-h-screen bg-gray-50 flex">
      {/* Sidebar */}
      <Sidebar />

      {/* Main Content */}
      <div className="flex-1 flex flex-col">
        {/* Navbar */}
        <Navbar />

        {/* Page Content */}
        <main className="flex-1 p-6 overflow-auto">
          {children}
        </main>
      </div>
    </div>
  );
}
```

**2. Sidebar Component:**
```typescript
// src/components/layout/Sidebar.tsx
import { Link, useLocation } from '@tanstack/react-router';
import { useAuth } from '@/features/auth/AuthContext';
import { 
  LayoutDashboard, 
  Package, 
  FileText, 
  Users, 
  DollarSign,
  BarChart3,
  Settings,
  LogOut
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface NavItem {
  label: string;
  href: string;
  icon: any;
  permission?: string;
  roles?: string[];
}

export function Sidebar() {
  const { user, logout } = useAuth();
  const location = useLocation();

  const navItems: NavItem[] = [
    {
      label: 'الرئيسية',
      href: '/dashboard',
      icon: LayoutDashboard,
    },
    {
      label: 'المنتجات',
      href: '/products',
      icon: Package,
      permission: 'product.view',
    },
    {
      label: 'أذونات الصرف',
      href: '/issue-vouchers',
      icon: FileText,
      permission: 'voucher.issue.view',
    },
    {
      label: 'أذونات الإرجاع',
      href: '/return-vouchers',
      icon: FileText,
      permission: 'voucher.return.view',
    },
    {
      label: 'العملاء',
      href: '/customers',
      icon: Users,
      permission: 'customer.view',
    },
    {
      label: 'المدفوعات',
      href: '/payments',
      icon: DollarSign,
      permission: 'payment.view',
    },
    {
      label: 'التقارير',
      href: '/reports',
      icon: BarChart3,
      permission: 'report.view',
    },
    {
      label: 'الإعدادات',
      href: '/settings',
      icon: Settings,
      roles: ['manager'],
    },
  ];

  const canAccessItem = (item: NavItem) => {
    if (item.roles && !item.roles.includes(user?.role || '')) return false;
    if (item.permission && !user?.permissions.includes(item.permission)) return false;
    return true;
  };

  return (
    <aside className="w-64 bg-white border-l border-gray-200 flex flex-col">
      {/* Logo */}
      <div className="p-6 border-b border-gray-200">
        <h1 className="text-xl font-bold text-gray-900">نظام المخزون</h1>
        <p className="text-sm text-gray-500">{user?.branch?.name}</p>
      </div>

      {/* Navigation */}
      <nav className="flex-1 p-4 space-y-1">
        {navItems.filter(canAccessItem).map((item) => {
          const Icon = item.icon;
          const isActive = location.pathname === item.href;

          return (
            <Link
              key={item.href}
              to={item.href}
              className={cn(
                'flex items-center gap-3 px-4 py-3 rounded-lg transition-colors',
                isActive
                  ? 'bg-blue-50 text-blue-600'
                  : 'text-gray-700 hover:bg-gray-50'
              )}
            >
              <Icon className="w-5 h-5" />
              <span>{item.label}</span>
            </Link>
          );
        })}
      </nav>

      {/* User Info & Logout */}
      <div className="p-4 border-t border-gray-200">
        <div className="flex items-center gap-3 mb-2">
          <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
            <span className="text-blue-600 font-semibold">
              {user?.name.charAt(0)}
            </span>
          </div>
          <div className="flex-1">
            <p className="text-sm font-medium">{user?.name}</p>
            <p className="text-xs text-gray-500">{user?.role}</p>
          </div>
        </div>
        <button
          onClick={logout}
          className="w-full flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
        >
          <LogOut className="w-4 h-4" />
          <span>تسجيل الخروج</span>
        </button>
      </div>
    </aside>
  );
}
```

**3. Navbar Component:**
```typescript
// src/components/layout/Navbar.tsx
import { Bell, Search } from 'lucide-react';
import { Input } from '@/components/ui/Input';
import { Button } from '@/components/ui/Button';

export function Navbar() {
  return (
    <header className="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6">
      {/* Search */}
      <div className="flex-1 max-w-md">
        <div className="relative">
          <Search className="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
          <Input
            placeholder="بحث... (Ctrl+K)"
            className="pr-10"
          />
        </div>
      </div>

      {/* Actions */}
      <div className="flex items-center gap-4">
        {/* Notifications */}
        <button className="relative p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
          <Bell className="w-5 h-5" />
          <span className="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full" />
        </button>

        {/* Quick Actions */}
        <Button variant="primary" size="sm">
          + إذن جديد
        </Button>
      </div>
    </header>
  );
}
```

#### Unit Testing

```typescript
// src/components/layout/__tests__/Sidebar.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Sidebar } from '../Sidebar';
import { AuthProvider } from '@/features/auth/AuthContext';

vi.mock('@tanstack/react-router', () => ({
  Link: ({ children, to }: any) => <a href={to}>{children}</a>,
  useLocation: () => ({ pathname: '/dashboard' }),
}));

const mockUser = {
  id: 1,
  name: 'أحمد محمد',
  role: 'manager',
  permissions: ['product.view', 'voucher.issue.view'],
  branch: { id: 1, name: 'فرع العتبة' },
};

describe('Sidebar Component', () => {
  it('should render navigation items', () => {
    render(
      <AuthProvider>
        <Sidebar />
      </AuthProvider>
    );

    expect(screen.getByText('الرئيسية')).toBeInTheDocument();
    expect(screen.getByText('المنتجات')).toBeInTheDocument();
  });

  it('should highlight active route', () => {
    render(
      <AuthProvider>
        <Sidebar />
      </AuthProvider>
    );

    const dashboardLink = screen.getByText('الرئيسية').closest('a');
    expect(dashboardLink).toHaveClass('bg-blue-50');
  });

  it('should show user info', () => {
    render(
      <AuthProvider>
        <Sidebar />
      </AuthProvider>
    );

    expect(screen.getByText('أحمد محمد')).toBeInTheDocument();
  });
});
```

```bash
# Run tests
npm run test -- src/components/layout

# Expected: 5+ tests PASS ✅
```

#### Integration Testing

```typescript
// src/test/integration/layout.test.tsx
import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { AuthProvider } from '@/features/auth/AuthContext';
import { AppLayout } from '@/components/layout/AppLayout';

describe('Layout Integration', () => {
  it('should render complete layout', () => {
    render(
      <BrowserRouter>
        <AuthProvider>
          <AppLayout>
            <div>Test Content</div>
          </AppLayout>
        </AuthProvider>
      </BrowserRouter>
    );

    // Sidebar exists
    expect(screen.getByText('نظام المخزون')).toBeInTheDocument();
    
    // Navbar exists
    expect(screen.getByPlaceholderText(/بحث/)).toBeInTheDocument();
    
    // Content rendered
    expect(screen.getByText('Test Content')).toBeInTheDocument();
  });
});
```

#### User Testing

**Scenarios:**
1. ✅ Sidebar يظهر مع كل الـ links
2. ✅ Active route مميز بلون مختلف
3. ✅ User info يظهر في الأسفل
4. ✅ Logout button يشتغل
5. ✅ Role-based items (manager يشوف Settings، غيره لا)
6. ✅ Responsive (يخفي Sidebar على Mobile)

#### Exit Criteria
- ✅ Layout كامل يشتغل
- ✅ Sidebar navigation صحيحة
- ✅ Role-based filtering يشتغل
- ✅ Tests تعدي
- ✅ UI responsive

#### Output
- ✅ AppLayout component
- ✅ Sidebar component
- ✅ Navbar component
- ✅ Ready للاستخدام في كل الصفحات

---

### ✅ TASK-105: Dashboard Pages (3 Versions)
**المدة:** 3 أيام (18 ساعة)  
**الأولوية:** 🟠 HIGH  
**Dependencies:** TASK-104  
**الحالة:** ⏳ Pending

#### الهدف
بناء 3 Dashboards مخصصة لكل دور (Manager, Accountant, Store Manager)

#### Development

**1. Base Dashboard Component:**
```typescript
// src/features/dashboard/DashboardPage.tsx
import { useAuth } from '@/features/auth/AuthContext';
import { ManagerDashboard } from './ManagerDashboard';
import { AccountantDashboard } from './AccountantDashboard';
import { StoreManagerDashboard } from './StoreManagerDashboard';

export function DashboardPage() {
  const { user } = useAuth();

  switch (user?.role) {
    case 'manager':
      return <ManagerDashboard />;
    case 'accountant':
      return <AccountantDashboard />;
    case 'store_user':
      return <StoreManagerDashboard />;
    default:
      return <div>غير مصرح</div>;
  }
}
```

**2. Manager Dashboard:**
```typescript
// src/features/dashboard/ManagerDashboard.tsx
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { StatCard } from '@/components/shared/StatCard';
import { Package, FileText, Users, DollarSign } from 'lucide-react';
import axios from '@/app/axios';

export function ManagerDashboard() {
  const { data: stats } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: async () => {
      const { data } = await axios.get('/dashboard/stats');
      return data;
    },
  });

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">لوحة التحكم - المدير العام</h1>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard
          title="إجمالي المنتجات"
          value={stats?.total_products || 0}
          icon={Package}
          color="blue"
        />
        <StatCard
          title="أذونات اليوم"
          value={stats?.today_vouchers || 0}
          icon={FileText}
          color="green"
        />
        <StatCard
          title="عملاء نشطين"
          value={stats?.active_customers || 0}
          icon={Users}
          color="purple"
        />
        <StatCard
          title="قيمة المخزون"
          value={`${stats?.inventory_value || 0} ج`}
          icon={DollarSign}
          color="yellow"
        />
      </div>

      {/* Charts & Tables */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Branch Comparison */}
        <Card className="p-6">
          <h3 className="font-semibold mb-4">مبيعات الفروع</h3>
          {/* Chart here */}
        </Card>

        {/* Low Stock */}
        <Card className="p-6">
          <h3 className="font-semibold mb-4">مخزون منخفض</h3>
          {/* Table here */}
        </Card>
      </div>
    </div>
  );
}
```

**3. Accountant Dashboard:**
```typescript
// src/features/dashboard/AccountantDashboard.tsx
import { useQuery } from '@tanstack/react-query';
import { Card } from '@/components/ui/Card';
import { StatCard } from '@/components/shared/StatCard';
import { AlertCircle, CheckCircle, Clock, DollarSign } from 'lucide-react';
import axios from '@/app/axios';
import { Button } from '@/components/ui/Button';

export function AccountantDashboard() {
  const { data: pendingVouchers } = useQuery({
    queryKey: ['pending-vouchers'],
    queryFn: async () => {
      const { data } = await axios.get('/vouchers/pending');
      return data;
    },
  });

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">لوحة التحكم - المحاسب</h1>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <StatCard
          title="معلق"
          value={pendingVouchers?.length || 0}
          icon={Clock}
          color="yellow"
        />
        <StatCard
          title="مبيعات اليوم"
          value="45,000 ج"
          icon={DollarSign}
          color="green"
        />
        <StatCard
          title="مدفوعات اليوم"
          value="12,000 ج"
          icon={CheckCircle}
          color="blue"
        />
        <StatCard
          title="شيكات معلقة"
          value={8}
          icon={AlertCircle}
          color="red"
        />
      </div>

      {/* Pending Vouchers (Priority) */}
      <Card className="p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-semibold">أذونات تنتظر الاعتماد</h3>
          <Button variant="ghost" size="sm">عرض الكل</Button>
        </div>

        <div className="space-y-3">
          {pendingVouchers?.slice(0, 5).map((voucher: any) => (
            <div
              key={voucher.id}
              className="flex items-center justify-between p-4 bg-yellow-50 border border-yellow-200 rounded-lg"
            >
              <div>
                <p className="font-medium">إذن صرف #{voucher.id}</p>
                <p className="text-sm text-gray-600">
                  {voucher.customer_name} | {voucher.branch_name}
                </p>
              </div>
              <div className="flex items-center gap-2">
                <span className="font-semibold">{voucher.total} ج</span>
                <Button variant="success" size="sm">اعتماد</Button>
                <Button variant="danger" size="sm">رفض</Button>
              </div>
            </div>
          ))}
        </div>
      </Card>
    </div>
  );
}
```

**4. Store Manager Dashboard:**
```typescript
// src/features/dashboard/StoreManagerDashboard.tsx
import { useQuery } from '@tanstack/react-query';
import { useAuth } from '@/features/auth/AuthContext';
import { Card } from '@/components/ui/Card';
import { StatCard } from '@/components/shared/StatCard';
import { Package, FileText, AlertTriangle, ArrowRightLeft } from 'lucide-react';
import axios from '@/app/axios';

export function StoreManagerDashboard() {
  const { user } = useAuth();
  const branchId = user?.branch_id;

  const { data: stats } = useQuery({
    queryKey: ['branch-stats', branchId],
    queryFn: async () => {
      const { data } = await axios.get(`/branches/${branchId}/stats`);
      return data;
    },
  });

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">
        لوحة التحكم - {user?.branch?.name}
      </h1>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <StatCard
          title="مخزون الفرع"
          value={stats?.total_items || 0}
          icon={Package}
          color="blue"
        />
        <StatCard
          title="أذوناتي اليوم"
          value={stats?.today_vouchers || 0}
          icon={FileText}
          color="green"
        />
        <StatCard
          title="معلقة (اعتماد)"
          value={stats?.pending_vouchers || 0}
          icon={AlertTriangle}
          color="yellow"
        />
        <StatCard
          title="تحويلات"
          value={stats?.transfers || 0}
          icon={ArrowRightLeft}
          color="purple"
        />
      </div>

      {/* Alerts */}
      <Card className="p-6">
        <h3 className="font-semibold mb-4 text-red-600">⚠️ تنبيهات المخزون</h3>
        <div className="space-y-2">
          <div className="flex items-center justify-between p-3 bg-red-50 rounded">
            <span>لمبة LED 10W</span>
            <span className="text-red-600 font-semibold">15 وحدة (الحد الأدنى: 50)</span>
          </div>
        </div>
      </Card>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Button className="h-20" variant="primary">
          + إذن صرف جديد
        </Button>
        <Button className="h-20" variant="secondary">
          + إذن إرجاع
        </Button>
        <Button className="h-20" variant="secondary">
          🔄 تحويل لفرع آخر
        </Button>
      </div>
    </div>
  );
}
```

**5. Shared Components:**
```typescript
// src/components/shared/StatCard.tsx
import { LucideIcon } from 'lucide-react';
import { Card } from '@/components/ui/Card';
import { cn } from '@/lib/utils';

interface StatCardProps {
  title: string;
  value: string | number;
  icon: LucideIcon;
  color: 'blue' | 'green' | 'yellow' | 'red' | 'purple';
}

export function StatCard({ title, value, icon: Icon, color }: StatCardProps) {
  const colors = {
    blue: 'bg-blue-100 text-blue-600',
    green: 'bg-green-100 text-green-600',
    yellow: 'bg-yellow-100 text-yellow-600',
    red: 'bg-red-100 text-red-600',
    purple: 'bg-purple-100 text-purple-600',
  };

  return (
    <Card className="p-6">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm text-gray-600 mb-1">{title}</p>
          <p className="text-2xl font-bold">{value}</p>
        </div>
        <div className={cn('p-3 rounded-lg', colors[color])}>
          <Icon className="w-6 h-6" />
        </div>
      </div>
    </Card>
  );
}
```

#### Unit Testing

```typescript
// src/features/dashboard/__tests__/DashboardPage.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/app/queryClient';
import { AuthProvider } from '@/features/auth/AuthContext';
import { DashboardPage } from '../DashboardPage';

vi.mock('@/features/auth/AuthContext', () => ({
  useAuth: () => ({
    user: { role: 'manager', name: 'أحمد' },
  }),
  AuthProvider: ({ children }: any) => children,
}));

describe('DashboardPage', () => {
  it('should render manager dashboard for manager role', () => {
    render(
      <QueryClientProvider client={queryClient}>
        <DashboardPage />
      </QueryClientProvider>
    );

    expect(screen.getByText('لوحة التحكم - المدير العام')).toBeInTheDocument();
  });
});
```

```bash
# Run tests
npm run test -- src/features/dashboard

# Expected: 5+ tests PASS ✅
```

#### Integration Testing

**Test: Dashboard loads data correctly**
```typescript
// src/test/integration/dashboard.test.tsx
import { describe, it, expect, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { QueryClientProvider } from '@tanstack/react-query';
import { queryClient } from '@/app/queryClient';
import { ManagerDashboard } from '@/features/dashboard/ManagerDashboard';
import axios from '@/app/axios';

vi.mock('@/app/axios');

describe('Dashboard Integration', () => {
  it('should load and display dashboard stats', async () => {
    vi.mocked(axios.get).mockResolvedValue({
      data: {
        total_products: 523,
        today_vouchers: 18,
        active_customers: 342,
        inventory_value: 2500000,
      },
    });

    render(
      <QueryClientProvider client={queryClient}>
        <ManagerDashboard />
      </QueryClientProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('523')).toBeInTheDocument();
      expect(screen.getByText('18')).toBeInTheDocument();
    });
  });
});
```

#### User Testing

**Scenarios per Role:**

**Manager:**
1. ✅ يشوف 4 KPI cards
2. ✅ Charts تظهر صح
3. ✅ Low stock alert يشتغل
4. ✅ كل الفروع visible

**Accountant:**
1. ✅ Pending vouchers تظهر
2. ✅ Quick approve/reject buttons تشتغل
3. ✅ Today's stats صحيحة
4. ✅ Cheque alerts تظهر

**Store Manager:**
1. ✅ Branch-specific stats
2. ✅ Low stock alerts لفرعه فقط
3. ✅ Quick action buttons تشتغل
4. ✅ Pending vouchers (منه) تظهر

#### Exit Criteria
- ✅ 3 Dashboards جاهزة
- ✅ Role-based rendering يشتغل
- ✅ Data fetching صحيح
- ✅ All tests passing
- ✅ UI responsive

#### Output
- ✅ 3 Dashboard variants
- ✅ Role-based logic
- ✅ Real-time data
- ✅ User-tested ✅

---

## 📊 Phase 1 Summary & Handoff

### ✅ Completed Tasks (Phase 1)

| Task ID | Name | Status | Duration | Tests |
|---------|------|--------|----------|-------|
| TASK-000 | Backend Verification | ✅ | 2h | 107/107 |
| TASK-101 | Project Setup | ✅ | 6h | 2/2 |
| TASK-102 | Design System | ✅ | 12h | 10+ |
| TASK-103 | Authentication | ✅ | 12h | 5+ |
| TASK-104 | Layout & Navigation | ✅ | 12h | 5+ |
| TASK-105 | Dashboards (3) | ✅ | 18h | 5+ |

**Total:** 62 hours (~8 days)  
**Total Tests:** 130+

---

### 📦 Deliverables (Phase 1)

```
✅ Frontend Project Setup
   - React 18 + TypeScript + Vite
   - Tailwind RTL configured
   - Axios + React Query
   - Folder structure

✅ Design System (15+ components)
   - Button, Input, Select, Dialog, Toast
   - DataTable, Card, Badge, Spinner
   - FormField, DatePicker, SearchInput
   - All tested + documented

✅ Authentication System
   - Login page
   - JWT integration
   - Auth context
   - Protected routes
   - Token management

✅ Layout System
   - AppLayout (Sidebar + Navbar)
   - Role-based navigation
   - User menu
   - Responsive

✅ Dashboards (3 versions)
   - Manager Dashboard
   - Accountant Dashboard
   - Store Manager Dashboard
   - KPI cards, charts, quick actions
```

---

### 🎯 Next Steps (Phase 2)

Phase 2 سيغطي:
- **TASK-201-210:** Products Management (5 pages)
- **TASK-301-310:** Issue Vouchers (5 pages)
- **TASK-401-410:** Return Vouchers (5 pages)
- **TASK-501-510:** Customers & Ledger (7 pages)
- **TASK-601-610:** Payments & Cheques (6 pages)

**المدة المتوقعة:** 4 أسابيع (160 ساعة)

---

### 📝 Handoff Checklist

قبل البدء في Phase 2، تأكد من:

- [ ] ✅ كل tests Phase 1 passing (130+ tests)
- [ ] ✅ Backend verification مكتمل (107 tests)
- [ ] ✅ Dev server يشتغل بدون errors
- [ ] ✅ Authentication flow يشتغل
- [ ] ✅ 3 Dashboards تظهر صح لكل role
- [ ] ✅ Layout responsive على كل الشاشات
- [ ] ✅ Code reviewed & approved
- [ ] ✅ Documentation updated
- [ ] ✅ Git committed & pushed

---

## 🚀 استعد لـ Phase 2!

**Phase 2 سيكون ضخم!** (~30 pages + 100+ tests)

هل أنت جاهز؟ 💪

---

**📄 End of Part 1**

**Next:** `PROJECT-TASKS-PART2.md` (Products, Vouchers, Customers, Payments)

**Status:** ✅ Foundation Complete - Ready for Core Features!
