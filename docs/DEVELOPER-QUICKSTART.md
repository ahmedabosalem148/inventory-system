# 📋 ملخص Architecture للمطورين
## دليل سريع للبدء في التطوير

**التاريخ**: 17 أكتوبر 2025  
**المصدر**: تحليل BUSINESS-LOGIC-MAPPING.md + DATA-FLOW-ARCHITECTURE.md

---

## 🎯 فهم النظام بسرعة

### الهدف الأساسي
**إعادة إنتاج نفس نتائج Excel الحالية بدقة 100%** + إضافة:
- ✅ الأمان والصلاحيات
- ✅ منع التلاعب
- ✅ التكامل بين المخازن
- ✅ التدقيق الكامل

---

## 🏢 الكيانات الأساسية

### 1. الفروع (3)
```
المصنع   (branch_id: 1)
العتبة   (branch_id: 2)
إمبابة   (branch_id: 3)
```

### 2. الأدوار (3)
```yaml
Store User (مستخدم المخزن):
  - مرتبط بفرع واحد
  - يدير فرعه فقط
  - يشوف باقي الفروع (قراءة)

Manager (المدير):
  - وصول كامل لكل شيء
  - غير مرتبط بفرع محدد
  - صلاحيات شاملة

Accounting (الحسابات):
  - إدارة المالية والعملاء
  - قراءة المخزون فقط
  - غير مرتبط بفرع محدد
```

### 3. أنواع العمليات (4)
```
ADD        → إضافة للمخزون (افتتاحي/توريد)
ISSUE      → صرف (بيع/تحويل)
RETURN     → ارتجاع من عميل
TRANSFER   → تحويل بين فروع (OUT/IN)
```

---

## 🗂️ الجداول الحرجة

### 1. product_branch (الرصيد المتحرك)
```sql
product_branch:
  product_id
  branch_id
  current_qty      ⭐ يتحدث تلقائياً مع كل حركة
  min_qty          حد أدنى خاص بالفرع
```

**معادلة Excel المطابقة**:
```
الرصيد الحالي = الرصيد السابق + الإضافة + الارتجاع - الصرف
```

### 2. inventory_movements (تفصيل كل حركة)
```sql
inventory_movements:
  branch_id
  product_id
  movement_type    (ADD/ISSUE/RETURN/TRANSFER_OUT/TRANSFER_IN)
  qty_units        (+) إضافة | (-) صرف
  ref_table        نوع المستند (issue_vouchers/return_vouchers)
  ref_id           رقم المستند
  created_at
```

### 3. customer_ledger_entries (دفتر العملاء)
```sql
customer_ledger_entries:
  customer_id
  debit_aliah     ⭐ علية (على العميل)
  credit_lah      ⭐ له (للعميل)
  ref_table
  ref_id
  created_at
```

**معادلة Excel المطابقة**:
```
رصيد العميل = الإجمالي السابق + علية - له
```

### 4. sequences (الترقيم بدون فجوات)
```sql
sequences:
  document_type   (ISSUE/RETURN/TRANSFER)
  current_value   ⭐ يزيد +1 مع كل اعتماد
  min_value
  max_value       للارتجاع: 125000
```

**أرقام خاصة**:
- أذون الصرف: `1, 2, 3, ...` (عادي)
- أذون الارتجاع: `100,001 → 125,000` (نطاق محدود)

---

## 🔄 تدفق العمليات الأساسية

### 1️⃣ بيع آجل (Credit Sale)

```php
// الخطوات
1. إنشاء إذن صرف (DRAFT)
   IssueVoucher::create([
       'branch_source_id' => $branchId,
       'issue_type' => 'SALE',
       'customer_id' => $customerId,
       'payment_type' => 'CREDIT',
       'status' => 'DRAFT'
   ]);

2. إضافة البنود
   IssueVoucherLine::create([...]);

3. الاعتماد (APPROVED)
   ├─ إسناد رقم متسلسل (sequences)
   ├─ خصم المخزون (inventory_movements)
   ├─ تحديث current_qty (product_branch)
   └─ دفتر العميل - قيد علية (customer_ledger_entries)
```

**SQL Transaction**:
```sql
BEGIN TRANSACTION;
  -- 1. الترقيم
  UPDATE sequences SET current_value = current_value + 1 
  WHERE document_type = 'ISSUE';
  
  -- 2. الاعتماد
  UPDATE issue_vouchers SET number = ?, status = 'APPROVED';
  
  -- 3. حركة المخزون
  INSERT INTO inventory_movements (...) VALUES (-qty);
  
  -- 4. تحديث الرصيد
  UPDATE product_branch SET current_qty = current_qty - qty;
  
  -- 5. دفتر العميل
  INSERT INTO customer_ledger_entries (debit_aliah) VALUES (total);
COMMIT;
```

### 2️⃣ بيع نقدي (Cash Sale)

```php
// نفس الآجل + قيد "له" فوري
customer_ledger_entries:
  - debit_aliah: total    // علية (الفاتورة)
  - credit_lah: total     // له (الدفع الفوري)
// النتيجة: الرصيد = صفر
```

### 3️⃣ تحويل بين فروع (Transfer)

```php
// عمليتان مترابطتان
1. TRANSFER_OUT (من المصدر)
   inventory_movements:
     branch_id: source
     movement_type: 'TRANSFER_OUT'
     qty_units: -qty  // سالب
     
2. TRANSFER_IN (للمستهدف)
   inventory_movements:
     branch_id: target
     movement_type: 'TRANSFER_IN'
     qty_units: +qty  // موجب

// تحديث الأرصدة للطرفين
product_branch (source): current_qty -= qty
product_branch (target): current_qty += qty
```

### 4️⃣ ارتجاع (Return)

```php
// ترقيم خاص 100001-125000
1. الترقيم
   UPDATE sequences SET current_value = current_value + 1 
   WHERE document_type = 'RETURN' 
     AND current_value < 125000;  // ⚠️ التحقق من النطاق

2. إضافة للمخزون
   inventory_movements:
     movement_type: 'RETURN'
     qty_units: +qty  // موجب

3. دفتر العميل - قيد له (خصم من المديونية)
   customer_ledger_entries:
     credit_lah: total
```

---

## 🔐 قواعد الحماية الحرجة

### 1. منع الرصيد السالب (100%)
```php
// قبل اعتماد أي صرف
$currentQty = ProductBranch::where('branch_id', $branchId)
                          ->where('product_id', $productId)
                          ->value('current_qty');

if ($currentQty < $requiredQty) {
    throw new \Exception(
        "المخزون غير كافي. الرصيد الحالي: {$currentQty}"
    );
}
```

### 2. الترقيم بدون فجوات
```php
// داخل Transaction + Lock
DB::transaction(function() {
    $seq = DB::table('sequences')
            ->where('document_type', $type)
            ->lockForUpdate()  // ⭐ قفل للحماية من التضارب
            ->first();
    
    $newValue = $seq->current_value + 1;
    
    // للارتجاع فقط
    if ($type === 'RETURN' && $newValue > 125000) {
        throw new \Exception('تم استنفاد أرقام الارتجاع');
    }
    
    DB::table('sequences')->update(['current_value' => $newValue]);
    return $newValue;
});
```

### 3. صلاحيات الفروع
```php
// Policy
public function update(User $user, IssueVoucher $voucher)
{
    // المدير: كل شيء
    if ($user->role === 'manager') return true;
    
    // المخزن: فرعه فقط
    if ($user->role === 'store_user') {
        return $user->branch_id === $voucher->branch_source_id;
    }
    
    // الحسابات: منع
    return false;
}
```

---

## 📊 الحسابات والمعادلات

### 1. الرصيد المتحرك للمخزون
```sql
SELECT 
  SUM(CASE WHEN movement_type IN ('ADD','RETURN','TRANSFER_IN') 
           THEN qty_units ELSE 0 END) -
  SUM(CASE WHEN movement_type IN ('ISSUE','TRANSFER_OUT') 
           THEN qty_units ELSE 0 END) as running_balance
FROM inventory_movements
WHERE branch_id = ? AND product_id = ?;
```

### 2. رصيد العميل
```sql
SELECT 
  SUM(debit_aliah) - SUM(credit_lah) as balance
FROM customer_ledger_entries
WHERE customer_id = ?;
```

### 3. الخصومات
```php
// خصم البند
$lineTotal = ($qty × $price) - $lineDiscount;

// خصم الفاتورة
$totalBefore = array_sum($lineTotals);
$totalAfter = $totalBefore - $headerDiscount;

// أنواع: PERCENT أو AMOUNT
if ($discountType === 'PERCENT') {
    $discountAmount = ($value × $discountPercent) / 100;
} else {
    $discountAmount = $discountValue;
}
```

### 4. فحص كسر العبوة
```php
// تحذير فقط (لا منع)
if ($product->pack_size > 0) {
    $remainder = $qty % $product->pack_size;
    if ($remainder !== 0) {
        // عرض تحذير
        session()->flash('warning', 
            "كسر عبوة: {$remainder} قطعة زائدة"
        );
    }
}
```

---

## 🎨 التقارير الأساسية

### 1. تقرير الإجمالي (Stock Summary)
```sql
SELECT 
  p.sku, p.name, 
  CONCAT('* ', p.pack_size) as pack,
  pb.current_qty,
  pb.min_qty,
  CASE WHEN pb.current_qty <= pb.min_qty 
       THEN 'منخفض' ELSE 'عادي' END as status
FROM products p
JOIN product_branch pb ON p.id = pb.product_id
WHERE pb.branch_id = ?
ORDER BY status DESC, p.name;
```

### 2. حركة صنف
```sql
SELECT 
  created_at as date,
  movement_type,
  qty_units,
  notes,
  (SELECT current_qty FROM product_branch 
   WHERE branch_id = im.branch_id 
     AND product_id = im.product_id) as balance
FROM inventory_movements im
WHERE branch_id = ? AND product_id = ?
ORDER BY created_at DESC;
```

### 3. أرصدة العملاء
```sql
SELECT 
  c.name,
  SUM(cle.debit_aliah - cle.credit_lah) as balance,
  MAX(cle.created_at) as last_activity
FROM customers c
LEFT JOIN customer_ledger_entries cle ON c.id = cle.customer_id
GROUP BY c.id
HAVING balance != 0
ORDER BY balance DESC;
```

---

## 🚀 نقاط البداية للتطوير

### للبدء في Feature جديدة:

#### 1. فهم التدفق
```
قراءة: DATA-FLOW-ARCHITECTURE.md (القسم المتعلق)
     ↓
فهم: الجداول المرتبطة
     ↓
مراجعة: BUSINESS-LOGIC-MAPPING.md (المعادلات)
```

#### 2. التحقق من الـ Models
```php
app/Models/
  ├── Product.php
  ├── Branch.php
  ├── IssueVoucher.php
  ├── ReturnVoucher.php
  ├── Customer.php
  └── InventoryMovement.php
```

#### 3. الـ Services المهمة
```php
app/Services/
  ├── SequenceService.php        // الترقيم
  ├── InventoryService.php       // المخزون
  ├── CustomerLedgerService.php  // دفتر العملاء
  └── VoucherService.php         // الأذون
```

#### 4. الـ Policies
```php
app/Policies/
  ├── BranchPolicy.php
  ├── IssueVoucherPolicy.php
  └── CustomerPolicy.php
```

---

## ⚡ أمثلة كود جاهزة

### إنشاء بيع جديد
```php
public function createSale($customerId, $items, $branchId)
{
    return DB::transaction(function() use ($customerId, $items, $branchId) {
        
        // 1. إنشاء الإذن
        $voucher = IssueVoucher::create([
            'branch_source_id' => $branchId,
            'issue_type' => 'SALE',
            'customer_id' => $customerId,
            'payment_type' => 'CREDIT',
            'status' => 'DRAFT'
        ]);
        
        // 2. إضافة البنود
        foreach ($items as $item) {
            IssueVoucherLine::create([
                'issue_voucher_id' => $voucher->id,
                'product_id' => $item['product_id'],
                'qty_units' => $item['qty'],
                'unit_price' => $item['price']
            ]);
        }
        
        // 3. الاعتماد
        app(VoucherService::class)->approve($voucher->id);
        
        return $voucher;
    });
}
```

### فحص المخزون قبل الصرف
```php
public function checkStock($branchId, $productId, $requiredQty)
{
    $stock = ProductBranch::where('branch_id', $branchId)
                         ->where('product_id', $productId)
                         ->first();
    
    if (!$stock || $stock->current_qty < $requiredQty) {
        throw ValidationException::withMessages([
            'qty' => [
                "المخزون غير كافي. الرصيد الحالي: " . 
                ($stock->current_qty ?? 0)
            ]
        ]);
    }
    
    return true;
}
```

### إضافة قيد لدفتر العميل
```php
public function addLedgerEntry($customerId, $description, $debit, $credit, $refTable, $refId)
{
    CustomerLedgerEntry::create([
        'customer_id' => $customerId,
        'date' => now(),
        'description' => $description,
        'debit_aliah' => $debit,
        'credit_lah' => $credit,
        'ref_table' => $refTable,
        'ref_id' => $refId
    ]);
    
    // إرجاع الرصيد الجديد
    return CustomerLedgerEntry::where('customer_id', $customerId)
                              ->sum(DB::raw('debit_aliah - credit_lah'));
}
```

---

## 📋 Checklist قبل كل Feature

- [ ] قرأت الـ Architecture docs المتعلقة؟
- [ ] فهمت تدفق البيانات؟
- [ ] تحققت من الـ Policies المطلوبة؟
- [ ] أضفت التحقق من الرصيد (للصرف)؟
- [ ] استخدمت Transactions للعمليات المترابطة؟
- [ ] سجلت في Activity Log؟
- [ ] أضفت Tests للسيناريوهات الحرجة؟

---

## 🔗 ملفات مهمة للرجوع

```
docs/architecture/
  ├── DATA-FLOW-ARCHITECTURE.md      ⭐ تدفق شامل
  └── BUSINESS-LOGIC-MAPPING.md      ⭐ المعادلات والمطابقة

routes/
  └── api.php                         ⭐ جميع الـ endpoints

app/Http/Controllers/Api/V1/
  ├── AuthController.php
  ├── IssueVoucherController.php
  ├── ReturnVoucherController.php
  └── CustomerController.php

database/migrations/                  ⭐ هيكل الجداول
```

---

**تم التلخيص**: 17 أكتوبر 2025  
**المصادر**: BUSINESS-LOGIC-MAPPING.md + DATA-FLOW-ARCHITECTURE.md  
**الهدف**: دليل سريع للمطورين الجدد والحاليين

