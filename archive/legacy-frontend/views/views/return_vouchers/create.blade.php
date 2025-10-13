@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2>إذن إرجاع جديد</h2>
            <p class="text-muted">إضافة مرتجعات منتجات من عميل</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('return-vouchers.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i>
                رجوع
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('return-vouchers.store') }}" id="returnVoucherForm">
        @csrf

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">معلومات الإذن</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">الفرع <span class="text-danger">*</span></label>
                        <select name="branch_id" id="branch_id" class="form-select" required>
                            <option value="">اختر الفرع</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">تاريخ الإرجاع <span class="text-danger">*</span></label>
                        <input type="date" name="return_date" class="form-control" 
                               value="{{ old('return_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">نوع العميل <span class="text-danger">*</span></label>
                        <select name="customer_type" id="customer_type" class="form-select" required>
                            <option value="registered" {{ old('customer_type') == 'registered' ? 'selected' : '' }}>عميل مسجل</option>
                            <option value="cash" {{ old('customer_type') == 'cash' ? 'selected' : '' }}>عميل نقدي</option>
                        </select>
                    </div>

                    <div class="col-md-3" id="registered_customer_field">
                        <label class="form-label">العميل <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customer_id" class="form-select">
                            <option value="">اختر العميل</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3" id="cash_customer_field" style="display: none;">
                        <label class="form-label">اسم العميل <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" id="customer_name" 
                               class="form-control" value="{{ old('customer_name') }}"
                               placeholder="اسم العميل">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">الأصناف المرتجعة</h5>
                <button type="button" class="btn btn-sm btn-success" onclick="addItem()">
                    <i class="bi bi-plus-circle"></i>
                    إضافة صنف
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="items-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%">المنتج</th>
                                <th style="width: 15%">المخزون المتاح</th>
                                <th style="width: 15%">الكمية المرتجعة</th>
                                <th style="width: 15%">سعر الوحدة</th>
                                <th style="width: 15%">الإجمالي</th>
                                <th style="width: 10%">حذف</th>
                            </tr>
                        </thead>
                        <tbody id="items-tbody">
                            <!-- سيتم إضافة الصفوف ديناميكياً -->
                        </tbody>
                    </table>
                </div>

                <div class="row mt-3">
                    <div class="col-md-8"></div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="d-flex justify-content-between">
                                    <span>الإجمالي الكلي:</span>
                                    <span id="grand-total" class="text-primary">0.00 ج.م</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i>
                    حفظ إذن الإرجاع
                </button>
                <a href="{{ route('return-vouchers.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i>
                    إلغاء
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    let itemCounter = 0;
    const products = @json($products);

    // Toggle customer type fields
    document.getElementById('customer_type').addEventListener('change', function() {
        const registeredField = document.getElementById('registered_customer_field');
        const cashField = document.getElementById('cash_customer_field');
        const customerIdInput = document.getElementById('customer_id');
        const customerNameInput = document.getElementById('customer_name');

        if (this.value === 'registered') {
            registeredField.style.display = 'block';
            cashField.style.display = 'none';
            customerIdInput.required = true;
            customerNameInput.required = false;
        } else {
            registeredField.style.display = 'none';
            cashField.style.display = 'block';
            customerIdInput.required = false;
            customerNameInput.required = true;
        }
    });

    // Trigger on page load
    document.getElementById('customer_type').dispatchEvent(new Event('change'));

    // Update stock when branch changes
    document.getElementById('branch_id').addEventListener('change', function() {
        updateAllStockDisplays();
    });

    function updateAllStockDisplays() {
        const branchId = document.getElementById('branch_id').value;
        if (!branchId) return;

        document.querySelectorAll('.item-row').forEach(row => {
            const productSelect = row.querySelector('.product-select');
            if (productSelect && productSelect.value) {
                updateStock(productSelect);
            }
        });
    }

    function addItem() {
        itemCounter++;
        const branchId = document.getElementById('branch_id').value;
        
        if (!branchId) {
            alert('يرجى اختيار الفرع أولاً');
            return;
        }

        const row = `
            <tr class="item-row" data-index="${itemCounter}">
                <td>
                    <select name="items[${itemCounter}][product_id]" class="form-select product-select" required onchange="updateStock(this)">
                        <option value="">اختر المنتج</option>
                        ${products.map(p => `<option value="${p.id}" data-price="${p.sale_price}">${p.name} (${p.category.name})</option>`).join('')}
                    </select>
                </td>
                <td>
                    <span class="stock-display badge bg-info">-</span>
                </td>
                <td>
                    <input type="number" name="items[${itemCounter}][quantity]" 
                           class="form-control quantity-input" min="1" required 
                           onchange="calculateRow(${itemCounter})">
                </td>
                <td>
                    <input type="number" name="items[${itemCounter}][unit_price]" 
                           class="form-control price-input" step="0.01" min="0" required 
                           onchange="calculateRow(${itemCounter})">
                </td>
                <td>
                    <input type="text" class="form-control row-total" readonly value="0.00">
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${itemCounter})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        document.getElementById('items-tbody').insertAdjacentHTML('beforeend', row);
    }

    function updateStock(selectElement) {
        const row = selectElement.closest('.item-row');
        const stockDisplay = row.querySelector('.stock-display');
        const priceInput = row.querySelector('.price-input');
        const productId = selectElement.value;
        const branchId = document.getElementById('branch_id').value;

        if (!productId || !branchId) {
            stockDisplay.textContent = '-';
            stockDisplay.className = 'stock-display badge bg-info';
            return;
        }

        const product = products.find(p => p.id == productId);
        if (!product) return;

        // Fill price automatically
        priceInput.value = product.sale_price;

        // Find stock for this branch
        const stock = product.branch_stocks.find(s => s.branch_id == branchId);
        
        if (stock) {
            stockDisplay.textContent = `${stock.current_stock} ${product.unit}`;
            stockDisplay.className = stock.current_stock > 0 ? 'stock-display badge bg-success' : 'stock-display badge bg-danger';
        } else {
            stockDisplay.textContent = `0 ${product.unit}`;
            stockDisplay.className = 'stock-display badge bg-danger';
        }

        // Recalculate row total
        const index = row.dataset.index;
        calculateRow(index); checkPackSize(index);
    }

    function calculateRow(index) {
        const row = document.querySelector(`tr[data-index="${index}"]`);
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const total = quantity * price;
        
        row.querySelector('.row-total').value = total.toFixed(2);
        
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let grandTotal = 0;
        
        document.querySelectorAll('.row-total').forEach(input => {
            grandTotal += parseFloat(input.value) || 0;
        });
        
        document.getElementById('grand-total').textContent = grandTotal.toFixed(2) + ' ج.م';
    }

    function removeItem(index) {
        const row = document.querySelector(`tr[data-index="${index}"]`);
        row.remove();
        calculateGrandTotal();
    }

    // Add first row on page load
    window.addEventListener('DOMContentLoaded', function() {
        addItem();
    });
</script>
@endpush
