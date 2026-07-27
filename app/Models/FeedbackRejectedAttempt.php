<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackRejectedAttempt extends Model
{
    protected $fillable = [
        'type', 'national_id', 'phone', 'office_id',
        'reason', 'ip_address', 'user_agent',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
