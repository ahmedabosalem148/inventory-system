<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::orderBy('name_ar')->get();
        return view('admin.products.index', compact('products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
            'carton_size' => 'required|integer|min:1',
            'carton_quantity' => 'required|integer|min:1',
            'warehouse_id' => 'required|exists:warehouses,id',
            'active' => 'boolean'
        ]);

        // Create the product
        $product = Product::create([
            'name' => $request->name,
            'carton_size' => $request->carton_size,
            'active' => $request->boolean('active', true)
        ]);

        // Create inventory record for the selected warehouse only
        WarehouseInventory::create([
            'warehouse_id' => $request->warehouse_id,
            'product_id' => $product->id,
            'closed_cartons' => $request->carton_quantity,
            'loose_units' => 0,
            'min_threshold' => 10 // Default minimum threshold
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'carton_size' => $product->carton_size,
                    'active' => $product->active
                ],
                'warehouse_inventory' => [
                    'warehouse_id' => $request->warehouse_id,
                    'cartons_added' => $request->carton_quantity,
                    'total_units' => $request->carton_quantity * $request->carton_size
                ],
                'play_sound' => 'new_product' // Trigger for new product sound
            ], 201);
        }

        return back()->with('success', 'تم إضافة المنتج بنجاح')->with('play_sound', 'new_product');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:products,name,' . $product->id,
            'carton_size' => 'required|integer|min:1',
            'active' => 'boolean'
        ]);

        $product->update([
            'name' => $request->name,
            'carton_size' => $request->carton_size,
            'active' => $request->boolean('active', $product->active)
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'carton_size' => $product->carton_size,
                    'active' => $product->active
                ]
            ]);
        }

        return back()->with('success', 'تم تحديث المنتج بنجاح');
    }
}
