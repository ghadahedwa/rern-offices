<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * عنصر من عناصر حساب أبنية المحاكم — رقم الشهر مجموعها (قرار العميل: الثمانية إلزامية).
 *
 * قائمة مرجعية تحت «إدارة النظام»، فعنصرٌ تاسع يضيفه المدير بلا كود.
 * المعطَّل يختفي من فورم الشهر الجديد ويبقى في الشهور التي سُجِّل فيها.
 */
class CourtBuildingItem extends Model
{
    /** جدول قيم الشهور — يُنشأ مع حساب أبنية المحاكم (المرحلة د). */
    public const VALUES_TABLE = 'finance_month_items';

    protected $fillable = ['name', 'order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'order'     => 'integer',
    ];

    /** ترتيب العرض الواحد: الترتيب اليدوي ثم الاسم — تقرأه الشاشة وفورم الشهر معاً. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /** ما يُطلب في فورم الشهر الجديد. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * هل سُجِّلت له قيمة في شهرٍ ما؟ — الحارس الفعلي للحذف.
     *
     * ⚠️ يفحص وجود جدول القيم أولاً (نمط `AttendanceStatus::isInUse`): فيصير
     *    الحارس حقيقياً **لحظة إنشاء الجدول** بلا تعديلٍ يُنسى هنا.
     * ⚠️ عنصرٌ له قيمة لا يُحذف: حذفه يُنقص مجموع شهرٍ مقفول صدر به أمر دفع.
     */
    public function isInUse(): bool
    {
        return Schema::hasTable(self::VALUES_TABLE)
            && DB::table(self::VALUES_TABLE)->where('court_building_item_id', $this->id)->exists();
    }
}
