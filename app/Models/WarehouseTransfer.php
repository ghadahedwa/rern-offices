<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class WarehouseTransfer extends Model
{
    protected $fillable = [
        'from_warehouse_id',
        'to_warehouse_id',
        'transferred_at',
        'document_type',
        'attachment_path',
        'attachment_original_name',
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

    protected static function booted(): void
    {
        // حذف ملف المرفق من التخزين عند حذف السجل
        static::deleting(function (self $transfer) {
            if ($transfer->attachment_path) {
                Storage::disk('public')->delete($transfer->attachment_path);
            }
        });
    }
}
