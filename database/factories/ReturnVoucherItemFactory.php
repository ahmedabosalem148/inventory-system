<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ReturnVoucher;
use App\Models\ReturnVoucherItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnVoucherItemFactory extends Factory
{
    protected $model = ReturnVoucherItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 10, 100);
        $totalPrice = $quantity * $unitPrice;

        return [
            'return_voucher_id' => ReturnVoucher::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
        ];
    }
}
