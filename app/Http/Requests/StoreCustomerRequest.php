<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
            // Basic Information
            'name' => ['required', 'string', 'max:200'],
            'code' => ['nullable', 'string', 'max:50', 'unique:customers,code'],
            'type' => ['nullable', 'in:retail,wholesale'], // قطاعي أو جملة (default: retail)
            
            // Contact Information
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            
            // Other fields
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم العميل مطلوب',
            'name.max' => 'اسم العميل لا يمكن أن يتجاوز 200 حرف',
            
            'code.unique' => 'كود العميل مستخدم بالفعل',
            'code.max' => 'كود العميل لا يمكن أن يتجاوز 50 حرف',
            
            'type.in' => 'نوع العميل يجب أن يكون قطاعي أو جملة',
            
            'phone.max' => 'رقم الهاتف لا يمكن أن يتجاوز 20 حرف',
            
            'is_active.boolean' => 'حالة التفعيل يجب أن تكون صحيح أو خطأ',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'اسم العميل',
            'customer_code' => 'رمز العميل',
            'customer_type' => 'نوع العميل',
            'phone' => 'رقم الهاتف',
            'email' => 'البريد الإلكتروني',
            'address' => 'العنوان',
            'city' => 'المدينة',
            'country' => 'الدولة',
            'credit_limit' => 'حد الائتمان',
            'payment_terms_days' => 'مدة الدفع',
            'branches' => 'الفروع',
            'notes' => 'الملاحظات',
            'is_active' => 'حالة التفعيل',
        ];
    }
}
