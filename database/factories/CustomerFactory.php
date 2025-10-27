<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('C####'),
            'name' => $this->faker->randomElement([
                'محمد أحمد',
                'علي حسن',
                'فاطمة محمود',
                'عبدالله سالم',
                'نورة خالد',
                'سارة عبدالرحمن',
                'خالد عمر'
            ]),
            'type' => $this->faker->randomElement(['retail', 'wholesale']),
            'phone' => $this->faker->numerify('05########'),
            'address' => $this->faker->randomElement([
                'الرياض - حي النرجس',
                'جدة - حي الصفا',
                'الدمام - حي الفيصلية',
                'مكة - حي العزيزية'
            ]),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'is_active' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
