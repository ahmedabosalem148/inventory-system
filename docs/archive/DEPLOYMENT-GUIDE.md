# 🚀 دليل النشر على Hostinger

دليل خطوة بخطوة لنشر نظام إدارة المخزون على **Hostinger Shared Hosting**.

---

## 📋 المحتويات

- [المتطلبات](#-المتطلبات)
- [التحضير قبل النشر](#-التحضير-قبل-النشر)
- [رفع الملفات](#-رفع-الملفات)
- [إعداد قاعدة البيانات](#-إعداد-قاعدة-البيانات)
- [ضبط الإعدادات](#-ضبط-الإعدادات)
- [إعداد Cron Jobs](#-إعداد-cron-jobs-اختياري)
- [الاختبار](#-الاختبار)
- [النسخ الاحتياطي](#-النسخ-الاحتياطي)
- [استكشاف الأخطاء](#-استكشاف-الأخطاء)

---

## ✅ المتطلبات

### في Hostinger
- **PHP**: 8.2 أو أعلى
- **MySQL**: 5.7 أو أعلى
- **SSH Access**: مُوصى به (اختياري)
- **Extensions**:
  - `php-mbstring`
  - `php-xml`
  - `php-gd`
  - `php-zip`
  - `php-mysql`

### تحقق من إصدار PHP
```
لوحة التحكم → Advanced → PHP Configuration
تأكد من اختيار PHP 8.2 أو أعلى
```

---

## 🔧 التحضير قبل النشر

### 1. تحسين المشروع محلياً

```bash
# في المشروع المحلي
cd inventory-system

# تحسين Composer للإنتاج
composer install --optimize-autoloader --no-dev

# مسح الـ Cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# تحسين الأداء
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. تحديث ملف `.env` للإنتاج

```env
APP_NAME="نظام المخزون"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database - سنعدلها بعد إنشاء قاعدة البيانات
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456_inventory
DB_USERNAME=u123456_inventoryuser
DB_PASSWORD=YourSecurePassword123!

# Session & Cache
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# Mail (اختياري)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=your_email@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# Timezone
APP_TIMEZONE=Africa/Cairo
```

### 3. تحديث `.gitignore`

تأكد من **عدم** رفع:
```
.env
/node_modules
/public/hot
/public/storage
/storage/*.key
/vendor
```

---

## 📤 رفع الملفات

### الطريقة 1: باستخدام File Manager (سهلة)

#### الخطوة 1: ضغط الملفات محلياً
```bash
# في مجلد المشروع
# احذف المجلدات غير الضرورية أولاً
rm -rf node_modules
rm -rf tests

# ضغط المشروع
zip -r inventory-system.zip . -x "*.git*" -x "node_modules/*" -x "tests/*"
```

#### الخطوة 2: رفع إلى Hostinger
```
1. لوحة التحكم → File Manager
2. انتقل إلى: domains/yourdomain.com/public_html/
3. رفع inventory-system.zip
4. انقر يمين → Extract
5. انقل محتويات المجلد المستخرج إلى public_html/
```

### الطريقة 2: باستخدام FTP (أسرع)

#### استخدام FileZilla
```
Host: ftp.yourdomain.com
Username: your_ftp_username
Password: your_ftp_password
Port: 21

1. اتصل بالسيرفر
2. الجانب الأيمن: انتقل إلى public_html/
3. الجانب الأيسر: اختر مجلد المشروع
4. اسحب وأفلت كل الملفات إلى public_html/
   ⚠️ قد يستغرق 10-30 دقيقة
```

### الطريقة 3: باستخدام SSH (الأسرع - مستحسن)

```bash
# 1. اتصل بـ SSH
ssh u123456@yourdomain.com

# 2. انتقل إلى public_html
cd domains/yourdomain.com/public_html

# 3. رفع من GitHub (إن كان المشروع على GitHub)
git clone https://github.com/your-repo/inventory-system.git .

# أو رفع ملف ZIP
# (رفع الـ ZIP أولاً عبر File Manager ثم)
unzip inventory-system.zip
rm inventory-system.zip

# 4. تثبيت الاعتماديات
composer install --optimize-autoloader --no-dev

# 5. الأذونات
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
```

---

## 🗄️ إعداد قاعدة البيانات

### الخطوة 1: إنشاء قاعدة بيانات MySQL

```
1. لوحة التحكم → Databases → MySQL Databases
2. اضغط "Create New Database"
   - Database Name: inventory_db
   - سينتج: u123456_inventory_db
3. احفظ الاسم الكامل
```

### الخطوة 2: إنشاء مستخدم

```
1. في نفس الصفحة → MySQL Users
2. اضغط "Create New User"
   - Username: inventoryuser
   - Password: أنشئ كلمة مرور قوية (احفظها!)
   - سينتج: u123456_inventoryuser
```

### الخطوة 3: ربط المستخدم بالقاعدة

```
1. في نفس الصفحة → Add User to Database
2. اختر المستخدم: u123456_inventoryuser
3. اختر القاعدة: u123456_inventory_db
4. اختر الصلاحيات: ALL PRIVILEGES
5. احفظ
```

### الخطوة 4: تحديث `.env`

```bash
# عبر File Manager أو SSH
nano .env

# عدّل القيم التالية:
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456_inventory_db
DB_USERNAME=u123456_inventoryuser
DB_PASSWORD=كلمة_المرور_التي_أنشأتها

# احفظ: Ctrl+X, Y, Enter
```

---

## ⚙️ ضبط الإعدادات

### 1. إعداد `.htaccess` في `public_html/`

أنشئ/عدّل `.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirect to public folder
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ /public/$1 [L,QSA]
</IfModule>

# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "(composer\.json|composer\.lock|\.env|\.git)">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 2. تشغيل Migrations

#### عبر SSH (مُوصى به)
```bash
cd domains/yourdomain.com/public_html

# تشغيل Migrations
php artisan migrate --force

# (اختياري) إدخال بيانات أولية
php artisan db:seed --force

# تحسين الأداء
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### عبر Web (إن لم يكن SSH متاحاً)

أنشئ ملف `install.php` في `public_html/`:

```php
<?php
// install.php - احذفه بعد الانتهاء!

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Run migrations
$kernel->call('migrate', ['--force' => true]);

// Run seeders (optional)
// $kernel->call('db:seed', ['--force' => true]);

// Cache configs
$kernel->call('config:cache');
$kernel->call('route:cache');
$kernel->call('view:cache');

echo "✅ Installation completed! Delete this file now.";
```

افتح: `https://yourdomain.com/install.php`  
**⚠️ احذف الملف فوراً بعد الانتهاء!**

### 3. ضبط الأذونات

```bash
# عبر SSH
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
chown -R u123456:u123456 storage bootstrap/cache

# أو عبر File Manager:
# انقر يمين على storage → Permissions → 755
# انقر يمين على storage/logs → Permissions → 775
```

---

## ⏰ إعداد Cron Jobs (اختياري)

للتنبيهات التلقائية والمهام المجدولة:

```
1. لوحة التحكم → Advanced → Cron Jobs
2. أضف Cron Job جديد:
   - Interval: Every 1 minute (أو حسب الحاجة)
   - Command:
     cd /home/u123456/domains/yourdomain.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### مهام مقترحة في `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // تنبيه يومي لأصناف أقل من الحد الأدنى
    $schedule->command('notify:low-stock')
        ->dailyAt('08:00');
    
    // تنبيه شيكات مستحقة قريباً
    $schedule->command('notify:upcoming-cheques')
        ->dailyAt('09:00');
    
    // نسخة احتياطية يومية
    $schedule->command('backup:run')
        ->dailyAt('02:00');
}
```

---

## ✅ الاختبار

### 1. فحص الموقع

افتح: `https://yourdomain.com`

**يجب أن ترى:**
- ✅ الصفحة الرئيسية (Dashboard)
- ✅ تصميم RTL صحيح
- ✅ Bootstrap يعمل
- ✅ لا رسائل خطأ

### 2. اختبار الوظائف الأساسية

```
✅ تسجيل دخول (إن وُجد)
✅ عرض المنتجات: /products
✅ عرض العملاء: /customers
✅ إنشاء إذن صرف: /issue-vouchers/create
✅ عرض التقارير: /reports/inventory
✅ طباعة PDF: اختبر أي تقرير
✅ استيراد CSV: /imports
```

### 3. فحص قاعدة البيانات

```sql
-- عبر phpMyAdmin في Hostinger
SELECT COUNT(*) FROM branches;      -- يجب أن ترى 3
SELECT COUNT(*) FROM categories;    -- حسب البيانات
SELECT COUNT(*) FROM products;      -- حسب البيانات
SELECT * FROM sequences;            -- تأكد من وجود 3 سجلات
```

### 4. فحص Logs

```bash
# عبر SSH
cd storage/logs
tail -f laravel.log

# أو عبر File Manager:
# افتح storage/logs/laravel.log وتحقق من عدم وجود أخطاء حرجة
```

---

## 💾 النسخ الاحتياطي

### نسخة احتياطية يدوية

#### 1. قاعدة البيانات
```
لوحة التحكم → Databases → phpMyAdmin
1. اختر القاعدة u123456_inventory_db
2. تبويب "Export"
3. Quick Export → SQL Format
4. تحميل
```

#### 2. الملفات
```bash
# عبر SSH
cd /home/u123456/domains/yourdomain.com
tar -czf inventory-backup-$(date +%Y%m%d).tar.gz public_html/

# تحميل عبر FTP/SFTP
```

### نسخة احتياطية تلقائية (مستحسنة)

#### استخدام Spatie Laravel Backup

```bash
# في المشروع محلياً
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"

# في config/backup.php:
'destination' => [
    'disks' => [
        'backup',  // سنعده في filesystems.php
    ],
],

# في config/filesystems.php:
'disks' => [
    'backup' => [
        'driver' => 'local',
        'root' => storage_path('app/backups'),
    ],
],

# إعداد Cron:
# في Console/Kernel.php
$schedule->command('backup:run')->daily()->at('02:00');

# رفع إلى Hostinger وإعادة الخطوات السابقة
```

---

## 🐛 استكشاف الأخطاء

### ❌ خطأ 500 - Internal Server Error

**الأسباب الشائعة:**

1. **أذونات ملفات خاطئة**
```bash
chmod -R 755 storage bootstrap/cache
chmod 644 .env
```

2. **Cache قديم**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

3. **فحص Logs**
```bash
tail -n 50 storage/logs/laravel.log
```

### ❌ الصفحة فارغة أو بيضاء

**الحل:**
```bash
# تفعيل عرض الأخطاء مؤقتاً
# في .env:
APP_DEBUG=true

# افتح الموقع وشاهد الخطأ
# ثم أعد APP_DEBUG=false بعد الإصلاح
```

### ❌ خطأ "CSRF token mismatch"

**الحل:**
```bash
# في .env تأكد من:
SESSION_DRIVER=file

# ثم:
php artisan config:clear
php artisan cache:clear

# تأكد من أذونات storage/framework/sessions:
chmod -R 775 storage/framework/sessions
```

### ❌ الصور/CSS لا تظهر

**الحل:**
```bash
# تأكد من أن Laravel يشير إلى public/:
# في .htaccess تأكد من:
RewriteRule ^(.*)$ /public/$1 [L,QSA]

# أو:
php artisan storage:link
```

### ❌ "Class not found" بعد رفع ملفات جديدة

**الحل:**
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### ❌ قاعدة البيانات لا تتصل

**الحل:**
```bash
# تحقق من .env:
DB_HOST=localhost  # ليس 127.0.0.1
DB_DATABASE=u123456_inventory_db  # الاسم الكامل مع البادئة
DB_USERNAME=u123456_inventoryuser
DB_PASSWORD=كلمة_المرور_الصحيحة

# ثم:
php artisan config:clear
```

### ❌ "Allowed memory size exhausted"

**الحل:**
```bash
# أنشئ .user.ini في public_html/:
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 20M
post_max_size = 25M

# انتظر 5 دقائق ليأخذ التأثير
```

### ❌ PDF فارغ أو لا يُطبع العربية

**الحل:**
```bash
# تأكد من أن خط DejaVu Sans موجود
# في vendor/dompdf/dompdf/lib/fonts/
# يجب أن يكون هناك: DejaVuSans.ttf

# إن لم يكن:
# حمّل الخط يدوياً ورفعه
```

---

## 📊 تحسين الأداء

### 1. Cache Configs

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Optimize Composer

```bash
composer install --optimize-autoloader --no-dev
```

### 3. تفعيل OPcache

في `.user.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### 4. استخدام CDN للـ Assets

في `layouts/app.blade.php`:
```html
<!-- Bootstrap من CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
```

### 5. تحسين Database

```sql
-- في phpMyAdmin
-- تحسين الجداول
OPTIMIZE TABLE products;
OPTIMIZE TABLE inventory_movements;
OPTIMIZE TABLE customer_ledger_entries;

-- إضافة Indexes (إن لم تكن موجودة)
ALTER TABLE inventory_movements ADD INDEX idx_created_at (created_at);
ALTER TABLE customer_ledger_entries ADD INDEX idx_customer_date (customer_id, date);
```

---

## 🔒 الأمان في الإنتاج

### 1. حماية `.env`

تأكد من أن `.htaccess` يمنع الوصول:
```apache
<FilesMatch "^\.env$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 2. HTTPS

```
1. لوحة التحكم → SSL/TLS
2. تفعيل "Force HTTPS Redirect"
3. احصل على شهادة SSL مجانية (Let's Encrypt)
```

في `.env`:
```env
APP_URL=https://yourdomain.com
SESSION_SECURE_COOKIE=true
```

### 3. إخفاء Laravel

في `public/index.php` أضف:
```php
// أعلى الملف
header('X-Powered-By: PHP');  // بدل Laravel
```

### 4. حماية من Brute Force

أضف في `.htaccess`:
```apache
# حد أقصى للطلبات
<IfModule mod_reqtimeout.c>
    RequestReadTimeout header=20-40,MinRate=500 body=20,MinRate=500
</IfModule>
```

---

## ✅ Checklist قبل الإطلاق

```
[ ] تحديث .env للإنتاج (APP_DEBUG=false)
[ ] إعداد قاعدة البيانات MySQL
[ ] رفع كل الملفات
[ ] تشغيل php artisan migrate
[ ] ضبط الأذونات (755/775)
[ ] إعداد .htaccess
[ ] تفعيل HTTPS
[ ] اختبار كل الوظائف الرئيسية
[ ] إعداد نسخة احتياطية تلقائية
[ ] إعداد Cron Jobs (اختياري)
[ ] فحص Logs (لا أخطاء)
[ ] تحسين الأداء (cache)
[ ] حماية الملفات الحساسة
[ ] توثيق بيانات الدخول
```

---

## 📞 الدعم

إذا واجهت مشاكل:

1. **فحص Logs**: `storage/logs/laravel.log`
2. **Hostinger Support**: اتصل بدعم Hostinger الفني
3. **Laravel Docs**: https://laravel.com/docs
4. **مجتمع Laravel**: https://laracasts.com/discuss

---

## 📝 ملاحظات إضافية

### تعدد المواقع (Multisite)

إن كنت تريد تشغيل المشروع في مجلد فرعي:

```
yourdomain.com/inventory/

في .htaccess:
RewriteBase /inventory/
RewriteRule ^(.*)$ /inventory/public/$1 [L,QSA]
```

### استخدام Subdomain

```
inventory.yourdomain.com

1. لوحة التحكم → Domains → Create Subdomain
2. Subdomain: inventory
3. Document Root: public_html/inventory/public
4. احفظ
```

---

<div align="center">

**🎉 تهانينا! مشروعك الآن على الهواء!**

![Success](https://img.shields.io/badge/Status-Live-success?style=for-the-badge)

**نظام إدارة المخزون | المصنع - العتبة - إمبابة**

</div>
