<?php

namespace App\Support\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;

/**
 * تقويم الشئون المالية — المصدر الواحد لترتيب الشهور والسنة المالية.
 *
 *   - **الإدخال بترتيب الشهور** (قرار العميل): لا يُدخَل شهرٌ قبل الذي قبله،
 *     بدءاً من أكتوبر ٢٠٢٦. فالشهر التالي **يحسبه النظام** ولا يختاره المستخدم.
 *   - **السنة المالية يوليو–يونيو**، وتُسمّى بسنة بدايتها (٢٠٢٦ = ٢٠٢٦/٢٠٢٧).
 *     الأولى ناقصة (أكتوبر–يونيو) فتُعرض بمداها الفعلي.
 *   - **لا يُدخَل شهرٌ لم يبدأ** — الشهر الجاري مسموح (أمر دفعه قد يصدر داخله).
 *
 * ⚠️ «الشهر الجاري» بتوقيت القاهرة لا UTC: أول يوم الشهر بين ١٢ و٢ فجراً بالقاهرة
 *    ما زال آخر يوم الشهر السابق بـUTC.
 */
class FinanceCalendar
{
    public const FIRST_MONTH = '2026-10-01';

    public static function firstMonth(): CarbonImmutable
    {
        return CarbonImmutable::parse(self::FIRST_MONTH);
    }

    public static function currentMonth(): CarbonImmutable
    {
        return CarbonImmutable::parse(now(LocalTime::timezone())->format('Y-m-01'));
    }

    /** سنة بداية السنة المالية التي يقع فيها الشهر. */
    public static function fiscalYearOf(CarbonImmutable $month): int
    {
        return $month->month >= 7 ? $month->year : $month->year - 1;
    }

    /** السنوات المالية من أول سنة إلى الجارية — الأحدث أولاً. */
    public static function fiscalYears(): array
    {
        return range(self::fiscalYearOf(self::currentMonth()), self::fiscalYearOf(self::firstMonth()));
    }

    public static function isValidFiscalYear(int $year): bool
    {
        return in_array($year, self::fiscalYears(), true);
    }

    /** شهور السنة المالية — من يوليو (أو أول شهر في النظام) إلى يونيو. */
    public static function monthsOf(int $fiscalYear): array
    {
        $month = CarbonImmutable::create($fiscalYear, 7, 1)->max(self::firstMonth());
        $end   = CarbonImmutable::create($fiscalYear + 1, 6, 1);

        $months = [];
        for (; $month <= $end; $month = $month->addMonth()) {
            $months[] = $month;
        }

        return $months;
    }

    /**
     * الشهر الذي يُدخَل بعد آخر مُدخَل — أكتوبر ٢٠٢٦ إن لم يُدخَل شيء.
     * ⚠️ داخل دفتر الوحدة لأبنية المحاكم: كل وحدة ماشية بشهورها (قرار المستخدمة).
     */
    public static function nextMonth(FinanceAccount $account, ?int $unitId = null): CarbonImmutable
    {
        $last = FinanceMonth::where('finance_account_id', $account->id)->forUnit($unitId)->max('month');

        return $last
            ? CarbonImmutable::parse($last)->startOfMonth()->addMonth()
            : self::firstMonth();
    }

    /** هل بدأ الشهر فيُسمح بإدخاله؟ */
    public static function isDue(CarbonImmutable $month): bool
    {
        return $month <= self::currentMonth();
    }

    public static function monthLabel(CarbonImmutable $month): string
    {
        return __('home.fin_months.'.$month->month).' '.$month->year;
    }

    public static function fiscalYearLabel(int $fiscalYear): string
    {
        return $fiscalYear.'/'.($fiscalYear + 1);
    }
}
