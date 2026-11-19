<?php

namespace App\Livewire\FeedbackResults;

use App\Exports\FeedbackOpinionsExport;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackExport;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Support\FeedbackResults\FeedbackAccess;
use App\Support\FeedbackResults\OpinionsQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * تقرير «آراء المواطنين» — التقييمات والمقترحات معاً بالمواطن والمقر والمحافظة
 * والرأي المكتوب، على فترة يحدّدها المستخدم. قراءة وتصدير فقط: الحذف والسلة
 * في الشاشتين الأصليتين.
 */
#[Layout('layouts.app')]
#[Title('آراء المواطنين')]
class Opinions extends Component
{
    use WithFeedbackExport, WithFeedbackFilters, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** تقييم / اقتراح / الكل — يُفحص بالقائمة البيضاء في OpinionsQuery::type. */
    #[Url(as: 'type', except: '')]
    public string $type = '';

    #[Url(as: 'dir', except: 'desc')]
    public string $sortDir = 'desc';

    public function mount(): void
    {
        abort_unless(FeedbackAccess::canView(Auth::user()), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function toggleSort(): void
    {
        $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('governorate_id', 'office_id', 'from', 'to', 'identity', 'type');
        $this->afterFilterChange();
    }

    public function hasActiveFilters(): bool
    {
        return OpinionsQuery::type($this->type) !== ''
            || $this->governorate_id !== '' || $this->office_id !== ''
            || $this->from !== '' || $this->to !== ''
            || $this->filterSet()->identity !== '';
    }

    protected function query(): Builder
    {
        return OpinionsQuery::build($this->filterSet(), Auth::user(), $this->search, $this->type, $this->sortDir);
    }

    /* ── التصدير ── */

    protected function exportBaseName(): string
    {
        return 'feedback-opinions';
    }

    protected function pdfRouteName(): ?string
    {
        return 'feedback-results.opinions.pdf';
    }

    protected function exportSubject(): string
    {
        return __('home.fr_opinions');
    }

    protected function exportIsEmpty(): bool
    {
        return ! $this->query()->exists();
    }

    protected function exportParams(): array
    {
        $params = $this->filterSet()->toQuery();

        if ($this->search !== '') {
            $params['q'] = $this->search;
        }

        if (($type = OpinionsQuery::type($this->type)) !== '') {
            $params['type'] = $type;
        }

        if ($this->sortDir === 'asc') {
            $params['dir'] = 'asc';
        }

        if ($this->exportPersonal) {
            $params['personal'] = 1;
        }

        return $params;
    }

    public function excelExport(): object
    {
        return new FeedbackOpinionsExport($this->query()->get()->map(fn ($row) => OpinionsQuery::hydrate($row)),$this->exportPersonal);
    }

    public function render()
    {
        $rows = $this->query()->paginate(15)->through(fn ($row) => OpinionsQuery::hydrate($row));

        return view('livewire.feedback-results.opinions', [
            'rows'   => $rows,
            'topics' => OpinionsQuery::topicsFor($rows->getCollection()),
        ]);
    }
}
