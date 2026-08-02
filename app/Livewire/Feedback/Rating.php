<?php

namespace App\Livewire\Feedback;

use App\Livewire\Feedback\Concerns\InteractsWithFeedbackGate;
use App\Models\FeedbackRating;
use App\Models\Governorate;
use App\Models\Office;
use App\Rules\EgyptianNationalId;
use App\Rules\PublicFeedbackOffice;
use App\Services\FeedbackGate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.feedback')]
#[Title('تقييم الخدمة')]
class Rating extends Component
{
    use InteractsWithFeedbackGate;

    /* ===== الخطوة الأولى: هوية مقدّم التقييم + المقر ===== */
    public string $name = '';
    public string $national_id = '';
    public string $phone = '';
    public ?int $governorate_id = null;
    public ?int $office_id = null;

    /* ===== الخطوة الثانية: بنود التقييم ===== */
    public string $wait_time = '';
    public ?int $rating_speed = null;
    public ?int $rating_staff = null;
    public ?int $rating_queue = null;
    public ?int $rating_cleanliness = null;
    public ?int $rating_clarity = null;
    public ?int $rating_accessibility = null; // اختياري
    public ?int $overall_rating = null;
    public string $notes = '';

    public bool $submitted = false;

    /** خيارات مدة الانتظار ومحاور التقييم — معرّفة على الموديل (مصدر واحد للبوابة والنتائج) */
    public const WAIT_TIMES = FeedbackRating::WAIT_TIMES;

    public const CRITERIA = FeedbackRating::CRITERIA;

    public function mount(): void
    {
        $this->resumeFromCarry();
    }

    protected function feedbackType(): string
    {
        return FeedbackGate::TYPE_RATING;
    }

    /** عند تغيير المحافظة نصفّر المقر المختار ونعيد ضبط البوابة */
    public function updatedGovernorateId(): void
    {
        $this->office_id = null;
        $this->gateBlocked = false;
        $this->gateRetryDate = '';
    }

    /** تحقق فوري من الرقم القومي + فحص التكرار مع الكتابة */
    public function updatedNationalId(): void
    {
        $this->evaluateGate();                       // يفكّ الحجب لو الرقم الجديد غير مكرّر
        if (strlen($this->national_id) >= 14) {      // نتحقق من الصيغة فقط بعد اكتمال الرقم
            $this->validateOnly('national_id');
        }
    }

    /** فحص التكرار بمجرد اختيار المقر (قبل عرض البنود) */
    public function updatedOfficeId(): void
    {
        $this->evaluateGate();
    }

    /** إعادة فحص التكرار عند تغيير الهاتف (لأنه أحد مفتاحي القفل) */
    public function updatedPhone(): void
    {
        $this->evaluateGate();
    }

    #[Computed]
    public function governorates()
    {
        // فقط المحافظات التي بها مقر واحد على الأقل من نوع ظاهر للمواطن
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

    /** تظهر بنود التقييم بعد اختيار المقر وطالما لم يُحجب بقاعدة التكرار */
    #[Computed]
    public function showRating(): bool
    {
        return $this->office_id && ! $this->gateBlocked;
    }

    protected function persist(string $ip): void
    {
        FeedbackRating::create([
            'governorate_id'       => $this->officeGovernorateId(),
            'office_id'            => $this->office_id,
            'name'                 => $this->name,
            'national_id'          => $this->national_id,
            'phone'                => $this->phone,
            'wait_time'            => $this->wait_time,
            'rating_speed'         => $this->rating_speed,
            'rating_staff'         => $this->rating_staff,
            'rating_queue'         => $this->rating_queue,
            'rating_cleanliness'   => $this->rating_cleanliness,
            'rating_clarity'       => $this->rating_clarity,
            'rating_accessibility' => $this->rating_accessibility,
            'overall_rating'       => $this->overall_rating,
            'notes'                => $this->notes ?: null,
            'ip_address'           => $ip,
            'user_agent'           => request()->userAgent(),
        ]);
    }

    protected function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:100'],
            'national_id'          => ['required', new EgyptianNationalId],
            'phone'                => ['required', 'regex:/^01[0125]\d{8}$/'],
            'governorate_id'       => ['required', 'exists:governorates,id'],
            'office_id'            => ['required', new PublicFeedbackOffice],
            'wait_time'            => ['required', 'in:'.implode(',', array_keys(self::WAIT_TIMES))],
            'rating_speed'         => ['required', 'integer', 'between:1,5'],
            'rating_staff'         => ['required', 'integer', 'between:1,5'],
            'rating_queue'         => ['required', 'integer', 'between:1,5'],
            'rating_cleanliness'   => ['required', 'integer', 'between:1,5'],
            'rating_clarity'       => ['required', 'integer', 'between:1,5'],
            'rating_accessibility' => ['nullable', 'integer', 'between:1,5'],
            'overall_rating'       => ['required', 'integer', 'between:1,5'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name'           => 'الاسم',
            'national_id'    => 'الرقم القومي',
            'phone'          => 'رقم الهاتف',
            'governorate_id' => 'المحافظة',
            'office_id'      => 'المقر',
            'wait_time'      => 'مدة الانتظار',
            'rating_speed'         => 'سرعة إنجاز المعاملة',
            'rating_staff'         => 'معاملة الموظفين',
            'rating_queue'         => 'تنظيم الدور',
            'rating_cleanliness'   => 'نظافة المقر',
            'rating_clarity'       => 'وضوح الإجراءات والرسوم',
            'rating_accessibility' => 'تيسير الخدمة لذوي الإعاقة',
            'overall_rating'       => 'التقييم العام',
            'notes'                => 'الملاحظات',
        ];
    }

    public function render()
    {
        return view('livewire.feedback.rating');
    }
}
