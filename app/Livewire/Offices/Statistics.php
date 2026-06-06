<?php

namespace App\Livewire\Offices;

use App\Models\Office;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('الإحصائيات')]
class Statistics extends Component
{
    public Office $office;
    public bool $canEdit = false;
    public bool $canView = false;

    public string $activeTab = 'transactions';

    /**
     * كل التابات المعرّفة (لا تُحذف — مرجع كامل).
     */
    public array $allTabs = [
        'transactions'           => 'معاملات التوثيق',
        'forms_folders'          => 'النماذج والحوافظ',
        'shaher_requests'        => 'طلبات الشهر',
        'law9_registrations'     => 'مشهرات قانون ٩',
        'monthly_forms_folders'  => 'نماذج وحوافظ قانون ٩',
        'law27_forms_folders'    => 'نماذج وحوافظ قانون ٢٧',
        'registry_requests'      => 'طلبات السجل',
        'registry_forms_folders' => 'نماذج وحوافظ سجل',
    ];

    /**
     * التابات الظاهرة وبالترتيب المطلوب (طلب العميل).
     * لإظهار/إخفاء/إعادة ترتيب تاب: عدّل هذه القائمة فقط.
     */
    public array $visibleKeys = [
        'transactions',       // معاملات التوثيق
        'shaher_requests',    // طلبات الشهر
        'law9_registrations', // مشهرات قانون ٩
        'registry_requests',  // طلبات السجل
        'forms_folders',      // النماذج والحوافظ
    ];

    /**
     * التابات المعروضة فعلياً (key => label) بالترتيب.
     */
    public function getTabsProperty(): array
    {
        $tabs = [];
        foreach ($this->visibleKeys as $key) {
            if (isset($this->allTabs[$key])) {
                $tabs[$key] = $this->allTabs[$key];
            }
        }

        return $tabs;
    }

    public function mount(Office $office): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.view') || $user?->can('offices.edit'),
            403
        );

        $this->canEdit = $user?->hasRole('super-admin') || $user?->can('offices.edit');
        $this->canView = $user?->hasRole('super-admin') || $user?->can('offices.view');
        $this->office  = $office;
    }

    public function render()
    {
        return view('livewire.offices.statistics');
    }
}
