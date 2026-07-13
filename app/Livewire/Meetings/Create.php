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
    public string $result = '';
    public string $notes = '';

    /** المعنيون بالاجتماع: كل عنصر ['name' => '', 'title' => ''] */
    public array $attendees = [];

    public function mount(?Meeting $meeting = null): void
    {
        $this->isEditing = $meeting && $meeting->exists;

        abort_unless(
            Auth::user()?->can($this->isEditing ? 'meetings.edit' : 'meetings.create'),
            403
        );

        if ($this->isEditing) {
            $this->meeting  = $meeting;
            $this->date     = optional($meeting->date)->format('Y-m-d') ?? '';
            $this->time     = $meeting->time ? substr($meeting->time, 0, 5) : '';
            $this->subject  = $meeting->subject ?? '';
            $this->location = $meeting->location ?? '';
            $this->result   = $meeting->result ?? '';
            $this->notes    = $meeting->notes ?? '';
            $this->attendees = $meeting->attendees
                ->map(fn ($a) => ['name' => $a->name, 'title' => $a->title ?? ''])
                ->toArray();
        }

        if (empty($this->attendees)) {
            $this->attendees = [['name' => '', 'title' => '']];
        }
    }

    public function addAttendee(): void
    {
        $this->attendees[] = ['name' => '', 'title' => ''];
    }

    public function removeAttendee(int $index): void
    {
        unset($this->attendees[$index]);
        $this->attendees = array_values($this->attendees);
        if (empty($this->attendees)) {
            $this->attendees = [['name' => '', 'title' => '']];
        }
    }

    protected function rules(): array
    {
        return [
            'date'                => ['required', 'date'],
            'time'                => ['required'],
            'subject'             => ['required', 'string', 'max:255'],
            'location'            => ['nullable', 'string', 'max:255'],
            'result'              => ['nullable', 'string'],
            'notes'               => ['nullable', 'string'],
            'attendees'           => ['array'],
            'attendees.*.name'    => ['nullable', 'string', 'max:255'],
            'attendees.*.title'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $meetingData = [
            'date'     => $this->date,
            'time'     => $this->time,
            'subject'  => $this->subject,
            'location' => $this->location ?: null,
            'result'   => $this->result ?: null,
            'notes'    => $this->notes ?: null,
        ];

        if ($this->isEditing) {
            $this->meeting->update($meetingData);
            $meeting = $this->meeting;
            $meeting->attendees()->delete();
        } else {
            $meeting = Meeting::create($meetingData);
        }

        $order = 0;
        foreach ($this->attendees as $a) {
            $name = trim($a['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $meeting->attendees()->create([
                'name'  => $name,
                'title' => trim($a['title'] ?? '') ?: null,
                'order' => $order++,
            ]);
        }

        Flux::toast(variant: 'success', text: __($this->isEditing ? 'home.meeting_updated' : 'home.meeting_created'));
        $this->redirect(route('meetings.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.meetings.create');
    }
}
