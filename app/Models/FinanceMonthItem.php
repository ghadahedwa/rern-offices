<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** قيمة عنصرٍ من عناصر أبنية المحاكم في شهر — الثمانية إلزامية والصفر مسموح. */
class FinanceMonthItem extends Model
{
    protected $fillable = ['finance_month_id', 'court_building_item_id', 'amount'];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function month(): BelongsTo
    {
        return $this->belongsTo(FinanceMonth::class, 'finance_month_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CourtBuildingItem::class, 'court_building_item_id');
    }
}
