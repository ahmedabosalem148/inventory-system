<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * تغيير كلمة مرور المستخدم الحالي
     */
    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()],
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة',
            'password.required' => 'كلمة المرور الجديدة مطلوبة',
            'password.confirmed' => 'كلمة المرور الجديدة وتأكيدها غير متطابقين',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
        ]);

        // التحقق من كلمة المرور الحالية
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة',
            ], 422);
        }

        // التحقق من أن كلمة المرور الجديدة مختلفة عن الحالية
        if (Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الجديدة يجب أن تكون مختلفة عن كلمة المرور الحالية',
            ], 422);
        }

        // تحديث كلمة المرور
        $user->password = Hash::make($validated['password']);
        $user->save();

        // تسجيل النشاط
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->log('قام المستخدم بتغيير كلمة المرور الخاصة به');

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }

    /**
     * الحصول على بيانات المستخدم الحالي
     */
    public function show()
    {
        $user = auth()->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->roles->first()?->name ?? 'user',
                'assigned_branch_id' => $user->assigned_branch_id,
                'current_branch_id' => $user->current_branch_id,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * تحديث بيانات المستخدم الحالي
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
        ], [
            'name.required' => 'الاسم مطلوب',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرف',
            'phone.max' => 'رقم الهاتف يجب ألا يتجاوز 20 رقم',
        ]);

        $user->update($validated);

        // تسجيل النشاط
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->log('قام المستخدم بتحديث بياناته الشخصية');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث البيانات بنجاح',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->roles->first()?->name ?? 'user',
            ],
        ]);
    }
}
