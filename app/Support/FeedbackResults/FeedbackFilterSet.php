<?php

namespace App\Support\FeedbackResults;

use App\Models\Governorate;
use App\Models\Office;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * فلاتر شاشات نتائج رأي المواطن (محافظة / مقر / فترة) ككائن مستقل.
 *
 * سبب وجوده: الشاشة مكوّن Livewire والتصدير كنترولر خارجه، ولو بنى كلٌّ منهما
 * الفلترة بنفسه لخرج الملف بأرقام غير التي على الشاشة. الكائن ده هو التطبيق
 * الوحيد للفلترة، يبنيه المكوّن من خصائصه ويبنيه الكنترولر من الـ Request.
 */
final class FeedbackFilterSet
{
    public function __construct(
        public readonly string $governorateId = '',
        public readonly string $officeId = '',
        public readonly string $from = '',
        public readonly string $to = '',
    ) {}

    /**
     * أسماء الـ query string هي نفسها أسماء الـ #[Url] في WithFeedbackFilters،
     * فرابط التصدير يحمل نفس فلاتر رابط الشاشة حرفياً.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            self::param($request, 'gov'),
            self::param($request, 'office'),
            self::param($request, 'from'),
            self::param($request, 'to'),
        );
    }

    /** القيمة من الـ URL قد تصل مصفوفة (?gov[]=1) — نتجاهلها بدل الانهيار. */
    private static function param(Request $request, string $key): string
    {
        $value = $request->query($key, '');

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * يطبّق الفلاتر على استعلام فيه office_id / created_at.
     * $governorateFilter لجدول بلا عمود governorate_id (المحاولات المرفوضة)،
     * فيمرّ عبر علاقة المقر بدل العمود.
     */
    public function apply(Builder $query, ?Closure $governorateFilter = null): Builder
    {
        return $this->applyDateRange($query)
            ->when($this->governorateId !== '', fn ($q) => $governorateFilter
                ? $governorateFilter($q)
                : $q->where('governorate_id', $this->governorateId))
            ->when($this->officeId !== '', fn ($q) => $q->where('office_id', $this->officeId));
    }

    /**
     * فلترة الفترة كنطاق مفتوح على العمود نفسه.
     * ⚠️ لا تستخدم whereDate هنا: `DATE(created_at) >= ?` يطبّق دالة على العمود
     * فيمنع MySQL من استخدام الفهرس ويحوّل الفلترة لمسح كامل للجدول.
     * الحد الأعلى `< اليوم التالي` حتى يدخل يوم النهاية بالكامل.
     */
    public function applyDateRange(Builder $query): Builder
    {
        $from = $this->parsedFrom();
        $to   = $this->parsedTo();

        return $query
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to->addDay()->startOfDay()));
    }

    public function parsedFrom(): ?CarbonImmutable
    {
        return self::parse($this->from);
    }

    public function parsedTo(): ?CarbonImmutable
    {
        return self::parse($this->to);
    }

    /** التاريخ يأتي من الـ URL فقد يكون تالفاً — نتجاهله بدل الانهيار. */
    public static function parse(string $value): ?CarbonImmutable
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

    public function isActive(): bool
    {
        return $this->governorateId !== '' || $this->officeId !== ''
            || $this->from !== '' || $this->to !== '';
    }

    /** الفلاتر كـ query string لبناء رابط التصدير. */
    public function toQuery(): array
    {
        return array_filter([
            'gov'    => $this->governorateId,
            'office' => $this->officeId,
            'from'   => $this->from,
            'to'     => $this->to,
        ], fn ($v) => $v !== '');
    }

    /**
     * وصف الفلتر المطبَّق كأزواج (تسمية، قيمة) لترويسة التقرير.
     * تقرير بلا سياق فلتره رقم بلا معنى — لذلك يظهر دائماً حتى لو بلا فلتر.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function describe(): array
    {
        $parts = [];

        if ($this->governorateId !== '') {
            $parts[] = [__('home.fr_governorate'), Governorate::find($this->governorateId)?->name ?? '—'];
        }

        if ($this->officeId !== '') {
            $parts[] = [__('home.fr_office'), Office::find($this->officeId)?->name ?? __('home.fr_deleted_office')];
        }

        $parts[] = [__('home.fr_export_period'), $this->describePeriod()];

        return $parts;
    }

    public function describePeriod(): string
    {
        $from = $this->parsedFrom()?->toDateString();
        $to   = $this->parsedTo()?->toDateString();

        return match (true) {
            $from !== null && $to !== null => __('home.fr_export_period_between', ['from' => $from, 'to' => $to]),
            $from !== null                 => __('home.fr_export_period_from', ['from' => $from]),
            $to !== null                   => __('home.fr_export_period_to', ['to' => $to]),
            default                        => __('home.fr_export_period_all'),
        };
    }
}
