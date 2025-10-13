@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2>إضافة منتج جديد</h2>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('products.store') }}" method="POST">
                @csrf

                <div class="row">
                    <!-- بيانات المنتج الأساسية -->
                    <div class="col-md-6">
                        <h5 class="mb-3">بيانات المنتج</h5>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">التصنيف *</label>
                            <select class="form-select @error('category_id') is-invalid @enderror" 
                                    id="category_id" name="category_id" required>
                                <option value="">اختر التصنيف</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">اسم المنتج *</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" 
                                   value="{{ old('name') }}"
                                   maxlength="200" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">الوصف</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" 
                                      rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="unit" class="form-label">وحدة القياس *</label>
                            <input type="text" 
                                   class="form-control @error('unit') is-invalid @enderror" 
                                   id="unit" name="unit" 
                                   value="{{ old('unit', 'قطعة') }}"
                                   maxlength="50" required>
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">مثال: قطعة، متر، كيلو، علبة</div>
                        </div>
                    </div>

                    <!-- الأسعار والمخزون -->
                    <div class="col-md-6">
                        <h5 class="mb-3">الأسعار والمخزون</h5>

                        <div class="mb-3">
                            <label for="purchase_price" class="form-label">سعر الشراء (ج.م) *</label>
                            <input type="number" 
                                   class="form-control @error('purchase_price') is-invalid @enderror" 
                                   id="purchase_price" name="purchase_price" 
                                   value="{{ old('purchase_price', 0) }}"
                                   step="0.01" min="0" required>
                            @error('purchase_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="sale_price" class="form-label">سعر البيع (ج.م) *</label>
                            <input type="number" 
                                   class="form-control @error('sale_price') is-invalid @enderror" 
                                   id="sale_price" name="sale_price" 
                                   value="{{ old('sale_price', 0) }}"
                                   step="0.01" min="0" required>
                            @error('sale_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="min_stock" class="form-label">الحد الأدنى للمخزون *</label>
                            <input type="number" 
                                   class="form-control @error('min_stock') is-invalid @enderror" 
                                   id="min_stock" name="min_stock" 
                                   value="{{ old('min_stock', 10) }}"
                                   min="0" required>
                            @error('min_stock')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">سيتم التنبيه عند انخفاض المخزون عن هذا الحد</div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="is_active" name="is_active"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                منتج نشط
                            </label>
                        </div>
                    </div>
                </div>

                <!-- المخزون الأولي لكل فرع -->
                <hr class="my-4">
                <h5 class="mb-3">المخزون الأولي (اختياري)</h5>
                <div class="row">
                    @foreach($branches as $branch)
                        <div class="col-md-4 mb-3">
                            <label for="initial_stock_{{ $branch->id }}" class="form-label">
                                <i class="bi bi-building"></i> {{ $branch->name }}
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="initial_stock_{{ $branch->id }}" 
                                   name="initial_stock[{{ $branch->id }}]" 
                                   value="{{ old('initial_stock.'.$branch->id, 0) }}"
                                   min="0" placeholder="0">
                        </div>
                    @endforeach
                </div>

                <hr class="my-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> حفظ المنتج
                    </button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">
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
@endpush
