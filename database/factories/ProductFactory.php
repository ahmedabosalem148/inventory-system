<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $classification = $this->faker->randomElement(Product::CLASSIFICATIONS);
        
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'category_id' => Category::factory(),
            'brand' => $this->faker->company(),
            'product_classification' => $classification,
            'sku' => null, // Will be auto-generated
            'purchase_price' => $this->faker->randomFloat(2, 50, 500),
            'sale_price' => $this->faker->randomFloat(2, 100, 1000),
            'unit' => $this->faker->randomElement(['قطعة', 'كجم', 'لتر', 'متر']),
            'pack_size' => in_array($classification, ['parts', 'plastic_parts', 'aluminum_parts']) 
                ? $this->faker->numberBetween(10, 100) 
                : null,
            'reorder_level' => $this->faker->numberBetween(10, 50),
        ];
    }
}
