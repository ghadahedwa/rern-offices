<?php

namespace App\Livewire\FeedbackResults\Concerns;

use App\Support\FeedbackResults\FeedbackAccess;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * تحديد جماعي وحذف لصفوف شاشات نتائج رأي المواطن (نمط بريد إلكتروني:
 * checkbox في كل صف + تحديد الصفحة + ترقية التحديد لكل نتائج الفلتر).
 *
 * المكوّن المستخدِم يوفّر:
 *   bulkModel() → اسم الموديل
 *   bulkQuery() → الاستعلام المفلتر نفسه الذي يعرضه render (بلا eager load أو ترتيب)
 *   bulkSubject() → وصف الشاشة لسجل النشاط
 *
 * الموديلات ذات SoftDeletes تُحذف إلى سلة (مع شاشة سلة واسترجاع)، وغيرها يُحذف نهائياً.
 */
trait WithBulkDelete
{
    /** الصفوف المحددة يدوياً — نصوص لأن الـ checkbox في الـ DOM يرسل قيمته نصاً. */
    public array $selected = [];

    /**
     * «حدّد كل المطابق للفلتر». لا يمرّ عبر $selected لأن العدد قد يبلغ الآلاف —
     * الحذف يُنفَّذ باستعلام واحد على نفس الفلتر.
     */
    public bool $selectAllMatching = false;

    /** عرض سلة المحذوفات بدل الصفوف الحية (الشاشات ذات SoftDeletes فقط). */
    #[Url(as: 'trashed', except: false)]
    public bool $showTrashed = false;

    /* حالة مودال التأكيد المشترك livewire.partials.delete-modal — نفس مودال حذف المقرات. */
    public bool $showDelete = false;
    public string $deletingPrompt = '';
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    /** الإجراء المنتظر تأكيده؛ ينفّذه deleteRow() الذي يستدعيه زر المودال. */
    public string $pendingBulkAction = '';

    public function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->bulkModel()), true);
    }

    /**
     * هل نعرض سلة المحذوفات الآن؟ **السلة لمن يملك الحذف وحده.**
     *
     * ⚠️ لا يكفي إخفاء الزر: `showTrashed` مربوطة بالـURL (`?trashed=1`)
     *    فتصل من العميل — والقراءة تمرّ من هنا لا من الخاصية مباشرة.
     */
    public function viewingTrash(): bool
    {
        return $this->showTrashed && $this->canDelete();
    }

    /** يُستدعى من bulkQuery في المكوّن: سلة المحذوفات أم الصفوف الحية. */
    protected function applyTrashScope(Builder $query): Builder
    {
        if (! $this->usesSoftDeletes()) {
            return $query;
        }

        return $this->viewingTrash() ? $query->onlyTrashed() : $query;
    }

    public function toggleTrashed(): void
    {
        if (! $this->canDelete()) {
            return;
        }

        $this->showTrashed = ! $this->showTrashed;
        $this->clearSelection();
        $this->resetPage();
    }

    /**
     * أي تعديل يدوي على التحديد يلغي «حدّد كل المطابق» — وإلا استثنى المستخدم
     * صفاً بصرياً بينما الحذف يمرّ على كل نتائج الفلتر.
     */
    public function updatedSelected(): void
    {
        $this->selectAllMatching = false;
    }

    /**
     * تغيّر الصفحة يمسح التحديد. الفلاتر والبحث تمرّ كلها بـ resetPage فتغطّيها
     * هذه النقطة نفسها — ومن دونها يبقى تحديد لصفوف خرجت من الشاشة أصلاً.
     */
    public function updatedPaginators(): void
    {
        $this->clearSelection();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAllMatching = false;
    }

    /** تحديد/إلغاء تحديد صفوف الصفحة الحالية دفعةً واحدة. */
    public function togglePage(array $ids): void
    {
        $ids = array_map('strval', $ids);

        $this->selected = $this->pageFullySelected($ids)
            ? array_values(array_diff($this->selected, $ids))
            : array_values(array_unique(array_merge($this->selected, $ids)));

        $this->selectAllMatching = false;
    }

    public function pageFullySelected(array $ids): bool
    {
        return $ids !== [] && array_diff($ids, array_map('strval', $this->selected)) === [];
    }

    public function hasPartialPageSelection(array $ids): bool
    {
        return ! $this->pageFullySelected($ids)
            && array_intersect($ids, array_map('strval', $this->selected)) !== [];
    }

    /** عدد ما سيُنفَّذ عليه الإجراء فعلاً — يظهر في الشريط وفي نص التأكيد. */
    public function selectedCount(): int
    {
        return $this->selectAllMatching
            ? $this->bulkQuery()->count()
            : count($this->selected);
    }

    public function markAllMatching(): void
    {
        $this->selectAllMatching = true;
    }

    /* الحذف يمرّ بمودال التأكيد المشترك؛ الاسترجاع فعل غير مدمّر فينفَّذ مباشرة. */

    public function askBulkDelete(): void
    {
        $this->askBulk('delete');
    }

    public function askBulkForceDelete(): void
    {
        $this->askBulk('forceDelete');
    }

    private function askBulk(string $action): void
    {
        $count = $this->selectedCount();

        if ($count === 0) {
            Flux::toast(variant: 'warning', text: __('home.fr_bulk_none_selected'));

            return;
        }

        // الحذف المنطقي وحده قابل للتراجع، فهو وحده بلا تنبيه أحمر
        $reversible = $this->usesSoftDeletes() && $action === 'delete';

        $this->pendingBulkAction = $action;
        $this->deletingPrompt    = $reversible ? __('home.fr_bulk_confirm_delete') : __('home.fr_bulk_confirm_purge');
        $this->deletingLabel     = __('home.fr_bulk_label', ['count' => $count, 'subject' => $this->bulkSubject()]);
        $this->deletingWarning   = $reversible ? '' : __('home.fr_bulk_warning_permanent');
        $this->showDelete        = true;
    }

    /** زر التأكيد في المودال المشترك. */
    public function deleteRow(): void
    {
        // مودال مغلق = لا تأكيد قائم؛ يمنع تنفيذ إجراء مؤجَّل عبر طلب مستقل
        $action = $this->showDelete ? $this->pendingBulkAction : '';

        $this->reset('showDelete', 'deletingPrompt', 'deletingLabel', 'deletingWarning', 'pendingBulkAction');

        if (in_array($action, ['delete', 'forceDelete'], true)) {
            $this->applyBulk($action);
        }
    }

    public function deleteSelected(): void
    {
        $this->applyBulk('delete');
    }

    public function restoreSelected(): void
    {
        $this->applyBulk('restore');
    }

    public function forceDeleteSelected(): void
    {
        $this->applyBulk('forceDelete');
    }

    /**
     * الاستعلام المستهدَف — دائماً مبنيّ على bulkQuery حتى لو جاء التحديد يدوياً،
     * فأي معرّف يُدسّ من العميل خارج نطاق الفلتر/الصلاحية لا يُمسّ.
     */
    private function bulkTargets(): ?Builder
    {
        $query = $this->bulkQuery();

        if ($this->selectAllMatching) {
            return $query;
        }

        $ids = array_values(array_filter(array_map('intval', $this->selected)));

        return $ids === [] ? null : $query->whereIn($query->getModel()->getQualifiedKeyName(), $ids);
    }

    /** للقالب: مَن له العرض بلا حذف لا يرى مربّعات التحديد ولا السلة أصلاً. */
    public function canDelete(): bool
    {
        return FeedbackAccess::canDelete(Auth::user());
    }

    private function applyBulk(string $action): void
    {
        // حارس ثانٍ: الإجراء يصل في طلب مستقل عن mount، فلا يكفي فحص الدخول
        // للشاشة ولا إخفاء الأزرار من القالب.
        abort_unless($this->canDelete(), 403);

        if ($action !== 'delete' && ! $this->usesSoftDeletes()) {
            abort(403);
        }

        $query = $this->bulkTargets();

        if (! $query) {
            Flux::toast(variant: 'warning', text: __('home.fr_bulk_none_selected'));

            return;
        }

        $wasAllMatching = $this->selectAllMatching;

        $count = (int) match ($action) {
            'delete'      => $query->delete(),
            'restore'     => $query->restore(),
            'forceDelete' => $query->forceDelete(),
        };

        // الحذف المنطقي وحده قابل للاسترجاع؛ ما عداه نهائي فتختلف رسالته
        $messageKey = match (true) {
            $action === 'restore'          => 'fr_bulk_restored',
            $action === 'forceDelete'      => 'fr_bulk_force_deleted',
            $this->usesSoftDeletes()       => 'fr_bulk_deleted',
            default                        => 'fr_bulk_purged',
        };

        $this->logBulk($action, $count, $wasAllMatching);

        $this->clearSelection();
        $this->resetPage();

        Flux::toast(variant: 'success', text: __('home.'.$messageKey, ['count' => $count]));
    }

    /**
     * سجل مساءلة: مَن حذف، وكم صفاً، وبأي فلتر. بلا subject حتى لا يظهر
     * في سجلّي داشبورد المقرات وإدارة النظام (كلاهما يفلتر على subject_type).
     */
    private function logBulk(string $action, int $count, bool $allMatching): void
    {
        $event = match ($action) {
            'delete'      => 'bulk_delete',
            'restore'     => 'bulk_restore',
            'forceDelete' => 'bulk_force_delete',
        };

        activity('feedback')
            ->causedBy(Auth::user())
            ->event($event)
            ->withProperties([
                'screen'       => $this->bulkSubject(),
                'count'        => $count,
                'all_matching' => $allMatching,
                'filters'      => array_filter([
                    'governorate_id' => $this->governorate_id,
                    'office_id'      => $this->office_id,
                    'from'           => $this->from,
                    'to'             => $this->to,
                    'search'         => $this->search,
                    'trashed'        => $this->viewingTrash(),
                ]),
            ])
            ->log(__('home.fr_bulk_log_'.$event).' — '.$this->bulkSubject());
    }
}
