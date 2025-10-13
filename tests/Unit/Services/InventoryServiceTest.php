<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Category;
use App\Models\ProductBranchStock;
use App\Models\InventoryMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $service;
    protected Product $product;
    protected Branch $branch;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();

        $this->category = Category::create([
            'name' => 'Electronics',
            'description' => 'Electronic items',
        ]);

        $this->branch = Branch::create([
            'code' => 'MAIN',
            'name' => 'Main Branch',
            'location' => 'Cairo',
        ]);

        $this->product = Product::create([
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'category_id' => $this->category->id,
            'unit' => 'piece',
            'purchase_price' => 100,
            'sale_price' => 150,
            'reorder_level' => 10,
        ]);
    }

    /** @test */
    public function it_prevents_negative_stock_on_issue()
    {
        ProductBranchStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 5,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->issueProduct(
            $this->product->id,
            $this->branch->id,
            10,
            'Over-issue test'
        );
    }

    /** @test */
    public function it_successfully_issues_product_with_sufficient_stock()
    {
        ProductBranchStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 20,
        ]);

        $movement = $this->service->issueProduct(
            $this->product->id,
            $this->branch->id,
            5,
            'Test issue'
        );

        $this->assertInstanceOf(InventoryMovement::class, $movement);
        $this->assertEquals('ISSUE', $movement->movement_type);
        $this->assertEquals(5, $movement->qty_units);

        $stock = ProductBranchStock::where('product_id', $this->product->id)
            ->where('branch_id', $this->branch->id)
            ->first();

        $this->assertEquals(15, $stock->current_stock);
    }

    /** @test */
    public function it_successfully_returns_product_and_increases_stock()
    {
        ProductBranchStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 10,
        ]);

        $movement = $this->service->returnProduct(
            $this->product->id,
            $this->branch->id,
            5,
            'Test return'
        );

        $this->assertEquals('RETURN', $movement->movement_type);
        $this->assertEquals(5, $movement->qty_units);

        $stock = ProductBranchStock::where('product_id', $this->product->id)
            ->where('branch_id', $this->branch->id)
            ->first();

        $this->assertEquals(15, $stock->current_stock);
    }

    /** @test */
    public function it_creates_product_branch_record_if_not_exists()
    {
        $this->assertEquals(0, ProductBranchStock::count());

        $this->service->returnProduct(
            $this->product->id,
            $this->branch->id,
            10,
            'Initial stock'
        );

        $this->assertEquals(1, ProductBranchStock::count());
        
        $stock = ProductBranchStock::first();
        $this->assertEquals(10, $stock->current_stock);
    }

    /** @test */
    public function it_transfers_between_branches()
    {
        $targetBranch = Branch::create([
            'code' => 'SEC',
            'name' => 'Secondary Branch',
            'location' => 'Alexandria',
        ]);

        ProductBranchStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 20,
        ]);

        $movements = $this->service->transferProduct(
            $this->product->id,
            $this->branch->id,
            $targetBranch->id,
            10,
            'Transfer test'
        );

        $this->assertCount(2, $movements);
        
        $outMovement = $movements['out'];
        $inMovement = $movements['in'];

        $this->assertEquals('TRANSFER_OUT', $outMovement->movement_type);
        $this->assertEquals('TRANSFER_IN', $inMovement->movement_type);
        $this->assertEquals(10, $outMovement->qty_units);
        $this->assertEquals(10, $inMovement->qty_units);

        $sourceStock = ProductBranchStock::where('product_id', $this->product->id)
            ->where('branch_id', $this->branch->id)
            ->first();

        $targetStock = ProductBranchStock::where('product_id', $this->product->id)
            ->where('branch_id', $targetBranch->id)
            ->first();

        $this->assertEquals(10, $sourceStock->current_stock);
        $this->assertEquals(10, $targetStock->current_stock);
    }

    /** @test */
    public function it_prevents_transfer_with_insufficient_stock()
    {
        $targetBranch = Branch::create([
            'code' => 'SEC',
            'name' => 'Secondary Branch',
            'location' => 'Alexandria',
        ]);

        ProductBranchStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 5,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->service->transferProduct(
            $this->product->id,
            $this->branch->id,
            $targetBranch->id,
            10,
            'Over-transfer'
        );
    }

    /** @test */
    public function it_calculates_running_balance_in_movements()
    {
        ProductBranchStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 100,
        ]);

        $this->service->returnProduct($this->product->id, $this->branch->id, 50, 'Return 1');
        $this->service->issueProduct($this->product->id, $this->branch->id, 20, 'Issue 1');
        $this->service->returnProduct($this->product->id, $this->branch->id, 30, 'Return 2');
        $this->service->issueProduct($this->product->id, $this->branch->id, 10, 'Issue 2');

        $finalStock = ProductBranchStock::where('product_id', $this->product->id)
            ->where('branch_id', $this->branch->id)
            ->first();

        $this->assertEquals(150, $finalStock->current_stock);

        $movements = InventoryMovement::where('product_id', $this->product->id)
            ->where('branch_id', $this->branch->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(4, $movements);
        $this->assertEquals(50, $movements[0]->qty_units);
        $this->assertEquals(20, $movements[1]->qty_units);
        $this->assertEquals(30, $movements[2]->qty_units);
        $this->assertEquals(10, $movements[3]->qty_units);
    }

    /** @test */
    public function it_gets_current_stock_for_product_in_branch()
    {
        ProductBranchStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 42,
        ]);

        $stock = $this->service->getCurrentStock($this->product->id, $this->branch->id);

        $this->assertEquals(42, $stock);
    }

    /** @test */
    public function it_returns_zero_if_no_stock_record_exists()
    {
        $stock = $this->service->getCurrentStock($this->product->id, $this->branch->id);

        $this->assertEquals(0, $stock);
    }

    /** @test */
    public function it_checks_if_stock_is_below_reorder_level()
    {
        ProductBranchStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'current_stock' => 5,
        ]);

        $isBelowReorder = $this->service->isBelowReorderLevel($this->product->id, $this->branch->id);

        $this->assertTrue($isBelowReorder);

        ProductBranchStock::where('product_id', $this->product->id)
            ->where('branch_id', $this->branch->id)
            ->update(['current_stock' => 20]);

        $isBelowReorder = $this->service->isBelowReorderLevel($this->product->id, $this->branch->id);
        $this->assertFalse($isBelowReorder);
    }
}
