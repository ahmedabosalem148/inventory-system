@extends('layouts.app')

@section('title', 'تسجيل الدخول - ' . $warehouse->name)
@section('page-title', 'تسجيل الدخول للمخزن')
@section('breadcrumb')
    <a href="/warehouses">المخازن</a> / {{ $warehouse->name }}
@endsection

@section('content')
    <div class="warehouse-login-container">
        <div class="warehouse-login-card">
            <div class="warehouse-login-header">
                <div class="warehouse-icon-container">
                    <div class="warehouse-icon">🏪</div>
                    <div class="warehouse-icon-bg"></div>
                </div>
                <h2>{{ $warehouse->name }}</h2>
                <p>أدخل كلمة المرور للوصول إلى مخزون هذا المخزن</p>
            </div>

            @if(session('error'))
                <div class="alert alert-danger">
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

            <form method="POST" action="/warehouses/{{ $warehouse->id }}/login" class="warehouse-login-form">
                @csrf
                
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-icon">
                            <i class="fa fa-key"></i>
                        </div>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="كلمة مرور المخزن"
                               required 
                               autofocus>
                    </div>
                    @error('password')
                        <div class="invalid-feedback">
                            <i class="fa fa-times-circle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-login">
                    <span class="btn-text">
                        <i class="fa fa-unlock"></i>
                        دخول المخزن
                    </span>
                    <span class="btn-loading" style="display: none;">
                        <i class="fa fa-spinner fa-spin"></i>
                        جاري الدخول...
                    </span>
                </button>
            </form>

            <div class="warehouse-login-footer">
                <a href="/warehouses" class="btn btn-link">
                    <i class="fa fa-arrow-right"></i>
                    العودة لقائمة المخازن
                </a>
            </div>
        </div>

        <!-- Warehouse Info Card -->
        <div class="warehouse-info-card">
            <h3>🔐 نظام حماية المخازن</h3>
            <div class="info-list">
                <div class="info-item">
                    <i class="fa fa-shield"></i>
                    <span>كل مخزن محمي بكلمة مرور خاصة</span>
                </div>
                <div class="info-item">
                    <i class="fa fa-user-lock"></i>
                    <span>وصول آمن لبيانات المخزون</span>
                </div>
                <div class="info-item">
                    <i class="fa fa-clock"></i>
                    <span>جلسة عمل محدودة لكل مخزن</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
.warehouse-login-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-xl);
    max-width: 900px;
    margin: 0 auto;
    padding: var(--spacing-lg);
    min-height: 500px;
    align-items: center;
}

.warehouse-login-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-xl);
    padding: var(--spacing-xl);
    border: 1px solid var(--gray-200);
    position: relative;
    overflow: hidden;
}

.warehouse-login-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--success) 100%);
}

.warehouse-login-header {
    text-align: center;
    margin-bottom: var(--spacing-xl);
}

.warehouse-icon-container {
    position: relative;
    display: inline-block;
    margin-bottom: var(--spacing-lg);
}

.warehouse-icon {
    font-size: 4rem;
    position: relative;
    z-index: 2;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
}

.warehouse-icon-bg {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--success) 100%);
    border-radius: 50%;
    opacity: 0.1;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: translate(-50%, -50%) scale(1); opacity: 0.1; }
    50% { transform: translate(-50%, -50%) scale(1.1); opacity: 0.2; }
    100% { transform: translate(-50%, -50%) scale(1); opacity: 0.1; }
}

.warehouse-login-header h2 {
    color: var(--primary);
    margin-bottom: var(--spacing-sm);
    font-size: 1.75rem;
    font-weight: 700;
}

.warehouse-login-header p {
    color: var(--gray-600);
    font-size: 1rem;
    line-height: 1.5;
}

.warehouse-login-form {
    margin-bottom: var(--spacing-lg);
}

.form-group {
    margin-bottom: var(--spacing-lg);
}

.input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    right: var(--spacing-md);
    z-index: 3;
    color: var(--gray-500);
    pointer-events: none;
}

.form-control {
    width: 100%;
    padding: var(--spacing-md) var(--spacing-xl) var(--spacing-md) var(--spacing-md);
    border: 2px solid var(--gray-300);
    border-radius: var(--border-radius-lg);
    font-size: 1.1rem;
    transition: all 0.3s ease;
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(49, 130, 206, 0.1);
    transform: translateY(-1px);
}

.form-control.is-invalid {
    border-color: var(--danger);
    box-shadow: 0 0 0 4px rgba(229, 62, 62, 0.1);
}

.invalid-feedback {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    color: var(--danger);
    font-size: 0.875rem;
    margin-top: var(--spacing-sm);
    padding: var(--spacing-sm);
    background: rgba(229, 62, 62, 0.05);
    border-radius: var(--border-radius);
    border: 1px solid rgba(229, 62, 62, 0.2);
}

.btn-login {
    position: relative;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border: none;
    padding: var(--spacing-md) var(--spacing-lg);
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: var(--border-radius-lg);
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(49, 130, 206, 0.3);
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(49, 130, 206, 0.4);
}

.btn-login:active {
    transform: translateY(0);
}

.btn-login:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.btn-block {
    width: 100%;
}

.warehouse-login-footer {
    text-align: center;
    padding-top: var(--spacing-lg);
    border-top: 1px solid var(--gray-200);
}

.btn-link {
    color: var(--gray-600);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.2s ease;
}

.btn-link:hover {
    color: var(--primary);
}

.warehouse-info-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border-radius: var(--border-radius-lg);
    padding: var(--spacing-xl);
    box-shadow: var(--shadow-lg);
}

.warehouse-info-card h3 {
    margin-bottom: var(--spacing-lg);
    font-size: 1.25rem;
    text-align: center;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

.info-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    background: rgba(255, 255, 255, 0.1);
    border-radius: var(--border-radius);
    transition: background 0.2s ease;
}

.info-item:hover {
    background: rgba(255, 255, 255, 0.15);
}

.info-item i {
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
}

.alert {
    padding: var(--spacing-md);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-lg);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    font-weight: 500;
}

.alert-danger {
    background: linear-gradient(135deg, #fee 0%, #fdd 100%);
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert-success {
    background: linear-gradient(135deg, #efe 0%, #dfd 100%);
    border: 1px solid #c3e6cb;
    color: #155724;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .warehouse-login-container {
        grid-template-columns: 1fr;
        gap: var(--spacing-lg);
        padding: var(--spacing-md);
    }
    
    .warehouse-icon {
        font-size: 3rem;
    }
    
    .warehouse-login-header h2 {
        font-size: 1.5rem;
    }
    
    .form-control {
        font-size: 1rem;
        padding: var(--spacing-md);
    }
    
    .btn-login {
        font-size: 1rem;
    }
}

/* Loading state for button */
.btn-loading {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.warehouse-login-form');
    const submitButton = document.querySelector('.btn-login');
    const btnText = submitButton.querySelector('.btn-text');
    const btnLoading = submitButton.querySelector('.btn-loading');
    const passwordInput = document.getElementById('password');

    // Form submission with loading state
    form.addEventListener('submit', function(e) {
        // Show loading state
        submitButton.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        
        // Add a small delay for better UX
        setTimeout(() => {
            // Form will submit normally
        }, 300);
    });

    // Password input improvements
    passwordInput.addEventListener('input', function() {
        // Remove invalid state when user starts typing
        this.classList.remove('is-invalid');
        
        // Remove any existing error messages
        const errorFeedback = document.querySelector('.invalid-feedback');
        if (errorFeedback && this.value.length > 0) {
            errorFeedback.style.opacity = '0';
        }
    });

    // Auto-focus on password field
    passwordInput.focus();

    // Add keyboard shortcut (Enter key)
    passwordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            form.submit();
        }
    });

    // Add some animation to form elements
    setTimeout(() => {
        const card = document.querySelector('.warehouse-login-card');
        const infoCard = document.querySelector('.warehouse-info-card');
        
        if (card) {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }
        
        if (infoCard) {
            infoCard.style.opacity = '0';
            infoCard.style.transform = 'translateY(20px)';
            infoCard.style.transition = 'all 0.6s ease';
            
            setTimeout(() => {
                infoCard.style.opacity = '1';
                infoCard.style.transform = 'translateY(0)';
            }, 300);
        }
    }, 50);
});
</script>
@endpush
