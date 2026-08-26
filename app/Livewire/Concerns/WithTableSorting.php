<?php

namespace App\Livewire\Concerns;

use App\Support\TableSort;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * ترتيب الجدول بالضغط على رأس العمود، محفوظاً في الرابط.
 *
 * دورة الضغط ثلاثية: تصاعدي ← تنازلي ← **الترتيب الافتراضي للشاشة**.
 * الحالة الثالثة ليست زينة: الترتيب الافتراضي هنا ذو معنى تنظيمي
 * (المخازن بالمستوى ثم المحافظة · الأصناف بالقسم ثم ترتيبها داخله)،
 * فبلا طريق للرجوع إليه يفقده المستخدم بضغطة ولا يستعيده إلا بمسح الرابط.
 *
 * ⚠️ اسم العمود يأتي من الرابط — التحقق من الخريطة البيضاء في
 *    App\Support\TableSort، ولا يُمرَّر لـ orderBy قبله.
 */
trait WithTableSorting
{
    /** '' = الترتيب الافتراضي للشاشة. */
    #[Url(as: 'sort', except: '')]
    public string $sortBy = '';

    #[Url(as: 'dir', except: 'asc')]
    public string $sortDir = 'asc';

    /**
     * الأعمدة المسموح الترتيب بها: مفتاح الرابط ← عمود SQL (أو عدة أعمدة).
     *
     * @return array<string, string|array<int, string>>
     */
    abstract protected function sortableColumns(): array;

    /** الترتيب الافتراضي للشاشة — يُطبَّق ما لم يختر المستخدم عموداً. */
    abstract protected function defaultOrder(Builder $query): Builder;

    public function sort(string $column): void
    {
        if (! array_key_exists($column, $this->sortableColumns())) {
            return;
        }

        if ($this->sortBy !== $column) {
            $this->sortBy  = $column;
            $this->sortDir = 'asc';
        } elseif ($this->sortDir === 'asc') {
            $this->sortDir = 'desc';
        } else {
            $this->resetSort();
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function resetSort(): void
    {
        $this->sortBy  = '';
        $this->sortDir = 'asc';
    }

    /** هل الشاشة على ترتيبٍ اختاره المستخدم (لا الافتراضي)؟ */
    public function isCustomSorted(): bool
    {
        return array_key_exists($this->sortBy, $this->sortableColumns());
    }

    protected function applySorting(Builder $query, ?string $tieBreaker = null, string $tieBreakerDir = 'asc'): Builder
    {
        return TableSort::apply(
            $query,
            $this->sortBy,
            $this->sortDir,
            $this->sortableColumns(),
            fn (Builder $q) => $this->defaultOrder($q),
            $tieBreaker,
            $tieBreakerDir,
        );
    }
}
