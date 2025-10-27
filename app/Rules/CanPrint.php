<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class CanPrint implements ValidationRule
{
    protected string $documentType;
    protected $document;

    /**
     * Create a new rule instance.
     *
     * @param string $documentType (issue-voucher, return-voucher, purchase-order, customer-statement, cheque)
     * @param mixed $document The document model instance
     */
    public function __construct(string $documentType, $document)
    {
        $this->documentType = $documentType;
        $this->document = $document;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();

        // 1. التحقق من الحالة - يجب أن تكون approved
        if (!$this->document || !property_exists($this->document, 'status')) {
            $fail('المستند غير موجود أو غير صالح');
            return;
        }

        if ($this->document->status !== 'approved' && $this->document->status !== 'APPROVED') {
            $fail('لا يمكن طباعة المستند قبل اعتماده. الحالة الحالية: ' . $this->document->status);
            return;
        }

        // 2. التحقق من الصلاحيات
        $permission = $this->getRequiredPermission();
        if (!$user->hasRole('super-admin') && !$user->can($permission)) {
            $fail('ليس لديك صلاحية طباعة هذا النوع من المستندات');
            return;
        }

        // 3. التحقق من اكتمال البيانات
        $missingData = $this->checkDataCompleteness();
        if (!empty($missingData)) {
            $fail('بيانات المستند غير مكتملة: ' . implode(', ', $missingData));
            return;
        }

        // 4. Audit Log
        activity()
            ->performedOn($this->document)
            ->causedBy($user)
            ->withProperties([
                'action' => 'print_validation_passed',
                'document_type' => $this->documentType,
                'document_id' => $this->document->id,
            ])
            ->log("تم التحقق من إمكانية طباعة {$this->documentType}");
    }

    /**
     * Get required permission based on document type
     */
    protected function getRequiredPermission(): string
    {
        return match($this->documentType) {
            'issue-voucher' => 'print-issue-vouchers',
            'return-voucher' => 'print-return-vouchers',
            'purchase-order' => 'print-purchase-orders',
            'customer-statement' => 'print-customer-statements',
            'cheque' => 'print-cheques',
            default => 'print-documents',
        };
    }

    /**
     * Check data completeness based on document type
     * 
     * @return array Missing data fields
     */
    protected function checkDataCompleteness(): array
    {
        $missing = [];

        switch ($this->documentType) {
            case 'issue-voucher':
            case 'return-voucher':
                // Check customer
                if (empty($this->document->customer_id) && empty($this->document->customer_name)) {
                    $missing[] = 'بيانات العميل';
                }
                
                // Check items
                if (method_exists($this->document, 'items')) {
                    $items = $this->document->items;
                    if (empty($items) || (is_countable($items) && count($items) === 0)) {
                        $missing[] = 'المنتجات';
                    }
                }
                
                // Check voucher number
                if (empty($this->document->voucher_number)) {
                    $missing[] = 'رقم الإذن';
                }
                break;

            case 'purchase-order':
                // Check supplier
                if (empty($this->document->supplier_id)) {
                    $missing[] = 'بيانات المورد';
                }
                
                // Check items
                if (method_exists($this->document, 'items')) {
                    $items = $this->document->items;
                    if (empty($items) || (is_countable($items) && count($items) === 0)) {
                        $missing[] = 'المنتجات';
                    }
                }
                
                // Check order number
                if (empty($this->document->order_number)) {
                    $missing[] = 'رقم الأمر';
                }
                break;

            case 'customer-statement':
                // Check customer
                if (empty($this->document->customer_id)) {
                    $missing[] = 'بيانات العميل';
                }
                
                // Check date range
                if (empty($this->document->from_date) || empty($this->document->to_date)) {
                    $missing[] = 'الفترة الزمنية';
                }
                break;

            case 'cheque':
                // Check cheque details
                if (empty($this->document->cheque_number)) {
                    $missing[] = 'رقم الشيك';
                }
                if (empty($this->document->bank_name)) {
                    $missing[] = 'اسم البنك';
                }
                if (empty($this->document->amount) || $this->document->amount <= 0) {
                    $missing[] = 'المبلغ';
                }
                break;
        }

        return $missing;
    }
}
