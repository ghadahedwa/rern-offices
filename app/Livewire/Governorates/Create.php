<?php

namespace App\Livewire\Governorates;

use App\Models\Governorate;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('المحافظة')]
class Create extends Component
{
    public ?Governorate $governorate = null;

    public string $name                  = '';
    public int    $order                 = 0;
    public string $supervising_counselor = '';
    public string $latitude              = '';
    public string $longitude             = '';

    public function mount(?Governorate $governorate = null): void
    {
        $user     = auth()->user();
        $isEdit   = (bool) $governorate?->exists;
        $required = $isEdit ? 'governorates.edit' : 'governorates.create';
        abort_unless($user?->hasRole('super-admin') || $user?->can($required), 403);

        if ($governorate?->exists) {
            $this->governorate           = $governorate;
            $this->name                  = $governorate->name;
            $this->order                 = $governorate->order ?? 0;
            $this->supervising_counselor = $governorate->supervising_counselor ?? '';
            $this->latitude              = (string) ($governorate->latitude ?? '');
            $this->longitude             = (string) ($governorate->longitude ?? '');
        } else {
            $this->order = (int) Governorate::max('order') + 1;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name'      => ['required', 'string', 'max:255'],
            'order'     => ['required', 'integer', 'min:0'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $data = [
            'name'                  => $this->name,
            'order'                 => $this->order,
            'supervising_counselor' => $this->supervising_counselor ?: null,
            'latitude'              => $this->latitude ?: null,
            'longitude'             => $this->longitude ?: null,
        ];

        if ($this->governorate?->exists) {
            $this->governorate->update($data);
            Flux::toast(variant: 'success', text: __('home.governorate_updated'));
        } else {
            Governorate::create($data);
            Flux::toast(variant: 'success', text: __('home.governorate_created'));
        }

        $this->redirect(route('governorates.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.governorates.create');
    }
}
