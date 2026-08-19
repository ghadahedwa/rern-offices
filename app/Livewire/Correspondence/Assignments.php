<?php

namespace App\Livewire\Correspondence;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout("layouts.app")]
#[Title("التكليفات")]
class Assignments extends Component
{
    use Concerns\IsPlaceholderScreen;

    protected function screenTitle(): string
    {
        return __("home.corr_assignments");
    }

    protected function screenAbility(): string
    {
        return "correspondence.index";
    }
}
