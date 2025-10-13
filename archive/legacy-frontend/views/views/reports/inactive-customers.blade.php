@extends('layouts.app')

@section('title', 'تقرير العملاء غير النشطين')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">
        <i class="bi bi-person-x"></i>
        تقرير العملاء غير النشطين
    </h2>

    <!-- Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">فترة عدم النشاط (بالأشهر)</label>
                    <input type="number" name="months" class="form-control" value="{{ $months }}" min="1" max="120">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> بحث
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        عرض العملاء الذين لم ينشطوا منذ <strong>{{ $months }}</strong> شهر أو أكثر
        | <strong>إجمالي:</strong> {{ $customers->total() }} عميل
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">قائمة العملاء غير النشطين</h5>
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
                            <th>آخر نشاط</th>
                            <th>مدة عدم النشاط</th>
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
                                <td>{{ $customer->last_activity_at?->format('Y-m-d') ?? 'لم ينشط أبداً' }}</td>
                                <td>
                                    @if($customer->last_activity_at)
                                        {{ $customer->last_activity_at->diffForHumans() }}
                                    @else
                                        <span class="text-danger">لا يوجد نشاط</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-check-circle text-success fs-1"></i>
                                    <p class="mt-2">ممتاز! جميع العملاء نشطون</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($customers->hasPages())
            <div class="card-footer">
                {{ $customers->appends(['months' => $months])->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
