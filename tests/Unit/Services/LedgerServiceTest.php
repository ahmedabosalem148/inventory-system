<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LedgerService;
use App\Models\Customer;
use App\Models\LedgerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LedgerService $service;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LedgerService();

        $this->customer = Customer::create([
            'code' => 'CUST-001',
            'name' => 'Test Customer',
            'phone' => '01234567890',
            'address' => 'Test Address',
            'type' => 'retail',
        ]);
    }

    /** @test */
    public function it_records_debit_entry()
    {
        // Act
        $entry = $this->service->recordDebit(
            $this->customer->id,
            500,
            'Test invoice',
            'issue_voucher',
            123
        );

        // Assert
        $this->assertInstanceOf(LedgerEntry::class, $entry);
        $this->assertEquals('debit', $entry->type);
        $this->assertEquals(500, $entry->amount);
        $this->assertEquals($this->customer->id, $entry->customer_id);
        $this->assertEquals('issue_voucher', $entry->reference_type);
        $this->assertEquals(123, $entry->reference_id);
    }

    /** @test */
    public function it_records_credit_entry()
    {
        // Act
        $entry = $this->service->recordCredit(
            $this->customer->id,
            300,
            'Payment received',
            'payment',
            456
        );

        // Assert
        $this->assertEquals('credit', $entry->type);
        $this->assertEquals(300, $entry->amount);
        $this->assertEquals($this->customer->id, $entry->customer_id);
    }

    /** @test */
    public function it_calculates_customer_balance_correctly()
    {
        // Arrange: Multiple transactions
        $this->service->recordDebit($this->customer->id, 1000, 'Invoice 1', 'issue_voucher', 1);
        $this->service->recordDebit($this->customer->id, 500, 'Invoice 2', 'issue_voucher', 2);
        $this->service->recordCredit($this->customer->id, 300, 'Payment 1', 'payment', 1);
        $this->service->recordCredit($this->customer->id, 200, 'Payment 2', 'payment', 2);

        // Act
        $balance = $this->service->getCustomerBalance($this->customer->id);

        // Assert: Balance = Debits - Credits = (1000 + 500) - (300 + 200) = 1000
        $this->assertEquals(1000, $balance);
    }

    /** @test */
    public function it_returns_zero_balance_for_customer_with_no_entries()
    {
        // Act
        $balance = $this->service->getCustomerBalance($this->customer->id);

        // Assert
        $this->assertEquals(0, $balance);
    }

    /** @test */
    public function it_calculates_negative_balance_when_credits_exceed_debits()
    {
        // Arrange: More credits than debits
        $this->service->recordDebit($this->customer->id, 500, 'Invoice', 'issue_voucher', 1);
        $this->service->recordCredit($this->customer->id, 1000, 'Overpayment', 'payment', 1);

        // Act
        $balance = $this->service->getCustomerBalance($this->customer->id);

        // Assert: Balance = 500 - 1000 = -500 (customer has credit)
        $this->assertEquals(-500, $balance);
    }

    /** @test */
    public function it_gets_ledger_entries_for_customer()
    {
        // Arrange
        $this->service->recordDebit($this->customer->id, 100, 'Entry 1', 'issue_voucher', 1);
        $this->service->recordCredit($this->customer->id, 50, 'Entry 2', 'payment', 1);
        $this->service->recordDebit($this->customer->id, 200, 'Entry 3', 'issue_voucher', 2);

        // Act
        $entries = $this->service->getCustomerLedger($this->customer->id);

        // Assert
        $this->assertCount(3, $entries);
        $this->assertEquals('debit', $entries[0]->type);
        $this->assertEquals('credit', $entries[1]->type);
        $this->assertEquals('debit', $entries[2]->type);
    }

    /** @test */
    public function it_orders_entries_by_date_descending()
    {
        // Arrange: Create entries with different dates
        $entry1 = LedgerEntry::create([
            'customer_id' => $this->customer->id,
            'type' => 'debit',
            'amount' => 100,
            'description' => 'Old entry',
            'created_at' => now()->subDays(5),
        ]);

        $entry2 = LedgerEntry::create([
            'customer_id' => $this->customer->id,
            'type' => 'credit',
            'amount' => 50,
            'description' => 'Recent entry',
            'created_at' => now()->subDay(),
        ]);

        $entry3 = LedgerEntry::create([
            'customer_id' => $this->customer->id,
            'type' => 'debit',
            'amount' => 200,
            'description' => 'Latest entry',
            'created_at' => now(),
        ]);

        // Act
        $entries = $this->service->getCustomerLedger($this->customer->id);

        // Assert: Should be in descending order (latest first)
        $this->assertEquals($entry3->id, $entries[0]->id);
        $this->assertEquals($entry2->id, $entries[1]->id);
        $this->assertEquals($entry1->id, $entries[2]->id);
    }

    /** @test */
    public function it_calculates_running_balance_in_ledger()
    {
        // Arrange
        $this->service->recordDebit($this->customer->id, 1000, 'Invoice 1', 'issue_voucher', 1);
        $this->service->recordCredit($this->customer->id, 300, 'Payment 1', 'payment', 1);
        $this->service->recordDebit($this->customer->id, 500, 'Invoice 2', 'issue_voucher', 2);
        $this->service->recordCredit($this->customer->id, 200, 'Payment 2', 'payment', 2);

        // Act
        $entries = $this->service->getCustomerLedgerWithBalance($this->customer->id);

        // Assert: Each entry should have running balance
        // Running balance after each:
        // 1. +1000 = 1000
        // 2. -300 = 700
        // 3. +500 = 1200
        // 4. -200 = 1000

        $this->assertEquals(1000, $entries[0]['balance']); // Latest entry shows final balance
        $this->assertEquals(1200, $entries[1]['balance']);
        $this->assertEquals(700, $entries[2]['balance']);
        $this->assertEquals(1000, $entries[3]['balance']);
    }

    /** @test */
    public function it_prevents_negative_amounts()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        // Act
        $this->service->recordDebit($this->customer->id, -100, 'Negative amount', 'test', 1);
    }

    /** @test */
    public function it_prevents_zero_amounts()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        // Act
        $this->service->recordCredit($this->customer->id, 0, 'Zero amount', 'test', 1);
    }

    /** @test */
    public function it_gets_customers_with_outstanding_balance()
    {
        // Arrange: Create multiple customers with different balances
        $customer2 = Customer::create([
            'code' => 'CUST-002',
            'name' => 'Customer 2',
            'phone' => '01111111111',
            'type' => 'wholesale',
        ]);

        $customer3 = Customer::create([
            'code' => 'CUST-003',
            'name' => 'Customer 3',
            'phone' => '01222222222',
            'type' => 'retail',
        ]);

        // Customer 1: Balance = 500
        $this->service->recordDebit($this->customer->id, 500, 'Invoice', 'issue_voucher', 1);

        // Customer 2: Balance = 0 (fully paid)
        $this->service->recordDebit($customer2->id, 300, 'Invoice', 'issue_voucher', 2);
        $this->service->recordCredit($customer2->id, 300, 'Payment', 'payment', 1);

        // Customer 3: Balance = 1000
        $this->service->recordDebit($customer3->id, 1000, 'Invoice', 'issue_voucher', 3);

        // Act
        $customersWithBalance = $this->service->getCustomersWithOutstandingBalance();

        // Assert: Should only include customers 1 and 3
        $this->assertCount(2, $customersWithBalance);
        
        $codes = $customersWithBalance->pluck('code')->toArray();
        $this->assertContains('CUST-001', $codes);
        $this->assertContains('CUST-003', $codes);
        $this->assertNotContains('CUST-002', $codes);
    }

    /** @test */
    public function it_filters_ledger_by_date_range()
    {
        // Arrange
        $oldDate = now()->subMonths(2);
        $recentDate = now()->subDays(5);
        $latestDate = now();

        $old = LedgerEntry::create([
            'customer_id' => $this->customer->id,
            'type' => 'debit',
            'amount' => 100,
            'description' => 'Old',
        ]);
        $old->created_at = $oldDate;
        $old->save();

        $recent = LedgerEntry::create([
            'customer_id' => $this->customer->id,
            'type' => 'credit',
            'amount' => 50,
            'description' => 'Recent',
        ]);
        $recent->created_at = $recentDate;
        $recent->save();

        $latest = LedgerEntry::create([
            'customer_id' => $this->customer->id,
            'type' => 'debit',
            'amount' => 200,
            'description' => 'Latest',
        ]);
        $latest->created_at = $latestDate;
        $latest->save();

        // Act: Get entries from last 7 days
        $startDate = now()->subDays(7);
        $endDate = now();
        
        $entries = $this->service->getCustomerLedger(
            $this->customer->id,
            $startDate,
            $endDate
        );

        // Assert: Should only include recent and latest entries (not old)
        $this->assertCount(2, $entries);
        $descriptions = $entries->pluck('description')->toArray();
        $this->assertContains('Recent', $descriptions);
        $this->assertContains('Latest', $descriptions);
        $this->assertNotContains('Old', $descriptions);
    }

    /** @test */
    public function it_records_discount_as_credit()
    {
        // Arrange: Invoice with discount
        $this->service->recordDebit($this->customer->id, 1000, 'Invoice total', 'issue_voucher', 1);

        // Act
        $discountEntry = $this->service->recordCredit(
            $this->customer->id,
            100,
            'Discount 10%',
            'discount',
            1
        );

        // Assert
        $balance = $this->service->getCustomerBalance($this->customer->id);
        $this->assertEquals(900, $balance); // 1000 - 100 discount
        $this->assertEquals('discount', $discountEntry->reference_type);
    }

    /** @test */
    public function it_handles_return_voucher_as_credit()
    {
        // Arrange: Original invoice
        $this->service->recordDebit($this->customer->id, 1000, 'Invoice', 'issue_voucher', 1);

        // Act: Return voucher
        $returnEntry = $this->service->recordCredit(
            $this->customer->id,
            200,
            'Return voucher',
            'return_voucher',
            1
        );

        // Assert
        $balance = $this->service->getCustomerBalance($this->customer->id);
        $this->assertEquals(800, $balance); // 1000 - 200 return
    }

    /** @test */
    public function it_validates_customer_exists()
    {
        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act: Try to record entry for non-existent customer
        $this->service->recordDebit(9999, 100, 'Test', 'test', 1);
    }
}
