@echo off
echo ================================
echo     تحضير المشروع للنشر
echo ================================
echo.

echo [1/5] تثبيت التبعيات للإنتاج...
call composer install --no-dev --optimize-autoloader --no-interaction

echo.
echo [2/5] مسح الكاشات القديمة...
call php artisan config:clear
call php artisan route:clear
call php artisan view:clear
call php artisan cache:clear

echo.
echo [3/5] بناء كاشات الإنتاج...
call php artisan config:cache
call php artisan route:cache
call php artisan view:cache

echo.
echo [4/5] تحسين autoloader...
call composer dump-autoload --optimize

echo.
echo [5/5] إنشاء ملف zip للرفع...
powershell Compress-Archive -Path "." -DestinationPath "../warehouse-app-production.zip" -Force

echo.
echo ✅ تم تحضير المشروع بنجاح!
echo.
echo 📦 ملف ZIP جاهز للرفع: warehouse-app-production.zip
echo 📋 ملف البيئة للإنتاج: .env.production
echo 📖 دليل النشر: HOSTINGER_DEPLOYMENT_GUIDE.md
echo.
echo 🔑 كلمات مرور المخازن:
echo    العتبة: ataba123
echo    امبابة: imbaba123
echo    المصنع: factory123
echo.
echo 🔐 PIN الإدارة الحالي: 1234
echo    (غيّره في الإنتاج لأمان أكبر)
echo.
pause
