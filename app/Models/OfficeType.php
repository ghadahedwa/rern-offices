<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeType extends Model
{
    use HasFactory;

    protected $table = 'office_types';

    protected $fillable = ['name', 'is_public', 'has_contract_workers'];

    protected $casts = [
        'is_public'      => 'boolean',
        // هل هذا النوع مقرُّ عملٍ أصلاً؟ (استراحة · تحت الإنشاء · معلَّق · منتهٍ · أراضٍ = لا)
        'has_contract_workers' => 'boolean',
    ];
}
