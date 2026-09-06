<?php

namespace Database\Factories;

use App\Models\ContractorAssignment;
use App\Models\Contractor;
use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractorAssignmentFactory extends Factory
{
    protected $model = ContractorAssignment::class;

    public function definition(): array
    {
        return [
            'contractor_id' => Contractor::factory(),
            'office_id'   => Office::factory(),
            'started_on'  => '2020-01-01',
            'ended_on'    => null,
        ];
    }
}
