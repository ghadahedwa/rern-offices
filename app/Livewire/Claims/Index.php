<?php

namespace App\Livewire\Claims;

use App\Models\Governorate;
use App\Models\GovernorateCancelledDemand;
use App\Models\GovernorateClaim;
use App\Models\GovernorateDemand;
use Flux\Flux;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'debt';

    // فلتر مشترك
    public ?int $filterGovernorate = null;
    public string $filterYear = '';

    // ترتيب جدول المديونية: name | demands | cancelled | collected | debt
    public string $sortField = 'name';
    public string $sortDir = 'asc';

    // ── modals المطالبات ──
    public bool $showDemand = false;
    public bool $showDeleteDemand = false;
    public ?int $editingDemandId = null;
    public ?int $demandGov = null;
    public string $demandYear = '';
    public string $demandMonth = '';
    public string $demandAmount = '';
    public ?int $deletingDemandId = null;
    public string $deletingDemandLabel = '';

    // ── modals المحصل ──
    public bool $showForm = false;
    public bool $showDelete = false;
    public ?int $editingId = null;
    public ?int $formGov = null;
    public string $formYear = '';
    public string $formMonth = '';
    public string $formValue = '';
    public ?float $formGovDebt = null; // المديونية الحالية للمحافظة المختارة (تنبيه عند الإدخال)
    public ?int $deletingId = null;
    public string $deletingLabel = '';

    // ── modals المطالبات الملغاة ──
    public bool $showCancelled = false;
    public bool $showDeleteCancelled = false;
    public ?int $editingCancelledId = null;
    public ?int $cancGov = null;
    public string $cancYear = '';
    public string $cancMonth = '';
    public string $cancAmount = '';
    public string $cancReason = '';
    public ?int $deletingCancelledId = null;
    public string $deletingCancelledLabel = '';

    public function mount(): void
    {
        abort_unless(
            auth()->user()?->hasRole('super-admin') || auth()->user()?->can('claims.index'),
            403
        );
    }

    /** صلاحية التعديل: super-admin أو claims.edit */
    public function canEdit(): bool
    {
        $u = auth()->user();

        return $u?->hasRole('super-admin') || $u?->can('claims.edit');
    }

    /** المحافظات المتاحة للمستخدم حسب صلاحياته (super-admin = الكل) */
    private function allowedGovernorates()
    {
        $user = auth()->user();

        return $user?->hasRole('super-admin')
            ? Governorate::orderBy('order')->orderBy('id')->get()
            : $user->governorates()->orderBy('order')->orderBy('id')->get();
    }

    private function scopedDemands()
    {
        return GovernorateDemand::whereIn('governorate_id', $this->allowedGovernorates()->pluck('id'));
    }

    private function scopedClaims()
    {
        return GovernorateClaim::whereIn('governorate_id', $this->allowedGovernorates()->pluck('id'));
    }

    private function scopedCancelled()
    {
        return GovernorateCancelledDemand::whereIn('governorate_id', $this->allowedGovernorates()->pluck('id'));
    }

    private function months(): array
    {
        return [
            1 => 'يناير',  2 => 'فبراير', 3 => 'مارس',    4 => 'أبريل',
            5 => 'مايو',   6 => 'يونيو',  7 => 'يوليو',   8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['debt', 'demands', 'cancelled', 'collection'], true) ? $tab : 'debt';
        $this->resetPage('demandsPage');
        $this->resetPage('cancelledPage');
        $this->resetPage('collectionPage');
    }

    public function updatedFilterGovernorate(): void
    {
        $this->resetPage('demandsPage');
        $this->resetPage('cancelledPage');
        $this->resetPage('collectionPage');
    }

    public function updatedFilterYear(): void
    {
        $this->resetPage('demandsPage');
        $this->resetPage('cancelledPage');
        $this->resetPage('collectionPage');
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['name', 'demands', 'cancelled', 'collected', 'debt'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
    }

    // ── المطالبات ──
    public function openAddDemand(): void
    {
        abort_unless($this->canEdit(), 403);

        $this->editingDemandId = null;
        $this->demandGov   = $this->filterGovernorate;
        $this->demandYear  = '';
        $this->demandMonth = '';
        $this->demandAmount = '';
        $this->resetValidation();
        $this->showDemand = true;
    }

    public function openEditDemand(int $demandId): void
    {
        abort_unless($this->canEdit(), 403);

        $demand = $this->scopedDemands()->findOrFail($demandId);

        $this->editingDemandId = $demand->id;
        $this->demandGov    = $demand->governorate_id;
        $this->demandYear   = (string) $demand->year;
        $this->demandMonth  = (string) $demand->month;
        $this->demandAmount = (string) (0 + $demand->amount);
        $this->resetValidation();
        $this->showDemand = true;
    }

    public function saveDemand(): void
    {
        abort_unless($this->canEdit(), 403);

        $allowedIds = $this->allowedGovernorates()->pluck('id')->all();

        $this->validate([
            'demandGov'    => ['required', 'integer', 'in:' . implode(',', $allowedIds)],
            'demandYear'   => 'required|integer|min:2000',
            'demandMonth'  => 'required|integer|between:1,12',
            'demandAmount' => 'required|numeric|min:0',
        ], [], [
            'demandGov'    => __('home.claims_governorate'),
            'demandYear'   => __('home.claims_year'),
            'demandMonth'  => __('home.claims_month'),
            'demandAmount' => __('home.claims_value'),
        ]);

        $dup = GovernorateDemand::where('governorate_id', $this->demandGov)
            ->where('year', $this->demandYear)
            ->where('month', $this->demandMonth)
            ->when($this->editingDemandId, fn($q) => $q->where('id', '!=', $this->editingDemandId))
            ->exists();

        if ($dup) {
            $this->addError('demandYear', __('home.claims_duplicate'));
            return;
        }

        $data = [
            'governorate_id' => $this->demandGov,
            'year'           => $this->demandYear,
            'month'          => $this->demandMonth,
            'amount'         => $this->demandAmount,
        ];

        if ($this->editingDemandId) {
            $this->scopedDemands()->findOrFail($this->editingDemandId)->update($data);
            $msg = __('home.claims_updated');
        } else {
            GovernorateDemand::create($data);
            $msg = __('home.claims_added');
        }

        $this->showDemand = false;
        Flux::toast(variant: 'success', text: $msg);
    }

    public function askDeleteDemand(int $demandId): void
    {
        abort_unless($this->canEdit(), 403);

        $demand = $this->scopedDemands()->with('governorate')->findOrFail($demandId);
        $months = $this->months();

        $this->deletingDemandId    = $demand->id;
        $this->deletingDemandLabel = $demand->governorate->name . ' — ' . ($months[$demand->month] ?? $demand->month) . ' ' . $demand->year;
        $this->showDeleteDemand = true;
    }

    public function deleteDemand(): void
    {
        abort_unless($this->canEdit(), 403);

        if ($this->deletingDemandId) {
            $this->scopedDemands()->findOrFail($this->deletingDemandId)->delete();
            $this->deletingDemandId = null;
            $this->deletingDemandLabel = '';
            $this->showDeleteDemand = false;
            Flux::toast(variant: 'success', text: __('home.claims_deleted'));
        }
    }

    // ── المطالبات الملغاة ──
    public function openAddCancelled(): void
    {
        abort_unless($this->canEdit(), 403);

        $this->editingCancelledId = null;
        $this->cancGov    = $this->filterGovernorate;
        $this->cancYear   = '';
        $this->cancMonth  = '';
        $this->cancAmount = '';
        $this->cancReason = '';
        $this->resetValidation();
        $this->showCancelled = true;
    }

    public function openEditCancelled(int $id): void
    {
        abort_unless($this->canEdit(), 403);

        $row = $this->scopedCancelled()->findOrFail($id);

        $this->editingCancelledId = $row->id;
        $this->cancGov    = $row->governorate_id;
        $this->cancYear   = (string) $row->year;
        $this->cancMonth  = (string) $row->month;
        $this->cancAmount = (string) (0 + $row->amount);
        $this->cancReason = (string) $row->reason;
        $this->resetValidation();
        $this->showCancelled = true;
    }

    public function saveCancelled(): void
    {
        abort_unless($this->canEdit(), 403);

        $allowedIds = $this->allowedGovernorates()->pluck('id')->all();

        $this->validate([
            'cancGov'    => ['required', 'integer', 'in:' . implode(',', $allowedIds)],
            'cancYear'   => 'required|integer|min:2000',
            'cancMonth'  => 'required|integer|between:1,12',
            'cancAmount' => 'required|numeric|min:0',
            'cancReason' => 'nullable|string|max:1000',
        ], [], [
            'cancGov'    => __('home.claims_governorate'),
            'cancYear'   => __('home.claims_year'),
            'cancMonth'  => __('home.claims_month'),
            'cancAmount' => __('home.claims_value'),
            'cancReason' => __('home.claims_cancel_reason'),
        ]);

        $data = [
            'governorate_id' => $this->cancGov,
            'year'           => $this->cancYear,
            'month'          => $this->cancMonth,
            'amount'         => $this->cancAmount,
            'reason'         => $this->cancReason !== '' ? $this->cancReason : null,
        ];

        if ($this->editingCancelledId) {
            $this->scopedCancelled()->findOrFail($this->editingCancelledId)->update($data);
            $msg = __('home.claims_updated');
        } else {
            GovernorateCancelledDemand::create($data);
            $msg = __('home.claims_added');
        }

        $this->showCancelled = false;
        Flux::toast(variant: 'success', text: $msg);
    }

    public function askDeleteCancelled(int $id): void
    {
        abort_unless($this->canEdit(), 403);

        $row    = $this->scopedCancelled()->with('governorate')->findOrFail($id);
        $months = $this->months();

        $this->deletingCancelledId    = $row->id;
        $this->deletingCancelledLabel = $row->governorate->name . ' — ' . ($months[$row->month] ?? $row->month) . ' ' . $row->year;
        $this->showDeleteCancelled = true;
    }

    public function deleteCancelled(): void
    {
        abort_unless($this->canEdit(), 403);

        if ($this->deletingCancelledId) {
            $this->scopedCancelled()->findOrFail($this->deletingCancelledId)->delete();
            $this->deletingCancelledId = null;
            $this->deletingCancelledLabel = '';
            $this->showDeleteCancelled = false;
            Flux::toast(variant: 'success', text: __('home.claims_deleted'));
        }
    }

    // ── المحصل (الشهري) ──

    /** المديونية الحالية لمحافظة = المطالبات − الملغاة − المحصل */
    private function governorateDebt(?int $govId): ?float
    {
        if (! $govId || ! $this->allowedGovernorates()->contains('id', $govId)) {
            return null;
        }

        $demands   = (float) GovernorateDemand::where('governorate_id', $govId)->sum('amount');
        $cancelled = (float) GovernorateCancelledDemand::where('governorate_id', $govId)->sum('amount');
        $collected = (float) GovernorateClaim::where('governorate_id', $govId)->sum('value');

        return $demands - $cancelled - $collected;
    }

    public function updatedFormGov($value): void
    {
        $this->formGovDebt = $this->governorateDebt($value !== '' && $value !== null ? (int) $value : null);
    }

    public function openAdd(): void
    {
        abort_unless($this->canEdit(), 403);

        $this->editingId = null;
        $this->formGov   = $this->filterGovernorate;
        $this->formYear  = '';
        $this->formMonth = '';
        $this->formValue = '';
        $this->formGovDebt = $this->governorateDebt($this->formGov);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function openEdit(int $claimId): void
    {
        abort_unless($this->canEdit(), 403);

        $claim = $this->scopedClaims()->findOrFail($claimId);

        $this->editingId = $claim->id;
        $this->formGov   = $claim->governorate_id;
        $this->formYear  = (string) $claim->year;
        $this->formMonth = (string) $claim->month;
        $this->formValue = (string) (0 + $claim->value);
        $this->formGovDebt = $this->governorateDebt($this->formGov);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless($this->canEdit(), 403);

        $allowedIds = $this->allowedGovernorates()->pluck('id')->all();

        $this->validate([
            'formGov'   => ['required', 'integer', 'in:' . implode(',', $allowedIds)],
            'formYear'  => 'required|integer|min:2000',
            'formMonth' => 'required|integer|between:1,12',
            'formValue' => 'required|numeric|min:0',
        ], [], [
            'formGov'   => __('home.claims_governorate'),
            'formYear'  => __('home.claims_year'),
            'formMonth' => __('home.claims_month'),
            'formValue' => __('home.claims_value'),
        ]);

        $dup = GovernorateClaim::where('governorate_id', $this->formGov)
            ->where('year', $this->formYear)
            ->where('month', $this->formMonth)
            ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($dup) {
            $this->addError('formYear', __('home.claims_duplicate'));
            return;
        }

        $data = [
            'governorate_id' => $this->formGov,
            'year'           => $this->formYear,
            'month'          => $this->formMonth,
            'value'          => $this->formValue,
        ];

        if ($this->editingId) {
            $this->scopedClaims()->findOrFail($this->editingId)->update($data);
            $msg = __('home.claims_updated');
        } else {
            GovernorateClaim::create($data);
            $msg = __('home.claims_added');
        }

        $this->showForm = false;
        Flux::toast(variant: 'success', text: $msg);
    }

    public function askDelete(int $claimId): void
    {
        abort_unless($this->canEdit(), 403);

        $claim  = $this->scopedClaims()->with('governorate')->findOrFail($claimId);
        $months = $this->months();

        $this->deletingId    = $claim->id;
        $this->deletingLabel = $claim->governorate->name . ' — ' . ($months[$claim->month] ?? $claim->month) . ' ' . $claim->year;
        $this->showDelete = true;
    }

    public function deleteRow(): void
    {
        abort_unless($this->canEdit(), 403);

        if ($this->deletingId) {
            $this->scopedClaims()->findOrFail($this->deletingId)->delete();
            $this->deletingId = null;
            $this->deletingLabel = '';
            $this->showDelete = false;
            Flux::toast(variant: 'success', text: __('home.claims_deleted'));
        }
    }

    public function render()
    {
        $all    = $this->allowedGovernorates();
        $govIds = $all->pluck('id');

        // إجماليات لكل محافظة
        $demandsTotals = GovernorateDemand::whereIn('governorate_id', $govIds)
            ->selectRaw('governorate_id, SUM(amount) as total')
            ->groupBy('governorate_id')
            ->pluck('total', 'governorate_id');

        $cancelledTotals = GovernorateCancelledDemand::whereIn('governorate_id', $govIds)
            ->selectRaw('governorate_id, SUM(amount) as total')
            ->groupBy('governorate_id')
            ->pluck('total', 'governorate_id');

        $collectedTotals = GovernorateClaim::whereIn('governorate_id', $govIds)
            ->selectRaw('governorate_id, SUM(value) as total')
            ->groupBy('governorate_id')
            ->pluck('total', 'governorate_id');

        // تاب المديونية: المديونية = المطالبات − الملغاة − المحصل
        $debtRows = ($this->filterGovernorate
            ? $all->where('id', $this->filterGovernorate)->values()
            : $all)
            ->sortBy(function ($gov) use ($demandsTotals, $cancelledTotals, $collectedTotals) {
                $d = (float) ($demandsTotals[$gov->id] ?? 0);
                $x = (float) ($cancelledTotals[$gov->id] ?? 0);
                $c = (float) ($collectedTotals[$gov->id] ?? 0);
                return match ($this->sortField) {
                    'demands'   => $d,
                    'cancelled' => $x,
                    'collected' => $c,
                    'debt'      => $d - $x - $c,
                    default     => $gov->order,
                };
            }, SORT_REGULAR, $this->sortDir === 'desc')
            ->values();

        // تاب المطالبات
        $demands = $this->scopedDemands()
            ->with('governorate')
            ->when($this->filterGovernorate, fn($q) => $q->where('governorate_id', $this->filterGovernorate))
            ->when($this->filterYear !== '', fn($q) => $q->where('year', $this->filterYear))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(10, ['*'], 'demandsPage');

        // تاب المطالبات الملغاة
        $cancelled = $this->scopedCancelled()
            ->with('governorate')
            ->when($this->filterGovernorate, fn($q) => $q->where('governorate_id', $this->filterGovernorate))
            ->when($this->filterYear !== '', fn($q) => $q->where('year', $this->filterYear))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(10, ['*'], 'cancelledPage');

        // تاب المحصل
        $collection = $this->scopedClaims()
            ->with('governorate')
            ->when($this->filterGovernorate, fn($q) => $q->where('governorate_id', $this->filterGovernorate))
            ->when($this->filterYear !== '', fn($q) => $q->where('year', $this->filterYear))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(10, ['*'], 'collectionPage');

        return view('livewire.claims.index', [
            'allGovernorates' => $all,
            'debtRows'        => $debtRows,
            'demandsTotals'   => $demandsTotals,
            'cancelledTotals' => $cancelledTotals,
            'collectedTotals' => $collectedTotals,
            'demands'         => $demands,
            'cancelled'       => $cancelled,
            'collection'      => $collection,
            'years'           => range((int) date('Y'), 2024),
            'months'          => $this->months(),
        ]);
    }
}
