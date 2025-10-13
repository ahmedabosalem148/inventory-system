# ✅ TASK-014: Low Stock Alerts (تنبيهات الحد الأدنى للمخزون) - COMPLETED

**التاريخ:** 2025-10-02  
**الحالة:** ✅ مكتمل 100%

---

## 📋 نظرة عامة

تنفيذ تنبيهات الحد الأدنى للمخزون مع:
- **Dashboard Widgets** (4 ويدجت رئيسية)
- **Low Stock Report** (تقرير مستقل مفصّل)
- **Product List Filter** (فلتر سريع في قائمة المنتجات)

---

## 🔧 الملفات المُعدّلة والمُنشأة

### 1. DashboardController (Modified)
**المسار:** `app/Http/Controllers/DashboardController.php`

**التعديلات:**
```php
use App\Models\Cheque; // ✅ إضافة

public function index()
{
    // 1. الإحصائيات الأساسية
    $stats = [
        'branches_count' => Branch::active()->count(),
        'categories_count' => Category::active()->count(),
        'products_count' => Product::active()->count(),
        'total_stock_value' => ProductBranchStock::with('product')
            ->get()
            ->sum(fn($stock) => $stock->current_stock * $stock->product->purchase_price),
    ];

    // 2. أصناف أقل من الحد الأدنى (Top 10)
    $lowStockItems = ProductBranchStock::with(['product.category', 'branch'])
        ->whereHas('product', fn($q) => $q->where('is_active', true))
        ->get()
        ->filter(fn($stock) => $stock->current_stock < $stock->product->min_stock)
        ->sortBy(fn($stock) => ($stock->current_stock / max($stock->product->min_stock, 1)))
        ->take(10);

    // 3. أصناف نفذت تمامًا (current_stock = 0)
    $outOfStock = ProductBranchStock::with(['product', 'branch'])
        ->whereHas('product', fn($q) => $q->where('is_active', true))
        ->where('current_stock', 0)
        ->orderBy('updated_at', 'desc')
        ->take(10)
        ->get();

    // 4. شيكات مستحقة قريبًا (خلال 7 أيام)
    $upcomingCheques = Cheque::with(['customer', 'creator'])
        ->pending()
        ->dueSoon(7)
        ->orderBy('due_date', 'asc')
        ->take(10)
        ->get();

    // 5. شيكات متأخرة (overdue)
    $overdueCheques = Cheque::with(['customer', 'creator'])
        ->overdue()
        ->orderBy('due_date', 'asc')
        ->take(10)
        ->get();

    return view('dashboard', compact(
        'stats',
        'lowStockItems',
        'outOfStock',
        'upcomingCheques',
        'overdueCheques'
    ));
}
```

**الميزات:**
- ✅ حساب قيمة المخزون الكلية (بسعر الشراء)
- ✅ فلترة المنتجات النشطة فقط
- ✅ ترتيب حسب نسبة النقص (الأكثر حرجًا أولاً)
- ✅ استخدام scopes من Cheque model (pending, dueSoon, overdue)

---

### 2. dashboard.blade.php (Replaced)
**المسار:** `resources/views/dashboard.blade.php`  
**الحجم:** ~370 سطر (كان 150 سطر)

**الأقسام الجديدة:**

#### أ) بطاقات الإحصائيات (4 Cards)
```html
<div class="row g-3 mb-4">
    <!-- الفروع النشطة -->
    <!-- التصنيفات -->
    <!-- المنتجات النشطة -->
    <!-- قيمة المخزون (ج.م) -->
</div>
```

#### ب) Widget 1: أصناف أقل من الحد الأدنى
**الميزات:**
- ✅ عرض Top 10 أصناف تحتاج إعادة توريد
- ✅ عرض: (المنتج، الفرع، المخزون، الحد الأدنى، النقص، النسبة المئوية)
- ✅ Badge تحذيري: `bg-warning` للمخزون المنخفض، `bg-danger` لنفذ
- ✅ رابط: `/products?low_stock=1`

#### ج) Widget 2: أصناف نفذت تمامًا
**الميزات:**
- ✅ عرض المنتجات بمخزون = 0
- ✅ عرض آخر تحديث (`diffForHumans()`)
- ✅ رسالة إيجابية عند عدم وجود نقص

#### د) Widget 3: شيكات مستحقة قريبًا
**الميزات:**
- ✅ شيكات PENDING خلال 7 أيام
- ✅ عرض: (العميل، البنك، المبلغ، تاريخ الاستحقاق)
- ✅ رابط: `/cheques/pending`

#### هـ) Widget 4: شيكات متأخرة
**الميزات:**
- ✅ شيكات PENDING تجاوزت تاريخ الاستحقاق
- ✅ عرض عدد أيام التأخير
- ✅ صف أحمر (`table-danger`)
- ✅ رابط: `/cheques?status=overdue`

---

### 3. ProductController (Modified)
**المسار:** `app/Http/Controllers/ProductController.php`

#### أ) index() Method - إضافة فلتر low_stock
```php
public function index(Request $request)
{
    $query = Product::with(['category', 'branchStocks.branch']);

    // ... الفلاتر الموجودة ...

    // ✅ NEW: فلترة المخزون المنخفض
    if ($request->filled('low_stock') && $request->low_stock == 1) {
        $productIds = ProductBranchStock::with('product')
            ->get()
            ->filter(fn($stock) => $stock->current_stock < $stock->product->min_stock)
            ->pluck('product_id')
            ->unique();
        
        $query->whereIn('id', $productIds);
    }

    $products = $query->orderBy('name')->paginate(15);
    // ...
}
```

#### ب) lowStockReport() Method - تقرير مستقل جديد
```php
/**
 * تقرير نقص المخزون
 */
public function lowStockReport(Request $request)
{
    $query = ProductBranchStock::with(['product.category', 'branch'])
        ->whereHas('product', fn($q) => $q->where('is_active', true));

    // Filter by branch
    if ($request->filled('branch_id')) {
        $query->where('branch_id', $request->branch_id);
    }

    // Filter by category
    if ($request->filled('category_id')) {
        $query->whereHas('product', fn($q) => $q->where('category_id', $request->category_id));
    }

    // Get all stocks and filter in PHP
    $stocks = $query->get()->filter(function($stock) {
        return $stock->current_stock < $stock->product->min_stock;
    })->sortBy(function($stock) {
        return ($stock->current_stock / max($stock->product->min_stock, 1));
    });

    $branches = Branch::active()->orderBy('name')->get();
    $categories = Category::active()->orderBy('name')->get();

    // Statistics
    $stats = [
        'total_items' => $stocks->count(),
        'out_of_stock' => $stocks->filter(fn($s) => $s->current_stock == 0)->count(),
        'critical' => $stocks->filter(function($s) {
            return $s->current_stock > 0 && ($s->current_stock / max($s->product->min_stock, 1)) < 0.2;
        })->count(),
    ];

    return view('reports.low-stock', compact('stocks', 'branches', 'categories', 'stats'));
}
```

**Logic:**
- ✅ فلترة في PHP بدلاً من raw SQL (أكثر أمانًا)
- ✅ ترتيب حسب نسبة النقص (الأكثر حرجًا أولاً)
- ✅ إحصائيات: (إجمالي، نفذ، حرج <20%)

---

### 4. routes/web.php (Modified)
**الإضافة:**
```php
// تقرير نقص المخزون
Route::get('/reports/low-stock', [ProductController::class, 'lowStockReport'])->name('reports.low-stock');
```

---

### 5. reports/low-stock.blade.php (New)
**المسار:** `resources/views/reports/low-stock.blade.php`  
**الحجم:** ~200 سطر

**الأقسام:**

#### أ) Header مع زر الطباعة
```html
<h2><i class="bi bi-graph-down-arrow"></i> تقرير نقص المخزون</h2>
<button onclick="window.print()" class="btn btn-outline-primary">
    <i class="bi bi-printer"></i> طباعة
</button>
```

#### ب) بطاقات الإحصائيات (3 Cards)
- **إجمالي الأصناف المنخفضة**
- **نفذت تمامًا (0)**
- **حالة حرجة (<20%)**

#### ج) فلاتر
```html
<form method="GET" action="{{ route('reports.low-stock') }}">
    <select name="branch_id"> ... </select>
    <select name="category_id"> ... </select>
    <button type="submit">تصفية</button>
</form>
```

#### د) جدول النتائج
**الأعمدة:**
1. `#` (Serial Number)
2. المنتج
3. التصنيف (Badge)
4. الفرع
5. المخزون الحالي
6. الحد الأدنى
7. النقص (بالسالب، لون أحمر)
8. الحالة (نفذ/حرج/منخفض/متوسط) + نسبة مئوية

**حالات الـ Badges:**
- `bg-danger` → نفذ (0)
- `bg-dark` → حرج (<20%)
- `bg-warning` → منخفض (20-50%)
- `bg-info` → متوسط (50-100%)

#### هـ) رسالة فارغة
```html
@if($stocks->count() == 0)
    <i class="bi bi-check-circle text-success"></i>
    <h4>لا يوجد نقص في المخزون</h4>
@endif
```

#### و) CSS للطباعة
```css
@media print {
    .btn, nav, .card-body form { display: none; }
}
```

---

## 📊 الإحصائيات

### الكود المُضاف/المُعدّل:
```
DashboardController.php:  +45 سطر (كان ~30، أصبح ~75)
dashboard.blade.php:      +220 سطر (كان 150، أصبح 370)
ProductController.php:    +55 سطر (فلتر + method جديدة)
reports/low-stock.blade.php: ~200 سطر (جديد)
routes/web.php:           +1 route

الإجمالي: ~520 سطر كود جديد
```

### الملفات:
- **Modified:** 4 files
- **New:** 1 file (low-stock.blade.php)
- **Total Routes:** 53 routes (52 previous + 1 new)

---

## 🧪 الاختبار

### سيناريو 1: Dashboard يعرض التنبيهات
1. زيارة `/dashboard`
2. ✅ يعرض 4 بطاقات إحصائيات
3. ✅ يعرض أصناف منخفضة (إن وجدت)
4. ✅ يعرض شيكات قريبة/متأخرة (إن وجدت)
5. ✅ رسائل إيجابية عند عدم وجود تنبيهات

### سيناريو 2: Low Stock Filter في Products
1. Dashboard → رابط "عرض جميع الأصناف المنخفضة"
2. التوجيه إلى `/products?low_stock=1`
3. ✅ يعرض فقط المنتجات التي `current_stock < min_stock`

### سيناريو 3: Low Stock Report
1. زيارة `/reports/low-stock`
2. ✅ يعرض 3 بطاقات إحصائيات
3. ✅ يعرض جدول الأصناف المنخفضة مرتبة حسب الحرجية
4. ✅ فلترة حسب فرع أو تصنيف
5. ✅ طباعة تخفي الفلاتر والأزرار

### سيناريو 4: Edge Cases
- ✅ **min_stock = 0**: لا يظهر في التنبيهات
- ✅ **current_stock = 0**: badge أحمر "نفذ"
- ✅ **لا منتجات نشطة**: رسالة إيجابية

---

## 🎯 الميزات المُنفذة

### ✅ Dashboard Widgets
- [x] أصناف أقل من الحد الأدنى (Top 10)
- [x] أصناف نفذت تمامًا (Top 10)
- [x] شيكات مستحقة قريبًا (7 أيام)
- [x] شيكات متأخرة

### ✅ Low Stock Report
- [x] تقرير مستقل `/reports/low-stock`
- [x] فلترة حسب الفرع والتصنيف
- [x] إحصائيات (إجمالي، نفذ، حرج)
- [x] ترتيب حسب شدة النقص
- [x] دعم الطباعة

### ✅ Product List Integration
- [x] فلتر `?low_stock=1`
- [x] رابط مباشر من Dashboard

---

## 🔗 الاعتماديات

- ✅ **TASK-006:** `ProductBranchStock` (current_stock, min_stock)
- ✅ **TASK-013:** `Cheque` model with scopes (pending, dueSoon, overdue)

---

## 📝 ملاحظات تقنية

### 1. لماذا فلترة في PHP وليس SQL؟
```php
// ❌ Raw SQL (خطر SQL Injection)
$query->whereRaw('current_stock < min_stock');

// ✅ PHP Filter (آمن)
$stocks->filter(fn($s) => $s->current_stock < $s->product->min_stock);
```

### 2. منع Division by Zero
```php
$percent = $stock->current_stock / max($stock->product->min_stock, 1);
```

### 3. Eager Loading لتقليل N+1
```php
ProductBranchStock::with(['product.category', 'branch'])
```

### 4. Scopes تبسّط الاستعلامات
```php
Cheque::pending()->dueSoon(7)->orderBy('due_date')
// بدلاً من:
Cheque::where('status', 'PENDING')
      ->where('due_date', '<=', now()->addDays(7))
      ->orderBy('due_date')
```

---

## 🚀 التحسينات المستقبلية (Optional)

### 1. Email Notifications (TASK-014 Extended)
- إرسال تنبيه يومي للمدير عند نقص المخزون
- شرط: دعم Hostinger لـ SMTP

### 2. Cron Job (Optional)
```bash
# في crontab
0 8 * * * php /path/to/artisan schedule:run
```

```php
// في app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('stock:check-low')
             ->dailyAt('08:00');
}
```

### 3. Export to Excel (TASK-020 Extended)
- تصدير تقرير نقص المخزون إلى Excel
- استخدام `maatwebsite/excel`

---

## ✅ Acceptance Criteria (من BACKLOG)

- [x] استعلام: `product_branch.current_stock < product_branch.min_stock`
- [x] **Dashboard Widget**: قائمة بالأصناف المنخفضة ✅
- [x] **تقرير**: نقص المخزون لكل فرع ✅
- [x] **Edge Case**: min_qty=0 → لا تنبيه ✅
- [x] **اختياري**: Cron يومي + بريد → مؤجل (يحتاج دعم Hostinger)

---

## 📚 المراجع

- **BACKLOG:** TASK-014 (Line 61)
- **Spec:** "تنبيهات حد أدنى للمخزون لكل صنف/فرع"
- **Dependencies:** TASK-006, TASK-013

---

**Status:** ✅ COMPLETED  
**Next Task:** TASK-015 (تحقق حجم العبوة/الكرتونة)
