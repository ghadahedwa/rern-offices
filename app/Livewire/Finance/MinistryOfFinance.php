<?php

namespace App\Livewire\Finance;

use App\Models\FinanceAccount;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MinistryOfFinance extends Component
{
    use Concerns\IsAccountPage;

    protected function accountKey(): string
    {
        return FinanceAccount::MINISTRY_OF_FINANCE;
    }
}
