@extends('layouts.app')

@section('title', 'نتيجة الاستيراد')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card shadow-sm">
                <div class="card-header {{ $errorCount > 0 ? 'bg-warning' : 'bg-success' }} text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-{{ $errorCount > 0 ? 'exclamation-triangle' : 'check-circle' }}-fill"></i>
                        نتيجة الاستيراد
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Summary -->
                    <div class="row text-center mb-4">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h2 class="text-primary">{{ $successCount + $errorCount }}</h2>
                                    <p class="mb-0">إجمالي الأسطر</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h2>{{ $successCount }}</h2>
                                    <p class="mb-0">
                                        <i class="bi bi-check-circle"></i>
                                        نجح الاستيراد
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h2>{{ $errorCount }}</h2>
                                    <p class="mb-0">
                                        <i class="bi bi-x-circle"></i>
                                        فشل الاستيراد
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 80px;">السطر</th>
                                    <th style="width: 100px;">الحالة</th>
                                    <th>الرسالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results as $result)
                                    <tr class="{{ $result['status'] === 'success' ? 'table-success' : 'table-danger' }}">
                                        <td class="text-center fw-bold">{{ $result['row'] }}</td>
                                        <td class="text-center">
                                            @if($result['status'] === 'success')
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> نجح
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-x-circle"></i> خطأ
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $result['message'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">لا توجد نتائج</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Actions -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
                        <a href="{{ route('imports.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i>
                            استيراد ملف جديد
                        </a>
                        <a href="{{ route('products.index') }}" class="btn btn-primary">
                            <i class="bi bi-box-seam"></i>
                            عرض المنتجات
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-scroll to first error if exists
    document.addEventListener('DOMContentLoaded', function() {
        const errorRow = document.querySelector('.table-danger');
        if (errorRow) {
            errorRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>
@endpush
