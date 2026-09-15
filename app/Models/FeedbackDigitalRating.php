<?php

namespace App\Models;

use App\Models\Concerns\HasFeedbackIdentity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * رأي مواطن في المنصات الرقمية — الفورم الثالث في بوابة رأي المواطن.
 *
 * الأسئلة منقولة من القسم الثاني في استمارة الوزارة الورقية (٢٠١–٢١٣) **بصياغتها
 * العامية كما هي** (قرار المستخدمة)، والمفاتيح أرقام الأسئلة نفسها.
 * (النصوص العربية هنا مشمولة باستثناء قاعدة اللغة الخاص بالبوابة.)
 */
class FeedbackDigitalRating extends Model
{
    use HasFactory, HasFeedbackIdentity, SoftDeletes;

    private const YES_NO = ['yes' => 'نعم', 'no' => 'لا'];

    private const PLATFORMS = [
        'misr_digital' => 'منصة مصر الرقمية',
        'tawkeel_app'  => 'تطبيق أرغب في عمل توكيل',
    ];

    /**
     * الأسئلة بترتيب الاستمارة. الأنواع:
     *  - radio    اختيار واحد (الأكواد الرقمية ١، ٢ في الورقة)
     *  - checkbox اختيار متعدد (الأكواد A، B في الورقة) — يُخزَّن في feedback_digital_choices
     *  - scale    درجة من min إلى max
     *  - text     نص حر (مربعات الترميز في الورقة) — اختياري دائماً
     */
    public const QUESTIONS = [
        'q201' => ['radio', 'يا ترى حجزت قبل ما تيجي ولا جيت بدون حجز؟', [
            'booked'  => 'حجزت',
            'walk_in' => 'بدون حجز',
        ]],
        'q202' => ['radio', 'يا ترى حجزت عن طريق منصة مصر الرقمية ولا تطبيق أرغب في عمل توكيل؟', self::PLATFORMS],
        'q203' => ['radio', 'هل قابلتك أي مشاكل وأنت بتحجز؟', self::YES_NO],
        'q204' => ['text', 'إيه هي المشاكل دي؟', null],
        'q205' => ['scale', 'لو طلبت منك تقييم (المنصة/التطبيق) تديله كام من عشرة بحيث إن صفر أقل درجة وعشرة أعلى درجة', [0, 10]],
        'q206' => ['radio', 'يا ترى لما روحت الشهر العقاري دخلت في ميعادك على طول ولا لأ؟', [
            'on_time' => 'دخلت في ميعادي',
            'waited'  => 'انتظرت بعد ميعادي',
        ]],
        'q207' => ['radio', 'انتظرت قد إيه؟', [
            'under_15' => 'أقل من ربع ساعة',
            '15_30'    => 'من ربع ساعة إلى أقل من نصف ساعة',
            '30_60'    => 'من نصف ساعة إلى أقل من ساعة',
            'over_60'  => 'أكثر من ساعة',
        ]],
        'q208' => ['radio', 'يا ترى تعرف إن فيه منصة للحجز؟', self::YES_NO],
        'q209' => ['checkbox', 'إيه هي المنصة أو التطبيق اللي تعرفه؟', self::PLATFORMS],
        'q210' => ['text', 'ليه ماستخدمتش المنصة/ التطبيق في الحجز؟', null],
        'q211' => ['radio', 'هل سمعت أو شاهدت الإعلان الخاص بالشهر العقاري؟', self::YES_NO],
        'q212' => ['checkbox', 'طيب إيه هو الإعلان؟', [
            'register_no_delay' => 'سجل وبلاش تأجل',
            'tawkeel_app'       => 'تطبيق أرغب في عمل توكيل',
        ]],
        'q213' => ['radio', 'يا ترى تقييمك إيه للحملة الإعلامية الخاصة بالشهر العقاري هل هي جيدة جداً أم جيدة أم سيئة أم سيئة جداً؟', [
            'very_good' => 'جيدة جداً',
            'good'      => 'جيدة',
            'bad'       => 'سيئة',
            'very_bad'  => 'سيئة جداً',
        ]],
    ];

    /**
     * متى يظهر كل سؤال — ترجمة عمود «انتقل إلى» في الورقة. q201 يظهر دائماً.
     * الشرط: [السؤال => الإجابة أو قائمة إجابات]، وكل الشروط مجتمعة.
     *
     * ⚠️ «انتقل إلى ٣٠١» = انتهاء القسم. أسئلة القسم الثالث تُضاف هنا حين تصل.
     */
    public const SHOWN_WHEN = [
        'q202' => ['q201' => 'booked'],
        'q203' => ['q201' => 'booked'],
        'q204' => ['q201' => 'booked', 'q203' => 'yes'],
        'q205' => ['q201' => 'booked'],
        'q206' => ['q201' => 'booked'],
        'q207' => ['q201' => 'booked', 'q206' => 'waited'],
        'q208' => ['q201' => 'walk_in'],
        'q209' => ['q201' => 'walk_in', 'q208' => 'yes'],
        'q210' => ['q201' => 'walk_in', 'q208' => 'yes'],
        'q211' => ['q201' => ['booked', 'walk_in']],   // المساران يلتقيان هنا
        'q212' => ['q211' => 'yes'],
        'q213' => ['q211' => 'yes'],
    ];

    protected $fillable = [
        'governorate_id', 'office_id',
        'name', 'national_id', 'phone', 'device_token',
        'q201', 'q202', 'q203', 'q204', 'q205', 'q206', 'q207', 'q208', 'q210', 'q211', 'q213',
        'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['q205' => 'integer'];
    }

    /**
     * الأسئلة الظاهرة لمجموعة إجابات — المصدر الواحد لما يُعرض ويُتحقق منه ويُحفظ.
     *
     * ⚠️ **الشرط يُقرأ من إجابة سؤالٍ ظاهر وحده** (السلسلة كاملة): إجابة q211 = نعم
     * بقيت في حالة الفورم من مسارٍ تُرك لا تُظهر q212. ولذلك الحلقة بترتيب الاستمارة،
     * فشرط كل سؤال يقع على سؤالٍ حُسمت ظاهريته قبله.
     *
     * @param  array<string, mixed>  $answers
     * @return array<int, string>
     */
    public static function visibleQuestions(array $answers): array
    {
        $visible = [];

        foreach (array_keys(self::QUESTIONS) as $key) {
            $conditions = self::SHOWN_WHEN[$key] ?? [];

            $shown = collect($conditions)->every(fn ($expected, $dependsOn) => in_array($dependsOn, $visible, true)
                && in_array($answers[$dependsOn] ?? null, (array) $expected, true));

            if ($shown) {
                $visible[] = $key;
            }
        }

        return $visible;
    }

    /**
     * مَن سُئل هذا السؤال — مقام أي نسبة عليه. **مشتقّ من SHOWN_WHEN لا مكتوب يدوياً**:
     * q204 = حجز · قابلته مشاكل. والشرط يُطبَّق بسلسلته كاملة (شرط السؤال ثم شرط ما يعتمد عليه).
     *
     * ⚠️ نسبة سؤالٍ مقامها كل الآراء تُخرج رقماً أصغر من الحقيقة ومضلِّلاً:
     * «٥٪ قابلتهم مشاكل» من ألف رأي، وهي «٢٥٪» من مئتي حاجز.
     *
     * 📌 تتبّع السلسلة **لا يغيّر نتيجة الأسئلة الحالية** — كل شرط في SHOWN_WHEN يذكر
     * سلسلته صراحةً (q207 يشترط الحجز بنفسه)، ولذلك لا يسقط اختبارٌ بحذفه. هو لأسئلة
     * القسم الثالث حين يشترط سؤالٌ سؤالاً مشروطاً دون تكرار شروطه.
     */
    public function scopeAsked(Builder $query, string $key): Builder
    {
        foreach (self::SHOWN_WHEN[$key] ?? [] as $dependsOn => $expected) {
            $query->whereIn($query->qualifyColumn($dependsOn), (array) $expected);
            $this->scopeAsked($query, $dependsOn);
        }

        return $query;
    }

    /** @return array<int, string> أسئلة نوع بعينه */
    public static function questionsOfType(string $type): array
    {
        return array_keys(array_filter(self::QUESTIONS, fn ($q) => $q[0] === $type));
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(FeedbackDigitalChoice::class);
    }

    /**
     * إجابات الصف كما خُزِّنت — الاختيار المتعدد من علاقة choices (حمّلها مسبقاً في القوائم).
     *
     * @return array<string, mixed>
     */
    public function answers(): array
    {
        $out = [];
        foreach (self::QUESTIONS as $key => [$type]) {
            $out[$key] = $type === 'checkbox' ? $this->choicesFor($key) : $this->{$key};
        }

        return $out;
    }

    /**
     * نص الإجابة للعرض، أو null لسؤال ظهر ولم يُجَب (النص الحر الاختياري).
     * الاختيار بمسمّاه لا بمفتاحه، والمتعدد مجمَّعاً، والدرجة «٧ / ١٠».
     */
    public function answerLabel(string $key): ?string
    {
        [$type, , $options] = self::QUESTIONS[$key];
        $value = $type === 'checkbox' ? $this->choicesFor($key) : $this->{$key};

        return match ($type) {
            'radio'    => $value !== null ? ($options[$value] ?? $value) : null,
            'checkbox' => $value !== [] ? implode('، ', array_map(fn ($v) => $options[$v] ?? $v, $value)) : null,
            'scale'    => $value !== null ? $value.' / '.$options[1] : null,
            'text'     => $value !== null && $value !== '' ? $value : null,
        };
    }

    /**
     * أسئلة هذا الصف التي سُئلها صاحبه — من إجاباته المخزَّنة. المخفي عنه خانته NULL أصلاً.
     *
     * @return array<int, string>
     */
    public function askedQuestions(): array
    {
        return self::visibleQuestions($this->answers());
    }

    /** @return array<int, string> مفاتيح الاختيارات المحفوظة لسؤال متعدد */
    public function choicesFor(string $question): array
    {
        return $this->choices->where('question', $question)->pluck('option')->values()->all();
    }
}
