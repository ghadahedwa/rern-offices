<?php

namespace App\Livewire\MicrofilmOptions;

use App\Models\MicrofilmOption;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('خيارات الميكروفيلم')]
class Create extends Component
{
    public ?MicrofilmOption $microfilmOption = null;

    public string $name = '';

    public function mount(?MicrofilmOption $microfilmOption = null): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        if ($microfilmOption?->exists) {
            $this->microfilmOption = $microfilmOption;
            $this->name            = $microfilmOption->name;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->microfilmOption?->exists) {
            $this->microfilmOption->update(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.microfilm_option_updated'));
        } else {
            MicrofilmOption::create(['name' => $this->name]);
            Flux::toast(variant: 'success', text: __('home.microfilm_option_created'));
        }

        $this->redirect(route('microfilm-options.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.microfilm-options.create');
    }
}
