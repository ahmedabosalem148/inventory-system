@extends('layouts.app')

@section('title', 'لوحة التحكم الإدارية - نظام إدارة المخزون')
@section('page-title', 'لوحة التحكم الإدارية')
@section('breadcrumb')
    نظرة عامة على حالة المخزون
@endsection

@section('content')
    <!-- Logout Button -->
    <form action="/admin/logout" method="POST" style="position: absolute; top: 20px; left: 20px;">
        @csrf
        <button type="submit" class="btn btn-danger btn-sm">تسجيل الخروج</button>
    </form>
    
    <!-- KPI Cards -->
    <div class="dashboard-kpis">
        <div class="kpi-card">
            <div class="kpi-number nums">{{ $kpis['totalProducts'] }}</div>
            <div class="kpi-label">إجمالي المنتجات</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-number nums">{{ $kpis['totalWarehouses'] }}</div>
            <div class="kpi-label">إجمالي المخازن</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-number nums">{{ $kpis['belowMinCount'] }}</div>
            <div class="kpi-label">منتجات تحت الحد الأدنى</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-number nums">{{ number_format($kpis['totalValue']) }}</div>
            <div class="kpi-label">إجمالي قيمة المخزون</div>
        </div>
    </div>
    
    <!-- Summary Table -->
    <div class="table-container">
        <div class="card-header">
            <div class="card-title">ملخص المخزون حسب المنتج والمخزن</div>
        </div>
        
        @if(count($rows) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>المخزن</th>
                    <th>المخزون الحالي</th>
                    <th>إجمالي الوحدات</th>
                    <th>الحد الأدنى</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr class="{{ $row->belowMin ? 'table-row-below-min' : '' }}" 
                    data-below-min="{{ $row->belowMin ? 'true' : 'false' }}" 
                    data-key="{{ $row->product_name }}-{{ $row->warehouse_name }}">
                    <td class="table-product-name">{{ $row->product_name }}</td>
                    <td class="warehouse-name" style="color: var(--primary); font-weight: 600;">{{ $row->warehouse_name }}</td>
                    <td>
                        <div class="table-stock-info">
                            <div class="table-cartons">{{ $row->closed_cartons }} كرتونة</div>
                            <div class="table-units">{{ $row->loose_units }} وحدة منفردة</div>
                        </div>
                    </td>
                    <td class="table-cartons nums">{{ number_format($row->total_units) }} وحدة</td>
                    <td class="nums">{{ number_format($row->min_threshold) }} وحدة</td>
                    <td>
                        @if($row->belowMin)
                            <span class="status-below-min">تحت الحد الأدنى ⚠️</span>
                        @else
                            <span class="status-ok">مخزون جيد ✅</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">
            <h3>لا يوجد مخزون</h3>
            <p>لم يتم إضافة أي منتجات أو مخازن بعد</p>
        </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Alert for below minimum items
    const beeped = {};
    const rows = document.querySelectorAll('[data-below-min="true"]');
    
    rows.forEach(row => {
        const key = row.dataset.key;
        if (!beeped[key] && !App.isMuted()) {
            App.playAlert();
            beeped[key] = true;
        }
    });
});
</script>
@endpush
