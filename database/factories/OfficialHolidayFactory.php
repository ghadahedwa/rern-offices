<?php

namespace Database\Factories;

use App\Models\OfficialHoliday;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficialHolidayFactory extends Factory
{
    protected $model = OfficialHoliday::class;

    public function definition(): array
    {
        return [
            'name'       => 'عطلة رسمية',
            'starts_on'  => '2026-09-17',
            'ends_on'    => '2026-09-17',
        ];
    }
}
