# دليل استخدام الفلاتر المتقدمة (Advanced Filters)

## نظرة عامة

تم إنشاء نظام فلاتر متقدم قابل لإعادة الاستخدام يتضمن:
- مكوّن Blade جاهز للاستخدام
- Trait للـ Models يحتوي على Scopes
- Middleware لحفظ الفلاتر في Session
- أمثلة تطبيق على التقارير

---

## المكونات الرئيسية

### 1. **Filterable Trait**

يضاف إلى أي Model يحتاج فلاتر:

```php
// في Model
use App\Models\Traits\Filterable;

class InventoryMovement extends Model
{
    use Filterable;
    
    // الآن يمكن استخدام:
    // ->filterByDateRange($from, $to)
    // ->filterByBranch($branchId)
    // ->filterByProduct($productId)
    // ->filterByCustomer($customerId)
    // ->filterByStatus($status)
    // ->filterByCategory($categoryId)
    // ->search($searchTerm)
    // ->applyFilters($request->all())
}
```

#### Scopes المتوفرة:

| Scope | الوصف | المعاملات |
|-------|-------|-----------|
| `filterByDateRange()` | فلترة بفترة زمنية | `$from`, `$to`, `$column` (افتراضي: created_at) |
| `filterByBranch()` | فلترة حسب الفرع | `$branchId`, `$column` (افتراضي: branch_id) |
| `filterByProduct()` | فلترة حسب المنتج | `$productId` |
| `filterByCustomer()` | فلترة حسب العميل | `$customerId` |
| `filterByStatus()` | فلترة حسب الحالة | `$status` |
| `filterByCategory()` | فلترة حسب التصنيف | `$categoryId` (يدعم العلاقات) |
| `search()` | بحث في name, code, sku, number | `$searchTerm` |
| `applyFilters()` | تطبيق جميع الفلاتر دفعة واحدة | `array $filters` |

---

### 2. **Advanced Filter Component**

مكوّن Blade قابل لإعادة الاستخدام:

```blade
@include('components.filters.advanced-filter', [
    'action' => route('reports.inventory.movements'),
    'showDateRange' => true,
    'showBranch' => true,
    'showProduct' => true,
    'showCustomer' => false,
    'showCategory' => false,
    'showStatus' => false,
    'showLowStock' => false,
    'showExport' => true,
    'autoSubmit' => false,
])
```

#### المعاملات المتاحة:

| المعامل | النوع | الافتراضي | الوصف |
|---------|------|-----------|-------|
| `action` | string | `#` | مسار الـ form action |
| `showDateRange` | bool | `false` | عرض فلتر التاريخ (من/إلى) |
| `showBranch` | bool | `false` | عرض فلتر الفرع |
| `showProduct` | bool | `false` | عرض فلتر الصنف |
| `showCustomer` | bool | `false` | عرض فلتر العميل |
| `showCategory` | bool | `false` | عرض فلتر التصنيف |
| `showStatus` | bool | `false` | عرض فلتر الحالة |
| `statusOptions` | array | `null` | خيارات مخصصة للحالة |
| `showLowStock` | bool | `false` | عرض checkbox "أقل من الحد الأدنى" |
| `showExport` | bool | `false` | عرض أزرار تصدير CSV/PDF |
| `autoSubmit` | bool | `false` | إرسال Form تلقائياً عند تغيير الفلتر |

---

### 3. **PersistFilters Middleware**

يحفظ الفلاتر في Session ليتم استرجاعها عند العودة للصفحة:

```php
// في Kernel.php أو routes
Route::middleware(['auth', 'persist.filters'])->group(function () {
    Route::get('/reports/inventory/movements', [InventoryReportController::class, 'movements']);
});
```

**الميزات:**
- حفظ تلقائي للفلاتر في Session
- استرجاع الفلاتر عند العودة للصفحة
- مسح الفلاتر عند النقر "إعادة تعيين"

---

## أمثلة الاستخدام

### مثال 1: تقرير حركة المخزون

```php
// في Controller
public function movements(Request $request)
{
    $query = InventoryMovement::with(['product', 'branch'])
        ->applyFilters($request->only([
            'date_from',
            'date_to',
            'branch_id',
            'product_id',
            'category_id',
        ]));

    // فلتر إضافي مخصص
    if ($request->filled('movement_type')) {
        $query->ofType($request->movement_type);
    }

    $movements = $query->latest()->paginate(50)->appends($request->query());

    return view('reports.inventory.movements', compact('movements'));
}
```

```blade
{{-- في View --}}
@include('components.filters.advanced-filter', [
    'action' => route('reports.inventory.movements'),
    'showDateRange' => true,
    'showBranch' => true,
    'showProduct' => true,
    'showCategory' => true,
    'showExport' => true,
])
```

---

### مثال 2: تقرير الأرصدة الحالية

```php
// في Controller
public function currentStock(Request $request)
{
    $query = Product::with(['branches', 'category'])
        ->active()
        ->applyFilters($request->only(['category_id', 'search']));

    // فلتر: أقل من الحد الأدنى
    if ($request->filled('low_stock')) {
        $query->whereHas('branches', function ($q) {
            $q->whereRaw('current_qty < min_qty');
        });
    }

    // فلتر حسب فرع معين
    if ($request->filled('branch_id')) {
        $query->whereHas('branches', function ($q) use ($request) {
            $q->where('branch_id', $request->branch_id);
        });
    }

    $products = $query->paginate(50)->appends($request->query());

    return view('reports.inventory.current-stock', compact('products'));
}
```

```blade
{{-- في View --}}
@include('components.filters.advanced-filter', [
    'action' => route('reports.inventory.current-stock'),
    'showBranch' => true,
    'showCategory' => true,
    'showLowStock' => true,
    'showExport' => true,
])
```

---

### مثال 3: تقرير أرصدة العملاء

```php
// في Controller
public function customerBalances(Request $request)
{
    $query = Customer::with('ledgerEntries')
        ->active()
        ->search($request->search);

    // حساب الرصيد لكل عميل
    $customers = $query->get()->map(function ($customer) {
        $customer->balance = $customer->ledgerEntries->sum('debit_aliah') 
                           - $customer->ledgerEntries->sum('credit_lah');
        return $customer;
    });

    // فلتر: عملاء لديهم رصيد فقط
    if ($request->filled('has_balance')) {
        $customers = $customers->filter(fn($c) => abs($c->balance) > 0);
    }

    return view('reports.customers.balances', compact('customers'));
}
```

---

### مثال 4: فلاتر مخصصة

يمكن إضافة فلاتر مخصصة خاصة بـ Model معين:

```php
// في Model
class IssueVoucher extends Model
{
    use Filterable;

    /**
     * Scope للإذونات النقدية فقط
     */
    public function scopeCashOnly($query)
    {
        return $query->where('payment_type', 'CASH');
    }

    /**
     * Scope للإذونات الآجلة فقط
     */
    public function scopeCreditOnly($query)
    {
        return $query->where('payment_type', 'CREDIT');
    }

    /**
     * Scope للإذونات المعتمدة فقط
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }
}
```

```php
// في Controller
$vouchers = IssueVoucher::applyFilters($request->all())
    ->approved()
    ->creditOnly()
    ->latest()
    ->paginate(50);
```

---

## التصدير (CSV/PDF)

### تصدير CSV:

```php
public function exportCSV($data)
{
    $filename = 'report-' . date('Y-m-d') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
    ];

    $callback = function() use ($data) {
        $file = fopen('php://output', 'w');
        
        // UTF-8 BOM للعربية في Excel
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($file, ['العمود 1', 'العمود 2', 'العمود 3']);

        foreach ($data as $row) {
            fputcsv($file, [$row->field1, $row->field2, $row->field3]);
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}
```

### تصدير PDF:

```php
public function exportPDF($data)
{
    $pdf = app('dompdf.wrapper');
    $pdf->loadView('reports.pdf-template', compact('data'));
    
    return $pdf->download('report-' . date('Y-m-d') . '.pdf');
}
```

في Controller:

```php
if ($request->filled('export')) {
    if ($request->export === 'csv') {
        return $this->exportCSV($products);
    } elseif ($request->export === 'pdf') {
        return $this->exportPDF($products);
    }
}
```

---

## Best Practices

### ✅ **افعل:**

1. **استخدم `applyFilters()` لجميع الفلاتر القياسية:**
   ```php
   $query->applyFilters($request->only(['date_from', 'date_to', 'branch_id', ...]));
   ```

2. **احفظ query parameters في pagination:**
   ```php
   $data->paginate(50)->appends($request->query());
   ```

3. **أضف Scopes مخصصة للمنطق الخاص:**
   ```php
   public function scopeLowStock($query) {
       return $query->whereRaw('current_qty < min_qty');
   }
   ```

4. **استخدم `showExport` فقط على التقارير الكبيرة:**
   ```blade
   'showExport' => true, // فقط للتقارير
   ```

### ❌ **لا تفعل:**

1. **لا تكرر Scopes موجودة في Trait:**
   ```php
   // ❌ خطأ
   public function scopeFilterByBranch($query, $branchId) { ... }
   ```

2. **لا تنسى `appends()` في pagination:**
   ```php
   // ❌ سيفقد الفلاتر عند الانتقال للصفحة التالية
   $data->paginate(50);
   
   // ✅ صحيح
   $data->paginate(50)->appends($request->query());
   ```

3. **لا تحمّل جميع البيانات بدون pagination:**
   ```php
   // ❌ بطيء جداً
   $allProducts = Product::all();
   
   // ✅ استخدم pagination
   $products = Product::paginate(50);
   ```

---

## ملفات المشروع

### Files Created:
- `app/Models/Traits/Filterable.php` - Trait للـ Scopes
- `app/Http/Middleware/PersistFilters.php` - Middleware للـ Session
- `app/Http/Controllers/InventoryReportController.php` - مثال Controller
- `app/Models/InventoryMovement.php` - مثال Model
- `resources/views/components/filters/advanced-filter.blade.php` - مكوّن الفلاتر
- `resources/views/reports/inventory/movements.blade.php` - مثال View

### Routes:
```php
Route::middleware(['auth', 'persist.filters'])->prefix('reports')->group(function () {
    Route::get('/inventory/movements', [InventoryReportController::class, 'movements'])->name('reports.inventory.movements');
    Route::get('/inventory/current-stock', [InventoryReportController::class, 'currentStock'])->name('reports.inventory.current-stock');
    Route::get('/inventory/most-active', [InventoryReportController::class, 'mostActive'])->name('reports.inventory.most-active');
    Route::get('/inventory/product/{product}/history', [InventoryReportController::class, 'productMovementHistory'])->name('reports.inventory.product-history');
});
```

---

## المراجع

- TASK-028 في BACKLOG.md
- Bootstrap 5.3 RTL Documentation
- Laravel Query Scopes: https://laravel.com/docs/eloquent#query-scopes

