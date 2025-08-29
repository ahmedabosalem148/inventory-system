# 🛠️ دليل حل مشاكل نظام المخزون

## المشاكل المحتملة وحلولها:

### 1. مشكلة Authentication 🔐
**المشكلة:** لم يتم تسجيل الدخول للمخزن
**الحل:** 
- اذهب إلى: http://localhost:8000/warehouses
- اختر المخزن المطلوب
- أدخل كلمة المرور: `1234`

### 2. مشكلة Session 📝
**المشكلة:** Session cookies لا تُرسل مع API calls
**الحل:** تم إضافة `credentials: 'same-origin'` للـ fetch requests

### 3. مشكلة Middleware 🛡️
**المشكلة:** API routes غير محمية بـ warehouse auth
**الحل:** تم إضافة `warehouse.auth` middleware لجميع API routes

### 4. مشكلة JSON Response 📡
**المشكلة:** Middleware يُرجع HTML redirects بدلاً من JSON للـ API calls
**الحل:** تم تعديل middleware ليُرجع JSON errors للـ AJAX requests

### 5. مشكلة JavaScript Errors 🐛
**المشكلة:** Syntax errors في JavaScript
**الحل:** تم إصلاح جميع syntax errors وإضافة proper error handling

## التحقق من الحالة:

### فحص Session:
```
GET /debug/session/4
```

### فحص API:
```
GET /api/warehouses/4/inventory
```

### فحص المخزون:
```
GET /warehouses/4
```

## خطوات الاستكشاف:

1. **افتح Console في المتصفح** (F12)
2. **ابحث عن الأخطاء:** أي رسائل حمراء؟
3. **تحقق من Network tab:** هل API calls تتم؟
4. **تحقق من Response:** ما هو status code؟

## إعدادات السيرفر:

### تشغيل السيرفر:
```bash
cd "C:\Users\DELL\Desktop\protfolio\inventory system\warehouse-app"
C:\xampp\php\php.exe artisan serve --port=8000
```

### مسح Cache:
```bash
C:\xampp\php\php.exe artisan cache:clear
C:\xampp\php\php.exe artisan view:clear
```

## بيانات المخازن:

- **العتبة** (ID: 4)
- **امبابة** (ID: 5) 
- **المصنع** (ID: 6)

**كلمة المرور لجميع المخازن:** `1234`

## URLs مهمة:

- المخازن: http://localhost:8000/warehouses
- العتبة: http://localhost:8000/warehouses/4
- Admin: http://localhost:8000/admin/dashboard (PIN: 1234)

---
📝 **ملاحظة:** تم إضافة logging مفصل لجميع العمليات. افتح Console لرؤية التفاصيل.
