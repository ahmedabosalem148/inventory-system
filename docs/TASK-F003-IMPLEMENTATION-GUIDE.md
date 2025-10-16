# 🎯 دليل تنفيذ TASK-F003: دفتر العملاء Frontend

## 📝 نظرة عامة
المهمة: بناء نظام دفتر عملاء متكامل مع كشف حساب وأرصدة متحركة

**الوقت المقدر:** 2-2.5 أسبوع  
**الأولوية:** 🔴 حرجة جداً  
**Backend:** ✅ جاهز 100%

---

## 📂 الملفات المطلوبة

### 1. CustomersPage.tsx (تحديث الملف الموجود)
**المسار:** `src/features/customers/CustomersPage.tsx`

**التغييرات المطلوبة:**
- ✅ استبدال API call من `getCustomers` إلى `/api/v1/customers-balances`
- ✅ إضافة Stats Cards (4 بطاقات)
- ✅ تحديث واجهة Customer لتتوافق مع Backend
- ✅ إضافة فلاتر: only_with_balance, sort_by
- ✅ تحسين عرض الرصيد (ملون حسب الحالة)
- ✅ زر "كشف حساب" لكل عميل

**واجهة Customer الجديدة:**
```typescript
interface Customer {
  id: number
  code: string // CUS-XXXXX
  name: string
  phone?: string
  address?: string
  balance: number // موجب = مدين, سالب = دائن
  status: 'debtor' | 'creditor' | 'zero'
  last_activity_at?: string
  purchases_count: number
  purchases_total: number
  returns_count: number
  returns_total: number
  payments_total: number
}
```

**API Response:**
```typescript
{
  customers: Customer[],
  statistics: {
    total_customers: number
    debtors_count: number
    creditors_count: number
    zero_balance_count: number
  }
}
```

---

### 2. CustomerDetailsPage.tsx (جديد)
**المسار:** `src/features/customers/CustomerDetailsPage.tsx`

**الأقسام الرئيسية:**

#### القسم 1: معلومات العميل
```tsx
<Card>
  <div className="grid grid-cols-2 gap-4">
    <div>
      <label>الاسم:</label>
      <p className="font-bold">{customer.name}</p>
    </div>
    <div>
      <label>الكود:</label>
      <p>{customer.code}</p>
    </div>
    <div>
      <label>الهاتف:</label>
      <p>{customer.phone}</p>
    </div>
    <div>
      <label>الرصيد الحالي:</label>
      <p className="text-3xl font-bold text-red-600">
        {Math.abs(balance).toFixed(2)} ر.س
      </p>
      <Badge>{status}</Badge> {/* مدين/دائن/متوازن */}
    </div>
  </div>
</Card>
```

#### القسم 2: إحصائيات سريعة (4 Cards)
```tsx
<div className="grid grid-cols-4 gap-4">
  <Card>
    <p>إجمالي المشتريات</p>
    <p className="text-2xl">{customer.purchases_count}</p>
    <p className="text-gray-500">{customer.purchases_total} ر.س</p>
  </Card>
  {/* ... باقي الـ Cards */}
</div>
```

**API:** `GET /api/v1/customers/{id}/activity`

#### القسم 3: كشف الحساب (الجدول الرئيسي)
```tsx
<Card>
  {/* Filters */}
  <div className="flex gap-4 mb-4">
    <DatePicker 
      label="من تاريخ" 
      value={fromDate}
      onChange={setFromDate}
      required
    />
    <DatePicker 
      label="إلى تاريخ" 
      value={toDate}
      onChange={setToDate}
      required
    />
    <Button onClick={loadStatement}>عرض</Button>
  </div>

  {/* Summary */}
  <div className="grid grid-cols-4 gap-4 mb-4 p-4 bg-gray-50">
    <div>
      <label>رصيد أول المدة:</label>
      <p className="font-bold">{openingBalance} ر.س</p>
    </div>
    <div>
      <label>إجمالي علية (Debit):</label>
      <p className="font-bold text-red-600">{totalDebit} ر.س</p>
    </div>
    <div>
      <label>إجمالي له (Credit):</label>
      <p className="font-bold text-green-600">{totalCredit} ر.س</p>
    </div>
    <div>
      <label>رصيد آخر المدة:</label>
      <p className="font-bold text-2xl">{closingBalance} ر.س</p>
    </div>
  </div>

  {/* Ledger Table */}
  <DataTable
    columns={[
      { key: 'date', header: 'التاريخ' },
      { key: 'description', header: 'البيان' },
      { 
        key: 'debit', 
        header: 'علية',
        render: (entry) => (
          <span className="text-red-600 font-bold">
            {entry.debit_aliah > 0 ? entry.debit_aliah.toFixed(2) : '-'}
          </span>
        )
      },
      { 
        key: 'credit', 
        header: 'له',
        render: (entry) => (
          <span className="text-green-600 font-bold">
            {entry.credit_lah > 0 ? entry.credit_lah.toFixed(2) : '-'}
          </span>
        )
      },
      { 
        key: 'balance', 
        header: 'الرصيد',
        render: (entry) => (
          <span className="font-bold text-lg">
            {entry.running_balance.toFixed(2)} ر.س
          </span>
        )
      },
    ]}
    data={entries}
  />

  {/* Actions */}
  <div className="flex gap-2 mt-4">
    <Button onClick={printPDF}>
      <Printer className="h-4 w-4 ml-2" />
      طباعة كشف حساب PDF
    </Button>
    <Button variant="outline" onClick={exportExcel}>
      <FileDown className="h-4 w-4 ml-2" />
      تصدير Excel
    </Button>
  </div>
</Card>
```

**API:** `GET /api/v1/customers/{id}/statement?from_date=X&to_date=Y`

**Response:**
```typescript
{
  customer: Customer
  opening_balance: number
  entries: Array<{
    id: number
    date: string
    description: string
    debit_aliah: number
    credit_lah: number
    running_balance: number
    reference_type: string // IssueVoucher, ReturnVoucher, Payment
    reference_id: number
  }>
  total_debit: number
  total_credit: number
  closing_balance: number
}
```

---

### 3. CustomerDialog.tsx (تحديث/إنشاء جديد)
**المسار:** `src/features/customers/CustomerDialog.tsx`

**الحقول:**
```tsx
<form onSubmit={handleSubmit}>
  <Input
    label="الاسم *"
    value={formData.name}
    onChange={(e) => setFormData({...formData, name: e.target.value})}
    required
  />
  
  <Input
    label="الهاتف"
    value={formData.phone}
    onChange={(e) => setFormData({...formData, phone: e.target.value})}
  />
  
  <Textarea
    label="العنوان"
    value={formData.address}
    onChange={(e) => setFormData({...formData, address: e.target.value})}
  />
  
  <Input
    label="حد الائتمان"
    type="number"
    value={formData.credit_limit}
    onChange={(e) => setFormData({...formData, credit_limit: parseFloat(e.target.value)})}
  />
  
  <Textarea
    label="ملاحظات"
    value={formData.notes}
    onChange={(e) => setFormData({...formData, notes: e.target.value})}
  />
  
  <div className="flex gap-2">
    <Button type="submit" loading={loading}>
      {customer ? 'تحديث' : 'إضافة'}
    </Button>
    <Button type="button" variant="outline" onClick={onClose}>
      إلغاء
    </Button>
  </div>
</form>
```

**API:**
- Create: `POST /api/v1/customers`
- Update: `PUT /api/v1/customers/{id}`

---

## 🛠️ الخطوات التنفيذية

### المرحلة 1: تحديث CustomersPage (يوم 1-2)
1. ✅ تحديث واجهة Customer
2. ✅ استبدال API call
3. ✅ إضافة Stats Cards
4. ✅ إضافة فلاتر
5. ✅ تحسين UI للأرصدة

### المرحلة 2: إنشاء CustomerDetailsPage (يوم 3-5)
1. ✅ القسم الأول: معلومات العميل
2. ✅ القسم الثاني: إحصائيات سريعة
3. ✅ القسم الثالث: كشف الحساب
4. ✅ Filters (من/إلى تاريخ)
5. ✅ Summary (أول المدة/علية/له/آخر المدة)
6. ✅ Ledger Table مع Running Balance
7. ✅ Pagination
8. ✅ طباعة PDF
9. ✅ تصدير Excel

### المرحلة 3: CustomerDialog (يوم 6-7)
1. ✅ نموذج إضافة
2. ✅ نموذج تعديل
3. ✅ Validation
4. ✅ API Integration

### المرحلة 4: Routing & Integration (يوم 8-9)
1. ✅ إضافة Routes:
   - `/customers` → CustomersPage
   - `/customers/:id` → CustomerDetailsPage
   - `/customers/new` → CustomerDialog
2. ✅ Navigation من قائمة العملاء لكشف الحساب
3. ✅ Back button من كشف الحساب

### المرحلة 5: Backend - PDF Generation (يوم 10)
**ملف جديد Backend:** `resources/views/pdf/customer-statement.blade.php`

```blade
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <title>كشف حساب - {{ $customer->name }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url({{ storage_path('fonts/DejaVuSans.ttf') }}) format('truetype');
        }
        body { font-family: 'DejaVu Sans', sans-serif; direction: rtl; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 30px; }
        .summary { background-color: #f9f9f9; padding: 15px; margin: 20px 0; }
        .debit { color: #dc2626; font-weight: bold; }
        .credit { color: #16a34a; font-weight: bold; }
        .balance { font-weight: bold; font-size: 1.2em; }
    </style>
</head>
<body>
    <div class="header">
        <h1>كشف حساب عميل</h1>
        <h2>{{ $customer->name }} ({{ $customer->code }})</h2>
        <p>من {{ $from_date }} إلى {{ $to_date }}</p>
    </div>

    <div class="summary">
        <table>
            <tr>
                <th>رصيد أول المدة</th>
                <td>{{ number_format($opening_balance, 2) }} ر.س</td>
                <th>إجمالي علية</th>
                <td class="debit">{{ number_format($total_debit, 2) }} ر.س</td>
            </tr>
            <tr>
                <th>إجمالي له</th>
                <td class="credit">{{ number_format($total_credit, 2) }} ر.س</td>
                <th>رصيد آخر المدة</th>
                <td class="balance">{{ number_format($closing_balance, 2) }} ر.س</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>البيان</th>
                <th>علية</th>
                <th>له</th>
                <th>الرصيد</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
            <tr>
                <td>{{ $entry->date }}</td>
                <td>{{ $entry->description }}</td>
                <td class="debit">
                    {{ $entry->debit_aliah > 0 ? number_format($entry->debit_aliah, 2) : '-' }}
                </td>
                <td class="credit">
                    {{ $entry->credit_lah > 0 ? number_format($entry->credit_lah, 2) : '-' }}
                </td>
                <td class="balance">{{ number_format($entry->running_balance, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
```

**API Endpoint:** `GET /api/v1/customers/{id}/statement/pdf?from_date=X&to_date=Y`

---

## ✅ معايير الاختبار

### اختبار CustomersPage:
- [ ] عرض قائمة العملاء بنجاح
- [ ] Stats Cards تعرض الأرقام الصحيحة
- [ ] الفلترة تعمل (only_with_balance, sort_by)
- [ ] البحث يعمل
- [ ] الرصيد يظهر بالألوان الصحيحة
- [ ] زر "كشف حساب" يفتح الصفحة الصحيحة

### اختبار CustomerDetailsPage:
- [ ] معلومات العميل تظهر بشكل صحيح
- [ ] الإحصائيات السريعة صحيحة
- [ ] فلاتر التاريخ تعمل (validation: من ≤ إلى)
- [ ] كشف الحساب يعرض البيانات الصحيحة
- [ ] Running Balance محسوب بشكل صحيح
- [ ] الألوان صحيحة (علية أحمر، له أخضر)
- [ ] Pagination يعمل
- [ ] طباعة PDF تعمل
- [ ] تصدير Excel يعمل

### اختبار CustomerDialog:
- [ ] إضافة عميل جديد
- [ ] تعديل عميل موجود
- [ ] Validation يعمل (الاسم مطلوب)
- [ ] رسائل الخطأ واضحة
- [ ] رسائل النجاح تظهر

---

## 📝 ملاحظات هامة

### 1. التعامل مع الرصيد:
```typescript
// الرصيد الموجب = مدين (علينا للعميل)
// الرصيد السالب = دائن (للعميل علينا)
// الرصيد صفر = متوازن

const getBalanceStatus = (balance: number) => {
  if (balance > 0) return { status: 'debtor', color: 'red', label: 'مدين' }
  if (balance < 0) return { status: 'creditor', color: 'green', label: 'دائن' }
  return { status: 'zero', color: 'gray', label: 'متوازن' }
}
```

### 2. Running Balance:
```typescript
// يتم حسابه في Backend
// كل سطر في كشف الحساب له running_balance
// running_balance = opening_balance + Σ(debit) - Σ(credit) حتى هذا السطر
```

### 3. Pagination في كشف الحساب:
```typescript
// إذا كانت المعاملات كثيرة (> 100)
// استخدم Pagination:
const [page, setPage] = useState(1)
const [perPage] = useState(50)

// API: /customers/{id}/statement?page=1&per_page=50&from_date=X&to_date=Y
```

---

## 🚀 الخطوة التالية

**ابدأ بالترتيب:**
1. ✅ تحديث CustomersPage
2. ✅ إنشاء CustomerDetailsPage
3. ✅ تحديث CustomerDialog
4. ✅ Routing
5. ✅ Backend PDF

**بعد الانتهاء من TASK-F003:**
- ✅ Mark as completed
- ⏭️ الانتقال لـ TASK-F004 (إدارة المدفوعات والشيكات)

---

**تاريخ الإنشاء:** 16 أكتوبر 2025  
**الأولوية:** 🔴 حرجة جداً  
**الحالة:** 📝 جاهز للتنفيذ
