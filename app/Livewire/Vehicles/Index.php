<?php

namespace App\Livewire\Vehicles;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('السيارات المتنقلة')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.vehicles.index');
    }
}
