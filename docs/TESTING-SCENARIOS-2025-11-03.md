# 🧪 سيناريوهات الاختبار - November 3, 2025

## دليل اختبار التحديثات المنفذة اليوم

---

## 1️⃣ CUST-002: اختبار حساب رصيد العميل

### 🎯 الهدف
التأكد من أن حسابات أرصدة العملاء دقيقة بعد إصلاح أسماء الحقول.

### 📋 السيناريو 1: إنشاء عميل جديد وتسجيل معاملات

**الخطوات:**

1. **إنشاء عميل جديد**
   ```
   POST /api/v1/customers
   {
     "name": "عميل اختبار الرصيد",
     "code": "TEST-001",
     "phone": "01012345678",
     "email": "test@example.com"
   }
   ```
   ✅ **المتوقع:** يتم إنشاء العميل بنجاح - الرصيد = 0

2. **إنشاء فاتورة مبيعات (إذن صرف)**
   ```
   POST /api/v1/issue-vouchers
   {
     "customer_id": <customer_id>,
     "branch_id": 1,
     "issue_date": "2025-11-03",
     "payment_type": "CREDIT",
     "items": [
       {
         "product_id": 1,
         "quantity": 5,
         "unit_price": 100
       }
     ]
   }
   ```
   ✅ **المتوقع:** 
   - الفاتورة تُنشأ بنجاح
   - رصيد العميل = 500 جنيه (عليه)

3. **تسجيل دفعة جزئية**
   ```
   POST /api/v1/payments
   {
     "customer_id": <customer_id>,
     "payment_date": "2025-11-03",
     "amount": 200,
     "payment_method": "CASH"
   }
   ```
   ✅ **المتوقع:** رصيد العميل = 300 جنيه (500 - 200)

4. **إنشاء مرتجع**
   ```
   POST /api/v1/return-vouchers
   {
     "customer_id": <customer_id>,
     "return_date": "2025-11-03",
     "items": [
       {
         "product_id": 1,
         "quantity": 2,
         "unit_price": 100
       }
     ]
   }
   ```
   ✅ **المتوقع:** رصيد العميل = 100 جنيه (300 - 200)

5. **التحقق من كشف الحساب**
   ```
   GET /api/v1/print/customer-statement/<customer_id>?from_date=2025-11-01&to_date=2025-11-30
   ```
   ✅ **المتوقع:**
   - الرصيد الافتتاحي = 0
   - إجمالي المدين (علية) = 500
   - إجمالي الدائن (له) = 400 (200 دفعة + 200 مرتجع)
   - الرصيد الختامي = 100

### 📋 السيناريو 2: اختبار calculateBalance()

**الخطوات:**

1. افتح `php artisan tinker`
2. نفذ:
   ```php
   $customer = \App\Models\Customer::first();
   $service = app(\App\Services\CustomerLedgerService::class);
   
   // اختبر الرصيد الحالي
   $balance = $service->calculateBalance($customer->id);
   echo "Current Balance: $balance\n";
   
   // اختبر الرصيد حتى تاريخ معين
   $balanceUpTo = $service->calculateBalance($customer->id, '2025-11-03');
   echo "Balance up to date: $balanceUpTo\n";
   ```

✅ **المتوقع:** 
- لا توجد SQL errors
- الأرصدة منطقية ومطابقة للمعاملات

### 🐛 اختبار الأخطاء المحتملة

**سيناريو خطأ SQL:**
```php
// في tinker
DB::enableQueryLog();
$balance = app(\App\Services\CustomerLedgerService::class)->calculateBalance(1);
dd(DB::getQueryLog());
```

✅ **المتوقع:** 
- Queries تستخدم `entry_date` ليس `transaction_date`
- Queries تستخدم `debit_aliah` و `credit_lah` ليس `debit` و `credit`

---

## 2️⃣ SALE-002: اختبار طرق الدفع الجديدة

### 🎯 الهدف
التأكد من أن طرق الدفع الجديدة (Vodafone Cash, InstaPay, Bank Account) تعمل بشكل صحيح.

### 📋 السيناريو 1: دفع بـ Vodafone Cash

**الخطوات:**

1. **إنشاء دفعة بـ Vodafone Cash**
   ```
   POST /api/v1/payments
   {
     "customer_id": 1,
     "payment_date": "2025-11-03",
     "amount": 500,
     "payment_method": "VODAFONE_CASH",
     "vodafone_number": "01012345678",
     "vodafone_reference": "VF-2025-001"
   }
   ```
   ✅ **المتوقع:** الدفعة تُسجل بنجاح

2. **اختبر validation - رقم خاطئ**
   ```
   POST /api/v1/payments
   {
     "payment_method": "VODAFONE_CASH",
     "vodafone_number": "1234567890",  // رقم غير مصري
     "amount": 100
   }
   ```
   ✅ **المتوقع:** 
   - Status: 422
   - Error: "رقم فودافون كاش غير صحيح (يجب أن يكون رقم مصري)"

3. **اختبر validation - حقل مفقود**
   ```
   POST /api/v1/payments
   {
     "payment_method": "VODAFONE_CASH",
     "amount": 100
     // vodafone_number مفقود
   }
   ```
   ✅ **المتوقع:**
   - Status: 422
   - Error: "رقم فودافون كاش مطلوب"

### 📋 السيناريو 2: دفع بـ InstaPay

**الخطوات:**

1. **إنشاء دفعة بـ InstaPay**
   ```
   POST /api/v1/payments
   {
     "customer_id": 1,
     "payment_date": "2025-11-03",
     "amount": 1000,
     "payment_method": "INSTAPAY",
     "instapay_reference": "IP-2025-11-03-001",
     "instapay_account": "accountname@bank"
   }
   ```
   ✅ **المتوقع:** الدفعة تُسجل بنجاح

2. **التحقق من قاعدة البيانات**
   ```sql
   SELECT * FROM payments 
   WHERE payment_method = 'INSTAPAY' 
   ORDER BY id DESC LIMIT 1;
   ```
   ✅ **المتوقع:** السجل موجود مع البيانات الصحيحة

### 📋 السيناريو 3: دفع بـ Bank Account

**الخطوات:**

1. **إنشاء دفعة بـ Bank Account**
   ```
   POST /api/v1/payments
   {
     "customer_id": 1,
     "payment_date": "2025-11-03",
     "amount": 2000,
     "payment_method": "BANK_ACCOUNT",
     "bank_account_number": "1234567890",
     "bank_account_name": "البنك الأهلي المصري",
     "bank_transaction_reference": "BA-2025-001"
   }
   ```
   ✅ **المتوقع:** الدفعة تُسجل بنجاح

### 🧪 اختبار Migration

**في Terminal:**
```bash
# اختبر rollback
php artisan migrate:rollback --step=1

# تحقق من الـ ENUM القديم
php artisan tinker
# DB::select("SHOW COLUMNS FROM payments WHERE Field = 'payment_method'");

# أعد الـ migration
php artisan migrate

# تحقق من الـ ENUM الجديد
php artisan tinker
# DB::select("SHOW COLUMNS FROM payments WHERE Field = 'payment_method'");
```

✅ **المتوقع:**
- بعد rollback: CASH, CHEQUE فقط
- بعد migrate: CASH, CHEQUE, VODAFONE_CASH, INSTAPAY, BANK_ACCOUNT

### 🎨 اختبار Frontend Utility

**في Browser Console (بعد تحميل الصفحة):**
```javascript
import { getPaymentMethodLabel, getPaymentMethodOptions } from './utils/paymentMethods';

// اختبر Labels
console.log(getPaymentMethodLabel('VODAFONE_CASH')); // "فودافون كاش"
console.log(getPaymentMethodLabel('INSTAPAY')); // "إنستاباي"
console.log(getPaymentMethodLabel('BANK_ACCOUNT')); // "حساب بنكي"

// اختبر Options
const options = getPaymentMethodOptions();
console.log(options);
// المتوقع: [{value: 'CASH', label: 'نقدي'}, ...]
```

---

## 3️⃣ SALE-006: اختبار Transactional Safety

### 🎯 الهدف
التأكد من أن جميع العمليات ذرية (atomic) ولا تحدث حفظات جزئية.

### 📋 السيناريو 1: فشل إنشاء إذن صرف في المنتصف

**الخطوات:**

1. **تعطيل منتج مؤقتاً في قاعدة البيانات**
   ```sql
   -- احفظ الـ ID قبل الحذف
   UPDATE products SET id = 99999 WHERE id = 1;
   ```

2. **حاول إنشاء إذن صرف**
   ```
   POST /api/v1/issue-vouchers
   {
     "customer_id": 1,
     "branch_id": 1,
     "issue_date": "2025-11-03",
     "items": [
       {
         "product_id": 1,  // منتج غير موجود
         "quantity": 5,
         "unit_price": 100
       }
     ]
   }
   ```

3. **تحقق من قاعدة البيانات**
   ```sql
   -- يجب ألا يوجد سجل في issue_vouchers
   SELECT COUNT(*) FROM issue_vouchers 
   WHERE created_at >= NOW() - INTERVAL 1 MINUTE;
   
   -- يجب ألا يوجد سجل في issue_voucher_items
   SELECT COUNT(*) FROM issue_voucher_items 
   WHERE created_at >= NOW() - INTERVAL 1 MINUTE;
   
   -- يجب ألا يوجد سجل في inventory_movements
   SELECT COUNT(*) FROM inventory_movements 
   WHERE created_at >= NOW() - INTERVAL 1 MINUTE;
   ```

✅ **المتوقع:**
- Status: 422 أو 500
- **لا توجد سجلات جزئية** في أي من الـ tables
- Error message واضح
- تم عمل Rollback بنجاح

4. **أعد المنتج**
   ```sql
   UPDATE products SET id = 1 WHERE id = 99999;
   ```

### 📋 السيناريو 2: اختبار Rollback في Payment

**الخطوات:**

1. **احفظ رصيد العميل الحالي**
   ```php
   $customer = \App\Models\Customer::find(1);
   $balanceBefore = $customer->balance;
   ```

2. **حاول إنشاء دفعة مع cheque غير صالح**
   ```
   POST /api/v1/payments
   {
     "customer_id": 1,
     "payment_method": "CHEQUE",
     "amount": 500,
     "cheque_number": "",  // فارغ - سيفشل
     "bank_name": "Test Bank"
   }
   ```

3. **تحقق من الرصيد**
   ```php
   $customer->refresh();
   $balanceAfter = $customer->balance;
   
   // يجب أن يكون الرصيد لم يتغير
   assert($balanceBefore === $balanceAfter);
   ```

✅ **المتوقع:**
- لا تغيير في رصيد العميل
- لا سجل جديد في payments table
- لا سجل جديد في customer_ledger_entries

### 🧪 اختبار Concurrent Transactions

**في Terminal (افتح 2 terminals):**

**Terminal 1:**
```bash
php artisan tinker
DB::transaction(function() {
    sleep(5); // انتظر 5 ثواني
    $customer = Customer::find(1);
    $customer->balance = 1000;
    $customer->save();
});
```

**Terminal 2 (خلال الـ 5 ثواني):**
```bash
php artisan tinker
$customer = Customer::find(1);
echo $customer->balance; // يجب أن يعرض القيمة القديمة
```

✅ **المتوقع:** 
- Terminal 2 لا يرى التغييرات من Terminal 1 حتى ينتهي الـ transaction
- Isolation level يعمل بشكل صحيح

---

## 4️⃣ CUST-001: اختبار PDF Export

### 🎯 الهدف
التأكد من أن PDF يتم تحميله مباشرة بدون فتح صفحة web.

### 📋 السيناريو 1: طباعة إذن صرف

**الخطوات:**

1. **افتح تفاصيل إذن صرف**
   ```
   Navigate to: /issue-vouchers/{id}
   ```

2. **اضغط على زر "طباعة"**
   - المفروض يفتح tab جديد

3. **تحقق من السلوك**
   ✅ **المتوقع:**
   - يفتح tab جديد
   - يبدأ تحميل PDF فوراً
   - اسم الملف: `issue-voucher-{voucher_number}.pdf`
   - PDF يحتوي على بيانات الإذن الصحيحة

4. **اختبر في متصفحات مختلفة**
   - ✅ Chrome: يحمل مباشرة
   - ✅ Firefox: يحمل مباشرة
   - ✅ Edge: يحمل مباشرة

### 📋 السيناريو 2: كشف حساب عميل

**الخطوات:**

1. **افتح صفحة العميل**
   ```
   Navigate to: /customers/{id}
   ```

2. **اضغط على "تصدير كشف حساب PDF"**

3. **تحقق من اسم الملف**
   ✅ **المتوقع:**
   - اسم الملف: `customer-statement-{customer_code}-2025-11-03.pdf`
   - يحتوي على timestamp
   - PDF يحتوي على:
     * بيانات العميل
     * الرصيد الافتتاحي
     * جميع المعاملات
     * الرصيد الختامي

### 🧪 اختبار API مباشرة

**في Browser أو Postman:**
```
GET http://localhost:8000/api/v1/print/customer-statement/1?from_date=2025-11-01&to_date=2025-11-30
Headers:
  Authorization: Bearer {token}
```

✅ **المتوقع:**
- Headers تحتوي على: `Content-Disposition: attachment; filename="..."`
- Content-Type: `application/pdf`
- الملف يُحمل مباشرة

### 🐛 اختبار HTML Preview (للـ debugging)

```
GET http://localhost:8000/api/v1/print/customer-statement/1?from_date=2025-11-01&to_date=2025-11-30&format=html
```

✅ **المتوقع:**
- يعرض HTML في المتصفح
- يمكن مراجعة البيانات قبل التحويل لـ PDF

---

## 5️⃣ PROD-003: اختبار Delete Button

### 🎯 الهدف
التأكد من أن حذف المنتجات يعمل بأمان مع الحماية المناسبة.

### 📋 السيناريو 1: حذف منتج بدون رصيد

**الخطوات:**

1. **أنشئ منتج جديد للاختبار**
   ```
   POST /api/v1/products
   {
     "name": "منتج للاختبار - سيتم حذفه",
     "sku": "TEST-DELETE-001",
     "classification": "other",
     "unit": "قطعة"
   }
   ```

2. **تأكد من عدم وجود رصيد**
   ```sql
   SELECT SUM(current_stock) FROM product_branches 
   WHERE product_id = <product_id>;
   ```
   - يجب أن يكون NULL أو 0

3. **احذف المنتج**
   ```
   DELETE /api/v1/products/{id}
   ```

✅ **المتوقع:**
- Status: 200
- Message: "تم حذف المنتج بنجاح"
- المنتج لا يظهر في القائمة

### 📋 السيناريو 2: محاولة حذف منتج لديه رصيد

**الخطوات:**

1. **اختر منتج لديه رصيد**
   ```sql
   SELECT p.id, p.name, SUM(pb.current_stock) as total_stock
   FROM products p
   JOIN product_branches pb ON p.id = pb.product_id
   GROUP BY p.id
   HAVING total_stock > 0
   LIMIT 1;
   ```

2. **حاول حذف المنتج**
   ```
   DELETE /api/v1/products/{id}
   ```

✅ **المتوقع:**
- Status: 422
- Message: "لا يمكن حذف المنتج. يوجد رصيد: X وحدة"
- المنتج لم يُحذف

### 📋 السيناريو 3: محاولة حذف منتج له حركات مخزنية

**الخطوات:**

1. **أنشئ منتج وأضف له حركة**
   ```
   POST /api/v1/products
   {
     "name": "منتج بحركات",
     "sku": "TEST-MOVEMENTS-001"
   }
   
   POST /api/v1/inventory-movements
   {
     "product_id": <product_id>,
     "branch_id": 1,
     "movement_type": "ADD",
     "qty_units": 10
   }
   ```

2. **صفّر الرصيد**
   ```
   POST /api/v1/inventory-movements
   {
     "product_id": <product_id>,
     "branch_id": 1,
     "movement_type": "ISSUE",
     "qty_units": 10
   }
   ```

3. **حاول حذف المنتج**
   ```
   DELETE /api/v1/products/{id}
   ```

✅ **المتوقع:**
- Status: 422
- Message: "لا يمكن حذف المنتج. يوجد حركات مخزنية مسجلة"
- المنتج لم يُحذف

### 🎨 اختبار Frontend

**الخطوات:**

1. **افتح صفحة المنتجات**
   ```
   Navigate to: /products
   ```

2. **ابحث عن منتج بدون رصيد**

3. **اضغط على زر الحذف (🗑️)**
   ✅ **المتوقع:**
   - يظهر confirmation dialog
   - الرسالة: "هل أنت متأكد من حذف هذا المنتج؟"

4. **اضغط "تأكيد"**
   ✅ **المتوقع:**
   - Loading spinner يظهر
   - المنتج يختفي من الجدول
   - Toast notification: "تم الحذف بنجاح"

5. **جرب مع منتج لديه رصيد**
   ✅ **المتوقع:**
   - Toast notification (error): "لا يمكن حذف المنتج. يوجد رصيد"
   - المنتج يبقى في الجدول

---

## 🚀 اختبارات التكامل الشاملة (E2E)

### 📋 سيناريو: دورة حياة كاملة للعميل

**الهدف:** اختبار جميع التحديثات مع بعضها في سيناريو واقعي.

**الخطوات:**

1. **إنشاء عميل جديد**
   - اسم: "عميل التكامل الكامل"
   - الرصيد المبدئي: 0

2. **إنشاء فاتورة آجلة (CREDIT)**
   - قيمة: 10,000 جنيه
   - تحقق: رصيد العميل = 10,000 (عليه)

3. **دفعة بـ Vodafone Cash**
   - قيمة: 3,000 جنيه
   - رقم: 01012345678
   - تحقق: رصيد العميل = 7,000

4. **دفعة بـ InstaPay**
   - قيمة: 2,000 جنيه
   - تحقق: رصيد العميل = 5,000

5. **مرتجع بضاعة**
   - قيمة: 1,000 جنيه
   - تحقق: رصيد العميل = 4,000

6. **دفعة بـ Bank Account**
   - قيمة: 4,000 جنيه
   - تحقق: رصيد العميل = 0

7. **طباعة كشف الحساب PDF**
   - تحقق: يُحمل مباشرة
   - تحقق: البيانات صحيحة
   - تحقق: الرصيد النهائي = 0

8. **محاولة حذف العميل**
   - المتوقع: فشل (لديه معاملات)

✅ **النتيجة المتوقعة:**
- جميع العمليات تنجح
- الأرصدة دقيقة في كل خطوة
- لا توجد SQL errors
- PDF يعمل بشكل صحيح
- الحماية من الحذف تعمل

---

## 📊 Checklist للمراجعة النهائية

### Backend
- [ ] جميع Migrations تعمل (up & down)
- [ ] جميع Validation Rules تعمل
- [ ] Transactional Safety مؤكد
- [ ] لا توجد SQL errors في logs
- [ ] Arabic error messages تظهر

### Frontend
- [ ] PDF يُحمل مباشرة
- [ ] Delete button يعمل مع confirmations
- [ ] Error handling يعمل
- [ ] Loading states تظهر
- [ ] Toast notifications تظهر

### Database
- [ ] Customer balances صحيحة
- [ ] Ledger entries صحيحة
- [ ] Payment methods الجديدة موجودة
- [ ] لا توجد orphaned records

### Integration
- [ ] سيناريو E2E كامل يعمل
- [ ] Cross-browser compatibility
- [ ] Mobile responsive (إن أمكن)

---

## 🐛 Log Files للمراجعة

**في حالة وجود مشاكل، راجع:**

```bash
# Laravel Logs
tail -f storage/logs/laravel.log

# Query Logs (في tinker)
DB::enableQueryLog();
# ... your operations
dd(DB::getQueryLog());

# Browser Console
F12 → Console Tab → انظر للأخطاء
```

---

## 📝 تقرير الاختبار

**بعد الانتهاء من الاختبارات، املأ:**

| السيناريو | النتيجة | ملاحظات |
|-----------|---------|----------|
| CUST-002: Balance | ✅ / ❌ | |
| SALE-002: Vodafone Cash | ✅ / ❌ | |
| SALE-002: InstaPay | ✅ / ❌ | |
| SALE-002: Bank Account | ✅ / ❌ | |
| SALE-006: Transactions | ✅ / ❌ | |
| CUST-001: PDF Export | ✅ / ❌ | |
| PROD-003: Delete | ✅ / ❌ | |
| E2E Integration | ✅ / ❌ | |

---

**تم إنشاؤه:** November 3, 2025  
**الإصدار:** 1.0  
**الحالة:** جاهز للاختبار 🚀
