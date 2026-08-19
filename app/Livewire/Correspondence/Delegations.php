<?php

namespace App\Livewire\Correspondence;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout("layouts.app")]
#[Title("التفويض")]
class Delegations extends Component
{
    use Concerns\IsPlaceholderScreen;

    protected function screenTitle(): string
    {
        return __("home.corr_delegations");
    }

    protected function screenAbility(): string
    {
        return "correspondence.delegate";
    }
}
