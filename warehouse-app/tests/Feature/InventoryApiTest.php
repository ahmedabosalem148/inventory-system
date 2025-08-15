<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseInventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected Warehouse $w1;
    protected Warehouse $w2;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->w1 = Warehouse::create([
            'name' => 'مخزن 1',
            'location' => 'موقع 1'
        ]);

        $this->w2 = Warehouse::create([
            'name' => 'مخزن 2',
            'location' => 'موقع 2'
        ]);

        $this->product = Product::create([
            'name_ar' => 'منتج تجريبي',
            'carton_size' => 4,
            'active' => true
        ]);

        WarehouseInventory::create([
            'warehouse_id' => $this->w1->id,
            'product_id' => $this->product->id,
            'closed_cartons' => 0,
            'loose_units' => 0,
            'min_threshold' => 0
        ]);

        WarehouseInventory::create([
            'warehouse_id' => $this->w2->id,
            'product_id' => $this->product->id,
            'closed_cartons' => 0,
            'loose_units' => 0,
            'min_threshold' => 0
        ]);
    }

    public function test_displays_warehouse_inventory_with_correct_calculations()
    {
        // Set up inventory data
        WarehouseInventory::where('warehouse_id', $this->w1->id)
            ->where('product_id', $this->product->id)
            ->update([
                'closed_cartons' => 2,
                'loose_units' => 1,
                'min_threshold' => 5
            ]);

        $response = $this->getJson("/api/warehouses/{$this->w1->id}/inventory");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data);
        
        $item = $data[0];
        $this->assertEquals(2, $item['closed_cartons']);
        $this->assertEquals(1, $item['loose_units']);
        $this->assertEquals(9, $item['totalUnits']); // 2*4 + 1
        $this->assertEquals(5, $item['min_threshold']);
        $this->assertFalse($item['belowMin']); // 9 > 5, so should be false
    }

    public function test_shows_belowMin_true_when_total_less_than_min()
    {
        WarehouseInventory::where('warehouse_id', $this->w1->id)
            ->where('product_id', $this->product->id)
            ->update([
                'closed_cartons' => 1,
                'loose_units' => 0,
                'min_threshold' => 10
            ]);

        $response = $this->getJson("/api/warehouses/{$this->w1->id}/inventory");

        $response->assertStatus(200);
        $data = $response->json();
        $item = $data[0];
        $this->assertEquals(4, $item['totalUnits']); // 1*4 + 0
        $this->assertEquals(10, $item['min_threshold']);
        $this->assertTrue($item['belowMin']); // 4 < 10
    }

    public function test_rejects_withdrawal_with_amount_greater_than_available()
    {
        // Empty inventory
        WarehouseInventory::where('warehouse_id', $this->w1->id)
            ->where('product_id', $this->product->id)
            ->update([
                'closed_cartons' => 0,
                'loose_units' => 0,
                'min_threshold' => 0
            ]);

        $response = $this->postJson('/api/inventory/withdraw', [
            'warehouse_id' => $this->w1->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_type' => 'units'
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'الكمية المطلوبة أكبر من المتاح. المتاح = 0'
        ]);
    }

    public function test_handles_complex_scenario_add_withdraw_set_min_check_belowMin()
    {
        // Step 1: Add 9 units (should result in CC=2, LU=1, total=9)
        $response = $this->postJson('/api/inventory/add', [
            'warehouse_id' => $this->w1->id,
            'product_id' => $this->product->id,
            'quantity' => 9,
            'unit_type' => 'units'
        ]);
        $response->assertStatus(200);

        // Verify after addition
        $checkResponse = $this->getJson("/api/warehouses/{$this->w1->id}/inventory");
        $data = $checkResponse->json();
        $item = $data[0];
        $this->assertEquals(2, $item['closed_cartons']);
        $this->assertEquals(1, $item['loose_units']);
        $this->assertEquals(9, $item['totalUnits']);

        // Step 2: Withdraw 3 units (should result in CC=1, LU=2, total=6)
        $response = $this->postJson('/api/inventory/withdraw', [
            'warehouse_id' => $this->w1->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'unit_type' => 'units'
        ]);
        $response->assertStatus(200);

        // Verify after withdrawal
        $checkResponse = $this->getJson("/api/warehouses/{$this->w1->id}/inventory");
        $data = $checkResponse->json();
        $item = $data[0];
        $this->assertEquals(1, $item['closed_cartons']);
        $this->assertEquals(2, $item['loose_units']);
        $this->assertEquals(6, $item['totalUnits']);

        // Step 3: Set min=7 (should make belowMin=true since 6 < 7)
        $response = $this->patchJson('/api/inventory/set-min', [
            'warehouse_id' => $this->w1->id,
            'product_id' => $this->product->id,
            'min_threshold' => 7
        ]);
        $response->assertStatus(200);

        // Step 4: Check that belowMin is now true
        $checkResponse = $this->getJson("/api/warehouses/{$this->w1->id}/inventory");
        $data = $checkResponse->json();
        $item = $data[0];
        $this->assertEquals(6, $item['totalUnits']);
        $this->assertEquals(7, $item['min_threshold']);
        $this->assertTrue($item['belowMin']);
    }

    public function test_handles_arabic_error_messages_for_validation()
    {
        // Test with negative quantity
        $response = $this->postJson('/api/inventory/add', [
            'warehouse_id' => $this->w1->id,
            'product_id' => $this->product->id,
            'quantity' => -5,
            'unit_type' => 'units'
        ]);
        $response->assertStatus(422);
        
        // Test with zero quantity
        $response = $this->postJson('/api/inventory/withdraw', [
            'warehouse_id' => $this->w1->id,
            'product_id' => $this->product->id,
            'quantity' => 0,
            'unit_type' => 'units'
        ]);
        $response->assertStatus(422);
    }

    public function test_handles_multiple_products_in_same_warehouse()
    {
        $product2 = Product::create([
            'name_ar' => 'منتج ثاني',
            'carton_size' => 6,
            'active' => true
        ]);

        WarehouseInventory::create([
            'warehouse_id' => $this->w1->id,
            'product_id' => $product2->id,
            'closed_cartons' => 3,
            'loose_units' => 2,
            'min_threshold' => 15
        ]);

        $response = $this->getJson("/api/warehouses/{$this->w1->id}/inventory");
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertCount(2, $data);
        
        // Find the second product in response
        $product2Item = collect($data)->firstWhere('product_id', $product2->id);
        $this->assertEquals(20, $product2Item['totalUnits']); // 3*6 + 2
        $this->assertFalse($product2Item['belowMin']); // 20 >= 15
    }
}
