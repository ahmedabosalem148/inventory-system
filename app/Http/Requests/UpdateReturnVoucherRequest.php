<?php

namespace App\Http\Requests;

use App\Rules\ValidReturnVoucherNumber;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReturnVoucherRequest extends FormRequest
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
        $returnVoucher = $this->route('returnVoucher');
        $branchId = $returnVoucher?->branch_id ?? auth()->user()->branch_id;
        $excludeId = $returnVoucher?->id;
        
        return [
            // Basic fields
            'voucher_number' => [
                'sometimes',
                'string',
                'max:50',
                new ValidReturnVoucherNumber($branchId, $excludeId)
            ],
            'return_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'issue_voucher_id' => ['sometimes', 'integer', 'exists:issue_vouchers,id'],
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            
            // Return reason fields (Task 1.4)
            'reason' => ['sometimes', 'string', 'max:500'],
            'reason_category' => [
                'nullable',
                'string',
                'in:damaged,defective,customer_request,wrong_item,other'
            ],
            
            // Financial fields
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0'],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
            
            // Items array
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['sometimes', 'integer', 'exists:return_voucher_items,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.issue_voucher_item_id' => ['required', 'integer', 'exists:issue_voucher_items,id'],
            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    // Extract the index from the attribute path
                    preg_match('/items\.(\d+)\.quantity/', $attribute, $matches);
                    $index = $matches[1] ?? null;
                    
                    if ($index !== null && isset($this->input('items')[$index]['issue_voucher_item_id'])) {
                        $issueItemId = $this->input('items')[$index]['issue_voucher_item_id'];
                        $issueItem = \App\Models\IssueVoucherItem::find($issueItemId);
                        
                        if ($issueItem) {
                            // Get existing quantity for this item if updating
                            $existingQuantity = 0;
                            if (isset($this->input('items')[$index]['id'])) {
                                $existingItem = \App\Models\ReturnVoucherItem::find($this->input('items')[$index]['id']);
                                $existingQuantity = $existingItem ? $existingItem->quantity : 0;
                            }
                            
                            // Calculate total returned excluding this update
                            $totalReturned = \App\Models\ReturnVoucherItem::where('issue_voucher_item_id', $issueItemId)
                                ->when(isset($this->input('items')[$index]['id']), function ($query) use ($index) {
                                    $query->where('id', '!=', $this->input('items')[$index]['id']);
                                })
                                ->sum('quantity');
                            
                            $availableToReturn = $issueItem->quantity - $totalReturned;
                            
                            if ($value > $availableToReturn) {
                                $fail("الكمية المرتجعة ({$value}) تتجاوز الكمية المتاحة للإرجاع ({$availableToReturn})");
                            }
                        }
                    }
                }
            ],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
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
            
            'return_date.date' => 'تاريخ الإرجاع غير صالح',
            'return_date.before_or_equal' => 'تاريخ الإرجاع لا يمكن أن يكون في المستقبل',
            
            'issue_voucher_id.exists' => 'إذن الصرف المحدد غير موجود',
            'customer_id.exists' => 'العميل المحدد غير موجود',
            
            'reason.string' => 'سبب الإرجاع يجب أن يكون نصاً',
            'reason.max' => 'سبب الإرجاع لا يمكن أن يتجاوز 500 حرف',
            'reason_category.in' => 'تصنيف السبب غير صالح',
            
            'subtotal.numeric' => 'المجموع الجزئي يجب أن يكون رقماً',
            'subtotal.min' => 'المجموع الجزئي لا يمكن أن يكون سالباً',
            'tax_amount.numeric' => 'مبلغ الضريبة يجب أن يكون رقماً',
            'tax_amount.min' => 'مبلغ الضريبة لا يمكن أن يكون سالباً',
            'total_amount.numeric' => 'المبلغ الإجمالي يجب أن يكون رقماً',
            'total_amount.min' => 'المبلغ الإجمالي لا يمكن أن يكون سالباً',
            'notes.max' => 'الملاحظات لا يمكن أن تتجاوز 500 حرف',
            
            'items.array' => 'العناصر يجب أن تكون مصفوفة',
            'items.min' => 'يجب إضافة عنصر واحد على الأقل',
            'items.*.product_id.required' => 'المنتج مطلوب',
            'items.*.product_id.exists' => 'المنتج المحدد غير موجود',
            'items.*.issue_voucher_item_id.required' => 'عنصر إذن الصرف مطلوب',
            'items.*.issue_voucher_item_id.exists' => 'عنصر إذن الصرف غير موجود',
            'items.*.quantity.required' => 'الكمية مطلوبة',
            'items.*.quantity.numeric' => 'الكمية يجب أن تكون رقماً',
            'items.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر',
            'items.*.unit_price.required' => 'سعر الوحدة مطلوب',
            'items.*.unit_price.numeric' => 'سعر الوحدة يجب أن يكون رقماً',
            'items.*.unit_price.min' => 'سعر الوحدة لا يمكن أن يكون سالباً',
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
            'return_date' => 'تاريخ الإرجاع',
            'issue_voucher_id' => 'إذن الصرف الأصلي',
            'customer_id' => 'العميل',
            'reason' => 'سبب الإرجاع',
            'reason_category' => 'تصنيف السبب',
            'subtotal' => 'المجموع الجزئي',
            'tax_amount' => 'مبلغ الضريبة',
            'total_amount' => 'المبلغ الإجمالي',
            'notes' => 'الملاحظات',
            'items' => 'العناصر',
        ];
    }
}
