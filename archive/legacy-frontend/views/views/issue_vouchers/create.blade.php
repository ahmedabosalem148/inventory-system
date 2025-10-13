@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2>إنشاء إذن صرف جديد</h2>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('issue-vouchers.store') }}" method="POST" id="issueVoucherForm">
        @csrf

        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">بيانات الإذن</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">نوع العميل *</label>
                            <select class="form-select @error('customer_type') is-invalid @enderror" 
                                    name="customer_type" id="customer_type" required>
                                <option value="registered" {{ old('customer_type') === 'registered' ? 'selected' : '' }}>
                                    عميل مسجل
                                </option>
                                <option value="cash" {{ old('customer_type') === 'cash' ? 'selected' : '' }}>
                                    عميل نقدي
                                </option>
                            </select>
                            @error('customer_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3" id="registered_customer_div">
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">العميل المسجل</label>
                            <select class="form-select @error('customer_id') is-invalid @enderror" 
                                    name="customer_id" id="customer_id">
                                <option value="">اختر عميل</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }} ({{ $customer->phone }})
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3" id="cash_customer_div" style="display: none;">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">اسم العميل النقدي</label>
                            <input type="text" 
                                   class="form-control @error('customer_name') is-invalid @enderror" 
                                   name="customer_name" 
                                   id="customer_name"
                                   value="{{ old('customer_name') }}">
                            @error('customer_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="branch_id" class="form-label">الفرع *</label>
                            <select class="form-select @error('branch_id') is-invalid @enderror" 
                                    name="branch_id" id="branch_id" required>
                                <option value="">اختر الفرع</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="issue_date" class="form-label">تاريخ الصرف *</label>
                            <input type="date" 
                                   class="form-control @error('issue_date') is-invalid @enderror" 
                                   name="issue_date" 
                                   id="issue_date"
                                   value="{{ old('issue_date', date('Y-m-d')) }}" required>
                            @error('issue_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea class="form-control" name="notes" id="notes" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">الأصناف</h5>
                <button type="button" class="btn btn-light btn-sm" onclick="addItem()">
                    <i class="bi bi-plus-lg"></i> إضافة صنف
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="itemsTable">
                        <thead>
                            <tr>
                                <th width="25%">المنتج</th>
                                <th width="12%">المخزون</th>
                                <th width="10%">الكمية</th>
                                <th width="10%">السعر</th>
                                <th width="8%">المجموع</th>
                                <th width="10%">خصم البند</th>
                                <th width="10%">القيمة</th>
                                <th width="10%">الصافي</th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- سيتم إضافة الصفوف هنا ديناميكياً -->
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="7" class="text-end"><strong>مجموع البنود:</strong></td>
                                <td colspan="2"><strong id="subtotalDisplay">0.00 ج.م</strong></td>
                            </tr>
                            <tr id="voucherDiscountRow">
                                <td colspan="4" class="text-end">خصم الفاتورة:</td>
                                <td colspan="2">
                                    <select class="form-select form-select-sm" id="voucherDiscountType" onchange="calculateGrandTotal()">
                                        <option value="none">بدون خصم</option>
                                        <option value="percentage">نسبة مئوية %</option>
                                        <option value="fixed">مبلغ ثابت</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" class="form-control form-control-sm" 
                                           id="voucherDiscountValue" value="0" min="0" 
                                           onchange="calculateGrandTotal()" placeholder="القيمة">
                                </td>
                                <td colspan="2">
                                    <strong id="voucherDiscountDisplay">0.00 ج.م</strong>
                                </td>
                            </tr>
                            <tr class="table-success">
                                <td colspan="7" class="text-end"><strong>الصافي النهائي:</strong></td>
                                <td colspan="2"><strong id="netTotalDisplay" class="text-success">0.00 ج.م</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Hidden fields for voucher discount -->
        <input type="hidden" name="voucher_discount_type" id="hiddenVoucherDiscountType" value="none">
        <input type="hidden" name="voucher_discount_value" id="hiddenVoucherDiscountValue" value="0">

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-save"></i> حفظ الإذن
            </button>
            <a href="{{ route('issue-vouchers.index') }}" class="btn btn-secondary btn-lg">
                <i class="bi bi-x-lg"></i> إلغاء
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
let itemIndex = 0;
const products = @json($products);

// Toggle customer type
document.getElementById('customer_type').addEventListener('change', function() {
    const type = this.value;
    const registeredDiv = document.getElementById('registered_customer_div');
    const cashDiv = document.getElementById('cash_customer_div');
    
    if (type === 'registered') {
        registeredDiv.style.display = 'block';
        cashDiv.style.display = 'none';
        document.getElementById('customer_id').required = true;
        document.getElementById('customer_name').required = false;
    } else {
        registeredDiv.style.display = 'none';
        cashDiv.style.display = 'block';
        document.getElementById('customer_id').required = false;
        document.getElementById('customer_name').required = true;
    }
});

// Initialize
if (document.getElementById('customer_type').value === 'cash') {
    document.getElementById('customer_type').dispatchEvent(new Event('change'));
}

// Add item row
function addItem() {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.id = `item_${itemIndex}`;
    
    row.innerHTML = `
        <td>
            <select class="form-select form-select-sm" name="items[${itemIndex}][product_id]" 
                    onchange="updateStock(${itemIndex})" required>
                <option value="">اختر منتج</option>
                ${products.map(p => `<option value="${p.id}" data-price="${p.sale_price}">${p.name}</option>`).join('')}
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm text-center" id="stock_${itemIndex}" readonly>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" 
                   name="items[${itemIndex}][quantity]" 
                   min="1" value="1" 
                   onchange="calculateRow(${itemIndex})" required>
        </td>
        <td>
            <input type="number" step="0.01" class="form-control form-control-sm" 
                   name="items[${itemIndex}][unit_price]" 
                   min="0" value="0" 
                   onchange="calculateRow(${itemIndex})" required>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm text-end" 
                   id="total_${itemIndex}" readonly value="0.00">
        </td>
        <td>
            <select class="form-select form-select-sm" 
                    name="items[${itemIndex}][discount_type]" 
                    id="discount_type_${itemIndex}"
                    onchange="calculateRow(${itemIndex})">
                <option value="none">بدون</option>
                <option value="percentage">%</option>
                <option value="fixed">مبلغ</option>
            </select>
        </td>
        <td>
            <input type="number" step="0.01" class="form-control form-control-sm" 
                   name="items[${itemIndex}][discount_value]" 
                   id="discount_value_${itemIndex}"
                   min="0" value="0" 
                   onchange="calculateRow(${itemIndex})">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm text-end" 
                   id="net_${itemIndex}" readonly value="0.00">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(${itemIndex})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
    itemIndex++;
}

// Update stock display
function updateStock(index) {
    const select = document.querySelector(`#item_${index} select[name="items[${index}][product_id]"]`);
    const selectedOption = select.options[select.selectedIndex];
    const productId = select.value;
    const branchId = document.getElementById('branch_id').value;
    
    if (!productId || !branchId) {
        document.getElementById(`stock_${index}`).value = '';
        return;
    }
    
    const product = products.find(p => p.id == productId);
    const branchStock = product?.branch_stocks?.find(s => s.branch_id == branchId);
    
    document.getElementById(`stock_${index}`).value = branchStock ? `${branchStock.current_stock}` : '0';
    
    // Set default price
    if (selectedOption.dataset.price) {
        document.querySelector(`#item_${index} input[name="items[${index}][unit_price]"]`).value = selectedOption.dataset.price;
        calculateRow(index);
    }
}

// Calculate row total with discount
function calculateRow(index) {
    const qty = parseFloat(document.querySelector(`#item_${index} input[name="items[${index}][quantity]"]`)?.value || 0);
    const price = parseFloat(document.querySelector(`#item_${index} input[name="items[${index}][unit_price]"]`)?.value || 0);
    const total = qty * price;
    
    // حساب الخصم
    const discountType = document.getElementById(`discount_type_${index}`)?.value || 'none';
    const discountValue = parseFloat(document.getElementById(`discount_value_${index}`)?.value || 0);
    
    let discountAmount = 0;
    if (discountType === 'percentage') {
        discountAmount = (total * discountValue) / 100;
    } else if (discountType === 'fixed') {
        discountAmount = discountValue;
    }
    
    // التأكد من عدم تجاوز الخصم للمجموع
    if (discountAmount > total) {
        discountAmount = total;
        document.getElementById(`discount_value_${index}`).value = discountAmount.toFixed(2);
    }
    
    const netPrice = total - discountAmount;
    
    // عرض القيم
    document.getElementById(`total_${index}`).value = total.toFixed(2);
    document.getElementById(`net_${index}`).value = netPrice.toFixed(2);
    
    calculateGrandTotal();
}

// Calculate grand total with voucher discount
function calculateGrandTotal() {
    // مجموع صافي البنود (بعد خصم البند)
    let subtotal = 0;
    document.querySelectorAll('[id^="net_"]').forEach(input => {
        subtotal += parseFloat(input.value || 0);
    });
    
    // عرض المجموع
    document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2) + ' ج.م';
    
    // حساب خصم الفاتورة
    const voucherDiscountType = document.getElementById('voucherDiscountType')?.value || 'none';
    const voucherDiscountValue = parseFloat(document.getElementById('voucherDiscountValue')?.value || 0);
    
    let voucherDiscountAmount = 0;
    if (voucherDiscountType === 'percentage') {
        voucherDiscountAmount = (subtotal * voucherDiscountValue) / 100;
    } else if (voucherDiscountType === 'fixed') {
        voucherDiscountAmount = voucherDiscountValue;
    }
    
    // التأكد من عدم تجاوز الخصم للمجموع
    if (voucherDiscountAmount > subtotal) {
        voucherDiscountAmount = subtotal;
        document.getElementById('voucherDiscountValue').value = voucherDiscountAmount.toFixed(2);
    }
    
    // الصافي النهائي
    const netTotal = subtotal - voucherDiscountAmount;
    
    // عرض القيم
    document.getElementById('voucherDiscountDisplay').textContent = voucherDiscountAmount.toFixed(2) + ' ج.م';
    document.getElementById('netTotalDisplay').textContent = netTotal.toFixed(2) + ' ج.م';
    
    // نسخ القيم للـhidden inputs
    document.getElementById('hiddenVoucherDiscountType').value = voucherDiscountType;
    document.getElementById('hiddenVoucherDiscountValue').value = voucherDiscountValue;
}

// Remove item
function removeItem(index) {
    document.getElementById(`item_${index}`)?.remove();
    calculateGrandTotal();
}

// Add first row on load
addItem();

// Update stock when branch changes
document.getElementById('branch_id').addEventListener('change', function() {
    document.querySelectorAll('[id^="item_"] select').forEach((select, i) => {
        if (select.value) {
            const index = select.closest('tr').id.split('_')[1];
            updateStock(index);
        }
    });
});
 
// Pack Size Validation Functions 
function checkPackSize(index) { 
    const row = document.querySelector(`#row-${index}`); 
    if (!row) return; 
    const productSelect = row.querySelector('select[name="products[]"]'); 
    const qtyInput = row.querySelector('input[name="quantities[]"]'); 
    if (!productSelect || !qtyInput) return; 
    const selectedOption = productSelect.options[productSelect.selectedIndex]; 
    const packSize = selectedOption ? parseInt(selectedOption.getAttribute('data-pack-size')) : 0; 
    const qty = parseInt(qtyInput.value) || 0; 
    const existingWarning = row.querySelector('.pack-warning'); 
    if (existingWarning) existingWarning.remove(); 
    if (packSize && packSize > 0 && qty > 0 && qty % packSize !== 0) { 
        const fullPacks = Math.floor(qty / packSize); 
        const extraUnits = qty % packSize; 
        const warningDiv = createWarningDiv(row); 
        warningDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i><strong>ØªÙ†Ø¨ÙŠÙ‡: ÙƒØ³Ø± Ø¹Ø¨ÙˆØ©!</strong><br>Ø§Ù„ÙƒÙ…ÙŠØ© Ù„Ø§ ØªØªÙˆØ§ÙÙ‚ Ù…Ø¹ Ø­Ø¬Ù… Ø§Ù„Ø¹Ø¨ÙˆØ© (${packSize} ÙˆØ­Ø¯Ø©).<br>Ù„Ø¯ÙŠÙƒ <strong>${fullPacks} Ø¹Ø¨ÙˆØ© ÙƒØ§Ù…Ù„Ø© + ${extraUnits} ÙˆØ­Ø¯Ø© Ø¥Ø¶Ø§ÙÙŠØ©</strong>.`; 
        const qtyCell = qtyInput.closest('td'); 
        if (qtyCell) qtyCell.appendChild(warningDiv); 
    } 
} 
function createWarningDiv(row) { 
    const warningDiv = document.createElement('div'); 
    warningDiv.className = 'alert alert-warning alert-sm mt-2 pack-warning'; 
    warningDiv.style.fontSize = '0.85rem'; 
    warningDiv.style.padding = '0.5rem'; 
    return warningDiv; 
} 
 
</script>
@endpush
@endsection
