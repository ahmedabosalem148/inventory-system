<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerLedgerEntry>
 */
class CustomerLedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isDebit = fake()->boolean();
        
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'entry_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'description' => $isDebit 
                ? 'فاتورة رقم ' . fake()->numberBetween(1000, 9999)
                : fake()->randomElement([
                    'دفعة نقدية',
                    'تحويل بنكي',
                    'ارتجاع بضاعة',
                    'شيك رقم ' . fake()->numberBetween(10000, 99999),
                ]),
            'debit_aliah' => $isDebit ? fake()->randomFloat(2, 100, 10000) : 0,
            'credit_lah' => !$isDebit ? fake()->randomFloat(2, 100, 5000) : 0,
            'ref_table' => $isDebit ? 'issue_vouchers' : 'payments',
            'ref_id' => fake()->numberBetween(1, 100),
            'notes' => fake()->optional()->sentence(),
            'created_by' => 1, // Default admin user
        ];
    }

    /**
     * State: قيد علية (مديونية)
     */
    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'فاتورة رقم ' . fake()->numberBetween(1000, 9999),
            'debit_aliah' => fake()->randomFloat(2, 100, 10000),
            'credit_lah' => 0,
            'ref_table' => 'issue_vouchers',
        ]);
    }

    /**
     * State: قيد له (دائنية)
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->randomElement([
                'دفعة نقدية',
                'تحويل بنكي',
                'شيك رقم ' . fake()->numberBetween(10000, 99999),
            ]),
            'debit_aliah' => 0,
            'credit_lah' => fake()->randomFloat(2, 100, 5000),
            'ref_table' => 'payments',
        ]);
    }

    /**
     * State: ارتجاع بضاعة
     */
    public function returnEntry(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => 'ارتجاع رقم ' . fake()->numberBetween(100001, 125000),
            'debit_aliah' => 0,
            'credit_lah' => fake()->randomFloat(2, 50, 2000),
            'ref_table' => 'return_vouchers',
        ]);
    }
}
