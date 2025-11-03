<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidStatusTransition implements ValidationRule
{
    /**
     * Allowed status transitions map
     * 
     * @var array<string, array<string>>
     */
    private array $allowedTransitions = [
        'PENDING' => ['APPROVED', 'CANCELLED'],
        'APPROVED' => ['COMPLETED', 'CANCELLED'],
        'CANCELLED' => [], // Terminal state - no transitions allowed
        'COMPLETED' => [], // Terminal state - no transitions allowed
    ];

    /**
     * Create a new rule instance.
     */
    public function __construct(
        private ?string $currentStatus = null,
        private ?string $documentType = 'document'
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If no current status provided, allow any status (for new records)
        if ($this->currentStatus === null) {
            return;
        }

        // Normalize status values to uppercase
        $currentStatus = strtoupper($this->currentStatus);
        $newStatus = strtoupper($value);

        // If status hasn't changed, allow it
        if ($currentStatus === $newStatus) {
            return;
        }

        // Check if current status exists in transitions map
        if (!isset($this->allowedTransitions[$currentStatus])) {
            $fail("الحالة الحالية '$currentStatus' غير معروفة");
            return;
        }

        // Check if transition is allowed
        $allowedNextStatuses = $this->allowedTransitions[$currentStatus];
        
        if (empty($allowedNextStatuses)) {
            $fail($this->getTerminalStateMessage($currentStatus));
            return;
        }

        if (!in_array($newStatus, $allowedNextStatuses, true)) {
            $fail($this->getInvalidTransitionMessage($currentStatus, $newStatus, $allowedNextStatuses));
            return;
        }
    }

    /**
     * Get error message for terminal state
     */
    private function getTerminalStateMessage(string $status): string
    {
        return match ($status) {
            'CANCELLED' => "لا يمكن تعديل {$this->documentType} بعد إلغائه",
            'COMPLETED' => "لا يمكن تعديل {$this->documentType} بعد اكتماله",
            default => "لا يمكن تغيير الحالة من '$status'",
        };
    }

    /**
     * Get error message for invalid transition
     */
    private function getInvalidTransitionMessage(string $from, string $to, array $allowed): string
    {
        $allowedArabic = array_map(fn($s) => $this->translateStatus($s), $allowed);
        $allowedList = implode(' أو ', $allowedArabic);
        
        $fromArabic = $this->translateStatus($from);
        $toArabic = $this->translateStatus($to);
        
        return "لا يمكن تغيير الحالة من '$fromArabic' إلى '$toArabic'. الحالات المسموحة: $allowedList";
    }

    /**
     * Translate status to Arabic
     */
    private function translateStatus(string $status): string
    {
        return match ($status) {
            'PENDING' => 'قيد الانتظار',
            'APPROVED' => 'معتمد',
            'COMPLETED' => 'مكتمل',
            'CANCELLED' => 'ملغي',
            default => $status,
        };
    }
}
