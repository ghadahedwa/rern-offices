<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'governorate_id',
        'warehouse_type_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(WarehouseType::class, 'warehouse_type_id');
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    /** المستوى الهرمي للمخزن (من نوعه) — 1=رئيسي. */
    public function level(): ?int
    {
        return $this->type?->level;
    }

    /** هل هو مخزن رئيسي (level=1)؟ الوارد يُسجَّل عليه فقط. */
    public function isMain(): bool
    {
        return $this->level() === 1;
    }
}
