<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseIssueItem extends Model
{
    protected $fillable = ['warehouse_issue_id', 'item_id', 'quantity'];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(WarehouseIssue::class, 'warehouse_issue_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
