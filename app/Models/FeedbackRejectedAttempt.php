<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackRejectedAttempt extends Model
{
    protected $fillable = [
        'type', 'national_id', 'phone', 'device_token', 'office_id',
        'reason', 'matched_by', 'matched_id', 'matched_at', 'ip_address', 'user_agent',
    ];

    /** مفاتيح المطابقة بترتيب العرض — `matched_by` يُخزَّن بها مفصولةً بفاصلة. */
    public const MATCH_KEYS = ['national_id', 'phone', 'device_token'];

    protected function casts(): array
    {
        return ['matched_at' => 'datetime'];
    }

    /** @return array<int, string> المفاتيح التي طابقت (فارغة لغير رفض التكرار أو للصفوف القديمة). */
    public function matchedKeys(): array
    {
        return $this->matched_by ? explode(',', $this->matched_by) : [];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
