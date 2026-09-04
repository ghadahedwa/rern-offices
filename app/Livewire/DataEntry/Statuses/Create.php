<?php

namespace App\Livewire\DataEntry\Statuses;

use App\Models\AttendanceStatus;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('حالة حضور')]
class Create extends Component
{
    /** لوحة ألوان مغلقة — لا حقل لون حرّ: اللون يُقرأ في تقويم صغير، والحرّية فيه
     *  تُخرج ألواناً لا تُقرأ على الخلفية الفاتحة أو الداكنة. */
    public const COLORS = [
        '#16a34a' => 'أخضر',
        '#dc2626' => 'أحمر',
        '#2563eb' => 'أزرق',
        '#c9a847' => 'ذهبي',
        '#7c3aed' => 'بنفسجي',
        '#71717a' => 'رمادي',
    ];

    public ?AttendanceStatus $attendanceStatus = null;

    public string $name = '';
    public string $color = '#71717a';
    public int $order = 0;
    public bool $is_active = true;

    public function mount(?AttendanceStatus $attendanceStatus = null): void
    {
        abort_unless(auth()->user()?->can('data-entry.settings'), 403);

        if ($attendanceStatus?->exists) {
            $this->attendanceStatus = $attendanceStatus;
            $this->name             = $attendanceStatus->name;
            $this->color            = $attendanceStatus->color;
            $this->order            = $attendanceStatus->order;
            $this->is_active        = $attendanceStatus->is_active;
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('data-entry.settings'), 403);

        $this->validate([
            'name'  => [
                'required', 'string', 'max:255',
                Rule::unique('attendance_statuses', 'name')->ignore($this->attendanceStatus?->id),
            ],
            'color' => ['required', Rule::in(array_keys(self::COLORS))],
            'order' => ['integer', 'min:0', 'max:999'],
        ]);

        $data = [
            'name'      => $this->name,
            'color'     => $this->color,
            'order'     => $this->order,
            // ⚠️ الحالة الأساسية لا تُعطَّل: تعطيل «حاضر» يترك شاشة التسجيل
            //    بلا الحالة التي يُسجَّل بها أغلب الأيام
            'is_active' => $this->attendanceStatus?->is_system ? true : $this->is_active,
        ];

        if ($this->attendanceStatus?->exists) {
            $this->attendanceStatus->update($data);
            Flux::toast(variant: 'success', text: __('home.de_status_updated'));
        } else {
            AttendanceStatus::create($data);
            Flux::toast(variant: 'success', text: __('home.de_status_created'));
        }

        $this->redirect(route('attendance-statuses.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.data-entry.statuses.create', ['colors' => self::COLORS]);
    }
}
