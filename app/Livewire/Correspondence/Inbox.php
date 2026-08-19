<?php

namespace App\Livewire\Correspondence;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout("layouts.app")]
#[Title("الوارد")]
class Inbox extends Component
{
    use Concerns\IsPlaceholderScreen;

    protected function screenTitle(): string
    {
        return __("home.corr_inbox");
    }

    protected function screenAbility(): string
    {
        return "correspondence.index";
    }
}
