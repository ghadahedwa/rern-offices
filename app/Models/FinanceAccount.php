<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * حساب مالي — أربعة صفوف ثابتة تزرعها الهجرة، لا تُضاف ولا تُحذف من شاشة.
 *
 * الشكل (`kind`) يحدّد ما يُكتب كل شهر:
 *   - `single`   مبلغ واحد                     (الميكنة · وزارة المالية)
 *   - `itemized` مجموع عناصر                   (أبنية المحاكم — عناصرها في `CourtBuildingItem`)
 *   - `ledger`   إيراد ومنصرف برصيد مرحَّل       (اتصالات)
 *
 * ⚠️ الاسم المعروض في ملف اللغة (`fin_account_{key}`) لا في الجدول — قاعدة اللغة.
 */
class FinanceAccount extends Model
{
    public const COURT_BUILDINGS     = 'court_buildings';
    public const MECHANIZATION       = 'mechanization';
    public const TELECOM             = 'telecom';
    public const MINISTRY_OF_FINANCE = 'ministry_of_finance';

    public const KIND_SINGLE   = 'single';
    public const KIND_ITEMIZED = 'itemized';
    public const KIND_LEDGER   = 'ledger';

    protected $fillable = ['key', 'kind', 'order'];

    protected $casts = [
        'order' => 'integer',
    ];

    public static function byKey(string $key): self
    {
        return static::where('key', $key)->firstOrFail();
    }

    public function label(): string
    {
        return __('home.fin_account_'.$this->key);
    }

    /** المفتاح في الرابط — `ministry_of_finance` ← `ministry-of-finance`. */
    public function slug(): string
    {
        return str_replace('_', '-', $this->key);
    }

    public static function bySlug(string $slug): self
    {
        return static::byKey(str_replace('-', '_', $slug));
    }

    /** راوت صفحة الحساب — اسمه `finance.{slug}`. */
    public function pageRoute(): string
    {
        return 'finance.'.$this->slug();
    }

    /**
     * هل يُدخَل هذا الحساب؟ — الأشكال الثلاثة: المبلغ الواحد (ب) · أبنية المحاكم (د) · اتصالات (هـ).
     */
    public function acceptsEntry(): bool
    {
        return in_array($this->kind, [self::KIND_SINGLE, self::KIND_ITEMIZED, self::KIND_LEDGER], true);
    }

    public function isLedger(): bool
    {
        return $this->kind === self::KIND_LEDGER;
    }

    /** الرصيد الافتتاحي للوحدة — لكل وحدة رصيدها (المرحلة ٢، 2026-11-15) */
    public function openingFor(?int $unitId): ?FinanceOpening
    {
        if (! $this->isLedger() || $unitId === null) {
            return null;
        }

        return FinanceOpening::where('finance_account_id', $this->id)->where('finance_unit_id', $unitId)->first();
    }

    /**
     * اتصالات: لا شهر للوحدة قبل رصيدها الافتتاحي — عليه تُحسب أرصدتها كلها.
     * صفحة الإجمالي (بلا وحدة) لا دفتر لها فلا تطلبه.
     */
    public function needsOpening(?int $unitId): bool
    {
        return $this->isLedger() && $unitId !== null && $this->openingFor($unitId) === null;
    }

    public function isItemized(): bool
    {
        return $this->kind === self::KIND_ITEMIZED;
    }

    /**
     * الحسابات التي تُدخلها الوحدات الحسابية — كلٌّ لنفسها، وصفحة الحساب إجماليها (طلب العميل
     * 2026-11-15: الحسابات «زي أبنية المحاكم بالظبط»). اتصالات معها بالرصيد الافتتاحي لكل وحدة.
     */
    public const UNIT_ACCOUNTS = [self::COURT_BUILDINGS, self::MECHANIZATION, self::TELECOM, self::MINISTRY_OF_FINANCE];

    public function byUnits(): bool
    {
        return in_array($this->key, self::UNIT_ACCOUNTS, true);
    }

    /** مفاتيح الرابط لحسابات الوحدات — قائمة الراوتات البيضاء */
    public static function unitSlugs(): array
    {
        return array_map(fn ($k) => str_replace('_', '-', $k), self::UNIT_ACCOUNTS);
    }

    /** حسابات الوحدات بترتيبها — تبويبات صفحة الوحدة */
    public static function unitAccounts(): \Illuminate\Support\Collection
    {
        return static::whereIn('key', self::UNIT_ACCOUNTS)->orderBy('order')->get();
    }

    public function months(): HasMany
    {
        return $this->hasMany(FinanceMonth::class);
    }
}
