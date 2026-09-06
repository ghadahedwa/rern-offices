<?php

namespace App\Livewire\Contractors\Professions;

use App\Models\Profession;
use App\Support\ArabicText;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * صفات العاملين بالتعاقد — قائمة مرجعية تحت «إدارة النظام» كحالات الحضور.
 *
 * ⚠️ «مدخل بيانات» صفةٌ أساسية (`is_system`): هي صفة كل الصفوف التي دخلت قبل
 *    الموديول الجديد، وحذفها يترك جمهور الموديول بلا صفة.
 */
#[Layout('layouts.app')]
#[Title('صفات العاملين')]
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
        abort_unless(Auth::user()?->can('contractors.settings'), 403);

        $profession = Profession::findOrFail($id);

        $this->deletingId      = $profession->id;
        $this->deletingLabel   = $profession->name;
        $this->deletingWarning = __('home.ct_profession_delete_warning');
        $this->showDelete      = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('contractors.settings'), 403);

        // ⚠️ النداء يصل في طلب مستقل — فلا يُنفَّذ إجراءٌ لم يُطلب تأكيده
        if (! $this->showDelete || ! $this->deletingId) {
            return;
        }

        $profession = Profession::findOrFail($this->deletingId);

        // ⚠️ الحارسان في الإجراء لا في القالب: الزرّ مخفيّ عن الأساسية، والنداء يصل مباشرةً
        if ($profession->is_system) {
            $this->closeDelete();
            Flux::toast(variant: 'danger', text: __('home.ct_profession_system_locked'));

            return;
        }

        if ($profession->isInUse()) {
            $this->closeDelete();
            Flux::toast(variant: 'danger', text: __('home.ct_profession_in_use'));

            return;
        }

        $profession->delete();
        $this->closeDelete();
        Flux::toast(variant: 'success', text: __('home.ct_profession_deleted'));
    }

    private function closeDelete(): void
    {
        $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
    }

    public function render()
    {
        return view('livewire.contractors.professions.index', [
            'professions' => Profession::query()
                ->withCount('contractors')
                ->when($this->search, fn ($q) => $q->whereRaw(
                    ArabicText::sqlNormalize('name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                ))
                ->ordered()
                ->paginate(15),
        ]);
    }
}
