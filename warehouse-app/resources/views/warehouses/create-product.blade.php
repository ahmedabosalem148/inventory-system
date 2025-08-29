@extends('layouts.app')

@section('title')
إضافة منتج جديد - {{ $warehouse->name }}
@endsection

@section('page-title')
إضافة منتج جديد - {{ $warehouse->name }}
@endsection

@section('breadcrumb')
    <a href="/warehouses">قائمة المخازن</a> &lt; 
    <a href="/warehouses/{{ $warehouse->id }}">{{ $warehouse->name }}</a> &lt;
    إضافة منتج جديد
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fa fa-plus"></i>
            إضافة منتج جديد لمخزن {{ $warehouse->name }}
        </h3>
    </div>
    
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-right: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="/warehouses/{{ $warehouse->id }}/products" id="productForm">
            @csrf
            
            <div class="form-group">
                <label for="name" class="form-label required">اسم المنتج</label>
                <input type="text" 
                       class="form-input @error('name') form-input-error @enderror" 
                       id="name" 
                       name="name" 
                       value="{{ old('name') }}" 
                       required 
                       maxlength="255"
                       placeholder="أدخل اسم المنتج">
                @error('name')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="units_per_carton" class="form-label required">عدد الوحدات في كل كرتونة</label>
                    <input type="number" 
                           class="form-input @error('units_per_carton') form-input-error @enderror" 
                           id="units_per_carton" 
                           name="units_per_carton" 
                           value="{{ old('units_per_carton', 1) }}" 
                           min="1" 
                           required
                           placeholder="مثال: 24">
                    @error('units_per_carton')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="min_threshold" class="form-label">الحد الأدنى للتنبيه</label>
                    <input type="number" 
                           class="form-input @error('min_threshold') form-input-error @enderror" 
                           id="min_threshold" 
                           name="min_threshold" 
                           value="{{ old('min_threshold', 0) }}" 
                           min="0"
                           placeholder="0 = بدون تنبيه">
                    @error('min_threshold')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="cartons" class="form-label required">عدد الكراتين</label>
                <input type="number" 
                       class="form-input @error('cartons') form-input-error @enderror" 
                       id="cartons" 
                       name="cartons" 
                       value="{{ old('cartons', 1) }}" 
                       min="1" 
                       required
                       placeholder="أدخل عدد الكراتين">
                @error('cartons')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <!-- Hidden fields for unused values -->
            <input type="hidden" name="initial_cartons" value="0">
            <input type="hidden" name="initial_units" value="0">

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i>
                    حفظ المنتج
                </button>
                <a href="/warehouses/{{ $warehouse->id }}" class="btn btn-outline-secondary">
                    <i class="fa fa-times"></i>
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-label.required::after {
    content: " *";
    color: #e74c3c;
}

.form-input {
    width: 100%;
    padding: 12px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #fff;
}

.form-input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-input-error {
    border-color: #e74c3c;
}

.form-error {
    color: #e74c3c;
    font-size: 12px;
    margin-top: 5px;
}

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
    display: flex;
    gap: 15px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('productForm');
    const submitBtn = document.querySelector('button[type="submit"]');
    
    // Refresh CSRF token function
    async function refreshCsrfToken() {
        try {
            const response = await fetch('/csrf-token');
            if (response.ok) {
                const data = await response.json();
                // Update form token
                const tokenInput = form.querySelector('input[name="_token"]');
                if (tokenInput) {
                    tokenInput.value = data.token;
                }
                // Update meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.setAttribute('content', data.token);
                }
                console.log('✅ CSRF Token refreshed');
                return data.token;
            }
        } catch (error) {
            console.warn('❌ Failed to refresh CSRF token:', error);
        }
        return null;
    }
    
    // Handle form submission with CSRF refresh
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Show loading state
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري الحفظ...';
        
        try {
            // Refresh CSRF token before submission
            await refreshCsrfToken();
            
            // Submit form using fetch with proper CSRF
            const formData = new FormData(form);
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                // Success - show alert and redirect
                if (typeof App !== 'undefined' && App.showSuccessAlert) {
                    App.showSuccessAlert('تم إنشاء المنتج بنجاح! ✅\n📦 سيتم إعادة التوجيه...');
                } else {
                    alert('✅ تم إنشاء المنتج بنجاح!');
                }
                
                // Redirect to warehouse page
                setTimeout(() => {
                    window.location.href = '/warehouses/{{ $warehouse->id }}';
                }, 1500);
                
            } else {
                // Error - show details
                const errorText = await response.text();
                let errorMessage = 'فشل في إنشاء المنتج';
                
                try {
                    const errorData = JSON.parse(errorText);
                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                    if (errorData.errors) {
                        const errors = Object.values(errorData.errors).flat();
                        errorMessage += '\n\nالأخطاء:\n' + errors.join('\n');
                    }
                } catch (e) {
                    errorMessage += '\n\nتفاصيل الخطأ:\n' + response.status + ' ' + response.statusText;
                }
                
                if (typeof App !== 'undefined' && App.showErrorAlert) {
                    App.showErrorAlert('فشل في إنشاء المنتج', errorMessage);
                } else {
                    alert('❌ ' + errorMessage);
                }
            }
            
        } catch (error) {
            const errorMessage = 'خطأ في الاتصال: ' + error.message;
            if (typeof App !== 'undefined' && App.showErrorAlert) {
                App.showErrorAlert('خطأ في الاتصال', errorMessage);
            } else {
                alert('❌ ' + errorMessage);
            }
        } finally {
            // Restore button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    
    // Remove previous alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Check for success/error messages from session
    @if(session('success'))
        setTimeout(function() {
            if (typeof App !== 'undefined' && App.showSuccessAlert) {
                App.showSuccessAlert('{{ session('success') }}');
            } else {
                alert('✅ {{ session('success') }}');
            }
        }, 100);
    @endif
    
    @if(session('error'))
        setTimeout(function() {
            if (typeof App !== 'undefined' && App.showErrorAlert) {
                App.showErrorAlert('فشل في إضافة المنتج', '{{ session('error') }}');
            } else {
                alert('❌ {{ session('error') }}');
            }
        }, 100);
    @endif
    
    @if($errors->any())
        const errorMessages = @json($errors->all());
        const errorText = errorMessages.join('\n');
        setTimeout(function() {
            if (typeof App !== 'undefined' && App.showErrorAlert) {
                App.showErrorAlert('خطأ في البيانات المدخلة', errorText);
            } else {
                alert('❌ خطأ في البيانات:\n' + errorText);
            }
        }, 100);
    @endif
    
    // Auto-refresh CSRF token every 30 seconds to prevent expiry
    setInterval(refreshCsrfToken, 30000);
});
</script>
@endsection
