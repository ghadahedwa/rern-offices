<?php

namespace App\Services;

use App\Models\FeedbackRating;
use App\Models\FeedbackRejectedAttempt;
use App\Models\FeedbackSuggestion;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * بوابة حماية بوابة رأي المواطن — مشتركة بين التقييم والمقترحات.
 * تفحص: التكرار (رقم قومي/هاتف خلال المدة) + حد الجهاز (IP) + تسجّل المرفوض.
 * الـ honeypot يُفحص داخل المكوّن (حقل مخفي). الحفظ يتم في المكوّن.
 */
class FeedbackGate
{
    public const TYPE_RATING = 'rating';
    public const TYPE_SUGGESTION = 'suggestion';

    /**
     * لو وُجد إرسال سابق لنفس (الرقم القومي أو الهاتف) + المقر خلال المدة،
     * تُرجع تاريخ السماح القادم (آخر إرسال + المدة)، وإلا null (مسموح).
     * الهاتف معرّف شخصي (المواطن يُدخل رقمه هو حتى على جهاز غيره)، فيصلح كطبقة قفل ثانية.
     * (ملاحظة: يبقى احتمال حجب من يتشاركون رقماً واحداً — عائلة/مساعِد — وهو نادر ومقبول.)
     */
    public function duplicateRetryDate(string $type, string $nationalId, string $phone, int $officeId): ?CarbonInterface
    {
        $windowDays = (int) config('feedback.window_days', 7);
        $model = $type === self::TYPE_SUGGESTION ? FeedbackSuggestion::class : FeedbackRating::class;

        // withTrashed: الصف المحذوف إدارياً (سلة المحذوفات) يظل حارساً للنافذة.
        // بدونها يصير حذف رأي عبثي من شاشة النتائج إذناً لصاحبه بإعادة إرساله فوراً.
        $last = $model::query()
            ->withTrashed()
            ->where('office_id', $officeId)
            ->where(function ($q) use ($nationalId, $phone) {
                $q->where('national_id', $nationalId);
                if ($phone !== '') {
                    $q->orWhere('phone', $phone);
                }
            })
            ->where('created_at', '>=', now()->subDays($windowDays))
            ->latest('created_at')
            ->first();

        return $last ? $last->created_at->copy()->addDays($windowDays) : null;
    }

    /** هل تجاوز هذا الـ IP الحد المسموح في الدقيقة؟ */
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

    /** يسجّل محاولة مرفوضة دون احتسابها في النتائج. */
    public function logRejection(string $type, string $reason, string $nationalId, string $phone, ?int $officeId, Request $request): void
    {
        FeedbackRejectedAttempt::create([
            'type'        => $type,
            'national_id' => $nationalId ?: null,
            'phone'       => $phone ?: null,
            'office_id'   => $officeId,
            'reason'      => $reason,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);
    }

    private function ipKey(string $ip): string
    {
        return 'feedback:'.$ip;
    }
}
