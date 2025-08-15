# نظام إدارة المخزون - Laravel 11

نظام إدارة مخزون باللغة العربية (RTL) مبني بـ Laravel 11 مع تصميم حديث وواجهة سهلة الاستخدام.

## المميزات

- 🏪 إدارة المخازن والمنتجات
- 📦 تتبع المخزون (كراتين + وحدات منفردة)
- ⚠️ تنبيهات صوتية للمنتجات تحت الحد الأدنى
- 🔊 نظام تحكم في الصوت (كتم/تشغيل)
- 📱 تصميم متجاوب (Responsive)
- 🔒 نظام حماية بكلمة مرور PIN
- 🌐 دعم كامل للغة العربية و RTL
- ⚡ واجهات AJAX سريعة
- 📊 لوحة تحكم مع إحصائيات شاملة

## متطلبات النظام

- PHP 8.2 أو أحدث
- MySQL 5.7 أو أحدث
- Composer
- مخدم ويب (Apache/Nginx)

## التثبيت المحلي

### 1. استنساخ المشروع
```bash
git clone [repository-url]
cd warehouse-app
```

### 2. تثبيت التبعيات
```bash
composer install
```

### 3. إعداد ملف البيئة
```bash
cp .env.example .env
```

قم بتحرير ملف `.env` وإضافة:
```env
APP_NAME="نظام إدارة المخزون"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=

# PIN للدخول للوحة الإدارة (1234)
ADMIN_PIN_HASH=$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

### 4. إنشاء قاعدة البيانات
```sql
CREATE DATABASE inventory_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. إنشاء مفتاح التطبيق
```bash
php artisan key:generate
```

### 6. تشغيل الهجرات
```bash
php artisan migrate
```

### 7. إنشاء البيانات التجريبية (اختياري)
```bash
php artisan db:seed
```

### 8. ربط التخزين
```bash
php artisan storage:link
```

### 9. تشغيل السيرفر
```bash
php artisan serve
```

أو باستخدام PHP المدمج:
```bash
php -S localhost:8000 -t public
```

الآن يمكنك زيارة: `http://localhost:8000`

## النشر على Hostinger

### 1. رفع الملفات
قم برفع جميع ملفات المشروع إلى مجلد `public_html` أو المجلد المخصص لموقعك.

### 2. إعداد متغيرات البيئة
```bash
# نسخ ملف البيئة
cp .env.example .env
```

قم بتحرير `.env` للإنتاج:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### 3. تثبيت التبعيات للإنتاج
```bash
composer install --no-dev --optimize-autoloader
```

### 4. إعداد Laravel للإنتاج
```bash
# إنشاء مفتاح التطبيق
php artisan key:generate --force

# تشغيل الهجرات
php artisan migrate --force

# ربط التخزين
php artisan storage:link

# بناء الكاشات
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. ضبط صلاحيات المجلدات
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### 6. توجيه Web Root (إذا أمكن)
في control panel الاستضافة، وجه document root إلى مجلد `public/`

### 7. البديل: استخدام .htaccess في الجذر
إذا لم يمكن توجيه web root، أضف هذا الملف في جذر المشروع:

**.htaccess (في الجذر)**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### 8. اختبار النشر
- تصفح الموقع للتأكد من عمله
- اختبر تسجيل الدخول بـ PIN (1234)
- تحقق من عمل قاعدة البيانات
- اختبر إضافة/سحب منتج

### 9. تحسينات الأمان للإنتاج
- غيّر `ADMIN_PIN_HASH` في `.env`
- تأكد من `APP_DEBUG=false`
- فعّل HTTPS
- احم ملف `.env` من الوصول المباشر

## استكشاف الأخطاء

### خطأ 500 Internal Server Error
```bash
# تحقق من logs
tail -f storage/logs/laravel.log

# تأكد من الصلاحيات
chmod -R 755 storage/ bootstrap/cache/
```

### خطأ قاعدة البيانات
- تحقق من بيانات الاتصال في `.env`
- تأكد من إنشاء قاعدة البيانات
- شغل الهجرات: `php artisan migrate`
- قم برفع جميع ملفات المشروع إلى مجلد موقعك
- تأكد من رفع المجلدات المخفية مثل `.htaccess`

### 2. إعداد Document Root
**الطريقة المفضلة:** غيّر Document Root إلى مجلد `public/`

**الطريقة البديلة:** إذا لم تستطع تغيير Document Root، فإن ملف `.htaccess` في الجذر سيقوم بإعادة التوجيه تلقائياً

### 3. إعداد قاعدة البيانات
- أنشئ قاعدة بيانات MySQL جديدة من لوحة التحكم
- احفظ بيانات الاتصال (اسم قاعدة البيانات، المستخدم، كلمة المرور)

### 4. تحديث ملف .env
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# إنشاء PIN hash جديد
ADMIN_PIN_HASH=[استخدم php artisan tinker و bcrypt('your_pin')]
```

### 5. تثبيت التبعيات على السيرفر
```bash
composer install --no-dev --optimize-autoloader
```

### 6. تشغيل الأوامر المطلوبة
```bash
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. ضبط الصلاحيات
تأكد من أن المجلدات التالية قابلة للكتابة:
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### 8. اختبار الموقع
- تفقد أن الموقع يعمل بشكل صحيح
- تأكد من عمل تسجيل الدخول بـ PIN
- اختبر إضافة/سحب المنتجات

## ملف الصوت

النظام يبحث عن ملف الصوت في:
- `public/sounds/alert.mp3`
- `public/sounds/alert.ogg`

إذا لم يجد الملف، سيستخدم Web Audio API لإنتاج صوت بديل.

لإضافة ملف صوت مخصص:
1. أنشئ مجلد `public/sounds/`
2. ضع ملف `alert.mp3` بداخله
3. تأكد من أن الملف أقل من 2MB

## إنشاء PIN جديد

لإنشاء PIN جديد للأدمن:
```bash
php artisan tinker
>>> bcrypt('1234')  // استبدل 1234 بـ PIN المطلوب
```

انسخ الناتج وضعه في `ADMIN_PIN_HASH` في ملف `.env`

## حل المشاكل الشائعة

### 1. خطأ 500 - Internal Server Error
- تحقق من ملف `storage/logs/laravel.log`
- تأكد من صلاحيات المجلدات
- تحقق من صحة ملف `.env`

### 2. قاعدة البيانات لا تتصل
- تحقق من بيانات الاتصال في `.env`
- تأكد من وجود قاعدة البيانات
- تحقق من صلاحيات المستخدم

### 3. الصفحة لا تظهر CSS/JS
- تحقق من مسار `APP_URL` في `.env`
- تأكد من وجود الملفات في `public/css/` و `public/js/`
- امسح الكاش: `php artisan cache:clear`

### 4. الصوت لا يعمل
- تحقق من وجود ملف `public/sounds/alert.mp3`
- تأكد من أن المتصفح يدعم تشغيل الصوت
- جرب تفعيل الصوت من زر 🔔

## الاستخدام

### 1. تسجيل الدخول
- ادخل على موقعك الرئيسي
- أدخل PIN الأدمن (افتراضي: 1234)

### 2. إدارة المخازن
- اضغط على "قائمة المخازن" من لوحة التحكم
- اختر مخزناً لعرض المنتجات

### 3. عمليات المخزون
- **إضافة مخزون:** اضغط "إضافة" بجانب المنتج
- **سحب مخزون:** اضغط "سحب" بجانب المنتج  
- **تعديل الحد الأدنى:** اضغط "تعديل الحد الأدنى"

### 4. التنبيهات الصوتية
- يتم تشغيل تنبيه صوتي للمنتجات تحت الحد الأدنى
- يمكن كتم/تفعيل الصوت من زر 🔔

## الدعم الفني

للدعم الفني أو الاستفسارات، يرجى فتح issue في المستودع أو التواصل مع فريق التطوير.

## الترخيص

هذا المشروع مرخص تحت رخصة MIT. راجع ملف `LICENSE` للمزيد من التفاصيل.
