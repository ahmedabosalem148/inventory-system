<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    public function definition(): array
    {
        $quantityOrdered = $this->faker->numberBetween(10, 100);
        $unitPrice = $this->faker->randomFloat(2, 10, 100);
        $subtotal = $quantityOrdered * $unitPrice;
        $discountAmount = $this->faker->randomFloat(2, 0, $subtotal * 0.1);
        $total = $subtotal - $discountAmount;

        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'quantity_ordered' => $quantityOrdered,
            'quantity_received' => 0,
            'unit_price' => $unitPrice,
            'discount_type' => 'fixed',
            'discount_value' => $discountAmount,
            'discount_amount' => $discountAmount,
            'subtotal' => $subtotal,
            'total' => $total,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
