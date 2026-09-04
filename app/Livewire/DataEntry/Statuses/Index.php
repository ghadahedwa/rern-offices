<?php

namespace App\Livewire\DataEntry\Statuses;

use App\Models\AttendanceStatus;
use App\Support\ArabicText;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('حالات الحضور')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('data-entry.settings'), 403);

        $status = AttendanceStatus::findOrFail($id);

        $this->deletingId      = $status->id;
        $this->deletingLabel   = $status->name;
        $this->deletingWarning = __('home.de_status_delete_warning');
        $this->showDelete      = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('data-entry.settings'), 403);

        // ⚠️ النداء يصل في طلب مستقل — فلا يُنفَّذ إجراءٌ لم يُطلب تأكيده
        if (! $this->showDelete || ! $this->deletingId) {
            return;
        }

        $status = AttendanceStatus::findOrFail($this->deletingId);

        // ⚠️ الحارسان في الإجراء لا في القالب: الزرّ مخفيّ عن الأساسية، والنداء يصل مباشرةً
        if ($status->is_system) {
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'danger', text: __('home.de_status_system_locked'));

            return;
        }

        if ($status->isInUse()) {
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'danger', text: __('home.de_status_in_use'));

            return;
        }

        $status->delete();
        $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
        Flux::toast(variant: 'success', text: __('home.de_status_deleted'));
    }

    public function render()
    {
        return view('livewire.data-entry.statuses.index', [
            'statuses' => AttendanceStatus::query()
                ->when($this->search, fn ($q) => $q->whereRaw(
                    ArabicText::sqlNormalize('name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                ))
                ->ordered()
                ->paginate(15),
        ]);
    }
}
