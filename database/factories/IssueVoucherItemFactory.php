<?php

namespace Database\Factories;

use App\Models\IssueVoucher;
use App\Models\IssueVoucherItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssueVoucherItemFactory extends Factory
{
    protected $model = IssueVoucherItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitPrice = $this->faker->randomFloat(2, 10, 100);
        $totalPrice = $quantity * $unitPrice;
        $discountAmount = $this->faker->randomFloat(2, 0, $totalPrice * 0.1);
        $netPrice = $totalPrice - $discountAmount;

        return [
            'issue_voucher_id' => IssueVoucher::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'discount_type' => 'fixed',
            'discount_value' => $discountAmount,
            'discount_amount' => $discountAmount,
            'net_price' => $netPrice,
        ];
    }
}
