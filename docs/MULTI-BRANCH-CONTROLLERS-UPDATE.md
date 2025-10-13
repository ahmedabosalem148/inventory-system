# تحديث Controllers مع نظام الصلاحيات متعدد الفروع

## ✅ التحديثات المكتملة

تم تحديث **4 Controllers رئيسية** لتدعم نظام صلاحيات الفروع المتعددة مع الحفاظ على صلاحيات الـ Admin الكاملة.

---

## 1. ProductController

### التغييرات المطبقة:

#### `index()` - عرض المنتجات
```php
// Admin: يرى جميع المنتجات أو يمكنه الفلترة بـ branch_id
// Regular Users: يرى فقط منتجات الفرع النشط (current_branch_id أو assigned_branch_id)

if (!$user->hasRole('super-admin')) {
    $activeBranch = $user->getActiveBranch();
    $query->whereHas('productStocks', function ($q) use ($activeBranch) {
        $q->where('branch_id', $activeBranch->id);
    });
} elseif ($request->filled('branch_id')) {
    $query->whereHas('productStocks', function ($q) use ($request) {
        $q->where('branch_id', $request->branch_id);
    });
}
```

#### `store()` - إنشاء منتج جديد
```php
// Admin: يتخطى كل الفحوصات
// Regular Users: يجب أن يكون لديهم full_access على الفرع النشط
// يتم فحص الصلاحيات لكل فرع في initial_stock

if (!$user->hasRole('super-admin')) {
    $activeBranch = $user->getActiveBranch();
    if (!$user->hasFullAccessToBranch($activeBranch->id)) {
        return 403; // ليس لديك صلاحية كاملة
    }
    
    // فحص كل فرع في initial_stock
    foreach ($validated['initial_stock'] as $stock) {
        if (!$user->hasFullAccessToBranch($stock['branch_id'])) {
            return 403; // ليس لديك صلاحية كاملة لهذا الفرع
        }
    }
}
```

#### `update()` - تحديث منتج
```php
// Admin: يتخطى الفحوصات
// Regular Users: يجب full_access على الفرع النشط

if (!$user->hasRole('super-admin')) {
    $activeBranch = $user->getActiveBranch();
    if (!$user->hasFullAccessToBranch($activeBranch->id)) {
        return 403;
    }
}
```

#### `destroy()` - حذف منتج
```php
// فقط super-admin يمكنه حذف المنتجات (للحفاظ على سلامة البيانات)

if (!$user->hasRole('super-admin')) {
    return 403; // فقط المدير يمكنه حذف المنتجات
}
```

---

## 2. DashboardController

### التغييرات المطبقة:

#### `index()` - إحصائيات الداشبورد الرئيسية
```php
// Admin: يرى إحصائيات جميع الفروع أو يمكنه الفلترة بـ branch_id
// Regular Users: يرى فقط إحصائيات الفرع النشط

if (!$user->hasRole('super-admin')) {
    $activeBranch = $user->getActiveBranch();
    $branchId = $activeBranch->id;
} else {
    $branchId = $request->get('branch_id'); // optional
}

// يتم تمرير $branchId لجميع الـ helper methods
```

#### `stats()` - إحصائيات تفصيلية
```php
// نفس المنطق - Admin يرى الكل، المستخدمين العاديين يرون فرعهم فقط
// يتم فلترة:
- sales_summary (مبيعات الفترة)
- top_products (أكثر المنتجات مبيعاً)
- top_customers (أفضل العملاء)
- branch_performance (أداء الفروع)
```

#### `lowStock()` - المنتجات منخفضة المخزون
```php
// Admin: يرى جميع الفروع أو يفلتر
// Regular Users: يرى فقط فرعه

$query = ProductBranchStock::with(['product', 'branch']);
if ($branchId) {
    $query->where('branch_id', $branchId);
}
```

#### Helper Methods المحدّثة:
- `calculateTotalStockValue(?int $branchId = null)`
- `getLowStockCount(?int $branchId = null)`
- `getOutOfStockCount(?int $branchId = null)`
- `getTodaySales(?int $branchId = null)`
- `getTodayVouchersCount(?int $branchId = null)`
- `getTopProducts($dateFrom, int $limit, ?int $branchId = null)`
- `getTopCustomers($dateFrom, int $limit, ?int $branchId = null)`
- `getBranchPerformance($user, ?int $branchId = null)`

---

## 3. IssueVoucherController (أذونات الصرف)

### التغييرات المطبقة:

#### `index()` - عرض أذونات الصرف
```php
// Admin: يرى جميع الأذونات أو يفلتر بالفرع
// Regular Users: يرى فقط أذونات فرعه النشط

if (!$user->hasRole('super-admin')) {
    $activeBranch = $user->getActiveBranch();
    $query->where('branch_id', $activeBranch->id);
} elseif ($request->filled('branch_id')) {
    $query->where('branch_id', $request->branch_id);
}
```

#### `store()` - إنشاء إذن صرف جديد
```php
// Admin: يتخطى الفحوصات
// Regular Users: يجب full_access على الفرع المحدد في branch_id

if (!$user->hasRole('super-admin')) {
    if (!$user->hasFullAccessToBranch($validated['branch_id'])) {
        return 403; // ليس لديك صلاحية كاملة لإنشاء أذونات صرف
    }
}
```

#### `show()` - عرض تفاصيل إذن صرف
```php
// Admin: يرى كل الأذونات
// Regular Users: يجب أن يكون لديهم أي صلاحية (view_only أو full_access) على الفرع

if (!$user->hasRole('super-admin')) {
    if (!$user->canAccessBranch($issueVoucher->branch_id)) {
        return 403; // ليس لديك صلاحية لعرض هذا الإذن
    }
}
```

#### `destroy()` - إلغاء إذن صرف
```php
// Admin: يمكنه إلغاء أي إذن
// Regular Users: يجب full_access على الفرع

if (!$user->hasRole('super-admin')) {
    if (!$user->hasFullAccessToBranch($issueVoucher->branch_id)) {
        return 403; // ليس لديك صلاحية كاملة لإلغاء الأذونات
    }
}
```

---

## 4. ReturnVoucherController (أذونات المرتجع)

### التغييرات المطبقة:

**نفس المنطق تماماً مثل IssueVoucherController:**

- `index()`: Admin يرى الكل، المستخدمين العاديين يرون فرعهم فقط
- `store()`: يتطلب full_access (أو admin)
- `show()`: يتطلب أي صلاحية على الفرع (أو admin)
- `destroy()`: يتطلب full_access (أو admin)

---

## Pattern المستخدم

### 1. **القراءة (Read Operations)**
```php
// Admin: يرى كل شيء أو يفلتر اختيارياً
// Regular Users: يرى فقط الفرع النشط
```

### 2. **الإنشاء/التعديل (Create/Update Operations)**
```php
// Admin: صلاحية كاملة بدون فحوصات
// Regular Users: يجب full_access permission
```

### 3. **الحذف (Delete Operations)**
```php
// غالباً: فقط super-admin (للحفاظ على سلامة البيانات)
// أو: full_access مع فحوصات إضافية
```

---

## الصلاحيات المطبّقة

### Admin (super-admin role)
✅ رؤية جميع الفروع  
✅ عمليات CRUD كاملة على جميع الفروع  
✅ لا توجد فحوصات صلاحيات  
✅ يمكنه الفلترة الاختيارية بالفرع  

### Regular Users

#### مع view_only permission:
✅ رؤية بيانات الفرع المخصص  
✅ عرض المنتجات والأذونات  
❌ إنشاء/تعديل/حذف أي شيء  

#### مع full_access permission:
✅ رؤية بيانات الفرع المخصص  
✅ عرض المنتجات والأذونات  
✅ إنشاء منتجات وأذونات  
✅ تعديل البيانات  
✅ إلغاء الأذونات  
❌ حذف المنتجات (admin only)  

---

## أمثلة API Requests

### 1. عرض المنتجات
```http
GET /api/v1/products
Authorization: Bearer {token}

# Admin يرى جميع المنتجات
# User يرى فقط منتجات فرعه

# Admin يمكنه الفلترة:
GET /api/v1/products?branch_id=2
```

### 2. إنشاء إذن صرف
```http
POST /api/v1/issue-vouchers
Authorization: Bearer {token}
Content-Type: application/json

{
  "customer_id": 1,
  "branch_id": 2,
  "issue_date": "2025-01-15",
  "items": [
    {
      "product_id": 5,
      "quantity": 10,
      "unit_price": 50
    }
  ]
}

# Admin: ينجح دائماً
# User: ينجح فقط إذا كان لديه full_access على branch_id=2
```

### 3. عرض الداشبورد
```http
GET /api/v1/dashboard
Authorization: Bearer {token}

# Admin: إحصائيات جميع الفروع
# User: إحصائيات فرعه فقط

# Admin يمكنه الفلترة:
GET /api/v1/dashboard?branch_id=3
```

---

## Error Responses

### 403 - ليس لديك صلاحية
```json
{
  "message": "لم يتم تعيين فرع للمستخدم"
}
```

```json
{
  "message": "ليس لديك صلاحية كاملة لإنشاء أذونات صرف في هذا الفرع"
}
```

```json
{
  "message": "ليس لديك صلاحية لعرض هذا الإذن"
}
```

```json
{
  "message": "فقط المدير يمكنه حذف المنتجات"
}
```

---

## Next Steps

### ✅ مكتمل:
1. Database schema (migrations)
2. Models & relationships
3. Middleware (EnsureBranchAccess)
4. 4 Controllers updated with permissions
5. Documentation

### ⏳ قادم:
1. **Feature Tests**: كتابة اختبارات شاملة للصلاحيات
2. **React Frontend**: واجهة المستخدم مع إدارة الفروع والصلاحيات
3. **User Management UI**: إضافة/تعديل صلاحيات المستخدمين على الفروع

---

## ملاحظات مهمة

1. **Admin Bypass**: الـ super-admin دائماً يتخطى كل فحوصات الصلاحيات ✅
2. **Branch Context**: المستخدمون العاديون يعملون فقط في سياق فرعهم النشط ✅
3. **Permission Levels**: view_only للقراءة فقط، full_access للعمليات الكاملة ✅
4. **Data Integrity**: عمليات الحذف محصورة بالـ admin للحفاظ على سلامة البيانات ✅
5. **Arabic Messages**: جميع رسائل الخطأ بالعربية لسهولة الفهم ✅

---

**تاريخ التحديث**: 2025-01-15  
**الحالة**: ✅ مكتمل
