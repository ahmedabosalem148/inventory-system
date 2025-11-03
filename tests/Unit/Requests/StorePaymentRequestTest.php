<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StorePaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(array $data): StorePaymentRequest
    {
        $request = new StorePaymentRequest();
        $request->replace($data);
        return $request;
    }

    public function test_it_allows_authenticated_users(): void
    {
        $user = User::factory()->create();
        $request = $this->makeRequest([]);
        
        $this->actingAs($user);
        $this->assertTrue($request->authorize());
    }

    public function test_it_validates_required_fields(): void
    {
        $request = $this->makeRequest([]);
        
        $validator = Validator::make([], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('issue_voucher_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertArrayHasKey('payment_method', $validator->errors()->toArray());
        $this->assertArrayHasKey('payment_date', $validator->errors()->toArray());
    }

    public function test_it_validates_customer_exists(): void
    {
        $issueVoucher = \App\Models\IssueVoucher::factory()->create();
        
        $request = $this->makeRequest([
            'issue_voucher_id' => $issueVoucher->id,
            'customer_id' => 99999,
            'amount' => 100,
            'payment_method' => 'CASH',
            'payment_date' => now()->format('Y-m-d')
        ]);
        
        $validator = Validator::make($request->all(), $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('customer_id', $validator->errors()->toArray());
    }

    public function test_it_validates_payment_method_enum(): void
    {
        $issueVoucher = \App\Models\IssueVoucher::factory()->create();
        $customer = Customer::factory()->create();
        
        $request = $this->makeRequest([
            'issue_voucher_id' => $issueVoucher->id,
            'customer_id' => $customer->id,
            'amount' => 100,
            'payment_method' => 'INVALID_METHOD',
            'payment_date' => now()->format('Y-m-d')
        ]);
        
        $validator = Validator::make($request->all(), $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('payment_method', $validator->errors()->toArray());
    }

    public function test_it_validates_amount_is_positive(): void
    {
        $issueVoucher = \App\Models\IssueVoucher::factory()->create();
        $customer = Customer::factory()->create();
        
        $request = $this->makeRequest([
            'issue_voucher_id' => $issueVoucher->id,
            'customer_id' => $customer->id,
            'amount' => -100,
            'payment_method' => 'CASH',
            'payment_date' => now()->format('Y-m-d')
        ]);
        
        $validator = Validator::make($request->all(), $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    public function test_it_accepts_valid_payment_data(): void
    {
        $issueVoucher = \App\Models\IssueVoucher::factory()->create();
        $customer = Customer::factory()->create();
        
        $request = $this->makeRequest([
            'issue_voucher_id' => $issueVoucher->id,
            'customer_id' => $customer->id,
            'amount' => 500.00,
            'payment_method' => 'CASH',
            'payment_date' => now()->format('Y-m-d'),
            'notes' => 'Test payment'
        ]);
        
        $validator = Validator::make($request->all(), $request->rules());
        
        $this->assertFalse($validator->fails());
    }

    public function test_it_warns_when_payment_exceeds_customer_balance(): void
    {
        $customer = Customer::factory()->create(['balance' => 100.00]);
        
        $request = $this->makeRequest([
            'customer_id' => $customer->id,
            'amount' => 150.00,
            'payment_method' => 'CASH',
            'payment_date' => now()->format('Y-m-d')
        ]);
        
        $warnings = $request->getWarnings();
        
        $this->assertNotEmpty($warnings);
        $this->assertEquals('amount', $warnings[0]['field']);
        $this->assertStringContainsString('تحذير', $warnings[0]['message']);
        $this->assertStringContainsString('150.00', $warnings[0]['message']);
        $this->assertStringContainsString('100.00', $warnings[0]['message']);
    }

    public function test_it_does_not_warn_when_payment_within_customer_balance(): void
    {
        $customer = Customer::factory()->create(['balance' => 100.00]);
        
        $request = $this->makeRequest([
            'customer_id' => $customer->id,
            'amount' => 50.00,
            'payment_method' => 'CASH',
            'payment_date' => now()->format('Y-m-d')
        ]);
        
        $warnings = $request->getWarnings();
        
        // Should have no warnings about balance
        $balanceWarnings = array_filter($warnings, function($warning) {
            return $warning['field'] === 'amount';
        });
        
        $this->assertEmpty($balanceWarnings);
    }

    public function test_it_warns_about_post_dated_cheques(): void
    {
        $issueVoucher = \App\Models\IssueVoucher::factory()->create();
        $customer = Customer::factory()->create();
        $futureChequeDate = now()->addMonths(7)->format('Y-m-d');
        
        $request = $this->makeRequest([
            'issue_voucher_id' => $issueVoucher->id,
            'customer_id' => $customer->id,
            'amount' => 100.00,
            'payment_method' => 'CHEQUE',
            'payment_date' => now()->format('Y-m-d'),
            'cheque_number' => 'CHQ-12345',
            'cheque_date' => $futureChequeDate,
            'bank_name' => 'Test Bank'
        ]);
        
        $warnings = $request->getWarnings();
        
        $this->assertNotEmpty($warnings);
        $chequeWarnings = array_filter($warnings, function($warning) {
            return $warning['field'] === 'cheque_date';
        });
        $this->assertNotEmpty($chequeWarnings);
    }

    public function test_it_has_arabic_error_messages(): void
    {
        $request = $this->makeRequest([]);
        
        $messages = $request->messages();
        
        $this->assertArrayHasKey('customer_id.required', $messages);
        $this->assertArrayHasKey('amount.required', $messages);
        $this->assertArrayHasKey('payment_method.required', $messages);
    }
}
