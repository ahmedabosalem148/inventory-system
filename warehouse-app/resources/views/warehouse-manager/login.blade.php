@extends('layouts.app')

@section('title')
تسجيل دخول - لوحة تحكم المخازن
@endsection

@section('content')
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">
                <i class="fa fa-warehouse"></i>
            </div>
            <h2>لوحة تحكم المخازن</h2>
            <p>أدخل كود PIN للوصول إلى لوحة تحكم المخازن</p>
        </div>

        @if(session('error'))
            <div class="alert alert-error">
                <i class="fa fa-exclamation-triangle"></i>
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="/warehouse-manager/login" class="login-form">
            @csrf
            <div class="input-group">
                <label for="pin">كود PIN</label>
                <input type="password" 
                       id="pin" 
                       name="pin" 
                       maxlength="10" 
                       required 
                       autofocus
                       placeholder="أدخل كود PIN">
                <i class="fa fa-lock input-icon"></i>
            </div>

            <button type="submit" class="btn-login">
                <i class="fa fa-sign-in"></i>
                دخول
            </button>
        </form>

        <div class="login-footer">
            <a href="/" class="back-link">
                <i class="fa fa-arrow-right"></i>
                العودة للصفحة الرئيسية
            </a>
        </div>
    </div>
</div>

<style>
.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
}

.login-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    padding: 40px;
    width: 100%;
    max-width: 400px;
    text-align: center;
}

.login-header {
    margin-bottom: 30px;
}

.login-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.login-icon i {
    font-size: 35px;
    color: white;
}

.login-header h2 {
    margin: 0 0 10px;
    color: #333;
    font-size: 24px;
    font-weight: 600;
}

.login-header p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.alert {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-error {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
}

.alert-success {
    background: #efe;
    color: #363;
    border: 1px solid #cfc;
}

.input-group {
    position: relative;
    margin-bottom: 25px;
    text-align: right;
}

.input-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
    font-size: 14px;
}

.input-group input {
    width: 100%;
    padding: 15px 50px 15px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.3s ease;
    direction: ltr;
    text-align: center;
    letter-spacing: 2px;
}

.input-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.input-icon {
    position: absolute;
    left: 15px;
    top: 43px;
    color: #999;
    font-size: 16px;
}

.btn-login {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.btn-login:active {
    transform: translateY(0);
}

.login-footer {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.back-link {
    color: #666;
    text-decoration: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: color 0.3s ease;
}

.back-link:hover {
    color: #667eea;
}

@media (max-width: 480px) {
    .login-card {
        padding: 30px 20px;
    }
    
    .login-header h2 {
        font-size: 20px;
    }
}
</style>
@endsection
