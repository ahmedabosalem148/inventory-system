<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait للفلاتر المتقدمة
 * 
 * يمكن استخدامه في أي Model يحتاج فلاتر بالتاريخ، الفرع، المنتج، العميل، إلخ
 */
trait Filterable
{
    /**
     * Scope للفلترة حسب فترة زمنية
     * 
     * @param Builder $query
     * @param string|null $from تاريخ البداية
     * @param string|null $to تاريخ النهاية
     * @param string $column اسم العمود (default: created_at)
     * @return Builder
     */
    public function scopeFilterByDateRange(Builder $query, $from = null, $to = null, $column = 'created_at')
    {
        if ($from) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }

    /**
     * Scope للفلترة حسب الفرع
     * 
     * @param Builder $query
     * @param int|null $branchId
     * @param string $column اسم العمود (default: branch_id)
     * @return Builder
     */
    public function scopeFilterByBranch(Builder $query, $branchId = null, $column = 'branch_id')
    {
        if ($branchId) {
            $query->where($column, $branchId);
        }

        return $query;
    }

    /**
     * Scope للفلترة حسب المنتج
     * 
     * @param Builder $query
     * @param int|null $productId
     * @return Builder
     */
    public function scopeFilterByProduct(Builder $query, $productId = null)
    {
        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query;
    }

    /**
     * Scope للفلترة حسب العميل
     * 
     * @param Builder $query
     * @param int|null $customerId
     * @return Builder
     */
    public function scopeFilterByCustomer(Builder $query, $customerId = null)
    {
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        return $query;
    }

    /**
     * Scope للفلترة حسب الحالة
     * 
     * @param Builder $query
     * @param string|null $status
     * @return Builder
     */
    public function scopeFilterByStatus(Builder $query, $status = null)
    {
        if ($status) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Scope للفلترة حسب التصنيف
     * 
     * @param Builder $query
     * @param int|null $categoryId
     * @return Builder
     */
    public function scopeFilterByCategory(Builder $query, $categoryId = null)
    {
        if ($categoryId) {
            // إذا كان Model مرتبط مباشرة بـ category
            if (in_array('category_id', $this->getFillable())) {
                $query->where('category_id', $categoryId);
            }
            // إذا كان عبر علاقة (مثل InventoryMovement → Product → Category)
            elseif (method_exists($this, 'product')) {
                $query->whereHas('product', function ($q) use ($categoryId) {
                    $q->where('category_id', $categoryId);
                });
            }
        }

        return $query;
    }

    /**
     * Scope للبحث بالنص (في الاسم أو الكود)
     * 
     * @param Builder $query
     * @param string|null $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, $search = null)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $searchTerm = "%{$search}%";
                
                // البحث في الحقول الممكنة
                $searchableColumns = ['name', 'code', 'sku', 'number'];
                
                foreach ($searchableColumns as $column) {
                    if (in_array($column, $this->getFillable())) {
                        $q->orWhere($column, 'LIKE', $searchTerm);
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Scope لتطبيق جميع الفلاتر دفعة واحدة من Request
     * 
     * @param Builder $query
     * @param array $filters المصفوفة المحتوية على الفلاتر من Request
     * @return Builder
     */
    public function scopeApplyFilters(Builder $query, array $filters)
    {
        return $query
            ->filterByDateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->filterByBranch($filters['branch_id'] ?? null)
            ->filterByProduct($filters['product_id'] ?? null)
            ->filterByCustomer($filters['customer_id'] ?? null)
            ->filterByStatus($filters['status'] ?? null)
            ->filterByCategory($filters['category_id'] ?? null)
            ->search($filters['search'] ?? null);
    }
}
