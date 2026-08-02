<?php

namespace App\Livewire\FeedbackResults\Concerns;

use App\Models\Governorate;
use App\Models\Office;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * الفلاتر المشتركة لشاشات نتائج بوابة رأي المواطن (محافظة / مقر / فترة).
 * مربوطة بالـ URL حتى تبقى الحالة عند المشاركة أو الرجوع.
 *
 * ملاحظة نطاق: الشاشات حالياً super-admin فقط، لكن applyScope() هو النقطة
 * الوحيدة اللي هتتوسّع فيها فلترة محافظات المستخدم عند فتح الموديول لأدوار أخرى.
 */
trait WithFeedbackFilters
{
    #[Url(as: 'gov', except: '')]
    public string $governorate_id = '';

    #[Url(as: 'office', except: '')]
    public string $office_id = '';

    #[Url(as: 'from', except: '')]
    public string $from = '';

    #[Url(as: 'to', except: '')]
    public string $to = '';

    public function updatedGovernorateId(): void
    {
        $this->office_id = '';
        $this->afterFilterChange();
    }

    public function updatedOfficeId(): void
    {
        $this->afterFilterChange();
    }

    public function updatedFrom(): void
    {
        $this->afterFilterChange();
    }

    public function updatedTo(): void
    {
        $this->afterFilterChange();
    }

    /** اختصارات الفترة الجاهزة. */
    public const PERIODS = ['this_month', 'last_3_months', 'this_year'];

    /**
     * نفس القائمة كدالة — القوالب لا تستطيع قراءة ثابت trait مباشرة
     * (PHP 8.2+ يمنع Trait::CONST خارج الكلاس المستخدِم).
     */
    public function periodOptions(): array
    {
        return self::PERIODS;
    }

    /** يضبط from/to من اختصار جاهز؛ الضغط على المفعّل يلغيه. */
    public function setPeriod(string $key): void
    {
        if ($this->activePeriod() === $key) {
            $this->from = $this->to = '';
            $this->afterFilterChange();

            return;
        }

        $now = CarbonImmutable::now();

        [$from, $to] = match ($key) {
            'this_month'    => [$now->startOfMonth(), $now],
            'last_3_months' => [$now->subMonths(3), $now],
            'this_year'     => [$now->startOfYear(), $now],
            default         => [null, null],
        };

        $this->from = $from?->toDateString() ?? '';
        $this->to   = $to?->toDateString() ?? '';
        $this->afterFilterChange();
    }

    /** أي اختصار مطابق للفترة الحالية (لتمييز الزر المفعّل)؛ null لو الفترة مخصّصة. */
    public function activePeriod(): ?string
    {
        foreach (self::PERIODS as $key) {
            $now = CarbonImmutable::now();
            [$from, $to] = match ($key) {
                'this_month'    => [$now->startOfMonth(), $now],
                'last_3_months' => [$now->subMonths(3), $now],
                'this_year'     => [$now->startOfYear(), $now],
            };

            if ($this->from === $from->toDateString() && $this->to === $to->toDateString()) {
                return $key;
            }
        }

        return null;
    }

    public function resetFilters(): void
    {
        $this->reset('governorate_id', 'office_id', 'from', 'to');
        $this->afterFilterChange();
    }

    /** هل في فلتر مفعّل حالياً؟ (لإظهار زر إعادة الضبط) */
    public function hasActiveFilters(): bool
    {
        return $this->governorate_id !== '' || $this->office_id !== ''
            || $this->from !== '' || $this->to !== '';
    }

    /** الصفحات ذات الـ pagination ترجع للصفحة الأولى عند تغيّر الفلتر. */
    protected function afterFilterChange(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * يطبّق فلاتر المحافظة/المقر/الفترة على أي استعلام فيه
     * الأعمدة office_id / created_at (والمحافظة عبر filterByGovernorate).
     */
    protected function applyFilters(Builder $query): Builder
    {
        return $this->applyDateRange($this->applyScope($query))
            ->when($this->governorate_id !== '', fn ($q) => $this->filterByGovernorate($q))
            ->when($this->office_id !== '', fn ($q) => $q->where('office_id', $this->office_id));
    }

    /**
     * فلترة الفترة كنطاق مفتوح على العمود نفسه.
     * ⚠️ لا تستخدم whereDate هنا: `DATE(created_at) >= ?` يطبّق دالة على العمود
     * فيمنع MySQL من استخدام الفهرس ويحوّل الفلترة لمسح كامل للجدول.
     * الحد الأعلى `< اليوم التالي` حتى يدخل يوم النهاية بالكامل (٢٣:٥٩ وما بعدها).
     */
    protected function applyDateRange(Builder $query): Builder
    {
        $from = $this->parsedDate($this->from);
        $to   = $this->parsedDate($this->to);

        return $query
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to->addDay()->startOfDay()));
    }

    /** التاريخ يأتي من الـ URL فقد يكون تالفاً — نتجاهله بدل الانهيار. */
    protected function parsedDate(string $value): ?CarbonImmutable
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * فلترة المحافظة — الافتراضي على العمود المخزَّن (التقييمات/المقترحات).
     * جدول المحاولات المرفوضة ليس فيه العمود، فيتجاوزها المكوّن عبر علاقة المقر.
     */
    protected function filterByGovernorate(Builder $query): Builder
    {
        return $query->where('governorate_id', $this->governorate_id);
    }

    /**
     * نطاق رؤية المستخدم. مفتوح حالياً (الشاشات super-admin فقط)؛
     * عند فتح الموديول لأدوار أخرى تُضاف هنا فلترة محافظات المستخدم.
     */
    protected function applyScope(Builder $query): Builder
    {
        return $query;
    }

    #[Computed]
    public function governorateOptions()
    {
        return Governorate::orderBy('order')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * مقرات المحافظة المختارة — كل المقرات لا العامة فقط، لأن رأياً قديماً
     * قد يكون معلّقاً على مقر تغيّر نوعه لاحقاً فيلزم أن يظل قابلاً للفلترة.
     */
    #[Computed]
    public function officeOptions()
    {
        if ($this->governorate_id === '') {
            return collect();
        }

        return Office::where('governorate_id', $this->governorate_id)
            ->orderBy('name')->get(['id', 'name']);
    }
}
