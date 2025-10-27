<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Rules\ValidSkuFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
        return [
            'sku' => [
                'nullable',
                'string',
                'max:50',
                'unique:products,sku',
                new ValidSkuFormat()
            ],
            
            'category_id' => 'required|exists:categories,id',
            
            'product_classification' => [
                'required',
                'string',
                Rule::in(Product::CLASSIFICATIONS)
            ],
            
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            
            'unit' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $classification = $this->input('product_classification');
                    
                    // Validation حسب التصنيف
                    if ($classification === Product::CLASSIFICATION_PARTS) {
                        $validUnits = ['pcs', 'piece', 'unit', 'قطعة'];
                        if (!in_array(strtolower($value), $validUnits)) {
                            $fail('الأجزاء يجب أن تكون بالقطعة');
                        }
                    }
                    
                    if (in_array($classification, [
                        Product::CLASSIFICATION_PLASTIC_PARTS,
                        Product::CLASSIFICATION_ALUMINUM_PARTS
                    ])) {
                        $validUnits = ['kg', 'gram', 'ton', 'pcs', 'piece', 'كجم', 'جرام', 'قطعة', 'طن'];
                        if (!in_array(strtolower($value), $validUnits)) {
                            $fail('وحدة القياس غير مناسبة لهذا التصنيف');
                        }
                    }
                }
            ],
            
            'pack_size' => [
                'nullable',
                'integer',
                'min:1',
                Rule::requiredIf(function () {
                    $classification = $this->input('product_classification');
                    return in_array($classification, [
                        Product::CLASSIFICATION_PARTS,
                        Product::CLASSIFICATION_PLASTIC_PARTS,
                        Product::CLASSIFICATION_ALUMINUM_PARTS,
                    ]);
                })
            ],
            
            'purchase_price' => 'required|numeric|min:0',
            
            'sale_price' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $classification = $this->input('product_classification');
                    $purchasePrice = $this->input('purchase_price');
                    
                    // للمنتجات التامة فقط: sale_price >= purchase_price
                    if ($classification === Product::CLASSIFICATION_FINISHED && $value < $purchasePrice) {
                        $fail('سعر البيع يجب أن يكون أكبر من أو يساوي سعر الشراء للمنتجات التامة');
                    }
                }
            ],
            
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
            'name.max' => 'اسم المنتج لا يجب أن يتجاوز 255 حرف',
            
            'brand.required' => 'الماركة مطلوبة',
            'brand.max' => 'الماركة لا يجب أن تتجاوز 100 حرف',
            
            'unit.required' => 'وحدة القياس مطلوبة',
            
            'pack_size.required' => 'حجم العبوة مطلوب لهذا النوع من المنتجات',
            'pack_size.integer' => 'حجم العبوة يجب أن يكون رقم صحيح',
            'pack_size.min' => 'حجم العبوة يجب أن يكون على الأقل 1',
            
            'purchase_price.required' => 'سعر الشراء مطلوب',
            'purchase_price.numeric' => 'سعر الشراء يجب أن يكون رقم',
            'purchase_price.min' => 'سعر الشراء لا يمكن أن يكون سالب',
            
            'sale_price.required' => 'سعر البيع مطلوب',
            'sale_price.numeric' => 'سعر البيع يجب أن يكون رقم',
            'sale_price.min' => 'سعر البيع لا يمكن أن يكون سالب',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'التصنيف',
            'product_classification' => 'نوع المنتج',
            'name' => 'اسم المنتج',
            'brand' => 'الماركة',
            'description' => 'الوصف',
            'unit' => 'وحدة القياس',
            'pack_size' => 'حجم العبوة',
            'purchase_price' => 'سعر الشراء',
            'sale_price' => 'سعر البيع',
            'min_stock' => 'الحد الأدنى للمخزون',
            'reorder_level' => 'مستوى إعادة الطلب',
        ];
    }
}
