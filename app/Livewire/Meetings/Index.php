<?php

namespace App\Livewire\Meetings;

use App\Models\Meeting;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('أجندة الاجتماعات')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $dateFilter = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('meetings.index'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    protected function canDelete(): bool
    {
        return Auth::user()?->can('meetings.delete');
    }

    public function askDelete(int $id): void
    {
        abort_unless($this->canDelete(), 403);
        $meeting = Meeting::findOrFail($id);
        $this->deletingId    = $meeting->id;
        $this->deletingLabel = $meeting->subject;
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless($this->canDelete(), 403);
        if ($this->deletingId) {
            Meeting::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.meeting_deleted'));
        }
    }

    public function render()
    {
        $user = Auth::user();

        $meetings = Meeting::query()
            ->with('attendees')
            ->when($this->search, function ($q) {
                $s = $this->search;
                $q->where(function ($w) use ($s) {
                    $w->where('subject', 'like', "%{$s}%")
                      ->orWhere('location', 'like', "%{$s}%")
                      ->orWhere('result', 'like', "%{$s}%")
                      ->orWhereHas('attendees', fn ($a) => $a->where('name', 'like', "%{$s}%")
                                                             ->orWhere('title', 'like', "%{$s}%"));
                });
            })
            ->when($this->dateFilter, fn ($q) => $q->whereDate('date', $this->dateFilter))
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->paginate(10);

        return view('livewire.meetings.index', [
            'meetings'  => $meetings,
            'canCreate' => $user?->can('meetings.create'),
            'canEdit'   => $user?->can('meetings.edit'),
            'canDelete' => $user?->can('meetings.delete'),
        ]);
    }
}
