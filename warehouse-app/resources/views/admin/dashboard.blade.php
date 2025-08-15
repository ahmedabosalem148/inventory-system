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
    
    {{-- Self-Check Panel for Debug Mode --}}
    @includeWhen(request()->boolean('debug'), 'admin.partials.self_check')
    
    <!-- KPI Cards -->
    <div class="dashboard-kpis">
        <div class="kpi-card kpi-products">
            <div class="kpi-icon">📦</div>
            <div class="kpi-content">
                <div class="kpi-number nums">{{ $kpis['totalProducts'] }}</div>
                <div class="kpi-label">إجمالي المنتجات</div>
            </div>
        </div>
        <div class="kpi-card kpi-warehouses">
            <div class="kpi-icon">🏪</div>
            <div class="kpi-content">
                <div class="kpi-number nums">{{ $kpis['totalWarehouses'] }}</div>
                <div class="kpi-label">إجمالي المخازن</div>
            </div>
        </div>
        <div class="kpi-card kpi-alerts {{ $kpis['belowMinCount'] > 0 ? 'kpi-danger' : 'kpi-success' }}">
            <div class="kpi-icon">{{ $kpis['belowMinCount'] > 0 ? '⚠️' : '✅' }}</div>
            <div class="kpi-content">
                <div class="kpi-number nums">{{ $kpis['belowMinCount'] }}</div>
                <div class="kpi-label">منتجات تحت الحد الأدنى</div>
                @if($kpis['belowMinCount'] > 0)
                    <div class="kpi-subtitle">يتطلب انتباه!</div>
                @endif
            </div>
        </div>
        <div class="kpi-card kpi-value">
            <div class="kpi-icon">💰</div>
            <div class="kpi-content">
                <div class="kpi-number nums">{{ number_format($kpis['totalValue']) }}</div>
                <div class="kpi-label">إجمالي قيمة المخزون</div>
                <div class="kpi-subtitle">تقدير تقريبي</div>
            </div>
        </div>
    </div>
    
    <!-- Audio Test Section -->
    <div class="audio-test-panel">
        <div class="audio-test-header">
            <span class="audio-test-title">🔊 اختبار الأصوات</span>
            <div class="audio-test-buttons">
                <button type="button" 
                        onclick="testLowStockSound()" 
                        class="btn-audio-test btn-audio-alert" 
                        title="اختبار صوت انخفاض المخزون"
                        data-testid="test-low-stock-btn">
                    🚨
                </button>
                <button type="button" 
                        onclick="testNewProductSound()" 
                        class="btn-audio-test btn-audio-success" 
                        title="اختبار صوت إضافة منتج"
                        data-testid="test-new-product-btn">
                    ✅
                </button>
            </div>
        </div>
    </div>
    
    <!-- Product Creation Section -->
    @include('admin.partials.product_create')
    
    <!-- Summary Table -->
    <div class="table-container">
        <div class="card-header">
            <div class="card-title">ملخص المخزون حسب المنتج والمخزن</div>
            <div class="table-controls">
                <div class="search-box">
                    <input type="text" 
                           id="table-search" 
                           placeholder="🔍 بحث في المنتجات أو المخازن..."
                           autocomplete="off"
                           data-testid="search-input">
                </div>
                <div class="filter-controls">
                    <select id="filter-status" data-testid="filter-status">
                        <option value="">كل المنتجات</option>
                        <option value="below-min">تحت الحد الأدنى فقط</option>
                        <option value="above-min">فوق الحد الأدنى فقط</option>
                    </select>
                    <select id="filter-warehouse" data-testid="filter-warehouse">
                        <option value="">كل المخازن</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->name }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        
        @if(count($rows) > 0)
        <div class="table-info">
            <span id="table-count">عرض {{ count($rows) }} عنصر</span>
            <div class="table-legend">
                <span class="legend-item">
                    <span class="legend-color status-below-min"></span>
                    تحت الحد الأدنى
                </span>
                <span class="legend-item">
                    <span class="legend-color status-ok"></span>
                    مخزون جيد
                </span>
            </div>
        </div>
        
        <div class="table-wrapper">
            <table class="table table-sortable" data-testid="admin-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="product">
                            المنتج 
                            <span class="sort-icon">↕️</span>
                        </th>
                        <th class="sortable" data-sort="warehouse">
                            المخزن 
                            <span class="sort-icon">↕️</span>
                        </th>
                        <th class="sortable" data-sort="cartons">
                            الكراتين 
                            <span class="sort-icon">↕️</span>
                        </th>
                        <th>حجم الكرتونة</th>
                        <th>قطع مفردة</th>
                        <th class="sortable" data-sort="total">
                            إجمالي الوحدات 
                            <span class="sort-icon">↕️</span>
                        </th>
                        <th class="sortable" data-sort="min">
                            الحد الأدنى 
                            <span class="sort-icon">↕️</span>
                        </th>
                        <th class="sortable" data-sort="status">
                            الحالة 
                            <span class="sort-icon">↕️</span>
                        </th>
                    </tr>
                </thead>
                <tbody id="table-body">
                @foreach($rows as $row)
                <tr class="{{ $row->below_min ? 'table-row-below-min' : '' }}" 
                    data-below-min="{{ $row->below_min ? '1' : '0' }}" 
                    data-key="{{ $row->product_name }}-{{ $row->warehouse_name }}">
                    <td><span data-testid="row-product">{{ $row->product_name }}</span></td>
                    <td><span data-testid="row-warehouse" style="color: var(--primary); font-weight: 600;">{{ $row->warehouse_name }}</span></td>
                    <td><span data-testid="row-cc" class="nums">{{ $row->closed_cartons }}</span></td>
                    <td><span data-testid="row-size" class="nums">{{ $row->carton_size ?? 1 }}</span></td>
                    <td><span data-testid="row-lu" class="nums">{{ $row->loose_units }}</span></td>
                    <td><span data-testid="row-total" class="nums">{{ number_format($row->total_units) }}</span></td>
                    <td><span data-testid="row-min" class="nums">{{ number_format($row->min_threshold) }}</span></td>
                    <td>
                        <span data-testid="row-status">
                            @if($row->below_min)
                                <span class="status-below-min">تحت الحد الأدنى 🔔</span>
                            @else
                                <span class="status-ok">مخزون جيد ✅</span>
                            @endif
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @else
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>لا يوجد مخزون</h3>
            <p>لم يتم إضافة أي منتجات أو مخازن بعد</p>
            <button onclick="document.getElementById('create-product-btn').click()" class="btn btn-primary">
                إضافة منتج جديد
            </button>
        </div>
        @endif
        
        <!-- No Results Found (Hidden by default) -->
        <div id="no-results" class="empty-state" style="display: none;">
            <div class="empty-state-icon">🔍</div>
            <h3>لا توجد نتائج</h3>
            <p>لم يتم العثور على منتجات مطابقة لمعايير البحث</p>
            <button onclick="clearFilters()" class="btn btn-secondary">
                مسح الفلاتر
            </button>
        </div>
    </div>
@endsection

@push('scripts')
<script defer>
// Debounced search functionality
let searchTimeout;
const SEARCH_DELAY = 300; // ms

function debounceSearch(searchTerm) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch(searchTerm);
    }, SEARCH_DELAY);
}

function performSearch(searchTerm) {
    if (!searchTerm || searchTerm.length < 2) {
        filterTable(); // Use existing client-side filter
        return;
    }
    
    // For server-side search, you could make AJAX call here
    filterTable(); // Keep existing client-side for now
}

// Test function for low stock alert sound
function testLowStockSound() {
    console.log('🧪 Testing low stock alert sound...');
    testSound('low_stock', '[data-testid="test-low-stock-btn"]');
}

// Test function for new product sound
function testNewProductSound() {
    console.log('🧪 Testing new product sound...');
    testSound('new_product', '[data-testid="test-new-product-btn"]');
}

// Generic test sound function
function testSound(type, buttonSelector) {
    // Force unmute for testing
    const wasMuted = App.isMuted();
    if (wasMuted) {
        App.setMuted(false);
        console.log('🔊 Temporarily unmuted for testing');
    }
    
    // Play the specific sound
    App.playSpecificAlert(type);
    
    // Show feedback
    const btn = document.querySelector(buttonSelector);
    if (btn) {
        const originalText = btn.innerHTML;
        btn.innerHTML = '🎵 جاري...';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            // Restore mute state
            if (wasMuted) {
                App.setMuted(true);
                console.log('🔇 Restored mute state');
            }
        }, 2000);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('📊 Dashboard loaded, checking for below-minimum items...');
    
    // Initialize table functionality
    initTableFeatures();
    
    // Check for session flash sound
    @if(session('play_sound'))
        console.log('🎵 Session sound trigger: {{ session("play_sound") }}');
        setTimeout(() => {
            @if(session('play_sound') === 'new_product')
                App.playNewProductSound();
            @elseif(session('play_sound') === 'low_stock')
                App.playAlert();
            @endif
        }, 500);
    @endif
    
    // Alert for below minimum items
    const beeped = {};
    const rows = document.querySelectorAll('[data-below-min="1"]');
    
    console.log(`🔍 Found ${rows.length} items below minimum threshold`);
    
    if (rows.length > 0) {
        // Wait a bit for App to initialize
        setTimeout(() => {
            rows.forEach(row => {
                const key = row.dataset.key;
                if (!beeped[key] && !App.isMuted()) {
                    console.log(`🚨 Playing alert for: ${key}`);
                    App.playAlert();
                    beeped[key] = true;
                }
            });
        }, 500);
    } else {
        console.log('✅ All items are above minimum threshold');
    }
});

// Initialize table features
function initTableFeatures() {
    const searchInput = document.getElementById('table-search');
    const filterStatus = document.getElementById('filter-status');
    const filterWarehouse = document.getElementById('filter-warehouse');
    const tableBody = document.getElementById('table-body');
    const tableCount = document.getElementById('table-count');
    const noResults = document.getElementById('no-results');
    
    if (!tableBody) return; // No table present
    
    const originalRows = Array.from(tableBody.querySelectorAll('tr'));
    let currentSortColumn = '';
    let currentSortDirection = 'asc';
    
    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            debounceSearch(e.target.value);
        });
    }
    
    // Filter functionality
    if (filterStatus) {
        filterStatus.addEventListener('change', filterTable);
    }
    
    if (filterWarehouse) {
        filterWarehouse.addEventListener('change', filterTable);
    }
    
    // Sort functionality
    document.querySelectorAll('.sortable').forEach(header => {
        header.addEventListener('click', () => {
            const sortBy = header.getAttribute('data-sort');
            sortTable(sortBy);
        });
    });
    
    function filterTable() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const statusFilter = filterStatus ? filterStatus.value : '';
        const warehouseFilter = filterWarehouse ? filterWarehouse.value : '';
        
        let visibleCount = 0;
        
        originalRows.forEach(row => {
            const productName = row.querySelector('[data-testid="row-product"]').textContent.toLowerCase();
            const warehouseName = row.querySelector('[data-testid="row-warehouse"]').textContent.toLowerCase();
            const isbelowMin = row.getAttribute('data-below-min') === '1';
            
            // Search filter
            const matchesSearch = !searchTerm || 
                productName.includes(searchTerm) || 
                warehouseName.includes(searchTerm);
            
            // Status filter
            const matchesStatus = !statusFilter || 
                (statusFilter === 'below-min' && isbelowMin) ||
                (statusFilter === 'above-min' && !isbelowMin);
            
            // Warehouse filter
            const matchesWarehouse = !warehouseFilter || 
                warehouseName.includes(warehouseFilter.toLowerCase());
            
            const shouldShow = matchesSearch && matchesStatus && matchesWarehouse;
            
            if (shouldShow) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update count and show/hide no results
        if (tableCount) {
            tableCount.textContent = `عرض ${visibleCount} من أصل ${originalRows.length} عنصر`;
        }
        
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
        
        if (tableBody.parentElement) {
            tableBody.parentElement.style.display = visibleCount === 0 ? 'none' : '';
        }
    }
    
    function sortTable(column) {
        // Update sort direction
        if (currentSortColumn === column) {
            currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortColumn = column;
            currentSortDirection = 'asc';
        }
        
        // Update header visuals
        document.querySelectorAll('.sortable').forEach(header => {
            header.classList.remove('sorted', 'asc', 'desc');
        });
        
        const currentHeader = document.querySelector(`[data-sort="${column}"]`);
        if (currentHeader) {
            currentHeader.classList.add('sorted', currentSortDirection);
        }
        
        // Sort the rows
        const sortedRows = [...originalRows].sort((a, b) => {
            let aValue, bValue;
            
            switch(column) {
                case 'product':
                    aValue = a.querySelector('[data-testid="row-product"]').textContent;
                    bValue = b.querySelector('[data-testid="row-product"]').textContent;
                    break;
                case 'warehouse':
                    aValue = a.querySelector('[data-testid="row-warehouse"]').textContent;
                    bValue = b.querySelector('[data-testid="row-warehouse"]').textContent;
                    break;
                case 'cartons':
                    aValue = parseInt(a.querySelector('[data-testid="row-cc"]').textContent);
                    bValue = parseInt(b.querySelector('[data-testid="row-cc"]').textContent);
                    break;
                case 'total':
                    aValue = parseInt(a.querySelector('[data-testid="row-total"]').textContent.replace(/,/g, ''));
                    bValue = parseInt(b.querySelector('[data-testid="row-total"]').textContent.replace(/,/g, ''));
                    break;
                case 'min':
                    aValue = parseInt(a.querySelector('[data-testid="row-min"]').textContent.replace(/,/g, ''));
                    bValue = parseInt(b.querySelector('[data-testid="row-min"]').textContent.replace(/,/g, ''));
                    break;
                case 'status':
                    aValue = a.getAttribute('data-below-min') === '1' ? 1 : 0;
                    bValue = b.getAttribute('data-below-min') === '1' ? 1 : 0;
                    break;
                default:
                    return 0;
            }
            
            if (typeof aValue === 'string') {
                aValue = aValue.toLowerCase();
                bValue = bValue.toLowerCase();
            }
            
            if (currentSortDirection === 'asc') {
                return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
            } else {
                return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
            }
        });
        
        // Update original rows array and re-append to table
        originalRows.length = 0;
        originalRows.push(...sortedRows);
        
        // Clear and re-populate table body
        tableBody.innerHTML = '';
        sortedRows.forEach(row => tableBody.appendChild(row));
        
        // Re-apply filters
        filterTable();
    }
}

// Clear all filters
function clearFilters() {
    const searchInput = document.getElementById('table-search');
    const filterStatus = document.getElementById('filter-status');
    const filterWarehouse = document.getElementById('filter-warehouse');
    
    if (searchInput) searchInput.value = '';
    if (filterStatus) filterStatus.value = '';
    if (filterWarehouse) filterWarehouse.value = '';
    
    // Re-initialize table
    initTableFeatures();
}
</script>
@endpush
