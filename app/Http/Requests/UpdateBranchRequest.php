<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super-admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $branch = $this->route('branch');
        $branchId = $branch instanceof \App\Models\Branch ? $branch->id : $branch;
        
        return [
            'code' => 'sometimes|string|max:10|unique:branches,code,' . $branchId,
            'name' => 'sometimes|string|max:100|unique:branches,name,' . $branchId,
            'location' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
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
            'code.max' => 'كود الفرع لا يمكن أن يتجاوز 10 أحرف',
            'code.unique' => 'كود الفرع موجود بالفعل',
            'name.max' => 'اسم الفرع لا يمكن أن يتجاوز 100 حرف',
            'name.unique' => 'اسم الفرع موجود بالفعل',
            'location.max' => 'الموقع لا يمكن أن يتجاوز 255 حرف',
            'phone.max' => 'رقم الهاتف لا يمكن أن يتجاوز 20 حرف',
            'is_active.boolean' => 'حالة النشاط يجب أن تكون صحيح أو خطأ',
        ];
    }
}
