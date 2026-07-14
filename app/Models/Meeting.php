<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class)->orderBy('order');
    }

    protected $fillable = [
        'date',
        'time',
        'subject',
        'location',
        'result',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
