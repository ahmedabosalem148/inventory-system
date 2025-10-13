@extends('layouts.app')

@section('title', 'سجل الأنشطة')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i>
                        سجل الأنشطة
                    </h5>
                </div>

                <div class="card-body">
                    <!-- فلاتر البحث -->
                    <form method="GET" action="{{ route('activity-log.index') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">نوع النشاط</label>
                                <select name="subject_type" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="App\Models\Payment" {{ request('subject_type') == 'App\Models\Payment' ? 'selected' : '' }}>
                                        المدفوعات
                                    </option>
                                    <option value="App\Models\Cheque" {{ request('subject_type') == 'App\Models\Cheque' ? 'selected' : '' }}>
                                        الشيكات
                                    </option>
                                    <option value="App\Models\ReturnVoucher" {{ request('subject_type') == 'App\Models\ReturnVoucher' ? 'selected' : '' }}>
                                        إذونات المرتجعات
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">الحدث</label>
                                <select name="event" class="form-select">
                                    <option value="">الكل</option>
                                    <option value="created" {{ request('event') == 'created' ? 'selected' : '' }}>إنشاء</option>
                                    <option value="updated" {{ request('event') == 'updated' ? 'selected' : '' }}>تعديل</option>
                                    <option value="deleted" {{ request('event') == 'deleted' ? 'selected' : '' }}>حذف</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">من تاريخ</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">إلى تاريخ</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> بحث
                                    </button>
                                    <a href="{{ route('activity-log.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> إعادة تعيين
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- جدول الأنشطة -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">التاريخ والوقت</th>
                                    <th width="15%">المستخدم</th>
                                    <th width="10%">الحدث</th>
                                    <th width="15%">النوع</th>
                                    <th width="30%">الوصف</th>
                                    <th width="10%">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activities as $activity)
                                <tr>
                                    <td>{{ $activity->id }}</td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $activity->created_at->format('Y-m-d') }}<br>
                                            {{ $activity->created_at->format('H:i:s') }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($activity->causer)
                                            <strong>{{ $activity->causer->name }}</strong><br>
                                            <small class="text-muted">{{ $activity->causer->email }}</small>
                                        @else
                                            <span class="text-muted">النظام</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $eventBadges = [
                                                'created' => ['class' => 'success', 'icon' => 'plus-circle', 'text' => 'إنشاء'],
                                                'updated' => ['class' => 'info', 'icon' => 'pencil-square', 'text' => 'تعديل'],
                                                'deleted' => ['class' => 'danger', 'icon' => 'trash', 'text' => 'حذف'],
                                            ];
                                            $badge = $eventBadges[$activity->event] ?? ['class' => 'secondary', 'icon' => 'question', 'text' => $activity->event];
                                        @endphp
                                        <span class="badge bg-{{ $badge['class'] }}">
                                            <i class="bi bi-{{ $badge['icon'] }}"></i>
                                            {{ $badge['text'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $typeNames = [
                                                'App\\Models\\Payment' => 'سداد',
                                                'App\\Models\\Cheque' => 'شيك',
                                                'App\\Models\\ReturnVoucher' => 'إذن مرتجع',
                                                'App\\Models\\IssueVoucher' => 'إذن صرف',
                                            ];
                                        @endphp
                                        <small class="text-muted">
                                            {{ $typeNames[$activity->subject_type] ?? $activity->subject_type }}
                                            <br>
                                            #{{ $activity->subject_id }}
                                        </small>
                                    </td>
                                    <td>
                                        <small>{{ $activity->description }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('activity-log.show', $activity) }}" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        لا توجد أنشطة مسجلة
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($activities->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $activities->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
