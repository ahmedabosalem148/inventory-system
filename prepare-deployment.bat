@echo off
echo ========================================
echo تحضير نظام إدارة المخزون للنشر على Hostinger
echo ========================================
echo.

cd /d "c:\Users\DELL\Desktop\protfolio\inventory system\warehouse-app"

echo 1. مسح ملفات التخزين المؤقت...
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo ✅ تم مسح الملفات المؤقتة

echo.
echo 2. إنشاء ملفات التخزين المؤقت للإنتاج...
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo ✅ تم إنشاء ملفات التخزين المؤقت

echo.
echo 3. تحسين autoloader...
if exist "C:\composer\composer.phar" (
    php "C:\composer\composer.phar" install --no-dev --optimize-autoloader
    echo ✅ تم تحسين autoloader
) else if exist "composer.phar" (
    php composer.phar install --no-dev --optimize-autoloader
    echo ✅ تم تحسين autoloader
) else (
    echo ⚠️  Composer غير موجود. ستحتاج لتشغيل: composer install --no-dev --optimize-autoloader
)

echo.
echo 4. إنشاء ملف الضغط للنشر...
powershell -Command "if (Test-Path 'warehouse-app-deployment.zip') { Remove-Item 'warehouse-app-deployment.zip' -Force }"
powershell -Command "Compress-Archive -Path @('app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'storage', 'artisan', 'composer.json', 'composer.lock', '.htaccess-root', '.env.hostinger') -DestinationPath 'warehouse-app-deployment.zip' -Force"
echo ✅ تم إنشاء ملف warehouse-app-deployment.zip

echo.
echo 5. فحص الملفات المطلوبة...
if exist ".env.hostinger" echo ✅ ملف .env.hostinger موجود
if exist ".htaccess-root" echo ✅ ملف .htaccess-root موجود
if exist "HOSTINGER_GUIDE.md" echo ✅ دليل النشر موجود
if exist "warehouse-app-deployment.zip" echo ✅ ملف الضغط موجود

echo.
echo ========================================
echo 🎉 تم تحضير النظام للنشر بنجاح!
echo ========================================
echo.
echo الخطوات التالية:
echo 1. ارفع ملف warehouse-app-deployment.zip إلى Hostinger
echo 2. اتبع التعليمات في ملف HOSTINGER_GUIDE.md
echo 3. أعد تسمية .env.hostinger إلى .env وعدّل بياناتك
echo 4. أعد تسمية .htaccess-root إلى .htaccess في الجذر
echo 5. شغّل: php artisan migrate --seed
echo.
pause
