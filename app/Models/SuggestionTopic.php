<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SuggestionTopic extends Model
{
    protected $fillable = ['suggestion_domain_id', 'key', 'name', 'order'];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(SuggestionDomain::class, 'suggestion_domain_id');
    }

    public function suggestions(): BelongsToMany
    {
        return $this->belongsToMany(FeedbackSuggestion::class, 'feedback_suggestion_topic');
    }
}
