<?php

namespace App\Livewire\BuffetServices;

use App\Models\BuffetService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('خدمة البوفيه')]
class Create extends Component
{
    public ?BuffetService $buffetService = null;

    public string $name = '';

    public function mount(?BuffetService $buffetService = null): void
    {
        abort_unless(auth()->user()?->can('offices.settings'), 403);

        if ($buffetService?->exists) {
            $this->buffetService = $buffetService;
            $this->name          = $buffetService->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->buffetService?->exists) {
            $this->buffetService->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.buffet_service_updated'));
        } else {
            BuffetService::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.buffet_service_created'));
        }

        $this->redirect(route('buffet-services.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.buffet-services.create');
    }
}
