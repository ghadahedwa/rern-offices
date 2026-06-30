<?php

namespace App\Livewire\Reports;

use App\Models\Governorate;
use App\Reports\ClaimsStatement as StatementBuilder;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('كشف حساب المطالبات')]
class ClaimsStatement extends Component
{
    public ?int $governorateId = null;
    public ?int $fromYear = null;
    public ?int $fromMonth = 1;
    public ?int $toYear = null;
    public ?int $toMonth = 12;

    public array $applied = [];
    public bool $hasSearched = false;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('claims.index'),
            403
        );

        // المحافظة تبدأ فارغة ليظهر "اختر" ويُلزم المستخدم بالاختيار
        $this->fromYear = (int) date('Y');
        $this->toYear   = (int) date('Y');
    }

    /** المحافظات المتاحة للمستخدم (super-admin = الكل) */
    private function allowedGovernorates()
    {
        $user = auth()->user();

        return $user?->hasRole('super-admin')
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();
    }

    public function search(): void
    {
        $this->validate([
            'governorateId' => 'required|integer',
            'fromYear'      => 'required|integer|min:2000',
            'fromMonth'     => 'required|integer|between:1,12',
            'toYear'        => 'required|integer|min:2000',
            'toMonth'       => 'required|integer|between:1,12',
        ], [
            'governorateId.required' => __('home.claims_select_gov_required'),
        ], [
            'governorateId' => __('home.claims_governorate'),
            'fromYear'      => __('home.report_from'),
            'toYear'        => __('home.report_to'),
        ]);

        // المحافظة لازم تكون ضمن المسموح
        abort_unless($this->allowedGovernorates()->contains('id', $this->governorateId), 403);

        $fromKey = $this->fromYear * 100 + $this->fromMonth;
        $toKey   = $this->toYear * 100 + $this->toMonth;

        if ($fromKey > $toKey) {
            $this->addError('fromYear', __('home.report_period_invalid'));
            return;
        }

        $this->applied = [
            'governorateId' => $this->governorateId,
            'fromKey'       => $fromKey,
            'toKey'         => $toKey,
        ];
        $this->hasSearched = true;
    }

    public function exportPdf()
    {
        if (! $this->hasSearched) {
            Flux::toast(variant: 'warning', text: __('home.report_search_prompt'));
            return;
        }

        session(['claims_statement_filters' => $this->applied]);
        $this->js("window.open('" . route('reports.claims-statement.pdf') . "', '_blank')");
    }

    public function render()
    {
        $statement = null;
        if ($this->hasSearched) {
            $gov = $this->allowedGovernorates()->firstWhere('id', $this->applied['governorateId']);
            if ($gov) {
                $statement = StatementBuilder::build($gov, $this->applied['fromKey'], $this->applied['toKey']);
            }
        }

        return view('livewire.reports.claims-statement', [
            'governorates' => $this->allowedGovernorates(),
            'years'        => range((int) date('Y'), 2024),
            'months'       => StatementBuilder::months(),
            'statement'    => $statement,
        ]);
    }
}
