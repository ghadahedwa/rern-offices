<?php

namespace App\Livewire\FeedbackResults;

use App\Exports\FeedbackDigitalRatingsExport;
use App\Livewire\FeedbackResults\Concerns\WithBulkDelete;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackExport;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackSorting;
use App\Models\FeedbackDigitalRating;
use App\Support\FeedbackResults\DigitalRatingsQuery;
use App\Support\FeedbackResults\FeedbackAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * آراء المواطنين في المنصات الرقمية — قائمة بنمط شاشة التقييمات.
 * صف التفاصيل يعرض **المسار الذي مشى فيه المواطن سؤالاً بسؤال**، والأرقام في «ملخص المنصات».
 */
#[Layout('layouts.app')]
#[Title('تقييم المنصات الرقمية')]
class DigitalRatings extends Component
{
    use WithBulkDelete, WithFeedbackExport, WithFeedbackFilters, WithFeedbackSorting, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    /** حجز / بدون حجز — يُفحص بالقائمة البيضاء في DigitalRatingsQuery. */
    #[Url(as: 'path', except: '')]
    public string $path = '';

    public ?int $expanded = null;

    public function mount(): void
    {
        abort_unless(FeedbackAccess::canView(Auth::user()), 403);
    }

    protected function sortableColumns(): array
    {
        return DigitalRatingsQuery::SORTABLE;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPath(): void
    {
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    /** مسح الفلاتر المشتركة يمسح المسار معها. */
    public function resetFilters(): void
    {
        $this->reset('governorate_id', 'office_id', 'from', 'to', 'identity', 'path');
        $this->afterFilterChange();
    }

    public function hasActiveFilters(): bool
    {
        return $this->filterSet()->isActive() || in_array($this->path, DigitalRatingsQuery::PATHS, true);
    }

    protected function bulkModel(): string
    {
        return FeedbackDigitalRating::class;
    }

    protected function bulkSubject(): string
    {
        return __('home.fr_digital');
    }

    /** الاستعلام المفلتر — مصدر واحد لما يُعرض ولما يُحذف جماعياً ولما يُصدَّر. */
    protected function bulkQuery(): Builder
    {
        return DigitalRatingsQuery::build(
            $this->filterSet(), Auth::user(), $this->search, $this->viewingTrash(), $this->path,
        );
    }

    /* ── التصدير — Excel وحده: ثلاثة عشر عمود إجابة لا تدخل صفحة مطبوعة، والـPDF للملخص ── */

    protected function exportBaseName(): string
    {
        return 'feedback-digital';
    }

    protected function exportIsEmpty(): bool
    {
        return $this->bulkQuery()->count() === 0;
    }

    protected function extraExportParams(): array
    {
        return in_array($this->path, DigitalRatingsQuery::PATHS, true) ? ['path' => $this->path] : [];
    }

    public function excelExport(): object
    {
        return new FeedbackDigitalRatingsExport(
            $this->applySorting($this->bulkQuery()),
            $this->exportPersonal,
        );
    }

    public function render()
    {
        $rows = $this->bulkQuery()
            ->with(['office:id,name', 'governorate:id,name', 'choices'])
            ->tap(fn ($q) => $this->applySorting($q))
            ->paginate(15);

        return view('livewire.feedback-results.digital-ratings', [
            'rows'      => $rows,
            'questions' => FeedbackDigitalRating::QUESTIONS,
        ]);
    }
}
