<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * صفة العامل المتعاقد — مدخل بيانات · مترجم · عامل · سائق · مساحي، وما يضيفه
 * المدير بعدها من شاشة «الصفات» بلا تعديل كود.
 *
 * ⚠️ الصفة **على العامل لا على تسكينه** (قرار العميلة 2026-09-06): تقريرٌ عن
 *    شهرٍ مضى يعرض صفته الحالية. ولو لزم تأريخها فمكانها عمودٌ على التسكين.
 */
class Profession extends Model
{
    use HasFactory;

    protected $table = 'professions';

    protected $fillable = ['name', 'order', 'is_active', 'is_system'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'order'     => 'integer',
    ];

    public function contractors(): HasMany
    {
        return $this->hasMany(Contractor::class);
    }

    /** ترتيب العرض الواحد: الترتيب اليدوي ثم الاسم — تقرأه الشاشة والمنسدلة معاً. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /** المعروضة في فورم الإضافة — المعطَّلة تبقى على أصحابها ولا تُختار من جديد. */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * هل عليها عاملون؟ — الحارس الفعلي للحذف.
     *
     * ⚠️ في الإجراء لا في القالب: الزرّ يُخفى في الشاشة، والنداء يصل بلا زرّ.
     */
    public function isInUse(): bool
    {
        return $this->contractors()->exists();
    }
}
