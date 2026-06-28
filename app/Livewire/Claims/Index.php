<?php

namespace App\Livewire\Claims;

use App\Models\Governorate;
use App\Models\GovernorateClaim;
use Flux\Flux;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'debt';

    // فلتر مشترك بالمحافظة
    public ?int $filterGovernorate = null;
    // فلتر السنة (تاب المحصل فقط)
    public string $filterYear = '';

    // ترتيب جدول المديونية: name | debt | collected | remaining
    public string $sortField = 'name';
    public string $sortDir = 'asc';

    // ── modal المديونية ──
    public bool $showDebt = false;
    public ?int $editGovId = null;
    public string $editGovName = '';
    public string $debtAmount = '';

    // ── modals المحصل ──
    public bool $showForm = false;
    public bool $showDelete = false;
    public ?int $editingId = null;
    public ?int $formGov = null;
    public string $formYear = '';
    public string $formMonth = '';
    public string $formValue = '';
    public ?int $deletingId = null;
    public string $deletingLabel = '';

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
        $this->tab = in_array($tab, ['debt', 'collection'], true) ? $tab : 'debt';
        $this->resetPage();
    }

    public function updatedFilterGovernorate(): void { $this->resetPage(); }
    public function updatedFilterYear(): void { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['name', 'debt', 'collected', 'remaining'], true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
    }

    // ── المديونية ──
    public function openDebt(int $govId): void
    {
        abort_unless($this->canEdit(), 403);

        $gov = $this->allowedGovernorates()->firstWhere('id', $govId);
        abort_unless($gov, 403);

        $this->editGovId   = $gov->id;
        $this->editGovName = $gov->name;
        $this->debtAmount  = $gov->debt_amount !== null ? (string) (0 + $gov->debt_amount) : '';
        $this->resetValidation();
        $this->showDebt = true;
    }

    public function saveDebt(): void
    {
        abort_unless($this->canEdit(), 403);

        $gov = $this->allowedGovernorates()->firstWhere('id', $this->editGovId);
        abort_unless($gov, 403);

        $this->validate(
            ['debtAmount' => 'nullable|numeric|min:0'],
            [],
            ['debtAmount' => __('home.claims_debt_amount')]
        );

        $gov->update(['debt_amount' => $this->debtAmount !== '' ? $this->debtAmount : null]);

        $this->showDebt = false;
        Flux::toast(variant: 'success', text: __('home.claims_debt_saved'));
    }

    // ── المحصل (الشهري) ──
    public function openAdd(): void
    {
        abort_unless($this->canEdit(), 403);

        $this->editingId = null;
        $this->formGov   = $this->filterGovernorate; // يفضّل المحافظة المفلترة إن وجدت
        $this->formYear  = '';
        $this->formMonth = '';
        $this->formValue = '';
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
            $this->scopedClaims()->where('id', $this->editingId)->update($data);
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
            $this->scopedClaims()->where('id', $this->deletingId)->delete();
            $this->deletingId = null;
            $this->deletingLabel = '';
            $this->showDelete = false;
            Flux::toast(variant: 'success', text: __('home.claims_deleted'));
        }
    }

    /** استعلام المحصل مقيّد بمحافظات المستخدم المسموحة */
    private function scopedClaims()
    {
        return GovernorateClaim::whereIn('governorate_id', $this->allowedGovernorates()->pluck('id'));
    }

    public function render()
    {
        $all = $this->allowedGovernorates();

        // تاب المديونية: المحافظات + إجمالي المحصّل لكل محافظة
        $debtRows = $this->filterGovernorate
            ? $all->where('id', $this->filterGovernorate)->values()
            : $all;

        $collectedTotals = GovernorateClaim::whereIn('governorate_id', $all->pluck('id'))
            ->selectRaw('governorate_id, SUM(value) as total')
            ->groupBy('governorate_id')
            ->pluck('total', 'governorate_id');

        // ترتيب صفوف المديونية حسب العمود المختار
        $debtRows = $debtRows->sortBy(function ($gov) use ($collectedTotals) {
            $collected = (float) ($collectedTotals[$gov->id] ?? 0);
            return match ($this->sortField) {
                'debt'      => $gov->debt_amount !== null ? (float) $gov->debt_amount : -INF,
                'collected' => $collected,
                'remaining' => $gov->debt_amount !== null ? (float) $gov->debt_amount - $collected : -INF,
                default     => $gov->order, // ترتيب المحافظة بـ order مش بالاسم
            };
        }, SORT_REGULAR, $this->sortDir === 'desc')->values();

        // تاب المحصل: جدول شهري مفلتر
        $collection = $this->scopedClaims()
            ->with('governorate')
            ->when($this->filterGovernorate, fn($q) => $q->where('governorate_id', $this->filterGovernorate))
            ->when($this->filterYear !== '', fn($q) => $q->where('year', $this->filterYear))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(10);

        return view('livewire.claims.index', [
            'allGovernorates' => $all,
            'debtRows'        => $debtRows,
            'collectedTotals' => $collectedTotals,
            'collection'      => $collection,
            'years'           => range((int) date('Y'), 2024),
            'months'          => $this->months(),
        ]);
    }
}
