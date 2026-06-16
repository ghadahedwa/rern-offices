<?php

namespace App\Livewire\DisabilitiesAccess;

use App\Models\DisabilitieAccess;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('تجهيزات ذوي الهمم')]
class Create extends Component
{
    public ?DisabilitieAccess $disabilitiesAccess = null;

    public string $name = '';

    public function mount(?DisabilitieAccess $disabilitiesAccess = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($disabilitiesAccess?->exists) {
            $this->disabilitiesAccess = $disabilitiesAccess;
            $this->name               = $disabilitiesAccess->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->disabilitiesAccess?->exists) {
            $this->disabilitiesAccess->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.disabilities_access_updated'));
        } else {
            DisabilitieAccess::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.disabilities_access_created'));
        }

        $this->redirect(route('disabilities-access.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.disabilities-access.create');
    }
}
