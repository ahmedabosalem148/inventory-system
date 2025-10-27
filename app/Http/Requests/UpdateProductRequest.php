<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Rules\ValidSkuFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Super admin or user with full_access to their branch
        return $this->user()->hasRole('super-admin') || 
               ($this->user()->getActiveBranch() && 
                $this->user()->hasFullAccessToBranch($this->user()->getActiveBranch()->id));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product');
        
        return [
            'sku' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('products', 'sku')->ignore($productId),
                new ValidSkuFormat()
            ],
            
            'category_id' => 'sometimes|required|exists:categories,id',
            
            'product_classification' => [
                'sometimes',
                'required',
                'string',
                Rule::in(Product::CLASSIFICATIONS)
            ],
            
            'name' => 'sometimes|required|string|max:255',
            'brand' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:1000',
            
            'unit' => 'sometimes|required|string|max:50',
            
            'pack_size' => 'nullable|integer|min:1',
            
            'purchase_price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'sometimes|required|numeric|min:0',
            
            'min_stock' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'يجب اختيار التصنيف',
            'category_id.exists' => 'التصنيف المحدد غير موجود',
            
            'product_classification.required' => 'يجب اختيار نوع المنتج',
            'product_classification.in' => 'نوع المنتج غير صحيح',
            
            'name.required' => 'اسم المنتج مطلوب',
            'brand.max' => 'الماركة لا يجب أن تتجاوز 100 حرف',
            
            'unit.required' => 'وحدة القياس مطلوبة',
            
            'purchase_price.required' => 'سعر الشراء مطلوب',
            'sale_price.required' => 'سعر البيع مطلوب',
        ];
    }
}
