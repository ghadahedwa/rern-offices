<?php

namespace App\Livewire\LocationDescriptions;

use App\Models\LocationDescription;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('وصف المقر')]
class Create extends Component
{
    public ?LocationDescription $locationDescription = null;

    public string $name = '';
    public bool $shows_windows_count = false;

    public function mount(?LocationDescription $locationDescription = null): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);

        if ($locationDescription?->exists) {
            $this->locationDescription    = $locationDescription;
            $this->name                   = $locationDescription->name;
            $this->shows_windows_count    = $locationDescription->shows_windows_count;
        }
    }

    public function save(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:255']]);

        $data = ['name' => $this->name, 'shows_windows_count' => $this->shows_windows_count];

        if ($this->locationDescription?->exists) {
            $this->locationDescription->update($data);
            Flux::toast(variant: 'success', text: __('home.location_description_updated'));
        } else {
            LocationDescription::create($data);
            Flux::toast(variant: 'success', text: __('home.location_description_created'));
        }

        $this->redirect(route('location-descriptions.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.location-descriptions.create');
    }
}
