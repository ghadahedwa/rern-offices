<?php

namespace App\Livewire\Warehouses;

use App\Models\ItemCategory;
use App\Models\Warehouse;
use App\Reports\CategoryStatement;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * شاشة «بيان بأرصدة {القسم}» — معاينة على الشاشة ثم طباعة بصورة الدفتر.
 *
 * ⚠️ الفلتران في الرابط لا في الـsession (بخلاف تقارير المقرات): رابط البيان
 *    صار قابلاً للمشاركة والحفظ، ويصل إلى كنترولر الطباعة كما هو.
 */
#[Layout('layouts.app')]
#[Title('بيان بأرصدة قسم')]
class Statement extends Component
{
    /** معرّف مخزن، أو '' قبل الاختيار. */
    #[Url(as: 'wh', except: '')]
    public string $warehouseId = '';

    /** معرّف قسم، أو '' قبل الاختيار. */
    #[Url(as: 'category', except: '')]
    public string $categoryId = '';

    public function mount(): void
    {
        // البيان **تصدير لا عرض**: ورقة تخرج من النظام موقّعةً، فتُفحص بـexport
        abort_unless(Auth::user()?->can('warehouses.export'), 403);
    }

    public function resetFilters(): void
    {
        $this->reset('warehouseId', 'categoryId');
    }

    public function hasActiveFilters(): bool
    {
        return $this->warehouseId !== '' || $this->categoryId !== '';
    }

    /** المخزن والقسم المختاران، أو null ما لم يكتمل الاختيار بمعرّفين صحيحين. */
    protected function selection(): ?array
    {
        // ⚠️ القيمتان تصلان من الرابط — غير الرقمي يُهمَل ولا يُمرَّر للاستعلام
        if (! ctype_digit($this->warehouseId) || ! ctype_digit($this->categoryId)) {
            return null;
        }

        $warehouse = Warehouse::find((int) $this->warehouseId);
        $category  = ItemCategory::find((int) $this->categoryId);

        return $warehouse && $category ? [$warehouse, $category] : null;
    }

    public function render()
    {
        $statement = ($pair = $this->selection())
            ? CategoryStatement::build(...$pair)
            : null;

        return view('livewire.warehouses.statement', [
            'statement'  => $statement,
            'warehouses' => Warehouse::ordered()->get(),
            'categories' => ItemCategory::orderBy('order')->orderBy('name')->get(),
        ]);
    }
}
