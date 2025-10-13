@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2>تعديل الفرع: {{ $branch->name }}</h2>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('branches.update', $branch) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="code" class="form-label">كود الفرع *</label>
                    <input type="text" 
                           class="form-control @error('code') is-invalid @enderror" 
                           id="code" 
                           name="code" 
                           value="{{ old('code', $branch->code) }}"
                           maxlength="20"
                           required
                           {{ in_array($branch->code, ['FAC', 'ATB', 'IMB']) ? 'readonly' : '' }}>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if(in_array($branch->code, ['FAC', 'ATB', 'IMB']))
                        <div class="form-text text-warning">لا يمكن تعديل كود الفروع الأساسية</div>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">اسم الفرع *</label>
                    <input type="text" 
                           class="form-control @error('name') is-invalid @enderror" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $branch->name) }}"
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
                           {{ old('is_active', $branch->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        فرع نشط
                    </label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> حفظ التعديلات
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