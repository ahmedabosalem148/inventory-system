# 🚨 حل مشكلة 404 في Login

## المشكلة
```
POST http://localhost:8000/api/v1/auth/login 404 (Not Found)
```

## السبب
Backend (Laravel Server) **مش شغال**! 🛑

---

## ✅ الحل السريع

### الخطوة 1: شغّل Backend
افتح **Terminal 1** (PowerShell):
```powershell
cd C:\Users\DELL\Desktop\protfolio\inventory-system
php artisan serve
```

يجب أن تشوف:
```
INFO  Server running on [http://127.0.0.1:8000].
Press Ctrl+C to stop the server
```

### الخطوة 2: شغّل Frontend
افتح **Terminal 2** (PowerShell جديد):
```powershell
cd C:\Users\DELL\Desktop\protfolio\inventory-system\frontend\frontend
npm run dev
```

يجب أن تشوف:
```
Local:   http://localhost:5173/
```

### الخطوة 3: افتح المتصفح
```
http://localhost:5173
```

---

## 🔍 كيف تتأكد إن Backend شغال؟

### من المتصفح
افتح: http://localhost:8000

يجب أن تشوف صفحة Laravel

### من Terminal
```powershell
curl http://localhost:8000
```

---

## ⚠️ مشاكل شائعة

### 1. Port 8000 مستخدم
```powershell
# أوقف أي process على port 8000
netstat -ano | findstr :8000
taskkill /PID <رقم_الـPID> /F

# أو استخدم port تاني
php artisan serve --port=8001
```

ولو غيرت الـ port، غيّر في Frontend:
```env
# frontend/frontend/.env
VITE_API_URL=http://localhost:8001/api/v1
```

### 2. مشكلة Cache
```powershell
php artisan route:clear
php artisan cache:clear
php artisan config:clear
```

### 3. Frontend مش شغال
```powershell
cd frontend/frontend
npm install
npm run dev
```

---

## 📋 Checklist سريع

- [ ] Backend شغال؟ ✓ افتح http://localhost:8000
- [ ] Frontend شغال؟ ✓ افتح http://localhost:5173
- [ ] Cache نظيف؟ ✓ `php artisan cache:clear`
- [ ] التوكن موجود في Frontend؟ ✓ شوف Console

---

## 🎯 الحسابات التجريبية

```
📧 manager@inventory.local
🔒 password

📧 accounting@inventory.local
🔒 password

📧 store1@inventory.local
🔒 password
```

---

**💡 نصيحة**: خلّي Terminal Backend مفتوح دايماً وانت شغال!

**آخر تحديث**: 17 أكتوبر 2025
