<?php

namespace App\Livewire\Feedback;

use App\Livewire\Feedback\Concerns\InteractsWithFeedbackGate;
use App\Models\FeedbackMessage;
use App\Rules\EgyptianNationalId;
use App\Rules\PublicFeedbackOffice;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * رسالة مواطن نصية (شكر موظف · …) — فورم واحد لكل الأنواع، وما يطلبه كل نوع
 * من FeedbackMessage::TYPES. الحماية نفسها (honeypot · سقف الـIP · قفل الأسبوع
 * لكل نوع ومقر) من InteractsWithFeedbackGate.
 */
#[Layout('components.layouts.feedback')]
class Message extends Component
{
    use InteractsWithFeedbackGate;

    /** ⚠️ مقفول: يصل من الراوت، وتغييره من المتصفح كان يحفظ الرسالة بنوعٍ آخر بقواعد نوعها. */
    #[Locked]
    public string $messageType = '';

    public string $name = '';
    public string $national_id = '';
    public string $phone = '';
    public ?int $governorate_id = null;
    public ?int $office_id = null;

    public string $employee_name = '';
    public string $body = '';

    public bool $submitted = false;

    public function mount(string $messageType): void
    {
        abort_unless(FeedbackMessage::isType($messageType), 404);

        $this->messageType = $messageType;
        $this->resumeFromCarry();
    }

    protected function feedbackType(): string
    {
        return $this->messageType;
    }

    #[Computed]
    public function config(): array
    {
        return FeedbackMessage::config($this->messageType);
    }

    public function identityRequired(): bool
    {
        return $this->config['identity'] === 'required';
    }

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

    /** نص الرسالة يظهر بعد اختيار المقر وما دام لم يُحجب بقاعدة التكرار. */
    #[Computed]
    public function showMessage(): bool
    {
        return $this->office_id && ! $this->gateBlocked;
    }

    protected function persist(string $ip): void
    {
        FeedbackMessage::create([
            'type'           => $this->messageType,
            'governorate_id' => $this->officeGovernorateId(),
            'office_id'      => $this->office_id,
            ...$this->identityAttributes(),
            'employee_name'  => $this->config['employee'] ? (trim($this->employee_name) ?: null) : null,
            'body'           => trim($this->body),
            'ip_address'     => $ip,
            'user_agent'     => request()->userAgent(),
        ]);
    }

    protected function rules(): array
    {
        $identity = $this->identityRequired() ? 'required' : 'nullable';
        $employee = $this->config['employee'];

        return [
            'name'           => [$identity, 'string', 'max:100'],
            'national_id'    => [$identity, new EgyptianNationalId],
            'phone'          => [$identity, 'regex:/^01[0125]\d{8}$/'],
            'governorate_id' => ['required', 'exists:governorates,id'],
            'office_id'      => ['required', new PublicFeedbackOffice],
            'employee_name'  => $employee ? [$employee === 'required' ? 'required' : 'nullable', 'string', 'max:150'] : ['nullable'],
            'body'           => ['required', 'string', 'max:2000'],
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
            'employee_name'  => $this->config['employee_label'] ?? 'اسم الموظف',
            'body'           => $this->config['body_label'],
        ];
    }

    public function render()
    {
        return view('livewire.feedback.message')
            ->title(implode(' ', $this->config['title']));
    }
}
