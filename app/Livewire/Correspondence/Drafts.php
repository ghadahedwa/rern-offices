<?php

namespace App\Livewire\Correspondence;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout("layouts.app")]
#[Title("المسودات")]
class Drafts extends Component
{
    use Concerns\IsPlaceholderScreen;

    protected function screenTitle(): string
    {
        return __("home.corr_drafts");
    }

    protected function screenAbility(): string
    {
        return "correspondence.create";
    }
}
