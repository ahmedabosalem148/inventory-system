# دليل البحث الفوري (Quick Search Guide)

## نظرة عامة

تم تطبيق نظام بحث فوري متقدم مع اقتراحات تلقائية (Autocomplete) يدعم:
- البحث في المنتجات (SKU، الاسم، الماركة)
- البحث في العملاء (الكود، الاسم، رقم الهاتف)
- البحث في المخزون حسب الفرع
- عرض معلومات إضافية (الرصيد، الكمية المتوفرة)
- أداء عالي مع Indexes محسّنة

---

## المكونات الرئيسية

### 1. **QuickSearchController** (API)

يوفر 4 endpoints للبحث:

```php
GET /api/search/products?q=search_term&limit=10
GET /api/search/customers?q=search_term&limit=10
GET /api/search/stock?q=search_term&branch_id=1&limit=10
GET /api/search/global?q=search_term
```

#### Response Format:

**Products:**
```json
[
  {
    "id": 1,
    "sku": "LED-001",
    "name": "لمبة ليد 12 واط",
    "brand": "Philips",
    "category": "لمبات",
    "pack_size": 12,
    "unit": "pcs",
    "label": "LED-001 - لمبة ليد 12 واط"
  }
]
```

**Customers:**
```json
[
  {
    "id": 1,
    "code": "C001",
    "name": "محمد أحمد",
    "phone": "0123456789",
    "balance": "1,250.00",
    "label": "C001 - محمد أحمد"
  }
]
```

**Stock (بالكمية المتوفرة):**
```json
[
  {
    "id": 1,
    "sku": "LED-001",
    "name": "لمبة ليد",
    "current_qty": 50,
    "min_qty": 10,
    "is_low_stock": false,
    "label": "LED-001 - لمبة ليد (متوفر: 50)"
  }
]
```

---

### 2. **QuickSearch JavaScript Class**

مكوّن JavaScript قابل لإعادة الاستخدام.

#### التهيئة التلقائية:

```html
<input type="text" 
       class="form-control" 
       data-autocomplete="products"
       data-autocomplete-url="/api/search/products"
       data-target-field="product_id"
       placeholder="ابحث عن منتج...">

<input type="hidden" name="product_id">
```

#### التهيئة اليدوية:

```javascript
const searchInput = document.querySelector('#productSearch');
const quickSearch = new QuickSearch(searchInput, {
    url: '/api/search/products',
    minChars: 2,
    delay: 300,
    limit: 10,
    onSelect: (item) => {
        console.log('Selected:', item);
        // منطق إضافي عند الاختيار
    }
});
```

#### الخيارات المتاحة:

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `url` | string | - | API endpoint |
| `minChars` | number | `2` | الحد الأدنى للحروف قبل البحث |
| `delay` | number | `300` | التأخير بالميلي ثانية (debounce) |
| `limit` | number | `10` | عدد النتائج المعروضة |
| `targetField` | string | `null` | اسم الحقل المخفي لحفظ الـ ID |
| `onSelect` | function | `null` | Callback عند اختيار عنصر |

---

### 3. **CSS Styling**

تم توفير CSS جاهز في `public/css/quick-search.css`:

```html
<link rel="stylesheet" href="{{ asset('css/quick-search.css') }}">
```

الميزات:
- تصميم Bootstrap متوافق
- دعم RTL كامل
- Responsive للموبايل
- Loading states
- Highlight للنص المطابق

---

## أمثلة الاستخدام

### مثال 1: البحث عن منتج في فورم صرف

```blade
{{-- في View --}}
<div class="autocomplete-wrapper">
    <input type="text" 
           class="form-control product-search" 
           placeholder="ابحث بالكود أو الاسم..."
           data-autocomplete="products"
           data-autocomplete-url="/api/search/stock"
           data-target-field="items[0][product_id]">
    
    <input type="hidden" name="items[0][product_id]">
</div>

{{-- في Scripts --}}
<script src="{{ asset('js/quick-search.js') }}"></script>
<script>
// عند اختيار منتج، ملء السعر تلقائياً
document.addEventListener('autocomplete:select', (e) => {
    if (e.target.classList.contains('product-search')) {
        const product = e.detail;
        const row = e.target.closest('tr');
        
        // ملء السعر
        row.querySelector('.item-price').value = product.unit_price || 0;
        
        // تنبيه للمخزون المنخفض
        if (product.is_low_stock) {
            alert(`تنبيه: الكمية المتوفرة ${product.current_qty} فقط!`);
        }
    }
});
</script>
```

---

### مثال 2: البحث عن عميل

```blade
<div class="col-md-6">
    <label class="form-label">العميل</label>
    <div class="autocomplete-wrapper">
        <input type="text" 
               class="form-control" 
               placeholder="ابحث بالكود أو الاسم أو الهاتف..."
               data-autocomplete="customers"
               data-autocomplete-url="/api/search/customers"
               data-target-field="customer_id">
        
        <input type="hidden" name="customer_id">
    </div>
</div>

<script>
// عرض رصيد العميل عند الاختيار
document.addEventListener('autocomplete:select', (e) => {
    const customer = e.detail;
    
    if (customer.balance) {
        document.getElementById('customerBalance').textContent = customer.balance;
    }
});
</script>
```

---

### مثال 3: البحث حسب الفرع (Stock-aware)

```blade
{{-- اختيار الفرع أولاً --}}
<select name="branch_id" class="form-select" id="branchSelect">
    <option value="">اختر الفرع...</option>
    @foreach($branches as $branch)
        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
    @endforeach
</select>

{{-- البحث في المخزون --}}
<div class="autocomplete-wrapper">
    <input type="text" 
           class="form-control" 
           placeholder="ابحث عن منتج..."
           data-autocomplete="products"
           data-autocomplete-url="/api/search/stock"
           data-target-field="product_id">
    
    <input type="hidden" name="product_id">
</div>

<script>
// سيأخذ branch_id تلقائياً من select#branchSelect
// ويعرض الكمية المتوفرة في هذا الفرع فقط
</script>
```

---

### مثال 4: البحث العام (Global Search)

```blade
{{-- في Navbar --}}
<div class="autocomplete-wrapper">
    <input type="text" 
           class="form-control" 
           placeholder="بحث سريع..."
           data-autocomplete="global"
           data-autocomplete-url="/api/search/global">
</div>

<script>
// سيبحث في Products و Customers
// وعند الاختيار، ينتقل للصفحة مباشرة
document.addEventListener('autocomplete:select', (e) => {
    const item = e.detail;
    if (item.url) {
        window.location.href = item.url;
    }
});
</script>
```

---

### مثال 5: Custom Callback

```javascript
const productSearch = new QuickSearch(document.querySelector('#productInput'), {
    url: '/api/search/products',
    onSelect: (product) => {
        // منطق مخصص
        console.log('Selected product:', product);
        
        // ملء حقول إضافية
        document.querySelector('#productName').value = product.name;
        document.querySelector('#productBrand').value = product.brand;
        document.querySelector('#packSize').value = product.pack_size;
        
        // حساب السعر
        calculatePrice(product);
    }
});
```

---

## Events (الأحداث)

يمكن الاستماع للأحداث التالية:

```javascript
// عند اختيار عنصر
document.addEventListener('autocomplete:select', (e) => {
    console.log('Selected:', e.detail);
});

// عند بدء البحث
document.addEventListener('autocomplete:search', (e) => {
    console.log('Searching for:', e.detail.query);
});

// عند عرض النتائج
document.addEventListener('autocomplete:results', (e) => {
    console.log('Results:', e.detail.results);
});
```

---

## تحسين الأداء

### 1. **Database Indexes**

تم إضافة indexes محسّنة:

```sql
-- Products
INDEX idx_products_sku (sku)
INDEX idx_products_name (name)
INDEX idx_products_sku_name (sku, name)
INDEX idx_products_active_name (is_active, name)

-- Customers
INDEX idx_customers_code (code)
INDEX idx_customers_name (name)
INDEX idx_customers_code_name (code, name)
INDEX idx_customers_active_name (is_active, name)

-- Product-Branch
INDEX idx_pb_branch_product (branch_id, product_id)
INDEX idx_pb_product_qty (product_id, current_qty)
```

تطبيق الـ Migration:

```bash
php artisan migrate
```

---

### 2. **Query Optimization**

في Controller:

```php
// ✅ Good: استخدام LIKE مع index
Product::where('sku', 'LIKE', "{$search}%") // بداية النص
    ->orWhere('name', 'LIKE', "%{$search}%")
    ->limit(10)
    ->get();

// ❌ Bad: LIKE في البداية والنهاية يبطئ
Product::where('sku', 'LIKE', "%{$search}%") // بطيء
```

---

### 3. **Debouncing**

تأخير البحث لتقليل الطلبات:

```javascript
new QuickSearch(input, {
    delay: 300 // 300ms بعد آخر ضغطة
});
```

---

### 4. **Caching (اختياري)**

في Controller:

```php
use Illuminate\Support\Facades\Cache;

public function products(Request $request)
{
    $search = $request->get('q');
    $cacheKey = "search:products:{$search}";
    
    return Cache::remember($cacheKey, 60, function () use ($search) {
        return Product::where('name', 'LIKE', "%{$search}%")
            ->limit(10)
            ->get();
    });
}
```

---

## Keyboard Navigation

- **Arrow Down**: الانتقال للعنصر التالي
- **Arrow Up**: الانتقال للعنصر السابق
- **Enter**: اختيار العنصر الحالي
- **Escape**: إخفاء النتائج

---

## Responsive Design

يتكيف تلقائياً مع الشاشات الصغيرة:

```css
@media (max-width: 768px) {
    .autocomplete-results {
        max-height: 200px; /* أقصر على الموبايل */
    }
    
    .autocomplete-item {
        padding: 0.5rem; /* مسافات أصغر */
        font-size: 0.875rem;
    }
}
```

---

## Best Practices

### ✅ **افعل:**

1. **استخدم data attributes للتهيئة السريعة:**
   ```html
   <input data-autocomplete="products" data-autocomplete-url="/api/search/products">
   ```

2. **أضف placeholder واضح:**
   ```html
   <input placeholder="ابحث بالكود أو الاسم...">
   ```

3. **استخدم hidden field للـ ID:**
   ```html
   <input type="hidden" name="product_id">
   ```

4. **أضف validation للحقل المخفي:**
   ```javascript
   form.addEventListener('submit', (e) => {
       if (!document.querySelector('[name="product_id"]').value) {
           alert('يرجى اختيار منتج من القائمة');
           e.preventDefault();
       }
   });
   ```

5. **استخدم events للمنطق الإضافي:**
   ```javascript
   document.addEventListener('autocomplete:select', handleProductSelect);
   ```

---

### ❌ **لا تفعل:**

1. **لا تبحث بحرف واحد:**
   ```javascript
   // ❌ Bad
   minChars: 1 // سيؤدي لطلبات كثيرة جداً
   
   // ✅ Good
   minChars: 2 // أو 3
   ```

2. **لا تعرض آلاف النتائج:**
   ```javascript
   // ❌ Bad
   limit: 1000
   
   // ✅ Good
   limit: 10 // أو 20 كحد أقصى
   ```

3. **لا تنسى تحميل CSS و JS:**
   ```blade
   @push('styles')
   <link rel="stylesheet" href="{{ asset('css/quick-search.css') }}">
   @endpush
   
   @push('scripts')
   <script src="{{ asset('js/quick-search.js') }}"></script>
   @endpush
   ```

4. **لا تستخدم autocomplete للبيانات الحساسة:**
   ```html
   <!-- ❌ Bad: كلمات المرور -->
   <input type="password" data-autocomplete="passwords">
   ```

---

## Troubleshooting

### المشكلة: النتائج لا تظهر

**الحل:**
1. تأكد من تحميل `quick-search.js` و `quick-search.css`
2. افتح Console للأخطاء
3. تأكد من صحة API endpoint:
   ```javascript
   fetch('/api/search/products?q=test')
       .then(r => r.json())
       .then(console.log);
   ```

---

### المشكلة: البحث بطيء

**الحل:**
1. تأكد من تطبيق Indexes:
   ```bash
   php artisan migrate
   ```

2. قلل عدد النتائج:
   ```javascript
   limit: 5 // بدلاً من 10
   ```

3. زد delay:
   ```javascript
   delay: 500 // بدلاً من 300
   ```

---

### المشكلة: العربية لا تظهر بشكل صحيح

**الحل:**
1. تأكد من UTF-8 في Controller:
   ```php
   return response()->json($results, 200, [
       'Content-Type' => 'application/json; charset=UTF-8'
   ]);
   ```

2. تأكد من RTL في CSS:
   ```css
   [dir="rtl"] .autocomplete-results {
       text-align: right;
   }
   ```

---

## ملفات المشروع

### Files Created:
- `app/Http/Controllers/Api/QuickSearchController.php` - API Controller
- `public/js/quick-search.js` - JavaScript Component
- `public/css/quick-search.css` - Styling
- `routes/api_search.php` - API Routes
- `database/migrations/2025_10_03_120000_add_search_indexes.php` - Indexes
- `resources/views/issue-vouchers/create-example.blade.php` - مثال تطبيق

### Routes:
```php
GET /api/search/products
GET /api/search/customers
GET /api/search/stock
GET /api/search/global
```

---

## المراجع

- TASK-029 في BACKLOG.md
- Bootstrap 5.3 RTL: https://getbootstrap.com
- Fetch API: https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API

