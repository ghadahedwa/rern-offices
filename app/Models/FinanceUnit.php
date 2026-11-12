<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * وحدة حسابية — أبنية المحاكم مجموع ما تُدخله الوحدات، وكل وحدة تُدخل العناصر الثمانية لنفسها.
 *
 * قائمة مرجعية تحت «إدارة النظام» (سوبر أدمن وحده). المعطَّلة تختفي من الإدخال الجديد
 * وتبقى في الشهور التي سُجِّلت فيها.
 */
class FinanceUnit extends Model
{
    /** عمود الوحدة على شهور الحسابات — يُضاف مع إدخال الوحدات (الدفعة الثانية) */
    public const MONTHS_TABLE = 'finance_months';
    public const MONTHS_COLUMN = 'finance_unit_id';

    protected $fillable = ['name', 'order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'order'     => 'integer',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * الاسم المختصر لرأس عمودٍ ضيّق: «الوحدة الحسابية بطنطا» ← «طنطا»، و«…بالقاهرة» ← «القاهرة».
     * اسمٌ لا يبدأ بالبادئة يُعرض كما هو.
     */
    public function shortName(): string
    {
        $prefix = 'الوحدة الحسابية ';
        if (! str_starts_with($this->name, $prefix)) {
            return $this->name;
        }

        $rest = trim(mb_substr($this->name, mb_strlen($prefix)));

        return str_starts_with($rest, 'ب') ? mb_substr($rest, 1) : $rest;
    }

    /** ترتيب العرض الواحد: الترتيب اليدوي ثم الاسم */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * هل سُجِّل لها شهر؟ — الحارس الفعلي للحذف.
     *
     * ⚠️ يفحص وجود العمود أولاً (نمط `CourtBuildingItem::isInUse`): فيصير الحارس حقيقياً
     *    **لحظة إضافة العمود** في الدفعة الثانية بلا تعديلٍ يُنسى هنا.
     * ⚠️ وحدةٌ لها شهر لا تُحذف: حذفها يُنقص إجمالي أبنية المحاكم لشهرٍ مقفول.
     */
    public function isInUse(): bool
    {
        return Schema::hasColumn(self::MONTHS_TABLE, self::MONTHS_COLUMN)
            && DB::table(self::MONTHS_TABLE)->where(self::MONTHS_COLUMN, $this->id)->exists();
    }
}
