<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryCountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'count_date' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.physical_quantity' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'branch_id.required' => 'الفرع مطلوب',
            'branch_id.exists' => 'الفرع المحدد غير موجود',
            'count_date.required' => 'تاريخ الجرد مطلوب',
            'count_date.date' => 'تاريخ الجرد غير صحيح',
            'count_date.before_or_equal' => 'تاريخ الجرد لا يمكن أن يكون في المستقبل',
            'notes.max' => 'الملاحظات لا يمكن أن تتجاوز 1000 حرف',
            'items.required' => 'يجب إضافة منتج واحد على الأقل',
            'items.array' => 'بيانات المنتجات غير صحيحة',
            'items.min' => 'يجب إضافة منتج واحد على الأقل',
            'items.*.product_id.required' => 'المنتج مطلوب',
            'items.*.product_id.exists' => 'المنتج المحدد غير موجود',
            'items.*.physical_quantity.required' => 'الكمية الفعلية مطلوبة',
            'items.*.physical_quantity.numeric' => 'الكمية الفعلية يجب أن تكون رقم',
            'items.*.physical_quantity.min' => 'الكمية الفعلية لا يمكن أن تكون سالبة',
            'items.*.notes.max' => 'ملاحظات الصنف لا يمكن أن تتجاوز 500 حرف',
        ];
    }
}
