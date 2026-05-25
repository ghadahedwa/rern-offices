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

    public string $activeTab = 'transactions';

    public array $tabs = [
        'transactions'           => 'معاملات التوثيق',
        'forms_folders'          => 'نماذج وحوافظ توثيق',
        'shaher_requests'        => 'طلبات الشهر',
        'monthly_forms_folders'  => 'نماذج وحوافظ شهر',
        'registry_requests'      => 'طلبات السجل',
        'registry_forms_folders' => 'نماذج وحوافظ سجل',
    ];

    public function mount(Office $office): void
    {
        abort_unless(
            auth()->user()?->hasRole('super-admin') || auth()->user()?->can('offices.edit'),
            403
        );

        $this->office = $office;
    }

    public function render()
    {
        return view('livewire.offices.statistics');
    }
}
