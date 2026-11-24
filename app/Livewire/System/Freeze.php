<?php

namespace App\Livewire\System;

use App\Support\SystemFreeze;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

/**
 * تجميد النظام — **سوبر أدمن وحده** (حارسا الراوت و`guard()`).
 * المنطق كله في App\Support\SystemFreeze؛ هنا المفتاح والمحاولات المحجوبة.
 */
#[Layout('layouts.app')]
#[Title('تجميد النظام')] // ⚠️ العنوان حرفيٌّ كباقي المكوّنات (#[Title] لا يقبل __())
class Freeze extends Component
{
    public string $contact = '';

    public bool $confirming = false;

    public function mount(): void
    {
        $this->guard();
        $this->contact = SystemFreeze::contact();
    }

    /** يفتح نافذة التأكيد بعد فحص الاسم. */
    public function confirmActivate(): void
    {
        $this->guard();
        $this->validate();
        $this->confirming = true;
    }

    public function activate(): void
    {
        $this->guard();

        // ⚠️ النداء يصل بلا نافذة — التفعيل بعد التأكيد وحده
        if (! $this->confirming) {
            return;
        }

        $this->validate();
        SystemFreeze::activate(Auth::user(), $this->contact);
        $this->confirming = false;

        Flux::toast(variant: 'success', text: __('home.freeze_activated'));
    }

    public function deactivate(): void
    {
        $this->guard();
        SystemFreeze::deactivate(Auth::user());

        Flux::toast(variant: 'success', text: __('home.freeze_deactivated'));
    }

    protected function rules(): array
    {
        return ['contact' => ['required', 'string', 'max:120']];
    }

    protected function validationAttributes(): array
    {
        return ['contact' => __('home.freeze_contact')];
    }

    private function guard(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    public function render()
    {
        $events = Activity::query()
            ->where('log_name', SystemFreeze::LOG)
            ->with('causer')
            ->latest('id')
            ->limit(100)
            ->get();

        return view('livewire.system.freeze', [
            'active' => SystemFreeze::isActive(),
            'since'  => SystemFreeze::activeSince(),
            'events' => $events,
        ]);
    }
}
