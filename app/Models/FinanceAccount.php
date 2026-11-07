<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
