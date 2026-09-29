<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseTransfer extends Model
{
    use Concerns\HasWarehouseAttachments;

    public const ATTACHMENT_TYPE = 'transfer';
    public const ATTACHMENT_DIR  = 'warehouses/transfers';

    protected $fillable = [
        'from_warehouse_id',
        'to_warehouse_id',
        'transferred_at',
        'document_type',
        'created_by',
    ];

    protected $casts = [
        'transferred_at' => 'date',
    ];

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseTransferItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
