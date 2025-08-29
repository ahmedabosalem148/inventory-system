<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول المشرف - نظام إدارة المخزون</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 32px;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 24px;
            text-align: right;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .pin-input {
            width: 100%;
            padding: 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
            transition: border-color 0.3s;
        }
        
        .pin-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-right: 4px solid #c33;
            text-align: right;
        }
        
        .security-note {
            margin-top: 24px;
            font-size: 12px;
            color: #888;
            line-height: 1.5;
        }

        .additional-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .access-link {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 12px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .access-link:hover {
            background: #e9ecef;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .access-link .icon {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .access-link span {
            font-size: 13px;
            font-weight: 600;
            text-align: center;
        }

        @media (max-width: 480px) {
            .additional-links {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">نظام إدارة المخزون</div>
        <div class="subtitle">دخول المشرف</div>
        
        @if(session('error'))
            <div class="error-message">
                {{ session('error') }}
            </div>
        @endif
        
        <form method="POST" action="/admin/login">
            @csrf
            
            <div class="form-group">
                <label for="pin">رقم المرور (6 أرقام)</label>
                <input 
                    type="password" 
                    id="pin" 
                    name="pin" 
                    class="pin-input"
                    inputmode="numeric" 
                    pattern="[0-9]{6}" 
                    maxlength="6"
                    placeholder="••••••"
                    required
                    autocomplete="off"
                >
            </div>
            
            <button type="submit" class="btn-login">دخول</button>
        </form>
        
        <div class="security-note">
            تأكد من عدم مشاركة رقم المرور مع أي شخص آخر<br>
            للحماية والأمان
        </div>

        <!-- Additional Access Links -->
        <div class="additional-links">
            <a href="/warehouse-manager" class="access-link">
                <i class="icon">🏭</i>
                <span>لوحة تحكم المخازن</span>
            </a>
            <a href="/warehouses" class="access-link">
                <i class="icon">🏪</i>
                <span>دخول المخازن</span>
            </a>
        </div>
    </div>
    
    <script>
        // Auto-focus PIN input
        document.getElementById('pin').focus();
        
        // Only allow numeric input
        document.getElementById('pin').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
