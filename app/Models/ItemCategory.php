<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * قسم الأصناف — تصنيف الصنف الأعلى (مخزن التصوير · مخزن المستديم · الدفتر العقاري ...).
 *
 * ⚠️ القسم صفة على الصنف نفسه لا على المكان: حبر توشيبا يبقى «التصوير» في أي مخزن كان.
 * فهو لا يحمل رصيداً ولا حركة، ولا يدخل في أي حسبة كمية — وحدة التخزين تبقى (مخزن × صنف).
 */
class ItemCategory extends Model
{
    protected $fillable = ['name', 'order', 'is_active'];

    protected $casts = [
        'order'     => 'integer',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
