<?php

namespace App\Livewire\Reports;

use App\Models\Governorate;
use App\Models\Office;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('دليل الهاتف للمقرات')]
class PhoneDirectory extends Component
{
    /** متاحة لكل المستخدمين — بلا أي صلاحيات */

    public ?int $selectedGovernorateId = null;
    public ?int $selectedOfficeId = null;

    /** المحددات المُطبَّقة فعلياً (snapshot وقت الضغط على "عرض") */
    public ?int $appliedOfficeId = null;
    public bool $hasSearched = false;

    /** عند تغيير المحافظة: تصفير المقر المختار */
    public function updatedSelectedGovernorateId(): void
    {
        $this->selectedOfficeId = null;
    }

    public function search(): void
    {
        if (! $this->selectedGovernorateId || ! $this->selectedOfficeId) {
            Flux::toast(variant: 'warning', text: __('home.phone_directory_select_required'));
            return;
        }

        $this->appliedOfficeId = $this->selectedOfficeId;
        $this->hasSearched     = true;
    }

    public function resetFilters(): void
    {
        $this->reset(['selectedGovernorateId', 'selectedOfficeId', 'appliedOfficeId', 'hasSearched']);
    }

    /** المحافظات المسموح بها للمستخدم (null = super-admin يرى الكل) */
    protected function allowedGovIds(): ?array
    {
        $user = auth()->user();

        return $user?->hasRole('super-admin')
            ? null
            : $user->governorates()->pluck('governorates.id')->all();
    }

    public function render()
    {
        $allowedGovIds = $this->allowedGovIds(); // null = super-admin (الكل) · [] = لا شيء

        $governorates = Governorate::query()
            ->when($allowedGovIds !== null, fn ($q) => $q->whereIn('id', $allowedGovIds))
            ->orderBy('order')->orderBy('id')->get();

        // خيارات المقرات — مقيّدة بالمحافظة المختارة حالياً + نطاق المستخدم
        $offices = collect();
        if ($this->selectedGovernorateId && ($allowedGovIds === null || in_array($this->selectedGovernorateId, $allowedGovIds))) {
            $offices = Office::where('governorate_id', $this->selectedGovernorateId)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $results = collect();
        if ($this->hasSearched && $this->appliedOfficeId) {
            $results = Office::query()
                ->where('id', $this->appliedOfficeId)
                ->when($allowedGovIds !== null, fn ($q) => $q->whereIn('governorate_id', $allowedGovIds))
                ->get(['id', 'name', 'head_name', 'head_mobile']);
        }

        return view('livewire.reports.phone-directory', [
            'governorates' => $governorates,
            'offices'      => $offices,
            'results'      => $results,
        ]);
    }
}
