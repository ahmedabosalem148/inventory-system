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
            'issue_voucher_id' => ['nullable', 'integer', 'exists:issue_vouchers,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:CASH,CHEQUE,VODAFONE_CASH,INSTAPAY,BANK_ACCOUNT'],
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
            'cheque_due_date' => [
                'required_if:payment_method,CHEQUE',
                'nullable',
                'date',
                'after_or_equal:cheque_date',
            ],
            'cheque_status' => [
                'sometimes',
                'nullable',
                'in:PENDING,CLEARED,RETURNED,CANCELLED'
            ],
            
            // Vodafone Cash fields
            'vodafone_number' => [
                'required_if:payment_method,VODAFONE_CASH',
                'nullable',
                'string',
                'regex:/^01[0125][0-9]{8}$/', // Egyptian mobile number format
            ],
            'vodafone_reference' => [
                'required_if:payment_method,VODAFONE_CASH',
                'nullable',
                'string',
                'max:50'
            ],
            
            // InstaPay fields
            'instapay_reference' => [
                'required_if:payment_method,INSTAPAY',
                'nullable',
                'string',
                'max:100'
            ],
            'instapay_account' => [
                'required_if:payment_method,INSTAPAY',
                'nullable',
                'string',
                'max:100'
            ],
            
            // Bank Account fields
            'bank_account_number' => [
                'required_if:payment_method,BANK_ACCOUNT',
                'nullable',
                'string',
                'max:50'
            ],
            'bank_account_name' => [
                'required_if:payment_method,BANK_ACCOUNT',
                'nullable',
                'string',
                'max:100'
            ],
            'bank_transaction_reference' => [
                'required_if:payment_method,BANK_ACCOUNT',
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
            
            // Vodafone Cash validations
            'vodafone_number.required_if' => 'رقم فودافون كاش مطلوب',
            'vodafone_number.regex' => 'رقم فودافون كاش غير صحيح (يجب أن يكون رقم مصري)',
            'vodafone_reference.required_if' => 'رقم العملية مطلوب',
            'vodafone_reference.max' => 'رقم العملية لا يمكن أن يتجاوز 50 حرفاً',
            
            // InstaPay validations
            'instapay_reference.required_if' => 'رقم عملية InstaPay مطلوب',
            'instapay_reference.max' => 'رقم العملية لا يمكن أن يتجاوز 100 حرف',
            'instapay_account.required_if' => 'حساب InstaPay مطلوب',
            'instapay_account.max' => 'حساب InstaPay لا يمكن أن يتجاوز 100 حرف',
            
            // Bank Account validations
            'bank_account_number.required_if' => 'رقم الحساب البنكي مطلوب',
            'bank_account_number.max' => 'رقم الحساب البنكي لا يمكن أن يتجاوز 50 حرفاً',
            'bank_account_name.required_if' => 'اسم البنك مطلوب',
            'bank_account_name.max' => 'اسم البنك لا يمكن أن يتجاوز 100 حرف',
            'bank_transaction_reference.required_if' => 'رقم المعاملة البنكية مطلوب',
            'bank_transaction_reference.max' => 'رقم المعاملة البنكية لا يمكن أن يتجاوز 100 حرف',
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
            'vodafone_number' => 'رقم فودافون كاش',
            'vodafone_reference' => 'رقم عملية فودافون كاش',
            'instapay_reference' => 'رقم عملية InstaPay',
            'instapay_account' => 'حساب InstaPay',
            'bank_account_number' => 'رقم الحساب البنكي',
            'bank_account_name' => 'اسم البنك',
            'bank_transaction_reference' => 'رقم المعاملة البنكية',
        ];
    }

    /**
     * Get warnings for post-dated cheques and other non-blocking issues.
     */
    public function getWarnings(): array
    {
        $warnings = [];
        
        // Warning for post-dated cheques (more than 6 months)
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
        
        // Warning when payment exceeds customer balance
        if ($this->input('customer_id') && $this->input('amount')) {
            $customer = \App\Models\Customer::find($this->input('customer_id'));
            
            if ($customer && $customer->balance > 0) {
                $paymentAmount = floatval($this->input('amount'));
                
                if ($paymentAmount > $customer->balance) {
                    $warnings[] = [
                        'field' => 'amount',
                        'message' => sprintf(
                            'تحذير: المبلغ المدفوع (%.2f) أكبر من رصيد العميل الحالي (%.2f)',
                            $paymentAmount,
                            $customer->balance
                        )
                    ];
                }
            }
        }
        
        return $warnings;
    }
}
