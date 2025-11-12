<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users with filtering
     */
    public function index(Request $request)
    {
        $query = User::with(['assignedBranch', 'roles']);

        // Search by name or email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role) {
            $query->role($request->role);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform data
        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'role' => $user->roles->first()?->name ?? 'store_user',
                'branch_name' => $user->assignedBranch?->name ?? null,
                'branch_id' => $user->assigned_branch_id,
                'is_active' => $user->is_active ?? true,
                'created_at' => $user->created_at->toISOString(),
            ];
        });

        return response()->json($users);
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:admin,manager,accountant,store_user',
            'assigned_branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'assigned_branch_id' => $validated['assigned_branch_id'] ?? null,
                'current_branch_id' => $validated['assigned_branch_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Assign role
            $role = Role::findByName($validated['role']);
            $user->assignRole($role);

            DB::commit();

            // Send notification for new user
            $this->sendNewUserNotification($user, $validated['role']);

            return response()->json([
                'message' => 'تم إضافة المستخدم بنجاح',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $validated['role'],
                    'branch_id' => $user->assigned_branch_id,
                    'is_active' => $user->is_active,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل إضافة المستخدم',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load(['assignedBranch', 'roles', 'permissions']);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->roles->first()?->name ?? 'store_user',
                'branch_id' => $user->assigned_branch_id,
                'branch_name' => $user->assignedBranch?->name,
                'is_active' => $user->is_active ?? true,
                'permissions' => $user->permissions->pluck('name'),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'sometimes|required|string|in:admin,manager,accountant,store_user',
            'assigned_branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Update user basic info
            $updateData = [];
            if (isset($validated['name'])) $updateData['name'] = $validated['name'];
            if (isset($validated['email'])) $updateData['email'] = $validated['email'];
            if (isset($validated['phone'])) $updateData['phone'] = $validated['phone'];
            if (isset($validated['assigned_branch_id'])) {
                $updateData['assigned_branch_id'] = $validated['assigned_branch_id'];
                $updateData['current_branch_id'] = $validated['assigned_branch_id'];
            }
            if (isset($validated['is_active'])) $updateData['is_active'] = $validated['is_active'];
            
            // Update password if provided
            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            // Update role if provided
            if (isset($validated['role'])) {
                $user->syncRoles([$validated['role']]);
            }

            DB::commit();

            return response()->json([
                'message' => 'تم تحديث المستخدم بنجاح',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->roles->first()?->name ?? 'store_user',
                    'branch_id' => $user->assigned_branch_id,
                    'is_active' => $user->is_active,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'فشل تحديث المستخدم',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'لا يمكنك حذف حسابك الخاص',
            ], 403);
        }

        try {
            $user->delete();

            return response()->json([
                'message' => 'تم حذف المستخدم بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل حذف المستخدم',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available roles
     */
    public function getRoles()
    {
        $roles = Role::all()->map(function ($role) {
            return [
                'name' => $role->name,
                'label' => $this->getRoleLabel($role->name),
                'permissions_count' => $role->permissions->count(),
            ];
        });

        return response()->json(['data' => $roles]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'current_password' => 'required_if:user_id,' . auth()->id(),
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // If user is changing own password, verify current password
        if ($user->id === auth()->id()) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'كلمة المرور الحالية غير صحيحة',
                ], 422);
            }
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user)
    {
        // Prevent deactivating own account
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'لا يمكنك تعطيل حسابك الخاص',
            ], 403);
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return response()->json([
            'message' => $user->is_active ? 'تم تفعيل المستخدم' : 'تم تعطيل المستخدم',
            'data' => [
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Get role label in Arabic
     */
    private function getRoleLabel(string $role): string
    {
        return match($role) {
            'admin' => 'مدير نظام',
            'manager' => 'مدير',
            'accountant' => 'محاسب',
            'store_user' => 'مستخدم مخزن',
            default => $role,
        };
    }

    /**
     * Send new user notification to admins
     */
    private function sendNewUserNotification(User $user, string $role): void
    {
        try {
            $notificationService = new \App\Services\NotificationService();
            
            $roleLabel = $this->getRoleLabel($role);
            
            // Send to managers only
            $notificationService->sendToRole(
                'manager',
                \App\Models\Notification::TYPE_USER_CREATED,
                'مستخدم جديد',
                "تم إضافة مستخدم جديد: {$user->name} بصلاحية {$roleLabel}",
                [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'role' => $role,
                    'role_label' => $roleLabel,
                ],
                '#users'
            );
        } catch (\Exception $e) {
            // Log error but don't fail user creation
            \Log::error('Failed to send new user notification: ' . $e->getMessage());
        }
    }
}
