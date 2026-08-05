<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedbackRating extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * خيارات مدة الانتظار ومحاور التقييم — مصدر الحقيقة هنا (بيانات، لا واجهة)،
     * وتقرأها شاشة البوابة العامة وشاشات النتائج الإدارية معاً.
     * (النصوص العربية هنا مشمولة باستثناء قاعدة اللغة الخاص بالبوابة.)
     */
    public const WAIT_TIMES = [
        'under_15' => 'أقل من ١٥ دقيقة',
        '15_30'    => 'من ١٥ إلى ٣٠ دقيقة',
        '30_60'    => 'من ٣٠ إلى ٦٠ دقيقة',
        'over_60'  => 'أكثر من ساعة',
    ];

    /** نفس الخيارات بصيغة مختصرة — لخلايا الجداول حيث العرض محدود (العنوان الكامل في tooltip). */
    public const WAIT_TIMES_SHORT = [
        'under_15' => 'دون ١٥ د',
        '15_30'    => '١٥–٣٠ د',
        '30_60'    => '٣٠–٦٠ د',
        'over_60'  => 'فوق ساعة',
    ];

    /** المفتاح = اسم العمود، القيمة = [العنوان، اختياري؟] */
    public const CRITERIA = [
        'rating_speed'         => ['سرعة إنجاز المعاملة', false],
        'rating_staff'         => ['معاملة الموظفين وحُسن الاستقبال', false],
        'rating_queue'         => ['تنظيم الدور والانتظار', false],
        'rating_cleanliness'   => ['نظافة المقر وأماكن الانتظار', false],
        'rating_clarity'       => ['وضوح الإجراءات والرسوم', false],
        'rating_accessibility' => ['تيسير الخدمة لذوي الإعاقة وكبار السن', true],
    ];

    /**
     * أسماء المحاور في رؤوس أعمدة التقرير المطبوع، وبيانها الكامل أسفل الجدول.
     *
     * ⚠️ **هذه الكلمات مقيسة لا مختارة ذوقاً.** mpdf يوزّع أعمدة الجدول حسب
     * المحتوى ويتجاهل نِسَب width المعلَنة، وخلية المحور تحوي رقماً واحداً
     * فيضيّقها لأضيق حد — فالرأس ينكسر سطرين فوق عرض معيّن، ولا يمنعه توسيع
     * العمود ولا تصغير الخط ولا تقليل الهوامش ولا table-layout:fixed.
     * الحد المقيس ≈ عرض «التيسير»؛ «الموظف» و«الموظفون» يتجاوزانه (ظ+ف عريضتان)
     * ولذلك «التعامل» — وهي أدق للمحور أصلاً. لا تُطِل أياً منها بلا قياس:
     * الحارس RatingsPdfLayoutTest يبني الـPDF ويقيس ارتفاع صف الرؤوس.
     */
    public const CRITERIA_SHORT = [
        'rating_speed'         => 'السرعة',
        'rating_staff'         => 'التعامل',
        'rating_queue'         => 'الدور',
        'rating_cleanliness'   => 'النظافة',
        'rating_clarity'       => 'الوضوح',
        'rating_accessibility' => 'التيسير',
    ];

    protected $fillable = [
        'governorate_id', 'office_id',
        'name', 'national_id', 'phone',
        'wait_time',
        'rating_speed', 'rating_staff', 'rating_queue',
        'rating_cleanliness', 'rating_clarity', 'rating_accessibility',
        'overall_rating', 'notes',
        'ip_address', 'user_agent',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    /**
     * متوسط محاور التقييم لهذا الصف — يُحسب على المحاور المُجابة فقط.
     * المحور السادس (ذوو الإعاقة) اختياري، فاحتسابه كصفر يبوّظ المتوسط.
     */
    public function criteriaAverage(): ?float
    {
        $values = [];
        foreach (array_keys(self::CRITERIA) as $field) {
            if ($this->{$field} !== null) {
                $values[] = (int) $this->{$field};
            }
        }

        return $values === [] ? null : round(array_sum($values) / count($values), 2);
    }
}
