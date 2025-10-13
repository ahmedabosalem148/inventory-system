# 🚀 الخطوات القادمة - Next Steps

**آخر تحديث:** 2 أكتوبر 2025  
**الحالة الحالية:** المرحلة الأولى مكتملة (Basic CRUD)

---

## ✅ ما تم إنجازه حتى الآن

### التاسكات المكتملة (6 Tasks + 1 Bonus):

| الكود | الوصف | الملفات | الحالة |
|------|-------|---------|--------|
| **TASK-001** | Laravel 12 + Bootstrap RTL + Packages | config/, layouts/app.blade.php | ✅ 100% |
| **TASK-002** | Branches CRUD | Migration, Model, Controller, 3 Views | ✅ 100% |
| **TASK-003** | Categories CRUD | Migration, Model, Controller, 3 Views | ✅ 100% |
| **TASK-004** | Products Table | Migration, Model, Seeder (8 products) | ✅ 100% |
| **TASK-006** | product_branch_stock Pivot | Migration, Model, Seeder (24 records) | ✅ 100% |
| **Bonus** | Products Full UI | ProductController + 4 Views | ✅ 100% |

### قاعدة البيانات:
```
✅ 7 Migrations منفذة:
   ├── users, cache, jobs, sessions (Laravel default)
   ├── branches (3 فروع)
   ├── categories (6 تصنيفات)
   ├── products (8 منتجات)
   └── product_branch_stock (24 سجل مخزون)

✅ 5 Models:
   ├── Branch (with scopes + relationships)
   ├── Category (with scopes)
   ├── Product (with scopes + attributes)
   ├── ProductBranchStock (with scopes)
   └── User (default)

✅ 4 Controllers:
   ├── DashboardController (index with stats + low stock)
   ├── BranchController (CRUD)
   ├── CategoryController (CRUD)
   └── ProductController (CRUD + search + filter)

✅ 14 Views:
   ├── layouts/app.blade.php
   ├── dashboard.blade.php
   ├── branches/ (index, create, edit)
   ├── categories/ (index, create, edit)
   └── products/ (index, create, edit, show)

✅ 22 Routes مسجلة:
   ├── / → dashboard
   ├── /dashboard
   ├── /branches (7 routes)
   ├── /categories (7 routes)
   └── /products (7 routes)
```

### الميزات المُنفذة:
- ✅ **Bootstrap 5.3 RTL** مع Cairo Font
- ✅ **Eager Loading** لتحسين الأداء
- ✅ **Validation** كامل مع رسائل عربية
- ✅ **DB Transactions** في store() للمنتجات
- ✅ **Scopes** للاستعلامات المتكررة
- ✅ **Pagination** (15 عنصر/صفحة)
- ✅ **Search & Filter** في المنتجات
- ✅ **Initial Stock** عند إضافة منتج
- ✅ **Low Stock Alerts** في Dashboard
- ✅ **Product Analytics** في صفحة show

---

## 📋 الخطوات القادمة (حسب الأولوية)

### المرحلة 2: أذون الصرف والإرجاع + حركة المخزون

#### 🔥 أولوية عالية جداً (High Priority)

##### ⚙️ TASK-007: Sequencer Service
**الهدف:** خدمة توليد الأرقام المتسلسلة بأمان

**المتطلبات:**
- ✔️ Service Class: `app/Services/SequencerService.php`
- ✔️ استخدام `DB::table('products')->lockForUpdate()` أو `SELECT...FOR UPDATE`
- ✔️ دعم Prefixes (مثل: ISS-, RET-)
- ✔️ Reset سنوي (optional)

**الاستخدام:**
```php
SequencerService::getNext('issue_voucher', 'ISS-'); // ISS-00001
SequencerService::getNext('return_voucher', 'RET-'); // RET-100001
```

**مدة التنفيذ المتوقعة:** 1-2 ساعة

---

##### 📋 TASK-010: Issue Vouchers (أذون الصرف)
**الهدف:** إصدار أذون صرف للعملاء مع خصم المخزون

**المتطلبات:**

1. **Migration: `issue_vouchers`**
   ```
   - id
   - voucher_number (unique, e.g., ISS-00001)
   - customer_id (FK nullable - للعملاء المسجلين)
   - customer_name (string nullable - لعملاء الكاش)
   - branch_id (FK → branches)
   - issue_date (date)
   - notes (text nullable)
   - total_amount (decimal)
   - status (enum: pending, completed, cancelled)
   - created_by (FK → users)
   - timestamps
   ```

2. **Migration: `issue_voucher_items`**
   ```
   - id
   - issue_voucher_id (FK cascade)
   - product_id (FK)
   - quantity (integer)
   - unit_price (decimal)
   - total_price (decimal)
   - timestamps
   ```

3. **Models:**
   - `IssueVoucher` (with items relationship)
   - `IssueVoucherItem` (with product, voucher)

4. **Controller: `IssueVoucherController`**
   - index() - قائمة الأذونات
   - create() - نموذج إضافة
   - store() - حفظ + خصم المخزون (DB Transaction)
   - show() - طباعة الإذن
   - destroy() - إلغاء (إرجاع المخزون)

5. **Views:**
   - `issue_vouchers/index.blade.php` - قائمة مع فلترة
   - `issue_vouchers/create.blade.php` - نموذج ديناميكي (إضافة أصناف)
   - `issue_vouchers/show.blade.php` - عرض وطباعة

**Logic الأساسي:**
```php
DB::transaction(function () {
    // 1. إنشاء الإذن
    $voucher = IssueVoucher::create([...]);
    
    // 2. إضافة الأصناف
    foreach ($items as $item) {
        IssueVoucherItem::create([
            'issue_voucher_id' => $voucher->id,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            ...
        ]);
        
        // 3. خصم المخزون
        $stock = ProductBranchStock::where('product_id', $item['product_id'])
                                    ->where('branch_id', $voucher->branch_id)
                                    ->lockForUpdate()
                                    ->first();
        
        $stock->decrement('current_stock', $item['quantity']);
    }
    
    // 4. تحديث دفتر العميل (سيأتي في TASK-008)
});
```

**مدة التنفيذ المتوقعة:** 3-4 ساعات

---

##### 🔄 TASK-011: Return Vouchers (أذون الإرجاع)
**الهدف:** استقبال مرتجعات العملاء وإضافة للمخزون

**المتطلبات:**

1. **Migration: `return_vouchers`**
   ```
   - id
   - voucher_number (unique, range: 100001-125000)
   - customer_id (FK nullable)
   - customer_name (string nullable)
   - branch_id (FK)
   - return_date (date)
   - notes (text)
   - total_amount (decimal)
   - status
   - created_by
   - timestamps
   ```

2. **Migration: `return_voucher_items`**
   ```
   - id
   - return_voucher_id (FK)
   - product_id (FK)
   - quantity (integer)
   - unit_price (decimal)
   - total_price (decimal)
   - timestamps
   ```

3. **Models + Controller + Views** (مشابه لـ TASK-010 لكن عكسي)

**Logic الأساسي:**
```php
// في store():
$stock->increment('current_stock', $item['quantity']); // عكس الصرف
```

**مدة التنفيذ المتوقعة:** 2-3 ساعات

---

#### 🟡 أولوية متوسطة (Medium Priority)

##### 👥 TASK-008: Customers Management
**الهدف:** إدارة العملاء + دفتر الحساب

**المتطلبات:**

1. **Migration: `customers`**
   ```
   - id
   - name (string)
   - phone (string nullable)
   - address (text nullable)
   - balance (decimal default 0) - الرصيد الحالي
   - is_active (boolean)
   - notes (text nullable)
   - timestamps
   ```

2. **Model:** `Customer` (with ledger relationship)

3. **Controller:** `CustomerController` (CRUD + Ledger)

4. **Views:**
   - `customers/index.blade.php` - قائمة العملاء
   - `customers/create.blade.php` - إضافة عميل
   - `customers/edit.blade.php` - تعديل
   - `customers/ledger.blade.php` - دفتر الحساب

**مدة التنفيذ المتوقعة:** 2-3 ساعات

---

##### 📖 TASK-012: Customer Ledger (دفتر العملاء)
**الهدف:** تسجيل حركات الحسابات

**المتطلبات:**

1. **Migration: `customer_ledger`**
   ```
   - id
   - customer_id (FK)
   - transaction_date (date)
   - type (enum: debit, credit) - مدين/دائن
   - amount (decimal)
   - description (string)
   - reference_type (string nullable) - IssueVoucher, ReturnVoucher, Payment
   - reference_id (bigint nullable)
   - balance_after (decimal)
   - created_by
   - timestamps
   ```

2. **Model:** `CustomerLedger`

3. **Service:** `LedgerService` لتسجيل القيود تلقائياً

**مدة التنفيذ المتوقعة:** 2-3 ساعات

---

##### 💳 TASK-013: Payments Management
**الهدف:** تسجيل المدفوعات (كاش، شيكات)

**المتطلبات:**

1. **Migration: `payments`**
   ```
   - id
   - customer_id (FK)
   - payment_date (date)
   - amount (decimal)
   - payment_method (enum: cash, cheque, transfer)
   - cheque_number (string nullable)
   - cheque_date (date nullable)
   - cheque_bank (string nullable)
   - cheque_status (enum: pending, cleared, bounced)
   - notes (text)
   - created_by
   - timestamps
   ```

2. **Controller:** `PaymentController`

3. **Views:**
   - `payments/index.blade.php` - قائمة المدفوعات
   - `payments/create.blade.php` - إضافة دفعة
   - `cheques/index.blade.php` - قائمة الشيكات

**مدة التنفيذ المتوقعة:** 2-3 ساعات

---

### المرحلة 3: التقارير والتحليلات

#### 📊 TASK-014: Reports
**الهدف:** تقارير شاملة

**التقارير المطلوبة:**

1. **تقرير إجمالي المخزون:**
   - جميع المنتجات مع المخزون الحالي لكل فرع
   - القيمة الإجمالية (بسعر الشراء والبيع)
   - Export إلى Excel

2. **تقرير حركة صنف:**
   - جميع أذون الصرف والإرجاع لمنتج معين
   - فترة زمنية محددة

3. **تقرير أرصدة العملاء:**
   - جميع العملاء مع الرصيد الحالي
   - ترتيب حسب الرصيد

4. **تقرير العملاء غير النشطين:**
   - العملاء الذين لم يتم التعامل معهم منذ فترة

5. **تقرير الشيكات:**
   - جميع الشيكات (المصروفة، غير المصروفة، المرتدة)

**المتطلبات:**
- Controller: `ReportController`
- Views: `reports/*.blade.php`
- Export to Excel: `maatwebsite/excel`
- Export to PDF: `barryvdh/laravel-dompdf`

**مدة التنفيذ المتوقعة:** 4-5 ساعات

---

### المرحلة 4: الأمان والصلاحيات

#### 🔐 TASK-015: Roles & Permissions
**الهدف:** إدارة الصلاحيات

**الأدوار المطلوبة:**
- **Admin:** كل الصلاحيات
- **Manager:** صلاحيات الفروع + التقارير
- **Cashier:** أذون الصرف + المدفوعات
- **Viewer:** عرض فقط

**المتطلبات:**
- استخدام `spatie/laravel-permission` (مُثبّت بالفعل)
- Seeders للأدوار
- Middleware على الـ Controllers
- صفحة إدارة المستخدمين

**مدة التنفيذ المتوقعة:** 2-3 ساعات

---

#### 🔑 TASK-016: Authentication
**الهدف:** نظام تسجيل الدخول

**المتطلبات:**
- Laravel Breeze أو Jetstream
- صفحة Login
- صفحة Register (للأدمن فقط)
- Reset Password

**مدة التنفيذ المتوقعة:** 1-2 ساعة

---

### المرحلة 5: الميزات الإضافية

#### 📝 TASK-017: Activity Log
**الهدف:** تسجيل جميع العمليات

**المتطلبات:**
- استخدام `spatie/laravel-activitylog` (مُثبّت بالفعل)
- Logging تلقائي في Models
- صفحة عرض السجلات

**مدة التنفيذ المتوقعة:** 2-3 ساعات

---

#### 🔄 TASK-018: Transfers Between Branches
**الهدف:** نقل المنتجات بين الفروع

**المتطلبات:**

1. **Migration: `transfers`**
   ```
   - id
   - transfer_number
   - from_branch_id (FK)
   - to_branch_id (FK)
   - transfer_date
   - status (pending, completed, cancelled)
   - notes
   - created_by
   - timestamps
   ```

2. **Migration: `transfer_items`**
   ```
   - id
   - transfer_id (FK)
   - product_id (FK)
   - quantity
   - timestamps
   ```

3. **Logic:**
   - خصم من الفرع المُرسل
   - إضافة للفرع المُستقبل
   - DB Transaction

**مدة التنفيذ المتوقعة:** 3-4 ساعات

---

### المرحلة 6: Deployment & Testing

#### 🚀 TASK-019: Deployment to Hostinger
**الهدف:** رفع النظام على الاستضافة

**الخطوات:**
1. تغيير DB من SQLite إلى MySQL
2. تحديث `.env` للـ production
3. Run migrations على السيرفر
4. Run seeders
5. Optimize:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```
6. تفعيل HTTPS
7. اختبار شامل

**مدة التنفيذ المتوقعة:** 2-3 ساعات

---

## 📌 ملاحظات مهمة

### الأولويات المقترحة (الترتيب الأمثل):

```
1. TASK-007 (Sequencer) ← أساسي لكل شيء
2. TASK-008 (Customers) ← يحتاجه الصرف والإرجاع
3. TASK-010 (Issue Vouchers) ← الميزة الأهم
4. TASK-011 (Return Vouchers)
5. TASK-012 (Ledger)
6. TASK-013 (Payments)
7. TASK-014 (Reports)
8. TASK-015 (Roles & Permissions)
9. TASK-016 (Authentication)
10. TASK-017 (Activity Log)
11. TASK-018 (Transfers)
12. TASK-019 (Deployment)
```

### تقدير الوقت الإجمالي:
- **المرحلة 2:** ~10-13 ساعة
- **المرحلة 3:** ~4-5 ساعات
- **المرحلة 4:** ~3-5 ساعات
- **المرحلة 5:** ~5-7 ساعات
- **المرحلة 6:** ~2-3 ساعات
- **الإجمالي:** ~24-33 ساعة عمل

---

## 🎯 التوصيات

### للبدء الآن:
1. **TASK-007 (Sequencer)** - سريع ومطلوب لكل شيء
2. **TASK-008 (Customers)** - بنية أساسية
3. **TASK-010 (Issue Vouchers)** - القيمة الأكبر للمستخدم

### للتحسين المستقبلي:
- إضافة **Barcode Scanner** للمنتجات
- تطبيق **Mobile App** (React Native)
- **Email Notifications** للعملاء
- **Backup Automation** يومياً

---

## 📂 الملفات الداعمة

- **PLAN.md** - الخطة الشاملة للتنفيذ
- **BACKLOG.md** - 36 تاسك مفصلة
- **MIGRATIONS-ORDER.md** - ترتيب الـ migrations
- **API-CONTRACT.md** - مواصفات API والشاشات
- **QA-CHECKLIST.md** - معايير الجودة
- **TEST-CASES.md** - 60 حالة اختبار

---

## ✨ خلاصة

**الوضع الحالي:** نظام قوي جاهز لإدارة الفروع والتصنيفات والمنتجات  
**الخطوة القادمة:** TASK-007 (Sequencer) ثم TASK-010 (Issue Vouchers)  
**الهدف النهائي:** نظام إدارة مخزون وحسابات متكامل مع تقارير شاملة

**جاهز للانطلاق! 🚀**
