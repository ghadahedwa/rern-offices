<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class WarehouseIncoming extends Model
{
    protected $fillable = [
        'warehouse_id',
        'received_at',
        'supplier_name',
        'attachment_path',
        'attachment_original_name',
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

    protected static function booted(): void
    {
        // حذف ملف المرفق من التخزين عند حذف السجل
        static::deleting(function (self $incoming) {
            if ($incoming->attachment_path) {
                Storage::disk('public')->delete($incoming->attachment_path);
            }
        });
    }
}
