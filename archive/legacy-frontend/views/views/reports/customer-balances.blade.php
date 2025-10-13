@extends('layouts.app')

@section('title', 'تقرير أرصدة العملاء')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">
        <i class="bi bi-people"></i>
        تقرير أرصدة العملاء
    </h2>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3>{{ number_format($stats['total_customers']) }}</h3>
                    <small>إجمالي العملاء</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h3>{{ number_format($stats['total_debit'], 2) }} ج.م</h3>
                    <small>إجمالي علية</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h3>{{ number_format($stats['total_credit'], 2) }} ج.م</h3>
                    <small>إجمالي له</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card {{ $stats['net_balance'] >= 0 ? 'bg-info' : 'bg-warning' }} text-white">
                <div class="card-body">
                    <h3>{{ number_format($stats['net_balance'], 2) }} ج.م</h3>
                    <small>صافي الرصيد</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">نوع الرصيد</label>
                    <select name="balance_type" class="form-select">
                        <option value="">الكل</option>
                        <option value="debit" {{ request('balance_type') == 'debit' ? 'selected' : '' }}>علية (مدين)</option>
                        <option value="credit" {{ request('balance_type') == 'credit' ? 'selected' : '' }}>له (دائن)</option>
                        <option value="zero" {{ request('balance_type') == 'zero' ? 'selected' : '' }}>رصيد صفر</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="is_active" class="form-select">
                        <option value="">الكل</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>نشط</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>غير نشط</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> بحث</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">قائمة العملاء</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>الكود</th>
                            <th>الاسم</th>
                            <th>الهاتف</th>
                            <th class="text-center">الرصيد</th>
                            <th class="text-center">عدد الفواتير</th>
                            <th class="text-center">عدد المرتجعات</th>
                            <th>آخر نشاط</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td><code>{{ $customer->code }}</code></td>
                                <td><strong>{{ $customer->name }}</strong></td>
                                <td>{{ $customer->phone ?? '-' }}</td>
                                <td class="text-center">
                                    @if($customer->balance > 0)
                                        <span class="badge bg-success">+{{ number_format($customer->balance, 2) }} ج.م</span>
                                    @elseif($customer->balance < 0)
                                        <span class="badge bg-danger">{{ number_format($customer->balance, 2) }} ج.م</span>
                                    @else
                                        <span class="badge bg-secondary">0.00 ج.م</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $customer->invoices_count }}</td>
                                <td class="text-center">{{ $customer->returns_count }}</td>
                                <td>{{ $customer->last_activity_at?->format('Y-m-d') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">لا توجد بيانات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($customers->hasPages())
            <div class="card-footer">
                {{ $customers->appends(request()->all())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
