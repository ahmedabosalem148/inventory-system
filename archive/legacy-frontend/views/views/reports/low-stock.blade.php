@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2><i class="bi bi-graph-down-arrow"></i> تقرير نقص المخزون</h2>
            <p class="text-muted">الأصناف التي انخفضت عن الحد الأدنى</p>
        </div>
        <div class="col-auto">
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer"></i> طباعة
            </button>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning">{{ $stats['total_items'] }}</h3>
                    <p class="mb-0">إجمالي الأصناف المنخفضة</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h3 class="text-danger">{{ $stats['out_of_stock'] }}</h3>
                    <p class="mb-0">نفذت تمامًا (0)</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-dark">
                <div class="card-body text-center">
                    <h3 class="text-dark">{{ $stats['critical'] }}</h3>
                    <p class="mb-0">حالة حرجة (&lt;20%)</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.low-stock') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">جميع الفروع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">التصنيف</label>
                        <select name="category_id" class="form-select">
                            <option value="">جميع التصنيفات</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-funnel"></i> تصفية
                            </button>
                            <a href="{{ route('reports.low-stock') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Results Table --}}
    <div class="card">
        <div class="card-body p-0">
            @if($stocks->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>المنتج</th>
                                <th>التصنيف</th>
                                <th>الفرع</th>
                                <th>المخزون الحالي</th>
                                <th>الحد الأدنى</th>
                                <th>النقص</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stocks as $index => $stock)
                                @php
                                    $shortage = $stock->product->min_stock - $stock->current_stock;
                                    $percent = $stock->current_stock / max($stock->product->min_stock, 1);
                                    
                                    if ($stock->current_stock == 0) {
                                        $badgeClass = 'bg-danger';
                                        $status = 'نفذ';
                                    } elseif ($percent < 0.2) {
                                        $badgeClass = 'bg-dark';
                                        $status = 'حرج';
                                    } elseif ($percent < 0.5) {
                                        $badgeClass = 'bg-warning';
                                        $status = 'منخفض';
                                    } else {
                                        $badgeClass = 'bg-info';
                                        $status = 'متوسط';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $stock->product->name }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $stock->product->category->name }}</span>
                                    </td>
                                    <td>{{ $stock->branch->name }}</td>
                                    <td>
                                        <span class="fw-bold {{ $stock->current_stock == 0 ? 'text-danger' : '' }}">
                                            {{ $stock->current_stock }}
                                        </span>
                                    </td>
                                    <td>{{ $stock->product->min_stock }}</td>
                                    <td class="text-danger fw-bold">-{{ $shortage }}</td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                                        <br>
                                        <small class="text-muted">{{ round($percent * 100) }}%</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">لا يوجد نقص في المخزون</h4>
                    <p class="text-muted">جميع الأصناف أعلى من الحد الأدنى المحدد</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    @media print {
        .btn, nav, .card-body form { display: none; }
        .card { border: none !important; box-shadow: none !important; }
    }
</style>
@endsection
