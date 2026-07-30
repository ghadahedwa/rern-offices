<?php

namespace Database\Factories;

use App\Models\OfficeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OfficeType> */
class OfficeTypeFactory extends Factory
{
    protected $model = OfficeType::class;

    public function definition(): array
    {
        return [
            'name'      => 'نوع '.fake()->unique()->numberBetween(1, 9999),
            'is_public' => false,
        ];
    }

    /** نوع ظاهر للمواطن في بوابة رأي المواطن. */
    public function public(): static
    {
        return $this->state(['is_public' => true]);
    }
}
