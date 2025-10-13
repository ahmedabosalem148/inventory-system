<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware لحفظ الفلاتر في Session
 * 
 * يحفظ فلاتر التقارير والبحث في Session ليتم استرجاعها
 * عند العودة للصفحة أو التنقل بين الصفحات
 */
class PersistFilters
{
    /**
     * معالجة الطلب الوارد
     */
    public function handle(Request $request, Closure $next, $sessionKey = 'filters')
    {
        $route = $request->route()->getName();
        
        // فقط على صفحات التقارير والقوائم
        if ($this->shouldPersist($route)) {
            // حفظ الفلاتر الحالية في Session
            if ($request->hasAny($this->getFilterKeys())) {
                $filters = $request->only($this->getFilterKeys());
                session()->put("{$sessionKey}.{$route}", $filters);
            }
            // إذا لم توجد فلاتر في الطلب، استرجاعها من Session
            else {
                $savedFilters = session()->get("{$sessionKey}.{$route}", []);
                
                if (!empty($savedFilters)) {
                    // دمج الفلاتر المحفوظة مع الطلب
                    $request->merge($savedFilters);
                }
            }
        }

        return $next($request);
    }

    /**
     * فحص ما إذا كان يجب حفظ الفلاتر لهذا المسار
     */
    private function shouldPersist($route): bool
    {
        $persistableRoutes = [
            'reports.inventory.movements',
            'reports.inventory.current-stock',
            'reports.inventory.most-active',
            'reports.customers.ledger',
            'reports.customers.balances',
            'payments.index',
            'cheques.index',
        ];

        return in_array($route, $persistableRoutes);
    }

    /**
     * مفاتيح الفلاتر التي يجب حفظها
     */
    private function getFilterKeys(): array
    {
        return [
            'date_from',
            'date_to',
            'branch_id',
            'product_id',
            'customer_id',
            'category_id',
            'status',
            'movement_type',
            'search',
            'low_stock',
        ];
    }
}
