<?php

namespace App\Livewire\Feedback;

use App\Livewire\Feedback\Concerns\InteractsWithFeedbackGate;
use App\Models\FeedbackDigitalRating;
use App\Models\Governorate;
use App\Models\Office;
use App\Rules\EgyptianNationalId;
use App\Rules\PublicFeedbackOffice;
use App\Services\FeedbackGate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * بوابة «تقييم المنصات الرقمية» — الفورم الثالث بجانب تقييم الخدمة وتقديم الاقتراح.
 *
 * الأسئلة ومتى يظهر كلٌّ منها في FeedbackDigitalRating (QUESTIONS · SHOWN_WHEN) —
 * المكوّن يعرض ويتحقق ويحفظ **ما يظهر وحده** عبر visibleQuestions().
 */
#[Layout('components.layouts.feedback')]
#[Title('تقييم المنصات الرقمية')]
class DigitalPlatforms extends Component
{
    use InteractsWithFeedbackGate;

    /* ===== الخطوة الأولى: الهوية (اختيارية) + المقر ===== */
    public string $name = '';
    public string $national_id = '';
    public string $phone = '';
    public ?int $governorate_id = null;
    public ?int $office_id = null;

    /* ===== الخطوة الثانية: أسئلة الاستمارة ٢٠١–٢١٣ ===== */
    public string $q201 = '';
    public string $q202 = '';
    public string $q203 = '';
    public string $q204 = '';
    public ?int $q205 = null;
    public string $q206 = '';
    public string $q207 = '';
    public string $q208 = '';
    /** @var array<int, string> */
    public array $q209 = [];
    public string $q210 = '';
    public string $q211 = '';
    /** @var array<int, string> */
    public array $q212 = [];
    public string $q213 = '';

    public bool $submitted = false;

    public function mount(): void
    {
        $this->resumeFromCarry();
    }

    protected function feedbackType(): string
    {
        return FeedbackGate::TYPE_DIGITAL;
    }

    public function updatedGovernorateId(): void
    {
        $this->office_id = null;
        $this->gateBlocked = false;
        $this->gateRetryDate = '';
    }

    /** تحقق فوري من الرقم القومي + فحص التكرار مع الكتابة */
    public function updatedNationalId(): void
    {
        $this->evaluateGate();
        if (strlen($this->national_id) >= 14) {
            $this->validateOnly('national_id');
        }
    }

    public function updatedOfficeId(): void
    {
        $this->evaluateGate();
    }

    public function updatedPhone(): void
    {
        $this->evaluateGate();
    }

    #[Computed]
    public function governorates()
    {
        return Governorate::whereHas('offices', fn ($q) => $q->publicFeedback())
            ->orderBy('order')->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function offices()
    {
        if (! $this->governorate_id) {
            return collect();
        }

        return Office::publicFeedback()
            ->where('governorate_id', $this->governorate_id)
            ->orderBy('name')->get(['id', 'name']);
    }

    /** تظهر الأسئلة بعد اختيار المقر وطالما لم يُحجب بقاعدة التكرار */
    #[Computed]
    public function showQuestions(): bool
    {
        return $this->office_id && ! $this->gateBlocked;
    }

    /** @return array<string, mixed> إجابات الأسئلة كما في حالة الفورم */
    protected function answers(): array
    {
        return collect(array_keys(FeedbackDigitalRating::QUESTIONS))
            ->mapWithKeys(fn (string $key) => [$key => $this->{$key}])
            ->all();
    }

    /** @return array<int, string> */
    public function visibleQuestions(): array
    {
        return FeedbackDigitalRating::visibleQuestions($this->answers());
    }

    protected function persist(string $ip): void
    {
        $visible = $this->visibleQuestions();

        // ⚠️ السؤال المخفي يُحفظ NULL مهما بقي في حالة الفورم — مَن اختار «حجزت» وأجاب
        // عن المشاكل ثم رجع لـ«بدون حجز» اختفت الأسئلة من الشاشة لا من الخصائص، وحفظها
        // يُخرج صفاً متناقضاً (بلا حجز · قابلته مشاكل في الحجز).
        $columns = [];
        foreach (FeedbackDigitalRating::QUESTIONS as $key => [$type]) {
            if ($type === 'checkbox') {
                continue;
            }

            $value = $this->{$key};
            $columns[$key] = in_array($key, $visible, true) && $value !== '' && $value !== null
                ? ($type === 'text' ? trim($value) : $value)
                : null;

            if ($type === 'text' && $columns[$key] === '') {
                $columns[$key] = null;
            }
        }

        $rating = FeedbackDigitalRating::create([
            'governorate_id' => $this->officeGovernorateId(),
            'office_id'      => $this->office_id,
            ...$this->identityAttributes(),
            ...$columns,
            'ip_address'     => $ip,
            'user_agent'     => request()->userAgent(),
        ]);

        foreach (FeedbackDigitalRating::questionsOfType('checkbox') as $key) {
            if (! in_array($key, $visible, true)) {
                continue;
            }

            foreach (array_unique($this->{$key}) as $option) {
                $rating->choices()->create(['question' => $key, 'option' => $option]);
            }
        }
    }

    protected function rules(): array
    {
        $rules = [
            // الهوية اختيارية — لمن يريد إخفاء هويته. والمكتوب منها يُفحص بصيغته كاملة.
            'name'           => ['nullable', 'string', 'max:100'],
            'national_id'    => ['nullable', new EgyptianNationalId],
            'phone'          => ['nullable', 'regex:/^01[0125]\d{8}$/'],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'office_id'      => ['required', new PublicFeedbackOffice],
        ];

        // ما يظهر وحده يُتحقق منه: الاختيار والدرجة إجباريان حين يظهران، والنص الحر اختياري.
        // والمخفي لا يُتحقق منه لأنه لا يُحفظ أصلاً (persist).
        foreach ($this->visibleQuestions() as $key) {
            [$type, , $options] = FeedbackDigitalRating::QUESTIONS[$key];

            $rules += match ($type) {
                'radio'    => [$key => ['required', 'in:'.implode(',', array_keys($options))]],
                'checkbox' => [$key => ['required', 'array', 'min:1'], "{$key}.*" => ['string', 'in:'.implode(',', array_keys($options))]],
                'scale'    => [$key => ['required', 'integer', "between:{$options[0]},{$options[1]}"]],
                'text'     => [$key => ['nullable', 'string', 'max:1000']],
            };
        }

        return $rules;
    }

    protected function validationAttributes(): array
    {
        return [
            'name'           => 'الاسم',
            'national_id'    => 'الرقم القومي',
            'phone'          => 'رقم الهاتف',
            'governorate_id' => 'المحافظة',
            'office_id'      => 'المقر',
        ];
    }

    /** رسالة واحدة مفهومة لكل سؤال بدل اسم الحقل التقني (q202…) */
    protected function messages(): array
    {
        $messages = [];
        foreach (FeedbackDigitalRating::QUESTIONS as $key => [$type]) {
            $messages["{$key}.required"] = $type === 'checkbox'
                ? 'اختر إجابة واحدة على الأقل.'
                : 'من فضلك اختر إجابة.';
            $messages["{$key}.min"] = 'اختر إجابة واحدة على الأقل.';
            $messages["{$key}.in"] = 'الإجابة المختارة غير صحيحة.';
            $messages["{$key}.*.in"] = 'الإجابة المختارة غير صحيحة.';
            $messages["{$key}.between"] = 'اختر درجة من صفر إلى عشرة.';
            $messages["{$key}.integer"] = 'اختر درجة من صفر إلى عشرة.';
            $messages["{$key}.max"] = 'النص طويل جداً.';
        }

        return $messages;
    }

    public function render()
    {
        return view('livewire.feedback.digital-platforms');
    }
}
