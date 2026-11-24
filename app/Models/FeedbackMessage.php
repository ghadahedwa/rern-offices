<?php

namespace App\Models;

use App\Models\Concerns\HasFeedbackIdentity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * رسالة مواطن نصية في البوابة — نوعها في `type` وتعريفه في TYPES.
 *
 * TYPES هو **المصدر الواحد** للفورم (ما يُطلب ونصوصه) والتحقق والحماية وشاشة النتائج.
 * النصوص هنا inline كـ`FeedbackRating::CRITERIA` (استثناء البوابة العامة في CLAUDE.md)،
 * أما تسميات الشاشات الإدارية فمفاتيح `fr_msg_*` في home.php.
 *
 *   identity  required | optional — الاسم والرقم القومي والهاتف
 *   office    هل المحافظة والمقر مطلوبان
 *   employee  required | optional | null — خانة اسم الموظف
 *   color     لون الشاشة من متغيّرات layouts/feedback
 *   access    view = صاحب feedback.view في محافظاته · super-admin = السوبر أدمن وحده (البلاغات)
 *   icon      أيقونة الشاشة في القائمة الجانبية (Flux)
 */
class FeedbackMessage extends Model
{
    use HasFactory, HasFeedbackIdentity, SoftDeletes;

    public const TYPE_THANKS = 'thanks';

    public const TYPES = [
        self::TYPE_THANKS => [
            'identity'          => 'required',
            'office'            => true,
            'employee'          => 'required',
            'color'             => 'thanks',
            'access'            => 'view',
            'icon'              => 'heart',
            'eyebrow'           => 'نقدّر كلمتك الطيبة',
            'title'             => ['شكر موظف', 'أو الإشادة بمتميز'],
            'lead'              => 'قدّم لك أحد الموظفين خدمة متميزة؟ سجّل له رسالة شكر تقديراً لتميّزه.',
            'employee_label'    => 'اسم الموظف',
            'employee_hint'     => 'الاسم كما عرفته، أو وصفه إن لم تعرف اسمه (مثل: موظف الشباك رقم ٣)',
            'body_label'        => 'رسالة الشكر',
            'body_placeholder'  => 'اكتب هنا ما قدّمه لك الموظف من خدمة متميزة...',
            'stage_hint'        => 'أدخل بياناتك واختر المحافظة والمقر لكتابة رسالة الشكر.',
            'submit'            => 'إرسال رسالة الشكر',
            'thanks_title'      => 'شكراً لك',
            'thanks_text'       => 'وصلت رسالة الشكر بنجاح. نقدّر حرصك على تقدير المتميزين.',
            'blocked_text'      => 'لا يتيح النظام إرسال أكثر من رسالة شكر لهذا المقر كل أسبوع.',
        ],
    ];

    protected $fillable = [
        'type', 'governorate_id', 'office_id',
        'name', 'national_id', 'phone', 'device_token',
        'employee_name', 'body',
        'ip_address', 'user_agent',
    ];

    /** مفاتيح الأنواع المفعَّلة — قيد الراوت وقائمة الحماية البيضاء. */
    public static function types(): array
    {
        return array_keys(self::TYPES);
    }

    public static function isType(string $type): bool
    {
        return array_key_exists($type, self::TYPES);
    }

    /** تعريف النوع — ⚠️ النوع المجهول استثناء لا سقوط إلى نوعٍ آخر. */
    public static function config(string $type): array
    {
        return self::TYPES[$type] ?? throw new \InvalidArgumentException("نوع رسالة غير معروف: {$type}");
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where($query->qualifyColumn('type'), $type);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }
}
