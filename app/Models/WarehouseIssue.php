<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * مستند صرف من مخزن إلى **مقر** — النوع الخامس من الحركة.
 *
 * ⚠️ المستلِم `Office` لا `Warehouse`، وهذا ما يفرّقه عن النقل: المقر نهاية
 *    الطريق، فالحركة تُنقص المخزن ولا تزيد مخزناً آخر.
 */
class WarehouseIssue extends Model
{
    use Concerns\HasWarehouseAttachments;

    public const ATTACHMENT_TYPE = 'issue';
    public const ATTACHMENT_DIR  = 'warehouses/issues';

    protected $fillable = [
        'warehouse_id',
        'office_id',
        'issued_at',
        'document_type',
        'created_by',
    ];

    protected $casts = [
        // يومٌ كتبه المستخدم — لا لحظة زمنية، فلا يُحوَّل بتوقيت
        'issued_at' => 'date',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseIssueItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
