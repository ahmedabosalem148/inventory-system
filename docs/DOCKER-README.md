# 🐳 Docker Development Setup

## 📋 المتطلبات
- Docker Desktop (Windows/Mac) أو Docker Engine (Linux)
- Docker Compose v2.0+
- 4GB RAM على الأقل

---

## 🚀 التشغيل السريع (كل حاجة مرة واحدة!)

### ⚡ الطريقة الأسهل - PowerShell Script

```powershell
# تشغيل كل حاجة
.\docker-start.ps1
```

هيعمل كل ده تلقائي:
- ✅ يبني الـ containers
- ✅ يشغل كل الخدمات
- ✅ ينتظر MySQL
- ✅ يعمل migrations
- ✅ يعمل cache
- ✅ يجهز كل حاجة

### 🛑 الإيقاف

```powershell
.\docker-stop.ps1
```

### 🔄 إعادة التشغيل

```powershell
.\docker-restart.ps1

# أو لخدمة محددة
.\docker-restart.ps1 -Service backend
```

### 📝 عرض اللوجز

```powershell
# كل اللوجز
.\docker-logs.ps1

# لوجز خدمة محددة
.\docker-logs.ps1 -Service backend

# متابعة اللوجز مباشرة
.\docker-logs.ps1 -Follow
```

---

## 🛠️ التشغيل للمرة الأولى (Manual)

إذا كنت عايز تعمل كل حاجة يدوي:

### Windows (PowerShell):
```powershell
# 1. نسخ ملف البيئة
Copy-Item .env.example .env

# 2. تشغيل كل حاجة
docker-compose up -d --build

# 3. الانتظار (تلقائي في السكريبت)
Start-Sleep -Seconds 15

# خلاص! كل حاجة شغالة
```

### Linux/Mac (Makefile):
```bash
# تشغيل كل حاجة
make start

# إيقاف
make stop

# عرض المساعدة
make help
```

---

## 🌐 الروابط

| الخدمة | الرابط | الوصف |
|--------|--------|-------|
| Frontend | http://localhost:5173 | Vue.js/React Frontend |
| Backend API | http://localhost:8000 | Laravel API |
| phpMyAdmin | http://localhost:8080 | إدارة قاعدة البيانات |
| Nginx | http://localhost:80 | Web Server |
| Nginx | http://localhost:80 | Reverse Proxy |

## 🛠️ الأوامر المفيدة

### التشغيل اليومي:
```bash
# تشغيل جميع الخدمات
docker-compose up -d

# إيقاف جميع الخدمات
docker-compose down

# إعادة تشغيل
docker-compose restart

# عرض اللوجات
docker-compose logs -f

# لوجات خدمة معينة
docker-compose logs -f backend
docker-compose logs -f frontend
```

### الدخول للـ Container:
```bash
# Backend Shell
docker-compose exec backend sh

# Frontend Shell
docker-compose exec frontend sh

# MySQL Shell
docker-compose exec mysql mysql -u inventory_user -psecret123 inventory_db
```

### Laravel Commands:
```bash
# Migrations
docker-compose exec backend php artisan migrate
docker-compose exec backend php artisan migrate:fresh --seed

# Cache
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan route:clear

# Queue
docker-compose exec backend php artisan queue:work
```

### Frontend Commands:
```bash
# Install packages
docker-compose exec frontend npm install

# Build
docker-compose exec frontend npm run build
```

## 🗄️ قاعدة البيانات

### الاتصال من خارج Docker:
- Host: `localhost`
- Port: `3306`
- Database: `inventory_db`
- Username: `inventory_user`
- Password: `secret123`

### phpMyAdmin:
- URL: http://localhost:8080
- Server: `mysql`
- Username: `root`
- Password: `root123`

## 🔧 استكشاف الأخطاء

### المشروع لا يعمل:
```bash
# إعادة بناء الـ containers
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### MySQL لا يعمل:
```bash
# التحقق من حالة الخدمة
docker-compose ps

# عرض لوجات MySQL
docker-compose logs mysql

# إعادة تشغيل MySQL فقط
docker-compose restart mysql
```

### مشاكل الـ Permissions (Linux):
```bash
# إعطاء صلاحيات لـ storage
docker-compose exec backend chmod -R 775 storage bootstrap/cache
docker-compose exec backend chown -R www-data:www-data storage bootstrap/cache
```

## 🧹 التنظيف

```bash
# إيقاف وحذف كل شيء (مع البيانات)
docker-compose down -v

# حذف الـ images غير المستخدمة
docker system prune -f

# حذف كل شيء وإعادة البناء
make clean
make init
```

## 📦 Production Build

عند النشر على Hostinger:

### Frontend:
```bash
# بناء المشروع
docker-compose exec frontend npm run build

# الملفات في: frontend/frontend/dist
# ارفعها على public_html
```

### Backend:
```bash
# تجهيز الملفات
docker-compose exec backend php artisan config:cache
docker-compose exec backend php artisan route:cache
docker-compose exec backend php artisan view:cache

# ارفع كل ملفات Laravel ما عدا:
# - .git
# - node_modules
# - storage/logs/* (اترك المجلد فاضي)
# - .env (اعمل واحد جديد على السيرفر)
```

## 🎯 Tips

1. **Hot Reload**: يعمل تلقائياً بفضل Volumes
2. **Database Persistence**: البيانات محفوظة في Docker Volumes
3. **Multi-Container**: كل خدمة في Container منفصل
4. **Network Isolation**: الـ containers متصلة ببعض عبر شبكة خاصة
5. **Easy Reset**: `make clean && make init` يرجع كل حاجة للبداية

## 📞 الدعم

في حالة وجود مشاكل، افتح Issue على GitHub!
