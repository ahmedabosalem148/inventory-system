@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2>إضافة فرع جديد</h2>
        </div>
    </div>

    {{-- عرض جميع الأخطاء --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> يوجد أخطاء في البيانات:</h5>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- عرض رسائل النجاح --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- عرض رسائل الخطأ --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-x-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('branches.store') }}" method="POST" id="branchForm">
                @csrf

                <div class="mb-3">
                    <label for="code" class="form-label">كود الفرع *</label>
                    <input type="text" 
                           class="form-control @error('code') is-invalid @enderror" 
                           id="code" 
                           name="code" 
                           value="{{ old('code') }}"
                           maxlength="20"
                           required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">مثال: FAC, ATB, IMB</div>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">اسم الفرع *</label>
                    <input type="text" 
                           class="form-control @error('name') is-invalid @enderror" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}"
                           maxlength="100"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" 
                           class="form-check-input" 
                           id="is_active" 
                           name="is_active"
                           {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        فرع نشط
                    </label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> حفظ
                    </button>
                    <a href="{{ route('branches.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/form-handler.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Branch Create Page Debug ===');
    
    const form = document.getElementById('branchForm');
    console.log('Form found:', form ? 'YES' : 'NO');
    console.log('Form action:', form?.action);
    console.log('CSRF token:', document.querySelector('input[name="_token"]')?.value.substring(0, 20) + '...');
    
    // Check for validation errors on page load
    const errors = document.querySelectorAll('.alert-danger, .invalid-feedback');
    if (errors.length > 0) {
        console.warn('⚠️ Validation errors found on page:', errors.length);
        errors.forEach((error, index) => {
            console.log(`Error ${index + 1}:`, error.textContent.trim());
        });
    }
    
    // Check for success messages
    const success = document.querySelector('.alert-success');
    if (success) {
        console.log('✓ Success message:', success.textContent.trim());
    }
    
    // Monitor form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('🚀 Form submitting...');
            console.log('Form data:');
            const formData = new FormData(form);
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }
        });
    }
    
    console.log('=== Debug End ===');
});
</script>
@endpush