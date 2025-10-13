<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserBranchPermission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserBranchController extends Controller
{
    /**
     * المخازن المصرح للمستخدم
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $branches = $user->getAuthorizedBranchesWithPermissions();

        return response()->json([
            'data' => $branches,
            'current_branch' => $user->getActiveBranch(),
        ]);
    }

    /**
     * تبديل المخزن النشط
     */
    public function switchBranch(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $user = $request->user();

        if (!$user->switchBranch($validated['branch_id'])) {
            return response()->json([
                'message' => 'ليس لديك صلاحية الوصول لهذا المخزن',
            ], 403);
        }

        return response()->json([
            'message' => 'تم تبديل المخزن بنجاح',
            'data' => [
                'current_branch_id' => $user->fresh()->current_branch_id,
            ]
        ]);
    }

    /**
     * المخزن النشط الحالي
     */
    public function currentBranch(Request $request)
    {
        $user = $request->user();
        $activeBranch = $user->getActiveBranch();

        if (!$activeBranch) {
            return response()->json([
                'message' => 'لا يوجد مخزن نشط',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'data' => [
                'branch' => $activeBranch,
                'permission_level' => $activeBranch->getPermissionLevel($user),
                'can_edit' => $user->hasFullAccessToBranch($activeBranch),
            ]
        ]);
    }
}
