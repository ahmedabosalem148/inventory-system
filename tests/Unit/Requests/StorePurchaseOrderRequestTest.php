<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StorePurchaseOrderRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_allows_authenticated_users()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $request = new StorePurchaseOrderRequest();
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $request = new StorePurchaseOrderRequest();
        
        $validator = Validator::make([], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('supplier_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('branch_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('order_date', $validator->errors()->toArray());
        $this->assertArrayHasKey('items', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_supplier_exists()
    {
        $request = new StorePurchaseOrderRequest();
        
        $validator = Validator::make([
            'supplier_id' => 99999, // Non-existent
            'branch_id' => 1,
            'order_date' => now()->toDateString(),
            'items' => [['product_id' => 1, 'quantity_ordered' => 10, 'unit_price' => 100]],
        ], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('supplier_id', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_discount_type_enum()
    {
        $request = new StorePurchaseOrderRequest();
        
        $validator = Validator::make([
            'supplier_id' => 1,
            'branch_id' => 1,
            'order_date' => now()->toDateString(),
            'discount_type' => 'INVALID_TYPE',
            'items' => [['product_id' => 1, 'quantity_ordered' => 10, 'unit_price' => 100]],
        ], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('discount_type', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_discount_value_is_numeric()
    {
        $request = new StorePurchaseOrderRequest();
        
        // Non-numeric discount value
        $validator = Validator::make([
            'supplier_id' => 1,
            'branch_id' => 1,
            'order_date' => now()->toDateString(),
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 'invalid',
            'items' => [['product_id' => 1, 'quantity_ordered' => 10, 'unit_price' => 100]],
        ], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('discount_value', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_items_array_structure()
    {
        $request = new StorePurchaseOrderRequest();
        
        // Missing required item fields
        $validator = Validator::make([
            'supplier_id' => 1,
            'branch_id' => 1,
            'order_date' => now()->toDateString(),
            'items' => [
                ['product_id' => 1], // Missing quantity_ordered and unit_price
            ],
        ], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.quantity_ordered', $validator->errors()->toArray());
        $this->assertArrayHasKey('items.0.unit_price', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_nested_item_discount_is_numeric()
    {
        $request = new StorePurchaseOrderRequest();
        
        $validator = Validator::make([
            'supplier_id' => 1,
            'branch_id' => 1,
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'product_id' => 1,
                    'quantity_ordered' => 10,
                    'unit_price' => 100,
                    'discount_type' => 'PERCENTAGE',
                    'discount_value' => 'invalid', // Non-numeric
                ],
            ],
        ], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.discount_value', $validator->errors()->toArray());
    }

    /** @test */
    public function it_accepts_valid_purchase_order_data()
    {
        // Create required related models
        $supplier = \App\Models\Supplier::factory()->create();
        $branch = \App\Models\Branch::factory()->create();
        $product = \App\Models\Product::factory()->create();
        
        $request = new StorePurchaseOrderRequest();
        
        $validator = Validator::make([
            'supplier_id' => $supplier->id,
            'branch_id' => $branch->id,
            'order_date' => now()->toDateString(),
            'expected_delivery_date' => now()->addDays(7)->toDateString(),
            'discount_type' => 'PERCENTAGE',
            'discount_value' => 10,
            'tax_percentage' => 14,
            'shipping_cost' => 50,
            'notes' => 'Test order',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity_ordered' => 10,
                    'unit_price' => 100,
                    'discount_type' => 'FIXED',
                    'discount_value' => 50,
                ],
            ],
        ], $request->rules());
        
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_has_arabic_error_messages()
    {
        $request = new StorePurchaseOrderRequest();
        $messages = $request->messages();
        
        $this->assertArrayHasKey('supplier_id.required', $messages);
        $this->assertArrayHasKey('branch_id.required', $messages);
        $this->assertArrayHasKey('items.required', $messages);
        
        // Check messages are in Arabic
        $this->assertStringContainsString('المورد', $messages['supplier_id.required']);
        $this->assertStringContainsString('الفرع', $messages['branch_id.required']);
    }
}
