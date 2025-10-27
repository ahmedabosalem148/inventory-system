# خطة تنفيذ Validation - Priority Action Plan

## 🔴 **المرحلة 1: Critical Fixes (يجب تنفيذها فوراً)**

### ⚡ **متطلبات جديدة من العميل (أولوية عالية)**

### Task 0.1: Product Classification System
**الأولوية:** 🔴 CRITICAL (جديد)  
**الوقت المقدر:** 6-8 ساعات  
**الوصف:** إضافة تصنيف للمنتجات (منتج تام / غير تام / أجزاء / بلاستيك / ألومنيوم)

**الملفات الجديدة:**
```bash
# Migration
php artisan make:migration add_product_classification_to_products_table

# Update Model
app/Models/Product.php

# Form Request
php artisan make:request StoreProductRequest
```

**Database Schema:**
```sql
ALTER TABLE products 
ADD COLUMN product_classification ENUM(
    'finished_product',
    'semi_finished',
    'raw_material',
    'parts',
    'plastic_parts',
    'aluminum_parts',
    'other'
) DEFAULT 'finished_product' AFTER category_id;

CREATE INDEX idx_products_classification ON products(product_classification);
```

**Validation Rules:**
```php
'product_classification' => [
    'required',
    'string',
    Rule::in([
        'finished_product',
        'semi_finished',
        'raw_material',
        'parts',
        'plastic_parts',
        'aluminum_parts',
        'other'
    ])
],

// Conditional: pack_size required for parts/plastic/aluminum
'pack_size' => [
    'nullable',
    'integer',
    'min:1',
    Rule::requiredIf(fn() => in_array(
        $this->product_classification,
        ['parts', 'plastic_parts', 'aluminum_parts']
    ))
],

// Conditional: sale_price >= purchase_price (finished products only)
'sale_price' => [
    'required',
    'numeric',
    'min:0',
    Rule::when(
        $this->product_classification === 'finished_product',
        'gte:purchase_price'
    )
],
```

**Unit Validation حسب التصنيف:**
```php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        // أجزاء: يجب أن تكون بالقطعة
        if ($this->product_classification === 'parts') {
            $validUnits = ['pcs', 'piece', 'unit', 'قطعة'];
            if (!in_array(strtolower($this->unit), $validUnits)) {
                $validator->errors()->add('unit', 'الأجزاء يجب أن تكون بالقطعة');
            }
        }
        
        // بلاستيك/ألومنيوم: وزن أو قطعة
        if (in_array($this->product_classification, ['plastic_parts', 'aluminum_parts'])) {
            $validUnits = ['kg', 'gram', 'ton', 'pcs', 'piece', 'كجم', 'جرام', 'قطعة'];
            if (!in_array(strtolower($this->unit), $validUnits)) {
                $validator->errors()->add('unit', 'وحدة القياس غير مناسبة');
            }
        }
    });
}
```

**SKU Auto-Generation:**
```php
$prefix = match($validated['product_classification']) {
    'finished_product' => 'FIN',
    'semi_finished' => 'SEM',
    'parts' => 'PRT',
    'plastic_parts' => 'PLS',
    'aluminum_parts' => 'ALU',
    'raw_material' => 'RAW',
    default => 'OTH'
};

$validated['sku'] = $prefix . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
```

**Frontend:**
```typescript
// types/product.ts
export type ProductClassification = 
    | 'finished_product'
    | 'semi_finished'
    | 'raw_material'
    | 'parts'
    | 'plastic_parts'
    | 'aluminum_parts'
    | 'other';

// ProductForm.tsx
<Select label="تصنيف المنتج" required>
    <option value="finished_product">منتج تام</option>
    <option value="semi_finished">منتج غير تام</option>
    <option value="parts">أجزاء</option>
    <option value="plastic_parts">بلاستيك</option>
    <option value="aluminum_parts">ألومنيوم</option>
    <option value="raw_material">مواد خام</option>
    <option value="other">أخرى</option>
</Select>
```

---

### Task 0.2: Universal Print System
**الأولوية:** 🔴 CRITICAL (جديد)  
**الوقت المقدر:** 10-12 ساعة  
**الوصف:** نظام طباعة شامل لجميع المستندات مع validation

**الملفات الجديدة:**
```bash
# Custom Rule
php artisan make:rule CanPrint

# Controller
php artisan make:controller Api/V1/PrintController

# Migration
php artisan make:migration add_print_tracking_columns

# Views (PDF Templates)
resources/views/pdfs/issue-voucher-default.blade.php
resources/views/pdfs/issue-voucher-thermal.blade.php
resources/views/pdfs/return-voucher.blade.php
resources/views/pdfs/purchase-order.blade.php
resources/views/pdfs/customer-statement.blade.php
resources/views/pdfs/cheque.blade.php
resources/views/pdfs/bulk-print.blade.php
```

**CanPrint Validation Rule:**
```php
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
        // 1. التحقق من الحالة
        if ($this->document->status !== 'approved') {
            $fail('لا يمكن طباعة المستند قبل اعتماده');
        }

        // 2. التحقق من الصلاحيات
        $permission = "print-{$this->documentType}";
        if (!auth()->user()->can($permission)) {
            $fail('ليس لديك صلاحية طباعة هذا النوع');
        }

        // 3. التحقق من اكتمال البيانات
        if ($this->documentType === 'issue-voucher') {
            if (!$this->document->customer_id && !$this->document->customer_name) {
                $fail('بيانات العميل غير مكتملة');
            }
            
            if ($this->document->items->isEmpty()) {
                $fail('لا توجد منتجات في الإذن');
            }
        }

        // 4. Audit Log
        activity()
            ->performedOn($this->document)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'print_attempt'])
            ->log("محاولة طباعة {$this->documentType}");
    }
}
```

**Print Controller:**
```php
class PrintController extends Controller
{
    // 1. طباعة إذن صرف
    public function printIssueVoucher(Request $request, $id)
    {
        $voucher = IssueVoucher::with([...])->findOrFail($id);
        
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
        
        $format = $request->input('format', 'pdf'); // pdf, html
        $template = $request->input('template', 'default'); // default, thermal
        
        $pdf = PDF::loadView("pdfs.issue-voucher-{$template}", compact('voucher'));
        
        // Tracking
        $voucher->increment('print_count');
        $voucher->update(['last_printed_at' => now()]);
        
        activity()->performedOn($voucher)->log('print_issue_voucher');
        
        return $pdf->download("issue-voucher-{$voucher->voucher_number}.pdf");
    }
    
    // 2. طباعة إذن مرتجع
    public function printReturnVoucher($id) { ... }
    
    // 3. طباعة أمر شراء
    public function printPurchaseOrder($id) { ... }
    
    // 4. طباعة كشف حساب
    public function printCustomerStatement(Request $request, $customerId)
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);
        
        $customer = Customer::with(['ledgerEntries' => ...])->findOrFail($customerId);
        
        $pdf = PDF::loadView('pdfs.customer-statement', compact('customer'));
        return $pdf->download("statement-{$customer->code}.pdf");
    }
    
    // 5. طباعة جماعية
    public function bulkPrint(Request $request)
    {
        $validated = $request->validate([
            'document_type' => 'required|in:issue-voucher,return-voucher,purchase-order',
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'required|integer',
        ]);
        
        // ... bulk print logic
    }
}
```

**Migration:**
```sql
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

**Routes:**
```php
Route::prefix('print')->controller(PrintController::class)->group(function () {
    Route::get('issue-voucher/{id}', 'printIssueVoucher');
    Route::get('return-voucher/{id}', 'printReturnVoucher');
    Route::get('purchase-order/{id}', 'printPurchaseOrder');
    Route::get('customer-statement/{id}', 'printCustomerStatement');
    Route::get('cheque/{id}', 'printCheque');
    Route::post('bulk', 'bulkPrint');
});
```

**Permissions:**
```php
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
```

**Frontend:**
```typescript
// services/api/print.ts
export const printService = {
    printIssueVoucher: async (id: number, options?: PrintOptions) => {
        const response = await apiClient.get(
            `/print/issue-voucher/${id}`,
            { 
                params: { format: 'pdf', template: 'default' },
                responseType: 'blob' 
            }
        );
        downloadBlob(response.data, `issue-voucher-${id}.pdf`);
    },
    
    // ... other print methods
};
```

---

### Task 1.1: SufficientStock Custom Rule
**الأولوية:** 🔴 CRITICAL  
**الوقت المقدر:** 2 ساعات  
**الملفات:**
- `app/Rules/SufficientStock.php` (جديد)
- `app/Http/Controllers/Api/V1/IssueVoucherController.php` (تعديل)

**الكود:**
```bash
php artisan make:rule SufficientStock
```

### Task 1.2: إضافة Discount Validation
**الأولوية:** 🔴 CRITICAL  
**الوقت المقدر:** 3 ساعات  
**الملفات:**
- `app/Rules/MaxDiscountValue.php` (جديد)
- `app/Http/Controllers/Api/V1/IssueVoucherController.php`
- `app/Http/Controllers/Api/V1/ReturnVoucherController.php`

### Task 1.3: إضافة Transfer Validations
**الأولوية:** 🔴 CRITICAL  
**الوقت المقدر:** 2 ساعات  
**التغييرات:**
```php
// في IssueVoucherController::store()
'issue_type' => 'required|in:SALE,TRANSFER',
'target_branch_id' => 'required_if:issue_type,TRANSFER|exists:branches,id|different:branch_id',
'payment_type' => 'required_if:issue_type,SALE|in:CASH,CREDIT',
```

### Task 1.4: إضافة Reason للمرتجعات
**الأولوية:** 🔴 CRITICAL  
**الوقت المقدر:** 1 ساعة  
**الملفات:**
- `app/Http/Controllers/Api/V1/ReturnVoucherController.php`
- `database/migrations/xxxx_add_reason_to_return_vouchers.php` (جديد)

```php
// في ReturnVoucherController
'reason' => 'required|string|max:500',
'reason_category' => 'nullable|in:damaged,defective,customer_request,wrong_item,other',
```

---

## 🟠 **المرحلة 2: High Priority (هذا الأسبوع)**

### Task 2.1: إضافة SKU Validation
**الأولوية:** 🟠 HIGH  
**الوقت المقدر:** 3 ساعات  
**الملفات:**
- `app/Http/Controllers/Api/V1/ProductController.php`
- `database/migrations/xxxx_add_sku_unique_index.php` (جديد)

```php
'sku' => 'required|string|unique:products,sku|max:50|regex:/^[A-Z0-9-]+$/',

// Auto-generate if empty
if (!$request->filled('sku')) {
    $validated['sku'] = 'PRD-' . str_pad(Product::max('id') + 1, 6, '0', STR_PAD_LEFT);
}
```

### Task 2.2: Pack Size Warning System
**الأولوية:** 🟠 HIGH  
**الوقت المقدر:** 4 ساعات  
**الملفات:**
- `app/Rules/PackSizeCheck.php` (جديد)
- `app/Http/Middleware/AttachWarnings.php` (جديد)
- تعديل Controllers

```php
// Custom Rule الذي يضيف warnings بدلاً من errors
class PackSizeCheck implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // يضيف warning للـ session بدلاً من fail
        if ($remainder != 0) {
            session()->push('validation.warnings', [
                'field' => $attribute,
                'message' => "تنبيه: الكمية لا تساوي مضاعف العبوة"
            ]);
        }
    }
}
```

### Task 2.3: Cheque Validations Enhancement
**الأولوية:** 🟠 HIGH  
**الوقت المقدر:** 2 ساعات  
**التغييرات:**
```php
'cheque_due_date' => [
    'required_if:payment_method,cheque',
    'date',
    'after_or_equal:cheque_date'
],
'cheque_number' => [
    'required_if:payment_method,cheque',
    'string',
    'unique:cheques,cheque_number,NULL,id,bank_name,' . $request->bank_name
]
```

### Task 2.4: Return Voucher Number Range Validation
**الأولوية:** 🟠 HIGH  
**الوقت المقدر:** 1 ساعة  
**الملف:** `app/Services/SequencerService.php`

```php
public function validateReturnVoucherRange(int $number): bool
{
    if ($number < 100001 || $number > 125000) {
        throw new \Exception('رقم المرتجع خارج النطاق المسموح (100001-125000)');
    }
    return true;
}
```

---

## 🟡 **المرحلة 3: Form Request Classes (الأسبوع القادم)**

### Task 3.1: Create Form Requests
**الأولوية:** 🟡 MEDIUM  
**الوقت المقدر:** 8 ساعات  

**الملفات الجديدة:**
```bash
php artisan make:request StoreIssueVoucherRequest
php artisan make:request UpdateIssueVoucherRequest
php artisan make:request StoreReturnVoucherRequest
php artisan make:request StorePurchaseOrderRequest
php artisan make:request UpdatePurchaseOrderRequest
php artisan make:request StoreProductRequest
php artisan make:request UpdateProductRequest
php artisan make:request StoreCustomerRequest
php artisan make:request UpdateCustomerRequest
php artisan make:request StorePaymentRequest
php artisan make:request StoreChequeRequest
php artisan make:request UpdateChequeRequest
```

### Task 3.2: Migrate Inline Validations
**الأولوية:** 🟡 MEDIUM  
**الوقت المقدر:** 6 ساعات  
**العملية:**
1. نقل كل inline validation من Controllers إلى Form Requests
2. إضافة custom messages عربية
3. إضافة authorization logic
4. Testing

---

## 🟢 **المرحلة 4: Advanced Validations (الأسبوع بعد القادم)**

### Task 4.1: Customer Balance Validation
**الأولوية:** 🟢 LOW  
**الوقت المقدر:** 3 ساعات  

```php
// في PaymentController
'amount' => [
    'required',
    'numeric',
    'min:0.01',
    function ($attribute, $value, $fail) use ($request) {
        $balance = Customer::find($request->customer_id)->balance ?? 0;
        if ($value > $balance && $balance > 0) {
            // تنبيه فقط، لا fail
            session()->push('validation.warnings', [
                'message' => "المبلغ أكبر من رصيد العميل الحالي ($balance)"
            ]);
        }
    }
]
```

### Task 4.2: Phone Format Validation
**الأولوية:** 🟢 LOW  
**الوقت المقدر:** 1 ساعة  

```php
'phone' => [
    'nullable',
    'string',
    'max:20',
    'regex:/^(\+2)?01[0-2,5]{1}[0-9]{8}$/' // مصري فقط
]
```

### Task 4.3: Tax ID Unique Constraint
**الأولوية:** 🟢 LOW  
**الوقت المقدر:** 1 ساعة  

```php
'tax_id' => 'nullable|string|max:50|unique:customers,tax_id'
```

### Task 4.4: Status Transition Validations
**الأولوية:** 🟢 LOW  
**الوقت المقدر:** 4 ساعات  
**الملفات:**
- `app/Rules/ValidStatusTransition.php` (جديد)
- Controllers (تعديل)

```php
class ValidStatusTransition implements ValidationRule
{
    private array $allowedTransitions = [
        'PENDING' => ['APPROVED', 'CANCELLED'],
        'APPROVED' => ['COMPLETED'],
        'CANCELLED' => [],
        'COMPLETED' => []
    ];
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $currentStatus = $this->model->status;
        
        if (!in_array($value, $this->allowedTransitions[$currentStatus] ?? [])) {
            $fail("لا يمكن تغيير الحالة من $currentStatus إلى $value");
        }
    }
}
```

---

## 📊 **Timeline و Milestones (محدث)**

### **Week 1: Critical Fixes + New Requirements**
- ✅ Day 1-2: **Task 0.1** Product Classification System (6-8h)
- ✅ Day 3-4: **Task 0.2** Universal Print System (10-12h)
- ✅ Day 5: Tasks 1.1, 1.2, 1.3, 1.4 (Original Critical)

**Milestone 1:** Product classification working + Print system functional

### **Week 2: High Priority Validations**
- ✅ Day 1-2: Testing Week 1 deliverables
- ✅ Day 3-4: Tasks 2.1, 2.2 (SKU + Pack Size)
- ✅ Day 5: Tasks 2.3, 2.4 (Cheques + Return Range)

**Milestone 2:** منع الرصيد السالب + Discount validations + Pack size warnings

### **Week 3: Form Request Classes**
- ✅ Day 1-3: Task 3.1 (Create 12+ Form Requests)
- ✅ Day 4-5: Begin Task 3.2 (Migrate validations)

**Milestone 3:** Form Request classes + Product classification integrated

### **Week 4: Migration & Testing**
- ✅ Day 1-2: Complete Task 3.2 (Migrate all validations)
- ✅ Day 3-4: Comprehensive testing (Unit + Feature)
- ✅ Day 5: PDF templates testing

**Milestone 4:** كل الـ validations في Form Requests + Print templates working

### **Week 5: Advanced & Polish**
- ✅ Day 1-2: Tasks 4.1, 4.2, 4.3
- ✅ Day 3-4: Task 4.4 (Status Transitions)
- ✅ Day 5: Performance testing

**Milestone 5:** Advanced validations + Audit logging complete

### **Week 6: Final Testing & Documentation**
- ✅ Day 1-2: End-to-end testing
- ✅ Day 3: Documentation (OpenAPI/Swagger)
- ✅ Day 4: User acceptance testing
- ✅ Day 5: Deployment preparation

**Milestone 6:** نظام validation كامل 100% + Production ready

---

## 📈 **Updated Progress Tracking**

### **الإحصائيات المحدثة:**

| المرحلة | المهام | ساعات | الحالة |
|---------|--------|-------|--------|
| **Week 1** | 6 tasks | 30h | 🔴 Not Started |
| **Week 2** | 4 tasks | 18h | ⚪ Pending |
| **Week 3** | 2 tasks | 24h | ⚪ Pending |
| **Week 4** | 3 tasks | 24h | ⚪ Pending |
| **Week 5** | 4 tasks | 20h | ⚪ Pending |
| **Week 6** | 5 tasks | 16h | ⚪ Pending |
| **TOTAL** | **24 tasks** | **132h** | **0%** |

### **Validation Coverage:**

| النوع | الحالي | المطلوب | الفجوة |
|------|--------|---------|--------|
| Products | 11 | 20 | **+9** 🔴 |
| Issue Vouchers | 13 | 23 | **+10** 🔴 |
| Return Vouchers | 7 | 15 | **+8** 🔴 |
| Purchase Orders | 10 | 16 | **+6** 🟠 |
| Payments | 8 | 11 | **+3** 🟡 |
| **Printing System** | **0** | **9** | **+9** 🔴 |
| **TOTAL** | **56** | **105** | **+49** 🔴 |

**التقدم الإجمالي:** 53.3% (56/105)

---

## 🧪 **Testing Strategy**

### **Unit Tests**
```bash
php artisan make:test Rules/SufficientStockTest --unit
php artisan make:test Rules/PackSizeCheckTest --unit
php artisan make:test Rules/MaxDiscountValueTest --unit
```

### **Feature Tests**
```bash
php artisan make:test IssueVoucherValidationTest
php artisan make:test ReturnVoucherValidationTest
php artisan make:test ProductValidationTest
php artisan make:test PaymentValidationTest
```

### **Test Cases Examples**
```php
public function test_issue_voucher_prevents_negative_stock()
{
    $product = Product::factory()->create();
    $branch = Branch::factory()->create();
    
    // Set stock to 10
    ProductBranch::create([
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'current_stock' => 10
    ]);
    
    // Try to issue 15 (should fail)
    $response = $this->postJson('/api/v1/issue-vouchers', [
        'branch_id' => $branch->id,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 15]
        ]
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors('items.0.quantity');
}

public function test_discount_cannot_exceed_total()
{
    $response = $this->postJson('/api/v1/issue-vouchers', [
        'discount_type' => 'fixed',
        'discount_value' => 1000,
        'items' => [
            ['quantity' => 10, 'unit_price' => 50] // total = 500
        ]
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors('discount_value');
}

public function test_return_voucher_requires_reason()
{
    $response = $this->postJson('/api/v1/return-vouchers', [
        'branch_id' => 1,
        'items' => [...]
        // missing 'reason'
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors('reason');
}
```

---

## 📝 **Documentation Updates**

### **OpenAPI/Swagger Annotations**
```php
/**
 * @OA\Post(
 *     path="/api/v1/issue-vouchers",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"branch_id","items"},
 *             @OA\Property(property="discount_value", type="number", 
 *                 minimum=0, 
 *                 maximum="calculated from items total",
 *                 description="يجب ألا يتجاوز إجمالي الفاتورة"
 *             )
 *         )
 *     ),
 *     @OA\Response(response=422, description="Validation errors")
 * )
 */
```

### **README Updates**
- إضافة قسم Validation Rules
- توثيق Custom Rules
- أمثلة على Error responses
- Warning system documentation

---

## ✅ **Checklist التنفيذ (محدث)**

### **Phase 0: New Requirements (Week 1) 🆕**
- [ ] Task 0.1: Product Classification System
  - [ ] Migration: add product_classification column
  - [ ] Model: Product constants and scopes
  - [ ] Validation: classification + conditional rules
  - [ ] Controller: auto SKU generation
  - [ ] Frontend: classification selector
  - [ ] Reports: filter by classification
  - [ ] Tests: classification validation
  - [ ] Code review
- [ ] Task 0.2: Universal Print System
  - [ ] Custom Rule: CanPrint
  - [ ] Migration: print tracking columns
  - [ ] Controller: PrintController (6 methods)
  - [ ] PDF Templates: 7+ templates
  - [ ] Routes: /api/v1/print/*
  - [ ] Permissions: 6 print permissions
  - [ ] Frontend: print service
  - [ ] Tests: print validations
  - [ ] Code review
- [ ] Merge to develop (New Requirements)

### **Phase 1: Critical (Week 1-2)**
- [ ] Task 1.1: SufficientStock Rule
- [ ] Task 1.2: Discount Validation
- [ ] Task 1.3: Transfer Validations
- [ ] Task 1.4: Return Reason
- [ ] Tests for Phase 1
- [ ] Code review
- [ ] Merge to develop

### **Phase 2: High Priority (Week 2-3)**
- [ ] Task 2.1: SKU Validation
- [ ] Task 2.2: Pack Size Warnings
- [ ] Task 2.3: Cheque Validations
- [ ] Task 2.4: Return Number Range
- [ ] Tests for Phase 2
- [ ] Code review
- [ ] Merge to develop

### **Phase 3: Form Requests (Week 3-4)**
- [ ] Task 3.1: Create Form Requests
  - [ ] StoreProductRequest (with classification)
  - [ ] UpdateProductRequest
  - [ ] StoreIssueVoucherRequest (with print validation)
  - [ ] UpdateIssueVoucherRequest
  - [ ] StoreReturnVoucherRequest
  - [ ] UpdateReturnVoucherRequest
  - [ ] StorePurchaseOrderRequest
  - [ ] UpdatePurchaseOrderRequest
  - [ ] StoreCustomerRequest
  - [ ] UpdateCustomerRequest
  - [ ] StorePaymentRequest
  - [ ] StoreChequeRequest
  - [ ] UpdateChequeRequest
  - [ ] PrintRequest (generic)
- [ ] Task 3.2: Migrate Validations
- [ ] Tests for all Form Requests
- [ ] Code review
- [ ] Documentation
- [ ] Merge to develop

### **Phase 4: Advanced (Week 5)**
- [ ] Task 4.1: Balance Validation
- [ ] Task 4.2: Phone Format
- [ ] Task 4.3: Tax ID Unique
- [ ] Task 4.4: Status Transitions
- [ ] Final comprehensive testing
- [ ] Performance testing
- [ ] Documentation complete
- [ ] Merge to main

### **Phase 5: Deployment (Week 6)**
- [ ] Production database backup
- [ ] Run migrations on production
- [ ] Seed new permissions
- [ ] Update PDF templates
- [ ] Test printing system
- [ ] Monitor error logs
- [ ] User training (classification system)
- [ ] Tag v2.0-validation-complete
- [ ] Deploy to production

---

## 🎯 **Success Criteria (محدث)**

### **Must Have:**
✅ لا يمكن إنشاء إذن صرف بكمية أكبر من المخزون  
✅ الخصم لا يتجاوز إجمالي الفاتورة/البند  
✅ التحويلات تتطلب فرع مستلم مختلف  
✅ المرتجعات تتطلب سبب  
✅ SKU فريد لكل منتج  
✅ Pack size warnings تظهر للمستخدم  
🆕 **Product classification system يعمل بكفاءة**  
🆕 **طباعة جميع المستندات بصيغة PDF**  
🆕 **Validation للطباعة (status + permissions + data completeness)**  
🆕 **Classification-based SKU generation**  
🆕 **Unit validation حسب نوع المنتج**

### **Should Have:**
✅ Form Request classes لكل endpoint  
✅ رسائل أخطاء عربية واضحة  
✅ Test coverage ≥ 80%  
✅ Swagger documentation كامل  
🆕 **Print tracking (count + last_printed_at)**  
🆕 **Audit logging لكل عملية طباعة**  
🆕 **Multiple print templates (default/thermal/a5)**  
🆕 **Bulk printing support (max 50 documents)**

### **Nice to Have:**
✅ Real-time validation في Frontend  
✅ Validation performance < 50ms  
✅ Custom error pages  
🆕 **Print preview before download**  
🆕 **Email PDF attachments**  
🆕 **Custom watermarks on prints**  
🆕 **Print queue management**

### **Acceptance Criteria:**

#### **Product Classification:**
- [ ] يمكن اختيار تصنيف من 7 خيارات
- [ ] SKU يُولد تلقائياً بـ prefix حسب التصنيف (FIN/SEM/PRT/PLS/ALU/RAW/OTH)
- [ ] Pack size مطلوب فقط للأجزاء والبلاستيك والألومنيوم
- [ ] Sale price >= purchase price للمنتجات التامة فقط
- [ ] Unit validation يعمل حسب التصنيف
- [ ] يمكن الفلترة في التقارير حسب التصنيف
- [ ] Frontend يعرض التصنيفات بالعربية

#### **Print System:**
- [ ] يمكن طباعة: Issue voucher, Return voucher, Purchase order, Customer statement, Cheque
- [ ] لا يمكن الطباعة قبل الاعتماد (status = approved)
- [ ] التحقق من الصلاحيات قبل الطباعة
- [ ] التحقق من اكتمال البيانات (customer, items, etc.)
- [ ] Print count يزيد بعد كل طباعة
- [ ] last_printed_at يُحدث بعد كل طباعة
- [ ] Audit log يسجل كل عملية طباعة
- [ ] يمكن الطباعة الجماعية حتى 50 مستند
- [ ] PDF templates عربية صحيحة
- [ ] دعم thermal printer (80mm width)
- [ ] Frontend يُنزل PDF بنجاح

---

## 📞 **Contact & Support**

**المطور المسؤول:** [Your Name]  
**Git Branch:** `feature/validation-improvements`  
**Documentation:** `/docs/validation-rules.md`  
**Tests Location:** `/tests/Feature/Validation/`

---

**آخر تحديث:** 2025-10-27  
**الحالة:** 📋 Ready for Implementation  
**الأولوية:** 🔴 CRITICAL
