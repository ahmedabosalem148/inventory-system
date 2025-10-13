@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2>تعديل المنتج: {{ $product->name }}</h2>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('products.update', $product) }}" method="POST">
                @csrf
                @method('PUT')

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
                                    <option value="{{ $category->id }}" 
                                        {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
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
                                   value="{{ old('name', $product->name) }}"
                                   maxlength="200" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">الوصف</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" 
                                      rows="3">{{ old('description', $product->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="unit" class="form-label">وحدة القياس *</label>
                            <input type="text" 
                                   class="form-control @error('unit') is-invalid @enderror" 
                                   id="unit" name="unit" 
                                   value="{{ old('unit', $product->unit) }}"
                                   maxlength="50" required>
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                   value="{{ old('purchase_price', $product->purchase_price) }}"
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
                                   value="{{ old('sale_price', $product->sale_price) }}"
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
                                   value="{{ old('min_stock', $product->min_stock) }}"
                                   min="0" required>
                            @error('min_stock')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="is_active" name="is_active"
                                   {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                منتج نشط
                            </label>
                        </div>
                    </div>
                </div>

                <!-- عرض المخزون الحالي -->
                <hr class="my-4">
                <h5 class="mb-3">المخزون الحالي</h5>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    لتعديل كميات المخزون، استخدم أذون الصرف أو الإرجاع
                </div>
                <div class="row">
                    @foreach($branches as $branch)
                        @php
                            $stock = $product->branchStocks->firstWhere('branch_id', $branch->id);
                        @endphp
                        <div class="col-md-4 mb-3">
                            <div class="card {{ $stock && $stock->current_stock < $product->min_stock ? 'border-warning' : '' }}">
                                <div class="card-body">
                                    <h6>
                                        <i class="bi bi-building"></i> {{ $branch->name }}
                                    </h6>
                                    <h3 class="mb-0">
                                        {{ $stock ? $stock->current_stock : 0 }} {{ $product->unit }}
                                    </h3>
                                    @if($stock && $stock->current_stock < $product->min_stock)
                                        <small class="text-warning">
                                            <i class="bi bi-exclamation-triangle"></i> منخفض
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <hr class="my-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> حفظ التعديلات
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
