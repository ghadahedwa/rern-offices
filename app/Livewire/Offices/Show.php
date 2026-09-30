<?php

namespace App\Livewire\Offices;

use App\Models\Office;
use App\Support\OfficeVisitReports;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('تفاصيل المقر')]
class Show extends Component
{
    public Office $office;
    public string $activeTab = 'basic';
    public bool $canEdit = false;

    public function mount(Office $office): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasRole('super-admin') || $user?->can('offices.view') || $user?->can('offices.edit'),
            403
        );

        $this->canEdit = $user?->hasRole('super-admin') || $user?->can('offices.edit');

        activity()
            ->performedOn($office)
            ->causedBy($user)
            ->event('viewed')
            ->log('عرض مقر');

        $this->office = $office->load([
            'governorate',
            'officeType',
            'locationDescription',
            'workSystem',
            'workingHour',
            'connectionType',
            'contractualStatus',
            'MicrofilmOption',
            'DisabilitieAccess',
            'FireSafety',
            'DocumentPhotocopyingService',
            'BuffetService',
            'CleanlinessContract',
            'brokenDevices.deviceType',
            'media',
        ]);
    }

    /** التابات: مفتاحها => عنوانها. تقريرا الزيارة صفحتان مستقلتان بزرّين في الرأس. */
    public function tabs(): array
    {
        return [
            'basic'    => __('home.step_1_label'),
            'services' => __('home.step_2_label'),
            'media'    => __('home.step_4_label'),
        ];
    }

    public function render()
    {
        // التاب يُضبط من المتصفح (`$set`) — قيمة مجهولة تُردّ للأولى
        $tabs = $this->tabs();
        if (! array_key_exists($this->activeTab, $tabs)) {
            $this->activeTab = 'basic';
        }

        return view('livewire.offices.show', [
            'tabs'    => $tabs,
            // زرّ لكل تقريرٍ يملك المستخدم عرضه — الصفحة نفسها تحرس نفسها
            'reports' => OfficeVisitReports::viewable(auth()->user()),
        ]);
    }
}
