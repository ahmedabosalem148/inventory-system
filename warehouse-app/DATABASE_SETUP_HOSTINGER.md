# 🗄️ دليل إعداد قاعدة البيانات في Hostinger

## خطوات إنشاء قاعدة البيانات

### 1. الدخول إلى hPanel
- ادخل إلى حسابك في Hostinger
- اضغط على "Manage" للموقع المطلوب
- ستجد hPanel (لوحة التحكم)

### 2. إنشاء قاعدة البيانات
1. **في hPanel، ابحث عن "MySQL Databases"**
2. **اضغط على "Create Database"**
3. **أدخل اسم القاعدة (مثل: `inventory`)**
   - ⚠️ **مهم:** Hostinger سيضيف username حسابك تلقائياً
   - مثال: إذا أدخلت `inventory` سيصبح الاسم `u123456789_inventory`
4. **اضغط "Create Database"**

### 3. إنشاء مستخدم قاعدة البيانات
1. **في نفس الصفحة، اضغط "Create User"**
2. **أدخل اسم المستخدم (مثل: `invuser`)**
   - سيصبح: `u123456789_invuser`
3. **أدخل كلمة مرور قوية**
   - مثال: `Inv2025@Strong!`
4. **اضغط "Create User"**

### 4. ربط المستخدم بقاعدة البيانات
1. **في قسم "Add User to Database"**
2. **اختر المستخدم:** `u123456789_invuser`
3. **اختر قاعدة البيانات:** `u123456789_inventory`
4. **اعطِ صلاحيات "All Privileges"**
5. **اضغط "Add"**

## 📝 البيانات التي ستحتاجها

بعد إنشاء قاعدة البيانات، ستحصل على:

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_inventory
DB_USERNAME=u123456789_invuser  
DB_PASSWORD=Inv2025@Strong!
```

**مثال حقيقي:**
إذا كان username حسابك في Hostinger هو `u987654321`:
```env
DB_DATABASE=u987654321_inventory
DB_USERNAME=u987654321_invuser
DB_PASSWORD=[كلمة المرور التي اخترتها]
```

## 🔍 كيفية معرفة username حسابك

### الطريقة 1: من File Manager
- ادخل إلى File Manager في hPanel
- ستجد المسار: `/home/u123456789/public_html`
- الرقم `u123456789` هو username حسابك

### الطريقة 2: من MySQL Databases
- عند إنشاء أول قاعدة بيانات
- سيظهر لك اسم كامل مثل: `u123456789_database`
- الجزء الأول `u123456789` هو username حسابك

## ⚠️ نصائح مهمة

1. **احفظ البيانات في مكان آمن**
2. **استخدم كلمة مرور قوية**
3. **لا تشارك هذه البيانات مع أحد**
4. **تأكد من الأسماء الصحيحة قبل المتابعة**

## 🧪 اختبار الاتصال

بعد إعداد قاعدة البيانات، يمكنك اختبار الاتصال:

```php
// في ملف test-db.php
<?php
$host = 'localhost';
$username = 'u123456789_invuser';
$password = 'your_password';
$database = 'u123456789_inventory';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    echo "✅ الاتصال بقاعدة البيانات نجح!";
} catch(PDOException $e) {
    echo "❌ خطأ في الاتصال: " . $e->getMessage();
}
?>
```

## 📞 في حالة المشاكل

إذا واجهت صعوبة:
1. **تواصل مع دعم Hostinger** - لديهم دعم 24/7
2. **تحقق من الـ username** في File Manager
3. **تأكد من كلمة المرور** التي أدخلتها
4. **جرب إنشاء قاعدة بيانات جديدة** إذا لزم الأمر

---

**مع هذه البيانات ستتمكن من تعديل ملف `.env` وتشغيل النظام بنجاح! 🚀**
