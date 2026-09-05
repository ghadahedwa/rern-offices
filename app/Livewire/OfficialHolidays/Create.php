<?php

namespace App\Livewire\OfficialHolidays;

use App\Models\AttendanceDay;
use App\Models\OfficialHoliday;
use App\Support\WorkingDays;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * إضافة عطلة رسمية أو تعديلها — والترحيل تعديلُ تاريخٍ لا صفٌّ جديد.
 *
 * ⚠️ **حارس الإضافة بأثر رجعي**: الشاشة مفتوحة والقرار يصل بعد وقوعه، فقد يكون
 *    المفتش سجّل غياباً في يومٍ صار عطلة. اليوم حينها مخصوم من أيام العمل، فسجلّه
 *    يخصمه مرة ثانية. لذلك تُعرَض السجلات المتأثرة ولا يُحفظ إلا بموافقة تحذفها.
 */
#[Layout('layouts.app')]
#[Title('عطلة رسمية')]
class Create extends Component
{
    public ?OfficialHoliday $officialHoliday = null;

    public string $name = '';
    public string $starts_on = '';
    public string $ends_on = '';

    /** حالة مودال التعارض: عدد السجلات الواقعة داخل مدى العطلة. */
    public bool $showConflict = false;
    public int $conflictCount = 0;

    public function mount(?OfficialHoliday $officialHoliday = null): void
    {
        $this->guard();

        if ($officialHoliday?->exists) {
            $this->officialHoliday = $officialHoliday;
            $this->name            = $officialHoliday->name;
            $this->starts_on       = $officialHoliday->starts_on->toDateString();
            $this->ends_on         = $officialHoliday->ends_on->toDateString();

            return;
        }

        // «اليوم» بتوقيت القاهرة لا بـUTC — وإلا فتحت الشاشة على تاريخ الأمس فجراً.
        $this->starts_on = WorkingDays::today()->toDateString();
        $this->ends_on   = $this->starts_on;
    }

    private function guard(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    /** يوم واحد هو الحالة الغالبة — فتتبع النهايةُ البدايةَ ما لم يمدّها المستخدم. */
    public function updatedStartsOn(): void
    {
        if ($this->ends_on === '' || $this->ends_on < $this->starts_on) {
            $this->ends_on = $this->starts_on;
        }
    }

    public function save(): void
    {
        $this->guard();
        $this->validateForm();

        $conflicts = $this->conflictQuery()->count();

        if ($conflicts > 0) {
            $this->conflictCount = $conflicts;
            $this->showConflict  = true;

            return;
        }

        $this->persist();
    }

    /** الحفظ بعد الموافقة على حذف السجلات المتعارضة. */
    public function confirmSave(): void
    {
        $this->guard();

        // ⚠️ لا يُنفَّذ إجراءٌ لم يُطلب تأكيده — النداء يصل في طلب مستقل بلا مودال
        if (! $this->showConflict) {
            return;
        }

        $this->validateForm();
        $this->persist(deleteConflicts: true);
    }

    private function validateForm(): void
    {
        $this->validate([
            'name'      => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on'   => ['required', 'date', 'after_or_equal:starts_on'],
        ]);
    }

    /** سجلات الغياب/الإجازة الواقعة داخل مدى العطلة الجديد. */
    private function conflictQuery()
    {
        return AttendanceDay::query()
            ->whereDate('date', '>=', $this->starts_on)
            ->whereDate('date', '<=', $this->ends_on);
    }

    private function persist(bool $deleteConflicts = false): void
    {
        $data = [
            'name'      => $this->name,
            'starts_on' => $this->starts_on,
            'ends_on'   => $this->ends_on,
        ];

        DB::transaction(function () use ($data, $deleteConflicts) {
            if ($deleteConflicts) {
                $this->conflictQuery()->delete();
            }

            if ($this->officialHoliday?->exists) {
                $this->officialHoliday->update($data);
            } else {
                OfficialHoliday::create($data);
            }
        });

        Flux::toast(
            variant: 'success',
            text: $this->officialHoliday?->exists
                ? __('home.de_holiday_updated')
                : __('home.de_holiday_created')
        );

        $this->redirect(route('official-holidays.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.official-holidays.create');
    }
}
