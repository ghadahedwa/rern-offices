<?php

namespace Database\Factories;

use App\Models\FeedbackSuggestion;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackSuggestion> */
class FeedbackSuggestionFactory extends Factory
{
    protected $model = FeedbackSuggestion::class;

    public function definition(): array
    {
        return [
            'office_id'        => Office::factory()->public(),
            'governorate_id'   => fn (array $attrs) => Office::find($attrs['office_id'])?->governorate_id,
            'name'             => 'مواطن '.fake()->numberBetween(1, 9999),
            'national_id'      => '29001010101234',
            'phone'            => '01012345678',
            'other_suggestion' => 'اقتراح سابق',
        ];
    }
}
