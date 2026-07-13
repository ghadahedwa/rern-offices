<?php

namespace App\Livewire\Meetings;

use App\Models\Meeting;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('اجتماع')]
class Create extends Component
{
    public ?Meeting $meeting = null;
    public bool $isEditing = false;

    public string $date = '';
    public string $time = '';
    public string $subject = '';
    public string $location = '';
    public string $concerned_party = '';
    public string $concerned_party_title = '';
    public string $result = '';
    public string $notes = '';

    public function mount(?Meeting $meeting = null): void
    {
        $this->isEditing = $meeting && $meeting->exists;

        abort_unless(
            Auth::user()?->can($this->isEditing ? 'meetings.edit' : 'meetings.create'),
            403
        );

        if ($this->isEditing) {
            $this->meeting = $meeting;
            $this->date    = optional($meeting->date)->format('Y-m-d') ?? '';
            $this->time    = $meeting->time ? substr($meeting->time, 0, 5) : '';
            $this->subject = $meeting->subject ?? '';
            $this->location = $meeting->location ?? '';
            $this->concerned_party = $meeting->concerned_party ?? '';
            $this->concerned_party_title = $meeting->concerned_party_title ?? '';
            $this->result  = $meeting->result ?? '';
            $this->notes   = $meeting->notes ?? '';
        }
    }

    protected function rules(): array
    {
        return [
            'date'                  => ['required', 'date'],
            'time'                  => ['required'],
            'subject'               => ['required', 'string', 'max:255'],
            'location'              => ['nullable', 'string', 'max:255'],
            'concerned_party'       => ['nullable', 'string', 'max:255'],
            'concerned_party_title' => ['nullable', 'string', 'max:255'],
            'result'                => ['nullable', 'string'],
            'notes'                 => ['nullable', 'string'],
        ];
    }

    public function save(): void
    {
        // نموذج للمعاينة واعتماد التصميم — الحفظ غير مفعّل مؤقتاً
        Flux::toast(variant: 'warning', text: __('home.meeting_preview_notice'));
    }

    public function render()
    {
        return view('livewire.meetings.create');
    }
}
