<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Warehouse;
use Symfony\Component\HttpFoundation\Response;

class WarehouseAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $warehouseId = $request->route('warehouse');
        
        if (!$warehouseId) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'مخزن غير صحيح'], 400);
            }
            return redirect('/warehouses')->with('error', 'مخزن غير صحيح');
        }
        
        // Check if user is authenticated for this warehouse
        $sessionKey = "warehouse_{$warehouseId}_auth";
        
        if (!session($sessionKey)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'غير مصرح لك بالوصول لهذا المخزن'], 401);
            }
            return redirect("/warehouses/{$warehouseId}/login")
                ->with('error', 'يجب إدخال كلمة مرور المخزن أولاً');
        }
        
        return $next($request);
    }
}
