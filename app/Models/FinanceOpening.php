<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * الرصيد الافتتاحي لحسابٍ ذي رصيد (اتصالات) في وحدة حسابية — الرصيد في آخر سبتمبر ٢٠٢٦،
 * عليه تُحسب أرصدة شهور الوحدة كلها (`TelecomLedger`). يُكتب مرة ويُقفل، والتصحيح بسببٍ وسجلّ.
 */
class FinanceOpening extends Model
{
    protected $fillable = ['finance_account_id', 'finance_unit_id', 'amount', 'set_by', 'set_at'];

    protected $casts = [
        'amount' => 'integer',
        'set_at' => 'datetime',
    ];

    public function setter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }
}
