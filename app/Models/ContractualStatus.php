<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractualStatus extends Model
{
    protected $table = 'contractual_statuses';
    protected $fillable = ['name'];
}
