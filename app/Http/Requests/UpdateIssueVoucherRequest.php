<?php

namespace App\Http\Requests;

use App\Rules\MaxDiscountValue;
use App\Rules\SufficientStock;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIssueVoucherRequest extends FormRequest
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
        $branchId = $this->route('issueVoucher')?->branch_id ?? auth()->user()->branch_id;
        
        return [
            // Basic fields
            'voucher_number' => ['sometimes', 'string', 'max:50'],
            'issue_date' => ['sometimes', 'date'],
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            
            // Issue type and related fields
            'issue_type' => ['sometimes', 'in:SALE,TRANSFER'],
            'target_branch_id' => [
                'required_if:issue_type,TRANSFER',
                'integer',
                'exists:branches,id',
                'different:branch_id'
            ],
            'payment_type' => [
                'required_if:issue_type,SALE',
                'in:CASH,CREDIT'
            ],
            
            // Financial fields
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0'],
            'discount_type' => ['sometimes', 'nullable', 'in:PERCENTAGE,FIXED'],
            'discount_value' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('discount_type') && $this->input('subtotal')) {
                        $validator = new MaxDiscountValue(
                            $this->input('discount_type'),
                            $this->input('subtotal')
                        );
                        $validator->validate($attribute, $value, $fail);
                    }
                }
            ],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
            
            // Items array
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['sometimes', 'integer', 'exists:issue_voucher_items,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    // Extract the index from the attribute path (items.0.quantity -> 0)
                    preg_match('/items\.(\d+)\.quantity/', $attribute, $matches);
                    $index = $matches[1] ?? null;
                    
                    if ($index !== null && isset($this->input('items')[$index]['product_id'])) {
                        $productId = $this->input('items')[$index]['product_id'];
                        $branchId = $this->route('issueVoucher')?->branch_id ?? auth()->user()->branch_id;
                        
                        // Get existing quantity for this item if updating
                        $existingQuantity = 0;
                        if (isset($this->input('items')[$index]['id'])) {
                            $existingItem = \App\Models\IssueVoucherItem::find($this->input('items')[$index]['id']);
                            $existingQuantity = $existingItem ? $existingItem->quantity : 0;
                        }
                        
                        // Validate new quantity considering the difference
                        $quantityDifference = $value - $existingQuantity;
                        if ($quantityDifference > 0) {
                            $validator = new SufficientStock($productId, $branchId);
                            $validator->validate($attribute, $quantityDifference, $fail);
                        }
                    }
                }
            ],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['sometimes', 'nullable', 'in:PERCENTAGE,FIXED'],
            'items.*.discount_value' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if (!$value) return;
                    
                    // Extract the index from the attribute path
                    preg_match('/items\.(\d+)\.discount_value/', $attribute, $matches);
                    $index = $matches[1] ?? null;
                    
                    if ($index !== null) {
                        $item = $this->input('items')[$index];
                        $discountType = $item['discount_type'] ?? null;
                        
                        if ($discountType) {
                            $lineTotal = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                            $validator = new MaxDiscountValue($discountType, $lineTotal);
                            $validator->validate($attribute, $value, $fail);
                        }
                    }
                }
            ],
            'items.*.tax_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'voucher_number.string' => 'رقم الإذن يجب أن يكون نصاً',
            'voucher_number.max' => 'رقم الإذن لا يمكن أن يتجاوز 50 حرفاً',
            'issue_date.date' => 'تاريخ الإصدار غير صالح',
            'customer_id.exists' => 'العميل المحدد غير موجود',
            
            'issue_type.in' => 'نوع الإصدار يجب أن يكون بيع أو تحويل',
            'target_branch_id.required_if' => 'الفرع المستهدف مطلوب في حالة التحويل',
            'target_branch_id.exists' => 'الفرع المستهدف غير موجود',
            'target_branch_id.different' => 'الفرع المستهدف يجب أن يكون مختلفاً عن الفرع الحالي',
            'payment_type.required_if' => 'نوع الدفع مطلوب في حالة البيع',
            'payment_type.in' => 'نوع الدفع يجب أن يكون نقدي أو آجل',
            
            'subtotal.numeric' => 'المجموع الجزئي يجب أن يكون رقماً',
            'subtotal.min' => 'المجموع الجزئي لا يمكن أن يكون سالباً',
            'tax_amount.numeric' => 'مبلغ الضريبة يجب أن يكون رقماً',
            'tax_amount.min' => 'مبلغ الضريبة لا يمكن أن يكون سالباً',
            'discount_type.in' => 'نوع الخصم يجب أن يكون نسبة مئوية أو ثابت',
            'discount_value.numeric' => 'قيمة الخصم يجب أن تكون رقماً',
            'discount_value.min' => 'قيمة الخصم لا يمكن أن تكون سالبة',
            'total_amount.numeric' => 'المبلغ الإجمالي يجب أن يكون رقماً',
            'total_amount.min' => 'المبلغ الإجمالي لا يمكن أن يكون سالباً',
            'notes.max' => 'الملاحظات لا يمكن أن تتجاوز 500 حرف',
            
            'items.required' => 'يجب إضافة عنصر واحد على الأقل',
            'items.array' => 'العناصر يجب أن تكون مصفوفة',
            'items.min' => 'يجب إضافة عنصر واحد على الأقل',
            'items.*.product_id.required' => 'المنتج مطلوب',
            'items.*.product_id.exists' => 'المنتج المحدد غير موجود',
            'items.*.quantity.required' => 'الكمية مطلوبة',
            'items.*.quantity.numeric' => 'الكمية يجب أن تكون رقماً',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر',
            'items.*.unit_price.required' => 'سعر الوحدة مطلوب',
            'items.*.unit_price.numeric' => 'سعر الوحدة يجب أن يكون رقماً',
            'items.*.unit_price.min' => 'سعر الوحدة لا يمكن أن يكون سالباً',
            'items.*.discount_type.in' => 'نوع الخصم يجب أن يكون نسبة مئوية أو ثابت',
            'items.*.discount_value.numeric' => 'قيمة الخصم يجب أن تكون رقماً',
            'items.*.discount_value.min' => 'قيمة الخصم لا يمكن أن تكون سالبة',
            'items.*.tax_percentage.numeric' => 'نسبة الضريبة يجب أن تكون رقماً',
            'items.*.tax_percentage.min' => 'نسبة الضريبة لا يمكن أن تكون سالبة',
            'items.*.tax_percentage.max' => 'نسبة الضريبة لا يمكن أن تتجاوز 100%',
            'items.*.notes.max' => 'الملاحظات لا يمكن أن تتجاوز 255 حرف',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'voucher_number' => 'رقم الإذن',
            'issue_date' => 'تاريخ الإصدار',
            'customer_id' => 'العميل',
            'issue_type' => 'نوع الإصدار',
            'target_branch_id' => 'الفرع المستهدف',
            'payment_type' => 'نوع الدفع',
            'subtotal' => 'المجموع الجزئي',
            'tax_amount' => 'مبلغ الضريبة',
            'discount_type' => 'نوع الخصم',
            'discount_value' => 'قيمة الخصم',
            'total_amount' => 'المبلغ الإجمالي',
            'notes' => 'الملاحظات',
            'items' => 'العناصر',
        ];
    }
}
