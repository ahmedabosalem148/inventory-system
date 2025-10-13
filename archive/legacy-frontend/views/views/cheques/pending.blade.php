@extends('layouts.app')

@section('title', 'تقرير الشيكات المعلقة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-clock-history ms-2"></i>
            تقرير الشيكات المعلقة
        </h2>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer ms-1"></i>
                طباعة
            </button>
            <a href="{{ route('cheques.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-right ms-1"></i>
                رجوع للشيكات
            </a>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">إجمالي الشيكات المعلقة</h6>
                            <h3 class="mb-0">{{ $pendingCheques->count() }}</h3>
                        </div>
                        <div class="fs-1 text-warning">
                            <i class="bi bi-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-danger shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">شيكات متأخرة</h6>
                            <h3 class="mb-0">{{ $overdueCheques->count() }}</h3>
                        </div>
                        <div class="fs-1 text-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">تستحق خلال 7 أيام</h6>
                            <h3 class="mb-0">{{ $dueSoonCheques->count() }}</h3>
                        </div>
                        <div class="fs-1 text-info">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الشيكات المتأخرة (أولوية عالية) -->
    @if($overdueCheques->count() > 0)
    <div class="card shadow-sm mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <i class="bi bi-exclamation-triangle ms-1"></i>
            شيكات متأخرة - أولوية عالية
            <span class="badge bg-white text-danger me-2">{{ $overdueCheques->count() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>رقم الشيك</th>
                            <th>العميل</th>
                            <th>البنك</th>
                            <th>المبلغ</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>التأخير</th>
                            <th class="print-hide">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overdueCheques as $cheque)
                            <tr class="table-danger">
                                <td>
                                    <strong>{{ $cheque->cheque_number }}</strong>
                                    <br>
                                    <small class="text-muted">#{{ $cheque->id }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('customers.show', $cheque->customer) }}" class="text-decoration-none">
                                        {{ $cheque->customer->name }}
                                    </a>
                                    @if($cheque->customer->code)
                                        <br><small class="text-muted">{{ $cheque->customer->code }}</small>
                                    @endif
                                </td>
                                <td>{{ $cheque->bank_name }}</td>
                                <td>
                                    <span class="fw-bold text-danger">{{ number_format($cheque->amount, 2) }}</span> ريال
                                </td>
                                <td>{{ $cheque->due_date }}</td>
                                <td>
                                    @php
                                        $daysOverdue = \Carbon\Carbon::parse($cheque->due_date)->diffInDays(now());
                                    @endphp
                                    <span class="badge bg-danger">
                                        <i class="bi bi-exclamation-triangle ms-1"></i>
                                        {{ $daysOverdue }} يوم
                                    </span>
                                </td>
                                <td class="print-hide">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('cheques.show', $cheque) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="عرض">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#clearModal{{ $cheque->id }}"
                                                title="صرف">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#returnModal{{ $cheque->id }}"
                                                title="إرجاع">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">الإجمالي:</th>
                            <th colspan="4">
                                <span class="text-danger fw-bold">
                                    {{ number_format($overdueCheques->sum('amount'), 2) }} ريال
                                </span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- الشيكات التي تستحق قريباً -->
    @if($dueSoonCheques->count() > 0)
    <div class="card shadow-sm mb-4 border-info">
        <div class="card-header bg-info text-white">
            <i class="bi bi-calendar-check ms-1"></i>
            شيكات تستحق خلال 7 أيام
            <span class="badge bg-white text-info me-2">{{ $dueSoonCheques->count() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>رقم الشيك</th>
                            <th>العميل</th>
                            <th>البنك</th>
                            <th>المبلغ</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>المتبقي</th>
                            <th class="print-hide">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dueSoonCheques as $cheque)
                            <tr class="table-warning">
                                <td>
                                    <strong>{{ $cheque->cheque_number }}</strong>
                                    <br>
                                    <small class="text-muted">#{{ $cheque->id }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('customers.show', $cheque->customer) }}" class="text-decoration-none">
                                        {{ $cheque->customer->name }}
                                    </a>
                                    @if($cheque->customer->code)
                                        <br><small class="text-muted">{{ $cheque->customer->code }}</small>
                                    @endif
                                </td>
                                <td>{{ $cheque->bank_name }}</td>
                                <td>
                                    <span class="fw-bold">{{ number_format($cheque->amount, 2) }}</span> ريال
                                </td>
                                <td>{{ $cheque->due_date }}</td>
                                <td>
                                    @php
                                        $daysLeft = \Carbon\Carbon::parse($cheque->due_date)->diffInDays(now(), false);
                                    @endphp
                                    <span class="badge bg-warning">
                                        <i class="bi bi-calendar ms-1"></i>
                                        بعد {{ abs($daysLeft) }} يوم
                                    </span>
                                </td>
                                <td class="print-hide">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('cheques.show', $cheque) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="عرض">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#clearModal{{ $cheque->id }}"
                                                title="صرف">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#returnModal{{ $cheque->id }}"
                                                title="إرجاع">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">الإجمالي:</th>
                            <th colspan="4">
                                <span class="text-info fw-bold">
                                    {{ number_format($dueSoonCheques->sum('amount'), 2) }} ريال
                                </span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- باقي الشيكات المعلقة -->
    @if($otherPendingCheques->count() > 0)
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">
            <i class="bi bi-clock ms-1"></i>
            شيكات معلقة أخرى
            <span class="badge bg-white text-secondary me-2">{{ $otherPendingCheques->count() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>رقم الشيك</th>
                            <th>العميل</th>
                            <th>البنك</th>
                            <th>المبلغ</th>
                            <th>تاريخ الاستحقاق</th>
                            <th class="print-hide">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($otherPendingCheques as $cheque)
                            <tr>
                                <td>
                                    <strong>{{ $cheque->cheque_number }}</strong>
                                    <br>
                                    <small class="text-muted">#{{ $cheque->id }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('customers.show', $cheque->customer) }}" class="text-decoration-none">
                                        {{ $cheque->customer->name }}
                                    </a>
                                    @if($cheque->customer->code)
                                        <br><small class="text-muted">{{ $cheque->customer->code }}</small>
                                    @endif
                                </td>
                                <td>{{ $cheque->bank_name }}</td>
                                <td>
                                    <span class="fw-bold">{{ number_format($cheque->amount, 2) }}</span> ريال
                                </td>
                                <td>{{ $cheque->due_date }}</td>
                                <td class="print-hide">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('cheques.show', $cheque) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="عرض">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#clearModal{{ $cheque->id }}"
                                                title="صرف">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#returnModal{{ $cheque->id }}"
                                                title="إرجاع">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">الإجمالي:</th>
                            <th colspan="3">
                                <span class="fw-bold">
                                    {{ number_format($otherPendingCheques->sum('amount'), 2) }} ريال
                                </span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- رسالة إذا لم يكن هناك شيكات معلقة -->
    @if($pendingCheques->count() === 0)
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-check-circle fs-1 text-success"></i>
            <h4 class="mt-3 text-success">ممتاز! لا توجد شيكات معلقة</h4>
            <p class="text-muted">جميع الشيكات تم صرفها أو إرجاعها</p>
            <a href="{{ route('cheques.index') }}" class="btn btn-outline-primary mt-2">
                <i class="bi bi-credit-card ms-1"></i>
                عرض جميع الشيكات
            </a>
        </div>
    </div>
    @endif

    <!-- إجمالي عام -->
    @if($pendingCheques->count() > 0)
    <div class="card shadow-sm border-primary">
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">إجمالي عدد الشيكات المعلقة</h6>
                    <h2 class="text-primary mb-0">{{ $pendingCheques->count() }}</h2>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">إجمالي قيمة الشيكات المعلقة</h6>
                    <h2 class="text-success mb-0">{{ number_format($pendingCheques->sum('amount'), 2) }} ريال</h2>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modals للصرف والإرجاع -->
@foreach($pendingCheques as $cheque)
    <!-- Modal صرف الشيك -->
    <div class="modal fade" id="clearModal{{ $cheque->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('cheques.clear', $cheque) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-check-circle ms-1"></i>
                            صرف الشيك
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>هل أنت متأكد من صرف الشيك؟</p>
                        <ul class="list-unstyled">
                            <li><strong>رقم الشيك:</strong> {{ $cheque->cheque_number }}</li>
                            <li><strong>العميل:</strong> {{ $cheque->customer->name }}</li>
                            <li><strong>المبلغ:</strong> {{ number_format($cheque->amount, 2) }} ريال</li>
                            <li><strong>البنك:</strong> {{ $cheque->bank_name }}</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle ms-1"></i>
                            تأكيد الصرف
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal إرجاع الشيك -->
    <div class="modal fade" id="returnModal{{ $cheque->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('cheques.return', $cheque) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-x-circle ms-1"></i>
                            إرجاع الشيك
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle ms-1"></i>
                            تحذير: إرجاع الشيك يعني أنه مرتد ولن يُصرف
                        </div>
                        
                        <div class="mb-3">
                            <label for="return_reason{{ $cheque->id }}" class="form-label">سبب الإرجاع *</label>
                            <textarea class="form-control" 
                                      id="return_reason{{ $cheque->id }}" 
                                      name="return_reason" 
                                      rows="3" 
                                      required 
                                      placeholder="مثل: رصيد غير كافٍ، خطأ في البيانات، إلخ"></textarea>
                        </div>
                        
                        <ul class="list-unstyled">
                            <li><strong>رقم الشيك:</strong> {{ $cheque->cheque_number }}</li>
                            <li><strong>العميل:</strong> {{ $cheque->customer->name }}</li>
                            <li><strong>المبلغ:</strong> {{ number_format($cheque->amount, 2) }} ريال</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle ms-1"></i>
                            تأكيد الإرجاع
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('styles')
<style>
@media print {
    .print-hide {
        display: none !important;
    }
    .btn, .modal {
        display: none !important;
    }
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
}
</style>
@endpush
