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

    protected $fillable = ['key', 'kind', 'order', 'opening_balance', 'opening_set_by', 'opening_set_at'];

    protected $casts = [
        'order'           => 'integer',
        'opening_balance' => 'integer',
        'opening_set_at'  => 'datetime',
    ];

    public function openingSetter(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'opening_set_by');
    }

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

    /** اتصالات: لا شهر قبل الرصيد الافتتاحي — عليه تُحسب الأرصدة كلها */
    public function needsOpening(): bool
    {
        return $this->isLedger() && $this->opening_balance === null;
    }

    public function isItemized(): bool
    {
        return $this->kind === self::KIND_ITEMIZED;
    }

    public function months(): HasMany
    {
        return $this->hasMany(FinanceMonth::class);
    }
}
