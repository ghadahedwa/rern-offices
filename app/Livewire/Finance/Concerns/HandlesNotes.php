<?php

namespace App\Livewire\Finance\Concerns;

use App\Models\FinanceAccount;
use App\Models\FinanceNote;
use App\Models\FinanceUnit;
use App\Support\Finance\FinanceHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;

/**
 * ملاحظات الشهور (المرحلة و) — صفحة الوحدة وصفحة «الإجمالي العام» تشتركان فيها. المكوّن يحدّد:
 *
 *   - `noteTarget($key)`     الحساب والشهر من مفتاح الخانة (صفحة الوحدة: `Y-m` · الإجمالي: `حساب@Y-m`)
 *   - `noteAccountKeys()`    حسابات الملاحظات التي تُفتح من هذه الصفحة
 *   - `noteUnitId()`         وحدة الصفحة، وNULL لملاحظات الإجمالي
 *
 * ويحتاج من المكوّن خصائص مودال الحذف المشترك (`showDelete` · `deletingLabel` · `deletingWarning` · `deletingId`).
 */
trait HandlesNotes
{
    // ── الملاحظات (المرحلة و) ──
    /** مسوّدة ملاحظةٍ جديدة لكل شهر: ['Y-m' => '…'] */
    public array $noteDrafts = [];

    #[Locked]
    public ?int $editingNoteId = null;

    public string $editingNoteBody = '';

    /** ملاحظةٌ مرشّحة للحذف — المودال المشترك نفسه بلا خانة سبب */
    #[Locked]
    public ?int $deletingNoteId = null;

    /** الشهر من مفتاح الرابط `Y-m` — التالف null */
    protected static function monthFromKey(string $key): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $key)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m', $key) ?: null;
    }

    protected function noteRules(string $field): array
    {
        return [$field => ['required', 'string', 'max:'.FinanceNote::MAX_LENGTH]];
    }

    public function addNote(string $key): void
    {
        [$account, $month] = $this->noteTarget($key);

        abort_unless($month && FinanceNote::canAdd(auth()->user(), $account, $month, $this->noteUnit()), 403);

        $body = trim((string) ($this->noteDrafts[$key] ?? ''));
        $this->noteDrafts[$key] = $body;
        $this->validate(
            $this->noteRules("noteDrafts.$key"),
            ["noteDrafts.$key.required" => __('home.fin_note_required')],
        );

        FinanceNote::create([
            'finance_account_id' => $account->id,
            'finance_unit_id'    => $this->noteUnitId(),
            'month'              => $month->toDateString(),
            'body'               => $body,
            'created_by'         => auth()->id(),
        ]);

        unset($this->noteDrafts[$key]);
    }

    protected function noteOf(int $id): FinanceNote
    {
        // ⚠️ معرّفٌ من المتصفح: ملاحظة حسابٍ آخر لا تُفتح من صفحة هذا الحساب
        $note = FinanceNote::with('account')
            ->whereKey($id)
            ->whereHas('account', fn ($q) => $q->whereIn('key', $this->noteAccountKeys()))
            // ⚠️ وملاحظة وحدةٍ أخرى (أو الإجمالي) لا تُفتح من صفحة هذه الوحدة
            ->when($this->noteUnitId() === null, fn ($q) => $q->whereNull('finance_unit_id'), fn ($q) => $q->where('finance_unit_id', $this->noteUnitId()))
            ->first();
        abort_if($note === null, 404);

        return $note;
    }

    public function startEditNote(int $id): void
    {
        $note = $this->noteOf($id);
        abort_unless($note->canBeEditedBy(auth()->user()), 403);

        $this->editingNoteId   = $note->id;
        $this->editingNoteBody = $note->body;
        $this->resetErrorBag();
    }

    public function cancelEditNote(): void
    {
        $this->editingNoteId   = null;
        $this->editingNoteBody = '';
    }

    /** ⚠️ الصلاحية تُفحص من جديد: الشهر قد يُقفل والتعديل مفتوح، فيصير لصاحب `finance.edit` وحده */
    public function saveNote(): void
    {
        if ($this->editingNoteId === null) {
            return;
        }

        $note = $this->noteOf($this->editingNoteId);
        abort_unless($note->canBeEditedBy(auth()->user()), 403);

        $this->editingNoteBody = trim($this->editingNoteBody);
        $this->validate($this->noteRules('editingNoteBody'), ['editingNoteBody.required' => __('home.fin_note_required')]);

        if ($note->body !== $this->editingNoteBody) {
            DB::transaction(function () use ($note) {
                if ($note->isLocked()) {
                    FinanceHistory::recordNote(FinanceHistory::EVENT_NOTE_EDITED, $note->account, $note->month, $note->body, $this->editingNoteBody, $note->finance_unit_id);
                }
                $note->update(['body' => $this->editingNoteBody]);
            });
        }

        $this->cancelEditNote();
    }

    public function confirmDeleteNote(int $id): void
    {
        $note = $this->noteOf($id);
        abort_unless($note->canBeDeletedBy(auth()->user()), 403);

        $this->deletingId      = null;
        $this->deletingNoteId  = $note->id;
        $this->deletingLabel   = \Illuminate\Support\Str::limit($note->body, 80);
        $this->deletingWarning = '';
        $this->showDelete      = true;
    }

    protected function deleteNote(): void
    {
        $note = $this->noteOf($this->deletingNoteId);
        abort_unless($note->canBeDeletedBy(auth()->user()), 403);

        DB::transaction(function () use ($note) {
            if ($note->isLocked()) {
                FinanceHistory::recordNote(FinanceHistory::EVENT_NOTE_DELETED, $note->account, $note->month, $note->body, null, $note->finance_unit_id);
            }
            $note->delete();
        });

        $this->showDelete     = false;
        $this->deletingNoteId = null;
    }


    abstract protected function noteTarget(string $key): array;

    abstract protected function noteAccountKeys(): array;

    abstract protected function noteUnitId(): ?int;

    protected function noteUnit(): ?FinanceUnit
    {
        return $this->noteUnitId() ? FinanceUnit::find($this->noteUnitId()) : null;
    }
}
