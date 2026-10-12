<?php

namespace App\Livewire\Governorates;

use App\Models\Governorate;
use App\Support\ArabicText;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('المحافظات')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    /** الوصول: super-admin أو من يملك صلاحية governorates.index */
    public function mount(): void
    {
        abort_unless(
            Auth::user()?->hasRole('super-admin') || Auth::user()?->can('governorates.index'),
            403
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    protected function canDelete(): bool
    {
        return Auth::user()?->hasRole('super-admin') || Auth::user()?->can('governorates.delete');
    }

    public function askDelete(int $id): void
    {
        abort_unless($this->canDelete(), 403);
        $governorate = Governorate::withCount('offices')->findOrFail($id);
        $this->deletingId    = $governorate->id;
        $this->deletingLabel = $governorate->name;
        $this->deletingWarning = $governorate->offices_count > 0
            ? __('home.governorate_delete_warning', ['count' => $governorate->offices_count])
            : __('home.governorate_delete_warning_empty');
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless($this->canDelete(), 403);
        if ($this->deletingId) {
            Governorate::findOrFail($this->deletingId)->delete();
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.governorate_deleted'));
        }
    }

    public function render()
    {
        $isSuperAdmin = Auth::user()?->hasRole('super-admin');

        $query = $isSuperAdmin ? Governorate::query() : auth()->user()->governorates();

        // البحث المطبَّع (الألف/الياء/التاء المربوطة) في اسم المحافظة والمستشار المشرف — كباقي الشاشات
        $term = ArabicText::normalize($this->search);

        $governorates = $query->withCount('offices')
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereRaw(ArabicText::sqlNormalize('governorates.name').' LIKE ?', ['%'.$term.'%'])
                ->orWhereRaw(ArabicText::sqlNormalize('governorates.supervising_counselor').' LIKE ?', ['%'.$term.'%'])
            ))
            ->orderBy('governorates.order')->orderBy('governorates.id')
            ->paginate(10);

        $user = Auth::user();

        return view('livewire.governorates.index', [
            'governorates' => $governorates,
            'isSuperAdmin' => $isSuperAdmin,
            'canCreate'    => $isSuperAdmin || $user?->can('governorates.create'),
            'canEdit'      => $isSuperAdmin || $user?->can('governorates.edit'),
            'canDelete'    => $isSuperAdmin || $user?->can('governorates.delete'),
        ]);
    }
}
