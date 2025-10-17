# 🚀 Quick Start Guide

## تشغيل المشروع

### 1. Backend (Laravel)
```powershell
# في terminal منفصل
php artisan serve
```
سيعمل على: http://127.0.0.1:8000

### 2. Frontend (React/Vite)
```powershell
# في terminal آخر
cd frontend/frontend
npm run dev
```
سيعمل على: http://localhost:5173

---

## 🔐 حسابات تجريبية

| المستخدم | البريد الإلكتروني | كلمة المرور |
|---------|-------------------|-------------|
| 👨‍💼 مدير النظام | `manager@inventory.local` | `password` |
| 💰 محاسب | `accounting@inventory.local` | `password` |
| 📦 أمين مخزن | `store1@inventory.local` | `password` |

---

## ⚠️ حل المشاكل الشائعة

### مشكلة 404 في Login
```powershell
# مسح الـ cache
php artisan route:clear
php artisan cache:clear
php artisan config:clear

# إعادة تشغيل الـ server
# أوقف الـ server (Ctrl+C)
php artisan serve
```

### مشكلة CORS
تأكد أن Backend شغال على `http://127.0.0.1:8000` أو `http://localhost:8000`

### الـ Frontend مش بيتصل بالـ Backend
1. تأكد Backend شغال: افتح http://localhost:8000
2. تأكد من `.env` في Frontend:
   ```
   VITE_API_URL=http://localhost:8000/api/v1
   ```

---

## 📝 أوامر مفيدة

```powershell
# فحص المستخدمين
php scripts/utilities/check_test_users.php

# عرض الـ routes
php artisan route:list

# عرض الـ logs
tail -f storage/logs/laravel.log  # Linux/Mac
Get-Content storage/logs/laravel.log -Wait  # Windows
```

---

## 🐳 Docker (اختياري)

```powershell
# تشغيل كل حاجة مرة واحدة
.\docker-start.ps1

# إيقاف
.\docker-stop.ps1
```

---

**آخر تحديث**: 17 أكتوبر 2025
