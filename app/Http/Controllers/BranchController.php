<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branches = Branch::latest()->get();
        return view('branches.index', compact('branches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('branches.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('BranchController@store called', [
            'request_data' => $request->all(),
            'has_code' => $request->has('code'),
            'has_name' => $request->has('name'),
        ]);

        try {
            $validated = $request->validate([
                'code' => 'required|string|max:20|unique:branches,code',
                'name' => 'required|string|max:100',
                'is_active' => 'boolean',
            ], [
                'code.required' => 'كود الفرع مطلوب',
                'code.unique' => 'كود الفرع موجود مسبقاً',
                'name.required' => 'اسم الفرع مطلوب',
            ]);

            \Log::info('Validation passed', ['validated' => $validated]);

            $validated['is_active'] = $request->has('is_active');

            $branch = Branch::create($validated);

            \Log::info('Branch created successfully', ['branch_id' => $branch->id]);

            return redirect()->route('branches.index')
                ->with('success', 'تم إضافة الفرع بنجاح');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error creating branch', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:branches,code,' . $branch->id,
            'name' => 'required|string|max:100',
            'is_active' => 'boolean',
        ], [
            'code.required' => 'كود الفرع مطلوب',
            'code.unique' => 'كود الفرع موجود مسبقاً',
            'name.required' => 'اسم الفرع مطلوب',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $branch->update($validated);

        return redirect()->route('branches.index')
            ->with('success', 'تم تعديل الفرع بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        try {
            // منع حذف الفروع الأساسية الثلاثة
            if (in_array($branch->code, ['FAC', 'ATB', 'IMB'])) {
                return redirect()->back()
                    ->with('error', 'لا يمكن حذف الفروع الأساسية');
            }

            // التحقق من وجود مخزون
            $hasStock = \App\Models\ProductBranchStock::where('branch_id', $branch->id)
                ->where('current_stock', '>', 0)
                ->exists();
            
            if ($hasStock) {
                return redirect()->back()
                    ->with('error', 'لا يمكن حذف الفرع. يوجد منتجات في المخزون');
            }

            // التحقق من وجود حركات
            $hasMovements = \App\Models\InventoryMovement::where('branch_id', $branch->id)->exists();
            if ($hasMovements) {
                return redirect()->back()
                    ->with('error', 'لا يمكن حذف الفرع. يوجد حركات مخزنية مسجلة');
            }

            $branch->delete();

            return redirect()->route('branches.index')
                ->with('success', 'تم حذف الفرع بنجاح');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء الحذف: ' . $e->getMessage());
        }
    }
}