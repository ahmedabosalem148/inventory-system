<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'contact_name' => 'nullable|string|max:100',
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^(\+2)?01[0-2,5]{1}[0-9]{8}$/' // Egyptian phone format
            ],
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:500',
            'tax_number' => 'nullable|string|max:50|unique:suppliers,tax_number',
            'payment_terms' => ['nullable', Rule::in(['CASH', 'NET_7', 'NET_15', 'NET_30', 'NET_60'])],
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
            'notes' => 'nullable|string|max:1000',
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
            'name.required' => 'اسم المورد مطلوب',
            'name.max' => 'اسم المورد لا يمكن أن يتجاوز 100 حرف',
            'contact_name.max' => 'اسم جهة الاتصال لا يمكن أن يتجاوز 100 حرف',
            'phone.max' => 'رقم الهاتف لا يمكن أن يتجاوز 20 حرف',
            'phone.regex' => 'صيغة رقم الهاتف غير صحيحة (مثال: 01012345678 أو +201012345678)',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.max' => 'البريد الإلكتروني لا يمكن أن يتجاوز 100 حرف',
            'address.max' => 'العنوان لا يمكن أن يتجاوز 500 حرف',
            'tax_number.max' => 'الرقم الضريبي لا يمكن أن يتجاوز 50 حرف',
            'tax_number.unique' => 'الرقم الضريبي مستخدم بالفعل',
            'payment_terms.in' => 'شروط الدفع يجب أن تكون: نقداً، 7 أيام، 15 يوم، 30 يوم، أو 60 يوم',
            'credit_limit.numeric' => 'الحد الائتماني يجب أن يكون رقماً',
            'credit_limit.min' => 'الحد الائتماني لا يمكن أن يكون سالباً',
            'status.in' => 'الحالة يجب أن تكون: نشط أو غير نشط',
            'notes.max' => 'الملاحظات لا يمكن أن تتجاوز 1000 حرف',
        ];
    }
}
