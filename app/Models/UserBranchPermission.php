<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBranchPermission extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'permission_level',
    ];

    protected $casts = [
        'permission_level' => 'string',
    ];

    /**
     * Permission level constants
     */
    const PERMISSION_VIEW_ONLY = 'view_only';
    const PERMISSION_FULL_ACCESS = 'full_access';

    /**
     * العلاقة مع المستخدم
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * العلاقة مع المخزن
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Check if permission is full access
     */
    public function isFullAccess(): bool
    {
        return $this->permission_level === self::PERMISSION_FULL_ACCESS;
    }

    /**
     * Check if permission is view only
     */
    public function isViewOnly(): bool
    {
        return $this->permission_level === self::PERMISSION_VIEW_ONLY;
    }

    /**
     * Scope: Full access permissions only
     */
    public function scopeFullAccess($query)
    {
        return $query->where('permission_level', self::PERMISSION_FULL_ACCESS);
    }

    /**
     * Scope: View only permissions
     */
    public function scopeViewOnly($query)
    {
        return $query->where('permission_level', self::PERMISSION_VIEW_ONLY);
    }
}
