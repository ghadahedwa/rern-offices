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
#[Title('تقييم الخدمة')]
class Rating extends Component
{
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

    /** خيارات مدة الانتظار */
    public const WAIT_TIMES = [
        'under_15' => 'أقل من ١٥ دقيقة',
        '15_30'    => 'من ١٥ إلى ٣٠ دقيقة',
        '30_60'    => 'من ٣٠ إلى ٦٠ دقيقة',
        'over_60'  => 'أكثر من ساعة',
    ];

    /** محاور التقييم (المفتاح = اسم الحقل، القيمة = [العنوان، اختياري؟]) */
    public const CRITERIA = [
        'rating_speed'         => ['سرعة إنجاز المعاملة', false],
        'rating_staff'         => ['معاملة الموظفين وحُسن الاستقبال', false],
        'rating_queue'         => ['تنظيم الدور والانتظار', false],
        'rating_cleanliness'   => ['نظافة المقر وأماكن الانتظار', false],
        'rating_clarity'       => ['وضوح الإجراءات والرسوم', false],
        'rating_accessibility' => ['تيسير الخدمة لذوي الإعاقة وكبار السن', true],
    ];

    /** عند تغيير المحافظة نصفّر المقر المختار */
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

    /** تظهر بنود التقييم بمجرد اختيار المقر (بوابة التحقق ستُضاف هنا لاحقاً) */
    #[Computed]
    public function showRating(): bool
    {
        return (bool) $this->office_id;
    }

    public function submit(): void
    {
        $this->validate($this->rules(), attributes: $this->validationAttributes());

        // TODO(المرحلة ١): بوابة الحماية (توحيد الرقم القومي + قاعدة الأسبوعين
        //                  + حد الجهاز + سجل المرفوض) ثم الحفظ في feedback_ratings.
        //                  مؤجّلة لحد ما نقفل على البنود والتصميم.

        $this->submitted = true;
    }

    protected function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:100'],
            'national_id'          => ['required', new EgyptianNationalId],
            'phone'                => ['required', 'regex:/^01[0125]\d{8}$/'],
            'governorate_id'       => ['required', 'exists:governorates,id'],
            'office_id'            => ['required', 'exists:offices,id'],
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
