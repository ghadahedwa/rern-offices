<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** أمر دفع صدرت به أرقام الشهر — رقمه وتاريخه وصورته (واحدة على الأقل). */
class FinancePaymentOrder extends Model
{
    /** سقف أوامر الدفع في الشهر الواحد — «غالباً» واحد، والاستثناء اثنان أو ثلاثة */
    public const MAX_PER_MONTH = 10;

    protected $fillable = ['finance_month_id', 'number', 'issued_on', 'position'];

    protected $casts = [
        'issued_on' => 'date',
        'position'  => 'integer',
    ];

    public function month(): BelongsTo
    {
        return $this->belongsTo(FinanceMonth::class, 'finance_month_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FinanceAttachment::class)->orderBy('id');
    }
}
