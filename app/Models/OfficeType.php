<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeType extends Model
{
    protected $table = 'office_types';

    protected $fillable = ['name', 'is_public'];

    protected $casts = ['is_public' => 'boolean'];
}
