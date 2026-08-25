<?php

namespace App\Livewire\Warehouses\Categories;

use App\Models\ItemCategory;
use App\Support\ArabicText;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('أقسام الأصناف')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);
        $category = ItemCategory::withCount('items')->findOrFail($id);
        $this->deletingId    = $category->id;
        $this->deletingLabel = $category->name;
        $this->deletingWarning = $category->items_count > 0
            ? __('home.item_category_in_use_warning', ['count' => $category->items_count])
            : '';
        $this->showDelete    = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->can('warehouses.settings'), 403);
        if ($this->deletingId) {
            $category = ItemCategory::withCount('items')->findOrFail($this->deletingId);

            // ⚠️ الحذف يُفحص هنا لا في القاعدة وحدها: الـFK بـnullOnDelete، فحذف قسم
            //    مستعمل لا يُخطئ بل يترك أصنافه بلا قسم صامتاً — وهو أسوأ من الرفض.
            if ($category->items_count > 0) {
                Flux::toast(variant: 'danger', text: __('home.item_category_in_use_warning', ['count' => $category->items_count]));
                return;
            }

            $category->delete();
            $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
            Flux::toast(variant: 'success', text: __('home.item_category_deleted'));
        }
    }

    public function render()
    {
        return view('livewire.warehouses.categories.index', [
            'categories' => ItemCategory::query()
                ->withCount('items')
                ->when($this->search, fn ($q) => $q->whereRaw(
                    ArabicText::sqlNormalize('name').' LIKE ?',
                    ['%'.ArabicText::normalize($this->search).'%']
                ))
                ->orderBy('order')
                ->orderBy('name')
                ->paginate(15),
            'canManage' => Auth::user()?->can('warehouses.settings'),
        ]);
    }
}
