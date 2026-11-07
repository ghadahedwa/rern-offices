<?php

namespace App\Livewire\Finance\CourtBuildingItems;

use App\Models\CourtBuildingItem;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * عناصر حساب أبنية المحاكم — قائمة مرجعية تحت «إدارة النظام».
 *
 * ⚠️ **سوبر أدمن وحده** (نمط العطلات الرسمية): العنصر يدخل في مجموع حسابٍ مالي
 *    يصدر به أمر دفع، والعميل لا يمنح التصحيح إلا للسوبر أدمن — فلا صلاحية
 *    خامسة تفتح القائمة لغيره.
 */
#[Layout('layouts.app')]
#[Title('عناصر أبنية المحاكم')]
class Index extends Component
{
    public bool $showDelete = false;
    public ?int $deletingId = null;
    public string $deletingLabel = '';
    public string $deletingWarning = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);
    }

    public function askDelete(int $id): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);

        $item = CourtBuildingItem::findOrFail($id);

        $this->deletingId      = $item->id;
        $this->deletingLabel   = $item->name;
        $this->deletingWarning = __('home.fin_item_delete_warning');
        $this->showDelete      = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);

        // ⚠️ النداء يصل في طلب مستقل — فلا يُنفَّذ إجراءٌ لم يُطلب تأكيده
        if (! $this->showDelete || ! $this->deletingId) {
            return;
        }

        $item = CourtBuildingItem::findOrFail($this->deletingId);

        // ⚠️ الحارسان في الإجراء لا في القالب: الزرّ مخفيّ، والنداء يصل بلا زرّ
        if ($item->isInUse()) {
            $this->closeDelete();
            Flux::toast(variant: 'danger', text: __('home.fin_item_in_use'));

            return;
        }

        if ($item->is_active && CourtBuildingItem::active()->count() === 1) {
            $this->closeDelete();
            Flux::toast(variant: 'danger', text: __('home.fin_item_last_active'));

            return;
        }

        $item->delete();
        $this->closeDelete();
        Flux::toast(variant: 'success', text: __('home.fin_item_deleted'));
    }

    private function closeDelete(): void
    {
        $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
    }

    public function render()
    {
        return view('livewire.finance.court-building-items.index', [
            'items' => CourtBuildingItem::ordered()->get(),
        ]);
    }
}
