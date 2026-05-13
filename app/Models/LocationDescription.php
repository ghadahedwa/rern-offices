<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationDescription extends Model
{
    protected $table = 'location_descriptions';
    protected $fillable = ['name', 'shows_windows_count'];

    protected $casts = ['shows_windows_count' => 'boolean'];
}
