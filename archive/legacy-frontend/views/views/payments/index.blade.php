@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2>
                <i class="bi bi-cash-coin"></i>
                المدفوعات
            </h2>
            <p class="text-muted">إدارة مدفوعات العملاء</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('payments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i>
                تسجيل مدفوع جديد
            </a>
        </div>
    </div>

    {{-- فلتر البحث --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('payments.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">العميل</label>
                        <select name="customer_id" class="form-select">
                            <option value="">-- جميع العملاء --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">طريقة الدفع</label>
                        <select name="payment_method" class="form-select">
                            <option value="">-- الكل --</option>
                            <option value="CASH" {{ request('payment_method') == 'CASH' ? 'selected' : '' }}>نقدي</option>
                            <option value="CHEQUE" {{ request('payment_method') == 'CHEQUE' ? 'selected' : '' }}>شيك</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">من تاريخ</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="submit" class="btn btn-secondary">
                            <i class="bi bi-search"></i> بحث
                        </button>
                        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> إعادة تعيين
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- إحصائيات سريعة --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-cash-stack text-success fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">إجمالي المدفوعات</h6>
                            <h4 class="mb-0">{{ number_format($payments->sum('amount'), 2) }} ج.م</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-receipt text-primary fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">عدد المدفوعات</h6>
                            <h4 class="mb-0">{{ $payments->total() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول المدفوعات --}}
    <div class="card">
        <div class="card-body p-0">
            @if($payments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>التاريخ</th>
                                <th>العميل</th>
                                <th>المبلغ</th>
                                <th>طريقة الدفع</th>
                                <th>رقم الشيك</th>
                                <th>إذن الصرف</th>
                                <th>المسجل</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                <tr>
                                    <td>{{ $payment->id }}</td>
                                    <td>
                                        <strong>{{ $payment->payment_date->format('Y-m-d') }}</strong><br>
                                        <small class="text-muted">{{ $payment->payment_date->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('customers.show', $payment->customer_id) }}" class="text-decoration-none">
                                            <strong>{{ $payment->customer->name }}</strong>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-success fs-6">{{ number_format($payment->amount, 2) }} ج.م</span>
                                    </td>
                                    <td>
                                        @if($payment->payment_method === 'CASH')
                                            <span class="badge bg-primary">
                                                <i class="bi bi-cash"></i> نقدي
                                            </span>
                                        @else
                                            <span class="badge bg-info">
                                                <i class="bi bi-credit-card-2-front"></i> شيك
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->cheque)
                                            <a href="{{ route('cheques.show', $payment->cheque->id) }}" class="text-decoration-none">
                                                {{ $payment->cheque->cheque_number }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $payment->cheque->bank_name }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->issueVoucher)
                                            <a href="{{ route('issue-vouchers.show', $payment->issueVoucher->id) }}" class="text-decoration-none">
                                                {{ $payment->issueVoucher->number }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $payment->creator->name ?? '-' }}</small>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('payments.show', $payment->id) }}" class="btn btn-sm btn-outline-primary" title="عرض">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    {{ $payments->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-3">لا توجد مدفوعات</p>
                    <a href="{{ route('payments.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> تسجيل أول مدفوع
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
