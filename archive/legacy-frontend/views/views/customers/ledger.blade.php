@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2>كشف حساب عميل</h2>
            <p class="text-muted">{{ $customer->name }}</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('customers.statement', $customer->id) }}?date_from={{ request('date_from') }}&date_to={{ request('date_to') }}" class="btn btn-danger" target="_blank">
                <i class="bi bi-file-pdf"></i>
                ØªØµØ¯ÙŠØ± PDF
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i>
                طباعة
            </button>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i>
                رجوع
            </a>
        </div>
    </div>

    <!-- Customer Info Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">معلومات العميل</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>الاسم:</strong><br>
                            {{ $customer->name }}
                        </div>
                        <div class="col-md-3">
                            <strong>الهاتف:</strong><br>
                            {{ $customer->phone ?? '-' }}
                        </div>
                        <div class="col-md-3">
                            <strong>العنوان:</strong><br>
                            {{ $customer->address ?? '-' }}
                        </div>
                        <div class="col-md-3">
                            <strong>الرصيد الحالي:</strong><br>
                            <h4 class="mb-0">
                                @if($customer->balance > 0)
                                    <span class="text-success">{{ number_format($customer->balance, 2) }} ج.م (له)</span>
                                @elseif($customer->balance < 0)
                                    <span class="text-danger">{{ number_format(abs($customer->balance), 2) }} ج.م (عليه)</span>
                                @else
                                    <span class="text-muted">0.00 ج.م (متزن)</span>
                                @endif
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">إجمالي المدين (له)</h6>
                            <h3 class="mb-0 text-success">{{ number_format($stats['total_debits'], 2) }} ج.م</h3>
                        </div>
                        <div class="text-success" style="font-size: 2rem;">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">إجمالي الدائن (عليه)</h6>
                            <h3 class="mb-0 text-danger">{{ number_format($stats['total_credits'], 2) }} ج.م</h3>
                        </div>
                        <div class="text-danger" style="font-size: 2rem;">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">الرصيد النهائي</h6>
                            <h3 class="mb-0 text-primary">
                                {{ number_format(abs($stats['current_balance']), 2) }} ج.م
                                @if($stats['current_balance'] > 0)
                                    (له)
                                @elseif($stats['current_balance'] < 0)
                                    (عليه)
                                @else
                                    (متزن)
                                @endif
                            </h3>
                        </div>
                        <div class="text-primary" style="font-size: 2rem;">
                            <i class="bi bi-wallet2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('customers.show', $customer) }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">نوع العملية</label>
                        <select name="transaction_type" class="form-select">
                            <option value="">الكل</option>
                            <option value="issue_voucher" {{ request('transaction_type') == 'issue_voucher' ? 'selected' : '' }}>إذن صرف</option>
                            <option value="return_voucher" {{ request('transaction_type') == 'return_voucher' ? 'selected' : '' }}>إذن إرجاع</option>
                            <option value="payment" {{ request('transaction_type') == 'payment' ? 'selected' : '' }}>سداد</option>
                            <option value="initial_balance" {{ request('transaction_type') == 'initial_balance' ? 'selected' : '' }}>رصيد افتتاحي</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">من تاريخ - إلى تاريخ</label>
                        <div class="input-group">
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">&nbsp;</label><br>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> بحث
                        </button>
                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> إعادة تعيين
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">حركات الحساب</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%">التاريخ</th>
                            <th style="width: 15%">نوع العملية</th>
                            <th style="width: 15%">رقم المرجع</th>
                            <th style="width: 15%">مدين (له)</th>
                            <th style="width: 15%">دائن (عليه)</th>
                            <th style="width: 15%">الرصيد</th>
                            <th style="width: 15%">ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledgerEntries as $entry)
                            <tr>
                                <td>{{ $entry->transaction_date->format('Y-m-d') }}</td>
                                <td>
                                    <span class="badge {{ $entry->transaction_type_badge }}">
                                        <i class="{{ $entry->transaction_type_icon }}"></i>
                                        {{ $entry->transaction_type_name }}
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ $entry->reference_number ?? '-' }}</strong>
                                </td>
                                <td>
                                    @if($entry->debit > 0)
                                        <span class="text-success fw-bold">{{ number_format($entry->debit, 2) }} ج.م</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($entry->credit > 0)
                                        <span class="text-danger fw-bold">{{ number_format($entry->credit, 2) }} ج.م</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($entry->balance > 0)
                                        <span class="text-success fw-bold">{{ number_format($entry->balance, 2) }} ج.م (له)</span>
                                    @elseif($entry->balance < 0)
                                        <span class="text-danger fw-bold">{{ number_format(abs($entry->balance), 2) }} ج.م (عليه)</span>
                                    @else
                                        <span class="text-muted">0.00 ج.م</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $entry->notes ?? '-' }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    لا توجد حركات مسجلة لهذا العميل
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $ledgerEntries->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        .no-print {
            display: none !important;
        }
        
        .card {
            border: none;
            box-shadow: none;
        }
        
        body {
            background: white;
        }
        
        table {
            font-size: 12px;
        }
    }
</style>
@endpush
