# 🔐 Login Testing Guide

## Test Credentials

After running `php artisan migrate:fresh --seed`, you can use these credentials:

### 1. Admin User
```
Email: admin@inventory.test
Password: password
```

### 2. Factory Manager
```
Email: factory@inventory.test
Password: password
```

### 3. Ataba Store Manager
```
Email: ataba@inventory.test
Password: password
```

## Testing Steps

1. **Start Laravel Backend**:
```bash
cd c:\Users\DELL\Desktop\protfolio\inventory-system
php artisan serve
```
Should run on: http://localhost:8000

2. **Start React Frontend**:
```bash
cd c:\Users\DELL\Desktop\protfolio\inventory-system\frontend
npm run dev
```
Should run on: http://localhost:3000

3. **Open Browser**:
- Navigate to http://localhost:3000
- You should see the login page
- Use any credentials above

4. **Check for Errors**:
- Open browser DevTools (F12)
- Go to Console tab
- Check for any red errors

## Common Issues & Solutions

### Issue 1: 422 Unprocessable Content
**Problem**: Email/password validation failing
**Solution**: Make sure you're using correct test credentials

### Issue 2: CORS Error
**Problem**: `Access-Control-Allow-Origin` header missing
**Solution**: Already fixed with `config/cors.php`

### Issue 3: 401 Unauthorized
**Problem**: Token not being sent
**Solution**: Check `AuthContext.jsx` sets Authorization header

### Issue 4: Network Error
**Problem**: Backend not running
**Solution**: Start Laravel with `php artisan serve`

## API Endpoint Tests

You can also test directly with curl:

```bash
# Test Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"admin@inventory.test\",\"password\":\"password\"}"
```

Expected Response:
```json
{
  "message": "تم تسجيل الدخول بنجاح",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@inventory.test",
    ...
  },
  "token": "1|abc123...",
  "token_type": "Bearer"
}
```

## What Was Fixed

1. ✅ **AuthContext.jsx**: Fixed response data structure
   - Changed from `response.data.data` to `response.data`
   - Added proper error handling for 422 validation errors

2. ✅ **LoginPage.jsx**: Updated demo credentials
   - Changed to `admin@inventory.test`

3. ✅ **CORS Configuration**: Created `config/cors.php`
   - Allowed all origins for development
   - Enabled API routes

4. ✅ **Sanctum Configuration**: Published and configured
   - Added localhost:3000 to stateful domains

5. ✅ **Database**: Reset and seeded
   - Removed duplicate migration
   - Fresh data with test users

## Next Steps

After successful login:
- You should be redirected to `/dashboard`
- Dashboard should display with sidebar and navbar
- KPI cards should show mock data
- Branch selector should work
- User menu should show your name

## Debugging Tips

If still having issues, check:

1. **Network Tab in DevTools**:
   - Look at the login request
   - Check request payload
   - Check response status and body

2. **Console Errors**:
   - Any JavaScript errors?
   - Any API errors?

3. **Laravel Logs**:
```bash
tail -f storage/logs/laravel.log
```

4. **Check Token Storage**:
```javascript
// In browser console
localStorage.getItem('token')
```

---

**Status**: ✅ Ready for testing!
