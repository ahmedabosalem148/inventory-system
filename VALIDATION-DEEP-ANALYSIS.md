# تحليل عميق لنظام الـ Validation - Inventory System

## ✅ **Validation الموجود حالياً**

### 1️⃣ **Issue Vouchers (أذون الصرف)**
✅ موجود:
- `customer_id`: nullable|exists:customers
- `customer_name`: required_without:customer_id
- `branch_id`: required|exists:branches
- `issue_date`: required|date
- `discount_type`: nullable|in:none,fixed,percentage
- `discount_value`: nullable|numeric|min:0
- `items.*.product_id`: required|exists:products
- `items.*.quantity`: required|numeric|min:0.01
- `items.*.unit_price`: required|numeric|min:0
- `items.*.discount_type`: nullable|in:none,fixed,percentage
- `items.*.discount_value`: nullable|numeric|min:0

❌ **ناقص**:
- ✖️ **التحقق من كسر العبوة (pack_size)** - المواصفات تطلب تنبيه عند كسر العبوة
- ✖️ **التحقق من الرصيد السالب قبل الحفظ** - يتم التحقق في Service لكن لا يوجد validation قبلي
- ✖️ **max value للخصم** - لا يوجد تحقق من أن الخصم لا يتجاوز قيمة البند/الفاتورة
- ✖️ **التحقق من payment_type** (CASH/CREDIT) - غير موجود في validation
- ✖️ **issue_type validation** (SALE/TRANSFER) - غير موجود
- ✖️ **target_branch_id** للتحويلات - غير موجود
- ✖️ **التحقق من أن المصدر ≠ المستلم** في التحويلات

### 2️⃣ **Return Vouchers (أذون المرتجعات)**
✅ موجود:
- `customer_id`: nullable|exists:customers
- `customer_name`: required_without:customer_id
- `branch_id`: required|exists:branches
- `return_date`: required|date
- `items.*.product_id`: required|exists:products
- `items.*.quantity`: required|numeric|min:0.01
- `items.*.unit_price`: required|numeric|min:0

❌ **ناقص**:
- ✖️ **reason (سبب الارتجاع)** - المواصفات تطلبه لكن غير موجود في validation
- ✖️ **discount support** - المواصفات تسمح بخصومات في المرتجعات لكن غير موجودة
- ✖️ **التحقق من رقم الترقيم 100001-125000** - يتم في Sequencer لكن لا يوجد validation
- ✖️ **pack_size check** - نفس مشكلة أذون الصرف

### 3️⃣ **Purchase Orders (أوامر الشراء)**
✅ موجود:
- `supplier_id`: required|exists:suppliers
- `branch_id`: required|exists:branches
- `order_date`: required|date
- `expected_delivery_date`: nullable|date|after:order_date ✅
- `discount_type`: nullable|in:NONE,PERCENTAGE,FIXED
- `discount_value`: nullable|numeric|min:0
- `tax_percentage`: nullable|numeric|min:0|max:100 ✅
- `shipping_cost`: nullable|numeric|min:0
- `items.*.quantity_ordered`: required|integer|min:1
- `items.*.unit_price`: required|numeric|min:0

❌ **ناقص**:
- ✖️ **max value للخصومات** - لا تحقق من أن الخصم لا يتجاوز القيمة
- ✖️ **quantity_received validation** - عند الاستلام
- ✖️ **status transitions** - لا يوجد validation لتغيير الحالة

### 4️⃣ **Products (المنتجات)**
✅ موجود:
- `category_id`: required|exists:categories
- `name`: required|unique|max:200
- `brand`: nullable|max:100
- `unit`: required|max:50
- `pack_size`: nullable|integer|min:1 ✅
- `purchase_price`: required|numeric|min:0
- `sale_price`: required|numeric|min:0|gte:purchase_price ✅ **ممتاز!**
- `min_stock`: required|integer|min:0
- `reorder_level`: nullable|integer|min:0

❌ **ناقص**:
- ✖️ **SKU validation** - المواصفات تذكر SKU/كود لكن لا يوجد في validation
- ✖️ **is_active validation** - موجود في Model لكن غير مطلوب في validation
- ✖️ **brand required** - المواصفات تذكر الماركة كجزء أساسي لكن nullable
- ✖️ **min_qty_default** - المواصفات تذكره لكن يستخدم min_stock بدلاً منه

### 5️⃣ **Customers (العملاء)**
✅ موجود:
- `name`: required|max:200
- `type`: nullable|in:retail,wholesale
- `phone`: nullable|max:20
- `address`: nullable|max:500
- `tax_id`: nullable|max:50
- `credit_limit`: nullable|numeric|min:0
- `is_active`: boolean

❌ **ناقص**:
- ✖️ **code validation** - يتم توليده تلقائياً لكن لا يوجد unique constraint في validation
- ✖️ **phone format** - لا يوجد regex للتحقق من صيغة الهاتف المصري
- ✖️ **tax_id unique** - يجب أن يكون فريد
- ✖️ **last_activity_at** - غير موجود في validation عند التحديث

### 6️⃣ **Payments (المدفوعات)**
✅ موجود:
- `customer_id`: required|exists:customers
- `payment_date`: required|date
- `amount`: required|numeric|min:0.01 ✅
- `payment_method`: required|in:cash,cheque,bank_transfer
- `cheque_number`: required_if:payment_method,cheque ✅
- `cheque_date`: required_if:payment_method,cheque
- `cheque_due_date`: required_if:payment_method,cheque
- `bank_name`: required_if:payment_method,cheque

❌ **ناقص**:
- ✖️ **التحقق من رصيد العميل** - لا يوجد validation أن المبلغ لا يتجاوز رصيد العميل المدين
- ✖️ **التحقق من تاريخ الاستحقاق** - يجب أن يكون >= cheque_date
- ✖️ **cheque_number unique** - لا يوجد تحقق من تكرار رقم الشيك

### 7️⃣ **Cheques (الشيكات)**
✅ موجود:
- الشيكات تُنشأ تلقائياً من Payments

❌ **ناقص**:
- ✖️ **status transitions validation** - لا يوجد تحقق من تغيير الحالة (PENDING→CLEARED/RETURNED)
- ✖️ **cleared_at required_if status=cleared** - غير موجود
- ✖️ **due_date >= cheque_date** - غير موجود

### 8️⃣ **Reports (التقارير)**
✅ موجود (في InventoryReportRequest):
- `from_date`: nullable|date|before_or_equal:to_date
- `to_date`: nullable|date|after_or_equal:from_date
- `branch_id`: nullable|exists:branches
- `category_id`: nullable|exists:product_categories
- `threshold`: nullable|integer|min:0
- ✅ **date_range max 2 years** - validation مخصص ممتاز!

❌ **ناقص**:
- ✖️ **لا يتم استخدام InventoryReportRequest** - Controllers تستخدم inline validation
- ✖️ **product_id validation** في تقارير حركة الصنف
- ✖️ **customer_id validation** في تقارير العملاء

---

## 🔴 **Critical Missing Validations (حسب المواصفات)**

### **A. منع الرصيد السالب (CRITICAL)**
المواصفات: `prevent_negative_stock = true`

**الوضع الحالي:**
- ✅ يتم التحقق في `InventoryService::issueProduct()`
- ❌ **لكن لا يوجد validation قبل الحفظ في Controller**
- ❌ لا يوجد Custom Validation Rule

**المطلوب:**
```php
// إنشاء Custom Validation Rule
php artisan make:rule SufficientStock

// استخدامه في IssueVoucherController
'items.*.quantity' => [
    'required',
    'numeric',
    'min:0.01',
    new SufficientStock($request->branch_id)
]
```

### **B. التحقق من كسر العبوة (Should Have)**
المواصفات: `enforce_full_pack = false` (يسمح لكن مع تنبيه)

**الوضع الحالي:**
- ❌ **لا يوجد أي validation أو تنبيه**
- ✅ `pack_size` موجود في Model

**المطلوب:**
```php
// Custom Validation Rule مع Warning
'items.*.quantity' => [
    'required',
    'numeric',
    'min:0.01',
    new PackSizeCheck($productId, 'warning') // warning بدلاً من fail
]

// في Response يجب إرجاع warnings منفصلة عن errors
{
    "data": {...},
    "warnings": [
        "المنتج X: الكمية 15 لا تساوي مضاعف العبوة (10)"
    ]
}
```

### **C. التحقق من الخصومات (Must Have)**
المواصفات: الخصم لا يتجاوز قيمة البند/الفاتورة

**الوضع الحالي:**
- ❌ **لا يوجد max validation للخصم**

**المطلوب:**
```php
// في validation
'discount_value' => [
    'nullable',
    'numeric',
    'min:0',
    function ($attribute, $value, $fail) use ($request) {
        if ($request->discount_type === 'fixed') {
            $totalBefore = 0;
            foreach ($request->items as $item) {
                $totalBefore += $item['quantity'] * $item['unit_price'];
            }
            if ($value > $totalBefore) {
                $fail('الخصم لا يمكن أن يتجاوز إجمالي الفاتورة');
            }
        } elseif ($request->discount_type === 'percentage' && $value > 100) {
            $fail('نسبة الخصم لا يمكن أن تتجاوز 100%');
        }
    }
]
```

### **D. سبب الارتجاع (Must Have)**
المواصفات: مرتجعات تحتاج سبب

**الوضع الحالي:**
- ❌ **غير موجود في ReturnVoucherController**

**المطلوب:**
```php
// في ReturnVoucherController::store()
'reason' => 'required|string|max:500',
'reason_category' => 'nullable|in:damaged,defective,customer_request,wrong_item'
```

### **E. نطاق الترقيم للمرتجعات (Must Have)**
المواصفات: 100001 إلى 125000 بدون فجوات

**الوضع الحالي:**
- ✅ موجود في `SequencerService`
- ❌ **لا يوجد validation في Controller**

**المطلوب:**
```php
// في ReturnVoucherController قبل approve
if ($voucherNumber < 100001 || $voucherNumber > 125000) {
    throw new \Exception('رقم المرتجع خارج النطاق المسموح');
}
```

### **F. SKU للمنتجات (Must Have)**
المواصفات: كارت صنف يحتوي SKU/كود

**الوضع الحالي:**
- ❌ **لا يوجد sku في validation أو migration**
- ⚠️ يوجد في Model لكن غير مستخدم

**المطلوب:**
```php
// في ProductController::store()
'sku' => 'required|string|unique:products,sku|max:50',

// إضافة auto-generate إذا لم يُدخل
if (!$request->sku) {
    $validated['sku'] = 'PRD-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
}
```

### **G. التحويلات بين المخازن (Must Have)**
المواصفات: إذن واحد يخصم من المصدر ويضيف للمستلم

**الوضع الحالي:**
- ❌ **لا يوجد validation للتحويلات في IssueVoucher**

**المطلوب:**
```php
// في IssueVoucherController عند issue_type=TRANSFER
'target_branch_id' => 'required_if:issue_type,TRANSFER|exists:branches,id|different:branch_id',
'issue_type' => 'required|in:SALE,TRANSFER',
```

---

## 📊 **إحصائيات Validation**

### **حسب الأولوية:**

| الأولوية | العدد | الوصف |
|---------|-------|-------|
| 🔴 **CRITICAL** | 3 | منع الرصيد السالب، الخصومات، التحويلات |
| 🟠 **HIGH** | 4 | SKU، سبب الارتجاع، نطاق الترقيم، pack_size |
| 🟡 **MEDIUM** | 6 | phone format، tax_id unique، cheque validations |
| 🟢 **LOW** | 5 | warnings، reorder_level logic، reports |

### **حسب المكون:**

| المكون | Validations الموجودة | الناقصة | النسبة |
|-------|---------------------|---------|--------|
| Products | 11 | 4 | 73% ✅ |
| Issue Vouchers | 13 | 7 | 65% ⚠️ |
| Return Vouchers | 7 | 5 | 58% ⚠️ |
| Payments | 8 | 3 | 73% ✅ |
| Customers | 7 | 4 | 64% ⚠️ |
| Purchase Orders | 10 | 3 | 77% ✅ |
| **TOTAL** | **56** | **26** | **68%** ⚠️ |

---

## 🎯 **خطة العمل المقترحة**

### **Phase 1: Critical Validations (أسبوع 1)**
1. ✅ إنشاء `SufficientStockRule` custom validation
2. ✅ إضافة discount max validation
3. ✅ إضافة transfer validations (source ≠ target)
4. ✅ إضافة reason للمرتجعات

### **Phase 2: Data Integrity (أسبوع 2)**
5. ✅ إضافة SKU validation + auto-generation
6. ✅ إضافة unique constraints (tax_id, cheque_number)
7. ✅ إصلاح date validations (cheque_due_date >= cheque_date)
8. ✅ إضافة status transition validations

### **Phase 3: UX Enhancements (أسبوع 3)**
9. ✅ إضافة pack_size warnings
10. ✅ إنشاء Form Request classes لكل Controller
11. ✅ توحيد رسائل الأخطاء العربية
12. ✅ إضافة validation للـ Reports

### **Phase 4: Testing & Documentation (أسبوع 4)**
13. ✅ Feature tests لكل validation rule
14. ✅ توثيق في OpenAPI/Swagger
15. ✅ Performance testing للـ custom rules
16. ✅ User acceptance testing

---

## 📝 **أمثلة كود للتنفيذ**

### **1. Custom Rule: SufficientStock**
```php
<?php

namespace App\Rules;

use App\Models\ProductBranch;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SufficientStock implements ValidationRule
{
    public function __construct(
        private int $productId,
        private int $branchId
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $stock = ProductBranch::where('product_id', $this->productId)
            ->where('branch_id', $this->branchId)
            ->value('current_stock') ?? 0;

        if ($stock < $value) {
            $fail("الكمية المتاحة ($stock) أقل من المطلوبة ($value)");
        }
    }
}
```

### **2. Custom Rule: PackSizeWarning**
```php
<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PackSizeWarning implements ValidationRule
{
    public function __construct(
        private int $productId,
        private bool $enforceFull = false
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $product = Product::find($this->productId);
        
        if (!$product || !$product->pack_size) {
            return; // لا توجد عبوة محددة
        }

        $remainder = fmod($value, $product->pack_size);
        
        if ($remainder != 0) {
            if ($this->enforceFull) {
                $fail("الكمية يجب أن تكون مضاعف العبوة ({$product->pack_size})");
            }
            // في حالة warning فقط، نضيف warning للـ response بدون fail
            // يتم معالجته في Controller
        }
    }
}
```

### **3. Form Request: StoreIssueVoucherRequest**
```php
<?php

namespace App\Http\Requests;

use App\Rules\SufficientStock;
use Illuminate\Foundation\Http\FormRequest;

class StoreIssueVoucherRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required_without:customer_id|string|max:100',
            'branch_id' => 'required|exists:branches,id',
            'issue_type' => 'required|in:SALE,TRANSFER',
            'target_branch_id' => 'required_if:issue_type,TRANSFER|exists:branches,id|different:branch_id',
            'payment_type' => 'required|in:CASH,CREDIT',
            'issue_date' => 'required|date',
            
            'discount_type' => 'nullable|in:none,fixed,percentage',
            'discount_value' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->discount_type === 'percentage' && $value > 100) {
                        $fail('نسبة الخصم لا يمكن أن تتجاوز 100%');
                    }
                }
            ],
            
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $productId = $this->items[$index]['product_id'];
                    
                    $rule = new SufficientStock($productId, $this->branch_id);
                    $rule->validate($attribute, $value, $fail);
                }
            ],
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:none,fixed,percentage',
            'items.*.discount_value' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required_without' => 'يجب إدخال اسم العميل أو اختيار عميل موجود',
            'target_branch_id.required_if' => 'يجب تحديد الفرع المستلم عند التحويل',
            'target_branch_id.different' => 'الفرع المستلم يجب أن يكون مختلف عن الفرع المصدر',
            'items.required' => 'يجب إضافة منتج واحد على الأقل',
            'items.min' => 'يجب إضافة منتج واحد على الأقل',
        ];
    }
}
```

---

## 🚀 **التوصيات النهائية**

### **يجب تنفيذها فوراً (This Week):**
1. ✅ إضافة `SufficientStock` validation rule
2. ✅ إضافة `reason` للمرتجعات
3. ✅ إضافة discount max validation
4. ✅ إضافة transfer validations

### **مهم (Next 2 Weeks):**
5. ✅ إنشاء Form Request classes
6. ✅ إضافة SKU validation
7. ✅ إصلاح cheque validations
8. ✅ Pack size warnings

### **تحسينات (Future):**
9. ⚡ Custom validation messages في ملف منفصل
10. ⚡ Validation caching للـ performance
11. ⚡ Real-time validation في Frontend
12. ⚡ Swagger documentation للـ validation rules

---

## ✅ **ملخص النتائج**

**الإيجابيات:**
- ✅ 68% من الـ validations موجودة
- ✅ Date range validation ممتاز في Reports
- ✅ sale_price >= purchase_price موجود
- ✅ Conditional validation (required_if) مستخدم بشكل جيد

**السلبيات:**
- ❌ لا توجد Custom Validation Rules
- ❌ 32% من الـ validations ناقصة حسب المواصفات
- ❌ لا يوجد Form Request classes (inline validation فقط)
- ❌ بعض validations حرجة ناقصة (رصيد سالب، خصومات)

**التقييم العام: 7/10** ⭐⭐⭐⭐⭐⭐⭐☆☆☆

النظام جيد لكن يحتاج تحسينات حرجة قبل Production!

---

## 🆕 **متطلبات إضافية من العميل (2025-10-27)**

### **المشكلة الأولى: تصنيف الأجزاء داخل المصنع**

#### **الوصف:**
- الأجزاء التي تدخل المصنع حالياً تُعتبر **"منتج غير تام"**
- المطلوب: إضافة تصنيفات جديدة تشمل:
  - ✅ **أجزاء** (Parts)
  - ✅ **بلاستيك** (Plastic)
  - ✅ **ألومنيوم** (Aluminum)

#### **التحليل الفني:**

**الوضع الحالي:**
```php
// في Product Model
protected $fillable = [
    'category_id',  // يشير إلى categories table
    'name',
    'unit',  // حالياً: 'pcs', 'kg', 'meter' إلخ
    'pack_size',
    ...
];
```

**المشكلة:**
- ❌ لا يوجد حقل **`product_type`** أو **`classification`**
- ❌ الـ `categories` جدول عام ولا يميز بين (منتج تام / غير تام / أجزاء / خام)
- ❌ لا توجد validation لنوع المنتج

#### **الحل المقترح:**

##### **1. Database Schema Changes**
```sql
-- Migration: add_product_classification
ALTER TABLE products 
ADD COLUMN product_classification ENUM(
    'finished_product',      -- منتج تام (جاهز للبيع)
    'semi_finished',         -- منتج غير تام (تحت التصنيع)
    'raw_material',          -- مواد خام
    'parts',                 -- أجزاء
    'plastic_parts',         -- بلاستيك
    'aluminum_parts',        -- ألومنيوم
    'other'
) DEFAULT 'finished_product' AFTER category_id;

-- Index للأداء
CREATE INDEX idx_products_classification ON products(product_classification);
```

##### **2. Model Update**
```php
// app/Models/Product.php
class Product extends Model
{
    protected $fillable = [
        'category_id',
        'product_classification', // جديد
        'name',
        'brand',
        'unit',
        'pack_size',
        ...
    ];

    // Enum constants
    const CLASSIFICATION_FINISHED = 'finished_product';
    const CLASSIFICATION_SEMI_FINISHED = 'semi_finished';
    const CLASSIFICATION_RAW_MATERIAL = 'raw_material';
    const CLASSIFICATION_PARTS = 'parts';
    const CLASSIFICATION_PLASTIC = 'plastic_parts';
    const CLASSIFICATION_ALUMINUM = 'aluminum_parts';
    const CLASSIFICATION_OTHER = 'other';

    // Accessor
    public function getClassificationLabelAttribute(): string
    {
        return match($this->product_classification) {
            self::CLASSIFICATION_FINISHED => 'منتج تام',
            self::CLASSIFICATION_SEMI_FINISHED => 'منتج غير تام',
            self::CLASSIFICATION_RAW_MATERIAL => 'مواد خام',
            self::CLASSIFICATION_PARTS => 'أجزاء',
            self::CLASSIFICATION_PLASTIC => 'بلاستيك',
            self::CLASSIFICATION_ALUMINUM => 'ألومنيوم',
            default => 'أخرى'
        };
    }

    // Scope للفلترة
    public function scopeByClassification($query, string $classification)
    {
        return $query->where('product_classification', $classification);
    }

    public function scopeFactoryParts($query)
    {
        return $query->whereIn('product_classification', [
            self::CLASSIFICATION_PARTS,
            self::CLASSIFICATION_PLASTIC,
            self::CLASSIFICATION_ALUMINUM,
            self::CLASSIFICATION_SEMI_FINISHED
        ]);
    }
}
```

##### **3. Validation Rules**
```php
// app/Http/Requests/StoreProductRequest.php
public function rules(): array
{
    return [
        'category_id' => 'required|exists:categories,id',
        
        // جديد
        'product_classification' => [
            'required',
            'string',
            Rule::in([
                Product::CLASSIFICATION_FINISHED,
                Product::CLASSIFICATION_SEMI_FINISHED,
                Product::CLASSIFICATION_RAW_MATERIAL,
                Product::CLASSIFICATION_PARTS,
                Product::CLASSIFICATION_PLASTIC,
                Product::CLASSIFICATION_ALUMINUM,
                Product::CLASSIFICATION_OTHER,
            ])
        ],
        
        'name' => 'required|string|max:200|unique:products,name',
        'unit' => 'required|string|max:50',
        
        // Conditional validations حسب التصنيف
        'pack_size' => [
            'nullable',
            'integer',
            'min:1',
            // الأجزاء والبلاستيك عادة ما تُعبأ، المواد الخام قد لا
            Rule::requiredIf(function() {
                return in_array($this->product_classification, [
                    Product::CLASSIFICATION_PARTS,
                    Product::CLASSIFICATION_PLASTIC,
                    Product::CLASSIFICATION_ALUMINUM
                ]);
            })
        ],
        
        'purchase_price' => 'required|numeric|min:0',
        'sale_price' => [
            'required',
            'numeric',
            'min:0',
            // المنتجات التامة فقط يجب أن يكون سعر البيع >= سعر الشراء
            // الأجزاء والخام قد تُباع بالتكلفة أو أقل (حالات خاصة)
            Rule::when(
                $this->product_classification === Product::CLASSIFICATION_FINISHED,
                'gte:purchase_price'
            )
        ],
        
        'min_stock' => 'required|integer|min:0',
    ];
}

public function messages(): array
{
    return [
        'product_classification.required' => 'يجب اختيار تصنيف المنتج',
        'product_classification.in' => 'تصنيف المنتج غير صحيح',
        'pack_size.required' => 'حجم العبوة مطلوب للأجزاء والبلاستيك والألومنيوم',
    ];
}

// Custom validation لمنطق الأعمال
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        // إذا كان المنتج من نوع "أجزاء"، يجب أن تكون الوحدة مناسبة
        if ($this->product_classification === Product::CLASSIFICATION_PARTS) {
            $validUnits = ['pcs', 'piece', 'unit', 'قطعة'];
            if (!in_array(strtolower($this->unit), $validUnits)) {
                $validator->errors()->add(
                    'unit',
                    'وحدة القياس للأجزاء يجب أن تكون بالقطعة'
                );
            }
        }
        
        // البلاستيك والألومنيوم قد تكون بالوزن أو القطعة
        if (in_array($this->product_classification, [
            Product::CLASSIFICATION_PLASTIC,
            Product::CLASSIFICATION_ALUMINUM
        ])) {
            $validUnits = ['kg', 'gram', 'ton', 'pcs', 'piece', 'كجم', 'جرام', 'قطعة'];
            if (!in_array(strtolower($this->unit), $validUnits)) {
                $validator->errors()->add(
                    'unit',
                    'وحدة القياس غير مناسبة لهذا النوع من المواد'
                );
            }
        }
    });
}
```

##### **4. Controller Updates**
```php
// app/Http/Controllers/Api/V1/ProductController.php
public function store(StoreProductRequest $request)
{
    $validated = $request->validated();
    
    try {
        DB::beginTransaction();
        
        // Auto-generate SKU based on classification
        $prefix = match($validated['product_classification']) {
            Product::CLASSIFICATION_FINISHED => 'FIN',
            Product::CLASSIFICATION_SEMI_FINISHED => 'SEM',
            Product::CLASSIFICATION_PARTS => 'PRT',
            Product::CLASSIFICATION_PLASTIC => 'PLS',
            Product::CLASSIFICATION_ALUMINUM => 'ALU',
            Product::CLASSIFICATION_RAW_MATERIAL => 'RAW',
            default => 'OTH'
        };
        
        $lastProduct = Product::where('product_classification', $validated['product_classification'])
            ->latest('id')
            ->first();
        $nextNumber = $lastProduct ? ($lastProduct->id + 1) : 1;
        
        $validated['sku'] = $prefix . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        
        $product = Product::create($validated);
        
        DB::commit();
        
        return response()->json([
            'message' => 'تم إنشاء المنتج بنجاح',
            'data' => new ProductResource($product)
        ], 201);
        
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'خطأ في إنشاء المنتج',
            'error' => $e->getMessage()
        ], 500);
    }
}

// Filter by classification
public function index(Request $request)
{
    $query = Product::query();
    
    // فلتر حسب التصنيف
    if ($request->filled('classification')) {
        $query->byClassification($request->classification);
    }
    
    // فلتر حسب "أجزاء المصنع" فقط
    if ($request->boolean('factory_parts_only')) {
        $query->factoryParts();
    }
    
    $products = $query->with(['category', 'branchStocks'])
        ->paginate($request->per_page ?? 15);
    
    return ProductResource::collection($products);
}
```

##### **5. Frontend Updates**
```typescript
// frontend/src/types/product.ts
export type ProductClassification = 
    | 'finished_product'
    | 'semi_finished'
    | 'raw_material'
    | 'parts'
    | 'plastic_parts'
    | 'aluminum_parts'
    | 'other';

export interface Product {
    id: number;
    sku: string;
    name: string;
    product_classification: ProductClassification;
    classification_label: string; // من Accessor
    category_id: number;
    unit: string;
    pack_size?: number;
    purchase_price: number;
    sale_price: number;
    // ...
}

// frontend/src/features/products/ProductForm.tsx
const classificationOptions = [
    { value: 'finished_product', label: 'منتج تام' },
    { value: 'semi_finished', label: 'منتج غير تام' },
    { value: 'raw_material', label: 'مواد خام' },
    { value: 'parts', label: 'أجزاء' },
    { value: 'plastic_parts', label: 'بلاستيك' },
    { value: 'aluminum_parts', label: 'ألومنيوم' },
    { value: 'other', label: 'أخرى' },
];

<Select
    label="تصنيف المنتج"
    value={formData.product_classification}
    onValueChange={(value) => 
        setFormData({...formData, product_classification: value})
    }
    required
>
    {classificationOptions.map(opt => (
        <option key={opt.value} value={opt.value}>
            {opt.label}
        </option>
    ))}
</Select>
```

##### **6. Reports Integration**
```php
// إضافة فلتر في التقارير
public function stockValuation(Request $request)
{
    $validated = $request->validate([
        'branch_id' => 'nullable|exists:branches,id',
        'category_id' => 'nullable|exists:categories,id',
        'classification' => 'nullable|in:finished_product,semi_finished,parts,plastic_parts,aluminum_parts,raw_material,other',
    ]);
    
    $query = Product::with(['category', 'branchStocks.branch']);
    
    if ($validated['classification'] ?? null) {
        $query->byClassification($validated['classification']);
    }
    
    // باقي المنطق...
}
```

#### **الخلاصة - المشكلة الأولى:**

**ما يجب إضافته للـ Validation:**
- ✅ `product_classification` field (required, ENUM)
- ✅ Conditional validation للـ pack_size حسب التصنيف
- ✅ Conditional validation للـ sale_price >= purchase_price (للمنتجات التامة فقط)
- ✅ Unit validation حسب نوع المنتج (أجزاء = قطعة، بلاستيك/ألومنيوم = وزن أو قطعة)
- ✅ SKU auto-generation بـ prefix حسب التصنيف

**الأولوية:** 🟠 HIGH  
**الوقت المقدر:** 6-8 ساعات  
**الملفات المتأثرة:** 8 ملفات

---

### **المشكلة الثانية: أمر الطباعة لكل صفحة**

#### **الوصف:**
- المطلوب: توفير إمكانية طباعة لجميع أنواع الصفحات
- يجب أن يكون لكل نوع صفحة أمر طباعة مخصص
- دعم تنسيقات مختلفة (PDF، طباعة مباشرة، تصدير)

#### **التحليل الفني:**

**الوضع الحالي:**
```php
// حالياً يوجد PDF للفواتير فقط
public function printIssueVoucher($id)
{
    $voucher = IssueVoucher::with(['customer', 'branch', 'items.product'])->findOrFail($id);
    $pdf = PDF::loadView('pdfs.issue-voucher', compact('voucher'));
    return $pdf->download('issue-voucher-' . $voucher->voucher_number . '.pdf');
}
```

**المشكلة:**
- ❌ لا توجد طباعة للمرتجعات
- ❌ لا توجد طباعة لأوامر الشراء
- ❌ لا توجد طباعة لكشف حساب العميل
- ❌ لا توجد طباعة للشيكات
- ❌ لا توجد طباعة للتحويلات بين المخازن
- ❌ لا توجد validation للطباعة (مثل: لا يمكن الطباعة قبل الاعتماد)

#### **الحل المقترح:**

##### **1. Validation للطباعة**
```php
// app/Rules/CanPrint.php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CanPrint implements ValidationRule
{
    public function __construct(
        private string $documentType,
        private $document
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // التحقق من الحالة
        if ($this->document->status !== 'approved') {
            $fail('لا يمكن طباعة المستند قبل اعتماده');
        }

        // التحقق من الصلاحيات
        $user = auth()->user();
        $permission = "print-{$this->documentType}";
        
        if (!$user->can($permission)) {
            $fail('ليس لديك صلاحية طباعة هذا النوع من المستندات');
        }

        // التحقق من البيانات الكاملة
        if ($this->documentType === 'issue-voucher') {
            if (!$this->document->customer_id && !$this->document->customer_name) {
                $fail('بيانات العميل غير مكتملة. لا يمكن الطباعة');
            }
            
            if ($this->document->items->isEmpty()) {
                $fail('لا توجد منتجات في الإذن. لا يمكن الطباعة');
            }
        }

        // تسجيل عملية الطباعة في Audit Log
        activity()
            ->performedOn($this->document)
            ->causedBy($user)
            ->withProperties(['action' => 'print_attempt'])
            ->log("محاولة طباعة {$this->documentType}");
    }
}
```

##### **2. Controller مركزي للطباعة**
```php
// app/Http/Controllers/Api/V1/PrintController.php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IssueVoucher;
use App\Models\ReturnVoucher;
use App\Models\PurchaseOrder;
use App\Models\Customer;
use App\Rules\CanPrint;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    /**
     * طباعة إذن صرف
     */
    public function printIssueVoucher(Request $request, $id)
    {
        $voucher = IssueVoucher::with(['customer', 'branch', 'items.product', 'createdBy'])
            ->findOrFail($id);

        // Validation
        $validator = validator(['id' => $id], [
            'id' => [new CanPrint('issue-voucher', $voucher)]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'لا يمكن الطباعة',
                'errors' => $validator->errors()
            ], 422);
        }

        // تحديد نوع الإخراج
        $format = $request->input('format', 'pdf'); // pdf, html, print
        $template = $request->input('template', 'default'); // default, thermal, a5

        $pdf = PDF::loadView("pdfs.issue-voucher-{$template}", compact('voucher'))
            ->setPaper($template === 'thermal' ? [0, 0, 226.77, 566.93] : 'a4') // thermal 80mm
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        // تسجيل الطباعة
        activity()
            ->performedOn($voucher)
            ->causedBy(auth()->user())
            ->withProperties(['format' => $format, 'template' => $template])
            ->log('print_issue_voucher');

        // تحديث عداد الطباعات
        $voucher->increment('print_count');
        $voucher->update(['last_printed_at' => now()]);

        if ($format === 'html') {
            return view("pdfs.issue-voucher-{$template}", compact('voucher'));
        }

        return $pdf->download("issue-voucher-{$voucher->voucher_number}.pdf");
    }

    /**
     * طباعة إذن مرتجع
     */
    public function printReturnVoucher(Request $request, $id)
    {
        $voucher = ReturnVoucher::with(['customer', 'branch', 'items.product', 'createdBy'])
            ->findOrFail($id);

        $validator = validator(['id' => $id], [
            'id' => [new CanPrint('return-voucher', $voucher)]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'لا يمكن الطباعة',
                'errors' => $validator->errors()
            ], 422);
        }

        $format = $request->input('format', 'pdf');
        $template = $request->input('template', 'default');

        $pdf = PDF::loadView("pdfs.return-voucher-{$template}", compact('voucher'))
            ->setPaper('a4');

        activity()
            ->performedOn($voucher)
            ->causedBy(auth()->user())
            ->log('print_return_voucher');

        $voucher->increment('print_count');
        $voucher->update(['last_printed_at' => now()]);

        return $pdf->download("return-voucher-{$voucher->voucher_number}.pdf");
    }

    /**
     * طباعة أمر شراء
     */
    public function printPurchaseOrder(Request $request, $id)
    {
        $order = PurchaseOrder::with(['supplier', 'branch', 'items.product', 'createdBy'])
            ->findOrFail($id);

        $validator = validator(['id' => $id], [
            'id' => [new CanPrint('purchase-order', $order)]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'لا يمكن الطباعة',
                'errors' => $validator->errors()
            ], 422);
        }

        $pdf = PDF::loadView('pdfs.purchase-order', compact('order'))
            ->setPaper('a4');

        activity()
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->log('print_purchase_order');

        $order->increment('print_count');

        return $pdf->download("purchase-order-{$order->order_number}.pdf");
    }

    /**
     * طباعة كشف حساب عميل
     */
    public function printCustomerStatement(Request $request, $customerId)
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $customer = Customer::with(['ledgerEntries' => function($q) use ($validated) {
            $q->whereBetween('date', [$validated['from_date'], $validated['to_date']])
              ->orderBy('date')
              ->orderBy('id');
        }])->findOrFail($customerId);

        // حساب الرصيد
        $runningBalance = 0;
        $entries = $customer->ledgerEntries->map(function($entry) use (&$runningBalance) {
            $runningBalance += $entry->debit - $entry->credit;
            $entry->balance = $runningBalance;
            return $entry;
        });

        $pdf = PDF::loadView('pdfs.customer-statement', compact('customer', 'entries', 'validated'))
            ->setPaper('a4');

        activity()
            ->performedOn($customer)
            ->causedBy(auth()->user())
            ->withProperties($validated)
            ->log('print_customer_statement');

        return $pdf->download("customer-statement-{$customer->code}.pdf");
    }

    /**
     * طباعة شيك
     */
    public function printCheque($id)
    {
        $cheque = Cheque::with(['customer'])->findOrFail($id);

        $pdf = PDF::loadView('pdfs.cheque', compact('cheque'))
            ->setPaper([0, 0, 595.28, 283.46]); // حجم الشيك القياسي

        activity()
            ->performedOn($cheque)
            ->causedBy(auth()->user())
            ->log('print_cheque');

        return $pdf->download("cheque-{$cheque->cheque_number}.pdf");
    }

    /**
     * طباعة جماعية (Bulk Print)
     */
    public function bulkPrint(Request $request)
    {
        $validated = $request->validate([
            'document_type' => 'required|in:issue-voucher,return-voucher,purchase-order',
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'required|integer',
        ]);

        $documents = match($validated['document_type']) {
            'issue-voucher' => IssueVoucher::with(['customer', 'branch', 'items.product'])
                ->whereIn('id', $validated['ids'])
                ->where('status', 'approved')
                ->get(),
            'return-voucher' => ReturnVoucher::with(['customer', 'branch', 'items.product'])
                ->whereIn('id', $validated['ids'])
                ->where('status', 'approved')
                ->get(),
            'purchase-order' => PurchaseOrder::with(['supplier', 'branch', 'items.product'])
                ->whereIn('id', $validated['ids'])
                ->get(),
        };

        if ($documents->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد مستندات معتمدة للطباعة'
            ], 422);
        }

        $pdf = PDF::loadView('pdfs.bulk-print', compact('documents', 'validated'))
            ->setPaper('a4');

        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'document_type' => $validated['document_type'],
                'count' => $documents->count()
            ])
            ->log('bulk_print');

        return $pdf->download("bulk-print-{$validated['document_type']}.pdf");
    }
}
```

##### **3. Migration لتتبع الطباعة**
```sql
-- إضافة أعمدة تتبع الطباعة
ALTER TABLE issue_vouchers 
ADD COLUMN print_count INT DEFAULT 0,
ADD COLUMN last_printed_at TIMESTAMP NULL;

ALTER TABLE return_vouchers 
ADD COLUMN print_count INT DEFAULT 0,
ADD COLUMN last_printed_at TIMESTAMP NULL;

ALTER TABLE purchase_orders 
ADD COLUMN print_count INT DEFAULT 0,
ADD COLUMN last_printed_at TIMESTAMP NULL;
```

##### **4. Routes**
```php
// routes/api.php
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('print')->name('print.')->controller(PrintController::class)->group(function () {
        // طباعة فردية
        Route::get('issue-voucher/{id}', 'printIssueVoucher')->name('issue-voucher');
        Route::get('return-voucher/{id}', 'printReturnVoucher')->name('return-voucher');
        Route::get('purchase-order/{id}', 'printPurchaseOrder')->name('purchase-order');
        Route::get('customer-statement/{customerId}', 'printCustomerStatement')->name('customer-statement');
        Route::get('cheque/{id}', 'printCheque')->name('cheque');
        
        // طباعة جماعية
        Route::post('bulk', 'bulkPrint')->name('bulk');
    });
});
```

##### **5. Frontend Integration**
```typescript
// frontend/src/services/api/print.ts
export const printService = {
    printIssueVoucher: async (id: number, options?: PrintOptions) => {
        const params = new URLSearchParams({
            format: options?.format || 'pdf',
            template: options?.template || 'default'
        });
        
        const response = await apiClient.get(
            `/print/issue-voucher/${id}?${params}`,
            { responseType: 'blob' }
        );
        
        downloadBlob(response.data, `issue-voucher-${id}.pdf`);
    },
    
    printReturnVoucher: async (id: number) => {
        const response = await apiClient.get(
            `/print/return-voucher/${id}`,
            { responseType: 'blob' }
        );
        downloadBlob(response.data, `return-voucher-${id}.pdf`);
    },
    
    printCustomerStatement: async (customerId: number, fromDate: string, toDate: string) => {
        const response = await apiClient.get(
            `/print/customer-statement/${customerId}`,
            { 
                params: { from_date: fromDate, to_date: toDate },
                responseType: 'blob' 
            }
        );
        downloadBlob(response.data, `customer-statement-${customerId}.pdf`);
    },
    
    bulkPrint: async (documentType: string, ids: number[]) => {
        const response = await apiClient.post(
            '/print/bulk',
            { document_type: documentType, ids },
            { responseType: 'blob' }
        );
        downloadBlob(response.data, `bulk-${documentType}.pdf`);
    }
};

function downloadBlob(blob: Blob, filename: string) {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}
```

##### **6. Permissions**
```php
// database/seeders/PermissionSeeder.php
$printPermissions = [
    'print-issue-vouchers',
    'print-return-vouchers',
    'print-purchase-orders',
    'print-customer-statements',
    'print-cheques',
    'bulk-print',
];

foreach ($printPermissions as $permission) {
    Permission::create(['name' => $permission]);
}

// منح الصلاحيات للأدوار
$manager->givePermissionTo($printPermissions);
$accounting->givePermissionTo([
    'print-customer-statements',
    'print-cheques',
]);
$storeUser->givePermissionTo([
    'print-issue-vouchers',
    'print-return-vouchers',
]);
```

#### **الخلاصة - المشكلة الثانية:**

**ما يجب إضافته للـ Validation:**
- ✅ `CanPrint` custom validation rule
- ✅ التحقق من حالة المستند (status = approved)
- ✅ التحقق من الصلاحيات (permissions)
- ✅ التحقق من اكتمال البيانات قبل الطباعة
- ✅ Validation لصيغة الطباعة (format: pdf/html/print)
- ✅ Validation للـ template (default/thermal/a5)
- ✅ Validation للطباعة الجماعية (max 50 documents)
- ✅ Date range validation لكشف حساب العميل
- ✅ Audit logging لكل عملية طباعة

**الأولوية:** 🟠 HIGH  
**الوقت المقدر:** 10-12 ساعة  
**الملفات المتأثرة:** 15+ ملف

---

## 📊 **تحديث الإحصائيات بعد المتطلبات الجديدة**

| المكون | موجود | ناقص (قديم) | ناقص (جديد) | الإجمالي | النسبة |
|-------|--------|------------|------------|---------|--------|
| Products | 11 | 4 | **+5** | 20 | **55%** ⚠️ |
| Issue Vouchers | 13 | 7 | **+3** | 23 | **57%** ⚠️ |
| Return Vouchers | 7 | 5 | **+3** | 15 | **47%** 🔴 |
| Purchase Orders | 10 | 3 | **+3** | 16 | **63%** ⚠️ |
| Printing System | 0 | 0 | **+9** | 9 | **0%** 🔴 |
| **TOTAL** | **56** | **26** | **+23** | **105** | **53%** 🔴 |

**التقييم المحدث: 5.5/10** ⭐⭐⭐⭐⭐☆☆☆☆☆

---

## 🎯 **خطة العمل المحدثة**

### **Phase 1A: Critical (الأسبوع الأول)**
1. ✅ SufficientStock + Discount + Transfers (قديم)
2. 🆕 **Product Classification System** (6-8 ساعات)
3. 🆕 **CanPrint Validation Rule** (3 ساعات)

### **Phase 1B: Printing System (الأسبوع الثاني)**
4. 🆕 PrintController + Routes (4 ساعات)
5. 🆕 PDF Templates (6 ساعات)
6. 🆕 Permissions & Audit Logging (2 ساعات)

### **Phase 2: High Priority (باقي الأسابيع)**
7-11. Tasks السابقة...

**الوقت الإجمالي المقدر:** 6 أسابيع (بدلاً من 4)
