<?php

namespace App\Livewire\Correspondence\Entities;

use App\Models\CorrespondenceEntity;
use App\Support\ArabicText;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('أطراف المراسلات')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    /** فلتر الحالة: '' الكل | 'yes' مُفعَّل | 'no' موقوف */
    public string $activeFilter = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('correspondence.settings'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActiveFilter(): void
    {
        $this->resetPage();
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('correspondence.settings'), 403);

        $entity = CorrespondenceEntity::findOrFail($id);
        $this->deletingId    = $entity->id;
        $this->deletingLabel = $entity->name;
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('correspondence.settings'), 403);

        // لا يُنفَّذ إجراء مؤجَّل بطلب مستقل بعد إغلاق المودال
        if (! $this->showDelete || ! $this->deletingId) {
            return;
        }

        CorrespondenceEntity::findOrFail($this->deletingId)->delete();
        $this->reset('deletingId', 'deletingLabel', 'showDelete');

        Flux::toast(variant: 'success', text: __('home.corr_entity_deleted'));
    }

    public function render()
    {
        return view('livewire.correspondence.entities.index', [
            'entities' => CorrespondenceEntity::query()
                ->withCount('users')
                // البحث العربي المطبَّع (قاعدة المشروع) — يوحّد الألف والياء والتاء المربوطة
                ->when($this->search, fn ($q) => $q->whereRaw(
                    ArabicText::sqlNormalize('name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                ))
                ->when($this->activeFilter !== '', fn ($q) => $q->where('is_active', $this->activeFilter === 'yes'))
                ->orderBy('order')
                ->orderBy('id')
                ->paginate(15),
        ]);
    }
}
