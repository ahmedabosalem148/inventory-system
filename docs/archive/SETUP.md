# دليل إعداد المشروع - نظام إدارة المخزون

## ✅ TASK-001: إعداد البنية الأساسية (مكتمل جزئياً)

### 1. Laravel 12 ✓
- **الإصدار**: Laravel Framework 12.32.5
- **PHP**: 8.2.12
- **Composer**: 2.8.12

### 2. قاعدة البيانات
- **النوع**: SQLite (افتراضي للتطوير)
- **للإنتاج**: MySQL 8.x (سيتم إعداده لاحقاً على Hostinger)

### 3. الحزم المثبتة ✓

#### ✅ spatie/laravel-permission (v6.21.0)
- إدارة الأدوار والصلاحيات
- الأدوار: Manager, Store User, Accounting

#### ✅ barryvdh/laravel-dompdf (v3.1.1)
- طباعة PDF بالعربية
- سيتم إضافة خط Cairo/Amiri لاحقاً

#### ✅ spatie/laravel-activitylog (v4.10.2)
- سجل التدقيق (Activity Log)
- تسجيل الإنشاء/الاعتماد/الطباعة

#### ⚠️ maatwebsite/excel (v1.1.5)
**ملاحظة**: تم تنصيب إصدار قديم بسبب عدم توافق الإصدارات الأحدث مع Laravel 12.
- **البديل**: سنستخدم `phpoffice/phpspreadsheet` مباشرة لاحقاً
- **الحل**: إنشاء Wrapper مخصص للاستيراد/التصدير

### 4. الإعدادات المطلوبة التالية

#### ⏳ إعداد .env
```env
APP_NAME="نظام إدارة المخزون"
APP_TIMEZONE=Africa/Cairo
APP_LOCALE=ar
APP_FAKER_LOCALE=ar_SA

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=
```

#### ⏳ نشر configs للحزم
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

#### ⏳ Bootstrap 5.3 RTL
- تنزيل من CDN في layout.blade.php
- إعداد RTL بالكامل

### 5. Git Repository

#### ⏳ المطلوب
```bash
git init
git add .
git commit -m "Initial commit: Laravel 12 + Dependencies"
git branch develop
git checkout develop
```

---

## 🔄 الخطوات التالية (TASK-002)

1. إعداد MySQL وربطه بالمشروع
2. نشر configs للحزم
3. إنشاء Git repository
4. إعداد Bootstrap RTL
5. إنشاء أول Migration (branches)

---

## 📝 ملاحظات

### مشكلة maatwebsite/excel
الإصدار الحالي (v1.1.5) قديم ويستخدم `phpoffice/phpexcel` المهجورة.

**الحل المقترح**:
1. استخدام `phpoffice/phpspreadsheet` مباشرة
2. إنشاء Service class للاستيراد/التصدير
3. توثيق الكود بشكل واضح

**الكود المقترح** (سيتم إضافته في TASK-017):
```php
// app/Services/ExcelImportService.php
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportService {
    public function importProducts($file) {
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        // ... logic
    }
}
```

---

## ✅ Acceptance Criteria (TASK-001)

- [x] تنصيب Laravel 12 + PHP 8.2
- [x] ربط قاعدة بيانات (SQLite مؤقتاً)
- [x] تنصيب الحِزم: spatie/permission, dompdf, activitylog
- [ ] ⚠️ Excel: سنستخدم حل بديل
- [ ] إعداد `.env` (المنطقة الزمنية: Africa/Cairo)
- [ ] إنشاء مستودع Git (main, develop)
- [ ] إعداد Blade + Bootstrap 5.3 RTL

**الحالة**: 60% مكتمل
**المتبقي**: إعداد .env, Git, Bootstrap RTL

---

**تاريخ الإنشاء**: 2 أكتوبر 2025
**آخر تحديث**: 2 أكتوبر 2025
