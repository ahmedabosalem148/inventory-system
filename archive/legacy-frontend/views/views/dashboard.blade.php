@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2>لوحة المتابعة</h2>
            <p class="text-muted">نظرة شاملة على حالة المخزون والحسابات</p>
        </div>
    </div>

    {{-- بطاقات الإحصائيات الأساسية --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-shop text-primary fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">الفروع النشطة</h6>
                            <h3 class="mb-0">{{ $stats['branches_count'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-tags text-success fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">التصنيفات</h6>
                            <h3 class="mb-0">{{ $stats['categories_count'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-box-seam text-info fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">المنتجات</h6>
                            <h3 class="mb-0">{{ $stats['products_count'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="bi bi-cash-stack text-warning fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">قيمة المخزون</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_stock_value'], 0) }} ج.م</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row for Low Stock and Out of Stock --}}
    <div class="row g-3 mb-4">
        {{-- Low Stock Items --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="bi bi-exclamation-triangle text-warning"></i>
                                أصناف تحتاج إعادة توريد
                            </h5>
                            <small class="text-muted">أكثر 10 أصناف تحتاج إعادة توريد</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($lowStockItems->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الفرع</th>
                                        <th class="text-end">المخزون</th>
                                        <th class="text-end">الحد الأدنى</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStockItems as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->product->name }}</strong><br>
                                                <small class="text-muted">{{ $item->product->category->name ?? '-' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $item->branch->name }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-warning">{{ number_format($item->current_stock) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="text-muted">{{ number_format($item->product->min_stock) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle fs-1"></i>
                            <p class="mt-3">جميع الأصناف فوق الحد الأدنى</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Out of Stock Items --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="bi bi-x-circle text-danger"></i>
                                أصناف نفذت من المخزون
                            </h5>
                            <small class="text-muted">أصناف بمخزون صفر</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($outOfStock->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الفرع</th>
                                        <th class="text-end">آخر تحديث</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($outOfStock as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->product->name }}</strong><br>
                                                <small class="text-muted">{{ $item->product->category->name ?? '-' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $item->branch->name }}</span>
                                            </td>
                                            <td class="text-end">
                                                <small class="text-muted">{{ $item->updated_at->diffForHumans() }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle fs-1"></i>
                            <p class="mt-3">لا توجد أصناف نفذت من المخزون</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Row for Cheques --}}
    <div class="row g-3 mb-4">
        {{-- Upcoming Cheques --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="bi bi-calendar-check text-primary"></i>
                                شيكات مستحقة قريباً
                            </h5>
                            <small class="text-muted">خلال 7 أيام القادمة</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($upcomingCheques->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>رقم الشيك</th>
                                        <th>العميل</th>
                                        <th>تاريخ الاستحقاق</th>
                                        <th class="text-end">المبلغ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingCheques as $cheque)
                                        <tr>
                                            <td><strong>{{ $cheque->cheque_number }}</strong></td>
                                            <td>{{ $cheque->customer->name ?? '-' }}</td>
                                            <td>
                                                <small class="text-primary">{{ $cheque->due_date->format('Y-m-d') }}</small>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold">{{ number_format($cheque->amount, 2) }} ج.م</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1"></i>
                            <p class="mt-3">لا توجد شيكات مستحقة قريباً</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Overdue Cheques --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="bi bi-exclamation-circle text-danger"></i>
                                شيكات متأخرة
                            </h5>
                            <small class="text-muted">شيكات تجاوزت تاريخ الاستحقاق</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($overdueCheques->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>رقم الشيك</th>
                                        <th>العميل</th>
                                        <th>تاريخ الاستحقاق</th>
                                        <th class="text-end">المبلغ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($overdueCheques as $cheque)
                                        <tr class="table-danger">
                                            <td><strong>{{ $cheque->cheque_number }}</strong></td>
                                            <td>{{ $cheque->customer->name ?? '-' }}</td>
                                            <td>
                                                <small class="text-danger">{{ $cheque->due_date->format('Y-m-d') }}</small>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold text-danger">{{ number_format($cheque->amount, 2) }} ج.م</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle fs-1"></i>
                            <p class="mt-3">لا توجد شيكات متأخرة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Row for Most Active Products and Recent Vouchers --}}
    <div class="row g-3 mt-4">
        {{-- Most Active Products Widget --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="bi bi-graph-up-arrow text-success"></i>
                                أكثر الأصناف حركة
                            </h5>
                            <small class="text-muted">خلال الشهر الحالي (أعلى 10)</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($mostActiveProducts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>المنتج</th>
                                        <th>التصنيف</th>
                                        <th class="text-end">إجمالي الحركة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($mostActiveProducts as $movement)
                                        <tr>
                                            <td>
                                                <strong>{{ $movement->product->name ?? 'غير محدد' }}</strong><br>
                                                <small class="text-muted">{{ $movement->product->sku ?? '-' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $movement->product->category->name ?? '-' }}</span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-success">{{ number_format($movement->total_movement) }} وحدة</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1"></i>
                            <p class="mt-3">لا توجد حركات هذا الشهر</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Vouchers Widget --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="bi bi-file-earmark-text text-primary"></i>
                                آخر الأذون المعتمدة
                            </h5>
                            <small class="text-muted">آخر 10 أذون صرف معتمدة</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($recentVouchers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>رقم الإذن</th>
                                        <th>النوع</th>
                                        <th>العميل</th>
                                        <th>التاريخ</th>
                                        <th class="text-end">الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentVouchers as $item)
                                        @php
                                            $voucher = $item['voucher'];
                                            $type = $item['type'];
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong>#{{ $voucher->number ?? 'مسودة' }}</strong>
                                            </td>
                                            <td>
                                                @if($type === 'issue')
                                                    <span class="badge bg-primary">صرف</span>
                                                @else
                                                    <span class="badge bg-success">مرتجع</span>
                                                @endif
                                            </td>
                                            <td>{{ $voucher->customer->name ?? '-' }}</td>
                                            <td>
                                                <small class="text-muted">{{ $voucher->created_at->format('Y-m-d') }}</small>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold">{{ number_format($voucher->total_after ?? 0, 2) }} ج.م</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1"></i>
                            <p class="mt-3">لا توجد أذون معتمدة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
