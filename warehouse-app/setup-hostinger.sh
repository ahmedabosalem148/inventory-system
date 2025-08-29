#!/bin/bash

echo "========================================"
echo "إعداد نظام إدارة المخزون على Hostinger"
echo "========================================"
echo

# التأكد من وجود PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP غير مثبت أو غير موجود في PATH"
    exit 1
fi

echo "1. إعداد ملف البيئة..."
if [ -f ".env.hostinger" ]; then
    cp .env.hostinger .env
    echo "✅ تم نسخ ملف .env"
else
    echo "❌ ملف .env.hostinger غير موجود"
    exit 1
fi

echo
echo "2. إنشاء مفتاح التطبيق..."
php artisan key:generate --force
echo "✅ تم إنشاء مفتاح التطبيق"

echo
echo "3. إعداد صلاحيات المجلدات..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chmod -R 755 public/
echo "✅ تم إعداد الصلاحيات"

echo
echo "4. إعداد .htaccess..."
if [ -f ".htaccess-root" ]; then
    cp .htaccess-root .htaccess
    echo "✅ تم نسخ ملف .htaccess"
else
    echo "⚠️  ملف .htaccess-root غير موجود"
fi

echo
echo "5. تحسين الأداء..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ تم تحسين الأداء"

echo
echo "6. إعداد قاعدة البيانات..."
echo "تأكد من تعديل بيانات قاعدة البيانات في ملف .env قبل المتابعة"
echo "هل تريد تشغيل الجداول الآن؟ (y/n)"
read -r response
if [[ "$response" =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    echo "✅ تم إنشاء الجداول"
    
    echo "هل تريد إضافة البيانات الأساسية؟ (y/n)"
    read -r seed_response
    if [[ "$seed_response" =~ ^[Yy]$ ]]; then
        php artisan db:seed --force
        echo "✅ تم إضافة البيانات الأساسية"
    fi
else
    echo "⚠️  تذكر تشغيل: php artisan migrate --seed لاحقاً"
fi

echo
echo "========================================"
echo "🎉 تم إعداد النظام بنجاح!"
echo "========================================"
echo
echo "معلومات مهمة:"
echo "- رابط الإدارة: /admin"
echo "- PIN الافتراضي: 1234"
echo "- رابط المخازن: /warehouses/{id}/login"
echo "- كلمة المرور الافتراضية: password123"
echo
echo "⚠️  لا تنس:"
echo "1. تغيير PIN الإدارة"
echo "2. تغيير كلمات مرور المخازن"
echo "3. تفعيل SSL"
echo "4. مراجعة ملفات اللوج"
echo
