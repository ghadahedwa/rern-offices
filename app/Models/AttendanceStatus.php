<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * حالة يوم في سجل الحضور — حاضر · غائب · إجازة، وما يضيفه المدير بعدها.
 */
class AttendanceStatus extends Model
{
    use HasFactory;

    protected $table = 'attendance_statuses';

    protected $fillable = ['name', 'color', 'order', 'is_active', 'is_system'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'order'     => 'integer',
    ];

    /** ترتيب العرض الواحد: الترتيب اليدوي ثم الاسم — تقرأه الشاشة والمنسدلة معاً. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /** المعروضة في شاشة التسجيل — المعطَّلة تبقى في السجلات القديمة ولا تُختار من جديد. */
    public function scopeSelectable(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * هل استُعملت هذه الحالة في سجل حضور؟
     *
     * ⚠️ سجل الحضور لم يُنشأ بعد، ففحص وجود الجدول ليس احتياطاً زائداً بل هو
     *    ما يجعل الحارس **حقيقياً لحظة إنشائه** بلا تعديلٍ يُنسى هنا.
     */
    public function isInUse(): bool
    {
        return Schema::hasTable('attendance_days')
            && DB::table('attendance_days')->where('status_id', $this->id)->exists();
    }
}
