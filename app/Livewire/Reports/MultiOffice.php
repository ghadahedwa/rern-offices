<?php

namespace App\Livewire\Reports;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('تقرير مجموعة مقرات')]
class MultiOffice extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.export'),
            403
        );
    }

    public function render()
    {
        return view('livewire.reports.multi-office');
    }
}
