<?php

namespace App\Livewire\Feedback;

use App\Livewire\Feedback\Concerns\InteractsWithFeedbackGate;
use App\Models\FeedbackSuggestion;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\SuggestionTopic;
use App\Rules\EgyptianNationalId;
use App\Services\FeedbackGate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.feedback')]
#[Title('تقديم اقتراح')]
class Suggestion extends Component
{
    use InteractsWithFeedbackGate;

    /* ===== الخطوة الأولى: هوية مقدّم المقترح + المقر ===== */
    public string $name = '';
    public string $national_id = '';
    public string $phone = '';
    public ?int $governorate_id = null;
    public ?int $office_id = null;

    /* ===== الخطوة الثانية: العناوين المختارة + اقتراح حر ===== */
    /** @var array<string> مفاتيح العناوين المختارة */
    public array $topics = [];
    public string $other_suggestion = '';

    public bool $submitted = false;

    /**
     * كتالوج مجالات المقترح وعناوينها (multi-select).
     * يُنقل لاحقاً إلى جداول مرجعية (suggestion_domains / suggestion_topics)
     * عند بناء الموديول الداخلي. المفتاح = key ثابت، القيمة = العنوان العربي.
     */
    public const DOMAINS = [
        'work_procedures' => ['إجراءات العمل وتبسيطها', [
            'clarify_steps' => 'توضيح الخطوات والمستندات قبل الحضور',
            'one_window'    => 'إتاحة إنهاء المعاملة من شباك واحد',
            'reduce_time'   => 'تقليل مدة إنجاز المعاملة',
            'info_desk'     => 'توفير مكتب استعلامات وإرشاد',
        ]],
        'building' => ['المبنى والتجهيزات', [
            'more_seats'   => 'زيادة مقاعد الانتظار',
            'ventilation'  => 'تحسين التهوية والتكييف',
            'restrooms'    => 'تحسين دورات المياه',
            'more_windows' => 'زيادة عدد الشبابيك',
            'photocopy'    => 'توفير خدمة تصوير المستندات',
            'signage'      => 'تحسين اللافتات الإرشادية داخل المقر',
        ]],
        'digital' => ['التحول الرقمي والخدمات الإلكترونية', [
            'booking'       => 'إتاحة الحجز المسبق للمواعيد',
            'status_online' => 'إتاحة الاستعلام عن حالة المعاملة إلكترونياً',
            'queue_numbers' => 'تفعيل نظام أرقام لتنظيم الدور',
        ]],
        'hours' => ['مواعيد العمل', [
            'extend_hours' => 'مد ساعات العمل',
            'evening'      => 'العمل في فترة مسائية',
            'weekend_day'  => 'العمل يوم إجازة أسبوعياً',
            'special_slots'=> 'تخصيص أوقات لفئات معينة',
        ]],
        'accessibility' => ['التعامل مع كبار السن وذوي الإعاقة', [
            'priority_window'     => 'تخصيص شباك أولوية',
            'ramp'                => 'توفير منحدر ومسار للكراسي المتحركة',
            'accessible_restroom' => 'تحسين دورة مياه مهيأة',
        ]],
    ];

    /** كل مفاتيح العناوين الصالحة (للتحقق) */
    public static function topicKeys(): array
    {
        $keys = [];
        foreach (self::DOMAINS as [, $topics]) {
            $keys = array_merge($keys, array_keys($topics));
        }

        return $keys;
    }

    protected function feedbackType(): string
    {
        return FeedbackGate::TYPE_SUGGESTION;
    }

    public function updatedGovernorateId(): void
    {
        $this->office_id = null;
        $this->gateBlocked = false;
        $this->gateRetryDate = '';
    }

    /** تحقق فوري من الرقم القومي + فحص التكرار عند الخروج من الحقل */
    public function updatedNationalId(): void
    {
        $this->validateOnly('national_id');
        $this->evaluateGate();
    }

    /** فحص التكرار بمجرد اختيار المقر (قبل عرض المجالات) */
    public function updatedOfficeId(): void
    {
        $this->evaluateGate();
    }

    #[Computed]
    public function governorates()
    {
        return Governorate::orderBy('order')->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function offices()
    {
        if (! $this->governorate_id) {
            return collect();
        }

        return Office::where('governorate_id', $this->governorate_id)
            ->orderBy('name')->get(['id', 'name']);
    }

    /** تظهر المجالات بعد اختيار المقر وطالما لم يُحجب بقاعدة التكرار */
    #[Computed]
    public function showTopics(): bool
    {
        return $this->office_id && ! $this->gateBlocked;
    }

    /** لازم عنوان واحد على الأقل أو اقتراح حر */
    protected function afterValidation(): bool
    {
        if (empty($this->topics) && trim($this->other_suggestion) === '') {
            $this->addError('topics', 'اختر عنواناً واحداً على الأقل أو اكتب اقتراحاً في الخانة الحرة.');

            return false;
        }

        return true;
    }

    protected function persist(string $ip): void
    {
        $suggestion = FeedbackSuggestion::create([
            'governorate_id'   => $this->governorate_id,
            'office_id'        => $this->office_id,
            'name'             => $this->name,
            'national_id'      => $this->national_id,
            'phone'            => $this->phone,
            'other_suggestion' => trim($this->other_suggestion) ?: null,
            'ip_address'       => $ip,
            'user_agent'       => request()->userAgent(),
        ]);

        $topicIds = SuggestionTopic::whereIn('key', $this->topics)->pluck('id');
        $suggestion->topics()->sync($topicIds);
    }

    protected function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:100'],
            'national_id'      => ['required', new EgyptianNationalId],
            'phone'            => ['required', 'regex:/^01[0125]\d{8}$/'],
            'governorate_id'   => ['required', 'exists:governorates,id'],
            'office_id'        => ['required', 'exists:offices,id'],
            'topics'           => ['array'],
            'topics.*'         => ['string', 'in:'.implode(',', self::topicKeys())],
            'other_suggestion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name'             => 'الاسم',
            'national_id'      => 'الرقم القومي',
            'phone'            => 'رقم الهاتف',
            'governorate_id'   => 'المحافظة',
            'office_id'        => 'المقر',
            'topics'           => 'العناوين',
            'other_suggestion' => 'الاقتراح الحر',
        ];
    }

    public function render()
    {
        return view('livewire.feedback.suggestion');
    }
}
