<?php

namespace App\Livewire\FeedbackResults;

use App\Exports\FeedbackRatingsExport;
use App\Livewire\FeedbackResults\Concerns\WithBulkDelete;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackExport;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackSorting;
use App\Models\FeedbackRating;
use App\Support\FeedbackResults\RatingsQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('تقييمات المواطنين')]
class Ratings extends Component
{
    use WithBulkDelete, WithFeedbackExport, WithFeedbackFilters, WithFeedbackSorting, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** الصف المفتوح لعرض التفاصيل (null = مفيش) */
    public ?int $expanded = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    protected function sortableColumns(): array
    {
        return RatingsQuery::SORTABLE;
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
        return FeedbackRating::class;
    }

    protected function bulkSubject(): string
    {
        return __('home.fr_ratings');
    }

    /** الاستعلام المفلتر — مصدر واحد لما يُعرض ولما يُحذف جماعياً ولما يُصدَّر. */
    protected function bulkQuery(): Builder
    {
        return RatingsQuery::build($this->filterSet(), Auth::user(), $this->search, $this->showTrashed);
    }

    /* ── التصدير ── */

    protected function exportBaseName(): string
    {
        return 'feedback-ratings';
    }

    protected function pdfRouteName(): ?string
    {
        return 'feedback-results.ratings.pdf';
    }

    protected function exportIsEmpty(): bool
    {
        return $this->bulkQuery()->count() === 0;
    }

    public function excelExport(): object
    {
        return new FeedbackRatingsExport(
            $this->applySorting($this->bulkQuery()),
            $this->exportPersonal,
        );
    }

    public function render()
    {
        $ratings = $this->bulkQuery()
            ->with(['office:id,name', 'governorate:id,name'])
            ->tap(fn ($q) => $this->applySorting($q))
            ->paginate(15);

        return view('livewire.feedback-results.ratings', [
            'ratings'        => $ratings,
            'waitTimes'      => FeedbackRating::WAIT_TIMES,
            'waitTimesShort' => FeedbackRating::WAIT_TIMES_SHORT,
            'criteria'       => FeedbackRating::CRITERIA,
        ]);
    }
}
