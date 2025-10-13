@extends('layouts.app')

@section('title', 'تقرير حركة صنف')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-arrow-left-right"></i>
                تقرير حركة صنف
            </h2>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-funnel"></i> الفلاتر</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">المنتج <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-select" required>
                                <option value="">اختر منتج...</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">الفرع</label>
                            <select name="branch_id" class="form-select">
                                <option value="">الكل</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">من تاريخ</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">إلى تاريخ</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> بحث
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($product)
                <div class="alert alert-info">
                    <strong>المنتج:</strong> {{ $product->name }} ({{ $product->sku }})
                    | <strong>إجمالي الحركات:</strong> {{ $movements->total() }}
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">سجل الحركات</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>النوع</th>
                                        <th>الفرع</th>
                                        <th class="text-center">الكمية</th>
                                        <th>المرجع</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($movements as $movement)
                                        <tr>
                                            <td>{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                @php
                                                    $typeColors = [
                                                        'ADD' => 'success',
                                                        'ISSUE' => 'danger',
                                                        'RETURN' => 'info',
                                                        'TRANSFER_OUT' => 'warning',
                                                        'TRANSFER_IN' => 'primary',
                                                    ];
                                                    $typeLabels = [
                                                        'ADD' => 'إضافة',
                                                        'ISSUE' => 'صرف',
                                                        'RETURN' => 'ارتجاع',
                                                        'TRANSFER_OUT' => 'تحويل خارج',
                                                        'TRANSFER_IN' => 'تحويل وارد',
                                                    ];
                                                @endphp
                                                <span class="badge bg-{{ $typeColors[$movement->movement_type] ?? 'secondary' }}">
                                                    {{ $typeLabels[$movement->movement_type] ?? $movement->movement_type }}
                                                </span>
                                            </td>
                                            <td>{{ $movement->branch->name }}</td>
                                            <td class="text-center">
                                                <strong>{{ in_array($movement->movement_type, ['ISSUE', 'TRANSFER_OUT']) ? '-' : '+' }}{{ number_format($movement->qty_units) }}</strong>
                                            </td>
                                            <td><small>{{ $movement->ref_table }} #{{ $movement->ref_id }}</small></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">لا توجد حركات</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if($movements->hasPages())
                        <div class="card-footer">
                            {{ $movements->appends(request()->all())->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
