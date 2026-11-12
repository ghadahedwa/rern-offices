<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * أرقام حسابٍ مالي عن شهرٍ واحد — تُقفل لحظة حفظها (قرار العميل).
 *
 * ⚠️ `month` أول يوم الشهر، مصبوبٌ `'date'`: يومٌ لا لحظة، فلا تحويل توقيت.
 */
class FinanceMonth extends Model
{
    /** `amount`: المبلغ · إجمالي العناصر (أبنية المحاكم) · **المنصرف** (اتصالات، و`income` إيراده) */
    protected $fillable = ['finance_account_id', 'finance_unit_id', 'month', 'amount', 'income', 'created_by'];

    protected $casts = [
        'month'  => 'date',
        'amount' => 'integer',
        'income' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'finance_account_id');
    }

    /** الوحدة الحسابية — لأبنية المحاكم وحدها، والحسابات الثلاثة NULL */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(FinanceUnit::class, 'finance_unit_id');
    }

    /**
     * شهور «دفترٍ» واحد: الوحدة لأبنية المحاكم، وNULL للحسابات الثلاثة — كل ترتيبٍ وآخر شهرٍ
     * وتفرّدٍ يُحسب داخل الدفتر لا الحساب كله (كل وحدة ماشية بشهورها).
     */
    public function scopeForUnit(\Illuminate\Database\Eloquent\Builder $query, ?int $unitId): \Illuminate\Database\Eloquent\Builder
    {
        return $unitId === null
            ? $query->whereNull('finance_months.finance_unit_id')
            : $query->where('finance_months.finance_unit_id', $unitId);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentOrders(): HasMany
    {
        return $this->hasMany(FinancePaymentOrder::class)->orderBy('position')->chaperone('month');
    }

    /** عناصر أبنية المحاكم بترتيبها المعتمد — الحساب ذو العناصر وحده */
    public function items(): HasMany
    {
        return $this->hasMany(FinanceMonthItem::class)
            ->join('court_building_items', 'court_building_items.id', '=', 'finance_month_items.court_building_item_id')
            ->orderBy('court_building_items.order')
            ->orderBy('court_building_items.name')
            ->select('finance_month_items.*');
    }

    protected static function booted(): void
    {
        // ⚠️ الأوامر تُحذف بالموديل لا بـ`cascadeOnDelete` وحده: الأخير لا يُطلق حدث `deleted`
        //    على الصور فتبقى ملفاتها يتيمة على القرص
        static::deleting(function (self $month) {
            $month->paymentOrders()->get()->each->delete();
        });
    }
}
