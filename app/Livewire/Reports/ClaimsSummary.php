<?php

namespace App\Livewire\Reports;

use App\Models\Governorate;
use App\Reports\ClaimsSummary as SummaryBuilder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('ملخص المديونية')]
class ClaimsSummary extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('claims.index'),
            403
        );
    }

    /** المحافظات المتاحة للمستخدم (super-admin = الكل) */
    private function allowedGovernorates()
    {
        $user = auth()->user();

        return $user?->hasRole('super-admin')
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();
    }

    public function exportPdf()
    {
        $this->js("window.open('" . route('reports.claims-summary.pdf') . "', '_blank')");
    }

    public function render()
    {
        return view('livewire.reports.claims-summary', [
            'summary' => SummaryBuilder::build($this->allowedGovernorates()),
        ]);
    }
}
