<?php

namespace App\Http\Requests;

use App\Rules\ValidStatusTransition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'expected_delivery_date' => 'nullable|date',
            'discount_type' => ['nullable', Rule::in(['NONE', 'PERCENTAGE', 'FIXED'])],
            'discount_value' => 'nullable|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            
            // Status field with transition validation
            'status' => [
                'sometimes',
                'string',
                Rule::in(['PENDING', 'APPROVED', 'COMPLETED', 'CANCELLED']),
                new ValidStatusTransition(
                    $this->route('purchaseOrder')?->status,
                    'أمر شراء'
                )
            ],
            
            'items' => 'sometimes|required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_type' => ['nullable', Rule::in(['NONE', 'PERCENTAGE', 'FIXED'])],
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'المورد مطلوب',
            'supplier_id.exists' => 'المورد غير موجود',
            'expected_delivery_date.date' => 'تاريخ التسليم المتوقع يجب أن يكون تاريخاً صحيحاً',
            'discount_type.in' => 'نوع الخصم يجب أن يكون: بدون، نسبة مئوية، أو قيمة ثابتة',
            'discount_value.numeric' => 'قيمة الخصم يجب أن تكون رقماً',
            'discount_value.min' => 'قيمة الخصم يجب أن تكون صفر أو أكثر',
            'tax_percentage.numeric' => 'نسبة الضريبة يجب أن تكون رقماً',
            'tax_percentage.min' => 'نسبة الضريبة لا يمكن أن تكون سالبة',
            'tax_percentage.max' => 'نسبة الضريبة لا يمكن أن تتجاوز 100%',
            'shipping_cost.numeric' => 'تكلفة الشحن يجب أن تكون رقماً',
            'shipping_cost.min' => 'تكلفة الشحن لا يمكن أن تكون سالبة',
            'notes.max' => 'الملاحظات لا يمكن أن تتجاوز 1000 حرف',
            'items.required' => 'يجب إضافة منتج واحد على الأقل',
            'items.array' => 'المنتجات يجب أن تكون مصفوفة',
            'items.min' => 'يجب إضافة منتج واحد على الأقل',
            'items.*.product_id.required' => 'المنتج مطلوب',
            'items.*.product_id.exists' => 'المنتج غير موجود',
            'items.*.quantity_ordered.required' => 'الكمية المطلوبة مطلوبة',
            'items.*.quantity_ordered.integer' => 'الكمية يجب أن تكون رقماً صحيحاً',
            'items.*.quantity_ordered.min' => 'الكمية يجب أن تكون 1 على الأقل',
            'items.*.unit_price.required' => 'سعر الوحدة مطلوب',
            'items.*.unit_price.numeric' => 'سعر الوحدة يجب أن يكون رقماً',
            'items.*.unit_price.min' => 'سعر الوحدة لا يمكن أن يكون سالباً',
            'items.*.discount_type.in' => 'نوع الخصم يجب أن يكون: بدون، نسبة مئوية، أو قيمة ثابتة',
            'items.*.discount_value.numeric' => 'قيمة الخصم يجب أن تكون رقماً',
            'items.*.discount_value.min' => 'قيمة الخصم يجب أن تكون صفر أو أكثر',
            'items.*.notes.max' => 'ملاحظات البند لا يمكن أن تتجاوز 500 حرف',
        ];
    }
}
