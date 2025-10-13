@extends('layouts.app')

@section('title', 'تقرير إجمالي المخزون')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="bi bi-box-seam"></i>
                    تقرير إجمالي المخزون
                </h2>
                <div>
                    <a href="{{ route('reports.inventory.csv', request()->all()) }}" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i>
                        تصدير CSV
                    </a>
                    <a href="{{ route('reports.inventory.pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
                        <i class="bi bi-file-earmark-pdf"></i>
                        تصدير PDF
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h3 class="mb-0">{{ number_format($stats['total_items']) }}</h3>
                            <small>إجمالي الأصناف</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h3 class="mb-0">{{ number_format($stats['total_quantity']) }}</h3>
                            <small>إجمالي الكميات</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <h3 class="mb-0">{{ number_format($stats['below_min_count']) }}</h3>
                            <small>أقل من الحد الأدنى</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h3 class="mb-0">{{ number_format($stats['out_of_stock']) }}</h3>
                            <small>نفذ من المخزن</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-funnel"></i>
                        الفلاتر
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.inventory') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="branch_id" class="form-label">الفرع</label>
                            <select name="branch_id" id="branch_id" class="form-select">
                                <option value="">الكل</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="category_id" class="form-label">التصنيف</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">الكل</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="product_id" class="form-label">المنتج</label>
                            <select name="product_id" id="product_id" class="form-select">
                                <option value="">الكل</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }} ({{ $product->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="below_min" class="form-label">الحالة</label>
                            <select name="below_min" id="below_min" class="form-select">
                                <option value="">الكل</option>
                                <option value="1" {{ request('below_min') == '1' ? 'selected' : '' }}>
                                    أقل من الحد الأدنى
                                </option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                                بحث
                            </button>
                            <a href="{{ route('reports.inventory') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i>
                                مسح الفلاتر
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">جدول المخزون</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>الكود</th>
                                    <th>المنتج</th>
                                    <th>التصنيف</th>
                                    <th>الماركة</th>
                                    <th>الفرع</th>
                                    <th class="text-center">الرصيد الحالي</th>
                                    <th class="text-center">الحد الأدنى</th>
                                    <th class="text-center">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventory as $item)
                                    @php
                                        $rowClass = '';
                                        $status = 'طبيعي';
                                        $statusBadge = 'bg-success';
                                        
                                        if ($item->current_stock == 0) {
                                            $rowClass = 'table-danger';
                                            $status = 'نفذ';
                                            $statusBadge = 'bg-danger';
                                        } elseif ($item->current_stock < $item->product->min_stock) {
                                            $rowClass = 'table-warning';
                                            $status = 'أقل من الحد';
                                            $statusBadge = 'bg-warning text-dark';
                                        }
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td><code>{{ $item->product->sku }}</code></td>
                                        <td><strong>{{ $item->product->name }}</strong></td>
                                        <td>{{ $item->product->category->name ?? '-' }}</td>
                                        <td>{{ $item->product->brand ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $item->branch->name }}</span>
                                        </td>
                                        <td class="text-center">
                                            <strong class="fs-5">{{ number_format($item->current_stock) }}</strong>
                                        </td>
                                        <td class="text-center">{{ number_format($item->product->min_stock) }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $statusBadge }}">{{ $status }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            لا توجد بيانات
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($inventory->hasPages())
                    <div class="card-footer">
                        {{ $inventory->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
