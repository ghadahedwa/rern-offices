<?php

namespace App\Livewire\DataEntry\Concerns;

/**
 * سقالة شاشة مدخلي بيانات لم تُبنَ بعد.
 *
 * الشاشات الثلاث موجودة الآن **بحراسة صلاحياتها الحقيقية** وبمسارات صحيحة،
 * ليكون للفرع `default_route` قائم وليُعلَّم دور المفتش بصلاحياتٍ تفتح شيئاً.
 * ⚠️ محتواها مرهون بجدولَي المدخلين والحضور — وهما موقوفان على شكل الحالات
 *    (حاضر/غائب/إجازة… وهل تُسجَّل ساعات فعلية).
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
        return view('livewire.data-entry.placeholder', [
            'screenTitle' => $this->screenTitle(),
            'screenNote'  => $this->screenNote(),
        ]);
    }

    /** نصّ يشرح ما ستفعله الشاشة — لا «قيد الإنشاء» المجرّدة. */
    protected function screenNote(): string
    {
        return __('home.de_placeholder_note');
    }
}
