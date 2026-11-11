<?php

namespace App\Livewire\Finance\Units;

use App\Models\FinanceUnit;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * الوحدات الحسابية — قائمة مرجعية تحت «إدارة النظام»، **سوبر أدمن وحده**
 * (نمط عناصر أبنية المحاكم: الوحدة تدخل في إجمالي حسابٍ يصدر به أمر دفع).
 */
#[Layout('layouts.app')]
#[Title('الوحدات الحسابية')]
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

        $unit = FinanceUnit::findOrFail($id);

        $this->deletingId      = $unit->id;
        $this->deletingLabel   = $unit->name;
        $this->deletingWarning = __('home.fin_unit_delete_warning');
        $this->showDelete      = true;
    }

    public function deleteRow(): void
    {
        abort_unless(Auth::user()?->hasRole('super-admin'), 403);

        // ⚠️ النداء يصل في طلب مستقل — فلا يُنفَّذ إجراءٌ لم يُطلب تأكيده
        if (! $this->showDelete || ! $this->deletingId) {
            return;
        }

        $unit = FinanceUnit::findOrFail($this->deletingId);

        // ⚠️ الحارسان في الإجراء لا في القالب: الزرّ مخفيّ، والنداء يصل بلا زرّ
        if ($unit->isInUse()) {
            $this->closeDelete();
            Flux::toast(variant: 'danger', text: __('home.fin_unit_in_use'));

            return;
        }

        if ($unit->is_active && FinanceUnit::active()->count() === 1) {
            $this->closeDelete();
            Flux::toast(variant: 'danger', text: __('home.fin_unit_last_active'));

            return;
        }

        $unit->delete();
        $this->closeDelete();
        Flux::toast(variant: 'success', text: __('home.fin_unit_deleted'));
    }

    private function closeDelete(): void
    {
        $this->reset('deletingId', 'deletingLabel', 'deletingWarning', 'showDelete');
    }

    public function render()
    {
        return view('livewire.finance.units.index', [
            'units' => FinanceUnit::ordered()->get(),
        ]);
    }
}
