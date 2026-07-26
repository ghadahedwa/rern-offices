<?php

namespace App\Livewire\Feedback;

use App\Models\Governorate;
use App\Models\Office;
use App\Rules\EgyptianNationalId;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.feedback')]
#[Title('تقديم اقتراح')]
class Suggestion extends Component
{
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

    public function updatedGovernorateId(): void
    {
        $this->office_id = null;
    }

    /** تحقق فوري من الرقم القومي عند الخروج من الحقل */
    public function updatedNationalId(): void
    {
        $this->validateOnly('national_id');
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

    /** تظهر مجالات المقترح بمجرد اختيار المقر (بوابة التحقق ستُضاف هنا لاحقاً) */
    #[Computed]
    public function showTopics(): bool
    {
        return (bool) $this->office_id;
    }

    public function submit(): void
    {
        $this->validate($this->rules(), attributes: $this->validationAttributes());

        // لازم عنوان واحد على الأقل أو اقتراح حر
        if (empty($this->topics) && trim($this->other_suggestion) === '') {
            $this->addError('topics', 'اختر عنواناً واحداً على الأقل أو اكتب اقتراحاً في الخانة الحرة.');

            return;
        }

        // TODO(المرحلة ١): بوابة الحماية (توحيد الرقم القومي + قاعدة الأسبوعين
        //                  + حد الجهاز + سجل المرفوض) ثم الحفظ في feedback_suggestions
        //                  والعناوين في pivot. مؤجّلة لحد ما نقفل على البنود والتصميم.

        $this->submitted = true;
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
