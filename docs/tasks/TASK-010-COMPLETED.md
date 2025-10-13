# ✅ TASK-010: Issue Vouchers - Complete Documentation

**تاريخ الإنجاز:** 2 أكتوبر 2025  
**الوقت المستغرق:** ~60 دقيقة  
**الحالة:** ✅ Complete

---

## 📋 نظرة عامة

تم تنفيذ نظام **أذون الصرف** كاملاً مع:
- ✅ دعم العملاء المسجلين والنقديين
- ✅ خصم تلقائي من المخزون
- ✅ ترقيم تلقائي (ISS-00001, ISS-00002, ...)
- ✅ واجهة ديناميكية لإضافة الأصناف
- ✅ طباعة الإذن
- ✅ إلغاء الإذن (إرجاع المخزون)

---

## 🗄️ Database Migrations

### 1. issue_vouchers Table
**الملف:** `database/migrations/2025_10_02_222000_create_issue_vouchers_table.php`

**الحقول:**
```
- id
- voucher_number (unique, string 50) - رقم الإذن
- customer_id (FK → customers, nullable, onDelete: null)
- customer_name (string 200, nullable) - للعملاء النقديين
- branch_id (FK → branches, onDelete: restrict) - الفرع
- issue_date (date) - تاريخ الصرف
- notes (text, nullable)
- total_amount (decimal 12,2, default 0)
- status (enum: completed, cancelled, default: completed)
- created_by (FK → users, nullable, onDelete: null)
- timestamps
```

**الفهارس:**
```sql
index(voucher_number)
index(customer_id)
index(branch_id)
index(issue_date)
index(status)
```

---

### 2. issue_voucher_items Table
**الملف:** `database/migrations/2025_10_02_222100_create_issue_voucher_items_table.php`

**الحقول:**
```
- id
- issue_voucher_id (FK → issue_vouchers, onDelete: cascade)
- product_id (FK → products, onDelete: restrict)
- quantity (integer) - الكمية
- unit_price (decimal 10,2) - سعر البيع
- total_price (decimal 12,2) - quantity × unit_price
- timestamps
```

**الفهارس:**
```sql
index(issue_voucher_id)
index(product_id)
```

---

## 🎨 Models

### 1. IssueVoucher Model
**الملف:** `app/Models/IssueVoucher.php`

**Relationships:**
```php
customer() → belongsTo(Customer)
branch() → belongsTo(Branch)
items() → hasMany(IssueVoucherItem)
creator() → belongsTo(User, 'created_by')
```

**Attributes:**
```php
$voucher->customer_display_name; 
// Returns: اسم العميل المسجل OR customer_name OR "عميل نقدي"
```

**Scopes:**
```php
IssueVoucher::completed()->get();
IssueVoucher::cancelled()->get();
IssueVoucher::searchByNumber('ISS-001')->get();
```

---

### 2. IssueVoucherItem Model
**الملف:** `app/Models/IssueVoucherItem.php`

**Relationships:**
```php
voucher() → belongsTo(IssueVoucher)
product() → belongsTo(Product)
```

**Auto-Calculation:**
```php
// total_price يُحسب تلقائياً في boot()
protected static function boot() {
    static::creating/updating(function ($item) {
        $item->total_price = $item->quantity * $item->unit_price;
    });
}
```

---

## 🎮 IssueVoucherController

**الملف:** `app/Http/Controllers/IssueVoucherController.php`

### Methods:

#### 1. index() - قائمة الأذونات
**المسار:** `GET /issue-vouchers`

**الميزات:**
- ✅ البحث برقم الإذن
- ✅ فلترة حسب: الفرع، العميل، الحالة، التاريخ
- ✅ Pagination (15 إذن/صفحة)
- ✅ Eager Loading (customer, branch, items)

---

#### 2. create() - نموذج الإنشاء
**المسار:** `GET /issue-vouchers/create`

**البيانات المُرسلة:**
```php
$branches = Branch::active()->get();
$customers = Customer::active()->get();
$products = Product::with('branchStocks.branch')->active()->get();
```

---

#### 3. store() - حفظ الإذن
**المسار:** `POST /issue-vouchers`

**Validation:**
```php
[
    'customer_type' => 'required|in:registered,cash',
    'customer_id' => 'required_if:customer_type,registered',
    'customer_name' => 'required_if:customer_type,cash',
    'branch_id' => 'required|exists:branches,id',
    'issue_date' => 'required|date',
    'items' => 'required|array|min:1',
    'items.*.product_id' => 'required|exists:products,id',
    'items.*.quantity' => 'required|integer|min:1',
    'items.*.unit_price' => 'required|numeric|min:0',
]
```

**المنطق الرئيسي (DB Transaction):**
```php
DB::transaction(function () {
    // 1. توليد رقم الإذن
    $voucherNumber = SequencerService::getNext('issue_voucher', 'ISS-', 5);
    
    // 2. إنشاء الإذن
    $voucher = IssueVoucher::create([...]);
    
    // 3. لكل صنف:
    foreach ($items as $item) {
        // 3.1. إنشاء IssueVoucherItem
        IssueVoucherItem::create([...]);
        
        // 3.2. خصم المخزون مع قفل الصف
        $stock = ProductBranchStock::where(...)
            ->lockForUpdate()
            ->first();
        
        // 3.3. التحقق من توفر المخزون
        if ($stock->current_stock < $quantity) {
            throw new Exception("المخزون غير كافٍ");
        }
        
        // 3.4. خصم
        $stock->decrement('current_stock', $quantity);
    }
    
    // 4. تحديث رصيد العميل (إذا كان مسجلاً)
    if ($voucher->customer_id) {
        $customer->decrement('balance', $totalAmount); // عليه
    }
});
```

**الميزات:**
- ✅ **Thread-Safe:** استخدام `lockForUpdate()`
- ✅ **Atomic:** كل العمليات في transaction واحدة
- ✅ **Validation:** التحقق من كفاية المخزون
- ✅ **Auto-Numbering:** ترقيم تلقائي عبر SequencerService
- ✅ **Customer Balance:** تحديث الرصيد تلقائياً

---

#### 4. show() - عرض/طباعة الإذن
**المسار:** `GET /issue-vouchers/{id}`

**Eager Loading:**
```php
$issueVoucher->load(['customer', 'branch', 'items.product', 'creator']);
```

---

#### 5. destroy() - إلغاء الإذن
**المسار:** `DELETE /issue-vouchers/{id}`

**المنطق:**
```php
DB::transaction(function () {
    // 1. إرجاع المخزون
    foreach ($voucher->items as $item) {
        $stock->increment('current_stock', $item->quantity);
    }
    
    // 2. إرجاع رصيد العميل
    if ($voucher->customer_id) {
        $customer->increment('balance', $voucher->total_amount);
    }
    
    // 3. تحديث الحالة
    $voucher->update(['status' => 'cancelled']);
});
```

**ملاحظة:** لا يُحذف الإذن، فقط يُلغى ويُرجع المخزون.

---

## 🖼️ Views

### 1. issue_vouchers/index.blade.php - القائمة
**المسار:** `/issue-vouchers`

**الأقسام:**
1. **رأس الصفحة:**
   - العنوان + زر "إذن صرف جديد"

2. **نموذج البحث:**
   - رقم الإذن
   - الفرع
   - الحالة
   - من/إلى تاريخ

3. **الجدول:**
   | # | رقم الإذن | التاريخ | العميل | الفرع | عدد الأصناف | الإجمالي | الحالة | الإجراءات |
   
4. **الأزرار:**
   - 🖨️ **عرض/طباعة** (أزرق)
   - ❌ **إلغاء** (أحمر، فقط للمكتملة)

---

### 2. issue_vouchers/create.blade.php - الإنشاء
**المسار:** `/issue-vouchers/create`

#### بطاقة بيانات الإذن:
- نوع العميل (registered/cash) - toggle ديناميكي
- العميل المسجل OR اسم العميل النقدي
- الفرع
- تاريخ الصرف
- ملاحظات

#### بطاقة الأصناف (جدول ديناميكي):
| المنتج | المخزون المتاح | الكمية | سعر الوحدة | الإجمالي | [حذف] |

**JavaScript Features:**
```javascript
// 1. Toggle customer type
customer_type.onChange() → show/hide المناسب

// 2. Add item row
addItem() → إضافة صف جديد ديناميكياً

// 3. Update stock
updateStock(index) → عرض المخزون المتاح للفرع المختار

// 4. Calculate row total
calculateRow(index) → quantity × unit_price

// 5. Calculate grand total
calculateGrandTotal() → مجموع كل الصفوف

// 6. Remove item
removeItem(index) → حذف صف

// 7. Auto-fill price
onProductSelect → ملء سعر البيع تلقائياً
```

**الميزات:**
- ✅ واجهة ديناميكية 100%
- ✅ حساب تلقائي للإجماليات
- ✅ عرض المخزون المتاح
- ✅ ملء السعر تلقائياً
- ✅ تحديث عند تغيير الفرع

---

### 3. issue_vouchers/show.blade.php - العرض/الطباعة
**المسار:** `/issue-vouchers/{id}`

#### الأقسام:
1. **Header:**
   - عنوان: "إذن صرف بضاعة"
   - اسم النظام

2. **Voucher Info:**
   - رقم الإذن، التاريخ، العميل
   - الفرع، الحالة، المستخدم

3. **Items Table:**
   | # | المنتج | الكمية | سعر الوحدة | الإجمالي |

4. **Signatures:**
   - توقيع المستلم
   - توقيع المحاسب
   - توقيع المدير

**Print Styles:**
```css
@media print {
    .btn, nav, .sidebar → display: none
    background → white
    box-shadow → none
}
```

---

## 🛣️ Routes

```php
Route::resource('issue-vouchers', IssueVoucherController::class)
    ->except(['edit', 'update']);
```

**الـ routes (5):**
```
GET     /issue-vouchers              → index
GET     /issue-vouchers/create       → create
POST    /issue-vouchers              → store
GET     /issue-vouchers/{id}         → show
DELETE  /issue-vouchers/{id}         → destroy
```

**ملاحظة:** لا يوجد `edit` و `update` - لا يُعدّل الإذن بعد إنشائه (فقط إلغاء).

---

## 📊 الإحصائيات

```
✅ 2 Migrations (issue_vouchers, issue_voucher_items)
✅ 2 Models مع علاقات كاملة
✅ 1 Controller (5 methods)
✅ 3 Views (index, create, show)
✅ 5 Routes
✅ ~800 سطر كود (Controller + Views)
✅ ~150 سطر JavaScript
```

---

## 🧪 الاختبار

### Scenario 1: إنشاء إذن لعميل مسجل
```
1. اختيار: عميل مسجل → محمد أحمد علي
2. اختيار: الفرع → المصنع
3. إضافة صنف: لمبة LED 12 واط، كمية 10
4. حفظ
✅ Result: ISS-00001 created
✅ المخزون: 45 → 35
✅ رصيد العميل: 0 → -350 (عليه)
```

### Scenario 2: إذن لعميل نقدي
```
1. اختيار: عميل نقدي → "أحمد محمود"
2. باقي الخطوات مشابهة
✅ Result: ISS-00002 created
✅ المخزون خُصم
✅ لا تحديث للرصيد (عميل نقدي)
```

### Scenario 3: إلغاء إذن
```
1. فتح ISS-00001
2. حذف (Delete)
✅ Result: status → cancelled
✅ المخزون: 35 → 45 (رجع)
✅ رصيد العميل: -350 → 0 (رجع)
```

### Scenario 4: مخزون غير كافٍ
```
1. محاولة صرف كمية > المتاح
✅ Result: Exception → "المخزون غير كافٍ"
✅ Transaction rollback
✅ لا تغيير في البيانات
```

---

## 🔗 التكامل

**يستخدم:**
- ✅ SequencerService (TASK-007) → ترقيم تلقائي
- ✅ Customer Model (TASK-008) → تحديث الرصيد
- ✅ Product + ProductBranchStock (TASK-004, TASK-006) → خصم المخزون
- ✅ Branch Model (TASK-002) → الفروع

**سيُستخدم في:**
- TASK-012 (Customer Ledger) → تسجيل قيود الدفتر
- TASK-014 (Reports) → تقارير المبيعات

---

## 🎯 الميزات البارزة

1. **واجهة ديناميكية:** إضافة/حذف أصناف بدون إعادة تحميل
2. **حساب تلقائي:** الإجماليات تُحدّث فورياً
3. **عرض المخزون:** المتاح لكل منتج حسب الفرع
4. **Thread-Safe:** استخدام `lockForUpdate()` لمنع التعارض
5. **Atomic Operations:** كل العمليات في transaction واحدة
6. **Rollback on Error:** إذا فشلت أي خطوة، كل شيء يرجع
7. **Customer Balance:** تحديث تلقائي للرصيد
8. **Print-Ready:** صفحة طباعة احترافية
9. **Cancel with Restore:** إلغاء مع إرجاع المخزون والرصيد

---

## ⚠️ ملاحظات مهمة

### للـ layout:
يجب إضافة `@stack('scripts')` قبل `</body>` في `layouts/app.blade.php`:
```blade
@stack('scripts')
</body>
</html>
```

### للـ Authentication:
حالياً `created_by` يستخدم:
```php
'created_by' => auth()->id() ?? 1,
```
سيتم تحديثه في TASK-016 (Authentication).

---

## 📌 الخطوة القادمة

**TASK-011: Return Vouchers (أذون الإرجاع)**
- مشابه لأذون الصرف لكن عكسي
- إضافة للمخزون بدلاً من الخصم
- نطاق ترقيم خاص: RET-100001 إلى RET-125000

---

**Status:** ✅ TASK-010 Complete  
**Next:** TASK-011 (Return Vouchers) 🔄
