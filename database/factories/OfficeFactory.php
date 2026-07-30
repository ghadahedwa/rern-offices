<?php

namespace Database\Factories;

use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Office> */
class OfficeFactory extends Factory
{
    protected $model = Office::class;

    public function definition(): array
    {
        return [
            'governorate_id' => Governorate::factory(),
            'type_id'        => OfficeType::factory(),
            'name'           => 'مقر '.fake()->unique()->numberBetween(1, 9999),
        ];
    }

    /** مقر من نوع ظاهر للمواطن (يمرّ من scopePublicFeedback). */
    public function public(): static
    {
        return $this->state(['type_id' => OfficeType::factory()->public()]);
    }
}
