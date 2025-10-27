<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\ReturnVoucher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnVoucherFactory extends Factory
{
    protected $model = ReturnVoucher::class;

    public function definition(): array
    {
        $totalAmount = $this->faker->randomFloat(2, 100, 1000);

        return [
            'voucher_number' => $this->faker->unique()->numerify('RET-####'),
            'customer_id' => Customer::factory(),
            'customer_name' => null,
            'branch_id' => Branch::factory(),
            'return_date' => $this->faker->date(),
            'total_amount' => $totalAmount,
            'status' => 'PENDING',
            'notes' => $this->faker->optional()->sentence(),
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
