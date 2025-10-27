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
            'name' => ['required', 'string', 'max:255'],
            'customer_code' => ['nullable', 'string', 'max:50', 'unique:customers,customer_code'],
            'customer_type' => ['required', 'in:INDIVIDUAL,COMPANY'],
            
            // Contact Information
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+?[0-9]{1,4})?[0-9]{7,15}$/' // International phone format
            ],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            
            // Tax Information (required for companies)
            'tax_id' => [
                'required_if:customer_type,COMPANY',
                'nullable',
                'string',
                'max:50',
                'unique:customers,tax_id'
            ],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            
            // Credit Information
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            
            // Branch Assignment
            'branches' => ['nullable', 'array'],
            'branches.*' => ['integer', 'exists:branches,id'],
            
            // Other fields
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'name.string' => 'اسم العميل يجب أن يكون نصاً',
            'name.max' => 'اسم العميل لا يمكن أن يتجاوز 255 حرفاً',
            
            'customer_code.unique' => 'رمز العميل مستخدم بالفعل',
            'customer_code.max' => 'رمز العميل لا يمكن أن يتجاوز 50 حرفاً',
            
            'customer_type.required' => 'نوع العميل مطلوب',
            'customer_type.in' => 'نوع العميل يجب أن يكون فرد أو شركة',
            
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex' => 'رقم الهاتف غير صالح. يجب أن يحتوي على 7-15 رقم مع رمز الدولة الاختياري',
            'phone.max' => 'رقم الهاتف لا يمكن أن يتجاوز 20 حرفاً',
            
            'email.email' => 'البريد الإلكتروني غير صالح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'email.max' => 'البريد الإلكتروني لا يمكن أن يتجاوز 255 حرفاً',
            
            'address.max' => 'العنوان لا يمكن أن يتجاوز 500 حرف',
            'city.max' => 'المدينة لا يمكن أن تتجاوز 100 حرف',
            'country.max' => 'الدولة لا يمكن أن تتجاوز 100 حرف',
            
            'tax_id.required_if' => 'الرقم الضريبي مطلوب للشركات',
            'tax_id.unique' => 'الرقم الضريبي مستخدم بالفعل',
            'tax_id.max' => 'الرقم الضريبي لا يمكن أن يتجاوز 50 حرفاً',
            
            'tax_percentage.numeric' => 'نسبة الضريبة يجب أن تكون رقماً',
            'tax_percentage.min' => 'نسبة الضريبة لا يمكن أن تكون سالبة',
            'tax_percentage.max' => 'نسبة الضريبة لا يمكن أن تتجاوز 100%',
            
            'credit_limit.numeric' => 'حد الائتمان يجب أن يكون رقماً',
            'credit_limit.min' => 'حد الائتمان لا يمكن أن يكون سالباً',
            
            'payment_terms_days.integer' => 'مدة الدفع يجب أن تكون عدداً صحيحاً',
            'payment_terms_days.min' => 'مدة الدفع لا يمكن أن تكون سالبة',
            'payment_terms_days.max' => 'مدة الدفع لا يمكن أن تتجاوز 365 يوماً',
            
            'branches.array' => 'الفروع يجب أن تكون مصفوفة',
            'branches.*.exists' => 'الفرع المحدد غير موجود',
            
            'notes.max' => 'الملاحظات لا يمكن أن تتجاوز 1000 حرف',
            
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
            'tax_id' => 'الرقم الضريبي',
            'tax_percentage' => 'نسبة الضريبة',
            'credit_limit' => 'حد الائتمان',
            'payment_terms_days' => 'مدة الدفع',
            'branches' => 'الفروع',
            'notes' => 'الملاحظات',
            'is_active' => 'حالة التفعيل',
        ];
    }
}
