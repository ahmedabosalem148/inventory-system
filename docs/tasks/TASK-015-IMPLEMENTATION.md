# ✅ TASK-015: Pack Size Validation - Implementation Guide

**التاريخ:** 2025-10-02  
**الحالة:** 🔄 In Progress

---

## 📋 المتطلبات (من BACKLOG)

- ✅ تنبيه أصفر عند `qty_units % pack_size !== 0`
- ✅ لا يمنع الحفظ، فقط تنبيه
- ✅ إذا `pack_size = null` → لا تحقق
- ✅ عرض: "عبوة كاملة + وحدات إضافية"

---

## 🔧 التنفيذ

### 1. JavaScript Functions

تم إنشاء دالتين في `pack_validation.js`:

#### `checkPackSize(index)`
- **الغرض:** التحقق من كسر العبوة عند إدخال الكمية
- **Logic:**
  ```javascript
  if (packSize && packSize > 0 && qty > 0 && qty % packSize !== 0) {
      // عرض تنبيه
  } else {
      // إخفاء التنبيه
  }
  ```

#### `createWarningDiv(row)`
- **الغرض:** إنشاء div للتنبيه في الصف
- **Class:** `alert alert-warning alert-sm`
- **Position:** داخل `<td>` الكمية

---

### 2. التعديلات المطلوبة

#### A) `issue_vouchers/create.blade.php`

**الخطوة 1:** إضافة `data-pack-size` للـ products select

ابحث عن:
```php
@foreach($products as $product)
    <option value="{{ $product->id }}" 
            data-price="{{ $product->sale_price }}"
            data-stock="{{ $product->branchStocks->where('branch_id', old('branch_id'))->first()->current_stock ?? 0 }}">
```

استبدلها بـ:
```php
@foreach($products as $product)
    <option value="{{ $product->id }}" 
            data-price="{{ $product->sale_price }}"
            data-pack-size="{{ $product->pack_size ?? 0 }}"
            data-stock="{{ $product->branchStocks->where('branch_id', old('branch_id'))->first()->current_stock ?? 0 }}">
```

**الخطوة 2:** إضافة استدعاء `checkPackSize()` في event handler

ابحث عن:
```javascript
// في دالة calculateRow أو عند تغيير الكمية
qtyInput.addEventListener('input', function() {
    calculateRow(index);
});
```

أضف بعدها:
```javascript
qtyInput.addEventListener('input', function() {
    calculateRow(index);
    checkPackSize(index);  // ✅ إضافة جديدة
});
```

**الخطوة 3:** إضافة functions في نهاية `<script>`

قبل `</script>` مباشرة، أضف محتوى `pack_validation.js`

---

#### B) `return_vouchers/create.blade.php`

نفس الخطوات السابقة تماماً.

---

## 🧪 الاختبار

### سيناريو 1: منتج بدون pack_size
- **Product:** pack_size = null
- **Qty:** 15
- **النتيجة المتوقعة:** ✅ لا يظهر تنبيه

### سيناريو 2: كمية تتوافق مع العبوة
- **Product:** pack_size = 12
- **Qty:** 24 (12 × 2)
- **النتيجة المتوقعة:** ✅ لا يظهر تنبيه

### سيناريو 3: كسر عبوة
- **Product:** pack_size = 12
- **Qty:** 15
- **النتيجة المتوقعة:** 
  ```
  ⚠️ تنبيه: كسر عبوة! 
  الكمية لا تتوافق مع حجم العبوة (12 وحدة). 
  لديك 1 عبوة كاملة + 3 وحدة إضافية.
  ```

### سيناريو 4: كمية 0
- **Product:** pack_size = 12
- **Qty:** 0
- **النتيجة المتوقعة:** ✅ لا يظهر تنبيه

---

## 📁 الملفات

```
✅ pack_validation.js (JavaScript code)
⏳ issue_vouchers/create.blade.php (needs manual edit)
⏳ return_vouchers/create.blade.php (needs manual edit)
⏳ TASK-015-COMPLETED.md (documentation)
```

---

## 🎨 التصميم

**Alert Style:**
- Class: `alert alert-warning alert-sm`
- Icon: `bi-exclamation-triangle-fill`
- Color: أصفر (warning)
- Position: تحت input الكمية مباشرة
- Font Size: 0.85rem
- Padding: 0.5rem

---

## ✅ Acceptance Criteria

من BACKLOG.md:

- [x] `qty_units % pack_size !== 0` → تنبيه أصفر ✅
- [x] لا يُمنع الحفظ ✅
- [x] pack_size=null → لا تحقق ✅
- [x] عرض عدد العبوات الكاملة والوحدات الإضافية ✅

---

## 📝 ملاحظات

1. **لا يمنع الحفظ:** التنبيه فقط visual، الـ form validation لا تتأثر
2. **Real-time:** التنبيه يظهر فوراً عند الكتابة
3. **Dynamic:** يتم إنشاء div التنبيه ديناميكياً
4. **RTL:** النص عربي بالكامل

---

**Status:** 60% Complete (JavaScript ready, needs manual integration)  
**Next:** Add to blade files manually, then test
