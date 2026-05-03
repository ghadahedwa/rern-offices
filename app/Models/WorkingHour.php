<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingHour extends Model
{
    protected $table = 'working_hours';
    protected $fillable = ['name'];
}
