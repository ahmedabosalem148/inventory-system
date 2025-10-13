# 🎯 Quick Start Guide

## ✅ كيفية تشغيل المشروع

### 1. Backend (Laravel) - Port 8000
```bash
cd c:\Users\DELL\Desktop\protfolio\inventory-system
php artisan serve
```
✅ يجب أن يعمل على: `http://localhost:8000`

---

### 2. Frontend (React) - Port 3000
```bash
cd c:\Users\DELL\Desktop\protfolio\inventory-system\frontend
npm run dev
```
✅ يجب أن يعمل على: `http://localhost:3000`

---

## 🌐 URLs المهمة

| الوصف | URL | الاستخدام |
|-------|-----|-----------|
| **React App** | `http://localhost:3000` | ✅ افتح هذا في المتصفح |
| **Login Page** | `http://localhost:3000/login` | صفحة تسجيل الدخول |
| **Dashboard** | `http://localhost:3000/dashboard` | لوحة التحكم (بعد تسجيل الدخول) |
| **Laravel API** | `http://localhost:8000/api/v1` | للـ API فقط (لا تفتحه في المتصفح) |

---

## 🔐 بيانات تسجيل الدخول

```
Email: admin@inventory.test
Password: password
```

---

## ⚠️ أخطاء شائعة

### ❌ خطأ: فتح `http://localhost:8000/dashboard`
**السبب**: Laravel ليس لديه صفحة dashboard - هذا React app!
**الحل**: افتح `http://localhost:3000`

### ❌ خطأ: Service Worker errors
**السبب**: Service worker قديم من مشروع سابق
**الحل**: تم إضافة unregister script تلقائياً

### ❌ خطأ: 422 Login error  
**السبب**: بيانات تسجيل دخول خاطئة
**الحل**: استخدم `admin@inventory.test` و `password`

---

## 🚀 خطوات التشغيل الصحيحة

1. **شغل Laravel Backend**:
   ```bash
   php artisan serve
   ```
   انتظر حتى ترى: `Server started on http://localhost:8000`

2. **شغل React Frontend** (في terminal آخر):
   ```bash
   cd frontend
   npm run dev
   ```
   انتظر حتى ترى: `Local: http://localhost:3000/`

3. **افتح المتصفح**:
   - اذهب إلى: `http://localhost:3000`
   - اضغط Ctrl+Shift+R لمسح الـ cache
   - سجل دخول بالبيانات أعلاه

4. **استمتع! 🎉**

---

## 🔧 إذا ظهرت مشاكل

### Clear Browser Cache:
- Chrome: Ctrl+Shift+Delete
- أو: Hard refresh (Ctrl+Shift+R)

### Unregister Service Workers:
1. F12 → Application tab
2. Service Workers
3. Click "Unregister" لكل service worker

### Restart Everything:
```bash
# أغلق كل terminals
# ثم شغل من جديد:
php artisan serve
npm run dev
```

---

## ✅ علامات النجاح

عندما يعمل كل شيء صح، يجب أن ترى:

1. **في terminal Laravel**:
   ```
   INFO  Server running on [http://localhost:8000]
   ```

2. **في terminal React**:
   ```
   VITE v5.4.20  ready in XXX ms
   ➜  Local:   http://localhost:3000/
   ```

3. **في المتصفح على `localhost:3000`**:
   - صفحة login احترافية ✅
   - بدون أخطاء في Console ✅
   - يمكن تسجيل الدخول ✅
   - Dashboard يظهر بعد Login ✅

---

**آخر تحديث**: اليوم
**الحالة**: ✅ جاهز للتشغيل
