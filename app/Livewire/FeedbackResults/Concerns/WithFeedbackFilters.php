<?php

namespace App\Livewire\FeedbackResults\Concerns;

use App\Models\Governorate;
use App\Models\Office;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * الفلاتر المشتركة لشاشات نتائج بوابة رأي المواطن (محافظة / مقر / فترة).
 * مربوطة بالـ URL حتى تبقى الحالة عند المشاركة أو الرجوع.
 *
 * ملاحظة نطاق: الشاشات حالياً super-admin فقط، لكن applyScope() هو النقطة
 * الوحيدة اللي هتتوسّع فيها فلترة محافظات المستخدم عند فتح الموديول لأدوار أخرى.
 */
trait WithFeedbackFilters
{
    #[Url(as: 'gov', except: '')]
    public string $governorate_id = '';

    #[Url(as: 'office', except: '')]
    public string $office_id = '';

    #[Url(as: 'from', except: '')]
    public string $from = '';

    #[Url(as: 'to', except: '')]
    public string $to = '';

    public function updatedGovernorateId(): void
    {
        $this->office_id = '';
        $this->afterFilterChange();
    }

    public function updatedOfficeId(): void
    {
        $this->afterFilterChange();
    }

    public function updatedFrom(): void
    {
        $this->afterFilterChange();
    }

    public function updatedTo(): void
    {
        $this->afterFilterChange();
    }

    public function resetFilters(): void
    {
        $this->reset('governorate_id', 'office_id', 'from', 'to');
        $this->afterFilterChange();
    }

    /** هل في فلتر مفعّل حالياً؟ (لإظهار زر إعادة الضبط) */
    public function hasActiveFilters(): bool
    {
        return $this->governorate_id !== '' || $this->office_id !== ''
            || $this->from !== '' || $this->to !== '';
    }

    /** الصفحات ذات الـ pagination ترجع للصفحة الأولى عند تغيّر الفلتر. */
    protected function afterFilterChange(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * يطبّق فلاتر المحافظة/المقر/الفترة على أي استعلام فيه
     * الأعمدة office_id / created_at (والمحافظة عبر filterByGovernorate).
     */
    protected function applyFilters(Builder $query): Builder
    {
        return $this->applyScope($query)
            ->when($this->governorate_id !== '', fn ($q) => $this->filterByGovernorate($q))
            ->when($this->office_id !== '', fn ($q) => $q->where('office_id', $this->office_id))
            ->when($this->from !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->to));
    }

    /**
     * فلترة المحافظة — الافتراضي على العمود المخزَّن (التقييمات/المقترحات).
     * جدول المحاولات المرفوضة ليس فيه العمود، فيتجاوزها المكوّن عبر علاقة المقر.
     */
    protected function filterByGovernorate(Builder $query): Builder
    {
        return $query->where('governorate_id', $this->governorate_id);
    }

    /**
     * نطاق رؤية المستخدم. مفتوح حالياً (الشاشات super-admin فقط)؛
     * عند فتح الموديول لأدوار أخرى تُضاف هنا فلترة محافظات المستخدم.
     */
    protected function applyScope(Builder $query): Builder
    {
        return $query;
    }

    #[Computed]
    public function governorateOptions()
    {
        return Governorate::orderBy('order')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * مقرات المحافظة المختارة — كل المقرات لا العامة فقط، لأن رأياً قديماً
     * قد يكون معلّقاً على مقر تغيّر نوعه لاحقاً فيلزم أن يظل قابلاً للفلترة.
     */
    #[Computed]
    public function officeOptions()
    {
        if ($this->governorate_id === '') {
            return collect();
        }

        return Office::where('governorate_id', $this->governorate_id)
            ->orderBy('name')->get(['id', 'name']);
    }
}
