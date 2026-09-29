<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseIncoming extends Model
{
    use Concerns\HasWarehouseAttachments;

    public const ATTACHMENT_TYPE = 'incoming';
    public const ATTACHMENT_DIR  = 'warehouses/incoming';

    protected $fillable = [
        'warehouse_id',
        'received_at',
        'supplier_name',
        'created_by',
    ];

    protected $casts = [
        'received_at' => 'date',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseIncomingItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
