@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2>إذن صرف رقم: {{ $voucher->voucher_number }}</h2>
        </div>
        <div class="col-auto">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> طباعة
            </button>
                            
                            @if($voucher->status === 'completed')
                <a href="{{ route('issue-vouchers.print', $voucher->id) }}" 
                   class="btn btn-success" 
                   target="_blank">
                    <i class="bi bi-printer"></i> Ø·Ø¨Ø§Ø¹Ø© PDF
                </a>
                @endif
            <a href="{{ route('issue-vouchers.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i> رجوع
            </a>
        </div>
    </div>

    <div class="card" id="printable">
        <div class="card-body">
            <!-- Header -->
            <div class="text-center mb-4">
                <h3>إذن صرف بضاعة</h3>
                <p class="mb-0">{{ config('app.name', 'نظام إدارة المخزون') }}</p>
            </div>

            <!-- Voucher Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">رقم الإذن:</th>
                            <td><strong>{{ $voucher->voucher_number }}</strong></td>
                        </tr>
                        <tr>
                            <th>التاريخ:</th>
                            <td>{{ $voucher->issue_date->format('Y-m-d') }}</td>
                        </tr>
                        <tr>
                            <th>العميل:</th>
                            <td>{{ $voucher->customer_display_name }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">الفرع:</th>
                            <td>{{ $voucher->branch->name }}</td>
                        </tr>
                        <tr>
                            <th>الحالة:</th>
                            <td>
                                @if($voucher->status === 'completed')
                                    <span class="badge bg-success">مكتمل</span>
                                @else
                                    <span class="badge bg-danger">ملغي</span>
                                @endif
                            </td>
                        </tr>
                        @if($voucher->created_by)
                            <tr>
                                <th>المستخدم:</th>
                                <td>{{ $voucher->creator->name ?? 'غير محدد' }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Items Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="30%">المنتج</th>
                            <th width="10%">الكمية</th>
                            <th width="12%">سعر الوحدة</th>
                            <th width="12%">المجموع</th>
                            <th width="10%">نوع الخصم</th>
                            <th width="10%">قيمة الخصم</th>
                            <th width="11%">الصافي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voucher->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->product->name }}</td>
                                <td>{{ $item->quantity }} {{ $item->product->unit }}</td>
                                <td>{{ number_format($item->unit_price, 2) }} ج.م</td>
                                <td>{{ number_format($item->total_price, 2) }} ج.م</td>
                                <td>
                                    @if($item->discount_type === 'percentage')
                                        نسبة
                                    @elseif($item->discount_type === 'fixed')
                                        مبلغ
                                    @else
                                        لا يوجد
                                    @endif
                                </td>
                                <td>
                                    @if($item->discount_type === 'percentage')
                                        {{ number_format($item->discount_value, 2) }}%
                                    @elseif($item->discount_type === 'fixed')
                                        {{ number_format($item->discount_value, 2) }} ج.م
                                    @else
                                        -
                                    @endif
                                </td>
                                <td><strong>{{ number_format($item->net_price ?? $item->total_price, 2) }} ج.م</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-end"><strong>المجموع الفرعي:</strong></td>
                            <td><strong>{{ number_format($voucher->subtotal ?? $voucher->items->sum('net_price'), 2) }} ج.م</strong></td>
                        </tr>
                        @if($voucher->discount_type && $voucher->discount_type !== 'none')
                            <tr>
                                <td colspan="7" class="text-end">
                                    <strong>خصم الفاتورة 
                                        @if($voucher->discount_type === 'percentage')
                                            ({{ number_format($voucher->discount_value, 2) }}%)
                                        @elseif($voucher->discount_type === 'fixed')
                                            ({{ number_format($voucher->discount_value, 2) }} ج.م)
                                        @endif
                                        :</strong>
                                </td>
                                <td><strong class="text-danger">- {{ number_format($voucher->discount_amount, 2) }} ج.م</strong></td>
                            </tr>
                        @endif
                        <tr class="table-light">
                            <td colspan="7" class="text-end"><strong>الإجمالي النهائي:</strong></td>
                            <td><strong class="text-success">{{ number_format($voucher->net_total ?? $voucher->total_amount, 2) }} ج.م</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Notes -->
            @if($voucher->notes)
                <div class="mb-4">
                    <strong>ملاحظات:</strong>
                    <p class="mb-0">{{ $voucher->notes }}</p>
                </div>
            @endif

            <!-- Signatures -->
            <div class="row mt-5">
                <div class="col-4 text-center">
                    <div style="border-top: 1px solid #000; padding-top: 5px;">
                        توقيع المستلم
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div style="border-top: 1px solid #000; padding-top: 5px;">
                        توقيع المحاسب
                    </div>
                </div>
                <div class="col-4 text-center">
                    <div style="border-top: 1px solid #000; padding-top: 5px;">
                        توقيع المدير
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .btn, nav, .sidebar, .card-header, .alert {
        display: none !important;
    }
    
    body {
        background: white !important;
    }
    
    #printable {
        box-shadow: none !important;
        border: none !important;
    }
}
</style>
@endpush
@endsection
