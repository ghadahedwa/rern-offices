<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FeedbackSuggestion extends Model
{
    protected $fillable = [
        'governorate_id', 'office_id',
        'name', 'national_id', 'phone',
        'other_suggestion',
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

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(SuggestionTopic::class, 'feedback_suggestion_topic');
    }
}
