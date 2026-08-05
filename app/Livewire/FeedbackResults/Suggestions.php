<?php

namespace App\Livewire\FeedbackResults;

use App\Exports\FeedbackSuggestionsExport;
use App\Livewire\FeedbackResults\Concerns\WithBulkDelete;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackExport;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackSorting;
use App\Models\FeedbackSuggestion;
use App\Support\FeedbackResults\SuggestionsQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('مقترحات المواطنين')]
class Suggestions extends Component
{
    use WithBulkDelete, WithFeedbackExport, WithFeedbackFilters, WithFeedbackSorting, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** الصف المفتوح لعرض التفاصيل */
    public ?int $expanded = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    /** topics_count متاح للترتيب لأن withCount يضيفه كعمود في الاستعلام. */
    protected function sortableColumns(): array
    {
        return SuggestionsQuery::SORTABLE;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    protected function bulkModel(): string
    {
        return FeedbackSuggestion::class;
    }

    protected function bulkSubject(): string
    {
        return __('home.fr_suggestions');
    }

    /** الاستعلام المفلتر — مصدر واحد لما يُعرض ولما يُحذف جماعياً ولما يُصدَّر. */
    protected function bulkQuery(): Builder
    {
        return SuggestionsQuery::build($this->filterSet(), Auth::user(), $this->search, $this->showTrashed);
    }

    /* ── التصدير ── */

    protected function exportBaseName(): string
    {
        return 'feedback-suggestions';
    }

    protected function pdfRouteName(): ?string
    {
        return 'feedback-results.suggestions.pdf';
    }

    protected function exportIsEmpty(): bool
    {
        return $this->bulkQuery()->count() === 0;
    }

    /** withCount لازم قبل الترتيب: topics_count عمود محسوب لا عمود في الجدول. */
    public function excelExport(): object
    {
        return new FeedbackSuggestionsExport(
            $this->applySorting($this->bulkQuery()->withCount('topics')),
            $this->exportPersonal,
        );
    }

    public function render()
    {
        $suggestions = $this->bulkQuery()
            ->with(['office:id,name', 'governorate:id,name', 'topics.domain'])
            ->withCount('topics')
            ->tap(fn ($q) => $this->applySorting($q))
            ->paginate(15);

        return view('livewire.feedback-results.suggestions', [
            'suggestions' => $suggestions,
        ]);
    }
}
