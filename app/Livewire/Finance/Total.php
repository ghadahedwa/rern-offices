<?php

namespace App\Livewire\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceNote;
use App\Models\FinanceUnit;
use App\Support\Finance\FinanceAccess;
use App\Support\Finance\FinanceCalendar;
use App\Support\Finance\UnitsTotal;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * «الإجمالي العام» (المرحلة ٣ · نموذج وافقت عليه المستخدمة 2026-11-17): كارتٌ لكل حساب، ثم جدول
 * «الوحدات × الشهور» لكل حسابٍ تحت بعضها — حلّت محلّ صفحات الإجمالي الأربع.
 *
 * **لا إدخال هنا** — الشهور من صفحات الوحدات، والملاحظات وحدها تُكتب (بلا وحدة). مفتاح خانة الملاحظة
 * `حساب@Y-m` لأن الجداول الأربعة في صفحةٍ واحدة.
 */
#[Layout('layouts.app')]
class Total extends Component
{
    use Concerns\HandlesNotes;

    /** سنة بداية السنة المالية — تصل من الرابط فتُفحص */
    #[Url(as: 'year')]
    public ?int $year = null;

    // ── مودال حذف الملاحظة (المشترك) ──
    public bool $showDelete = false;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    #[Locked]
    public ?int $deletingId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(FinanceAccess::TOTAL_VIEW), 403);

        if ($this->year === null || ! FinanceCalendar::isValidFiscalYear($this->year)) {
            $this->year = FinanceCalendar::fiscalYearOf(FinanceCalendar::currentMonth());
        }
    }

    public function updatedYear(): void
    {
        if (! FinanceCalendar::isValidFiscalYear((int) $this->year)) {
            $this->year = FinanceCalendar::fiscalYearOf(FinanceCalendar::currentMonth());
        }
    }

    /** `حساب@Y-m` — ⚠️ الحساب من قائمة حسابات الوحدات وحدها، والتالف ٤٠٤ */
    protected function noteTarget(string $key): array
    {
        [$accountKey, $month] = array_pad(explode('@', $key, 2), 2, '');
        abort_unless(in_array($accountKey, FinanceAccount::UNIT_ACCOUNTS, true), 404);

        return [FinanceAccount::byKey($accountKey), self::monthFromKey($month)];
    }

    protected function noteAccountKeys(): array
    {
        return FinanceAccount::UNIT_ACCOUNTS;
    }

    protected function noteUnitId(): ?int
    {
        return null;
    }

    public function deleteRow(): void
    {
        if ($this->showDelete && $this->deletingNoteId !== null) {
            $this->deleteNote();
        }
    }

    public function render()
    {
        $user   = auth()->user();
        $months = FinanceCalendar::monthsOf($this->year);
        $units  = FinanceUnit::ordered()->get();

        $tables = FinanceAccount::unitAccounts()
            ->map(fn (FinanceAccount $account) => UnitsTotal::build($account, $this->year, $units))
            ->all();

        // ملاحظات الإجمالي (بلا وحدة) بمفتاح `حساب@Y-m`، وهل يضيف المستخدم على كل شهرٍ معروض
        $notes = FinanceNote::query()
            ->whereNull('finance_unit_id')
            ->whereHas('account', fn ($q) => $q->whereIn('key', FinanceAccount::UNIT_ACCOUNTS))
            ->whereBetween('month', [$months[0]->toDateString(), end($months)->toDateString().' 23:59:59'])
            ->with('author', 'account')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (FinanceNote $n) => $n->account->key.'@'.$n->month->format('Y-m'));

        $noteAdd = [];
        foreach ($tables as $table) {
            foreach ($table['rows'] as $row) {
                $noteAdd[$table['account']->key.'@'.$row['key']] = FinanceNote::canAdd($user, $table['account'], $row['month']);
            }
        }

        return view('livewire.finance.total', [
            'tables'   => $tables,
            'years'    => FinanceCalendar::fiscalYears(),
            'linked'   => FinanceAccess::viewableUnits($user)->pluck('id'),
            'yearFrom' => FinanceCalendar::monthLabel($months[0]),
            'yearTo'   => FinanceCalendar::monthLabel(end($months)),
            'notes'    => $notes,
            'noteAdd'  => $noteAdd,
        ])->title(__('home.fin_general_total'));
    }
}
