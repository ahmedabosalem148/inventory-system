@extends('layouts.app')

@section('title', 'تفاصيل السداد #' . $payment->id)

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-receipt ms-2"></i>
                    تفاصيل السداد #{{ $payment->id }}
                </h2>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-primary">
                        <i class="bi bi-printer ms-1"></i>
                        طباعة
                    </button>
                    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-right ms-1"></i>
                        رجوع
                    </a>
                </div>
            </div>

            <!-- معلومات السداد الأساسية -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-info-circle ms-1"></i>
                        معلومات السداد
                    </span>
                    @if($payment->payment_method === 'CHEQUE')
                        <span class="badge bg-light text-primary">
                            <i class="bi bi-credit-card ms-1"></i>
                            دفع بشيك
                        </span>
                    @else
                        <span class="badge bg-light text-success">
                            <i class="bi bi-cash-stack ms-1"></i>
                            دفع نقدي
                        </span>
                    @endif
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <!-- العميل -->
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">العميل</label>
                            <div class="fw-bold">
                                <a href="{{ route('customers.show', $payment->customer) }}" class="text-decoration-none">
                                    {{ $payment->customer->name }}
                                    @if($payment->customer->code)
                                        <span class="text-muted">({{ $payment->customer->code }})</span>
                                    @endif
                                </a>
                            </div>
                        </div>

                        <!-- تاريخ السداد -->
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">تاريخ السداد</label>
                            <div class="fw-bold">{{ $payment->payment_date }}</div>
                        </div>

                        <!-- المبلغ -->
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">المبلغ</label>
                            <div class="fw-bold fs-4 text-success">
                                {{ number_format($payment->amount, 2) }} ريال
                            </div>
                        </div>

                        <!-- طريقة الدفع -->
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">طريقة الدفع</label>
                            <div>
                                @if($payment->payment_method === 'CASH')
                                    <span class="badge bg-success px-3 py-2">
                                        <i class="bi bi-cash-stack ms-1"></i>
                                        نقدي
                                    </span>
                                @else
                                    <span class="badge bg-primary px-3 py-2">
                                        <i class="bi bi-credit-card ms-1"></i>
                                        شيك
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- إذن الصرف المرتبط -->
                        @if($payment->issueVoucher)
                        <div class="col-md-12 mb-3">
                            <label class="text-muted small mb-1">إذن الصرف المرتبط</label>
                            <div>
                                <a href="{{ route('issue-vouchers.show', $payment->issueVoucher) }}" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-file-earmark-text ms-1"></i>
                                    إذن صرف #{{ $payment->issueVoucher->id }}
                                </a>
                            </div>
                        </div>
                        @endif

                        <!-- ملاحظات -->
                        @if($payment->notes)
                        <div class="col-md-12">
                            <label class="text-muted small mb-1">ملاحظات</label>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-chat-left-text ms-1"></i>
                                {{ $payment->notes }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- تفاصيل الشيك -->
            @if($payment->cheque)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-credit-card ms-1"></i>
                        تفاصيل الشيك
                    </span>
                    @php
                        $statusConfig = [
                            'PENDING' => ['class' => 'warning', 'icon' => 'clock', 'text' => 'معلق'],
                            'CLEARED' => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'تم الصرف'],
                            'BOUNCED' => ['class' => 'danger', 'icon' => 'x-circle', 'text' => 'مرتد'],
                        ];
                        $status = $statusConfig[$payment->cheque->status] ?? ['class' => 'secondary', 'icon' => 'question', 'text' => 'غير معروف'];
                    @endphp
                    <span class="badge bg-{{ $status['class'] }}">
                        <i class="bi bi-{{ $status['icon'] }} ms-1"></i>
                        {{ $status['text'] }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small mb-1">رقم الشيك</label>
                            <div class="fw-bold">{{ $payment->cheque->cheque_number }}</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="text-muted small mb-1">اسم البنك</label>
                            <div class="fw-bold">{{ $payment->cheque->bank_name }}</div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="text-muted small mb-1">تاريخ الاستحقاق</label>
                            <div class="fw-bold">{{ $payment->cheque->due_date }}</div>
                        </div>

                        @if($payment->cheque->status === 'CLEARED')
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">تاريخ الصرف</label>
                            <div>{{ $payment->cheque->cleared_at?->format('Y-m-d H:i') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">تم الصرف بواسطة</label>
                            <div>{{ $payment->cheque->clearedBy?->name ?? 'غير معروف' }}</div>
                        </div>
                        @endif

                        @if($payment->cheque->status === 'BOUNCED' && $payment->cheque->return_reason)
                        <div class="col-md-12">
                            <label class="text-muted small mb-1">سبب الإرجاع</label>
                            <div class="alert alert-danger mb-0">
                                <i class="bi bi-exclamation-triangle ms-1"></i>
                                {{ $payment->cheque->return_reason }}
                            </div>
                        </div>
                        @endif

                        @if($payment->cheque->notes)
                        <div class="col-md-12 mt-2">
                            <label class="text-muted small mb-1">ملاحظات الشيك</label>
                            <div class="alert alert-info mb-0">
                                {{ $payment->cheque->notes }}
                            </div>
                        </div>
                        @endif

                        @if($payment->cheque->status === 'PENDING')
                        <div class="col-md-12 mt-3">
                            <a href="{{ route('cheques.show', $payment->cheque) }}" class="btn btn-primary">
                                <i class="bi bi-eye ms-1"></i>
                                إدارة الشيك
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- معلومات إضافية -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-clock-history ms-1"></i>
                    معلومات إضافية
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="text-muted small mb-1">أنشئ بواسطة</label>
                            <div>{{ $payment->creator->name ?? 'غير معروف' }}</div>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="text-muted small mb-1">تاريخ الإنشاء</label>
                            <div>{{ $payment->created_at->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- أزرار الإجراءات -->
            <div class="card shadow-sm print-hide">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        @if($payment->payment_method === 'CHEQUE' && $payment->cheque)
                            <a href="{{ route('cheques.show', $payment->cheque) }}" class="btn btn-info">
                                <i class="bi bi-credit-card ms-1"></i>
                                عرض الشيك
                            </a>
                        @endif
                        
                        @if($payment->issueVoucher)
                            <a href="{{ route('issue-vouchers.show', $payment->issueVoucher) }}" class="btn btn-info">
                                <i class="bi bi-file-earmark-text ms-1"></i>
                                عرض إذن الصرف
                            </a>
                        @endif

                        <form action="{{ route('payments.destroy', $payment) }}" 
                              method="POST" 
                              class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من إلغاء هذا السداد؟\n\nسيتم:\n- حذف السداد\n- إرجاع المبلغ لرصيد العميل\n- حذف الشيك المرتبط (إن وُجد ولم يُصرف)')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash ms-1"></i>
                                إلغاء السداد
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
@media print {
    .print-hide {
        display: none !important;
    }
    .btn, a {
        display: none !important;
    }
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
}
</style>
@endpush
