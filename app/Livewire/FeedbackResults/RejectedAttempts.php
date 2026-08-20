<?php

namespace App\Livewire\FeedbackResults;

use App\Exports\FeedbackRejectedExport;
use App\Livewire\FeedbackResults\Concerns\WithBulkDelete;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackExport;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Models\FeedbackRejectedAttempt;
use App\Services\FeedbackGate;
use App\Support\FeedbackResults\FeedbackAccess;
use App\Support\FeedbackResults\RejectedAttemptsQuery;
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
    use WithBulkDelete, WithFeedbackExport, WithFeedbackFilters, WithPagination;

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
        abort_unless(FeedbackAccess::canViewRejected(Auth::user()), 403);
    }

    /**
     * الشاشة كلها خلف `feedback.rejected`، فكل إجراء فيها يشترطها **فوق** صلاحيته.
     * ⚠️ الإجراءات تصل في طلبات مستقلة عن `mount`، فلا يكفي حارس الدخول.
     */
    public function canDelete(): bool
    {
        return FeedbackAccess::canDelete(Auth::user())
            && FeedbackAccess::canViewRejected(Auth::user());
    }

    protected function guardExport(): void
    {
        abort_unless(
            FeedbackAccess::canExport(Auth::user()) && FeedbackAccess::canViewRejected(Auth::user()),
            403,
        );
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

    protected function bulkModel(): string
    {
        return FeedbackRejectedAttempt::class;
    }

    protected function bulkSubject(): string
    {
        return __('home.fr_rejected');
    }

    /**
     * الاستعلام المفلتر — مصدر واحد لما يُعرض ولما يُحذف جماعياً ولما يُصدَّر.
     * لا سلة محذوفات هنا: الجدول يُنظَّف تلقائياً ولا علاقة له بمنع التكرار.
     */
    protected function bulkQuery(): Builder
    {
        return RejectedAttemptsQuery::build(
            $this->filterSet(), Auth::user(), $this->search, $this->reason, $this->type,
        );
    }

    /* ── التصدير — Excel فقط: قيمة هذه الشاشة تشغيلية لا تقريرية ── */

    protected function exportBaseName(): string
    {
        return 'feedback-rejected';
    }

    protected function exportIsEmpty(): bool
    {
        return $this->bulkQuery()->count() === 0;
    }

    protected function extraExportParams(): array
    {
        return array_filter(['reason' => $this->reason, 'type' => $this->type], fn ($v) => $v !== '');
    }

    public function excelExport(): object
    {
        return new FeedbackRejectedExport(
            $this->bulkQuery()->latest('created_at'),
            $this->exportPersonal,
        );
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
