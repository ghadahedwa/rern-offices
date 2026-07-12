<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('لوحة تحكم النظام')]
class SystemDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(
            auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.settings'),
            403
        );
    }

    public function render()
    {
        return view('livewire.system-dashboard');
    }
}
