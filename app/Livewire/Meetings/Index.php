<?php

namespace App\Livewire\Meetings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('أجندة الاجتماعات')]
class Index extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('meetings.index'), 403);
    }

    public function render()
    {
        return view('livewire.meetings.index');
    }
}
