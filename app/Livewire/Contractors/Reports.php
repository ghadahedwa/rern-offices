<?php

namespace App\Livewire\Contractors;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * تقارير الحضور: فرد · مقر · محافظة · جمهورية.
 * ⚠️ لا صلاحية لمستوى التقرير — النطاق (`governorate_user`) هو الذي يحدّ ما يُرى،
 *    فمَن له محافظتان يجد «الجمهورية» محافظتيه. والتصدير وحده بصلاحيته.
 */
#[Layout('layouts.app')]
#[Title('تقارير الحضور')]
class Reports extends Component
{
    use Concerns\IsPlaceholderScreen;

    protected function screenTitle(): string
    {
        return __('home.ct_reports');
    }

    protected function screenAbility(): string
    {
        return 'contractors.index';
    }
}
