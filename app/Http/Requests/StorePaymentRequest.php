<?php

namespace App\Http\Requests;

use App\Rules\UniqueChequeNumber;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            // Basic fields
            'issue_voucher_id' => ['required', 'integer', 'exists:issue_vouchers,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:CASH,CHEQUE,BANK_TRANSFER,CREDIT_CARD'],
            'notes' => ['nullable', 'string', 'max:500'],
            
            // Cheque fields (required if payment_method is CHEQUE)
            'cheque_number' => [
                'required_if:payment_method,CHEQUE',
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('payment_method') === 'CHEQUE') {
                        $bankName = $this->input('bank_name');
                        if (!$bankName) {
                            $fail('اسم البنك مطلوب عند استخدام الشيكات');
                            return;
                        }
                        
                        $validator = new UniqueChequeNumber($bankName);
                        $validator->validate($attribute, $value, $fail);
                    }
                }
            ],
            'bank_name' => [
                'required_if:payment_method,CHEQUE',
                'nullable',
                'string',
                'max:100'
            ],
            'cheque_date' => [
                'required_if:payment_method,CHEQUE',
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('payment_method') === 'CHEQUE') {
                        $paymentDate = $this->input('payment_date');
                        if ($paymentDate && strtotime($value) < strtotime($paymentDate)) {
                            $fail('تاريخ الشيك لا يمكن أن يكون قبل تاريخ الدفع');
                        }
                        
                        // Warning for post-dated cheques (more than 6 months)
                        $sixMonthsFromNow = strtotime('+6 months');
                        if (strtotime($value) > $sixMonthsFromNow) {
                            // This is a warning, not an error
                            // Could be handled differently in the controller
                        }
                    }
                }
            ],
            'cheque_status' => [
                'sometimes',
                'nullable',
                'in:PENDING,CLEARED,BOUNCED,CANCELLED'
            ],
            
            // Bank transfer fields (required if payment_method is BANK_TRANSFER)
            'transaction_reference' => [
                'required_if:payment_method,BANK_TRANSFER',
                'nullable',
                'string',
                'max:100'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'issue_voucher_id.required' => 'إذن الصرف مطلوب',
            'issue_voucher_id.exists' => 'إذن الصرف المحدد غير موجود',
            
            'customer_id.required' => 'العميل مطلوب',
            'customer_id.exists' => 'العميل المحدد غير موجود',
            
            'payment_date.required' => 'تاريخ الدفع مطلوب',
            'payment_date.date' => 'تاريخ الدفع غير صالح',
            'payment_date.before_or_equal' => 'تاريخ الدفع لا يمكن أن يكون في المستقبل',
            
            'amount.required' => 'المبلغ مطلوب',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر',
            
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'payment_method.in' => 'طريقة الدفع غير صالحة',
            
            'notes.max' => 'الملاحظات لا يمكن أن تتجاوز 500 حرف',
            
            // Cheque validations
            'cheque_number.required_if' => 'رقم الشيك مطلوب عند اختيار الدفع بالشيك',
            'cheque_number.max' => 'رقم الشيك لا يمكن أن يتجاوز 50 حرفاً',
            
            'bank_name.required_if' => 'اسم البنك مطلوب عند اختيار الدفع بالشيك',
            'bank_name.max' => 'اسم البنك لا يمكن أن يتجاوز 100 حرف',
            
            'cheque_date.required_if' => 'تاريخ الشيك مطلوب عند اختيار الدفع بالشيك',
            'cheque_date.date' => 'تاريخ الشيك غير صالح',
            
            'cheque_status.in' => 'حالة الشيك غير صالحة',
            
            // Bank transfer validations
            'transaction_reference.required_if' => 'رقم المعاملة مطلوب عند اختيار التحويل البنكي',
            'transaction_reference.max' => 'رقم المعاملة لا يمكن أن يتجاوز 100 حرف',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'issue_voucher_id' => 'إذن الصرف',
            'customer_id' => 'العميل',
            'payment_date' => 'تاريخ الدفع',
            'amount' => 'المبلغ',
            'payment_method' => 'طريقة الدفع',
            'notes' => 'الملاحظات',
            'cheque_number' => 'رقم الشيك',
            'bank_name' => 'اسم البنك',
            'cheque_date' => 'تاريخ الشيك',
            'cheque_status' => 'حالة الشيك',
            'transaction_reference' => 'رقم المعاملة',
        ];
    }

    /**
     * Get warnings for post-dated cheques and other non-blocking issues.
     */
    public function getWarnings(): array
    {
        $warnings = [];
        
        if ($this->input('payment_method') === 'CHEQUE' && $this->input('cheque_date')) {
            $chequeDate = strtotime($this->input('cheque_date'));
            $sixMonthsFromNow = strtotime('+6 months');
            
            if ($chequeDate > $sixMonthsFromNow) {
                $warnings[] = [
                    'field' => 'cheque_date',
                    'message' => 'تحذير: الشيك مؤجل لأكثر من 6 أشهر'
                ];
            }
        }
        
        return $warnings;
    }
}
