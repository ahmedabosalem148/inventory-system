@extends('layouts.app')

@section('title', 'إنشاء إذن صرف')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/quick-search.css') }}">
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-file-earmark-arrow-down"></i>
                        إذن صرف جديد
                    </h5>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('issue-vouchers.store') }}" id="issueVoucherForm">
                        @csrf

                        <div class="row g-3">
                            {{-- نوع الصرف --}}
                            <div class="col-md-3">
                                <label class="form-label">نوع الصرف <span class="text-danger">*</span></label>
                                <select name="issue_type" class="form-select" required>
                                    <option value="SALE">بيع</option>
                                    <option value="TRANSFER">تحويل</option>
                                </select>
                            </div>

                            {{-- الفرع المصدر --}}
                            <div class="col-md-3">
                                <label class="form-label">الفرع المصدر <span class="text-danger">*</span></label>
                                <select name="branch_id" class="form-select" required>
                                    <option value="">اختر...</option>
                                    @foreach(\App\Models\Branch::active()->get() as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- العميل (للبيع) --}}
                            <div class="col-md-4">
                                <label class="form-label">العميل</label>
                                <div class="autocomplete-wrapper">
                                    {{-- ✅ Quick Search Input --}}
                                    <input type="text" 
                                           class="form-control" 
                                           placeholder="ابحث بالكود أو الاسم..."
                                           data-autocomplete="customers"
                                           data-autocomplete-url="/api/search/customers"
                                           data-target-field="customer_id">
                                    
                                    {{-- Hidden field للـ ID --}}
                                    <input type="hidden" name="customer_id" id="customer_id">
                                </div>
                                <small class="text-muted">ابدأ الكتابة للبحث عن العميل</small>
                            </div>

                            {{-- التاريخ --}}
                            <div class="col-md-2">
                                <label class="form-label">التاريخ</label>
                                <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- أصناف الصرف --}}
                        <h6 class="mb-3">أصناف الصرف</h6>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="30%">الصنف</th>
                                        <th width="15%">الكمية</th>
                                        <th width="15%">السعر</th>
                                        <th width="15%">الخصم</th>
                                        <th width="15%">الإجمالي</th>
                                        <th width="10%">
                                            <button type="button" class="btn btn-sm btn-success" onclick="addItemRow()">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="item-row">
                                        <td>
                                            {{-- ✅ Quick Search للمنتجات --}}
                                            <div class="autocomplete-wrapper">
                                                <input type="text" 
                                                       class="form-control product-search" 
                                                       placeholder="ابحث بالكود أو الاسم..."
                                                       data-autocomplete="products"
                                                       data-autocomplete-url="/api/search/stock"
                                                       data-target-field="items[0][product_id]">
                                                <input type="hidden" name="items[0][product_id]">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][qty]" class="form-control item-qty" min="1" value="1">
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][price]" class="form-control item-price" step="0.01" min="0">
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][discount]" class="form-control item-discount" step="0.01" min="0" value="0">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control item-total" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeItemRow(this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>الإجمالي:</strong></td>
                                        <td><strong id="grandTotal">0.00</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <button type="submit" name="action" value="draft" class="btn btn-secondary">
                                <i class="bi bi-save"></i> حفظ كمسودة
                            </button>
                            <button type="submit" name="action" value="approve" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> حفظ واعتماد
                            </button>
                            <a href="{{ route('issue-vouchers.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/quick-search.js') }}"></script>
<script>
let itemCounter = 1;

// إضافة صف جديد
function addItemRow() {
    const tbody = document.querySelector('#itemsTable tbody');
    const newRow = document.querySelector('.item-row').cloneNode(true);
    
    // تحديث الأسماء
    newRow.querySelectorAll('input').forEach(input => {
        if (input.name) {
            input.name = input.name.replace(/\[\d+\]/, `[${itemCounter}]`);
            input.value = '';
        }
        if (input.dataset.targetField) {
            input.dataset.targetField = input.dataset.targetField.replace(/\[\d+\]/, `[${itemCounter}]`);
        }
    });
    
    tbody.appendChild(newRow);
    itemCounter++;
    
    // Re-initialize autocomplete
    newRow.querySelectorAll('[data-autocomplete]').forEach(el => {
        new QuickSearch(el);
    });
}

// حذف صف
function removeItemRow(btn) {
    const tbody = document.querySelector('#itemsTable tbody');
    if (tbody.querySelectorAll('tr').length > 1) {
        btn.closest('tr').remove();
        calculateTotal();
    }
}

// حساب الإجمالي
function calculateTotal() {
    let total = 0;
    
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
        const price = parseFloat(row.querySelector('.item-price')?.value || 0);
        const discount = parseFloat(row.querySelector('.item-discount')?.value || 0);
        
        const lineTotal = (qty * price) - discount;
        row.querySelector('.item-total').value = lineTotal.toFixed(2);
        
        total += lineTotal;
    });
    
    document.getElementById('grandTotal').textContent = total.toFixed(2);
}

// Event listeners لحساب الإجمالي
document.addEventListener('input', (e) => {
    if (e.target.matches('.item-qty, .item-price, .item-discount')) {
        calculateTotal();
    }
});

// عند اختيار منتج، ملء السعر تلقائياً
document.addEventListener('autocomplete:select', (e) => {
    if (e.target.classList.contains('product-search')) {
        const row = e.target.closest('tr');
        const product = e.detail;
        
        // ملء السعر إن وُجد
        if (product.unit_price) {
            row.querySelector('.item-price').value = product.unit_price;
        }
        
        // تنبيه إن كان المخزون منخفض
        if (product.is_low_stock) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-warning alert-dismissible fade show mt-2';
            alert.innerHTML = `
                <i class="bi bi-exclamation-triangle"></i>
                تنبيه: الصنف أقل من الحد الأدنى (متوفر: ${product.current_qty})
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            row.querySelector('td').appendChild(alert);
            
            setTimeout(() => alert.remove(), 5000);
        }
        
        calculateTotal();
    }
});
</script>
@endpush
