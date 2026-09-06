<?php

namespace App\Livewire\Contractors;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * ⚠️ صلاحيتها `contractors.attendance` لا `contractors.index` — التسجيل اليومي
 *    قد يُسنَد لمن لا يملك تعديل بيانات العاملين، والعكس.
 */
#[Layout('layouts.app')]
#[Title('تسجيل الحضور')]
class Attendance extends Component
{
    use Concerns\IsPlaceholderScreen;

    protected function screenTitle(): string
    {
        return __('home.ct_attendance');
    }

    protected function screenAbility(): string
    {
        return 'contractors.attendance';
    }
}
