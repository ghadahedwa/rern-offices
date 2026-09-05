<?php

namespace App\Livewire\OfficialHolidays;

use App\Models\OfficialHoliday;
use App\Support\ArabicText;
use App\Support\WorkingDays;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * العطلات الرسمية — قائمة قومية واحدة، **للسوبر أدمن وحده** (قرار العميل).
 *
 * ⚠️ أضيق من `data-entry.settings` عمداً: عطلةٌ بتاريخ خاطئ تغيّر أيام العمل في
 *    تقارير الجمهورية كلها لا في محافظةٍ واحدة.
 */
#[Layout('layouts.app')]
#[Title('العطلات الرسمية')]
class Index extends Component
{
    use WithPagination;

    /**
     * العطلات ثابتة التاريخ — تُزرع بضغطة، وتبقى الهجرية والمُرحَّلة يدوية.
     *
     * ⚠️ شمّ النسيم ليس هنا: تاريخه يتبع عيد القيامة القبطي فيتحرّك كل سنة.
     */
    public const FIXED = [
        '01-07' => 'عيد الميلاد المجيد',
        '01-25' => 'عيد الشرطة وثورة ٢٥ يناير',
        '04-25' => 'عيد تحرير سيناء',
        '05-01' => 'عيد العمال',
        '06-30' => 'ثورة ٣٠ يونيو',
        '07-23' => 'ثورة ٢٣ يوليو',
        '10-06' => 'عيد القوات المسلحة (نصر أكتوبر)',
    ];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'year', except: '')]
    public string $year = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    public function mount(): void
    {
        $this->guard();

        if ($this->year === '') {
            $this->year = (string) WorkingDays::today()->year;
        }
    }

    /** الحارس في كل إجراء لا في `mount` وحدها — النداء يصل في طلب مستقل. */
    private function guard(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
    }

    /** سنوات العطلات المسجَّلة + السنة الحالية والتالية (للزرع المسبق). */
    public function yearOptions(): array
    {
        $current = WorkingDays::today()->year;

        $years = OfficialHoliday::query()
            ->selectRaw('DISTINCT '.$this->yearExpression().' as y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->all();

        $years = array_unique(array_merge($years, [$current, $current + 1]));
        rsort($years);

        return $years;
    }

    /**
     * ⚠️ استخراج السنة يختلف بين المحرّكين: MySQL بـYEAR() وsqlite (الاختبارات)
     *    بـstrftime — فلا يُكتب أيٌّ منهما مباشرة.
     */
    private function yearExpression(): string
    {
        return \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', starts_on) AS INTEGER)"
            : 'YEAR(starts_on)';
    }

    /** يزرع ثوابت السنة المعروضة، ويتجاهل المسجَّل منها — فالتكرار غير ممكن. */
    public function seedFixed(): void
    {
        $this->guard();

        $year = ctype_digit($this->year) ? (int) $this->year : WorkingDays::today()->year;

        $added = 0;

        foreach (self::FIXED as $monthDay => $name) {
            $date = $year.'-'.$monthDay;

            if (OfficialHoliday::whereDate('starts_on', $date)->exists()) {
                continue;
            }

            OfficialHoliday::create(['name' => $name, 'starts_on' => $date, 'ends_on' => $date]);
            $added++;
        }

        $this->resetPage();

        Flux::toast(
            variant: $added > 0 ? 'success' : 'warning',
            text: $added > 0
                ? __('home.de_holiday_seed_done', ['count' => $added])
                : __('home.de_holiday_seed_none')
        );
    }

    public function askDelete(int $id): void
    {
        $this->guard();

        $holiday = OfficialHoliday::findOrFail($id);

        $this->deletingId      = $holiday->id;
        $this->deletingLabel   = $holiday->name;
        $this->deletingWarning = __('home.de_holiday_delete_warning');
        $this->showDelete      = true;
    }

    public function deleteRow(): void
    {
        $this->guard();

        // ⚠️ لا يُنفَّذ إجراءٌ لم يُطلب تأكيده — النداء يصل بلا مودال
        if (! $this->showDelete || ! $this->deletingId) {
            return;
        }

        OfficialHoliday::findOrFail($this->deletingId)->delete();

        $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
        Flux::toast(variant: 'success', text: __('home.de_holiday_deleted'));
    }

    public function render()
    {
        $holidays = OfficialHoliday::query()
            ->when($this->search, fn ($q) => $q->whereRaw(
                ArabicText::sqlNormalize('name').' LIKE ?',
                ['%'.ArabicText::normalize($this->search).'%']
            ))
            // ⚠️ القيمة تصل من الرابط — التالفة تُهمَل ولا تُمرَّر
            ->when(ctype_digit($this->year), fn ($q) => $q->whereRaw($this->yearExpression().' = ?', [(int) $this->year]))
            ->ordered()
            ->paginate(20);

        return view('livewire.official-holidays.index', [
            'holidays' => $holidays,
            'years'    => $this->yearOptions(),
        ]);
    }
}
