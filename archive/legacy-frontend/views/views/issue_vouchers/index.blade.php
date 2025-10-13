@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>أذون الصرف</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('issue-vouchers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> إذن صرف جديد
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- البحث والفلترة -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('issue-vouchers.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">رقم الإذن</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="ISS-00001">
                </div>
                <div class="col-md-2">
                    <label for="branch_id" class="form-label">الفرع</label>
                    <select class="form-select" id="branch_id" name="branch_id">
                        <option value="">الكل</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">الحالة</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">الكل</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغي</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="from_date" class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="from_date" name="from_date" 
                           value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <label for="to_date" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="to_date" name="to_date" 
                           value="{{ request('to_date') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول الأذونات -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>رقم الإذن</th>
                            <th>التاريخ</th>
                            <th>العميل</th>
                            <th>الفرع</th>
                            <th>عدد الأصناف</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                            <tr>
                                <td>{{ $loop->iteration + ($vouchers->currentPage() - 1) * $vouchers->perPage() }}</td>
                                <td>
                                    <strong>{{ $voucher->voucher_number }}</strong>
                                </td>
                                <td>{{ $voucher->issue_date->format('Y-m-d') }}</td>
                                <td>{{ $voucher->customer_display_name }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $voucher->branch->name }}</span>
                                </td>
                                <td>{{ $voucher->items->count() }} صنف</td>
                                <td>{{ number_format($voucher->total_amount, 2) }} ج.م</td>
                                <td>
                                    @if($voucher->status === 'completed')
                                        <span class="badge bg-success">مكتمل</span>
                                    @else
                                        <span class="badge bg-danger">ملغي</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('issue-vouchers.show', $voucher) }}" 
                                           class="btn btn-sm btn-info" title="عرض/طباعة">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        @if($voucher->status === 'completed')
                                            <form action="{{ route('issue-vouchers.destroy', $voucher) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الإذن؟ سيتم إرجاع المخزون.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="إلغاء">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted">لا توجد أذونات صرف</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($vouchers->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $vouchers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
