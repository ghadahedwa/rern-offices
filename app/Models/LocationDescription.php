<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationDescription extends Model
{
    protected $table = 'location_descriptions';
    protected $fillable = ['name'];
}
