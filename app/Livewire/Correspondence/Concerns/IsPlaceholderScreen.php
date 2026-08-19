<?php

namespace App\Livewire\Correspondence\Concerns;

/**
 * سقالة شاشة مراسلات لم تُبنَ بعد.
 *
 * الشاشات الخمس موجودة الآن **بحراسة صلاحياتها الحقيقية** وبمسارات صحيحة،
 * ليكون للفرع `default_route` قائم ولتُرى القائمة والعدّادات في مكانها.
 * ⚠️ محتواها مرهون بجداول المكاتبات (س٦ — مفتاح الترقيم).
 *
 * المكوّن يوفّر: `screenTitle()` · `screenAbility()` · `screenNote()`
 */
trait IsPlaceholderScreen
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->can($this->screenAbility()), 403);
    }

    public function render()
    {
        return view('livewire.correspondence.placeholder', [
            'screenTitle' => $this->screenTitle(),
            'screenNote'  => $this->screenNote(),
        ]);
    }

    /** نصّ يشرح ما ستفعله الشاشة — لا «قيد الإنشاء» المجرّدة. */
    protected function screenNote(): string
    {
        return __('home.corr_placeholder_note');
    }
}
