<?php

namespace App\Livewire\Correspondence;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout("layouts.app")]
#[Title("الصادر")]
class Outbox extends Component
{
    use Concerns\IsPlaceholderScreen;

    protected function screenTitle(): string
    {
        return __("home.corr_outbox");
    }

    protected function screenAbility(): string
    {
        return "correspondence.index";
    }
}
