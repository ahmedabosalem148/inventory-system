@extends('layouts.app')

@section('title', 'سداد جديد')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-cash-coin ms-2"></i>
                    سداد جديد
                </h2>
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-right ms-1"></i>
                    رجوع
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('payments.store') }}" method="POST" id="paymentForm">
                        @csrf

                        <!-- العميل -->
                        <div class="mb-3">
                            <label for="customer_id" class="form-label required">العميل</label>
                            <select class="form-select @error('customer_id') is-invalid @enderror" 
                                    id="customer_id" 
                                    name="customer_id" 
                                    required>
                                <option value="">-- اختر العميل --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" 
                                            {{ old('customer_id') == $customer->id ? 'selected' : '' }}
                                            data-balance="{{ $customer->balance }}">
                                        {{ $customer->name }} 
                                        @if($customer->code)
                                            ({{ $customer->code }})
                                        @endif
                                        - رصيد: {{ number_format($customer->balance, 2) }} ريال
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="customerBalanceInfo" class="form-text" style="display: none;">
                                <i class="bi bi-info-circle ms-1"></i>
                                الرصيد الحالي: <strong id="currentBalance">0.00</strong> ريال
                            </div>
                        </div>

                        <!-- تاريخ السداد -->
                        <div class="mb-3">
                            <label for="payment_date" class="form-label required">تاريخ السداد</label>
                            <input type="date" 
                                   class="form-control @error('payment_date') is-invalid @enderror" 
                                   id="payment_date" 
                                   name="payment_date" 
                                   value="{{ old('payment_date', date('Y-m-d')) }}" 
                                   max="{{ date('Y-m-d') }}"
                                   required>
                            @error('payment_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- المبلغ -->
                        <div class="mb-3">
                            <label for="amount" class="form-label required">المبلغ (ريال)</label>
                            <input type="number" 
                                   class="form-control @error('amount') is-invalid @enderror" 
                                   id="amount" 
                                   name="amount" 
                                   value="{{ old('amount') }}" 
                                   step="0.01" 
                                   min="0.01" 
                                   placeholder="0.00"
                                   required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- طريقة الدفع -->
                        <div class="mb-3">
                            <label class="form-label required">طريقة الدفع</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" 
                                       class="btn-check" 
                                       name="payment_method" 
                                       id="method_cash" 
                                       value="CASH" 
                                       {{ old('payment_method', 'CASH') == 'CASH' ? 'checked' : '' }}>
                                <label class="btn btn-outline-success" for="method_cash">
                                    <i class="bi bi-cash-stack ms-1"></i>
                                    نقدي
                                </label>

                                <input type="radio" 
                                       class="btn-check" 
                                       name="payment_method" 
                                       id="method_cheque" 
                                       value="CHEQUE"
                                       {{ old('payment_method') == 'CHEQUE' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="method_cheque">
                                    <i class="bi bi-credit-card ms-1"></i>
                                    شيك
                                </label>
                            </div>
                            @error('payment_method')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- تفاصيل الشيك (تظهر فقط عند اختيار شيك) -->
                        <div id="chequeFields" style="display: {{ old('payment_method') == 'CHEQUE' ? 'block' : 'none' }};">
                            <div class="card bg-light mb-3">
                                <div class="card-header">
                                    <i class="bi bi-credit-card ms-1"></i>
                                    تفاصيل الشيك
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cheque_number" class="form-label required">رقم الشيك</label>
                                            <input type="text" 
                                                   class="form-control @error('cheque_number') is-invalid @enderror" 
                                                   id="cheque_number" 
                                                   name="cheque_number" 
                                                   value="{{ old('cheque_number') }}">
                                            @error('cheque_number')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="bank_name" class="form-label required">اسم البنك</label>
                                            <input type="text" 
                                                   class="form-control @error('bank_name') is-invalid @enderror" 
                                                   id="bank_name" 
                                                   name="bank_name" 
                                                   value="{{ old('bank_name') }}">
                                            @error('bank_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label for="due_date" class="form-label required">تاريخ الاستحقاق</label>
                                            <input type="date" 
                                                   class="form-control @error('due_date') is-invalid @enderror" 
                                                   id="due_date" 
                                                   name="due_date" 
                                                   value="{{ old('due_date') }}"
                                                   min="{{ date('Y-m-d') }}">
                                            @error('due_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- إذن الصرف المرتبط (اختياري) -->
                        @if($recentVouchers->count() > 0)
                        <div class="mb-3">
                            <label for="issue_voucher_id" class="form-label">
                                إذن صرف مرتبط (اختياري)
                                <small class="text-muted">- للربط بإذن صرف معين</small>
                            </label>
                            <select class="form-select @error('issue_voucher_id') is-invalid @enderror" 
                                    id="issue_voucher_id" 
                                    name="issue_voucher_id">
                                <option value="">-- بدون ربط --</option>
                                @foreach($recentVouchers as $voucher)
                                    <option value="{{ $voucher->id }}" 
                                            {{ old('issue_voucher_id') == $voucher->id ? 'selected' : '' }}>
                                        إذن رقم #{{ $voucher->id }} - 
                                        {{ $voucher->customer->name }} - 
                                        {{ $voucher->issue_date }} - 
                                        {{ number_format($voucher->total_amount, 2) }} ريال
                                    </option>
                                @endforeach
                            </select>
                            @error('issue_voucher_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <!-- ملاحظات -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">ملاحظات (اختياري)</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- أزرار -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle ms-1"></i>
                                حفظ السداد
                            </button>
                            <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle ms-1"></i>
                                إلغاء
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
    const chequeFields = document.getElementById('chequeFields');
    const chequeNumberInput = document.getElementById('cheque_number');
    const bankNameInput = document.getElementById('bank_name');
    const dueDateInput = document.getElementById('due_date');
    const customerSelect = document.getElementById('customer_id');
    const balanceInfo = document.getElementById('customerBalanceInfo');
    const currentBalanceSpan = document.getElementById('currentBalance');

    // تبديل حقول الشيك
    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'CHEQUE') {
                chequeFields.style.display = 'block';
                chequeNumberInput.required = true;
                bankNameInput.required = true;
                dueDateInput.required = true;
            } else {
                chequeFields.style.display = 'none';
                chequeNumberInput.required = false;
                bankNameInput.required = false;
                dueDateInput.required = false;
            }
        });
    });

    // عرض رصيد العميل
    customerSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const balance = parseFloat(selectedOption.dataset.balance || 0);
            currentBalanceSpan.textContent = balance.toFixed(2);
            balanceInfo.style.display = 'block';
        } else {
            balanceInfo.style.display = 'none';
        }
    });

    // تشغيل عرض الرصيد إذا كان العميل محدد مسبقاً
    if (customerSelect.value) {
        customerSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush

@push('styles')
<style>
.required::after {
    content: " *";
    color: #dc3545;
}
</style>
@endpush
