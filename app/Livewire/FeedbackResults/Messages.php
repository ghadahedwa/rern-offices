<?php

namespace App\Livewire\FeedbackResults;

use App\Exports\FeedbackMessagesExport;
use App\Livewire\FeedbackResults\Concerns\WithBulkDelete;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackExport;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackFilters;
use App\Livewire\FeedbackResults\Concerns\WithFeedbackSorting;
use App\Models\FeedbackMessage;
use App\Support\FeedbackResults\FeedbackAccess;
use App\Support\FeedbackResults\MessagesQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * رسائل المواطنين لنوعٍ بعينه (شكر موظف · …) — شاشة واحدة والنوع من الراوت،
 * والتصدير (Excel · PDF) لكل الأنواع من هنا.
 */
#[Layout('layouts.app')]
class Messages extends Component
{
    use WithBulkDelete, WithFeedbackExport, WithFeedbackFilters, WithFeedbackSorting, WithPagination;

    /** ⚠️ مقفول: الحذف الجماعي يمرّ من bulkQuery المبني عليه. */
    #[Locked]
    public string $messageType = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?int $expanded = null;

    public function mount(string $messageType): void
    {
        abort_unless(FeedbackAccess::canViewMessages(Auth::user(), $messageType), 403);

        $this->messageType = $messageType;
    }

    /** الهوية إلزامية في بعض الأنواع، فالفلتر بلا معنى فيها. */
    public function showsIdentityFilter(): bool
    {
        return FeedbackMessage::config($this->messageType)['identity'] !== 'required';
    }

    protected function sortableColumns(): array
    {
        return MessagesQuery::SORTABLE;
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
        return FeedbackMessage::class;
    }

    protected function bulkSubject(): string
    {
        return __('home.fr_msg_'.$this->messageType);
    }

    /** الاستعلام المفلتر — مصدر واحد لما يُعرض ولما يُحذف جماعياً. */
    protected function bulkQuery(): Builder
    {
        return MessagesQuery::build($this->messageType, $this->filterSet(), Auth::user(), $this->search, $this->viewingTrash());
    }

    /* ── التصدير ── */

    /**
     * ⚠️ صلاحية التصدير **و** صلاحية النوع: الشاشة تُفحص في mount، والتصدير يصل في
     *    طلب مستقل — فمَن سُحب منه النوع والشاشة مفتوحة لا يُخرج ملفه.
     */
    public function canExport(): bool
    {
        return FeedbackAccess::canExport(Auth::user())
            && FeedbackAccess::canViewMessages(Auth::user(), $this->messageType);
    }

    protected function exportBaseName(): string
    {
        return 'feedback-'.$this->messageType;
    }

    protected function pdfRouteName(): ?string
    {
        return 'feedback-results.messages.pdf';
    }

    /** النوع معامل الراوت نفسه — route() يملؤه من المصفوفة ويجعل الباقي query string. */
    protected function extraExportParams(): array
    {
        return ['messageType' => $this->messageType];
    }

    protected function exportIsEmpty(): bool
    {
        return $this->bulkQuery()->count() === 0;
    }

    public function excelExport(): object
    {
        return new FeedbackMessagesExport(
            $this->applySorting($this->bulkQuery()),
            $this->messageType,
            $this->exportPersonal,
        );
    }

    public function render()
    {
        $messages = $this->bulkQuery()
            ->with(['office:id,name', 'governorate:id,name'])
            ->tap(fn ($q) => $this->applySorting($q))
            ->paginate(15);

        return view('livewire.feedback-results.messages', [
            'messages'    => $messages,
            'hasEmployee' => (bool) FeedbackMessage::config($this->messageType)['employee'],
        ])->title(__('home.fr_msg_'.$this->messageType));
    }
}
