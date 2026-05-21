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

    public string $activeTab = 'transactions_sales';

    public array $tabs = [
        'transactions_sales' => 'المعاملات والمبيعات',
        'claims'             => 'المطالبات',
        'requests'           => 'الطلبات',
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
