<?php

namespace Database\Factories;

use App\Models\FeedbackMessage;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackMessage> */
class FeedbackMessageFactory extends Factory
{
    protected $model = FeedbackMessage::class;

    public function definition(): array
    {
        return [
            'type'           => FeedbackMessage::TYPE_THANKS,
            'office_id'      => Office::factory()->public(),
            'governorate_id' => fn (array $attrs) => Office::find($attrs['office_id'])?->governorate_id,
            'name'           => 'مواطن '.fake()->numberBetween(1, 9999),
            'national_id'    => '29001010101234',
            'phone'          => '01012345678',
            'employee_name'  => 'أحمد محمود',
            'body'           => 'رسالة شكر سابقة',
        ];
    }
}
