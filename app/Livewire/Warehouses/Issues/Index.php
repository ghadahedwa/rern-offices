<?php

namespace App\Livewire\Warehouses\Issues;

use App\Exceptions\WarehouseException;
use App\Livewire\Concerns\WithDateRange;
use App\Livewire\Concerns\WithPerPage;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\WarehouseIssue;
use App\Support\ArabicText;
use App\Support\WarehouseLedger;
use App\Support\WarehouseScope;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * سجل الصرف للمقرات — النوع الخامس من الحركة.
 *
 * ⚠️ النطاق على **المخزن الصارف** لا على المقر: الخصم يقع عليه، وهو ما
 *    يملكه المستخدم. ومقارُ محافظته تصل عبره.
 */
#[Layout('layouts.app')]
#[Title('الصرف للمقرات')]
class Index extends Component
{
    use WithDateRange;
    use WithPagination;
    use WithPerPage;
    use WithTableSorting;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** معرّف مخزن، أو '' للكل. */
    #[Url(as: 'wh', except: '')]
    public string $warehouseFilter = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    public bool $showView = false;
    public ?WarehouseIssue $viewing = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.index'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'warehouseFilter', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->warehouseFilter !== '' || $this->hasDateFilter();
    }

    protected function sortableColumns(): array
    {
        return [
            'issued_at'     => 'warehouse_issues.issued_at',
            'warehouse'     => 'warehouses.name',
            'office'        => 'offices.name',
            'document_type' => 'warehouse_issues.document_type',
            'items_count'   => 'items_count',
        ];
    }

    protected function defaultOrder(Builder $query): Builder
    {
        return $query
            ->orderByDesc('warehouse_issues.issued_at')
            ->orderByDesc('warehouse_issues.id');
    }

    public function view(int $id): void
    {
        $this->viewing = WarehouseIssue::with(['warehouse', 'office', 'creator', 'items.item.unit'])->findOrFail($id);

        // ⚠️ المعرّف يصل من العميل في طلبٍ مستقل عن render — فلا تكفي فلترة القائمة
        abort_unless(WarehouseScope::allows($this->viewing->warehouse_id), 403);

        $this->showView = true;
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('warehouses.delete'), 403);

        $issue = WarehouseIssue::with(['warehouse', 'office'])->findOrFail($id);
        abort_unless(WarehouseScope::allows($issue->warehouse_id), 403);

        $this->deletingId    = $issue->id;
        $this->deletingLabel = ($issue->warehouse?->name ?? '—').' ← '.($issue->office?->name ?? '—')
            .' — '.$issue->issued_at->format('Y-m-d');
        $this->deletingWarning = '';
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('warehouses.delete'), 403);

        if (! $this->deletingId) {
            return;
        }

        $issue = WarehouseIssue::findOrFail($this->deletingId);
        abort_unless(WarehouseScope::allows($issue->warehouse_id), 403);

        try {
            WarehouseLedger::reverseIssue($issue);
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.wh_issue_deleted'));
        } catch (WarehouseException $e) {
            $this->showDelete = false;
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function render()
    {
        $issues = WarehouseIssue::query()
            ->join('warehouses', 'warehouse_issues.warehouse_id', '=', 'warehouses.id')
            ->join('offices', 'warehouse_issues.office_id', '=', 'offices.id')
            ->select('warehouse_issues.*')
            ->tap(fn ($q) => WarehouseScope::apply($q, 'warehouse_issues.warehouse_id'))
            // قيمة غير رقمية تصل من الرابط تُهمَل — وإلا خرجت شاشة فارغة بلا سبب
            ->when(ctype_digit($this->warehouseFilter), fn ($q) => $q->where('warehouse_issues.warehouse_id', (int) $this->warehouseFilter))
            // البحث يشمل **اسم المقر** قبل غيره: هو ما يسأل عنه السائل
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $term = ArabicText::normalize($this->search);

                $q->whereRaw(ArabicText::sqlNormalize('offices.name').' LIKE ?', ['%'.$term.'%'])
                    ->orWhereRaw(ArabicText::sqlNormalize('warehouses.name').' LIKE ?', ['%'.$term.'%'])
                    ->orWhereRaw(ArabicText::sqlNormalize('warehouse_issues.document_type').' LIKE ?', ['%'.$term.'%']);
            }))
            // issued_at يومٌ كتبه المستخدم (مصبوب 'date') — بلا تحويل توقيت
            ->tap(fn ($q) => $this->applyDayRange($q, 'warehouse_issues.issued_at'))
            ->with(['warehouse', 'office'])
            ->withCount('items')
            ->tap(fn ($q) => $this->applySorting($q, 'warehouse_issues.id'))
            ->paginate($this->perPage());

        return view('livewire.warehouses.issues.index', [
            'issues'     => $issues,
            'warehouses' => WarehouseScope::warehouses(),
            'canCreate'  => Auth::user()?->can('warehouses.issue'),
            'canDelete'  => Auth::user()?->can('warehouses.delete'),
            'canAttach'  => Auth::user()?->can('warehouses.attachments'),
        ]);
    }
}
