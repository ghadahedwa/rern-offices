<?php

namespace App\Livewire\DataEntry;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * ⚠️ صلاحيتها `data-entry.attendance` لا `data-entry.index` — التسجيل اليومي
 *    قد يُسنَد لمن لا يملك تعديل بيانات المدخلين، والعكس.
 */
#[Layout('layouts.app')]
#[Title('تسجيل الحضور')]
class Attendance extends Component
{
    use Concerns\IsPlaceholderScreen;

    protected function screenTitle(): string
    {
        return __('home.de_attendance');
    }

    protected function screenAbility(): string
    {
        return 'data-entry.attendance';
    }
}
