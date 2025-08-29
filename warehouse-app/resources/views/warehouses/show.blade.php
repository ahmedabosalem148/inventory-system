@extends('layouts.app')

@section('title')
مخزون {{ $warehouse->name }} - نظام إدارة المخزون
@endsection

@section('page-title')
مخزون {{ $warehouse->name }}
@endsection

@section('breadcrumb')
    <a href="/warehouses">قائمة المخازن</a> &lt; {{ $warehouse->name }}
@endsection

@section('content')
    <!-- Warehouse Header with Logout -->
    <div class="warehouse-header">
        <div class="warehouse-info">
            <h2>{{ $warehouse->name }}</h2>
            <p>إدارة وعرض مخزون المنتجات</p>
        </div>
        <div class="warehouse-actions">
            <form method="POST" action="/warehouses/{{ $warehouse->id }}/logout" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="fa fa-sign-out"></i>
                    تسجيل الخروج
                </button>
            </form>
            <a href="/warehouses" class="btn btn-outline-primary btn-sm">
                <i class="fa fa-list"></i>
                قائمة المخازن
            </a>
        </div>
    </div>

    {{-- Self-Check Panel for Debug Mode --}}
    @includeWhen(request()->boolean('debug'), 'admin.partials.self_check')
    
    <div class="table-container">
        <div class="card-header">
            <div class="card-title">جدول المخزون</div>
            <div class="card-actions">
                <a href="/warehouses/{{ $warehouse->id }}/products/create" class="btn btn-primary">
                    <i class="fa fa-plus"></i>
                    إضافة منتج جديد
                </a>
            </div>
        </div>
        
        <div id="inventory-container">
            <div class="loading">جاري تحميل المخزون...</div>
        </div>
    </div>

    <!-- Drawer -->
    <div class="drawer-overlay" id="drawerOverlay"></div>
    <div class="drawer" id="drawer">
        <div class="drawer-header">
            <div class="drawer-title" id="drawerTitle">عملية مخزون</div>
            <button class="drawer-close" id="closeDrawer">&times;</button>
        </div>
        
        <form id="inventoryForm" data-testid="form-add">
            <div class="form-group">
                <label class="form-label">المنتج</label>
                <input type="text" class="form-input" id="productName" readonly data-testid="drawer-product">
            </div>
            
            <div class="form-group">
                <label class="form-label" id="quantityLabel">الكمية</label>
                <input type="number" class="form-input" id="quantity" min="1" required data-testid="drawer-quantity">
            </div>
            
            <div class="form-group" id="unitTypeGroup">
                <label class="form-label">نوع الوحدة</label>
                <select class="form-input" id="unitType" required data-testid="drawer-unit-type">
                    <option value="cartons">كراتين</option>
                    <option value="units">وحدات</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">ملاحظة (اختيارية)</label>
                <input type="text" class="form-input" id="note" maxlength="255" data-testid="drawer-note">
            </div>
            
            <button type="submit" class="btn btn-full" id="submitBtn" data-testid="btn-add">تنفيذ العملية</button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
const warehouseId = {{ $warehouse->id }};
let currentAction = '';
let currentProductId = '';

document.addEventListener('DOMContentLoaded', function() {
    loadInventory();
    
    // Drawer controls
    document.getElementById('closeDrawer').addEventListener('click', closeDrawer);
    document.getElementById('drawerOverlay').addEventListener('click', closeDrawer);
    
    // Form submission
    document.getElementById('inventoryForm').addEventListener('submit', handleFormSubmit);
});

async function loadInventory() {
    try {
        const result = await App.fetchJSON(`/api/warehouses/${warehouseId}/inventory`);
        
        if (result.ok) {
            renderInventoryTable(result.data);
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        document.getElementById('inventory-container').innerHTML = 
            '<div class="empty-state"><h3>خطأ في تحميل البيانات</h3><p>يرجى المحاولة مرة أخرى</p></div>';
        console.error('Error loading inventory:', error);
    }
}

function renderInventoryTable(inventory) {
    const container = document.getElementById('inventory-container');
    
    if (inventory.length === 0) {
        container.innerHTML = 
            '<div class="empty-state"><h3>لا يوجد مخزون</h3><p>لم يتم إضافة أي منتجات لهذا المخزن بعد</p></div>';
        return;
    }
    
    const tableHTML = `
        <table class="table" data-testid="wh-table">
            <thead>
                <tr>
                    <th>المنتج</th>
                    <th>الكراتين</th>
                    <th>عدد الوحدات في كل كرتونة</th>
                    <th>إجمالي الوحدات</th>
                    <th>الحد الأدنى</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                ${inventory.map(item => `
                    <tr class="${item.belowMin ? 'table-row-below-min' : ''}" data-below-min="${item.belowMin ? '1' : '0'}">
                        <td><span data-testid="row-product">${item.product.name}</span></td>
                        <td><span data-testid="drawer-cc" class="nums">${item.closed_cartons}</span></td>
                        <td><span data-testid="drawer-size" class="nums">${item.carton_size || 1}</span></td>
                        <td><span data-testid="drawer-total" class="nums">${App.formatNumber(item.totalUnits)}</span></td>
                        <td><span data-testid="drawer-min" class="nums">${App.formatNumber(item.min_threshold)}</span></td>
                        <td>
                            ${item.belowMin ? 
                                '<span class="status-below-min">تحت الحد الأدنى 🔔</span>' : 
                                '<span class="status-ok">مخزون جيد ✅</span>'
                            }
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-success btn-sm" onclick="openDrawer('add', ${item.product.id}, '${item.product.name}')" data-testid="btn-details">إضافة</button>
                                <button class="btn btn-danger btn-sm" onclick="openDrawer('withdraw', ${item.product.id}, '${item.product.name}')">سحب</button>
                                <button class="btn btn-primary btn-sm" onclick="openDrawer('setMin', ${item.product.id}, '${item.product.name}')">تعديل الحد الأدنى</button>
                                <button class="btn btn-warning btn-sm" onclick="confirmDeleteProduct(${item.product.id}, '${item.product.name}')">حذف المنتج</button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    
    container.innerHTML = tableHTML;
}

function openDrawer(action, productId, productName) {
    currentAction = action;
    currentProductId = productId;
    
    document.getElementById('productName').value = productName;
    
    const drawerTitle = document.getElementById('drawerTitle');
    const quantityLabel = document.getElementById('quantityLabel');
    const unitTypeGroup = document.getElementById('unitTypeGroup');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('inventoryForm');
    
    switch (action) {
        case 'add':
            drawerTitle.textContent = 'إضافة مخزون';
            quantityLabel.textContent = 'الكمية المراد إضافتها';
            unitTypeGroup.style.display = 'block';
            submitBtn.textContent = 'إضافة للمخزون';
            submitBtn.className = 'btn btn-full btn-success';
            submitBtn.setAttribute('data-testid', 'btn-add');
            form.setAttribute('data-testid', 'form-add');
            break;
        case 'withdraw':
            drawerTitle.textContent = 'سحب من المخزون';
            quantityLabel.textContent = 'الكمية المراد سحبها';
            unitTypeGroup.style.display = 'block';
            submitBtn.textContent = 'سحب من المخزون';
            submitBtn.className = 'btn btn-full btn-danger';
            submitBtn.setAttribute('data-testid', 'btn-withdraw');
            form.setAttribute('data-testid', 'form-withdraw');
            break;
        case 'setMin':
            drawerTitle.textContent = 'تعديل الحد الأدنى';
            quantityLabel.textContent = 'الحد الأدنى الجديد (بالوحدات)';
            unitTypeGroup.style.display = 'none';
            submitBtn.textContent = 'تحديث الحد الأدنى';
            submitBtn.className = 'btn btn-full btn-primary';
            submitBtn.setAttribute('data-testid', 'btn-setmin');
            form.setAttribute('data-testid', 'form-setmin');
            break;
    }
    
    // Reset form
    document.getElementById('inventoryForm').reset();
    document.getElementById('productName').value = productName;
    
    // Show drawer
    document.getElementById('drawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('open');
}

function closeDrawer() {
    document.getElementById('drawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('open');
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const quantity = parseInt(document.getElementById('quantity').value);
    const unitType = document.getElementById('unitType').value;
    const note = document.getElementById('note').value;
    
    if (!quantity || quantity <= 0) {
        App.toast('يرجى إدخال كمية صحيحة', 'error');
        return;
    }
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'جاري التنفيذ...';
    submitBtn.disabled = true;
    
    try {
        let endpoint, method, body;
        
        switch (currentAction) {
            case 'add':
                endpoint = `/api/inventory/add`;
                method = 'POST';
                body = {
                    warehouse_id: warehouseId,
                    product_id: currentProductId,
                    quantity: quantity,
                    unit_type: unitType,
                    note: note
                };
                break;
            case 'withdraw':
                endpoint = `/api/inventory/withdraw`;
                method = 'POST';
                body = {
                    warehouse_id: warehouseId,
                    product_id: currentProductId,
                    quantity: quantity,
                    unit_type: unitType,
                    note: note
                };
                break;
            case 'setMin':
                endpoint = `/api/inventory/set-min`;
                method = 'PATCH';
                body = {
                    warehouse_id: warehouseId,
                    product_id: currentProductId,
                    min_threshold: quantity
                };
                break;
        }
        
        console.log('API Request:', {
            endpoint: endpoint,
            method: method,
            body: body
        });
        
        const result = await App.fetchJSON(endpoint, {
            method: method,
            body: JSON.stringify(body)
        });
        
        console.log('API Response:', result);
        
        if (result.ok) {
            App.toast(result.data.message || 'تم تنفيذ العملية بنجاح', 'success');
            closeDrawer();
            loadInventory(); // Reload inventory table
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        App.toast(error.message, 'error');
        console.error('Error:', error);
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

async function confirmDeleteProduct(productId, productName) {
    const confirmed = confirm(`هل أنت متأكد من حذف المنتج "${productName}"؟\n\nسيتم حذف المنتج نهائياً من جميع المخازن!`);
    
    if (!confirmed) {
        return;
    }
    
    try {
        const result = await App.fetchJSON(`/api/products/${productId}`, {
            method: 'DELETE'
        });
        
        if (result.ok) {
            App.toast('تم حذف المنتج بنجاح', 'success');
            loadInventory(); // Reload inventory table
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        App.toast(error.message, 'error');
        console.error('Error deleting product:', error);
    }
}

// Expose for inline onclick handlers
window.openDrawer = openDrawer;
window.confirmDeleteProduct = confirmDeleteProduct;
</script>
@endpush
