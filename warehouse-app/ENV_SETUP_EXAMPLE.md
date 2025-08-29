# مثال لتعديل ملف .env للنشر على Hostinger

## الخطوات:

### 1. انسخ ملف .env.hostinger إلى .env
```bash
cp .env.hostinger .env
```

### 2. عدّل البيانات التالية في ملف .env:

```env
# معلومات الموقع
APP_NAME="نظام إدارة المخزون"
APP_URL=https://yourdomain.com  # ← ضع رابط موقعك هنا

# معلومات قاعدة البيانات
DB_DATABASE=u123456789_inventory  # ← ضع username حسابك + _inventory
DB_USERNAME=u123456789_invuser    # ← ضع username حسابك + _invuser  
DB_PASSWORD=your_strong_password  # ← ضع كلمة المرور التي اخترتها
```

## مثال حقيقي:

إذا كان:
- **موقعك:** `https://mystore.com`
- **username حسابك في Hostinger:** `u987654321`
- **كلمة مرور قاعدة البيانات:** `Store2025@Secure!`

فسيكون ملف `.env`:

```env
APP_NAME="نظام إدارة المخزون"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://mystore.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u987654321_inventory
DB_USERNAME=u987654321_invuser
DB_PASSWORD=Store2025@Secure!

# باقي الإعدادات تبقى كما هي...
```

## خطوات ما بعد التعديل:

### 3. أنشئ مفتاح التطبيق
```bash
php artisan key:generate
```

### 4. شغّل قاعدة البيانات
```bash
php artisan migrate --seed
```

### 5. تحسين الأداء
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## نصائح مهمة:

⚠️ **تأكد من:**
- username حسابك صحيح (من File Manager أو hPanel)
- كلمة مرور قاعدة البيانات صحيحة
- رابط الموقع صحيح (https:// مطلوب)

✅ **بعد الانتهاء:**
- احذف ملف `.env.hostinger` للأمان
- لا تشارك محتويات ملف `.env` مع أحد
- اعمل نسخة احتياطية من الإعدادات

---

**الآن أصبح النظام جاهز للعمل على Hostinger! 🚀**
