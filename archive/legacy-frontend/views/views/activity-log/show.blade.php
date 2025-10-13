@extends('layouts.app')

@section('title', 'تفاصيل النشاط')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle"></i>
                        تفاصيل النشاط #{{ $activity->id }}
                    </h5>
                    <a href="{{ route('activity-log.index') }}" class="btn btn-sm btn-light">
                        <i class="bi bi-arrow-right"></i> رجوع
                    </a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- معلومات أساسية -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">المعلومات الأساسية</h6>
                            
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">رقم النشاط:</th>
                                    <td><strong>{{ $activity->id }}</strong></td>
                                </tr>
                                <tr>
                                    <th>نوع الحدث:</th>
                                    <td>
                                        @php
                                            $eventBadges = [
                                                'created' => ['class' => 'success', 'text' => 'إنشاء'],
                                                'updated' => ['class' => 'info', 'text' => 'تعديل'],
                                                'deleted' => ['class' => 'danger', 'text' => 'حذف'],
                                            ];
                                            $badge = $eventBadges[$activity->event] ?? ['class' => 'secondary', 'text' => $activity->event];
                                        @endphp
                                        <span class="badge bg-{{ $badge['class'] }}">{{ $badge['text'] }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>التاريخ والوقت:</th>
                                    <td>{{ $activity->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>المستخدم:</th>
                                    <td>
                                        @if($activity->causer)
                                            <strong>{{ $activity->causer->name }}</strong><br>
                                            <small class="text-muted">{{ $activity->causer->email }}</small>
                                        @else
                                            <span class="text-muted">النظام</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>الوصف:</th>
                                    <td>{{ $activity->description }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- معلومات الكائن المتأثر -->
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">الكائن المتأثر</h6>
                            
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">نوع الكائن:</th>
                                    <td>
                                        @php
                                            $typeNames = [
                                                'App\\Models\\Payment' => 'سداد',
                                                'App\\Models\\Cheque' => 'شيك',
                                                'App\\Models\\ReturnVoucher' => 'إذن مرتجع',
                                                'App\\Models\\IssueVoucher' => 'إذن صرف',
                                            ];
                                        @endphp
                                        {{ $typeNames[$activity->subject_type] ?? $activity->subject_type }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>معرف الكائن:</th>
                                    <td>#{{ $activity->subject_id }}</td>
                                </tr>
                                @if($activity->subject)
                                <tr>
                                    <th>حالة الكائن:</th>
                                    <td>
                                        <span class="badge bg-success">موجود</span>
                                    </td>
                                </tr>
                                @else
                                <tr>
                                    <th>حالة الكائن:</th>
                                    <td>
                                        <span class="badge bg-danger">محذوف</span>
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <!-- التغييرات (للتحديثات فقط) -->
                    @if($activity->event === 'updated' && $activity->properties && $activity->properties->has('attributes'))
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">التغييرات المسجلة</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-header bg-danger text-white">
                                            <h6 class="mb-0">القيم القديمة</h6>
                                        </div>
                                        <div class="card-body">
                                            <pre class="mb-0 small">{{ json_encode($activity->properties->get('old'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">القيم الجديدة</h6>
                                        </div>
                                        <div class="card-body">
                                            <pre class="mb-0 small">{{ json_encode($activity->properties->get('attributes'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- البيانات الكاملة (للإنشاء والحذف) -->
                    @if(in_array($activity->event, ['created', 'deleted']) && $activity->properties)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">البيانات المسجلة</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <pre class="mb-0 small">{{ json_encode($activity->properties, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
