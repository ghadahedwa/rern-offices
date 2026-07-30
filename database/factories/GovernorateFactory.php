<?php

namespace Database\Factories;

use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Governorate> */
class GovernorateFactory extends Factory
{
    protected $model = Governorate::class;

    public function definition(): array
    {
        return [
            'name'  => 'محافظة '.fake()->unique()->numberBetween(1, 9999),
            'order' => 0,
        ];
    }
}
