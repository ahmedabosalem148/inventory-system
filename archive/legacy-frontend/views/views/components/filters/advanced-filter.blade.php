{{--
    Advanced Filter Component
    
    Usage:
    @include('components.filters.advanced-filter', [
        'action' => route('reports.inventory'),
        'showBranch' => true,
        'showProduct' => true,
        'showCustomer' => false,
        'showDateRange' => true,
        'showStatus' => false,
    ])
--}}

<div class="card mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0">
            <i class="bi bi-funnel"></i>
            الفلاتر المتقدمة
            <button type="button" class="btn btn-sm btn-outline-secondary float-start" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="bi bi-chevron-down"></i>
            </button>
        </h6>
    </div>

    <div class="collapse {{ request()->hasAny(['date_from', 'date_to', 'branch_id', 'product_id', 'customer_id', 'status']) ? 'show' : '' }}" id="filterCollapse">
        <div class="card-body">
            <form method="GET" action="{{ $action ?? '#' }}" id="filterForm">
                <div class="row g-3">
                    {{-- Date Range Filter --}}
                    @if($showDateRange ?? false)
                    <div class="col-md-3">
                        <label class="form-label">من تاريخ</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" max="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" max="{{ date('Y-m-d') }}">
                    </div>
                    @endif

                    {{-- Branch Filter --}}
                    @if($showBranch ?? false)
                    <div class="col-md-3">
                        <label class="form-label">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">الكل</option>
                            @foreach(\App\Models\Branch::active()->get() as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Product Filter --}}
                    @if($showProduct ?? false)
                    <div class="col-md-3">
                        <label class="form-label">الصنف</label>
                        <select name="product_id" class="form-select" id="productFilter">
                            <option value="">الكل</option>
                            @foreach(\App\Models\Product::active()->orderBy('name')->get() as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->sku }} - {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Customer Filter --}}
                    @if($showCustomer ?? false)
                    <div class="col-md-3">
                        <label class="form-label">العميل</label>
                        <select name="customer_id" class="form-select" id="customerFilter">
                            <option value="">الكل</option>
                            @foreach(\App\Models\Customer::active()->orderBy('name')->get() as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->code }} - {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Status Filter --}}
                    @if($showStatus ?? false)
                    <div class="col-md-3">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="">الكل</option>
                            @foreach($statusOptions ?? ['DRAFT' => 'مسودة', 'APPROVED' => 'معتمد', 'CANCELLED' => 'ملغي'] as $value => $label)
                                <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Category Filter --}}
                    @if($showCategory ?? false)
                    <div class="col-md-3">
                        <label class="form-label">التصنيف</label>
                        <select name="category_id" class="form-select">
                            <option value="">الكل</option>
                            @foreach(\App\Models\Category::orderBy('name')->get() as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Low Stock Filter --}}
                    @if($showLowStock ?? false)
                    <div class="col-md-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="low_stock" value="1" class="form-check-input" id="lowStockCheck" {{ request('low_stock') ? 'checked' : '' }}>
                            <label class="form-check-label" for="lowStockCheck">
                                عرض فقط الأصناف أقل من الحد الأدنى
                            </label>
                        </div>
                    </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> بحث
                            </button>
                            <a href="{{ $action ?? request()->url() }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> إعادة تعيين
                            </a>
                            @if($showExport ?? false)
                            <button type="submit" name="export" value="csv" class="btn btn-success">
                                <i class="bi bi-file-earmark-excel"></i> تصدير CSV
                            </button>
                            <button type="submit" name="export" value="pdf" class="btn btn-danger">
                                <i class="bi bi-file-earmark-pdf"></i> تصدير PDF
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Active Filters Summary --}}
@if(request()->hasAny(['date_from', 'date_to', 'branch_id', 'product_id', 'customer_id', 'status', 'category_id', 'low_stock']))
<div class="alert alert-info mb-3">
    <strong><i class="bi bi-info-circle"></i> الفلاتر النشطة:</strong>
    <div class="d-flex flex-wrap gap-2 mt-2">
        @if(request('date_from'))
            <span class="badge bg-primary">من تاريخ: {{ request('date_from') }}</span>
        @endif
        @if(request('date_to'))
            <span class="badge bg-primary">إلى تاريخ: {{ request('date_to') }}</span>
        @endif
        @if(request('branch_id'))
            <span class="badge bg-info">
                الفرع: {{ \App\Models\Branch::find(request('branch_id'))->name ?? request('branch_id') }}
            </span>
        @endif
        @if(request('product_id'))
            <span class="badge bg-success">
                الصنف: {{ \App\Models\Product::find(request('product_id'))->name ?? request('product_id') }}
            </span>
        @endif
        @if(request('customer_id'))
            <span class="badge bg-warning">
                العميل: {{ \App\Models\Customer::find(request('customer_id'))->name ?? request('customer_id') }}
            </span>
        @endif
        @if(request('status'))
            <span class="badge bg-secondary">الحالة: {{ request('status') }}</span>
        @endif
        @if(request('low_stock'))
            <span class="badge bg-danger">أقل من الحد الأدنى</span>
        @endif
    </div>
</div>
@endif

@push('scripts')
<script>
// Auto-submit on change (optional)
document.addEventListener('DOMContentLoaded', function() {
    const autoSubmit = {{ $autoSubmit ?? 'false' }};
    
    if (autoSubmit) {
        const form = document.getElementById('filterForm');
        const inputs = form.querySelectorAll('select, input[type="checkbox"]');
        
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                form.submit();
            });
        });
    }
});
</script>
@endpush
