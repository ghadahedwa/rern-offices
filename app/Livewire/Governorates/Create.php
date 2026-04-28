<?php

namespace App\Livewire\Governorates;

use App\Models\Governorate;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('إضافة محافظة')]
class Create extends Component
{
    public string $name      = '';
    public string $latitude  = '';
    public string $longitude = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }

    public function save(): void
    {
        $this->validate([
            'name'      => ['required', 'string', 'max:255'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        Governorate::create([
            'name'      => $this->name,
            'latitude'  => $this->latitude ?: null,
            'longitude' => $this->longitude ?: null,
        ]);

        Flux::toast(variant: 'success', text: __('home.governorate_created'));
        $this->redirect(route('governorates.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.governorates.create');
    }
}
