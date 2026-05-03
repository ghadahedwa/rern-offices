<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CleanlinessContract extends Model
{
    protected $table = 'cleanliness_contracts';
    protected $fillable = ['name'];
}
