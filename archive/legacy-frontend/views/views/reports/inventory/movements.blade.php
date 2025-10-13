@extends('layouts.app')

@section('title', 'تقرير حركة المخزون')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            {{-- Page Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>
                    <i class="bi bi-graph-up"></i>
                    تقرير حركة المخزون
                </h3>
                <div>
                    <a href="{{ route('reports.inventory.current-stock') }}" class="btn btn-outline-primary">
                        <i class="bi bi-box-seam"></i> الأرصدة الحالية
                    </a>
                    <a href="{{ route('reports.inventory.most-active') }}" class="btn btn-outline-success">
                        <i class="bi bi-bar-chart"></i> الأكثر حركة
                    </a>
                </div>
            </div>

            {{-- Advanced Filters Component --}}
            @include('components.filters.advanced-filter', [
                'action' => route('reports.inventory.movements'),
                'showDateRange' => true,
                'showBranch' => true,
                'showProduct' => true,
                'showCategory' => true,
                'showExport' => true,
            ])

            {{-- Additional Filter: Movement Type --}}
            @if(request()->hasAny(['date_from', 'branch_id', 'product_id']))
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.inventory.movements') }}" class="row g-3">
                        {{-- Preserve existing filters --}}
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
                        <input type="hidden" name="product_id" value="{{ request('product_id') }}">
                        
                        <div class="col-md-4">
                            <label class="form-label">نوع الحركة</label>
                            <select name="movement_type" class="form-select" onchange="this.form.submit()">
                                <option value="">جميع الأنواع</option>
                                <option value="ADD" {{ request('movement_type') == 'ADD' ? 'selected' : '' }}>إضافة</option>
                                <option value="ISSUE" {{ request('movement_type') == 'ISSUE' ? 'selected' : '' }}>صرف</option>
                                <option value="RETURN" {{ request('movement_type') == 'RETURN' ? 'selected' : '' }}>مرتجع</option>
                                <option value="TRANSFER_OUT" {{ request('movement_type') == 'TRANSFER_OUT' ? 'selected' : '' }}>تحويل - خروج</option>
                                <option value="TRANSFER_IN" {{ request('movement_type') == 'TRANSFER_IN' ? 'selected' : '' }}>تحويل - دخول</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- Results --}}
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        النتائج ({{ $movements->total() }} حركة)
                    </h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="8%">التاريخ</th>
                                    <th width="10%">نوع الحركة</th>
                                    <th width="15%">الصنف</th>
                                    <th width="12%">الفرع</th>
                                    <th width="8%">الكمية</th>
                                    <th width="10%">السعر</th>
                                    <th width="10%">القيمة</th>
                                    <th width="15%">المرجع</th>
                                    <th width="12%">ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $movement)
                                <tr>
                                    <td>
                                        <small>{{ $movement->created_at->format('Y-m-d') }}</small><br>
                                        <small class="text-muted">{{ $movement->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $movement->movement_type_badge }}">
                                            <i class="{{ $movement->movement_type_icon }}"></i>
                                            {{ $movement->movement_type_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $movement->product->sku }}</strong><br>
                                        <small>{{ $movement->product->name }}</small>
                                    </td>
                                    <td>{{ $movement->branch->name }}</td>
                                    <td class="text-center">
                                        <strong class="{{ in_array($movement->movement_type, ['ADD', 'RETURN', 'TRANSFER_IN']) ? 'text-success' : 'text-danger' }}">
                                            {{ in_array($movement->movement_type, ['ADD', 'RETURN', 'TRANSFER_IN']) ? '+' : '-' }}
                                            {{ number_format($movement->qty_units) }}
                                        </strong>
                                    </td>
                                    <td>{{ number_format($movement->unit_price_snapshot, 2) }}</td>
                                    <td>
                                        <strong>{{ number_format($movement->qty_units * $movement->unit_price_snapshot, 2) }}</strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $movement->ref_table }}</small><br>
                                        #{{ $movement->ref_id }}
                                    </td>
                                    <td><small>{{ $movement->notes }}</small></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        لا توجد حركات مخزنية
                                        <br>
                                        <small>جرّب تعديل الفلاتر</small>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            
                            @if($movements->count())
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end"><strong>الإجمالي:</strong></td>
                                    <td class="text-center">
                                        <strong>{{ number_format($movements->sum('qty_units')) }}</strong>
                                    </td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($movements->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $movements->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
