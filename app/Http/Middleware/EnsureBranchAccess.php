<?php

namespace App\Http\Middleware;

use App\Models\UserBranchPermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchAccess
{
    /**
     * Handle an incoming request.
     * 
     * التحقق من أن المستخدم له صلاحية الوصول للمخزن المطلوب
     * ومستوى الصلاحية كافي للعملية المطلوبة
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $requiredPermission = 'view_only'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'غير مصرح لك بالوصول',
            ], 401);
        }

        // استخراج branch_id من الـ request
        $branchId = $this->getBranchIdFromRequest($request);

        // إذا مافيش branch_id، نستخدم المخزن النشط للمستخدم
        if (!$branchId) {
            $activeBranch = $user->getActiveBranch();
            if ($activeBranch) {
                $branchId = $activeBranch->id;
                // نحفظه في الـ request عشان الـ controller يستخدمه
                $request->merge(['branch_id' => $branchId]);
            }
        }

        // إذا لسه مافيش branch_id، نرجع error
        if (!$branchId) {
            return response()->json([
                'message' => 'يجب تحديد المخزن المطلوب',
            ], 422);
        }

        // التحقق من الصلاحية
        $hasAccess = false;

        if ($requiredPermission === 'full_access') {
            $hasAccess = $user->hasFullAccessToBranch($branchId);
        } else {
            $hasAccess = $user->canAccessBranch($branchId);
        }

        if (!$hasAccess) {
            return response()->json([
                'message' => 'ليس لديك صلاحية الوصول لهذا المخزن',
            ], 403);
        }

        // حفظ معلومات المخزن في الـ request
        $request->attributes->set('current_branch_id', $branchId);
        $request->attributes->set('can_edit_branch', $user->hasFullAccessToBranch($branchId));

        return $next($request);
    }

    /**
     * استخراج branch_id من الـ request
     * يبحث في route parameters, query string, أو request body
     */
    private function getBranchIdFromRequest(Request $request): ?int
    {
        // محاولة الحصول عليه من route parameters
        if ($request->route('branch')) {
            $branch = $request->route('branch');
            return is_object($branch) ? $branch->id : (int) $branch;
        }

        if ($request->route('branch_id')) {
            return (int) $request->route('branch_id');
        }

        // محاولة الحصول عليه من query string
        if ($request->has('branch_id')) {
            return (int) $request->input('branch_id');
        }

        // محاولة الحصول عليه من request body
        if ($request->filled('branch_id')) {
            return (int) $request->input('branch_id');
        }

        // محاولة الحصول عليه من voucher/model relationships
        // مثلاً لو في issue_voucher أو return_voucher
        if ($request->route('issueVoucher')) {
            $voucher = $request->route('issueVoucher');
            return is_object($voucher) ? $voucher->branch_id : null;
        }

        if ($request->route('returnVoucher')) {
            $voucher = $request->route('returnVoucher');
            return is_object($voucher) ? $voucher->branch_id : null;
        }

        return null;
    }
}
