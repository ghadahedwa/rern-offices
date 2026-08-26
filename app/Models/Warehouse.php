<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'governorate_id',
        'warehouse_type_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(WarehouseType::class, 'warehouse_type_id');
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    /**
     * ترتيب عرض المخازن المعتمد: **المستوى ثم المحافظة ثم الاسم**.
     *
     * ⚠️ يُستعمل في كل شاشة تعرض مخازن (قوائم وفلاتر ومنسدلات) — القاعدة واحدة،
     *    فالترتيب الأبجدي وحده يبعثر الرئيسي بين الفروع بلا معنى تنظيمي.
     *    وضعُه نطاقاً على الموديل يمنع تفرّق القاعدة على عشرة مواضع.
     *
     * ⚠️ الانضمامان `left` لا `inner`: محافظة المخزن اختيارية (المخزن الرئيسي
     *    بلا محافظة)، والانضمام الداخلي كان يُسقطه من كل قائمة.
     *
     * ⚠️ و`select('warehouses.*')` ضروري: بلا حصرٍ للأعمدة يطغى `id` الجدولين
     *    المنضمّين على `id` المخزن فيصير كل صف يحمل معرّف نوعه أو محافظته.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->withOrderingJoins()->applyDefaultOrdering();
    }

    /**
     * الترتيب وحده على استعلامٍ مُنضمٍّ سلفاً — مصدر واحد لقاعدة الترتيب
     * تقرأه scopeOrdered وتقرأه الشاشة التي ترجع للافتراضي بعد ترتيبٍ مخصّص.
     */
    public function scopeApplyDefaultOrdering(Builder $query): Builder
    {
        return $query
            ->orderBy('warehouse_types.level')
            ->orderBy('governorates.order')
            ->orderBy('governorates.name')
            ->orderBy('warehouses.name');
    }

    /**
     * الانضمامان وحدهما بلا ترتيب — لشاشةٍ يختار فيها المستخدم عمود الترتيب
     * (النوع أو المحافظة) فيحتاج الأعمدة المنضمّة دون فرض الترتيب الافتراضي.
     * الشرحان أعلاه (left ولا inner · حصر الأعمدة) يسريان عليه كما هما.
     */
    public function scopeWithOrderingJoins(Builder $query): Builder
    {
        return $query
            ->leftJoin('warehouse_types', 'warehouses.warehouse_type_id', '=', 'warehouse_types.id')
            ->leftJoin('governorates', 'warehouses.governorate_id', '=', 'governorates.id')
            ->select('warehouses.*');
    }

    /** المستوى الهرمي للمخزن (من نوعه) — 1=رئيسي. */
    public function level(): ?int
    {
        return $this->type?->level;
    }

    /** هل هو مخزن رئيسي (level=1)؟ الوارد يُسجَّل عليه فقط. */
    public function isMain(): bool
    {
        return $this->level() === 1;
    }
}
