<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Filterable;

/**
 * InventoryMovement Model
 * يسجل كل حركة مخزنية (إضافة، صرف، مرتجع، تحويل)
 */
class InventoryMovement extends Model
{
    use HasFactory, Filterable;

    protected $fillable = [
        'branch_id',
        'product_id',
        'movement_type',
        'qty_units',
        'unit_price_snapshot',
        'ref_table',
        'ref_id',
        'notes',
    ];

    protected $casts = [
        'qty_units' => 'integer',
        'unit_price_snapshot' => 'decimal:2',
    ];

    /**
     * العلاقة مع الفرع
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * العلاقة مع المنتج
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope للحركات من نوع معين
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope لحركات الصرف
     */
    public function scopeIssues($query)
    {
        return $query->where('movement_type', 'ISSUE');
    }

    /**
     * Scope لحركات المرتجعات
     */
    public function scopeReturns($query)
    {
        return $query->where('movement_type', 'RETURN');
    }

    /**
     * Scope لحركات الإضافة
     */
    public function scopeAdditions($query)
    {
        return $query->where('movement_type', 'ADD');
    }

    /**
     * Scope لحركات التحويل (خروج ودخول)
     */
    public function scopeTransfers($query)
    {
        return $query->whereIn('movement_type', ['TRANSFER_OUT', 'TRANSFER_IN']);
    }

    /**
     * Scope للحصول على الرصيد المتحرك لمنتج في فرع
     */
    public function scopeRunningBalance($query, $branchId, $productId)
    {
        return $query
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->orderBy('created_at')
            ->get()
            ->map(function ($movement, $index) use ($query, $branchId, $productId) {
                // حساب الرصيد المتحرك
                $previousMovements = static::where('branch_id', $branchId)
                    ->where('product_id', $productId)
                    ->where('created_at', '<=', $movement->created_at)
                    ->where('id', '<=', $movement->id)
                    ->get();

                $balance = 0;
                foreach ($previousMovements as $m) {
                    if (in_array($m->movement_type, ['ADD', 'RETURN', 'TRANSFER_IN'])) {
                        $balance += $m->qty_units;
                    } else {
                        $balance -= $m->qty_units;
                    }
                }

                $movement->running_balance = $balance;
                return $movement;
            });
    }

    /**
     * Accessor لنوع الحركة بالعربية
     */
    public function getMovementTypeNameAttribute()
    {
        $types = [
            'ADD' => 'إضافة',
            'ISSUE' => 'صرف',
            'RETURN' => 'مرتجع',
            'TRANSFER_OUT' => 'تحويل - خروج',
            'TRANSFER_IN' => 'تحويل - دخول',
        ];

        return $types[$this->movement_type] ?? $this->movement_type;
    }

    /**
     * Accessor لأيقونة نوع الحركة
     */
    public function getMovementTypeIconAttribute()
    {
        $icons = [
            'ADD' => 'bi-plus-circle',
            'ISSUE' => 'bi-arrow-down-circle',
            'RETURN' => 'bi-arrow-up-circle',
            'TRANSFER_OUT' => 'bi-arrow-right-circle',
            'TRANSFER_IN' => 'bi-arrow-left-circle',
        ];

        return $icons[$this->movement_type] ?? 'bi-question-circle';
    }

    /**
     * Accessor للون badge حسب نوع الحركة
     */
    public function getMovementTypeBadgeAttribute()
    {
        $badges = [
            'ADD' => 'success',
            'ISSUE' => 'danger',
            'RETURN' => 'info',
            'TRANSFER_OUT' => 'warning',
            'TRANSFER_IN' => 'primary',
        ];

        return $badges[$this->movement_type] ?? 'secondary';
    }
}
