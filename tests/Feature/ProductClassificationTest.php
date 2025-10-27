<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductClassificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Branch $branch;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary data
        $this->branch = Branch::factory()->create(['name' => 'Main Branch']);
        $this->category = Category::factory()->create(['name' => 'Electronics']);

        // Create super-admin user
        $role = Role::create(['name' => 'super-admin']);
        $this->user = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
        ]);
        $this->user->assignRole($role);

        // Authenticate
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function product_requires_classification()
    {
        $response = $this->postJson('/api/v1/products', [
            'name' => 'Test Product',
            'category_id' => $this->category->id,
            'brand' => 'Test Brand',
            'purchase_price' => 100,
            'sale_price' => 150,
            'unit' => 'قطعة',
            // product_classification missing
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_classification']);
    }

    /** @test */
    public function product_classification_must_be_valid()
    {
        $response = $this->postJson('/api/v1/products', [
            'name' => 'Test Product',
            'category_id' => $this->category->id,
            'brand' => 'Test Brand',
            'product_classification' => 'invalid_classification',
            'purchase_price' => 100,
            'sale_price' => 150,
            'unit' => 'قطعة',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_classification']);
    }

    /** @test */
    public function sku_is_auto_generated_with_correct_prefix()
    {
        $testCases = [
            ['classification' => 'finished_product', 'prefix' => 'FIN'],
            ['classification' => 'semi_finished', 'prefix' => 'SEM'],
            ['classification' => 'raw_material', 'prefix' => 'RAW'],
            ['classification' => 'parts', 'prefix' => 'PRT'],
            ['classification' => 'plastic_parts', 'prefix' => 'PLS'],
            ['classification' => 'aluminum_parts', 'prefix' => 'ALU'],
            ['classification' => 'other', 'prefix' => 'OTH'],
        ];

        foreach ($testCases as $case) {
            $productName = "Test {$case['classification']}";
            
            $response = $this->postJson('/api/v1/products', [
                'name' => $productName,
                'category_id' => $this->category->id,
                'brand' => 'Test Brand',
                'product_classification' => $case['classification'],
                'purchase_price' => 100,
                'sale_price' => 150,
                'unit' => 'قطعة',
                'pack_size' => in_array($case['classification'], ['parts', 'plastic_parts', 'aluminum_parts']) ? 10 : null,
            ]);

            $response->assertStatus(201);

            $product = Product::where('name', $productName)->first();
            $this->assertStringStartsWith($case['prefix'] . '-', $product->sku);
            $this->assertEquals($case['classification'], $product->product_classification);
        }
    }

    /** @test */
    public function sku_numbers_increment_correctly()
    {
        // Create first product
        $response1 = $this->postJson('/api/v1/products', [
            'name' => 'First Product',
            'category_id' => $this->category->id,
            'brand' => 'Test Brand',
            'product_classification' => 'finished_product',
            'purchase_price' => 100,
            'sale_price' => 150,
            'unit' => 'قطعة',
        ]);

        $response1->assertStatus(201);
        $first = Product::where('name', 'First Product')->first();
        $this->assertEquals('FIN-000001', $first->sku);

        // Create second product with same classification
        $response2 = $this->postJson('/api/v1/products', [
            'name' => 'Second Product',
            'category_id' => $this->category->id,
            'brand' => 'Test Brand',
            'product_classification' => 'finished_product',
            'purchase_price' => 100,
            'sale_price' => 150,
            'unit' => 'قطعة',
        ]);

        $response2->assertStatus(201);
        $second = Product::where('name', 'Second Product')->first();
        $this->assertEquals('FIN-000002', $second->sku);
    }

    /** @test */
    public function pack_size_required_for_parts()
    {
        $classificationsRequiringPackSize = ['parts', 'plastic_parts', 'aluminum_parts'];

        foreach ($classificationsRequiringPackSize as $classification) {
            $response = $this->postJson('/api/v1/products', [
                'name' => "Test {$classification}",
                'category_id' => $this->category->id,
                'brand' => 'Test Brand',
                'product_classification' => $classification,
                'purchase_price' => 100,
                'sale_price' => 150,
                'unit' => 'قطعة',
                // pack_size missing
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['pack_size']);
        }
    }

    /** @test */
    public function pack_size_not_required_for_other_classifications()
    {
        $classificationsNotRequiringPackSize = ['finished_product', 'semi_finished', 'raw_material', 'other'];

        foreach ($classificationsNotRequiringPackSize as $classification) {
            $response = $this->postJson('/api/v1/products', [
                'name' => "Test {$classification}",
                'category_id' => $this->category->id,
                'brand' => 'Test Brand',
                'product_classification' => $classification,
                'purchase_price' => 100,
                'sale_price' => 150,
                'unit' => 'قطعة',
                // pack_size not provided
            ]);

            $response->assertStatus(201);
        }
    }

    /** @test */
    public function sale_price_must_be_greater_than_purchase_price_for_finished_product()
    {
        $response = $this->postJson('/api/v1/products', [
            'name' => 'Finished Product',
            'category_id' => $this->category->id,
            'brand' => 'Test Brand',
            'product_classification' => 'finished_product',
            'purchase_price' => 200,
            'sale_price' => 150, // Less than purchase price
            'unit' => 'قطعة',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sale_price']);
    }

    /** @test */
    public function sale_price_validation_not_applied_for_non_finished_products()
    {
        $response = $this->postJson('/api/v1/products', [
            'name' => 'Raw Material',
            'category_id' => $this->category->id,
            'brand' => 'Test Brand',
            'product_classification' => 'raw_material',
            'purchase_price' => 200,
            'sale_price' => 150, // Less than purchase price - should be allowed
            'unit' => 'كجم',
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function can_filter_products_by_classification()
    {
        // Create products with different classifications
        Product::factory()->create([
            'product_classification' => 'finished_product',
            'sku' => 'FIN-000001',
        ]);
        Product::factory()->create([
            'product_classification' => 'parts',
            'sku' => 'PRT-000001',
            'pack_size' => 10,
        ]);
        Product::factory()->create([
            'product_classification' => 'raw_material',
            'sku' => 'RAW-000001',
        ]);

        // Filter by finished_product
        $response = $this->getJson('/api/v1/products?product_classification=finished_product');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('finished_product', $data[0]['product_classification']);

        // Filter by parts
        $response = $this->getJson('/api/v1/products?product_classification=parts');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('parts', $data[0]['product_classification']);
    }

    /** @test */
    public function sku_cannot_be_updated()
    {
        $product = Product::factory()->create([
            'product_classification' => 'finished_product',
            'sku' => 'FIN-000001',
        ]);

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'name' => 'Updated Product',
            'category_id' => $this->category->id,
            'product_classification' => 'finished_product',
            'purchase_price' => 100,
            'sale_price' => 150,
            'unit' => 'قطعة',
            'sku' => 'CHANGED-SKU', // Attempting to change SKU
        ]);

        $response->assertStatus(200);
        
        $product->refresh();
        $this->assertEquals('FIN-000001', $product->sku); // SKU should remain unchanged
    }

    /** @test */
    public function product_model_scopes_work_correctly()
    {
        Product::factory()->create(['product_classification' => 'finished_product', 'sku' => 'FIN-001']);
        Product::factory()->create(['product_classification' => 'parts', 'sku' => 'PRT-001', 'pack_size' => 10]);
        Product::factory()->create(['product_classification' => 'plastic_parts', 'sku' => 'PLS-001', 'pack_size' => 20]);
        Product::factory()->create(['product_classification' => 'aluminum_parts', 'sku' => 'ALU-001', 'pack_size' => 30]);

        // Test byClassification scope
        $finishedProducts = Product::byClassification('finished_product')->get();
        $this->assertCount(1, $finishedProducts);

        // Test factoryParts scope
        $factoryParts = Product::factoryParts()->get();
        $this->assertCount(3, $factoryParts); // parts + plastic_parts + aluminum_parts
    }

    /** @test */
    public function product_classification_label_accessor_returns_arabic()
    {
        $product = Product::factory()->create([
            'product_classification' => 'finished_product',
            'sku' => 'FIN-001',
        ]);

        $this->assertEquals('منتج تام', $product->classification_label);
    }

    /** @test */
    public function requires_pack_size_helper_works_correctly()
    {
        $parts = Product::factory()->create([
            'product_classification' => 'parts',
            'sku' => 'PRT-001',
            'pack_size' => 10,
        ]);
        $this->assertTrue($parts->requiresPackSize());

        $finished = Product::factory()->create([
            'product_classification' => 'finished_product',
            'sku' => 'FIN-001',
        ]);
        $this->assertFalse($finished->requiresPackSize());
    }
}
