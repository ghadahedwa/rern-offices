<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseIncomingItem extends Model
{
    protected $fillable = ['warehouse_incoming_id', 'item_id', 'quantity'];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function incoming(): BelongsTo
    {
        return $this->belongsTo(WarehouseIncoming::class, 'warehouse_incoming_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
