@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>إدارة العملاء</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> إضافة عميل جديد
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
            <form method="GET" action="{{ route('customers.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">البحث بالاسم أو الهاتف</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="ابحث عن عميل...">
                </div>
                <div class="col-md-3">
                    <label for="balance_filter" class="form-label">الرصيد</label>
                    <select class="form-select" id="balance_filter" name="balance_filter">
                        <option value="">الكل</option>
                        <option value="credit" {{ request('balance_filter') === 'credit' ? 'selected' : '' }}>
                            له (دائن)
                        </option>
                        <option value="debit" {{ request('balance_filter') === 'debit' ? 'selected' : '' }}>
                            عليه (مدين)
                        </option>
                        <option value="zero" {{ request('balance_filter') === 'zero' ? 'selected' : '' }}>
                            متزن
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="is_active" class="form-label">الحالة</label>
                    <select class="form-select" id="is_active" name="is_active">
                        <option value="">الكل</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>نشط</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>غير نشط</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> بحث
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول العملاء -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم العميل</th>
                            <th>الهاتف</th>
                            <th>العنوان</th>
                            <th>الرصيد</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</td>
                                <td>
                                    <strong>{{ $customer->name }}</strong>
                                    @if($customer->notes)
                                        <br><small class="text-muted">{{ Str::limit($customer->notes, 40) }}</small>
                                    @endif
                                </td>
                                <td>{{ $customer->phone ?? '-' }}</td>
                                <td>{{ Str::limit($customer->address ?? '-', 30) }}</td>
                                <td>
                                    @php
                                        $balance = $customer->balance;
                                        $badgeClass = 'bg-secondary';
                                        if ($balance > 0) {
                                            $badgeClass = 'bg-success';
                                        } elseif ($balance < 0) {
                                            $badgeClass = 'bg-danger';
                                        }
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">
                                        {{ $customer->formatted_balance }}
                                    </span>
                                </td>
                                <td>
                                    @if($customer->is_active)
                                        <span class="badge bg-success">نشط</span>
                                    @else
                                        <span class="badge bg-danger">غير نشط</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('customers.show', $customer) }}" 
                                           class="btn btn-sm btn-info" title="دفتر الحساب">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                        <a href="{{ route('customers.edit', $customer) }}" 
                                           class="btn btn-sm btn-warning" title="تعديل">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('customers.destroy', $customer) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('هل أنت متأكد من حذف هذا العميل؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="حذف">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">لا يوجد عملاء</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($customers->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
