# دليل استكشاف المشاكل والحلول

## المشاكل الشائعة وحلولها

### 1. خطأ 500 Internal Server Error

**الأسباب المحتملة:**
- صلاحيات المجلدات خاطئة
- ملف .env غير موجود أو خاطئ
- مفتاح التطبيق غير موجود

**الحلول:**
```bash
# إعداد الصلاحيات
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# إنشاء مفتاح التطبيق
php artisan key:generate

# فحص ملفات اللوج
tail -f storage/logs/laravel.log
```

### 2. خطأ في قاعدة البيانات

**الرسالة:** `SQLSTATE[HY000] [1045] Access denied`

**الحل:**
1. تحقق من بيانات قاعدة البيانات في `.env`
2. تأكد من إنشاء قاعدة البيانات والمستخدم في cPanel
3. تأكد من إعطاء صلاحيات كاملة للمستخدم

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=correct_db_name
DB_USERNAME=correct_username
DB_PASSWORD=correct_password
```

### 3. الصفحات لا تظهر بشكل صحيح

**المشكلة:** CSS/JS لا يتحمل

**الحل:**
```bash
# تحقق من مسار الملفات
php artisan route:clear
php artisan config:clear

# تأكد من وجود .htaccess في public/
# تأكد من تفعيل mod_rewrite في الخادم
```

### 4. خطأ في الصوت

**المشكلة:** التنبيهات الصوتية لا تعمل

**الحل:**
1. تحقق من إعدادات المتصفح للصوت
2. تأكد من وجود ملف الصوت في `public/sounds/`
3. تحقق من Console في المتصفح للأخطاء

### 5. مشكلة في تسجيل الدخول

**المشكلة:** لا يمكن تسجيل الدخول للإدارة أو المخازن

**للإدارة:**
```bash
# تحقق من PIN في .env
php artisan tinker
Hash::make('1234') // للحصول على hash جديد
```

**للمخازن:**
```bash
php artisan tinker
$warehouse = App\Models\Warehouse::find(1);
$warehouse->password = Hash::make('password123');
$warehouse->save();
```

### 6. خطأ في الذاكرة

**الرسالة:** `Fatal error: Allowed memory size exhausted`

**الحل:**
1. زود حجم الذاكرة في `.htaccess`:
```apache
php_value memory_limit 256M
```

2. أو في `php.ini`:
```ini
memory_limit = 256M
```

### 7. مشكلة في السجلات (Sessions)

**المشكلة:** تسجيل الخروج التلقائي

**الحل:**
```bash
# تحقق من صلاحيات مجلد التخزين
chmod -R 755 storage/framework/sessions/

# في .env
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### 8. خطأ في Composer

**المشكلة:** `composer: command not found`

**الحل:**
1. ثبت Composer محلياً:
```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

2. أو اطلب من Hostinger تثبيت dependencies

### 9. مشكلة في ملفات الـ Cache

**المشكلة:** تغييرات لا تظهر

**الحل:**
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### 10. خطأ في SSL

**المشكلة:** Mixed content warnings

**الحل:**
1. تأكد من `APP_URL=https://yourdomain.com` في `.env`
2. أضف في `.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## أدوات التشخيص

### فحص النظام
```bash
# فحص إصدار PHP
php -v

# فحص الإضافات المطلوبة
php -m | grep -E "(bcmath|ctype|fileinfo|json|mbstring|openssl|pdo|tokenizer|xml)"

# فحص صلاحيات المجلدات
ls -la storage/
ls -la bootstrap/cache/
```

### فحص قاعدة البيانات
```bash
# اختبار الاتصال
php artisan tinker
DB::connection()->getPdo();
```

### فحص الملفات
```bash
# التأكد من وجود الملفات المطلوبة
ls -la .env
ls -la .htaccess
ls -la public/.htaccess
```

## الحصول على المساعدة

### ملفات اللوج
```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Apache/Nginx logs (اطلب من Hostinger)
tail -f /var/log/apache2/error.log
```

### معلومات النظام
```bash
# معلومات PHP
php artisan tinker
phpinfo();
```

### اختبار المكونات
```bash
# اختبار الـ routes
php artisan route:list

# اختبار قاعدة البيانات
php artisan migrate:status

# اختبار الـ config
php artisan config:show
```

## نصائح للأداء

1. **استخدم Cache:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. **حسِّن قاعدة البيانات:**
```sql
OPTIMIZE TABLE products, warehouses, inventory_movements;
```

3. **راقب الأداء:**
- استخدم أدوات مراقبة Hostinger
- فعّل compression في `.htaccess`
- استخدم CDN للملفات الثابتة

4. **أمان إضافي:**
```apache
# في .htaccess
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

## الدعم

📧 **للدعم التقني:**
- تواصل مع فريق Hostinger
- راجع وثائق Laravel
- فحص ملفات اللوج أولاً

🔧 **للتطوير:**
- استخدم `php artisan tinker` للاختبار
- فعّل `APP_DEBUG=true` في التطوير فقط
- استخدم `dd()` للتتبع

⚠️ **تذكر:**
- اعمل نسخة احتياطية قبل أي تغيير
- لا تفعّل DEBUG في الإنتاج
- غيّر كلمات المرور الافتراضية
