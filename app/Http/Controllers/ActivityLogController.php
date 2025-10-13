<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * عرض قائمة سجل الأنشطة
     */
    public function index(Request $request)
    {
        // التأكد من الصلاحية (Manager فقط)
        if (!auth()->user()->hasPermissionTo('view-activity-log')) {
            abort(403, 'غير مصرح لك بعرض سجل الأنشطة');
        }

        $query = Activity::with(['causer', 'subject'])
            ->latest();

        // فلترة حسب نوع النموذج
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        // فلترة حسب اسم الحدث (created, updated, deleted)
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // فلترة حسب المستخدم
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        // فلترة حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activities = $query->paginate(50);

        return view('activity-log.index', compact('activities'));
    }

    /**
     * عرض تفاصيل نشاط معين
     */
    public function show(Activity $activity)
    {
        // التأكد من الصلاحية
        if (!auth()->user()->hasPermissionTo('view-activity-log')) {
            abort(403, 'غير مصرح لك بعرض سجل الأنشطة');
        }

        return view('activity-log.show', compact('activity'));
    }

    /**
     * الحصول على أنواع النماذج المتاحة
     */
    public function getSubjectTypes()
    {
        return [
            'App\\Models\\Payment' => 'المدفوعات',
            'App\\Models\\Cheque' => 'الشيكات',
            'App\\Models\\ReturnVoucher' => 'إذونات المرتجعات',
            'App\\Models\\IssueVoucher' => 'إذونات الصرف',
        ];
    }
}
