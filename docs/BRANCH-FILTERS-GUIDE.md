# دليل الفلاتر مع نظام الفروع المتعددة

## نظرة عامة

تم تصميم نظام الفلاتر ليعمل بشكل صحيح مع صلاحيات الفروع المتعددة. هذا الدليل يوضح كيفية عمل الفلاتر للمستخدمين العاديين والـ Admins.

---

## الفرق بين Admin والمستخدم العادي

### 🔐 **المستخدم العادي (Regular User)**

**الصلاحيات:**
- يرى فقط بيانات الفرع المخصص له (`current_branch_id` أو `assigned_branch_id`)
- **لا يستطيع** تغيير `branch_id` في الـ Request
- الفلاتر تعمل فقط على البيانات الخاصة بفرعه

**مثال:**
```http
GET /api/v1/products?category_id=1&search=led&is_active=1
```

النظام سيقوم بـ:
1. ✅ تحديد الفرع تلقائياً من `user->current_branch_id`
2. ✅ فلترة المنتجات من هذا الفرع فقط
3. ✅ تطبيق بقية الفلاتر (`category_id`, `search`, `is_active`)

---

### 👑 **Super Admin**

**الصلاحيات:**
- يرى جميع البيانات من كل الفروع افتراضياً
- **يستطيع** اختيار فرع معين باستخدام `branch_id` في الـ Request
- الفلاتر تعمل على كل البيانات أو الفرع المحدد

**مثال 1 - عرض كل الفروع:**
```http
GET /api/v1/products?category_id=1&search=led
```

**مثال 2 - فلترة حسب فرع معين:**
```http
GET /api/v1/products?branch_id=2&category_id=1&search=led
```

---

## كيف تعمل الفلاتر في كل Controller

### 📦 **ProductController**

#### **للمستخدم العادي:**
```php
// الفرع يتحدد تلقائياً من المستخدم
$branchId = $user->current_branch_id ?? $user->assigned_branch_id;

// الفلاتر المتاحة:
- search: البحث بالاسم أو الكود
- category_id: فلترة حسب التصنيف
- is_active: فلترة المنتجات النشطة/غير النشطة
- low_stock: عرض المنتجات منخفضة المخزون
- sort_by: ترتيب النتائج
```

#### **لـ Super Admin:**
```php
// الفرع اختياري
$branchId = $request->input('branch_id'); // null = كل الفروع

// نفس الفلاتر + إمكانية اختيار الفرع
```

---

### 📄 **IssueVoucherController**

#### **للمستخدم العادي:**
```php
// الفرع يتحدد من getActiveBranch()
$activeBranch = $user->getActiveBranch();
$query->where('branch_id', $activeBranch->id);

// الفلاتر المتاحة:
- search: البحث برقم الإذن
- customer_id: فلترة حسب العميل
- status: فلترة حسب الحالة (completed, pending, cancelled)
- from_date, to_date: فلترة حسب التاريخ
```

#### **لـ Super Admin:**
```php
// يمكن اختيار الفرع
if ($request->filled('branch_id')) {
    $query->where('branch_id', $request->branch_id);
}
// وإلا يرى كل الفروع
```

---

### 📊 **DashboardController**

#### **للمستخدم العادي:**
```php
// الفرع يتحدد من getActiveBranch()
$activeBranch = $user->getActiveBranch();
$branchId = $activeBranch->id;

// الإحصائيات تظهر للفرع فقط
```

#### **لـ Super Admin:**
```php
// يمكن اختيار الفرع أو رؤية كل الفروع
$branchId = $request->get('branch_id'); // null = كل الفروع
```

---

### 🔄 **ReturnVoucherController**

نفس منطق IssueVoucherController

---

## أمثلة عملية

### مثال 1: مستخدم عادي يبحث عن منتج

**Request:**
```http
GET /api/v1/products?search=laptop&category_id=5&is_active=1
Authorization: Bearer {user_token}
```

**ما يحدث في الـ Backend:**
```php
// 1. تحديد الفرع تلقائياً
$branchId = $user->current_branch_id; // مثلاً: 3

// 2. بناء الـ Query
Product::with(['category', 'branchStocks.branch'])
    ->whereHas('branchStocks', function($q) use ($branchId) {
        $q->where('branch_id', 3); // ✅ الفرع المحدد
    })
    ->where('name', 'like', '%laptop%') // ✅ البحث
    ->where('category_id', 5) // ✅ التصنيف
    ->where('is_active', 1) // ✅ نشط فقط
    ->get();
```

**النتيجة:** ✅ منتجات Laptop من الفرع 3 فقط

---

### مثال 2: Admin يعرض منتجات فرع معين

**Request:**
```http
GET /api/v1/products?branch_id=2&category_id=5&low_stock=1
Authorization: Bearer {admin_token}
```

**ما يحدث في الـ Backend:**
```php
// Admin يختار الفرع
$branchId = $request->branch_id; // 2

Product::with(['category', 'branchStocks.branch'])
    ->whereHas('branchStocks', function($q) use ($branchId) {
        $q->where('branch_id', 2) // ✅ الفرع المحدد
          ->whereRaw('current_stock < products.reorder_level'); // منخفضة المخزون
    })
    ->where('category_id', 5)
    ->get();
```

**النتيجة:** ✅ منتجات منخفضة المخزون من الفرع 2

---

### مثال 3: Admin يعرض كل الفروع

**Request:**
```http
GET /api/v1/products?category_id=5&is_active=1
Authorization: Bearer {admin_token}
```

**ما يحدث في الـ Backend:**
```php
// لا يوجد branch_id في الـ Request
// Admin يرى كل الفروع

Product::with(['category', 'branchStocks.branch'])
    // ✅ بدون فلترة على الفرع
    ->where('category_id', 5)
    ->where('is_active', 1)
    ->get();
```

**النتيجة:** ✅ كل المنتجات النشطة من جميع الفروع

---

## الأمان (Security)

### ✅ **ما تم تطبيقه:**

1. **عدم السماح للمستخدم العادي بتغيير الفرع:**
```php
// ❌ قبل التعديل (غير آمن)
$branchId = $request->input('branch_id', $user->current_branch_id);

// ✅ بعد التعديل (آمن)
$branchId = $user->current_branch_id ?? $user->assigned_branch_id;
```

2. **التحقق من الصلاحيات في كل عملية:**
```php
if (!$user->hasRole('super-admin')) {
    // فرض الفرع للمستخدم العادي
}
```

3. **فلترة البيانات حسب الفرع:**
```php
// المستخدم العادي يرى فرعه فقط
$query->where('branch_id', $activeBranch->id);
```

---

## الفلاتر المتاحة في كل Endpoint

### Products API
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `search` | string | البحث بالاسم أو الكود | `?search=laptop` |
| `category_id` | integer | فلترة حسب التصنيف | `?category_id=5` |
| `is_active` | boolean | منتجات نشطة/غير نشطة | `?is_active=1` |
| `low_stock` | boolean | منخفضة المخزون فقط | `?low_stock=1` |
| `branch_id` | integer | فرع معين (Admin فقط) | `?branch_id=2` |
| `sort_by` | string | ترتيب النتائج | `?sort_by=name` |
| `sort_order` | string | اتجاه الترتيب | `?sort_order=asc` |

### Issue Vouchers API
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `search` | string | البحث برقم الإذن | `?search=ISS-001` |
| `customer_id` | integer | فلترة حسب العميل | `?customer_id=10` |
| `status` | string | حالة الإذن | `?status=completed` |
| `from_date` | date | من تاريخ | `?from_date=2025-01-01` |
| `to_date` | date | إلى تاريخ | `?to_date=2025-12-31` |
| `branch_id` | integer | فرع معين (Admin فقط) | `?branch_id=2` |

### Dashboard API
| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `branch_id` | integer | فرع معين (Admin فقط) | `?branch_id=2` |
| `period` | string | فترة زمنية | `?period=month` |

---

## اختبار الفلاتر

### Test Case 1: المستخدم العادي لا يمكنه تجاوز فرعه
```php
public function test_regular_user_cannot_override_branch()
{
    $user = User::factory()->create(['current_branch_id' => 1]);
    
    // المستخدم يحاول الوصول لفرع آخر
    $response = $this->actingAs($user)
        ->getJson('/api/v1/products?branch_id=2');
    
    // يجب أن يرى فرعه فقط (1) وليس (2)
    $products = $response->json('data');
    foreach ($products as $product) {
        $this->assertContains(1, $product['branch_ids']);
    }
}
```

### Test Case 2: Admin يمكنه اختيار أي فرع
```php
public function test_admin_can_filter_by_branch()
{
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    
    $response = $this->actingAs($admin)
        ->getJson('/api/v1/products?branch_id=2&category_id=5');
    
    $response->assertOk();
    // يرى منتجات الفرع 2 فقط
}
```

---

## الخلاصة

✅ **المستخدم العادي:**
- الفرع يتحدد تلقائياً من حسابه
- الفلاتر تعمل على فرعه فقط
- لا يمكنه تغيير `branch_id` في الـ Request

✅ **Super Admin:**
- يرى كل الفروع افتراضياً
- يمكنه اختيار فرع معين باستخدام `branch_id`
- الفلاتر تعمل على كل البيانات أو الفرع المحدد

✅ **الأمان:**
- تم منع المستخدم العادي من تجاوز فرعه
- كل العمليات محمية بالصلاحيات
- الفلاتر آمنة ومحمية من SQL Injection

---

## المراجع

- [نظام الصلاحيات المتعددة](./MULTI-BRANCH-AUTHORIZATION.md)
- [دليل الـ API](./API-DOCUMENTATION.md)
- [الاختبارات](../tests/Feature/BranchPermissionTest.php)
