@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2>تفاصيل المنتج: {{ $product->name }}</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('products.edit', $product) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> تعديل
            </a>
            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i> رجوع
            </a>
        </div>
    </div>

    <div class="row">
        <!-- بيانات المنتج الأساسية -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> معلومات المنتج</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th width="40%">التصنيف:</th>
                                <td><span class="badge bg-info">{{ $product->category->name }}</span></td>
                            </tr>
                            <tr>
                                <th>اسم المنتج:</th>
                                <td><strong>{{ $product->name }}</strong></td>
                            </tr>
                            <tr>
                                <th>الوصف:</th>
                                <td>{{ $product->description ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>وحدة القياس:</th>
                                <td>{{ $product->unit }}</td>
                            </tr>
                            <tr>
                                <th>الحالة:</th>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge bg-success">نشط</span>
                                    @else
                                        <span class="badge bg-danger">غير نشط</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>تاريخ الإنشاء:</th>
                                <td>{{ $product->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- الأسعار -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-currency-exchange"></i> الأسعار والربح</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th width="40%">سعر الشراء:</th>
                                <td><strong class="text-danger">{{ number_format($product->purchase_price, 2) }} ج.م</strong></td>
                            </tr>
                            <tr>
                                <th>سعر البيع:</th>
                                <td><strong class="text-success">{{ number_format($product->sale_price, 2) }} ج.م</strong></td>
                            </tr>
                            <tr>
                                <th>هامش الربح:</th>
                                <td>
                                    @php
                                        $profit = $product->sale_price - $product->purchase_price;
                                        $profitPercent = $product->purchase_price > 0 
                                            ? ($profit / $product->purchase_price) * 100 
                                            : 0;
                                    @endphp
                                    <strong class="text-primary">{{ number_format($profit, 2) }} ج.م</strong>
                                    <span class="badge bg-primary">{{ number_format($profitPercent, 1) }}%</span>
                                </td>
                            </tr>
                            <tr>
                                <th>الحد الأدنى للمخزون:</th>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        {{ $product->min_stock }} {{ $product->unit }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- المخزون في الفروع -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-boxes"></i> المخزون في الفروع</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @php
                            $totalStock = $product->branchStocks->sum('current_stock');
                        @endphp
                        
                        @foreach($product->branchStocks as $stock)
                        <div class="col-md-4 mb-3">
                            <div class="card {{ $stock->current_stock < $product->min_stock ? 'border-warning' : 'border-success' }}">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">
                                        <i class="bi bi-building"></i> {{ $stock->branch->name }}
                                    </h6>
                                    <h2 class="mb-2">
                                        <span class="{{ $stock->current_stock == 0 ? 'text-danger' : ($stock->current_stock < $product->min_stock ? 'text-warning' : 'text-success') }}">
                                            {{ $stock->current_stock }}
                                        </span>
                                    </h2>
                                    <p class="mb-0 text-muted">{{ $product->unit }}</p>
                                    
                                    @if($stock->current_stock == 0)
                                        <span class="badge bg-danger mt-2">منتهي</span>
                                    @elseif($stock->current_stock < $product->min_stock)
                                        <span class="badge bg-warning text-dark mt-2">منخفض</span>
                                    @else
                                        <span class="badge bg-success mt-2">متوفر</span>
                                    @endif
                                    
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            آخر تحديث: {{ $stock->updated_at->format('Y-m-d') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach

                        <!-- إجمالي المخزون -->
                        <div class="col-12">
                            <div class="alert {{ $totalStock < $product->min_stock ? 'alert-warning' : 'alert-success' }} text-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-box-seam"></i>
                                    إجمالي المخزون في جميع الفروع: 
                                    <strong>{{ $totalStock }} {{ $product->unit }}</strong>
                                    @if($totalStock < $product->min_stock)
                                        <i class="bi bi-exclamation-triangle-fill ms-2"></i>
                                        <span class="badge bg-warning text-dark">أقل من الحد الأدنى</span>
                                    @endif
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- معلومات إضافية -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <h6 class="text-muted">إجمالي قيمة المخزون (شراء)</h6>
                            <h4 class="text-danger">
                                {{ number_format($totalStock * $product->purchase_price, 2) }} ج.م
                            </h4>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">إجمالي قيمة المخزون (بيع)</h6>
                            <h4 class="text-success">
                                {{ number_format($totalStock * $product->sale_price, 2) }} ج.م
                            </h4>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">الربح المتوقع</h6>
                            <h4 class="text-primary">
                                {{ number_format($totalStock * ($product->sale_price - $product->purchase_price), 2) }} ج.م
                            </h4>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted">عدد الفروع</h6>
                            <h4>{{ $product->branchStocks->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
