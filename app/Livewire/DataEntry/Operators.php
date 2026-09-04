<?php

namespace App\Livewire\DataEntry;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('مدخلو البيانات')]
class Operators extends Component
{
    use Concerns\IsPlaceholderScreen;

    protected function screenTitle(): string
    {
        return __('home.de_operators');
    }

    protected function screenAbility(): string
    {
        return 'data-entry.index';
    }
}
