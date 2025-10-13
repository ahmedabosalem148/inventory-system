@extends('layouts.app')

@section('title', 'إدارة الشيكات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-credit-card ms-2"></i>
            إدارة الشيكات
        </h2>
        <a href="{{ route('payments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle ms-1"></i>
            سداد جديد
        </a>
    </div>

    <!-- إحصائيات الشيكات -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">شيكات معلقة</h6>
                            <h3 class="mb-0">{{ $stats['pending_count'] }}</h3>
                            <small class="text-warning">{{ number_format($stats['pending_amount'], 2) }} ريال</small>
                        </div>
                        <div class="fs-1 text-warning">
                            <i class="bi bi-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-danger shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">متأخرة</h6>
                            <h3 class="mb-0">{{ $stats['overdue_count'] }}</h3>
                            <small class="text-danger">تجاوزت تاريخ الاستحقاق</small>
                        </div>
                        <div class="fs-1 text-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">تستحق خلال 7 أيام</h6>
                            <h3 class="mb-0">{{ $stats['due_soon_count'] }}</h3>
                            <small class="text-info">{{ number_format($stats['due_soon_amount'], 2) }} ريال</small>
                        </div>
                        <div class="fs-1 text-info">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">تم صرفها</h6>
                            <h3 class="mb-0">{{ $stats['cleared_count'] }}</h3>
                            <small class="text-success">الشهر الحالي</small>
                        </div>
                        <div class="fs-1 text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الفلاتر -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('cheques.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">الحالة</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">الكل</option>
                        <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>معلق</option>
                        <option value="CLEARED" {{ request('status') === 'CLEARED' ? 'selected' : '' }}>تم الصرف</option>
                        <option value="BOUNCED" {{ request('status') === 'BOUNCED' ? 'selected' : '' }}>مرتد</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="customer_id" class="form-label">العميل</label>
                    <select class="form-select" id="customer_id" name="customer_id">
                        <option value="">الكل</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="from_date" class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="from_date" name="from_date" value="{{ request('from_date') }}">
                </div>

                <div class="col-md-2">
                    <label for="to_date" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="to_date" name="to_date" value="{{ request('to_date') }}">
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-funnel ms-1"></i>
                        تصفية
                    </button>
                    <a href="{{ route('cheques.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول الشيكات -->
    <div class="card shadow-sm">
        <div class="card-body">
            @if($cheques->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>رقم الشيك</th>
                                <th>العميل</th>
                                <th>البنك</th>
                                <th>المبلغ</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>الحالة</th>
                                <th>السداد المرتبط</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cheques as $cheque)
                                <tr class="{{ $cheque->status === 'PENDING' && $cheque->due_date < now() ? 'table-danger' : '' }}">
                                    <td>{{ $cheque->id }}</td>
                                    <td>
                                        <strong>{{ $cheque->cheque_number }}</strong>
                                    </td>
                                    <td>
                                        <a href="{{ route('customers.show', $cheque->customer) }}" class="text-decoration-none">
                                            {{ $cheque->customer->name }}
                                        </a>
                                    </td>
                                    <td>{{ $cheque->bank_name }}</td>
                                    <td>
                                        <span class="fw-bold">{{ number_format($cheque->amount, 2) }}</span> ريال
                                    </td>
                                    <td>
                                        {{ $cheque->due_date }}
                                        @if($cheque->status === 'PENDING')
                                            @php
                                                $daysLeft = \Carbon\Carbon::parse($cheque->due_date)->diffInDays(now(), false);
                                            @endphp
                                            @if($daysLeft > 0)
                                                <br><small class="text-danger">متأخر {{ $daysLeft }} يوم</small>
                                            @elseif($daysLeft > -7)
                                                <br><small class="text-warning">بعد {{ abs($daysLeft) }} يوم</small>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusConfig = [
                                                'PENDING' => ['class' => 'warning', 'icon' => 'clock', 'text' => 'معلق'],
                                                'CLEARED' => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'تم الصرف'],
                                                'BOUNCED' => ['class' => 'danger', 'icon' => 'x-circle', 'text' => 'مرتد'],
                                            ];
                                            $status = $statusConfig[$cheque->status] ?? ['class' => 'secondary', 'icon' => 'question', 'text' => 'غير معروف'];
                                        @endphp
                                        <span class="badge bg-{{ $status['class'] }}">
                                            <i class="bi bi-{{ $status['icon'] }} ms-1"></i>
                                            {{ $status['text'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($cheque->payment)
                                            <a href="{{ route('payments.show', $cheque->payment) }}" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-receipt ms-1"></i>
                                                سداد #{{ $cheque->payment->id }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('cheques.show', $cheque) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="عرض">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            
                                            @if($cheque->status === 'PENDING')
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
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal صرف الشيك -->
                                @if($cheque->status === 'PENDING')
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
                                                    <p class="alert alert-warning">
                                                        <i class="bi bi-exclamation-triangle ms-1"></i>
                                                        تحذير: إرجاع الشيك يعني أنه مرتد ولن يُصرف
                                                    </p>
                                                    
                                                    <div class="mb-3">
                                                        <label for="return_reason{{ $cheque->id }}" class="form-label required">سبب الإرجاع</label>
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
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $cheques->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-credit-card fs-1 text-muted"></i>
                    <p class="mt-3 text-muted">لا توجد شيكات بعد</p>
                    <a href="{{ route('payments.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle ms-1"></i>
                        إضافة سداد بشيك
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.required::after {
    content: " *";
    color: #dc3545;
}
</style>
@endpush
