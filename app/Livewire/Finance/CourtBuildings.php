<?php

namespace App\Livewire\Finance;

use App\Models\FinanceAccount;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CourtBuildings extends Component
{
    use Concerns\IsAccountPage;

    protected function accountKey(): string
    {
        return FinanceAccount::COURT_BUILDINGS;
    }
}
