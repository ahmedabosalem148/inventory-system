# 🚀 Quick Start - Docker

## تشغيل كل حاجة مرة واحدة

```powershell
.\docker-start.ps1
```

## إيقاف كل حاجة

```powershell
.\docker-stop.ps1
```

## الوصول للنظام

- 🌐 Frontend: http://localhost:5173
- 🔌 Backend: http://localhost:8000
- 💾 phpMyAdmin: http://localhost:8080

## أوامر مفيدة

```powershell
# إعادة تشغيل
.\docker-restart.ps1

# عرض اللوجز
.\docker-logs.ps1

# الدخول لـ Backend
docker exec -it inventory-backend sh

# تشغيل Artisan commands
docker exec -it inventory-backend php artisan migrate
```

## معلومات قاعدة البيانات

- Host: localhost:3306
- Database: inventory_db
- Username: inventory_user
- Password: secret123

---

📖 للتفاصيل الكاملة: [docs/DOCKER-README.md](docs/DOCKER-README.md)
