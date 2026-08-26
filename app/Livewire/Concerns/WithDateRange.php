<?php

namespace App\Livewire\Concerns;

use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * فلتر فترة (من/إلى) محفوظ في الرابط، ومعه اختصارات جاهزة.
 *
 * ⚠️ للعمود دالتان لا واحدة، والخلط بينهما يخفي صفوفاً:
 *   - applyDayRange   لعمود يوم كتبه المستخدم (`received_at` · `transferred_at`
 *     المصبوبان 'date') — يُقارَن كما هو بلا تحويل توقيت.
 *   - applyTimestampRange لعمود لحظة زمنية (`created_at` المخزَّن UTC) —
 *     يُحوَّل طرفا اليوم من توقيت القاهرة عبر LocalTime.
 */
trait WithDateRange
{
    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    /** اختصارات الفترة الجاهزة. */
    public const PERIODS = ['this_month', 'last_3_months', 'this_year'];

    /** نفس القائمة كدالة — القوالب لا تقرأ ثابت trait مباشرة. */
    public function periodOptions(): array
    {
        return self::PERIODS;
    }

    public function updatingDateFrom(): void
    {
        $this->afterFilterChange();
    }

    public function updatingDateTo(): void
    {
        $this->afterFilterChange();
    }

    /** حدود الاختصار بتوقيت القاهرة — «هذا الشهر» شهر المستخدم لا شهر UTC. */
    private function periodBounds(string $key): array
    {
        $now = CarbonImmutable::now(LocalTime::timezone());

        return match ($key) {
            'this_month'    => [$now->startOfMonth(), $now],
            'last_3_months' => [$now->subMonths(3), $now],
            'this_year'     => [$now->startOfYear(), $now],
            default         => [null, null],
        };
    }

    /** يضبط الفترة من اختصار جاهز؛ والضغط على المفعّل يلغيه. */
    public function setPeriod(string $key): void
    {
        if (! in_array($key, self::PERIODS, true) || $this->activePeriod() === $key) {
            $this->dateFrom = $this->dateTo = '';
            $this->afterFilterChange();

            return;
        }

        [$from, $to] = $this->periodBounds($key);

        $this->dateFrom = $from?->toDateString() ?? '';
        $this->dateTo   = $to?->toDateString() ?? '';
        $this->afterFilterChange();
    }

    /** الاختصار المطابق للفترة الحالية (لتمييز زره)؛ null لو الفترة مخصّصة. */
    public function activePeriod(): ?string
    {
        foreach (self::PERIODS as $key) {
            [$from, $to] = $this->periodBounds($key);

            if ($this->dateFrom === $from->toDateString() && $this->dateTo === $to->toDateString()) {
                return $key;
            }
        }

        return null;
    }

    public function hasDateFilter(): bool
    {
        return $this->dateFrom !== '' || $this->dateTo !== '';
    }

    /** يوم كتبه المستخدم، بلا تحويل توقيت؛ null لقيمة تالفة تصل من الرابط. */
    private function parseDay(string $value): ?CarbonImmutable
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse(trim($value));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * عمود يوم كتبه المستخدم — بلا تحويل توقيت.
     *
     * ⚠️ الحدّ الأعلى «أصغر من اليوم التالي» لا «أصغر من أو يساوي اليوم»:
     *    العمود من نوع DATE في MySQL لكن الصبّ يكتبه '2026-08-26 00:00:00'
     *    على sqlite، فمقارنة النص بـ'2026-08-26' تُسقط يوم النهاية كله.
     *    والصيغة المفتوحة تعمل على المحرّكين وتُبقي الفهرس مستعمَلاً.
     */
    protected function applyDayRange(Builder $query, string $column): Builder
    {
        $from = $this->parseDay($this->dateFrom);
        $to   = $this->parseDay($this->dateTo);

        return $query
            ->when($from, fn ($q) => $q->where($column, '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->where($column, '<', $to->addDay()->toDateString()));
    }

    /** عمود لحظة زمنية مخزَّن بـUTC — طرفا اليوم بتوقيت القاهرة. */
    protected function applyTimestampRange(Builder $query, string $column): Builder
    {
        $from = LocalTime::dayStart($this->dateFrom);
        $to   = LocalTime::dayAfter($this->dateTo);

        return $query
            ->when($from, fn ($q) => $q->where($column, '>=', $from))
            ->when($to, fn ($q) => $q->where($column, '<', $to));
    }

    protected function afterFilterChange(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
