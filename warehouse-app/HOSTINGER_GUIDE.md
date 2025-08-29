# دليل رفع نظام إدارة المخزون على Hostinger

## الخطوات الأساسية

### 1. تحضير الملفات
✅ تم إنشاء ملف `warehouse-app-deployment.zip` 
✅ تم إنشاء ملف `.env.hostinger`
✅ تم إنشاء ملف `.htaccess-root`

### 2. رفع الملفات على Hostinger

1. **ادخل إلى cPanel الخاص بك في Hostinger**
2. **اذهب إلى File Manager**
3. **انتقل إلى مجلد `public_html`**
4. **اعمل نسخ احتياطي من الملفات الموجودة (إذا وجدت)**
5. **ارفع ملف `warehouse-app-deployment.zip`**
6. **فك ضغط الملف في `public_html`**
7. **انسخ محتويات مجلد `warehouse-app` إلى `public_html` مباشرة**

### 3. إعداد قاعدة البيانات

1. **في cPanel، اذهب إلى MySQL Databases**
2. **أنشئ قاعدة بيانات جديدة (مثل: `username_inventory`)**
3. **أنشئ مستخدم قاعدة بيانات**
4. **أعطِ المستخدم كامل الصلاحيات على القاعدة**
5. **احفظ بيانات الاتصال (اسم القاعدة، المستخدم، كلمة المرور)**

### 4. إعداد ملف البيئة

1. **انسخ ملف `.env.hostinger` إلى `.env`**
```bash
cp .env.hostinger .env
```

2. **عدّل الإعدادات التالية في `.env`:**
```env
APP_URL=https://yourdomain.com
DB_DATABASE=your_actual_db_name
DB_USERNAME=your_actual_db_user
DB_PASSWORD=your_actual_db_password
```

3. **أنشئ مفتاح التطبيق:**
```bash
php artisan key:generate
```

### 5. تشغيل قاعدة البيانات

```bash
# تشغيل الجداول
php artisan migrate

# إضافة البيانات الأساسية
php artisan db:seed
```

### 6. إعداد الصلاحيات

```bash
# إعداد صلاحيات المجلدات
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod -R 755 public/
```

### 7. إعداد .htaccess

1. **انسخ ملف `.htaccess-root` إلى `.htaccess` في الجذر:**
```bash
cp .htaccess-root .htaccess
```

2. **تأكد من وجود `.htaccess` في مجلد `public/` (موجود مسبقاً)**

### 8. تحسين الأداء

```bash
# تحسين الملفات للإنتاج
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 9. اختبار النظام

1. **اذهب إلى موقعك: `https://yourdomain.com`**
2. **تأكد من ظهور الصفحة الرئيسية**
3. **اختبر تسجيل الدخول للإدارة: `/admin`**
   - PIN افتراضي: `1234`
4. **اختبر تسجيل الدخول للمخازن: `/warehouses/{id}/login`**
   - كلمات المرور الافتراضية: `password123`

### 10. إعدادات الأمان

1. **غيّر PIN الإدارة:**
   - في `.env` غيّر `ADMIN_PIN_HASH`
   - استخدم: `php artisan tinker` ثم `Hash::make('your_new_pin')`

2. **غيّر كلمات مرور المخازن:**
   ```bash
   php artisan tinker
   ```
   ```php
   $warehouse = App\Models\Warehouse::find(1);
   $warehouse->password = Hash::make('new_password');
   $warehouse->save();
   ```

### 11. المراقبة والصيانة

- **تابع ملفات اللوج في `storage/logs/`**
- **راقب استخدام قاعدة البيانات**
- **اعمل نسخ احتياطية دورية**

## ملاحظات مهمة

⚠️ **تأكد من:**
- PHP 8.2+ مفعّل في Hostinger
- إضافات Laravel المطلوبة مثبتة (BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML)
- SSL مفعّل للموقع

🔧 **في حالة المشاكل:**
- تحقق من ملفات اللوج في `storage/logs/laravel.log`
- تأكد من صلاحيات المجلدات
- تحقق من إعدادات قاعدة البيانات

📞 **للدعم:**
- تواصل مع فريق دعم Hostinger إذا واجهت مشاكل في الخادم
- تحقق من وثائق Laravel 11 للمساعدة التقنية

## بعد التشغيل بنجاح

✅ ستتمكن من:
- إدارة المخزون من لوحة الإدارة
- تسجيل دخول منفصل لكل مخزن
- تلقي تنبيهات صوتية عند انخفاض المخزون
- استخدام API للتكامل مع أنظمة أخرى
- عرض تقارير مفصلة

🎉 **مبروك! نظام إدارة المخزون جاهز للعمل**
