@extends('layouts.app')

@section('title', 'لوحة تحكم المخازن - نظام إدارة المخزون')
@section('page-title', 'لوحة تحكم المخازن')
@section('breadcrumb')
    نظرة عامة شاملة على حالة المخزون في جميع المخازن
@endsection

@section('content')
    <!-- Logout Button -->
    <form action="/warehouse-manager/logout" method="POST" style="position: absolute; top: 20px; left: 20px;">
        @csrf
        <button type="submit" class="btn btn-danger btn-sm">تسجيل الخروج</button>
    </form>
    
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
        <div class="kpi-card kpi-units">
            <div class="kpi-icon">📊</div>
            <div class="kpi-content">
                <div class="kpi-number nums">{{ number_format($kpis['totalUnits']) }}</div>
                <div class="kpi-label">إجمالي الوحدات</div>
                <div class="kpi-subtitle">في جميع المخازن</div>
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
                        onclick="testUpdateSound()" 
                        class="btn-audio-test btn-audio-success" 
                        title="اختبار صوت تحديث ناجح"
                        data-testid="test-update-btn">
                    ✅
                </button>
            </div>
        </div>
    </div>
    
    <!-- Main Dashboard Content -->
    <div class="table-container">
        <div class="card-header">
            <div class="card-title">🏭 إدارة مخزون جميع المخازن</div>
            <div class="table-controls">
                <div class="search-box">
                    <input type="text" 
                           id="searchFilter" 
                           class="filter-input" 
                           placeholder="🔍 بحث في المنتجات أو المخازن..."
                           autocomplete="off">
                </div>
                <div class="filter-controls">
                    <select id="warehouseFilter" class="filter-select">
                        <option value="">جميع المخازن</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <label class="checkbox-filter">
                        <input type="checkbox" id="lowStockFilter"> المنتجات تحت الحد الأدنى فقط
                    </label>
                    <button type="button" onclick="refreshTable()" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-refresh"></i> تحديث
                    </button>
                </div>
            </div>
        </div>
        
        <div class="table-info">
            <span id="table-count">عرض البيانات...</span>
            <div class="table-legend">
                <span class="legend-item">
                    <span class="legend-indicator legend-normal"></span>
                    مخزون طبيعي
                </span>
                <span class="legend-item">
                    <span class="legend-indicator legend-warning"></span>
                    تحت الحد الأدنى
                </span>
            </div>
        </div>
        
        <div id="inventory-container" class="table-wrapper">
            <div class="loading">جاري تحميل البيانات...</div>
        </div>
    </div>

    <script>
        let currentWarehouse = '';
        let currentSearch = '';
        let currentLowStock = false;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadInventoryData();
            setupFilters();
            
            // Auto-refresh every 30 seconds
            setInterval(function() {
                loadInventoryData();
            }, 30000);
        });

        function setupFilters() {
            // Warehouse filter
            document.getElementById('warehouseFilter').addEventListener('change', function() {
                currentWarehouse = this.value;
                loadInventoryData();
            });

            // Search filter
            document.getElementById('searchFilter').addEventListener('input', debounce(function() {
                currentSearch = this.value;
                loadInventoryData();
            }, 500));

            // Low stock filter
            document.getElementById('lowStockFilter').addEventListener('change', function() {
                currentLowStock = this.checked;
                loadInventoryData();
            });
        }

        function loadInventoryData() {
            const container = document.getElementById('inventory-container');
            container.innerHTML = '<div class="loading">جاري تحميل البيانات...</div>';

            const params = new URLSearchParams();
            if (currentWarehouse) params.append('warehouse_id', currentWarehouse);
            if (currentSearch) params.append('search', currentSearch);
            if (currentLowStock) params.append('low_stock', '1');

            fetch(`/warehouse-manager/summary-flat?${params}`)
                .then(response => response.json())
                .then(data => {
                    renderInventoryTable(data);
                    checkLowStock(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    container.innerHTML = '<div class="error">حدث خطأ في تحميل البيانات</div>';
                });
        }

        function renderInventoryTable(rows) {
            const container = document.getElementById('inventory-container');
            
            if (rows.length === 0) {
                container.innerHTML = '<div class="empty-state">لا توجد منتجات تطابق المعايير المحددة</div>';
                document.getElementById('table-count').textContent = 'لا توجد نتائج';
                return;
            }

            document.getElementById('table-count').textContent = `عرض ${rows.length} عنصر`;

            let html = `
                <table class="table table-sortable">
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
                    <tbody>
            `;

            rows.forEach(row => {
                const belowMin = row.is_below_min || row.below_min;
                const statusClass = belowMin ? 'status-danger' : 'status-success';
                const statusText = belowMin ? 'تحت الحد الأدنى' : 'طبيعي';
                const statusIcon = belowMin ? '⚠️' : '✅';
                const rowClass = belowMin ? 'table-row-below-min' : '';

                html += `
                    <tr class="${rowClass}" 
                        data-below-min="${belowMin ? '1' : '0'}" 
                        data-key="${row.product_name}-${row.warehouse_name}">
                        <td class="product-name">${row.product_name}</td>
                        <td class="warehouse-name">${row.warehouse_name}</td>
                        <td class="quantity-cartons nums">${row.closed_cartons}</td>
                        <td class="quantity-units nums">${row.loose_units}</td>
                        <td class="total-units nums">${row.total_units}</td>
                        <td class="min-threshold nums">${row.min_threshold}</td>
                        <td class="status ${statusClass}">
                            <span class="status-indicator ${statusClass}">
                                ${statusIcon} ${statusText}
                            </span>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
            
            // Add sorting functionality
            addTableSorting();
            
            // Check for low stock and play sound
            checkLowStock(rows);
        }

        function addTableSorting() {
            const sortableHeaders = document.querySelectorAll('#inventory-container .sortable');
            sortableHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const sortKey = this.getAttribute('data-sort');
                    sortTable(sortKey);
                });
            });
        }

        function sortTable(sortKey) {
            const table = document.querySelector('#inventory-container table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Toggle sort direction
            const currentDirection = table.getAttribute('data-sort-direction') || 'asc';
            const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
            table.setAttribute('data-sort-direction', newDirection);
            
            // Update sort icons
            document.querySelectorAll('#inventory-container .sort-icon').forEach(icon => {
                icon.textContent = '↕️';
            });
            
            const activeHeader = document.querySelector(`#inventory-container [data-sort="${sortKey}"] .sort-icon`);
            if (activeHeader) {
                activeHeader.textContent = newDirection === 'asc' ? '↑' : '↓';
            }
            
            // Sort rows
            rows.sort((a, b) => {
                let aValue, bValue;
                
                switch(sortKey) {
                    case 'product':
                        aValue = a.querySelector('.product-name').textContent;
                        bValue = b.querySelector('.product-name').textContent;
                        break;
                    case 'warehouse':
                        aValue = a.querySelector('.warehouse-name').textContent;
                        bValue = b.querySelector('.warehouse-name').textContent;
                        break;
                    case 'cartons':
                        aValue = parseInt(a.querySelector('.quantity-cartons').textContent);
                        bValue = parseInt(b.querySelector('.quantity-cartons').textContent);
                        break;
                    case 'total':
                        aValue = parseInt(a.querySelector('.total-units').textContent);
                        bValue = parseInt(b.querySelector('.total-units').textContent);
                        break;
                    case 'min':
                        aValue = parseInt(a.querySelector('.min-threshold').textContent);
                        bValue = parseInt(b.querySelector('.min-threshold').textContent);
                        break;
                    case 'status':
                        aValue = a.getAttribute('data-below-min') === '1' ? 1 : 0;
                        bValue = b.getAttribute('data-below-min') === '1' ? 1 : 0;
                        break;
                    default:
                        return 0;
                }
                
                if (typeof aValue === 'string') {
                    return newDirection === 'asc' ? 
                        aValue.localeCompare(bValue, 'ar') : 
                        bValue.localeCompare(aValue, 'ar');
                } else {
                    return newDirection === 'asc' ? aValue - bValue : bValue - aValue;
                }
            });
            
            // Rebuild tbody
            rows.forEach(row => tbody.appendChild(row));
        }

        function refreshTable() {
            loadInventoryData();
        }

        function checkLowStock(rows) {
            const lowStockItems = rows.filter(row => row.is_below_min || row.below_min);
            if (lowStockItems.length > 0) {
                setTimeout(() => {
                    playSound('/sounds/low-stock.mp3');
                }, 1000);
            }
        }

        function checkLowStock(rows) {
            const lowStockItems = rows.filter(row => row.below_min);
            if (lowStockItems.length > 0) {
                setTimeout(() => {
                    playSound('/sounds/low-stock.mp3');
                }, 1000);
            }
        }

        // Audio functions
        function testLowStockSound() {
            playSound('/sounds/low-stock.mp3');
        }

        function testUpdateSound() {
            playSound('/sounds/success.mp3');
        }

        function playSound(src) {
            try {
                const audio = new Audio(src);
                audio.play().catch(e => console.log('Audio play failed:', e));
            } catch (e) {
                console.log('Audio not supported:', e);
            }
        }

        // Utility functions
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function showSuccessMessage(message) {
            // Create and show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-floating';
            alert.innerHTML = `<i class="fa fa-check-circle"></i> ${message}`;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }

        function showErrorMessage(message) {
            // Create and show error message
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-floating';
            alert.innerHTML = `<i class="fa fa-exclamation-triangle"></i> ${message}`;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    </script>

    <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .filters-container {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-select, .filter-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .alert-floating {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .row-warning {
            background-color: #fff3cd !important;
        }

        .status-danger {
            color: #721c24;
            font-weight: 600;
        }

        .status-success {
            color: #155724;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .filters-container {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group {
                justify-content: space-between;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
