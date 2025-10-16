# TASK-115: Fix 403 Forbidden Error - User Branch Assignment

## المشكلة
بعد إصلاح الـ 404 error، ظهر خطأ **403 Forbidden** على endpoint `/api/v1/issue-vouchers`:

```
Failed to load resource: the server responded with a status of 403 (Forbidden)
Error loading invoices: AxiosError
```

## السبب الجذري
الـ Backend في `IssueVoucherController::index()` يتحقق من أن المستخدم لديه فرع نشط:

```php
if (!$user->hasRole('super-admin')) {
    $activeBranch = $user->getActiveBranch();
    if (!$activeBranch) {
        return response()->json([
            'message' => 'لم يتم تعيين فرع للمستخدم'
        ], 403);
    }
    $query->where('branch_id', $activeBranch->id);
}
```

المستخدم المسجل دخوله لم يكن لديه:
- ❌ `current_branch_id` (الفرع النشط)
- ❌ `assigned_branch_id` (الفرع الافتراضي)
- ❌ علاقة في جدول `user_branch_permissions`

## الحل المطبق

### 1. فحص قاعدة البيانات
```bash
php artisan tinker --execute="echo 'Branches: ' . \App\Models\Branch::count();"
# Result: 3 branches exist
```

### 2. إنشاء Script لتعيين الفرع
ملف: `assign_branch.php`

```php
// Get first user
$user = App\Models\User::first();

// Get first branch
$branch = App\Models\Branch::first();

// Assign branch to user
$user->assigned_branch_id = $branch->id;
$user->current_branch_id = $branch->id;
$user->save();

// Add to authorized branches
$user->authorizedBranches()->attach($branch->id, [
    'permission_level' => 'full_access',
]);
```

### 3. تنفيذ Script
```bash
php assign_branch.php
```

**النتيجة:**
```
User: مدير النظام (ID: 1)
✅ Successfully assigned branch 'المصنع' to user 'مدير النظام'
✅ Added branch to authorized branches with full access

User branches:
  - المصنع (ID: 1)
```

## التغييرات في قاعدة البيانات

### جدول `users`
| user_id | name | current_branch_id | assigned_branch_id |
|---------|------|-------------------|-------------------|
| 1 | مدير النظام | 1 | 1 |

### جدول `user_branch_permissions`
| user_id | branch_id | permission_level |
|---------|-----------|-----------------|
| 1 | 1 | full_access |

## ملاحظات مهمة

### 1. هيكل جدول الصلاحيات
الجدول يستخدم `permission_level` enum وليس `full_access` boolean:
```php
$table->enum('permission_level', ['view_only', 'full_access'])
      ->default('view_only');
```

### 2. منطق الحصول على الفرع النشط
من `User::getActiveBranch()`:
1. يحاول `current_branch_id` أولاً
2. ثم `assigned_branch_id`
3. ثم أول فرع من `authorizedBranches()`

### 3. متطلبات الصلاحيات
- `super-admin`: يمكنه رؤية كل الفواتير من جميع الفروع
- مستخدمين عاديين: يجب أن يكون لديهم:
  - ✅ فرع نشط (`getActiveBranch()` returns a branch)
  - ✅ صلاحية `full_access` على الفرع لعمل CRUD operations

## الاختبار المطلوب
1. ✅ تحميل قائمة الفواتير (يجب أن يعمل الآن)
2. ⏳ إنشاء فاتورة جديدة
3. ⏳ تعديل فاتورة موجودة
4. ⏳ حذف فاتورة

## الحل طويل المدى

### خيار 1: Branch Selector في Frontend
إضافة dropdown في Navbar ليختار المستخدم الفرع النشط:
```tsx
<select onChange={(e) => setActiveBranch(e.target.value)}>
  {userBranches.map(branch => (
    <option key={branch.id} value={branch.id}>
      {branch.name}
    </option>
  ))}
</select>
```

### خيار 2: Auto-assign في Seeder
إضافة كود في `DatabaseSeeder` لتعيين فرع افتراضي لكل مستخدم:
```php
foreach (User::all() as $user) {
    $branch = Branch::first();
    $user->update([
        'assigned_branch_id' => $branch->id,
        'current_branch_id' => $branch->id,
    ]);
    $user->authorizedBranches()->attach($branch->id, [
        'permission_level' => 'full_access'
    ]);
}
```

### خيار 3: Middleware للتحقق
إنشاء middleware يتحقق من وجود فرع نشط قبل الوصول للـ routes:
```php
class EnsureUserHasBranch {
    public function handle($request, $next) {
        if (!auth()->user()->getActiveBranch()) {
            return response()->json([
                'message' => 'يرجى اختيار فرع للعمل عليه'
            ], 403);
        }
        return $next($request);
    }
}
```

---
**تاريخ الإصلاح:** أكتوبر 16، 2025
**الحالة:** ✅ تم الحل - جاهز للاختبار
**الخطوة التالية:** إعادة تحميل صفحة المبيعات في المتصفح
