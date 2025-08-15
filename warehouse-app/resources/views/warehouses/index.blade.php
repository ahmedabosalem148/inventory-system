@extends('layouts.app')

@section('title', 'قائمة المخازن - نظام إدارة المخزون')
@section('page-title', 'قائمة المخازن')
@section('breadcrumb')
    اختر مخزناً لعرض وإدارة المخزون
@endsection

@section('content')
    {{-- Self-Check Panel for Debug Mode --}}
    @includeWhen(request()->boolean('debug'), 'admin.partials.self_check')
    
    <div class="grid grid-auto" data-testid="wh-list">
        @foreach($warehouses as $warehouse)
        <a href="/warehouses/{{ $warehouse->id }}" class="warehouse-card" data-warehouse-id="{{ $warehouse->id }}" data-testid="wh-card">
            <div class="warehouse-name">{{ $warehouse->name }}</div>
            
            <div class="warehouse-stats">
                <div class="stat">
                    <div class="stat-number nums" data-total="0">-</div>
                    <div class="stat-label">إجمالي المنتجات</div>
                </div>
                <div class="stat">
                    <div class="stat-number nums" data-below="0">-</div>
                    <div class="stat-label">تحت الحد الأدنى</div>
                </div>
            </div>
            
            <div class="status-loading">جاري التحميل...</div>
        </a>
        @endforeach
    </div>
    
    <div style="text-align: center; margin-top: 32px;">
        <a href="/" class="btn btn-primary btn-lg">لوحة التحكم الإدارية</a>
        <p style="margin-top: 8px; color: #666; font-size: 0.875rem;">
            (يتطلب كود PIN الإداري)
        </p>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load stats for each warehouse
    document.querySelectorAll('.warehouse-card').forEach(card => {
        const warehouseId = card.dataset.warehouseId;
        loadWarehouseStats(warehouseId, card);
    });
});

async function loadWarehouseStats(warehouseId, cardElement) {
    try {
        const result = await App.fetchJSON(`/api/warehouses/${warehouseId}/inventory`);
        
        if (result.ok) {
            const inventory = result.data;
            const totalProducts = inventory.length;
            const belowMinCount = inventory.filter(item => item.belowMin).length;
            
            // Update stats
            cardElement.querySelector('[data-total]').textContent = App.formatNumber(totalProducts);
            cardElement.querySelector('[data-below]').textContent = App.formatNumber(belowMinCount);
            
            // Update status indicator
            const statusElement = cardElement.querySelector('.status-loading');
            if (belowMinCount > 0) {
                statusElement.className = 'status-below-min';
                statusElement.textContent = `${belowMinCount} منتج تحت الحد الأدنى ⚠️`;
            } else {
                statusElement.className = 'status-ok';
                statusElement.textContent = 'جميع المنتجات في حالة جيدة ✅';
            }
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        const statusElement = cardElement.querySelector('.status-loading');
        statusElement.textContent = 'خطأ في تحميل البيانات';
        console.error('Error loading warehouse stats:', error);
    }
}
</script>
@endpush
