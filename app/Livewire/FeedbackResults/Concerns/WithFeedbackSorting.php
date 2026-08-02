<?php

namespace App\Livewire\FeedbackResults\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

/**
 * ترتيب جداول النتائج بالضغط على رأس العمود.
 *
 * ⚠️ اسم العمود يأتي من الـ URL، فلا يُمرَّر لـ orderBy إلا بعد التحقق من
 * قائمة sortableColumns() البيضاء — وإلا صار حقن SQL عبر الرابط.
 */
trait WithFeedbackSorting
{
    #[Url(as: 'sort', except: 'created_at')]
    public string $sortBy = 'created_at';

    #[Url(as: 'dir', except: 'desc')]
    public string $sortDir = 'desc';

    /** الأعمدة المسموح الترتيب بها — يوفّرها المكوّن. */
    abstract protected function sortableColumns(): array;

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortableColumns(), true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * يطبّق الترتيب المتحقَّق منه، ويضيف created_at كمُرجِّح ثانوي
     * حتى يبقى ترتيب الصفوف المتساوية ثابتاً بين الصفحات.
     */
    protected function applySorting(Builder $query): Builder
    {
        $column = in_array($this->sortBy, $this->sortableColumns(), true) ? $this->sortBy : 'created_at';
        $dir    = $this->sortDir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($column, $dir)
            ->when($column !== 'created_at', fn ($q) => $q->orderBy('created_at', 'desc'));
    }
}
