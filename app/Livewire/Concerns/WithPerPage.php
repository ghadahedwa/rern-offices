<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * عدد صفوف الصفحة، مختاراً من المستخدم ومحفوظاً في الرابط.
 *
 * السبب: ٣٧٧ صنفاً على ١٥ صفاً = ٢٥ صفحة، ومَن يراجع بياناً ورقياً
 * يحتاج الصفحة الواحدة أطول لا أن يتنقّل بينها.
 *
 * ⚠️ القيمة تأتي من الرابط فتُحصر في القائمة — الخاصية نصّية عمداً حتى لا
 *    تنهار عملية الترطيب على `?per=abc`، والتحويل لعدد يتم في perPage().
 */
trait WithPerPage
{
    public const PER_PAGE_OPTIONS = [15, 25, 50, 100];

    public const PER_PAGE_DEFAULT = 15;

    #[Url(as: 'per', except: '15')]
    public string $perPage = '15';

    public function updatingPerPage(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /** نفس القائمة كدالة — القوالب لا تقرأ ثابت trait مباشرة. */
    public function perPageOptions(): array
    {
        return self::PER_PAGE_OPTIONS;
    }

    protected function perPage(): int
    {
        return in_array((int) $this->perPage, self::PER_PAGE_OPTIONS, true)
            ? (int) $this->perPage
            : self::PER_PAGE_DEFAULT;
    }
}
