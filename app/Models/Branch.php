<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'location',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * علاقة: الفرع لديه مخزون منتجات كثيرة
     */
    public function productStocks()
    {
        return $this->hasMany(ProductBranchStock::class);
    }

    /**
     * علاقة many-to-many: الفرع لديه منتجات كثيرة
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_branch_stock')
                    ->withPivot('current_stock')
                    ->withTimestamps();
    }

    /**
     * Scope a query to only include active branches.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ==================== User Relationships ====================

    /**
     * المستخدمون المرتبطون بهذا المخزن (عبر جدول الصلاحيات)
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_branch_permissions')
                    ->withPivot('permission_level')
                    ->withTimestamps();
    }

    /**
     * صلاحيات المستخدمين على هذا المخزن
     */
    public function userPermissions()
    {
        return $this->hasMany(UserBranchPermission::class);
    }

    /**
     * المستخدمون اللي هذا مخزنهم الافتراضي
     */
    public function assignedUsers()
    {
        return $this->hasMany(User::class, 'assigned_branch_id');
    }

    /**
     * المستخدمون اللي شغالين على هذا المخزن حاليًا
     */
    public function currentUsers()
    {
        return $this->hasMany(User::class, 'current_branch_id');
    }

    // ==================== Helper Methods ====================

    /**
     * التحقق من أن المستخدم له صلاحية على هذا المخزن
     */
    public function hasUser(User $user): bool
    {
        return $user->canAccessBranch($this->id);
    }

    /**
     * الحصول على صلاحية مستخدم معين على هذا المخزن
     */
    public function getPermissionLevel(User $user): ?string
    {
        $permission = $this->userPermissions()
                           ->where('user_id', $user->id)
                           ->first();

        return $permission?->permission_level;
    }
}
