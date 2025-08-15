# 📋 دليل النشر على Hostinger - خطوة بخطوة

## 🎯 الخطوات المطلوبة

### 1. تحضير الملفات محلياً
```bash
# في مجلد المشروع
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. تحضير ملف .env للإنتاج
```env
APP_NAME="نظام إدارة المخزون"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_hostinger_db_name
DB_USERNAME=your_hostinger_db_user
DB_PASSWORD=your_hostinger_db_password

# PIN للدخول للوحة الإدارة (غيّره لأمان أكبر)
ADMIN_PIN_HASH=$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

## 🌐 خطوات Hostinger

### الخطوة 1: إنشاء قاعدة البيانات
1. اذهب إلى hPanel في Hostinger
2. اختر "MySQL Databases"
3. أنشئ قاعدة بيانات جديدة
4. أنشئ مستخدم جديد وأعطه صلاحيات كاملة
5. احفظ بيانات الاتصال

### الخطوة 2: رفع الملفات
#### الطريقة الأولى: File Manager
1. اذهب إلى File Manager في hPanel
2. احذف محتويات مجلد `public_html`
3. ارفع جميع ملفات المشروع إلى `public_html`
4. انقل محتويات مجلد `public` إلى `public_html`
5. احذف مجلد `public` الفارغ

#### الطريقة الثانية: FTP
```bash
# استخدم FileZilla أو أي برنامج FTP
Host: your-domain.com أو IP
Username: your-ftp-username
Password: your-ftp-password
Port: 21
```

### الخطوة 3: إعداد Laravel
1. انسخ ملف `.env.example` إلى `.env`
2. عدّل بيانات قاعدة البيانات في `.env`
3. في Terminal في hPanel أو SSH:

```bash
# الانتقال للمجلد
cd public_html

# إنشاء مفتاح التطبيق
php artisan key:generate --force

# تشغيل الهجرات
php artisan migrate --force

# إضافة المخازن
php artisan db:seed --class=ProductionWarehousesSeeder

# ربط التخزين
php artisan storage:link

# ضبط الصلاحيات
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## 🔧 إعدادات إضافية

### إعداد .htaccess في الجذر (إذا لزم)
إذا لم تستطع توجيه domain root إلى مجلد public، أضف في جذر public_html:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # إذا كان الطلب ليس لمجلد أو ملف موجود
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    
    # وجه كل شيء إلى index.php
    RewriteRule ^(.*)$ index.php [L]
</IfModule>
```

### حماية ملف .env
أضف في .htaccess:
```apache
# حماية ملف البيئة
<Files ".env">
    Order allow,deny
    Deny from all
</Files>
```

## 🧪 اختبار النشر

### اختبارات يجب القيام بها:
1. ✅ زيارة الموقع الرئيسي
2. ✅ تسجيل الدخول بـ PIN (1234)
3. ✅ الوصول للمخازن الثلاثة
4. ✅ تسجيل الدخول للمخازن بكلمات المرور
5. ✅ إضافة منتج جديد من الإدارة
6. ✅ إضافة/سحب مخزون من مخزن
7. ✅ اختبار التنبيهات الصوتية

## 🔒 تحسينات الأمان للإنتاج

### في .env:
```env
APP_DEBUG=false
APP_ENV=production

# غيّر PIN الإدارة
ADMIN_PIN_HASH=your_new_hash

# استخدم HTTPS
APP_URL=https://yourdomain.com
```

### إنشاء PIN جديد:
```php
# في Terminal محلياً
php -r "echo password_hash('your_new_pin', PASSWORD_DEFAULT);"
```

## 📱 كلمات مرور المخازن
- **العتبة:** `ataba123`
- **امبابة:** `imbaba123`
- **المصنع:** `factory123`

## 🆘 استكشاف الأخطاء

### خطأ 500:
```bash
# تحقق من الـ logs
tail -f storage/logs/laravel.log

# تأكد من الصلاحيات
chmod -R 755 storage/ bootstrap/cache/
```

### خطأ قاعدة البيانات:
- تحقق من بيانات .env
- تأكد من إنشاء قاعدة البيانات
- شغّل الهجرات مرة أخرى

### خطأ مفتاح التطبيق:
```bash
php artisan key:generate --force
```

## 🎉 النظام جاهز!
بعد هذه الخطوات، سيكون نظام إدارة المخزون يعمل بكامل طاقته على Hostinger!
