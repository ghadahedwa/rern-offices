<?php

namespace Database\Factories;

use App\Models\DataEntryOperator;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataEntryOperatorFactory extends Factory
{
    protected $model = DataEntryOperator::class;

    public function definition(): array
    {
        return [
            'name'  => 'مدخل '.fake()->unique()->numberBetween(1, 99999),
            'phone' => '010'.fake()->numerify('########'),
        ];
    }
}
