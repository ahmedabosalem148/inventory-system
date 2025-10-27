<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Rules\SufficientStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SufficientStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_when_quantity_exceeds_stock(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();
        
        ProductBranch::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'current_stock' => 10,
        ]);

        // Act
        $rule = new SufficientStock($product->id, $branch->id);
        $fails = false;
        
        $rule->validate('quantity', 15, function($message) use (&$fails) {
            $fails = true;
        });

        // Assert
        $this->assertTrue($fails, 'Validation should fail when quantity exceeds stock');
    }

    public function test_passes_when_quantity_within_stock(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();
        
        ProductBranch::create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'current_stock' => 10,
        ]);

        // Act
        $rule = new SufficientStock($product->id, $branch->id);
        $fails = false;
        
        $rule->validate('quantity', 5, function($message) use (&$fails) {
            $fails = true;
        });

        // Assert
        $this->assertFalse($fails, 'Validation should pass when quantity is within stock');
    }

    public function test_fails_when_no_stock_record_exists(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $branch = Branch::factory()->create();

        // Act
        $rule = new SufficientStock($product->id, $branch->id);
        $fails = false;
        
        $rule->validate('quantity', 1, function($message) use (&$fails) {
            $fails = true;
        });

        // Assert
        $this->assertTrue($fails, 'Validation should fail when no stock record exists');
    }
}
