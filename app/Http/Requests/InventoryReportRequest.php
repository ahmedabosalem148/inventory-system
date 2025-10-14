<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class InventoryReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_date' => ['nullable', 'date', 'before_or_equal:to_date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'type' => ['nullable', 'in:IN,OUT,TRANSFER_IN,TRANSFER_OUT'],
            'threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if date range is provided
            if ($this->filled('from_date') && $this->filled('to_date')) {
                $fromDate = Carbon::parse($this->from_date);
                $toDate = Carbon::parse($this->to_date);
                
                // Calculate days difference (ceil to handle partial days)
                $daysDiff = ceil($fromDate->diffInDays($toDate, false));
                
                // Validate that range is not excessive (max 2 years = 730 days)
                if ($daysDiff > 730) {
                    $validator->errors()->add(
                        'date_range',
                        'نطاق التاريخ لا يمكن أن يتجاوز سنتين (730 يوم). النطاق الحالي: ' . round($daysDiff) . ' يوم.'
                    );
                }
                
                // Optional: Warn if range is very small (less than 1 day)
                if ($daysDiff < 1 && $fromDate->format('Y-m-d') !== $toDate->format('Y-m-d')) {
                    $validator->errors()->add(
                        'date_range',
                        'نطاق التاريخ صغير جداً. يجب أن يكون على الأقل يوم واحد.'
                    );
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'from_date.before_or_equal' => 'تاريخ البداية يجب أن يكون قبل أو يساوي تاريخ النهاية.',
            'to_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية.',
            'branch_id.exists' => 'الفرع المحدد غير موجود.',
            'category_id.exists' => 'الفئة المحددة غير موجودة.',
            'type.in' => 'نوع الحركة غير صحيح.',
            'threshold.min' => 'الحد الأدنى يجب أن يكون رقماً موجباً.',
        ];
    }
}
