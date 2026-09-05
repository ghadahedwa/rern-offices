<?php

namespace Database\Factories;

use App\Models\DataEntryAssignment;
use App\Models\DataEntryOperator;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataEntryAssignmentFactory extends Factory
{
    protected $model = DataEntryAssignment::class;

    public function definition(): array
    {
        return [
            'operator_id' => DataEntryOperator::factory(),
            'office_id'   => Office::factory(),
            'started_on'  => '2020-01-01',
            'ended_on'    => null,
        ];
    }
}
