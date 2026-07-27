<?php

namespace App\Livewire\Feedback\Concerns;

use App\Services\FeedbackGate;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * منطق بوابة الحماية المشترك بين فورمي التقييم والمقترحات:
 * honeypot + الفحص التفاعلي (قبل البنود) + الإرسال الحاسم + الحفظ + تنسيق التاريخ.
 * المكوّن المستخدِم يوفّر: feedbackType()، persist()، (اختياري) afterValidation().
 */
trait InteractsWithFeedbackGate
{
    /** حقل مصيدة مخفي — لو اتملأ فهو بوت. */
    public string $website = '';

    /** هل المقر محجوب بقاعدة التكرار؟ */
    public bool $gateBlocked = false;

    /** تاريخ السماح القادم (منسّق بالعربية) عند الحجب. */
    public string $gateRetryDate = '';

    abstract protected function feedbackType(): string;

    /** يحفظ الإرسال في قاعدة البيانات (داخل transaction). */
    abstract protected function persist(string $ip): void;

    /** تحقق إضافي بعد الأساسي — يرجع false لإيقاف الإرسال (مع إضافة الأخطاء). */
    protected function afterValidation(): bool
    {
        return true;
    }

    /** فحص تفاعلي: يُستدعى عند تغيّر المقر/الرقم القومي قبل عرض البنود. */
    protected function evaluateGate(): void
    {
        $this->gateBlocked = false;
        $this->gateRetryDate = '';

        // لا نفحص إلا برقم قومي صحيح شكلياً + مقر مختار
        if (! $this->office_id || ! preg_match('/^\d{14}$/', $this->national_id)) {
            return;
        }

        $retry = app(FeedbackGate::class)->duplicateRetryDate(
            $this->feedbackType(), $this->national_id, $this->phone, (int) $this->office_id,
        );

        if ($retry) {
            $this->gateBlocked = true;
            $this->gateRetryDate = $this->formatArabicDate($retry);
            app(FeedbackGate::class)->logRejection(
                $this->feedbackType(), 'duplicate_window',
                $this->national_id, $this->phone, (int) $this->office_id, request(),
            );
        }
    }

    public function submit(): void
    {
        $gate = app(FeedbackGate::class);
        $ip = (string) request()->ip();

        // 1) honeypot — بوت: نعرض "شكراً" بلا حفظ حتى لا يعرف أنه انكشف
        if (trim($this->website) !== '') {
            $gate->logRejection($this->feedbackType(), 'honeypot', $this->national_id, $this->phone, $this->office_id, request());
            $this->submitted = true;

            return;
        }

        $this->validate($this->rules(), attributes: $this->validationAttributes());

        if (! $this->afterValidation()) {
            return;
        }

        // 2) حد الجهاز (IP)
        if ($gate->ipThrottled($ip)) {
            $gate->logRejection($this->feedbackType(), 'rate_limit', $this->national_id, $this->phone, $this->office_id, request());
            $this->addError('gate', 'تم استقبال عدد كبير من المحاولات من جهازك، برجاء المحاولة بعد قليل.');

            return;
        }
        $gate->hitIp($ip);

        // 3) قاعدة الأسبوعين (رقم قومي / هاتف)
        $retry = $gate->duplicateRetryDate($this->feedbackType(), $this->national_id, $this->phone, (int) $this->office_id);
        if ($retry) {
            $gate->logRejection($this->feedbackType(), 'duplicate_window', $this->national_id, $this->phone, $this->office_id, request());
            $this->gateBlocked = true;
            $this->gateRetryDate = $this->formatArabicDate($retry);

            return;
        }

        // 4) الحفظ
        DB::transaction(fn () => $this->persist($ip));
        $this->submitted = true;
    }

    /** "٦ أغسطس ٢٠٢٦" — أسماء عربية وأرقام عربية. */
    protected function formatArabicDate(CarbonInterface $date): string
    {
        $formatted = $date->locale('ar')->translatedFormat('j F Y');

        return strtr($formatted, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
    }
}
