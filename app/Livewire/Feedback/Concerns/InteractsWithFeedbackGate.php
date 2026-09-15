<?php

namespace App\Livewire\Feedback\Concerns;

use App\Services\FeedbackGate;
use App\Support\FeedbackDevice;
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

    /**
     * يُصدر بصمة الجهاز مع **تحميل الصفحة** (hook تلقائي من Livewire لكل trait).
     *
     * ⚠️ بدونه لا تُصدر البصمة إلا مع أول فحص (اختيار المقر) داخل طلب Livewire
     * خلفي — والكوكي يصل المتصفح حينها متأخراً عن تحميل الصفحة. الرد الكامل هو
     * المكان المضمون لإعطاء المتصفح الكوكي قبل أي تفاعل.
     */
    public function mountInteractsWithFeedbackGate(): void
    {
        $this->deviceToken();
    }

    /** يحفظ الإرسال في قاعدة البيانات (داخل transaction). */
    abstract protected function persist(string $ip): void;

    /**
     * محافظة المقر المختار — تُشتق من المقر نفسه لا من مُدخَل المستخدم،
     * حتى لا يُحفظ صف بمحافظة لا تخصّ المقر (يفسد تجميع النتائج لاحقاً).
     */
    protected function officeGovernorateId(): ?int
    {
        return \App\Models\Office::whereKey($this->office_id)->value('governorate_id');
    }

    /**
     * أعمدة الهوية كما تُحفظ — مشتركة بين الفورمين. الحقول الثلاثة اختيارية،
     * والفارغ يُحفظ NULL لا نصاً فارغاً (عليه يُبنى تعريف «مجهول» في النتائج).
     * وبصمة الجهاز تُكتب على كل صف، المُعرَّف والمجهول معاً — وإلا صار مَن أرسل
     * باسمه اليوم يعيد الإرسال مجهولاً غداً من الهاتف نفسه.
     */
    protected function identityAttributes(): array
    {
        return [
            'name'         => trim($this->name) ?: null,
            'national_id'  => $this->national_id ?: null,
            'phone'        => $this->phone ?: null,
            'device_token' => $this->deviceToken(),
        ];
    }

    /** تحقق إضافي بعد الأساسي — يرجع false لإيقاف الإرسال (مع إضافة الأخطاء). */
    protected function afterValidation(): bool
    {
        return true;
    }

    /**
     * بصمة جهاز هذا المتصفح — تُقرأ من الكوكي المشفَّر في كل مرة، ولا تُحفظ في
     * خاصية عامة (خصائص Livewire تمرّ بالمتصفح فتُزوَّر). انظر FeedbackDevice.
     */
    protected function deviceToken(): string
    {
        return app(FeedbackDevice::class)->token();
    }

    /**
     * فحص تفاعلي: يُستدعى عند تغيّر المقر/الرقم القومي قبل عرض البنود.
     *
     * @param  bool  $logRejection  false عند الوصول بزرّ الانتقال بين الفورمات (resume):
     *   المواطن ضغط رابطاً بعد إرساله أو حجبه، لم يحاول إرسالاً جديداً — وكان كل ضغطة
     *   تُسجَّل «محاولة مرفوضة» فتملأ الشاشة بما ليس محاولة.
     */
    protected function evaluateGate(bool $logRejection = true): void
    {
        $wasBlocked = $this->gateBlocked;
        $this->gateBlocked = false;
        $this->gateRetryDate = '';

        // الهوية اختيارية، فالفحص يبدأ بمجرد اختيار المقر: بصمة الجهاز موجودة دائماً.
        // الرقم القومي يدخل المفاتيح فقط حين يكتمل (١٤ رقماً) — نصف رقم لا يطابق أحداً.
        if (! $this->office_id) {
            return;
        }

        $duplicate = app(FeedbackGate::class)->duplicateMatch(
            $this->feedbackType(), $this->completeNationalId(), $this->completePhone(),
            $this->deviceToken(), (int) $this->office_id,
        );

        if ($duplicate) {
            $this->gateBlocked = true;
            $this->gateRetryDate = $this->formatArabicDate($duplicate['retry']);
            $this->stashIdentity();

            // نسجّل الرفض مرة واحدة فقط عند الدخول في الحجب — لا مع كل إعادة فحص (live)
            if (! $wasBlocked && $logRejection) {
                app(FeedbackGate::class)->logRejection(
                    $this->feedbackType(), 'duplicate_window',
                    $this->national_id, $this->phone, (int) $this->office_id, request(), $this->deviceToken(),
                    $duplicate['match'],
                );
            }
        }
    }

    public function submit(): void
    {
        $gate = app(FeedbackGate::class);
        $ip = (string) request()->ip();

        // 1) honeypot — بوت: نعرض "شكراً" بلا حفظ حتى لا يعرف أنه انكشف
        if (trim($this->website) !== '') {
            $gate->logRejection($this->feedbackType(), 'honeypot', $this->national_id, $this->phone, $this->office_id, request(), $this->deviceToken());
            $this->submitted = true;

            return;
        }

        $this->validate($this->rules(), attributes: $this->validationAttributes());

        if (! $this->afterValidation()) {
            return;
        }

        // 2) حد الجهاز (IP)
        if ($gate->ipThrottled($ip)) {
            $gate->logRejection($this->feedbackType(), 'rate_limit', $this->national_id, $this->phone, $this->office_id, request(), $this->deviceToken());
            $this->addError('gate', 'تم استقبال عدد كبير من المحاولات من جهازك، برجاء المحاولة بعد قليل.');

            return;
        }
        $gate->hitIp($ip);

        // 3) سقف الـIP اليومي لكل مقر — السور حين تتغيّر بصمة الجهاز (للمُعرَّف والمجهول معاً)
        if ($gate->officeIpCapExceeded($ip, (int) $this->office_id)) {
            $gate->logRejection($this->feedbackType(), 'ip_daily_cap', $this->national_id, $this->phone, $this->office_id, request(), $this->deviceToken());
            $this->addError('gate', 'تم استقبال عدد كبير من الآراء لهذا المقر من نفس شبكة الإنترنت اليوم، برجاء المحاولة غداً.');

            return;
        }

        // 4) قاعدة الأسبوع (رقم قومي / هاتف / بصمة جهاز)
        $duplicate = $gate->duplicateMatch(
            $this->feedbackType(), $this->national_id, $this->phone, $this->deviceToken(), (int) $this->office_id,
        );
        if ($duplicate) {
            $gate->logRejection($this->feedbackType(), 'duplicate_window', $this->national_id, $this->phone, $this->office_id, request(), $this->deviceToken(), $duplicate['match']);
            $this->gateBlocked = true;
            $this->gateRetryDate = $this->formatArabicDate($duplicate['retry']);
            $this->stashIdentity();

            return;
        }

        // 5) الحفظ — ويُحتسب على السقف الناجحُ وحده (السقف عدد آراء لا محاولات)
        DB::transaction(fn () => $this->persist($ip));
        $gate->hitOfficeIpCap($ip, (int) $this->office_id);
        $this->stashIdentity();   // نحمل الهوية للانتقال للفورم الآخر بضغطة
        $this->submitted = true;
    }

    /** الرقم القومي مكتملاً (١٤ رقماً) وإلا فراغ — للفحص التفاعلي أثناء الكتابة. */
    protected function completeNationalId(): string
    {
        return preg_match('/^\d{14}$/', $this->national_id) ? $this->national_id : '';
    }

    /** الهاتف مكتملاً (١١ رقماً) وإلا فراغ — نصف رقم يطابق بالمصادفة هاتفاً آخر. */
    protected function completePhone(): string
    {
        return preg_match('/^\d{11}$/', $this->phone) ? $this->phone : '';
    }

    /* ===== الانتقال بين الفورمين بنفس البيانات ===== */

    /** يخزّن الهوية في الـ session لاستئنافها في الفورم الآخر (تُقرأ مرة واحدة). */
    protected function stashIdentity(): void
    {
        session()->put('feedback.carry', [
            'name'           => $this->name,
            'national_id'    => $this->national_id,
            'phone'          => $this->phone,
            'governorate_id' => $this->governorate_id,
            'office_id'      => $this->office_id,
        ]);
    }

    /** يُستدعى في mount: يعبّئ الهوية القادمة من الفورم الآخر (فقط عند ?resume=1). */
    protected function resumeFromCarry(): void
    {
        if (! request()->boolean('resume')) {
            return;
        }

        $carry = session()->pull('feedback.carry');   // قراءة + مسح (مرة واحدة)
        if (! $carry) {
            return;
        }

        $this->name           = $carry['name'] ?? '';
        $this->national_id    = $carry['national_id'] ?? '';
        $this->phone          = $carry['phone'] ?? '';
        $this->governorate_id = $carry['governorate_id'] ?? null;
        $this->office_id      = $carry['office_id'] ?? null;

        $this->evaluateGate(logRejection: false);   // يكشف البنود مباشرة أو يظهر الحجب — بلا تسجيل رفض (تنقّل لا محاولة)
    }

    /**
     * الفورمات الأخرى (اثنان من ثلاثة) مع إشارة الاستئناف — لشاشتي الشكر والحجب.
     *
     * @return array<int, array{url: string, also: string, alt: string}>
     *   also = نص زر شاشة الشكر · alt = نص رابط شاشة الحجب
     */
    public function otherForms(): array
    {
        $forms = [
            FeedbackGate::TYPE_RATING     => ['feedback.rating', 'قيّم الخدمة أيضاً', 'هل ترغب بتقييم الخدمة؟'],
            FeedbackGate::TYPE_SUGGESTION => ['feedback.suggestion', 'قدّم اقتراحاً أيضاً', 'لديك ملاحظة أخرى؟ قدّم اقتراح'],
            FeedbackGate::TYPE_DIGITAL    => ['feedback.digital', 'قيّم المنصات الرقمية أيضاً', 'قيّم المنصات الرقمية'],
        ];

        unset($forms[$this->feedbackType()]);

        return array_values(array_map(fn ($f) => [
            'url'  => route($f[0], ['resume' => 1]),
            'also' => $f[1],
            'alt'  => $f[2],
        ], $forms));
    }

    /** "٦ أغسطس ٢٠٢٦" — أسماء عربية وأرقام عربية. */
    protected function formatArabicDate(CarbonInterface $date): string
    {
        $formatted = $date->locale('ar')->translatedFormat('j F Y');

        return strtr($formatted, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
    }
}
