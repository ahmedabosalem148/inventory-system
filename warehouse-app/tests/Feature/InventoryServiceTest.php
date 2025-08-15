<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use App\Models\Movement;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryService::class);
    }

    protected function seedInv($size, $cc, $lu, $min = 0)
    {
        $product = Product::create([
            'name' => 'منتج تجريبي',
            'carton_size' => $size,
            'active' => true
        ]);

        $warehouse = Warehouse::create([
            'name' => 'مخزن تجريبي',
            'location' => 'موقع تجريبي'
        ]);

        $inv = WarehouseInventory::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'closed_cartons' => $cc,
            'loose_units' => $lu,
            'min_threshold' => $min
        ]);

        return [$product, $warehouse, $inv];
    }

    public function test_T1_withdraws_2_units_from_size_4_CC_10_LU_2()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 10, 2);

        $this->service->withdraw($warehouse->id, $product->id, 2);

        $inv->refresh();
        $this->assertEquals(10, $inv->closed_cartons);
        $this->assertEquals(0, $inv->loose_units);
    }

    public function test_T2_withdraws_3_units_from_4_10_1()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 10, 1);

        $this->service->withdraw($warehouse->id, $product->id, 3);

        $inv->refresh();
        $this->assertEquals(9, $inv->closed_cartons);
        $this->assertEquals(2, $inv->loose_units);
    }

    public function test_T3_withdraws_9_units_from_4_2_1()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 2, 1);

        $this->service->withdraw($warehouse->id, $product->id, 9);

        $inv->refresh();
        $this->assertEquals(0, $inv->closed_cartons);
        $this->assertEquals(0, $inv->loose_units);
    }

    public function test_T4_withdraws_3_units_from_4_1_0()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 1, 0);

        $this->service->withdraw($warehouse->id, $product->id, 3);

        $inv->refresh();
        $this->assertEquals(0, $inv->closed_cartons);
        $this->assertEquals(1, $inv->loose_units);
    }

    public function test_T5_throws_exception_when_withdrawing_from_empty_inventory()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 0, 0);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('الكمية المطلوبة أكبر من المتاح. المتاح = 0');

        $this->service->withdraw($warehouse->id, $product->id, 1);
    }

    public function test_T6_adds_1_unit_to_4_10_3_and_creates_movement()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 10, 3);

        $this->service->add($warehouse->id, $product->id, 1);

        $inv->refresh();
        $this->assertEquals(11, $inv->closed_cartons);
        $this->assertEquals(0, $inv->loose_units);

        $movement = Movement::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals('in', $movement->type);
        $this->assertEquals(1, $movement->quantity_units);
    }

    public function test_T7_adds_9_units_to_4_0_0()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 0, 0);

        $this->service->add($warehouse->id, $product->id, 9);

        $inv->refresh();
        $this->assertEquals(2, $inv->closed_cartons);
        $this->assertEquals(1, $inv->loose_units);
    }

    public function test_T8_adds_13_units_to_size_6_CC_0_LU_5()
    {
        [$product, $warehouse, $inv] = $this->seedInv(6, 0, 5);

        $this->service->add($warehouse->id, $product->id, 13);

        $inv->refresh();
        $this->assertEquals(3, $inv->closed_cartons);
        $this->assertEquals(0, $inv->loose_units);
    }

    public function test_T9_belowMin_is_true_when_total_less_than_min()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 2, 1, 12);

        $total = ($inv->closed_cartons * $product->carton_size) + $inv->loose_units;
        $belowMin = $total < $inv->min_threshold;

        $this->assertEquals(9, $total);
        $this->assertTrue($belowMin);
    }

    public function test_T10_belowMin_is_false_when_total_equals_min()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 2, 1, 9);

        $total = ($inv->closed_cartons * $product->carton_size) + $inv->loose_units;
        $belowMin = $total < $inv->min_threshold;

        $this->assertEquals(9, $total);
        $this->assertFalse($belowMin);
    }

    public function test_T11_throws_exception_when_adding_quantity_less_than_or_equal_to_0()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 10, 0);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->add($warehouse->id, $product->id, 0);
    }

    public function test_T11_throws_exception_when_adding_negative_quantity()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 10, 0);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->add($warehouse->id, $product->id, -5);
    }

    public function test_T12_throws_exception_when_withdrawing_quantity_less_than_or_equal_to_0()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 10, 2);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->withdraw($warehouse->id, $product->id, 0);
    }

    public function test_T12_throws_exception_when_withdrawing_negative_quantity()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 10, 2);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->withdraw($warehouse->id, $product->id, -3);
    }

    public function test_creates_movement_record_for_withdrawal()
    {
        [$product, $warehouse, $inv] = $this->seedInv(4, 10, 2);

        $this->service->withdraw($warehouse->id, $product->id, 2);

        $movement = Movement::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->where('type', 'out')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(2, $movement->quantity_units);
    }
}
