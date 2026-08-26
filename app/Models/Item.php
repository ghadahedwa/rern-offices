<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'name',
        'item_category_id',
        'code',
        'item_unit_id',
        'min_stock',
        'order',
        'is_active',
    ];

    protected $casts = [
        'min_stock' => 'integer',
        'order'     => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * ترتيب الدفتر الورقي: **القسم ثم ترتيب الصنف داخله ثم الاسم**،
     * والأصناف بلا قسم في الآخر.
     *
     * ⚠️ هو الترتيب المعتمد في كل عرضٍ للأصناف (شاشة الأصناف · الأرصدة ·
     *    بروفايل المخزن · منسدلات الاختيار)، لأن البيان المطبوع يجب أن يطابق
     *    الدفتر: الترتيب الأبجدي وحده يزيح سطوره مع كل صنف جديد يُضاف.
     *    ومَن أراد الأبجدي يضغط رأس عمود «الصنف» في الشاشة.
     *
     * ⚠️ يشترط أن يكون الاستعلام ضامّاً `items` و`item_categories`
     *    (استعمل scopeInStatementOrder لاستعلامٍ على الأصناف نفسها).
     */
    public static function statementOrder(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN items.item_category_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('item_categories.order')
            ->orderBy('item_categories.name')
            ->orderBy('items.order')
            ->orderBy('items.name');
    }

    /** استعلام أصناف بترتيب الدفتر — يضمّ الأقسام ويحصر الأعمدة على الأصناف. */
    public function scopeInStatementOrder(Builder $query): Builder
    {
        return self::statementOrder(
            $query
                ->leftJoin('item_categories', 'items.item_category_id', '=', 'item_categories.id')
                ->select('items.*')
        );
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class, 'item_unit_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }
}
