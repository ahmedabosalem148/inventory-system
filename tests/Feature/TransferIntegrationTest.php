<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Category;
use App\Models\ProductBranch;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransferIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected Branch $sourceBranch;
    protected Branch $targetBranch;
    protected Category $category;
    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryService = new InventoryService();

        // Create Category
        $this->category = Category::create([
            'name' => 'Electronics',
        ]);

        // Create Branches
        $this->sourceBranch = Branch::create([
            'code' => 'SRC',
            'name' => 'Source Branch',
            'location' => 'Cairo',
        ]);

        $this->targetBranch = Branch::create([
            'code' => 'TGT',
            'name' => 'Target Branch',
            'location' => 'Alexandria',
        ]);

        // Create Product
        $this->product = Product::create([
            'sku' => 'TRANS-001',
            'name' => 'Transfer Test Product',
            'category_id' => $this->category->id,
            'unit' => 'piece',
            'purchase_price' => 100,
            'sale_price' => 150,
            'reorder_level' => 10,
        ]);

        // Create User
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_transfers_product_between_branches_and_updates_both_stocks()
    {
        // Arrange: Source has 50 units
        ProductBranch::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->sourceBranch->id,
            'current_stock' => 50,
        ]);

        // Target has 0 units initially
        $this->assertEquals(0, $this->inventoryService->getCurrentStock(
            $this->product->id,
            $this->targetBranch->id
        ));

        // Act: Transfer 20 units
        $result = $this->inventoryService->transferProduct(
            $this->product->id,
            $this->sourceBranch->id,
            $this->targetBranch->id,
            20,
            'Transfer for testing'
        );

        // Assert: Transfer created 2 movements
        $this->assertArrayHasKey('out', $result);
        $this->assertArrayHasKey('in', $result);
        
        $this->assertEquals('TRANSFER_OUT', $result['out']->movement_type);
        $this->assertEquals('TRANSFER_IN', $result['in']->movement_type);
        $this->assertEquals(20, $result['out']->qty_units);
        $this->assertEquals(20, $result['in']->qty_units);

        // Assert: Source stock decreased
        $sourceStock = $this->inventoryService->getCurrentStock(
            $this->product->id,
            $this->sourceBranch->id
        );
        $this->assertEquals(30, $sourceStock); // 50 - 20 = 30

        // Assert: Target stock increased
        $targetStock = $this->inventoryService->getCurrentStock(
            $this->product->id,
            $this->targetBranch->id
        );
        $this->assertEquals(20, $targetStock); // 0 + 20 = 20
    }

    /** @test */
    public function it_prevents_transfer_with_insufficient_stock()
    {
        // Arrange: Source has only 10 units
        ProductBranch::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->sourceBranch->id,
            'current_stock' => 10,
        ]);

        // Assert: Should throw exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock for transfer');

        // Act: Try to transfer 20 units (more than available)
        $this->inventoryService->transferProduct(
            $this->product->id,
            $this->sourceBranch->id,
            $this->targetBranch->id,
            20,
            'Over-transfer test'
        );
    }

    /** @test */
    public function it_creates_target_branch_stock_record_if_not_exists()
    {
        // Arrange: Source has stock, target has nothing
        ProductBranch::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->sourceBranch->id,
            'current_stock' => 100,
        ]);

        // Verify target has no record initially
        $targetRecord = ProductBranch::where('product_id', $this->product->id)
            ->where('branch_id', $this->targetBranch->id)
            ->first();
        $this->assertNull($targetRecord);

        // Act: Transfer 30 units
        $this->inventoryService->transferProduct(
            $this->product->id,
            $this->sourceBranch->id,
            $this->targetBranch->id,
            30,
            'First transfer to target'
        );

        // Assert: Target record created with correct quantity
        $targetRecord = ProductBranch::where('product_id', $this->product->id)
            ->where('branch_id', $this->targetBranch->id)
            ->first();
        
        $this->assertNotNull($targetRecord);
        $this->assertEquals(30, $targetRecord->current_stock);
    }

    /** @test */
    public function it_records_both_transfer_movements_in_history()
    {
        // Arrange
        ProductBranch::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->sourceBranch->id,
            'current_stock' => 60,
        ]);

        // Act
        $this->inventoryService->transferProduct(
            $this->product->id,
            $this->sourceBranch->id,
            $this->targetBranch->id,
            15,
            'Test transfer movement'
        );

        // Assert: Check source movements
        $sourceMovements = \App\Models\InventoryMovement::where('product_id', $this->product->id)
            ->where('branch_id', $this->sourceBranch->id)
            ->where('movement_type', 'TRANSFER_OUT')
            ->get();
        
        $this->assertCount(1, $sourceMovements);
        $this->assertEquals(15, $sourceMovements->first()->qty_units);

        // Assert: Check target movements
        $targetMovements = \App\Models\InventoryMovement::where('product_id', $this->product->id)
            ->where('branch_id', $this->targetBranch->id)
            ->where('movement_type', 'TRANSFER_IN')
            ->get();
        
        $this->assertCount(1, $targetMovements);
        $this->assertEquals(15, $targetMovements->first()->qty_units);
    }

    /** @test */
    public function it_handles_multiple_consecutive_transfers()
    {
        // Arrange: Initial stock
        ProductBranch::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->sourceBranch->id,
            'current_stock' => 100,
        ]);

        // Act: Multiple transfers
        $this->inventoryService->transferProduct(
            $this->product->id,
            $this->sourceBranch->id,
            $this->targetBranch->id,
            20,
            'Transfer 1'
        );

        $this->inventoryService->transferProduct(
            $this->product->id,
            $this->sourceBranch->id,
            $this->targetBranch->id,
            15,
            'Transfer 2'
        );

        $this->inventoryService->transferProduct(
            $this->product->id,
            $this->sourceBranch->id,
            $this->targetBranch->id,
            10,
            'Transfer 3'
        );

        // Assert: Final stocks
        $sourceStock = $this->inventoryService->getCurrentStock(
            $this->product->id,
            $this->sourceBranch->id
        );
        $this->assertEquals(55, $sourceStock); // 100 - 20 - 15 - 10

        $targetStock = $this->inventoryService->getCurrentStock(
            $this->product->id,
            $this->targetBranch->id
        );
        $this->assertEquals(45, $targetStock); // 20 + 15 + 10
    }

    /** @test */
    public function it_can_transfer_back_to_original_branch()
    {
        // Arrange: Setup initial stocks
        ProductBranch::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->sourceBranch->id,
            'current_stock' => 50,
        ]);

        // Act: Transfer to target
        $this->inventoryService->transferProduct(
            $this->product->id,
            $this->sourceBranch->id,
            $this->targetBranch->id,
            30,
            'Transfer to target'
        );

        // Transfer back to source
        $this->inventoryService->transferProduct(
            $this->product->id,
            $this->targetBranch->id,
            $this->sourceBranch->id,
            10,
            'Transfer back to source'
        );

        // Assert: Final stocks
        $sourceStock = $this->inventoryService->getCurrentStock(
            $this->product->id,
            $this->sourceBranch->id
        );
        $this->assertEquals(30, $sourceStock); // 50 - 30 + 10

        $targetStock = $this->inventoryService->getCurrentStock(
            $this->product->id,
            $this->targetBranch->id
        );
        $this->assertEquals(20, $targetStock); // 30 - 10
    }

    /** @test */
    public function it_handles_pack_products_in_transfer()
    {
        // Arrange: Create product with pack size
        $packProduct = Product::create([
            'sku' => 'PACK-001',
            'name' => 'Pack Product',
            'category_id' => $this->category->id,
            'unit' => 'pack',
            'pieces_per_pack' => 12,
            'purchase_price' => 120,
            'sale_price' => 180,
            'reorder_level' => 5,
        ]);

        ProductBranch::create([
            'product_id' => $packProduct->id,
            'branch_id' => $this->sourceBranch->id,
            'current_stock' => 10, // 10 packs
        ]);

        // Act: Transfer 2 packs (24 pieces)
        $result = $this->inventoryService->transferProduct(
            $packProduct->id,
            $this->sourceBranch->id,
            $this->targetBranch->id,
            2,
            'Pack transfer test'
        );

        // Assert
        $this->assertEquals(2, $result['out']->qty_units);
        
        $sourceStock = $this->inventoryService->getCurrentStock(
            $packProduct->id,
            $this->sourceBranch->id
        );
        $this->assertEquals(8, $sourceStock); // 10 - 2

        $targetStock = $this->inventoryService->getCurrentStock(
            $packProduct->id,
            $this->targetBranch->id
        );
        $this->assertEquals(2, $targetStock);
    }
}
