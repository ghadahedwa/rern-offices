<?php

namespace App\Livewire\System;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * دليل التصميم الموحّد — **سوبر أدمن وحده** (حارسا الراوت و`mount()`).
 * الصفحة إطارٌ فقط: الدليل نفسه `documentation/design-guide.html` يُخدم كما هو من راوت
 * `design-guide.file` ويُعرض في iframe — فستايله وستايل النظام لا يتداخلان، وتعديل الملف يظهر فوراً.
 */
#[Layout('layouts.app')]
#[Title('دليل التصميم')] // ⚠️ العنوان حرفيٌّ كباقي المكوّنات (#[Title] لا يقبل __())
class DesignGuide extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    public function render()
    {
        return view('livewire.system.design-guide');
    }
}
