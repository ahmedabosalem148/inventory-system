<?php

namespace Tests\Unit\Rules;

use App\Rules\ValidStatusTransition;
use PHPUnit\Framework\TestCase;

class ValidStatusTransitionTest extends TestCase
{
    /**
     * Test that new records (no current status) can have any status
     */
    public function test_allows_any_status_for_new_records(): void
    {
        $rule = new ValidStatusTransition(null, 'إذن صرف');
        
        $fail = function ($message) {
            $this->fail("Validation should pass but failed with: $message");
        };
        
        $rule->validate('status', 'PENDING', $fail);
        $rule->validate('status', 'APPROVED', $fail);
        
        $this->assertTrue(true); // If we reach here, validation passed
    }

    /**
     * Test that same status is always allowed
     */
    public function test_allows_same_status(): void
    {
        $rule = new ValidStatusTransition('PENDING', 'إذن صرف');
        
        $fail = function ($message) {
            $this->fail("Validation should pass but failed with: $message");
        };
        
        $rule->validate('status', 'PENDING', $fail);
        
        $this->assertTrue(true);
    }

    /**
     * Test valid transitions from PENDING
     */
    public function test_allows_valid_transitions_from_pending(): void
    {
        $rule = new ValidStatusTransition('PENDING', 'إذن صرف');
        
        $fail = function ($message) {
            $this->fail("Validation should pass but failed with: $message");
        };
        
        // PENDING can go to APPROVED or CANCELLED
        $rule->validate('status', 'APPROVED', $fail);
        
        $rule2 = new ValidStatusTransition('PENDING', 'إذن صرف');
        $rule2->validate('status', 'CANCELLED', $fail);
        
        $this->assertTrue(true);
    }

    /**
     * Test invalid transition from PENDING to COMPLETED
     */
    public function test_rejects_invalid_transition_from_pending_to_completed(): void
    {
        $rule = new ValidStatusTransition('PENDING', 'إذن صرف');
        
        $failed = false;
        $failMessage = '';
        
        $fail = function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        };
        
        $rule->validate('status', 'COMPLETED', $fail);
        
        $this->assertTrue($failed);
        $this->assertStringContainsString('قيد الانتظار', $failMessage);
        $this->assertStringContainsString('مكتمل', $failMessage);
    }

    /**
     * Test valid transitions from APPROVED
     */
    public function test_allows_valid_transitions_from_approved(): void
    {
        $rule = new ValidStatusTransition('APPROVED', 'إذن صرف');
        
        $fail = function ($message) {
            $this->fail("Validation should pass but failed with: $message");
        };
        
        // APPROVED can go to COMPLETED or CANCELLED
        $rule->validate('status', 'COMPLETED', $fail);
        
        $rule2 = new ValidStatusTransition('APPROVED', 'إذن صرف');
        $rule2->validate('status', 'CANCELLED', $fail);
        
        $this->assertTrue(true);
    }

    /**
     * Test invalid transition from APPROVED to PENDING
     */
    public function test_rejects_invalid_transition_from_approved_to_pending(): void
    {
        $rule = new ValidStatusTransition('APPROVED', 'إذن صرف');
        
        $failed = false;
        $failMessage = '';
        
        $fail = function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        };
        
        $rule->validate('status', 'PENDING', $fail);
        
        $this->assertTrue($failed);
        $this->assertStringContainsString('معتمد', $failMessage);
        $this->assertStringContainsString('قيد الانتظار', $failMessage);
    }

    /**
     * Test that CANCELLED is a terminal state
     */
    public function test_rejects_any_transition_from_cancelled(): void
    {
        $rule = new ValidStatusTransition('CANCELLED', 'إذن صرف');
        
        $failed = false;
        $failMessage = '';
        
        $fail = function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        };
        
        $rule->validate('status', 'PENDING', $fail);
        
        $this->assertTrue($failed);
        $this->assertStringContainsString('إلغائه', $failMessage);
    }

    /**
     * Test that COMPLETED is a terminal state
     */
    public function test_rejects_any_transition_from_completed(): void
    {
        $rule = new ValidStatusTransition('COMPLETED', 'إذن صرف');
        
        $failed = false;
        $failMessage = '';
        
        $fail = function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        };
        
        $rule->validate('status', 'PENDING', $fail);
        
        $this->assertTrue($failed);
        $this->assertStringContainsString('اكتماله', $failMessage);
    }

    /**
     * Test case insensitivity
     */
    public function test_handles_case_insensitive_statuses(): void
    {
        $rule = new ValidStatusTransition('pending', 'إذن صرف');
        
        $fail = function ($message) {
            $this->fail("Validation should pass but failed with: $message");
        };
        
        $rule->validate('status', 'approved', $fail);
        
        $this->assertTrue(true);
    }

    /**
     * Test with different document types
     */
    public function test_uses_document_type_in_error_messages(): void
    {
        $rule = new ValidStatusTransition('COMPLETED', 'أمر شراء');
        
        $failed = false;
        $failMessage = '';
        
        $fail = function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        };
        
        $rule->validate('status', 'PENDING', $fail);
        
        $this->assertTrue($failed);
        $this->assertStringContainsString('أمر شراء', $failMessage);
    }

    /**
     * Test unknown status
     */
    public function test_rejects_unknown_current_status(): void
    {
        $rule = new ValidStatusTransition('UNKNOWN_STATUS', 'إذن صرف');
        
        $failed = false;
        $failMessage = '';
        
        $fail = function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        };
        
        $rule->validate('status', 'PENDING', $fail);
        
        $this->assertTrue($failed);
        $this->assertStringContainsString('غير معروفة', $failMessage);
    }
}
