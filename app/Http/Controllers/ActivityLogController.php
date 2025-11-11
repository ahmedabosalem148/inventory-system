<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    /**
     * عرض قائمة سجل الأنشطة
     */
    public function index(Request $request)
    {
        $query = Activity::with(['causer', 'subject'])
            ->latest();

        // فلترة حسب نوع النموذج
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        // فلترة حسب اسم الحدث (log_name)
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        // فلترة حسب المستخدم
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        // فلترة حسب التاريخ
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // بحث في الوصف
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 50);
        $activities = $query->paginate($perPage);

        // تحويل البيانات
        $data = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'log_name' => $activity->log_name ?? $activity->description,
                'description' => $activity->description,
                'subject_type' => $this->translateSubjectType($activity->subject_type),
                'subject_id' => $activity->subject_id,
                'causer_id' => $activity->causer_id,
                'causer_name' => $activity->causer ? $activity->causer->name : 'النظام',
                'properties' => $activity->properties,
                'created_at' => $activity->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * عرض تفاصيل نشاط معين
     */
    public function show($id)
    {
        $activity = Activity::with(['causer', 'subject'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $activity->id,
                'log_name' => $activity->log_name ?? $activity->description,
                'description' => $activity->description,
                'subject_type' => $this->translateSubjectType($activity->subject_type),
                'subject_id' => $activity->subject_id,
                'causer_id' => $activity->causer_id,
                'causer_name' => $activity->causer ? $activity->causer->name : 'النظام',
                'properties' => $activity->properties,
                'created_at' => $activity->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * الحصول على إحصائيات النشاط
     */
    public function statistics(Request $request)
    {
        $days = $request->get('days', 30);
        $fromDate = now()->subDays($days);

        $totalActivities = Activity::where('created_at', '>=', $fromDate)->count();
        
        $activitiesByLogName = Activity::select('log_name', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $fromDate)
            ->groupBy('log_name')
            ->get();

        $activitiesByUser = Activity::select('causer_id', DB::raw('count(*) as count'))
            ->with('causer:id,name')
            ->where('created_at', '>=', $fromDate)
            ->whereNotNull('causer_id')
            ->groupBy('causer_id')
            ->get()
            ->map(function ($item) {
                return [
                    'user' => $item->causer ? $item->causer->name : 'غير معروف',
                    'count' => $item->count,
                ];
            });

        $activitiesByType = Activity::select('subject_type', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $fromDate)
            ->whereNotNull('subject_type')
            ->groupBy('subject_type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $this->translateSubjectType($item->subject_type),
                    'count' => $item->count,
                ];
            });

        $recentActivities = Activity::with('causer')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer_name' => $activity->causer ? $activity->causer->name : 'النظام',
                    'created_at' => $activity->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_activities' => $totalActivities,
                'activities_by_log_name' => $activitiesByLogName,
                'activities_by_user' => $activitiesByUser,
                'activities_by_type' => $activitiesByType,
                'recent_activities' => $recentActivities,
            ],
        ]);
    }

    /**
     * الحصول على أنواع log_name المتاحة
     */
    public function getLogNames()
    {
        $logNames = Activity::select('log_name')
            ->distinct()
            ->whereNotNull('log_name')
            ->pluck('log_name');

        $translated = $logNames->map(function ($name) {
            return [
                'value' => $name,
                'label' => $this->translateLogName($name),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $translated,
        ]);
    }

    /**
     * الحصول على أنواع النماذج المتاحة
     */
    public function getSubjectTypes()
    {
        $types = Activity::select('subject_type')
            ->distinct()
            ->whereNotNull('subject_type')
            ->pluck('subject_type');

        $translated = $types->map(function ($type) {
            return [
                'value' => $type,
                'label' => $this->translateSubjectType($type),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $translated,
        ]);
    }

    /**
     * ترجمة نوع النموذج
     */
    private function translateSubjectType($type)
    {
        if (!$type) return 'غير محدد';

        $translations = [
            'App\\Models\\Payment' => 'مدفوعات',
            'App\\Models\\Cheque' => 'شيكات',
            'App\\Models\\ReturnVoucher' => 'إذونات مرتجعات',
            'App\\Models\\IssueVoucher' => 'إذونات صرف',
            'App\\Models\\User' => 'مستخدمين',
            'App\\Models\\Product' => 'منتجات',
            'App\\Models\\Customer' => 'عملاء',
            'App\\Models\\Supplier' => 'موردين',
            'App\\Models\\Branch' => 'فروع',
        ];

        return $translations[$type] ?? class_basename($type);
    }

    /**
     * ترجمة اسم الحدث
     */
    private function translateLogName($name)
    {
        $translations = [
            'created' => 'إنشاء',
            'updated' => 'تعديل',
            'deleted' => 'حذف',
            'login' => 'تسجيل دخول',
            'logout' => 'تسجيل خروج',
            'approved' => 'اعتماد',
            'cancelled' => 'إلغاء',
        ];

        return $translations[$name] ?? $name;
    }
}
