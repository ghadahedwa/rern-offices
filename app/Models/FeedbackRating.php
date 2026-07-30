<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'governorate_id', 'office_id',
        'name', 'national_id', 'phone',
        'wait_time',
        'rating_speed', 'rating_staff', 'rating_queue',
        'rating_cleanliness', 'rating_clarity', 'rating_accessibility',
        'overall_rating', 'notes',
        'ip_address', 'user_agent',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}
