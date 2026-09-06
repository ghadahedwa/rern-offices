<?php

namespace Database\Factories;

use App\Models\Contractor;
use App\Models\Profession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractorFactory extends Factory
{
    protected $model = Contractor::class;

    public function definition(): array
    {
        return [
            'name'  => 'عامل '.fake()->unique()->numberBetween(1, 99999),
            'phone' => '010'.fake()->numerify('########'),
            // الصفة مزروعة في هجرة `professions` — تُقرأ ولا تُنشأ، فلا تتكرّر أسماؤها
            'profession_id' => fn () => Profession::ordered()->value('id'),
        ];
    }
}
