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
    protected $fillable = ['finance_account_id', 'month', 'amount', 'created_by'];

    protected $casts = [
        'month'  => 'date',
        'amount' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'finance_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentOrders(): HasMany
    {
        return $this->hasMany(FinancePaymentOrder::class)->orderBy('position')->chaperone('month');
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
