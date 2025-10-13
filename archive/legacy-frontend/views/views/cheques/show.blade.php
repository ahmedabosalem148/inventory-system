@extends('layouts.app')

@section('title', 'تفاصيل الشيك #' . $cheque->id)

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="bi bi-credit-card ms-2"></i>
                    تفاصيل الشيك #{{ $cheque->id }}
                </h2>
                <a href="{{ route('cheques.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-right ms-1"></i>
                    رجوع
                </a>
            </div>

            <!-- حالة الشيك -->
            <div class="alert alert-{{ 
                $cheque->status === 'PENDING' ? 'warning' : 
                ($cheque->status === 'CLEARED' ? 'success' : 'danger') 
            }} d-flex align-items-center mb-4">
                @php
                    $statusConfig = [
                        'PENDING' => ['icon' => 'clock', 'text' => 'الشيك معلق وبانتظار الصرف'],
                        'CLEARED' => ['icon' => 'check-circle', 'text' => 'تم صرف الشيك بنجاح'],
                        'BOUNCED' => ['icon' => 'x-circle', 'text' => 'الشيك مرتد'],
                    ];
                    $status = $statusConfig[$cheque->status] ?? ['icon' => 'question', 'text' => 'حالة غير معروفة'];
                @endphp
                <i class="bi bi-{{ $status['icon'] }} fs-4 ms-3"></i>
                <div class="flex-fill">
                    <strong>{{ $status['text'] }}</strong>
                    @if($cheque->status === 'PENDING' && $cheque->due_date < now())
                        <br>
                        <small>
                            <i class="bi bi-exclamation-triangle ms-1"></i>
                            تحذير: تجاوز تاريخ الاستحقاق ({{ \Carbon\Carbon::parse($cheque->due_date)->diffForHumans() }})
                        </small>
                    @endif
                </div>
            </div>

            <!-- معلومات الشيك الأساسية -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-info-circle ms-1"></i>
                    معلومات الشيك
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">رقم الشيك</label>
                            <div class="fw-bold fs-5">{{ $cheque->cheque_number }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">اسم البنك</label>
                            <div class="fw-bold fs-5">{{ $cheque->bank_name }}</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">العميل</label>
                            <div>
                                <a href="{{ route('customers.show', $cheque->customer) }}" class="text-decoration-none fw-bold">
                                    {{ $cheque->customer->name }}
                                    @if($cheque->customer->code)
                                        <span class="text-muted">({{ $cheque->customer->code }})</span>
                                    @endif
                                </a>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">المبلغ</label>
                            <div class="fw-bold fs-4 text-success">
                                {{ number_format($cheque->amount, 2) }} ريال
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">تاريخ الاستحقاق</label>
                            <div class="fw-bold">
                                {{ $cheque->due_date }}
                                @if($cheque->status === 'PENDING')
                                    @php
                                        $daysLeft = \Carbon\Carbon::parse($cheque->due_date)->diffInDays(now(), false);
                                    @endphp
                                    @if($daysLeft > 0)
                                        <span class="badge bg-danger me-2">متأخر {{ $daysLeft }} يوم</span>
                                    @elseif($daysLeft > -7)
                                        <span class="badge bg-warning me-2">بعد {{ abs($daysLeft) }} يوم</span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">الحالة</label>
                            <div>
                                @php
                                    $statusDisplay = [
                                        'PENDING' => ['class' => 'warning', 'icon' => 'clock', 'text' => 'معلق'],
                                        'CLEARED' => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'تم الصرف'],
                                        'BOUNCED' => ['class' => 'danger', 'icon' => 'x-circle', 'text' => 'مرتد'],
                                    ];
                                    $display = $statusDisplay[$cheque->status] ?? ['class' => 'secondary', 'icon' => 'question', 'text' => 'غير معروف'];
                                @endphp
                                <span class="badge bg-{{ $display['class'] }} px-3 py-2">
                                    <i class="bi bi-{{ $display['icon'] }} ms-1"></i>
                                    {{ $display['text'] }}
                                </span>
                            </div>
                        </div>

                        @if($cheque->notes)
                        <div class="col-md-12">
                            <label class="text-muted small mb-1">ملاحظات</label>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-chat-left-text ms-1"></i>
                                {{ $cheque->notes }}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- معلومات الصرف (إذا تم الصرف) -->
            @if($cheque->status === 'CLEARED')
            <div class="card shadow-sm mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-check-circle ms-1"></i>
                    معلومات الصرف
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="text-muted small mb-1">تاريخ الصرف</label>
                            <div class="fw-bold">{{ $cheque->cleared_at?->format('Y-m-d H:i') }}</div>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="text-muted small mb-1">تم الصرف بواسطة</label>
                            <div class="fw-bold">{{ $cheque->clearedBy?->name ?? 'غير معروف' }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- معلومات الإرجاع (إذا تم الإرجاع) -->
            @if($cheque->status === 'BOUNCED')
            <div class="card shadow-sm mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-x-circle ms-1"></i>
                    معلومات الإرجاع
                </div>
                <div class="card-body">
                    @if($cheque->return_reason)
                    <div class="mb-3">
                        <label class="text-muted small mb-1">سبب الإرجاع</label>
                        <div class="alert alert-danger mb-0">
                            <i class="bi bi-exclamation-triangle ms-1"></i>
                            {{ $cheque->return_reason }}
                        </div>
                    </div>
                    @endif
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">تاريخ الإرجاع</label>
                            <div>{{ $cheque->updated_at->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- السداد المرتبط -->
            @if($cheque->payment)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-receipt ms-1"></i>
                    السداد المرتبط
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="mb-2">
                                <label class="text-muted small mb-1">رقم السداد</label>
                                <div class="fw-bold">#{{ $cheque->payment->id }}</div>
                            </div>
                            <div class="mb-2">
                                <label class="text-muted small mb-1">تاريخ السداد</label>
                                <div>{{ $cheque->payment->payment_date }}</div>
                            </div>
                            <div>
                                <label class="text-muted small mb-1">المبلغ</label>
                                <div class="fw-bold text-success">{{ number_format($cheque->payment->amount, 2) }} ريال</div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="{{ route('payments.show', $cheque->payment) }}" class="btn btn-info">
                                <i class="bi bi-eye ms-1"></i>
                                عرض السداد
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- إذن الصرف المرتبط -->
            @if($cheque->issueVoucher)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-file-earmark-text ms-1"></i>
                    إذن الصرف المرتبط
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="mb-2">
                                <label class="text-muted small mb-1">رقم الإذن</label>
                                <div class="fw-bold">#{{ $cheque->issueVoucher->id }}</div>
                            </div>
                            <div class="mb-2">
                                <label class="text-muted small mb-1">تاريخ الإصدار</label>
                                <div>{{ $cheque->issueVoucher->issue_date }}</div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="{{ route('issue-vouchers.show', $cheque->issueVoucher) }}" class="btn btn-secondary">
                                <i class="bi bi-eye ms-1"></i>
                                عرض الإذن
                            </a>
                        </div>
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
                            <div>{{ $cheque->creator->name ?? 'غير معروف' }}</div>
                        </div>

                        <div class="col-md-6 mb-2">
                            <label class="text-muted small mb-1">تاريخ الإنشاء</label>
                            <div>{{ $cheque->created_at->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- أزرار الإجراءات -->
            @if($cheque->status === 'PENDING')
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <button type="button" 
                                class="btn btn-success" 
                                data-bs-toggle="modal" 
                                data-bs-target="#clearModal">
                            <i class="bi bi-check-circle ms-1"></i>
                            صرف الشيك
                        </button>
                        
                        <button type="button" 
                                class="btn btn-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#returnModal">
                            <i class="bi bi-x-circle ms-1"></i>
                            إرجاع الشيك
                        </button>

                        @if($cheque->payment)
                            <a href="{{ route('payments.show', $cheque->payment) }}" class="btn btn-outline-info">
                                <i class="bi bi-receipt ms-1"></i>
                                عرض السداد
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Modal صرف الشيك -->
            <div class="modal fade" id="clearModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('cheques.clear', $cheque) }}" method="POST">
                            @csrf
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">
                                    <i class="bi bi-check-circle ms-1"></i>
                                    تأكيد صرف الشيك
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-success">
                                    <i class="bi bi-info-circle ms-1"></i>
                                    هل أنت متأكد من صرف هذا الشيك؟
                                </div>
                                <ul class="list-unstyled">
                                    <li><strong>رقم الشيك:</strong> {{ $cheque->cheque_number }}</li>
                                    <li><strong>العميل:</strong> {{ $cheque->customer->name }}</li>
                                    <li><strong>المبلغ:</strong> {{ number_format($cheque->amount, 2) }} ريال</li>
                                    <li><strong>البنك:</strong> {{ $cheque->bank_name }}</li>
                                    <li><strong>تاريخ الاستحقاق:</strong> {{ $cheque->due_date }}</li>
                                </ul>
                                <p class="text-muted small mb-0">
                                    * سيتم تسجيل عملية الصرف باسمك وتاريخ الصرف الحالي
                                </p>
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
            <div class="modal fade" id="returnModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('cheques.return', $cheque) }}" method="POST">
                            @csrf
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">
                                    <i class="bi bi-x-circle ms-1"></i>
                                    تأكيد إرجاع الشيك
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle ms-1"></i>
                                    <strong>تحذير:</strong> إرجاع الشيك يعني أنه مرتد ولن يُصرف
                                </div>
                                
                                <div class="mb-3">
                                    <label for="return_reason" class="form-label required">سبب الإرجاع</label>
                                    <textarea class="form-control @error('return_reason') is-invalid @enderror" 
                                              id="return_reason" 
                                              name="return_reason" 
                                              rows="4" 
                                              required 
                                              placeholder="مثل: رصيد غير كافٍ، خطأ في البيانات، توقيع غير صحيح، إلخ">{{ old('return_reason') }}</textarea>
                                    @error('return_reason')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
