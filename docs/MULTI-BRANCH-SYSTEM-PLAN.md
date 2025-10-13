# 🏢 Multi-Branch Authorization System - Analysis & Implementation Plan

## 📋 المتطلبات الأساسية

### 1. نظام الصلاحيات متعدد المخازن
- ✅ كل مستخدم مرتبط بمخزن أساسي (assigned branch)
- ✅ كل مستخدم له صلاحيات على مخازن معينة
- ✅ الصلاحيات: **View Only** أو **Full Access**
- ✅ المستخدم يقدر يشوف كل المخازن لكن مش يعدل إلا اللي له صلاحية عليها

### 2. العمليات المطلوبة لكل مخزن
- ✅ CRUD على الأصناف (Products) حسب المخزن
- ✅ CRUD على أذونات الصرف (Issue Vouchers)
- ✅ CRUD على أذونات المرتجع (Return Vouchers)
- ✅ CRUD على المدفوعات (Payments)
- ✅ عرض التقارير (Reports) حسب المخزن
- ✅ Dashboard خاص بكل مخزن

### 3. الربط مع الأنظمة الأخرى
- ✅ المخزون مرتبط بالحسابات (Ledger)
- ✅ أذونات الصرف مرتبطة بالعملاء
- ✅ كل عملية في المخزن تنعكس على الحسابات
- ✅ التقارير شاملة لكل النظام

---

## 🔍 تحليل البنية الحالية

### ما هو موجود بالفعل ✅
1. **Branch Model** - جدول الفروع موجود
2. **ProductBranchStock** - المخزون لكل صنف في كل فرع
3. **IssueVoucher** - مرتبطة بـ `branch_id`
4. **ReturnVoucher** - مرتبطة بـ `branch_id`
5. **InventoryService** - يدير المخزون لكل فرع
6. **Spatie Permissions** - جاهز للاستخدام

### ما هو مفقود ❌
1. **User-Branch Relationship** - ربط المستخدمين بالمخازن
2. **Branch Permissions** - صلاحيات المستخدم على كل مخزن
3. **Branch Context Middleware** - التحقق من الصلاحيات
4. **Branch Switching** - تبديل المخزن النشط
5. **Scoped Queries** - استعلامات محددة بالمخزن

---

## 🏗️ خطة التنفيذ

### Phase 1: Database Schema
```
1. إضافة حقول للـ users table:
   - assigned_branch_id (المخزن الافتراضي)
   - current_branch_id (المخزن النشط حاليًا)

2. إنشاء جدول user_branch_permissions:
   - user_id
   - branch_id  
   - permission_level (view_only, full_access)
```

### Phase 2: Models & Relations
```
1. User Model:
   - assignedBranch() belongsTo
   - currentBranch() belongsTo
   - authorizedBranches() belongsToMany
   - canAccessBranch($branchId, $level)
   - switchBranch($branchId)

2. Branch Model:
   - users() belongsToMany
   - hasUser($userId)
```

### Phase 3: Middleware
```
1. EnsureBranchAccess:
   - التحقق من أن المستخدم له صلاحية على المخزن
   - التحقق من نوع الصلاحية (view vs full)

2. SetCurrentBranch:
   - تعيين المخزن النشط من الـ request
   - حفظه في الـ session
```

### Phase 4: API Enhancements
```
1. Branch Context Endpoints:
   - GET /api/v1/user/branches - مخازن المستخدم
   - POST /api/v1/user/switch-branch - تبديل المخزن
   - GET /api/v1/user/current-branch - المخزن الحالي

2. تعديل Controllers:
   - فلترة تلقائية حسب current_branch_id
   - التحقق من الصلاحيات قبل التعديل/الحذف
   - إضافة branch_id لكل العمليات
```

### Phase 5: UI/UX (React)
```
1. Branch Selector Component:
   - Dropdown في الـ Navbar
   - عرض المخازن المصرح بها
   - تبديل سريع بين المخازن

2. Permission Indicators:
   - أيقونة "View Only" للمخازن المحدودة
   - تعطيل الأزرار (Add/Edit/Delete) للمخازن view-only
   - رسائل واضحة للمستخدم
```

---

## 💾 Database Schema Details

### 1. Migration: Add Branch Fields to Users
```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('assigned_branch_id')
          ->nullable()
          ->constrained('branches')
          ->onDelete('set null')
          ->comment('المخزن الافتراضي للمستخدم');
          
    $table->foreignId('current_branch_id')
          ->nullable()
          ->constrained('branches')
          ->onDelete('set null')
          ->comment('المخزن النشط حاليًا');
});
```

### 2. Migration: Create User Branch Permissions
```php
Schema::create('user_branch_permissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('branch_id')->constrained()->onDelete('cascade');
    $table->enum('permission_level', ['view_only', 'full_access'])
          ->default('view_only');
    $table->timestamps();
    
    $table->unique(['user_id', 'branch_id']);
});
```

---

## 🔐 Permission Levels

### View Only (قراءة فقط)
- ✅ عرض قائمة الأصناف
- ✅ عرض تفاصيل الأصناف
- ✅ عرض أذونات الصرف والمرتجع
- ✅ عرض التقارير
- ✅ عرض Dashboard
- ❌ إنشاء/تعديل/حذف أي شيء

### Full Access (صلاحيات كاملة)
- ✅ كل صلاحيات View Only
- ✅ إنشاء أصناف جديدة
- ✅ تعديل الأصناف
- ✅ حذف الأصناف
- ✅ إنشاء أذونات صرف/مرتجع
- ✅ إلغاء أذونات
- ✅ تسجيل مدفوعات

---

## 🎯 Use Cases

### Scenario 1: مستخدم العتبة يشوف مخزن إمبابة
```
1. User: محمد (assigned_branch: العتبة)
2. Permissions: 
   - العتبة: full_access
   - إمبابة: view_only
3. Action: محمد يختار "إمبابة" من القائمة
4. Result:
   - يقدر يشوف كل الأصناف في إمبابة
   - يقدر يشوف كل التقارير
   - مايقدرش يضيف/يعدل/يحذف أي حاجة
   - الأزرار (Add/Edit/Delete) معطلة أو مخفية
```

### Scenario 2: مدير النظام
```
1. User: أحمد (role: super-admin)
2. Permissions: full_access على كل المخازن
3. Action: يقدر يعمل أي حاجة في أي مخزن
4. Result: صلاحيات كاملة على كل شيء
```

### Scenario 3: أذن صرف من مخزن معين
```
1. User: سارة (current_branch: المصنع)
2. Action: تعمل إذن صرف جديد
3. System:
   - الإذن يتسجل بـ branch_id = المصنع
   - المخزون ينخصم من المصنع
   - القيد المحاسبي يسجل للعميل
   - سارة مش هتقدر تعدل الإذن ده من مخزن تاني
```

---

## 🚀 Implementation Priority

### High Priority (نبدأ فيها دلوقتي)
1. ✅ Database migrations (user branches + permissions)
2. ✅ User Model relationships
3. ✅ Branch permissions middleware
4. ✅ API endpoints for branch management
5. ✅ Update existing controllers للتحقق من الصلاحيات

### Medium Priority
6. ✅ Branch switching functionality
7. ✅ Scoped queries (تلقائي حسب current_branch)
8. ✅ Permission checks في كل controller
9. ✅ Tests للصلاحيات

### Low Priority (بعد الـ React)
10. ✅ UI components (Branch Selector)
11. ✅ Permission indicators
12. ✅ User management UI
13. ✅ Audit log للتبديل بين المخازن

---

## 📊 API Structure

### New Endpoints
```
GET    /api/v1/user/profile                    - معلومات المستخدم + مخازنه
GET    /api/v1/user/branches                   - قائمة المخازن المصرح بها
POST   /api/v1/user/switch-branch              - تبديل المخزن النشط
GET    /api/v1/user/current-branch             - المخزن الحالي

GET    /api/v1/admin/users/{id}/branches       - مخازن مستخدم معين (admin)
POST   /api/v1/admin/users/{id}/branches       - إضافة صلاحية مخزن
DELETE /api/v1/admin/users/{id}/branches/{bid} - حذف صلاحية مخزن
PATCH  /api/v1/admin/users/{id}/branches/{bid} - تعديل مستوى الصلاحية
```

### Modified Endpoints (تضيف branch context)
```
GET    /api/v1/products?branch_id=1            - أصناف مخزن معين
GET    /api/v1/issue-vouchers?branch_id=1      - أذونات مخزن معين
GET    /api/v1/dashboard?branch_id=1           - dashboard مخزن معين
```

---

## 🧪 Testing Strategy

### 1. Unit Tests
- User::canAccessBranch()
- User::hasFullAccessToBranch()
- Branch::hasUser()

### 2. Feature Tests
- Branch switching
- Permission checks
- Scoped queries

### 3. Integration Tests
- Create voucher with branch
- View-only user cannot edit
- Full-access user can edit

---

## ⚠️ Important Considerations

### 1. Default Branch Behavior
- لو المستخدم مش مختار مخزن → استخدم assigned_branch
- لو مافيش assigned_branch → استخدم أول مخزن مصرح له
- لو مافيش أي مخازن → error

### 2. Security
- ✅ التحقق من الصلاحيات في الـ middleware
- ✅ التحقق مرة تانية في الـ controller
- ✅ منع تبديل المخزن لمخزن غير مصرح به
- ✅ Audit log لكل العمليات المهمة

### 3. Performance
- ✅ Cache قائمة المخازن المصرح بها
- ✅ Eager load permissions
- ✅ Index على user_branch_permissions

---

## 🎨 UI/UX Guidelines

### Branch Selector (في الـ Navbar)
```
┌─────────────────────────────┐
│ 🏢 المخزن الحالي: العتبة   │
│ ▼                           │
├─────────────────────────────┤
│ ✓ العتبة (كامل)             │
│   إمبابة (عرض فقط) 👁      │
│   المصنع (كامل)             │
└─────────────────────────────┘
```

### Permission Indicators
- 🔓 **Full Access** - أيقونة قفل مفتوح
- 👁 **View Only** - أيقونة عين
- 🚫 **No Access** - لا يظهر في القائمة

### Disabled States
- Buttons: opacity: 0.5 + cursor: not-allowed
- Tooltip: "ليس لديك صلاحية للتعديل على هذا المخزن"

---

## 🤔 Questions to Answer

1. **هل المستخدم يقدر يشتغل على أكتر من مخزن في نفس الوقت؟**
   - لا، مخزن واحد نشط في كل لحظة

2. **هل الـ Super Admin محتاج permissions؟**
   - لا، له صلاحيات كاملة على كل المخازن تلقائيًا

3. **هل المدفوعات مرتبطة بمخزن؟**
   - لا، المدفوعات عامة على مستوى العميل

4. **هل التقارير تشمل كل المخازن أو مخزن واحد؟**
   - فيه option للاختيار (current branch أو all branches)

---

## ✅ Next Actions

هل نبدأ التنفيذ؟

**Option A:** نبدأ بالـ Database Migrations و Models (الأساس)
**Option B:** نعمل Middleware و Authorization الأول
**Option C:** نعمل الـ API Endpoints الجديدة
**Option D:** تحليل أكتر قبل ما نبدأ

---

**إيه رأيك في الخطة دي؟ في حاجة ناقصة أو محتاج تعديل؟** 🤔
