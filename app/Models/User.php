<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'assigned_branch_id',
        'current_branch_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    /**
     * Get all tokens for the user
     */
    public function tokens()
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }
    
    /**
     * Create a new personal access token
     */
    public function createToken(string $name, array $abilities = ['*'])
    {
        $plainTextToken = \Illuminate\Support\Str::random(40);
        $token = hash('sha256', $plainTextToken);

        $accessToken = $this->tokens()->create([
            'name' => $name,
            'token' => $token,
            'abilities' => $abilities,
        ]);

        return new class($accessToken, $plainTextToken) {
            public function __construct(
                public $accessToken,
                public $plainTextToken
            ) {}
        };
    }
    
    /**
     * Get current access token
     */
    public function currentAccessToken()
    {
        return request()->attributes->get('sanctum_token');
    }

    // ==================== Branch Relationships ====================
    
    /**
     * المخزن الافتراضي للمستخدم
     */
    public function assignedBranch()
    {
        return $this->belongsTo(Branch::class, 'assigned_branch_id');
    }

    /**
     * المخزن النشط حاليًا
     */
    public function currentBranch()
    {
        return $this->belongsTo(Branch::class, 'current_branch_id');
    }

    /**
     * صلاحيات المستخدم على المخازن
     */
    public function branchPermissions()
    {
        return $this->hasMany(UserBranchPermission::class);
    }

    /**
     * المخازن المصرح للمستخدم بالوصول إليها (مع معلومات الصلاحية)
     */
    public function authorizedBranches()
    {
        return $this->belongsToMany(Branch::class, 'user_branch_permissions')
                    ->withPivot('permission_level')
                    ->withTimestamps();
    }

    // ==================== Branch Permission Methods ====================

    /**
     * التحقق من صلاحية المستخدم على مخزن معين
     * 
     * @param int|Branch $branch
     * @param string|null $requiredLevel 'view_only' or 'full_access'
     * @return bool
     */
    public function canAccessBranch($branch, ?string $requiredLevel = null): bool
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        // Super admin يقدر يوصل لكل المخازن
        if ($this->hasRole('super-admin')) {
            return true;
        }

        $permission = $this->branchPermissions()
                           ->where('branch_id', $branchId)
                           ->first();

        if (!$permission) {
            return false;
        }

        // إذا مافيش مستوى مطلوب، نرجع true (عنده أي صلاحية)
        if ($requiredLevel === null) {
            return true;
        }

        // إذا المطلوب full_access، نتحقق
        if ($requiredLevel === UserBranchPermission::PERMISSION_FULL_ACCESS) {
            return $permission->isFullAccess();
        }

        // إذا المطلوب view_only، أي صلاحية كافية
        return true;
    }

    /**
     * هل المستخدم له صلاحيات كاملة على المخزن؟
     */
    public function hasFullAccessToBranch($branch): bool
    {
        return $this->canAccessBranch($branch, UserBranchPermission::PERMISSION_FULL_ACCESS);
    }

    /**
     * تبديل المخزن النشط
     * 
     * @param int|Branch $branch
     * @return bool
     */
    public function switchBranch($branch): bool
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        // التحقق من الصلاحية
        if (!$this->canAccessBranch($branchId)) {
            return false;
        }

        $this->update(['current_branch_id' => $branchId]);
        return true;
    }

    /**
     * الحصول على المخزن النشط أو الافتراضي
     * 
     * @return Branch|null
     */
    public function getActiveBranch(): ?Branch
    {
        // إذا فيه مخزن نشط، نرجعه
        if ($this->current_branch_id) {
            return $this->currentBranch;
        }

        // إذا فيه مخزن افتراضي، نرجعه
        if ($this->assigned_branch_id) {
            return $this->assignedBranch;
        }

        // نرجع أول مخزن مصرح له (إن وُجد)
        return $this->authorizedBranches()->first();
    }

    /**
     * الحصول على قائمة المخازن المصرح بها مع معلومات الصلاحيات
     * 
     * @return \Illuminate\Support\Collection
     */
    public function getAuthorizedBranchesWithPermissions()
    {
        // Super admin يشوف كل المخازن
        if ($this->hasRole('super-admin')) {
            return Branch::all()->map(function ($branch) {
                $branch->permission_level = UserBranchPermission::PERMISSION_FULL_ACCESS;
                $branch->is_assigned = $branch->id === $this->assigned_branch_id;
                $branch->is_current = $branch->id === $this->current_branch_id;
                return $branch;
            });
        }

        return $this->authorizedBranches()
                    ->get()
                    ->map(function ($branch) {
                        $branch->permission_level = $branch->pivot->permission_level;
                        $branch->is_assigned = $branch->id === $this->assigned_branch_id;
                        $branch->is_current = $branch->id === $this->current_branch_id;
                        return $branch;
                    });
    }
}
