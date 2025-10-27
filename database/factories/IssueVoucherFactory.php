<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\IssueVoucher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssueVoucherFactory extends Factory
{
    protected $model = IssueVoucher::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 1000);
        $discountAmount = $this->faker->randomFloat(2, 0, $subtotal * 0.1);
        $netTotal = $subtotal - $discountAmount;

        return [
            'voucher_number' => $this->faker->unique()->numerify('ISS-####'),
            'customer_id' => Customer::factory(),
            'customer_name' => null,
            'branch_id' => Branch::factory(),
            'issue_date' => $this->faker->date(),
            'notes' => $this->faker->optional()->sentence(),
            'total_amount' => $subtotal,
            'discount_type' => 'fixed',
            'discount_value' => $discountAmount,
            'discount_amount' => $discountAmount,
            'subtotal' => $subtotal,
            'net_total' => $netTotal,
            'status' => 'PENDING',
            'created_by' => User::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'APPROVED',
        ]);
    }
}
