<?php

namespace App\Livewire\FeedbackResults;

use App\Livewire\FeedbackResults\Concerns\WithBulkDelete;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Models\FeedbackRejectedAttempt;
use App\Services\FeedbackGate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('المحاولات المرفوضة')]
class RejectedAttempts extends Component
{
    use WithBulkDelete, WithFeedbackFilters, WithPagination;

    /** أسباب الرفض كما يسجّلها FeedbackGate/الـ trait */
    public const REASONS = ['duplicate_window', 'rate_limit', 'honeypot'];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'reason', except: '')]
    public string $reason = '';

    #[Url(as: 'type', except: '')]
    public string $type = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedReason(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    /** الجدول ليس فيه governorate_id — نمرّ عبر علاقة المقر. */
    protected function filterByGovernorate(Builder $query): Builder
    {
        return $query->whereHas('office', fn ($q) => $q->where('governorate_id', $this->governorate_id));
    }

    protected function bulkModel(): string
    {
        return FeedbackRejectedAttempt::class;
    }

    protected function bulkSubject(): string
    {
        return __('home.fr_rejected');
    }

    /**
     * الاستعلام المفلتر — مصدر واحد لما يُعرض ولما يُحذف جماعياً.
     * لا سلة محذوفات هنا: الجدول يُنظَّف تلقائياً ولا علاقة له بمنع التكرار.
     */
    protected function bulkQuery(): Builder
    {
        return $this->applyFilters(FeedbackRejectedAttempt::query())
            ->when($this->reason !== '', fn ($q) => $q->where('reason', $this->reason))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->when($this->search !== '', function ($q) {
                $term = trim($this->search);
                $q->where(fn ($sub) => $sub->where('national_id', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('ip_address', 'like', "%{$term}%"));
            });
    }

    public function render()
    {
        return view('livewire.feedback-results.rejected-attempts', [
            'attempts'       => $this->bulkQuery()->with('office:id,name,governorate_id')->latest('created_at')->paginate(15),
            'reasonCounts'   => $this->bulkQuery()->selectRaw('reason, COUNT(*) as total')->groupBy('reason')->pluck('total', 'reason'),
            'retentionDays'  => (int) config('feedback.rejected_retention_days', 30),
            'types'          => [FeedbackGate::TYPE_RATING, FeedbackGate::TYPE_SUGGESTION],
        ]);
    }
}
