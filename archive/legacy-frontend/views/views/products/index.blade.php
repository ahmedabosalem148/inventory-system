@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>إدارة المنتجات</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('products.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> إضافة منتج جديد
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- البحث والفلترة -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('products.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">البحث بالاسم</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="ابحث عن منتج...">
                </div>
                <div class="col-md-3">
                    <label for="category_id" class="form-label">التصنيف</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">الكل</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" 
                                {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="is_active" class="form-label">الحالة</label>
                    <select class="form-select" id="is_active" name="is_active">
                        <option value="">الكل</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>نشط</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>غير نشط</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> بحث
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول المنتجات -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم المنتج</th>
                            <th>التصنيف</th>
                            <th>الوحدة</th>
                            <th>سعر الشراء</th>
                            <th>سعر البيع</th>
                            <th>المخزون</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $loop->iteration + ($products->currentPage() - 1) * $products->perPage() }}</td>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                    @if($product->description)
                                        <br><small class="text-muted">{{ Str::limit($product->description, 40) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $product->category->name }}</span>
                                </td>
                                <td>{{ $product->unit }}</td>
                                <td>{{ number_format($product->purchase_price, 2) }} ج.م</td>
                                <td>{{ number_format($product->sale_price, 2) }} ج.م</td>
                                <td>
                                    @php
                                        $totalStock = $product->branchStocks->sum('current_stock');
                                        $lowStock = $totalStock < $product->min_stock;
                                    @endphp
                                    <span class="badge {{ $lowStock ? 'bg-warning' : 'bg-success' }}">
                                        {{ $totalStock }} {{ $product->unit }}
                                    </span>
                                    @if($lowStock)
                                        <i class="bi bi-exclamation-triangle text-warning"></i>
                                    @endif
                                    <br>
                                    <small class="text-muted">
                                        @foreach($product->branchStocks as $stock)
                                            {{ $stock->branch->code }}: {{ $stock->current_stock }}
                                            @if(!$loop->last) | @endif
                                        @endforeach
                                    </small>
                                </td>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge bg-success">نشط</span>
                                    @else
                                        <span class="badge bg-danger">غير نشط</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('products.show', $product) }}" 
                                           class="btn btn-sm btn-info" title="عرض">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('products.edit', $product) }}" 
                                           class="btn btn-sm btn-warning" title="تعديل">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('products.destroy', $product) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج وجميع بيانات المخزون المرتبطة به؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="حذف">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">لا توجد منتجات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($products->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
