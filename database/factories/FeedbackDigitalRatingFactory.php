<?php

namespace Database\Factories;

use App\Models\FeedbackDigitalRating;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackDigitalRating> */
class FeedbackDigitalRatingFactory extends Factory
{
    protected $model = FeedbackDigitalRating::class;

    /** مسار «بدون حجز · لا يعرف المنصة · لم يشاهد الإعلان» — أقصر مسار صالح. */
    public function definition(): array
    {
        return [
            'office_id'      => Office::factory()->public(),
            'governorate_id' => fn (array $attrs) => Office::find($attrs['office_id'])?->governorate_id,
            'q201'           => 'walk_in',
            'q208'           => 'no',
            'q211'           => 'no',
        ];
    }
}
