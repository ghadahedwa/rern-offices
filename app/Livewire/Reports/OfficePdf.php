<?php

namespace App\Livewire\Reports;

use App\Models\Governorate;
use App\Models\Office;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('تقرير مقر واحد')]
class OfficePdf extends Component
{
    public ?int $selectedGovernorateId = null;
    public ?int $selectedOfficeId = null;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.export'),
            403
        );
    }

    public function updatedSelectedGovernorateId(): void
    {
        $this->selectedOfficeId = null;
    }

    public function generatePdf(): void
    {
        if (!$this->selectedOfficeId) {
            Flux::toast(variant: 'warning', text: 'اختر مقراً أولاً');
            return;
        }

        $this->js("window.open('" . route('offices.pdf', $this->selectedOfficeId) . "', '_blank')");
    }

    public function render()
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            $governorates = Governorate::orderBy('order')->orderBy('name')->get();
        } else {
            $governorates = $user->governorates()->orderBy('order')->orderBy('name')->get();
        }

        $offices = collect();
        if ($this->selectedGovernorateId) {
            $offices = Office::where('governorate_id', $this->selectedGovernorateId)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('livewire.reports.office-pdf', [
            'governorates' => $governorates,
            'offices'      => $offices,
        ]);
    }
}
