<?php

namespace App\Services;

use App\Models\FeedbackDigitalRating;
use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\FeedbackSuggestion;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * بوابة حماية بوابة رأي المواطن — مشتركة بين الفورمات الثلاثة (التقييم · المقترحات · المنصات الرقمية).
 * تفحص: التكرار (هوية أو بصمة جهاز خلال المدة) + سقف الـIP اليومي لكل مقر
 * + صمّام البوتات (IP في الدقيقة)، وتسجّل المرفوض.
 * الـ honeypot يُفحص داخل المكوّن (حقل مخفي). الحفظ يتم في المكوّن.
 */
class FeedbackGate
{
    public const TYPE_RATING = 'rating';
    public const TYPE_SUGGESTION = 'suggestion';
    public const TYPE_DIGITAL = 'digital';

    /** كل الأنواع — تقرؤها شاشة المحاولات المرفوضة في فلترها. */
    public const TYPES = [self::TYPE_RATING, self::TYPE_SUGGESTION, self::TYPE_DIGITAL];

    /**
     * جدول كل نوع. ⚠️ **النوع المجهول استثناء لا سقوط إلى التقييمات** — كان الكود
     * `suggestion ? … : Rating` فصار الفورم الثالث يُفحص تكراره على جدول التقييمات
     * صامتاً: يُحجب مَن قيّم الخدمة، ولا يُحجب مَن كرّر تقييم المنصات.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public function modelFor(string $type): string
    {
        return match ($type) {
            self::TYPE_RATING     => FeedbackRating::class,
            self::TYPE_SUGGESTION => FeedbackSuggestion::class,
            self::TYPE_DIGITAL    => FeedbackDigitalRating::class,
            default               => throw new \InvalidArgumentException("نوع رأي غير معروف: {$type}"),
        };
    }

    /**
     * لو وُجد إرسال سابق بنفس (الرقم القومي أو الهاتف أو بصمة الجهاز) + المقر
     * خلال المدة، تُرجع تاريخ السماح القادم (آخر إرسال + المدة)، وإلا null.
     *
     * الهوية صارت اختيارية، فالمفاتيح الثلاثة اختيارية هنا ويُبنى الشرط من
     * الموجود منها وحده. الهاتف معرّف شخصي (المواطن يُدخل رقمه هو حتى على جهاز
     * غيره)، وبصمة الجهاز مفتاح المجهول.
     *
     * ⚠️ **بلا مفتاح واحد على الأقل تُرجع null فوراً** — لا تُبنى مجموعة شرطٍ
     * فارغة: `where(fn ($q) => …)` بلا شروط تطابق **كل** الصفوف، فيصير أول
     * تقييم للمقر حاجزاً لكل المواطنين بعده.
     */
    public function duplicateRetryDate(string $type, ?string $nationalId, ?string $phone, ?string $deviceToken, int $officeId): ?CarbonInterface
    {
        $keys = array_filter([
            'national_id'  => (string) $nationalId,
            'phone'        => (string) $phone,
            'device_token' => (string) $deviceToken,
        ], fn ($value) => $value !== '');

        if ($keys === []) {
            return null;
        }

        $windowDays = (int) config('feedback.window_days', 7);
        $model = $this->modelFor($type);

        // withTrashed: الصف المحذوف إدارياً (سلة المحذوفات) يظل حارساً للنافذة.
        // بدونها يصير حذف رأي عبثي من شاشة النتائج إذناً لصاحبه بإعادة إرساله فوراً.
        $last = $model::query()
            ->withTrashed()
            ->where('office_id', $officeId)
            ->where(function ($q) use ($keys) {
                foreach ($keys as $column => $value) {
                    $q->orWhere($column, $value);
                }
            })
            ->where('created_at', '>=', now()->subDays($windowDays))
            ->latest('created_at')
            ->first();

        return $last ? $last->created_at->copy()->addDays($windowDays) : null;
    }

    /** هل تجاوز هذا الـ IP الحد المسموح في الدقيقة؟ (صمّام بوتات) */
    public function ipThrottled(string $ip): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->ipKey($ip),
            (int) config('feedback.ip_max_per_minute', 10),
        );
    }

    /** يحتسب محاولة على هذا الـ IP (نافذة دقيقة). */
    public function hitIp(string $ip): void
    {
        RateLimiter::hit($this->ipKey($ip), 60);
    }

    /**
     * سقف الآراء من خط إنترنت واحد لمقر واحد في اليوم.
     *
     * السور الخارجي حين تُمسح الكوكيز أو يُفتح متصفح آخر: بصمة الجهاز تتغيّر،
     * والـIP أثبت نسبياً. ⚠️ وهو **حدّ خشن لا قفل**: شركات المحمول تجمع آلاف
     * المشتركين على IP واحد (CGNAT)، فالسقف مرتفع عمداً حتى لا يُحجب مواطن
     * عادي — يوقف السيل لا التكرار المفرد.
     *
     * ⚠️ **يُطبَّق على كل إرسال، المُعرَّف والمجهول معاً.** الرقم القومي والهاتف
     * غير موثَّقين: لو استُثني المُعرَّف لتخطّى السقفَ مَن يكتب هاتفاً مختلَقاً
     * في كل مرة — وهو بالضبط مَن وُضع السقف له.
     */
    public function officeIpCapExceeded(string $ip, int $officeId): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->officeIpKey($ip, $officeId),
            (int) config('feedback.ip_max_per_day_per_office', 30),
        );
    }

    /** يحتسب إرسالاً ناجحاً على (IP + مقر) — نافذة يوم. */
    public function hitOfficeIpCap(string $ip, int $officeId): void
    {
        RateLimiter::hit($this->officeIpKey($ip, $officeId), 86400);
    }

    /** يسجّل محاولة مرفوضة دون احتسابها في النتائج. */
    public function logRejection(string $type, string $reason, ?string $nationalId, ?string $phone, ?int $officeId, Request $request, ?string $deviceToken = null): void
    {
        FeedbackRejectedAttempt::create([
            'type'         => $type,
            'national_id'  => $nationalId ?: null,
            'phone'        => $phone ?: null,
            'device_token' => $deviceToken ?: null,
            'office_id'    => $officeId,
            'reason'       => $reason,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);
    }

    private function ipKey(string $ip): string
    {
        return 'feedback:'.$ip;
    }

    private function officeIpKey(string $ip, int $officeId): string
    {
        return 'feedback:office:'.$ip.':'.$officeId;
    }
}
