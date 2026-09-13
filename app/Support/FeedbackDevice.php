<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;

/**
 * بصمة جهاز المواطن في بوابة رأي المواطن — رمز عشوائي في كوكي مشفَّر عمره سنة.
 *
 * هو مفتاح منع التكرار حين يُرسل المواطن رأيه مجهولاً (الهوية صارت اختيارية).
 * الرمز **لا يحمل أي معلومة عن صاحبه** — عشوائي بالكامل، ولا يُستخرج منه اسم
 * ولا هاتف ولا موقع. وظيفته سؤال واحد: هل هذا هو الجهاز نفسه؟
 *
 * ⚠️ الرمز يُقرأ من **الطلب** في كل مرة ولا يُحفظ في خاصية عامة على المكوّن —
 * خصائص Livewire تمرّ بالمتصفح فتُزوَّر، والكوكي مشفَّر بمفتاح التطبيق فلا يُزوَّر.
 *
 * ⚠️ وحدوده معلومة ومقبولة: متصفح آخر أو مسح الكوكيز أو التصفّح المتخفّي =
 * رمز جديد. يمنع التكرار العادي لا التلاعب المصمَّم — وحدّ ذاك سقفُ الـIP
 * اليومي في FeedbackGate، والتحقق الحقيقي وحده (OTP) يقفله.
 */
class FeedbackDevice
{
    public const COOKIE = 'feedback_device';

    /** سنة بالدقائق. */
    private const LIFETIME = 525600;

    /**
     * رمز جهاز هذا المتصفح — يُقرأ من الكوكي، ويُصدَر ويُلحق بالرد إن لم يوجد.
     * يرجع رمزاً واحداً ثابتاً داخل الطلب الواحد (لا يُصدر رمزين لزائر واحد).
     */
    public function token(): string
    {
        $fromRequest = request()->cookie(self::COOKIE);
        if (is_string($fromRequest) && $this->looksValid($fromRequest)) {
            return $fromRequest;
        }

        // رمز أُصدر بالفعل في هذا الطلب نفسه (mount ثم submit في نفس الدورة)
        $queued = Cookie::queued(self::COOKIE);
        if ($queued && $this->looksValid((string) $queued->getValue())) {
            return (string) $queued->getValue();
        }

        $token = bin2hex(random_bytes(16));
        Cookie::queue(cookie(self::COOKIE, $token, self::LIFETIME));

        return $token;
    }

    private function looksValid(string $value): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', $value);
    }
}
