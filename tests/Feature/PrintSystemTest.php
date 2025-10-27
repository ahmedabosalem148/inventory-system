<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\IssueVoucher;
use App\Models\IssueVoucherItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ReturnVoucher;
use App\Models\ReturnVoucherItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrintSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Branch $branch;
    protected Customer $customer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create branch
        $this->branch = Branch::factory()->create(['name' => 'Main Branch']);

        // Create customer
        $this->customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'code' => 'CUST-001',
        ]);

        // Create product
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'product_classification' => 'finished_product',
            'sku' => 'FIN-001',
            'purchase_price' => 100,
            'sale_price' => 150,
        ]);

        // Create user with print permissions
        $role = Role::create(['name' => 'manager']);
        Permission::create(['name' => 'print-issue-vouchers']);
        Permission::create(['name' => 'print-return-vouchers']);
        Permission::create(['name' => 'print-purchase-orders']);
        Permission::create(['name' => 'print-customer-statements']);
        Permission::create(['name' => 'print-cheques']);
        Permission::create(['name' => 'bulk-print']);
        
        $role->givePermissionTo([
            'print-issue-vouchers',
            'print-return-vouchers',
            'print-purchase-orders',
            'print-customer-statements',
            'print-cheques',
            'bulk-print',
        ]);

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@test.com',
        ]);
        $this->user->assignRole($role);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function cannot_print_unapproved_issue_voucher()
    {
        $voucher = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => 'PENDING', // Not approved
            'voucher_number' => 'IV-001',
        ]);

        IssueVoucherItem::factory()->create([
            'issue_voucher_id' => $voucher->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson("/api/v1/print/issue-voucher/{$voucher->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'لا يمكن طباعة الإذن قبل اعتماده',
            ]);
    }

    /** @test */
    public function can_print_approved_issue_voucher()
    {
        $voucher = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => 'APPROVED',
            'voucher_number' => 'IV-001',
            'created_by' => $this->user->id,
        ]);

        IssueVoucherItem::factory()->create([
            'issue_voucher_id' => $voucher->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson("/api/v1/print/issue-voucher/{$voucher->id}");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function print_count_increments_after_printing()
    {
        $voucher = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => 'APPROVED',
            'voucher_number' => 'IV-001',
            'created_by' => $this->user->id,
            'print_count' => 0,
        ]);

        IssueVoucherItem::factory()->create([
            'issue_voucher_id' => $voucher->id,
            'product_id' => $this->product->id,
        ]);

        $this->assertEquals(0, $voucher->print_count);

        // First print
        $this->getJson("/api/v1/print/issue-voucher/{$voucher->id}");
        $voucher->refresh();
        $this->assertEquals(1, $voucher->print_count);

        // Second print
        $this->getJson("/api/v1/print/issue-voucher/{$voucher->id}");
        $voucher->refresh();
        $this->assertEquals(2, $voucher->print_count);
    }

    /** @test */
    public function last_printed_at_updates_after_printing()
    {
        $voucher = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => 'APPROVED',
            'voucher_number' => 'IV-001',
            'created_by' => $this->user->id,
            'last_printed_at' => null,
        ]);

        IssueVoucherItem::factory()->create([
            'issue_voucher_id' => $voucher->id,
            'product_id' => $this->product->id,
        ]);

        $this->assertNull($voucher->last_printed_at);

        $this->getJson("/api/v1/print/issue-voucher/{$voucher->id}");

        $voucher->refresh();
        $this->assertNotNull($voucher->last_printed_at);
    }

    /** @test */
    public function user_needs_permission_to_print_issue_voucher()
    {
        // Create user without print permission
        $userWithoutPermission = User::factory()->create();
        Sanctum::actingAs($userWithoutPermission);

        $voucher = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => 'APPROVED',
            'voucher_number' => 'IV-001',
            'created_by' => $this->user->id,
        ]);

        IssueVoucherItem::factory()->create([
            'issue_voucher_id' => $voucher->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson("/api/v1/print/issue-voucher/{$voucher->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function can_print_return_voucher()
    {
        $voucher = ReturnVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => 'APPROVED',
            'voucher_number' => 'RV-001',
            'created_by' => $this->user->id,
        ]);

        ReturnVoucherItem::factory()->create([
            'return_voucher_id' => $voucher->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson("/api/v1/print/return-voucher/{$voucher->id}");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function can_print_purchase_order()
    {
        $supplier = Supplier::factory()->create();

        $order = PurchaseOrder::factory()->create([
            'branch_id' => $this->branch->id,
            'supplier_id' => $supplier->id,
            'status' => 'APPROVED',
            'order_number' => 'PO-001',
            'created_by' => $this->user->id,
        ]);

        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson("/api/v1/print/purchase-order/{$order->id}");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function can_print_issue_voucher_with_thermal_template()
    {
        $voucher = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => 'APPROVED',
            'voucher_number' => 'IV-001',
            'created_by' => $this->user->id,
        ]);

        IssueVoucherItem::factory()->create([
            'issue_voucher_id' => $voucher->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson("/api/v1/print/issue-voucher/{$voucher->id}?template=thermal");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function cannot_print_voucher_without_items()
    {
        $voucher = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => 'APPROVED',
            'voucher_number' => 'IV-001',
            'created_by' => $this->user->id,
        ]);

        // No items added

        $response = $this->getJson("/api/v1/print/issue-voucher/{$voucher->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'لا توجد منتجات في الإذن',
            ]);
    }

    /** @test */
    public function bulk_print_validates_max_50_documents()
    {
        $response = $this->postJson('/api/v1/print/bulk', [
            'document_type' => 'issue-voucher',
            'ids' => range(1, 51), // 51 documents - exceeds limit
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    /** @test */
    public function bulk_print_requires_at_least_one_id()
    {
        $response = $this->postJson('/api/v1/print/bulk', [
            'document_type' => 'issue-voucher',
            'ids' => [], // Empty array
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);
    }

    /** @test */
    public function bulk_print_only_approved_documents()
    {
        $approved = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => 'APPROVED',
            'created_by' => $this->user->id,
        ]);

        $pending = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => 'PENDING',
            'created_by' => $this->user->id,
        ]);

        IssueVoucherItem::factory()->create([
            'issue_voucher_id' => $approved->id,
            'product_id' => $this->product->id,
        ]);

        IssueVoucherItem::factory()->create([
            'issue_voucher_id' => $pending->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->postJson('/api/v1/print/bulk', [
            'document_type' => 'issue-voucher',
            'ids' => [$approved->id, $pending->id],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'بعض المستندات غير معتمدة',
            ]);
    }

    /** @test */
    public function can_bulk_print_multiple_approved_documents()
    {
        $voucher1 = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => 'APPROVED',
            'created_by' => $this->user->id,
        ]);

        $voucher2 = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => 'APPROVED',
            'created_by' => $this->user->id,
        ]);

        IssueVoucherItem::factory()->create([
            'issue_voucher_id' => $voucher1->id,
            'product_id' => $this->product->id,
        ]);

        IssueVoucherItem::factory()->create([
            'issue_voucher_id' => $voucher2->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->postJson('/api/v1/print/bulk', [
            'document_type' => 'issue-voucher',
            'ids' => [$voucher1->id, $voucher2->id],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'count',
                'download_url',
            ]);
    }

    /** @test */
    public function print_customer_statement_requires_date_range()
    {
        $response = $this->getJson("/api/v1/print/customer-statement/{$this->customer->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_date', 'to_date']);
    }

    /** @test */
    public function print_customer_statement_validates_date_order()
    {
        $response = $this->getJson("/api/v1/print/customer-statement/{$this->customer->id}?from_date=2025-12-31&to_date=2025-01-01");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_date']);
    }

    /** @test */
    public function can_print_customer_statement_with_valid_dates()
    {
        $response = $this->getJson("/api/v1/print/customer-statement/{$this->customer->id}?from_date=2025-01-01&to_date=2025-12-31");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function print_tracking_works_for_all_document_types()
    {
        // Test Issue Voucher
        $issueVoucher = IssueVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => 'APPROVED',
            'created_by' => $this->user->id,
            'print_count' => 0,
        ]);
        IssueVoucherItem::factory()->create([
            'issue_voucher_id' => $issueVoucher->id,
            'product_id' => $this->product->id,
        ]);

        // Test Return Voucher
        $returnVoucher = ReturnVoucher::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => 'APPROVED',
            'created_by' => $this->user->id,
            'print_count' => 0,
        ]);
        ReturnVoucherItem::factory()->create([
            'return_voucher_id' => $returnVoucher->id,
            'product_id' => $this->product->id,
        ]);

        // Test Purchase Order
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create([
            'branch_id' => $this->branch->id,
            'supplier_id' => $supplier->id,
            'status' => 'APPROVED',
            'created_by' => $this->user->id,
            'print_count' => 0,
        ]);
        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $this->product->id,
        ]);

        // Print all
        $this->getJson("/api/v1/print/issue-voucher/{$issueVoucher->id}");
        $this->getJson("/api/v1/print/return-voucher/{$returnVoucher->id}");
        $this->getJson("/api/v1/print/purchase-order/{$purchaseOrder->id}");

        // Check tracking
        $issueVoucher->refresh();
        $returnVoucher->refresh();
        $purchaseOrder->refresh();

        $this->assertEquals(1, $issueVoucher->print_count);
        $this->assertEquals(1, $returnVoucher->print_count);
        $this->assertEquals(1, $purchaseOrder->print_count);

        $this->assertNotNull($issueVoucher->last_printed_at);
        $this->assertNotNull($returnVoucher->last_printed_at);
        $this->assertNotNull($purchaseOrder->last_printed_at);
    }
}
