@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2>إذن إرجاع رقم: {{ $voucher->voucher_number }}</h2>
            <p class="text-muted">عرض تفاصيل إذن الإرجاع</p>
        </div>
        <div class="col-auto">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i>
                طباعة
            </button>
                            
                            @if($voucher->status === 'completed')
                <a href="{{ route('return-vouchers.print', $voucher->id) }}" 
                   class="btn btn-success" 
                   target="_blank">
                    <i class="bi bi-printer"></i> Ø·Ø¨Ø§Ø¹Ø© PDF
                </a>
                @endif
            <a href="{{ route('return-vouchers.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i>
                رجوع
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show no-print">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show no-print">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="mb-0">إذن إرجاع</h4>
                </div>
                <div class="col-auto">
                    @if($voucher->status === 'completed')
                        <span class="badge bg-success fs-6">مكتمل</span>
                    @else
                        <span class="badge bg-danger fs-6">ملغى</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- معلومات الإذن -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 40%">رقم الإذن:</th>
                            <td><strong>{{ $voucher->voucher_number }}</strong></td>
                        </tr>
                        <tr>
                            <th>التاريخ:</th>
                            <td>{{ $voucher->return_date->format('Y-m-d') }}</td>
                        </tr>
                        <tr>
                            <th>الفرع/المخزن:</th>
                            <td>
                                <span class="badge bg-secondary">{{ $voucher->branch->name }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 40%">العميل:</th>
                            <td>
                                <strong>{{ $voucher->customer_display_name }}</strong>
                                @if($voucher->customer_id)
                                    <span class="badge bg-info">مسجل</span>
                                @else
                                    <span class="badge bg-secondary">نقدي</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>المسجل بواسطة:</th>
                            <td>{{ $voucher->creator->name }}</td>
                        </tr>
                        <tr>
                            <th>تاريخ الإنشاء:</th>
                            <td>{{ $voucher->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($voucher->notes)
                <div class="alert alert-info">
                    <strong>ملاحظات:</strong> {{ $voucher->notes }}
                </div>
            @endif

            <!-- جدول الأصناف -->
            <h5 class="mb-3">الأصناف المرتجعة</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%">#</th>
                            <th style="width: 35%">المنتج</th>
                            <th style="width: 15%">الكمية</th>
                            <th style="width: 15%">سعر الوحدة</th>
                            <th style="width: 15%">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voucher->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $item->product->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $item->product->category->name }}</small>
                                </td>
                                <td>{{ $item->quantity }} {{ $item->product->unit }}</td>
                                <td>{{ number_format($item->unit_price, 2) }} ج.م</td>
                                <td><strong>{{ number_format($item->total_price, 2) }} ج.م</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-start">الإجمالي الكلي:</th>
                            <th>
                                <h5 class="mb-0 text-primary">
                                    {{ number_format($voucher->items->sum('total_price'), 2) }} ج.م
                                </h5>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- الإجراءات -->
            <div class="mt-4 no-print">
                @if($voucher->status === 'completed')
                    <form action="{{ route('return-vouchers.destroy', $voucher) }}" 
                          method="POST" class="d-inline"
                          onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الإذن؟ سيتم خصم الكميات المرتجعة من المخزون.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i>
                            إلغاء الإذن
                        </button>
                    </form>
                @endif
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
    }
</style>
@endpush
