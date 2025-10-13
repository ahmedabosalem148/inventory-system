# دليل استخدام Activity Log (سجل الأنشطة)

## نظرة عامة

تم تطبيق **Spatie Laravel Activity Log** لتسجيل جميع العمليات الحساسة في النظام بما في ذلك:
- إنشاء/تعديل/حذف المدفوعات
- إنشاء/تعديل/حذف الشيكات
- إنشاء/تعديل/حذف إذونات المرتجعات
- اعتماد إذونات الصرف
- طباعة المستندات

---

## الميزات المطبقة

### 1. **Automatic Logging** (التسجيل التلقائي)

تم إضافة `LogsActivity` trait للـ Models التالية:
- `Payment`
- `Cheque`
- `ReturnVoucher`

**ما يتم تسجيله تلقائياً:**
- إنشاء سجل جديد (`created`)
- تعديل سجل موجود (`updated`) - فقط الحقول المتغيرة
- حذف سجل (`deleted`)

**مثال على التكوين:**
```php
// في Payment.php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['customer_id', 'amount', 'payment_method', 'payment_date'])
        ->logOnlyDirty() // سجل فقط التغييرات
        ->dontSubmitEmptyLogs() // لا تسجل إذا لم يكن هناك تغيير
        ->setDescriptionForEvent(fn(string $eventName) => "سداد: {$eventName}");
}
```

---

### 2. **Manual Logging** (التسجيل اليدوي)

للعمليات الحساسة مثل الاعتماد والطباعة:

```php
// في Controller
activity()
    ->performedOn($issueVoucher)           // الكائن المتأثر
    ->causedBy(auth()->user())             // المستخدم المنفذ
    ->withProperties([                     // بيانات إضافية
        'voucher_number' => $issueVoucher->number,
        'ip_address' => request()->ip(),
    ])
    ->log('تم اعتماد إذن صرف رقم ' . $issueVoucher->number);
```

---

### 3. **Viewer Interface** (واجهة العرض)

تم إنشاء واجهة كاملة لعرض السجلات:

#### **Routes:**
```php
GET /activity-log           // عرض قائمة الأنشطة
GET /activity-log/{id}      // عرض تفاصيل نشاط معين
```

#### **الفلاتر المتاحة:**
- نوع النشاط (Payments, Cheques, Return Vouchers)
- الحدث (created, updated, deleted)
- التاريخ (من - إلى)
- المستخدم

#### **الصلاحيات:**
- فقط المستخدمون الذين لديهم صلاحية `view-activity-log` (Manager فقط)

---

## أمثلة الاستخدام في Controllers

### مثال 1: تسجيل الاعتماد
```php
public function approve(IssueVoucher $issueVoucher)
{
    $this->authorize('approve', $issueVoucher);

    $issueVoucher->update([
        'status' => 'APPROVED',
        'approved_by' => auth()->id(),
        'approved_at' => now(),
    ]);

    // ✅ تسجيل عملية الاعتماد
    activity()
        ->performedOn($issueVoucher)
        ->causedBy(auth()->user())
        ->withProperties([
            'voucher_number' => $issueVoucher->number,
            'total' => $issueVoucher->total_after,
        ])
        ->log('اعتماد إذن صرف #' . $issueVoucher->number);

    return redirect()->back()->with('success', 'تم الاعتماد');
}
```

### مثال 2: تسجيل الطباعة
```php
public function print(IssueVoucher $issueVoucher)
{
    $this->authorize('print', $issueVoucher);

    // ✅ تسجيل عملية الطباعة
    activity()
        ->performedOn($issueVoucher)
        ->causedBy(auth()->user())
        ->log('طباعة إذن صرف #' . $issueVoucher->number);

    return view('issue-vouchers.print', compact('issueVoucher'));
}
```

### مثال 3: تسجيل التعديلات الهامة
```php
public function update(Request $request, Payment $payment)
{
    $oldAmount = $payment->amount;
    
    $payment->update($request->all());

    // ✅ تسجيل إضافي للتغييرات الكبيرة
    if (abs($oldAmount - $payment->amount) > 1000) {
        activity()
            ->performedOn($payment)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_amount' => $oldAmount,
                'new_amount' => $payment->amount,
                'difference' => $payment->amount - $oldAmount,
            ])
            ->log('تعديل مبلغ سداد كبير');
    }

    return redirect()->back();
}
```

---

## البيانات المسجلة

### في جدول `activity_log`:

| Column | Description |
|--------|-------------|
| `id` | معرف السجل |
| `log_name` | اسم السجل (default) |
| `description` | وصف النشاط |
| `subject_type` | نوع الكائن (App\Models\Payment) |
| `subject_id` | معرف الكائن |
| `causer_type` | نوع المنفذ (App\Models\User) |
| `causer_id` | معرف المنفذ |
| `properties` | JSON يحتوي على التغييرات/البيانات الإضافية |
| `event` | نوع الحدث (created, updated, deleted) |
| `created_at` | وقت حدوث النشاط |

---

## الأمان والخصوصية

### 1. **التحكم في الوصول:**
```php
// في ActivityLogController
if (!auth()->user()->hasPermissionTo('view-activity-log')) {
    abort(403, 'غير مصرح لك بعرض سجل الأنشطة');
}
```

### 2. **تسجيل IP Address:**
```php
activity()
    ->withProperties(['ip' => request()->ip()])
    ->log('...');
```

### 3. **عدم تسجيل البيانات الحساسة:**
```php
// ❌ لا تسجل كلمات المرور
->logOnly(['name', 'email']) // بدون password
```

---

## التقارير والتحليلات

### الحصول على أنشطة مستخدم معين:
```php
$activities = Activity::where('causer_id', $userId)->get();
```

### الحصول على أنشطة كائن معين:
```php
$activities = Activity::forSubject($payment)->get();
```

### الحصول على الأنشطة خلال فترة زمنية:
```php
$activities = Activity::whereBetween('created_at', [$from, $to])->get();
```

---

## Best Practices

### ✅ **افعل:**
1. سجّل جميع العمليات الحساسة (اعتماد، حذف، تعديل مبالغ كبيرة)
2. أضف context إضافي في `withProperties()` (IP, device, reason)
3. استخدم descriptions واضحة بالعربية
4. قيّد الوصول للـ Activity Log على Manager فقط

### ❌ **لا تفعل:**
1. لا تسجل كلمات المرور أو tokens
2. لا تسجل كل عملية صغيرة (view, search)
3. لا تجعل Activity Log متاحاً لجميع المستخدمين
4. لا تحذف سجلات الأنشطة

---

## الصيانة

### تنظيف السجلات القديمة:
```php
// في Command أو Task
Activity::where('created_at', '<', now()->subMonths(12))->delete();
```

### Backup سجلات الأنشطة:
```bash
php artisan backup:run --only-db
```

---

## المراجع

- [Spatie Activity Log Documentation](https://spatie.be/docs/laravel-activitylog)
- TASK-027 في BACKLOG.md
- Files Created:
  - `app/Http/Controllers/ActivityLogController.php`
  - `resources/views/activity-log/index.blade.php`
  - `resources/views/activity-log/show.blade.php`
  - `routes/activity_log.php`
  - Examples: `PaymentControllerExample.php`, `IssueVoucherControllerExample.php`

