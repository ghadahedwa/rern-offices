<?php

namespace App\Livewire\FeedbackResults\Concerns;

use App\Models\Governorate;
use App\Models\Office;
use App\Support\FeedbackResults\FeedbackAccess;
use App\Support\FeedbackResults\FeedbackFilterSet;
use App\Support\FeedbackResults\FeedbackScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * الفلاتر المشتركة لشاشات نتائج بوابة رأي المواطن (محافظة / مقر / فترة).
 * مربوطة بالـ URL حتى تبقى الحالة عند المشاركة أو الرجوع.
 *
 * التطبيق الفعلي للفلترة في App\Support\FeedbackResults\FeedbackFilterSet،
 * والـ trait يبنيه من خصائص المكوّن — عشان كنترولرات التصدير (خارج Livewire)
 * تفلتر بنفس الكود بالضبط، فلا يخرج الملف بأرقام تخالف الشاشة.
 *
 * ملاحظة نطاق: الفلاتر تضيّق النطاق ولا توسّعه — الحدّ الأعلى لما يراه المستخدم
 * يفرضه FeedbackScope داخل كلاسات الاستعلام، فقيمة `gov` مدسوسة في الرابط
 * لا تُخرج صفاً واحداً خارج محافظاته.
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

    /** الفلاتر الحالية ككائن مستقل — يشاركه المكوّن مع كنترولرات التصدير. */
    public function filterSet(): FeedbackFilterSet
    {
        return new FeedbackFilterSet(
            $this->governorate_id,
            $this->office_id,
            $this->from,
            $this->to,
        );
    }

    /**
     * يطبّق فلاتر المحافظة/المقر/الفترة على أي استعلام فيه
     * الأعمدة office_id / created_at (والمحافظة عبر filterByGovernorate).
     */
    protected function applyFilters(Builder $query): Builder
    {
        return $this->filterSet()->apply(
            $this->applyScope($query),
            fn ($q) => $this->filterByGovernorate($q),
        );
    }

    /** فلترة الفترة وحدها (لجدول لا تنطبق عليه بقية الفلاتر كما هي). */
    protected function applyDateRange(Builder $query): Builder
    {
        return $this->filterSet()->applyDateRange($query);
    }

    /** التاريخ يأتي من الـ URL فقد يكون تالفاً — نتجاهله بدل الانهيار. */
    protected function parsedDate(string $value): ?CarbonImmutable
    {
        return FeedbackFilterSet::parse($value);
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
     * نطاق رؤية المستخدم — يفوّض لـ FeedbackScope حتى تسري نفس الفلترة
     * على الشاشة وعلى الملف المصدَّر معاً.
     */
    protected function applyScope(Builder $query): Builder
    {
        return FeedbackScope::apply($query, Auth::user());
    }

    /** محافظات المستخدم — null لبلا حدّ (super-admin). تُقرأ مرة في الطلب. */
    protected function scopedGovernorateIds(): ?array
    {
        return FeedbackAccess::governorateIds(Auth::user());
    }

    /** قائمة المحافظات في الفلتر = ما يراه المستخدم فعلاً، لا كل المحافظات. */
    #[Computed]
    public function governorateOptions()
    {
        $allowed = $this->scopedGovernorateIds();

        return Governorate::when($allowed !== null, fn ($q) => $q->whereIn('id', $allowed))
            ->orderBy('order')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * مقرات المحافظة المختارة — كل المقرات لا العامة فقط، لأن رأياً قديماً
     * قد يكون معلّقاً على مقر تغيّر نوعه لاحقاً فيلزم أن يظل قابلاً للفلترة.
     *
     * ⚠️ مقيَّدة بنطاق المستخدم أيضاً: `?gov=` يأتي من الـURL، فبلا هذا القيد
     *    يقرأ مَن يعدّل الرابط أسماء مقرات محافظة ليست له (الأرقام مفلترة
     *    بـFeedbackScope، لكن القائمة نفسها كانت ستُبنى بلا قيد).
     */
    #[Computed]
    public function officeOptions()
    {
        if ($this->governorate_id === '') {
            return collect();
        }

        $allowed = $this->scopedGovernorateIds();

        if ($allowed !== null && ! in_array((int) $this->governorate_id, $allowed, true)) {
            return collect();
        }

        return Office::where('governorate_id', $this->governorate_id)
            ->orderBy('name')->get(['id', 'name']);
    }
}
