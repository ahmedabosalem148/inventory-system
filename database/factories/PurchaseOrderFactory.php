<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 1000, 10000);
        $discountAmount = $this->faker->randomFloat(2, 0, $subtotal * 0.1);
        $taxAmount = ($subtotal - $discountAmount) * 0.15; // 15% VAT
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        return [
            'order_number' => $this->faker->unique()->numerify('PO-####'),
            'supplier_id' => Supplier::factory(),
            'branch_id' => Branch::factory(),
            'order_date' => $this->faker->date(),
            'expected_delivery_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'actual_delivery_date' => null,
            'subtotal' => $subtotal,
            'discount_type' => 'FIXED',
            'discount_value' => $discountAmount,
            'discount_amount' => $discountAmount,
            'tax_percentage' => 15,
            'tax_amount' => $taxAmount,
            'shipping_cost' => 0,
            'total_amount' => $totalAmount,
            'status' => 'DRAFT',
            'receiving_status' => 'NOT_RECEIVED',
            'payment_status' => 'UNPAID',
            'notes' => $this->faker->optional()->sentence(),
            'cancellation_reason' => null,
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'APPROVED',
            'approved_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }
}
