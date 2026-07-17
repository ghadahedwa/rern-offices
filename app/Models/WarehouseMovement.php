<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WarehouseMovement extends Model
{
    // created_at فقط (لا يوجد updated_at في هذا الجدول)
    const UPDATED_AT = null;

    protected $fillable = [
        'warehouse_id',
        'item_id',
        'type',
        'quantity',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'user_id',
    ];

    protected $casts = [
        'quantity'       => 'integer',
        'balance_before' => 'integer',
        'balance_after'  => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
