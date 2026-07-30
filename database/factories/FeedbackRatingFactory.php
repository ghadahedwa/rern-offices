<?php

namespace Database\Factories;

use App\Models\FeedbackRating;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackRating> */
class FeedbackRatingFactory extends Factory
{
    protected $model = FeedbackRating::class;

    public function definition(): array
    {
        $office = Office::factory()->public();

        return [
            'office_id'          => $office,
            'governorate_id'     => fn (array $attrs) => Office::find($attrs['office_id'])?->governorate_id,
            'name'               => 'مواطن '.fake()->numberBetween(1, 9999),
            'national_id'        => '29001010101234',
            'phone'              => '01012345678',
            'wait_time'          => 'under_15',
            'rating_speed'       => 4,
            'rating_staff'       => 4,
            'rating_queue'       => 4,
            'rating_cleanliness' => 4,
            'rating_clarity'     => 4,
            'overall_rating'     => 4,
        ];
    }
}
