<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Customer;

/**
 * API Controller للبحث الفوري (Quick Search)
 */
class QuickSearchController extends Controller
{
    /**
     * البحث في المنتجات
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function products(Request $request)
    {
        $search = $request->get('q', '');
        $limit = $request->get('limit', 10);

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $products = Product::active()
            ->where(function ($query) use ($search) {
                $query->where('sku', 'LIKE', "%{$search}%")
                      ->orWhere('name', 'LIKE', "%{$search}%")
                      ->orWhere('brand', 'LIKE', "%{$search}%");
            })
            ->with('category')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'brand' => $product->brand,
                    'category' => $product->category?->name,
                    'pack_size' => $product->pack_size,
                    'unit' => $product->unit,
                    'label' => "{$product->sku} - {$product->name}",
                ];
            });

        return response()->json($products);
    }

    /**
     * البحث في العملاء
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function customers(Request $request)
    {
        $search = $request->get('q', '');
        $limit = $request->get('limit', 10);

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $customers = Customer::active()
            ->where(function ($query) use ($search) {
                $query->where('code', 'LIKE', "%{$search}%")
                      ->orWhere('name', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%");
            })
            ->limit($limit)
            ->get()
            ->map(function ($customer) {
                // حساب الرصيد
                $balance = $customer->ledgerEntries()
                    ->selectRaw('SUM(debit_aliah) - SUM(credit_lah) as balance')
                    ->value('balance') ?? 0;

                return [
                    'id' => $customer->id,
                    'code' => $customer->code,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'balance' => number_format($balance, 2),
                    'label' => "{$customer->code} - {$customer->name}",
                ];
            });

        return response()->json($customers);
    }

    /**
     * البحث في المخزون لفرع معين
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stockByBranch(Request $request)
    {
        $search = $request->get('q', '');
        $branchId = $request->get('branch_id');
        $limit = $request->get('limit', 10);

        if (strlen($search) < 2 || !$branchId) {
            return response()->json([]);
        }

        $products = Product::active()
            ->where(function ($query) use ($search) {
                $query->where('sku', 'LIKE', "%{$search}%")
                      ->orWhere('name', 'LIKE', "%{$search}%");
            })
            ->with(['branches' => function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            }])
            ->limit($limit)
            ->get()
            ->map(function ($product) use ($branchId) {
                $branch = $product->branches->first();
                $currentQty = $branch ? $branch->pivot->current_qty : 0;
                $minQty = $branch ? $branch->pivot->min_qty : 0;

                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'pack_size' => $product->pack_size,
                    'current_qty' => $currentQty,
                    'min_qty' => $minQty,
                    'is_low_stock' => $currentQty < $minQty,
                    'label' => "{$product->sku} - {$product->name} (متوفر: {$currentQty})",
                ];
            });

        return response()->json($products);
    }

    /**
     * البحث العام (في كل شيء)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function global(Request $request)
    {
        $search = $request->get('q', '');

        if (strlen($search) < 2) {
            return response()->json([
                'products' => [],
                'customers' => [],
            ]);
        }

        // البحث في المنتجات
        $products = Product::active()
            ->where(function ($query) use ($search) {
                $query->where('sku', 'LIKE', "%{$search}%")
                      ->orWhere('name', 'LIKE', "%{$search}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'type' => 'product',
                    'label' => "{$product->sku} - {$product->name}",
                    'url' => route('products.show', $product),
                ];
            });

        // البحث في العملاء
        $customers = Customer::active()
            ->where(function ($query) use ($search) {
                $query->where('code', 'LIKE', "%{$search}%")
                      ->orWhere('name', 'LIKE', "%{$search}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'type' => 'customer',
                    'label' => "{$customer->code} - {$customer->name}",
                    'url' => route('customers.show', $customer),
                ];
            });

        return response()->json([
            'products' => $products,
            'customers' => $customers,
        ]);
    }
}
