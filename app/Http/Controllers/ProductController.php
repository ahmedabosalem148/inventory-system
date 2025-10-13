<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Branch;
use App\Models\ProductBranchStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Ø¹Ø±Ø¶ Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„Ù…Ù†ØªØ¬Ø§Øª
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'branchStocks.branch']);

        // Ø§Ù„Ø¨Ø­Ø« Ø¨Ø§Ù„Ø§Ø³Ù…
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // ÙÙ„ØªØ±Ø© Ø­Ø³Ø¨ Ø§Ù„ØªØµÙ†ÙŠÙ
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // ÙÙ„ØªØ±Ø© Ø­Ø³Ø¨ Ø§Ù„Ø­Ø§Ù„Ø©
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $products = $query->latest()->paginate(15);
        $categories = Category::active()->get();

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Ø¹Ø±Ø¶ Ù†Ù…ÙˆØ°Ø¬ Ø¥Ø¶Ø§ÙØ© Ù…Ù†ØªØ¬ Ø¬Ø¯ÙŠØ¯
     */
    public function create()
    {
        $categories = Category::active()->get();
        $branches = Branch::active()->get();
        return view('products.create', compact('categories', 'branches'));
    }

    /**
     * Ø­ÙØ¸ Ù…Ù†ØªØ¬ Ø¬Ø¯ÙŠØ¯
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'min_stock' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'initial_stock' => 'nullable|array',
            'initial_stock.*' => 'nullable|integer|min:0',
        ], [
            'category_id.required' => __('product.validation.category_id.required'),
            'name.required' => __('product.validation.name.required'),
            'unit.required' => __('product.validation.unit.required'),
            'purchase_price.required' => __('product.validation.purchase_price.required'),
            'sale_price.required' => __('product.validation.sale_price.required'),
            'min_stock.required' => __('product.validation.min_stock.required'),
        ]);

        DB::beginTransaction();
        try {
            $validated['is_active'] = $request->has('is_active');
            
            // Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„Ù…Ù†ØªØ¬
            $product = Product::create($validated);

            // Ø¥Ø¶Ø§ÙØ© Ø§Ù„Ù…Ø®Ø²ÙˆÙ† Ø§Ù„Ø£ÙˆÙ„ÙŠ Ù„ÙƒÙ„ ÙØ±Ø¹
            if ($request->filled('initial_stock')) {
                foreach ($request->initial_stock as $branchId => $stock) {
                    if ($stock !== null) {
                        $product->branchStocks()->create([
                            'branch_id' => $branchId,
                            'current_stock' => $stock,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('products.index')
                ->with('success', __('product.messages.created'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', __('product.messages.create_error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Ø¹Ø±Ø¶ ØªÙØ§ØµÙŠÙ„ Ù…Ù†ØªØ¬
     */
    public function show(Product $product)
    {
        $product->load(['category', 'branchStocks.branch']);
        return view('products.show', compact('product'));
    }

    /**
     * Ø¹Ø±Ø¶ Ù†Ù…ÙˆØ°Ø¬ ØªØ¹Ø¯ÙŠÙ„ Ù…Ù†ØªØ¬
     */
    public function edit(Product $product)
    {
        $categories = Category::active()->get();
        $branches = Branch::active()->get();
        $product->load('branchStocks');
        
        return view('products.edit', compact('product', 'categories', 'branches'));
    }

    /**
     * ØªØ­Ø¯ÙŠØ« Ø¨ÙŠØ§Ù†Ø§Øª Ù…Ù†ØªØ¬
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'min_stock' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ], [
            'category_id.required' => __('product.validation.category_id.required'),
            'name.required' => __('product.validation.name.required'),
            'unit.required' => __('product.validation.unit.required'),
            'purchase_price.required' => __('product.validation.purchase_price.required'),
            'sale_price.required' => __('product.validation.sale_price.required'),
            'min_stock.required' => __('product.validation.min_stock.required'),
        ]);

        $validated['is_active'] = $request->has('is_active');

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', __('product.messages.updated'));
    }

    /**
     * Ø­Ø°Ù Ù…Ù†ØªØ¬
     */
    public function destroy(Product $product)
    {
        try {
            // التحقق من وجود رصيد في المخزون
            $totalStock = $product->productBranchStocks()->sum('current_stock');
            if ($totalStock > 0) {
                return redirect()->back()
                    ->with('error', __('product.messages.delete_stock', ['qty' => $totalStock]));
            }

            // التحقق من وجود حركات على المنتج
            $hasMovements = \App\Models\InventoryMovement::where('product_id', $product->id)->exists();
            if ($hasMovements) {
                return redirect()->back()
                    ->with('error', __('product.messages.delete_movements'));
            }

            // حذف المنتج (سيحذف المخزون تلقائياً بسبب cascade delete)
            $product->delete();

            return redirect()->route('products.index')
                ->with('success', __('product.messages.deleted'));
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('product.messages.delete_error', ['error' => $e->getMessage()]));
        }
    }


    /**
     * ØªÙ‚Ø±ÙŠØ± Ù†Ù‚Øµ Ø§Ù„Ù…Ø®Ø²ÙˆÙ†
     */
    public function lowStockReport(Request $request)
    {
        $query = ProductBranchStock::with(['product.category', 'branch'])
            ->whereHas('product', fn($q) => $q->where('is_active', true));

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('product', fn($q) => $q->where('category_id', $request->category_id));
        }

        $stocks = $query->get()->filter(function($stock) {
            return $stock->current_stock < $stock->product->min_stock;
        })->sortBy(function($stock) {
            return ($stock->current_stock / max($stock->product->min_stock, 1));
        });

        $branches = Branch::active()->orderBy('name')->get();
        $categories = Category::active()->orderBy('name')->get();

        $stats = [
            'total_items' => $stocks->count(),
            'out_of_stock' => $stocks->filter(fn($s) => $s->current_stock == 0)->count(),
            'critical' => $stocks->filter(function($s) {
                return $s->current_stock > 0 && ($s->current_stock / max($s->product->min_stock, 1)) < 0.2;
            })->count(),
        ];

        return view('reports.low-stock', compact('stocks', 'branches', 'categories', 'stats'));
    }

}
