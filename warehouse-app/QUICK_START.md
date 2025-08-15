# 🚀 تشغيل سريع للنظام

## الطريقة الأسهل - Laravel Herd:
1. حمل Laravel Herd من: https://herd.laravel.com/
2. ثبته وسيثبت PHP تلقائياً
3. افتح Terminal في مجلد المشروع
4. شغل: `php artisan serve`
5. افتح: http://localhost:8000

## طريقة XAMPP:
1. حمل XAMPP من: https://www.apachefriends.org/
2. ثبته وشغل Apache + MySQL
3. افتح CMD في: C:\xampp\php
4. شغل: `php "c:\Users\DELL\Desktop\protfolio\inventory system\warehouse-app\artisan" serve`
5. افتح: http://localhost:8000

## معلومات تسجيل الدخول:
- **PIN:** 1234
- بعد تسجيل الدخول ستدخل لوحة التحكم

## المسارات المهمة:
- `/` - صفحة تسجيل الدخول
- `/admin/dashboard` - لوحة التحكم
- `/warehouses` - عرض المخازن
- `/admin/products` - إدارة المنتجات

## الـ API:
- `GET /api/warehouses` - قائمة المخازن
- `GET /api/warehouses/{id}/inventory` - مخزون مخزن محدد
- `POST /api/inventory/add` - إضافة مخزون
- `POST /api/inventory/withdraw` - سحب مخزون

## ملاحظات:
- النظام يدعم العربية RTL
- يوجد تنبيهات صوتية (يمكن كتمها)
- جاهز للنشر على Hostinger مباشرة
