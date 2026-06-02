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

    public array $tabs = [
        'transactions'           => 'معاملات التوثيق',
        'forms_folders'          => 'نماذج وحوافظ التوثيق',
        'shaher_requests'        => 'طلبات الشهر',
        'law9_registrations'     => 'مشهرات قانون ٩',
        'monthly_forms_folders'  => 'نماذج وحوافظ قانون ٩',
        'law27_forms_folders'    => 'نماذج وحوافظ قانون ٢٧',
        'registry_requests'      => 'طلبات السجل',
        'registry_forms_folders' => 'نماذج وحوافظ سجل',
    ];

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
