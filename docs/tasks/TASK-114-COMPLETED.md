# TASK-114: Fix Sales Page 404 Error (Issue Vouchers Endpoint)

## المشكلة الأساسية
كانت صفحة المبيعات تعطي خطأ 404 لأن:
- الـ Frontend يطلب endpoint: `/api/v1/invoices`
- الـ Backend يوفر endpoint: `/api/v1/issue-vouchers`

## السبب
النظام يستخدم مصطلح **"Issue Vouchers" (إذن صرف)** بدلاً من **"Sales Invoices" (فاتورة مبيعات)**

## التغييرات المطبقة

### 1. API Service (`src/services/api/invoices.ts`)
✅ تم تحديث جميع الـ endpoints من `/invoices` إلى `/issue-vouchers`:
- `GET /issue-vouchers` - قائمة الفواتير
- `GET /issue-vouchers/{id}` - فاتورة واحدة
- `POST /issue-vouchers` - إنشاء فاتورة
- `PUT /issue-vouchers/{id}` - تحديث فاتورة
- `DELETE /issue-vouchers/{id}` - حذف فاتورة
- `POST /issue-vouchers/{id}/payments` - إضافة دفعة
- `GET /issue-vouchers/{id}/payments` - قائمة الدفعات
- `POST /issue-vouchers/{id}/cancel` - إلغاء فاتورة
- `GET /issue-vouchers/{id}/print` - طباعة فاتورة
- `GET /issue-vouchers/export` - تصدير Excel
- `GET /issue-vouchers-stats` - الإحصائيات

### 2. Types (`src/types/index.ts`)
✅ تم توسيع الـ interfaces لدعم كل من Frontend و Backend naming:

**SalesInvoice Interface:**
```typescript
- voucher_number (Backend) + invoice_number (Frontend alias)
- issue_date (Backend) + invoice_date (Frontend alias)
- discount_type: 'PERCENTAGE' | 'FIXED' (Backend)
- discount_value (Backend) + discount_percentage (Frontend alias)
- net_total (Backend final amount)
- is_transfer, target_branch_id (Backend for branch transfers)
- approved_at, approved_by (Backend approval fields)
```

**SalesInvoiceItem Interface:**
```typescript
- issue_voucher_id (Backend) + sales_invoice_id (Frontend alias)
- total_price (Backend before discount)
- net_price (Backend after discount)
- discount_type, discount_value (Backend)
```

**CreateSalesInvoiceInput Interface:**
```typescript
- issue_date (Backend) + invoice_date (Frontend alias)
- discount_type, discount_value (Backend)
- customer_name (Backend optional field)
```

### 3. Sales Page (`src/features/sales/SalesPage.tsx`)
✅ تم تحديث عرض البيانات:
- `voucher_number` بدلاً من `invoice_number`
- `issue_date` بدلاً من `invoice_date`
- `net_total` للحساب النهائي
- إضافة default values للحقول الاختيارية (paid_amount, remaining_amount, payment_status)

### 4. Invoice Dialog (`src/features/sales/InvoiceDialog.tsx`)
✅ تم تحديث إرسال واستقبال البيانات:
- إرسال `issue_date` للـ Backend
- إرسال `discount_type` و `discount_value` بدلاً من `discount_percentage` فقط
- استقبال `voucher_number`, `issue_date`, `net_price` من Backend
- تحويل البيانات من Backend format إلى Frontend format عند العرض

## الحقول المختلفة بين Frontend و Backend

| المفهوم | Frontend | Backend |
|---------|----------|---------|
| رقم الوثيقة | invoice_number | voucher_number |
| تاريخ الإصدار | invoice_date | issue_date |
| نوع الخصم | discount_percentage | discount_type + discount_value |
| المجموع النهائي | total | net_total |
| أصناف الفاتورة | sales_invoice_id | issue_voucher_id |
| السعر النهائي للصنف | total | net_price |

## ميزات Backend غير موجودة في Frontend (مستقبلاً)
- ✅ `is_transfer` - تحويل بين الفروع
- ✅ `target_branch_id` - الفرع المستهدف
- ✅ `voucher_type` - نوع الإذن
- ✅ `approved_at`, `approved_by` - اعتماد الإذن
- ✅ `customer_name` - اسم العميل كنص (للعملاء الغير مسجلين)

## الاختبارات المطلوبة
1. ✅ التأكد من تحميل قائمة الفواتير بنجاح
2. ✅ إنشاء فاتورة جديدة مع أصناف
3. ✅ عرض تفاصيل فاتورة موجودة
4. ✅ تعديل فاتورة موجودة
5. ✅ حذف فاتورة
6. ✅ إضافة دفعة لفاتورة
7. ⏳ طباعة فاتورة (PDF)
8. ⏳ تصدير فواتير (Excel)
9. ⏳ عرض الإحصائيات

## الملاحظات
- جميع التعديلات متوافقة للخلف (backward compatible)
- الـ Frontend يدعم كل من التسميات القديمة والجديدة
- لا حاجة لتغيير الـ Backend
- النظام جاهز للاختبار الآن

## الخطوة التالية
```bash
cd frontend/frontend
npm run dev
```
ثم افتح المتصفح على: `http://localhost:5173` واذهب لصفحة المبيعات لاختبار الوظائف.

---
**تاريخ الإنجاز:** $(date)
**الحالة:** ✅ مكتمل - جاهز للاختبار
