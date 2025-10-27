<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'شركة التوريدات المتحدة',
                'مؤسسة الأمل التجارية',
                'شركة النجاح للتجارة',
                'مؤسسة الرواد'
            ]),
            'contact_name' => $this->faker->name(),
            'phone' => $this->faker->numerify('05########'),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'tax_number' => $this->faker->numerify('3##########'),
            'payment_terms' => $this->faker->randomElement(['CASH', 'NET_7', 'NET_15', 'NET_30', 'NET_60']),
            'credit_limit' => $this->faker->randomFloat(2, 10000, 100000),
            'current_balance' => 0,
            'status' => 'ACTIVE',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
