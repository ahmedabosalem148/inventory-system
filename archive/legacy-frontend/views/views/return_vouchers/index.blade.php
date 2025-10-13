@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2>أذون الإرجاع</h2>
            <p class="text-muted">إدارة مرتجعات المنتجات من العملاء</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('return-vouchers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i>
                إذن إرجاع جديد
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- نموذج البحث والتصفية -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('return-vouchers.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">رقم الإذن</label>
                        <input type="text" name="search" class="form-control" 
                               value="{{ request('search') }}" placeholder="RET-100001">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">الكل</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" 
                                    {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">العميل</label>
                        <select name="customer_id" class="form-select">
                            <option value="">الكل</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="">الكل</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغى</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">من تاريخ - إلى تاريخ</label>
                        <div class="input-group">
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> بحث
                        </button>
                        <a href="{{ route('return-vouchers.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> إعادة تعيين
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول أذون الإرجاع -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>رقم الإذن</th>
                            <th>التاريخ</th>
                            <th>العميل</th>
                            <th>الفرع</th>
                            <th>الإجمالي</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                            <tr>
                                <td>
                                    <strong>{{ $voucher->voucher_number }}</strong>
                                </td>
                                <td>{{ $voucher->return_date->format('Y-m-d') }}</td>
                                <td>{{ $voucher->customer_display_name }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $voucher->branch->name }}</span>
                                </td>
                                <td>
                                    <strong>{{ number_format($voucher->total_amount, 2) }} ج.م</strong>
                                </td>
                                <td>
                                    @if($voucher->status === 'completed')
                                        <span class="badge bg-success">مكتمل</span>
                                    @else
                                        <span class="badge bg-danger">ملغى</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('return-vouchers.show', $voucher) }}" 
                                       class="btn btn-sm btn-info" title="عرض">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    @if($voucher->status === 'completed')
                                        <form action="{{ route('return-vouchers.destroy', $voucher) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الإذن؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="إلغاء">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    لا توجد أذون إرجاع
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $vouchers->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
