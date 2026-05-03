<?php

namespace App\Livewire\Offices;

use App\Models\BuffetService;
use App\Models\CleanlinessContract;
use App\Models\ConnectionType;
use App\Models\ContractualStatus;
use App\Models\DisabilitieAccess;
use App\Models\DocumentPhotocopyingService;
use App\Models\FireSafety;
use App\Models\Governorate;
use App\Models\LocationDescription;
use App\Models\MicrofilmOption;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\WorkingHour;
use App\Models\WorkSystem;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('مقر')]
class Create extends Component
{
    public int $step = 1;
    public int $totalSteps = 4;
    public ?int $office_id = null;
    public bool $isEditing = false;

    // Step 1 — Basic Info
    #[Url]
    public ?int $governorate_id = null;
    public ?int $parent_office_id = null;
    public string $name = '';
    public string $established_at = '';
    public ?int $type_id = null;
    public ?int $location_description_id = null;
    public ?int $work_system_id = null;
    public string $address = '';
    public string $google_maps_link = '';
    public string $floors_description = '';
    public ?int $connection_type_id = null;
    public ?int $working_hours_id = null;
    public string $avg_daily_transactions = '';
    public ?int $contractual_status_id = null;

    public string $structural_condition = '';
    public string $office_area = '';
    public string $district_court = '';

    // Step 2 — Services & Equipment
    public ?int $microfilm_option_id = null;
    public ?int $disabilities_access_id = null;
    public ?int $fire_safety_id = null;
    public ?int $document_photocopying_service_id = null;
    public ?int $buffet_service_id = null;
    public ?int $cleanliness_contract_id = null;
    public string $Braille_sign_device = '';
    public string $queue_management_system = '';
    public string $payment_machine_count = '';
    public string $computers_count = '';
    public string $scanners_count = '';
    public string $printers_count = '';
    public string $fingerprints_count = '';

    public function mount(?Office $office = null): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if ($office) {
            abort_unless($user?->hasRole('super-admin') || $user?->can('offices.edit'), 403);
            $this->isEditing = true;
            $this->office_id = $office->id;
            $this->loadOffice($office);
        } else {
            abort_unless($user?->hasRole('super-admin') || $user?->can('offices.create'), 403);
        }
    }

    private function loadOffice(Office $office): void
    {
        // Step 1
        $this->governorate_id          = $office->governorate_id;
        $this->parent_office_id        = $office->parent_office_id;
        $this->name                    = $office->name ?? '';
        $this->established_at          = $office->established_at?->format('Y-m-d') ?? '';
        $this->type_id                 = $office->type_id;
        $this->location_description_id = $office->location_description_id;
        $this->work_system_id          = $office->work_system_id;
        $this->address                 = $office->address ?? '';
        $this->google_maps_link        = $office->google_maps_link ?? '';
        $this->floors_description      = $office->floors_description ?? '';
        $this->connection_type_id      = $office->connection_type_id;
        $this->working_hours_id        = $office->working_hours_id;
        $this->avg_daily_transactions  = (string) ($office->avg_daily_transactions ?? '');
        $this->contractual_status_id   = $office->contractual_status_id;
        $this->structural_condition    = $office->structural_condition ?? '';
        $this->office_area             = (string) ($office->office_area ?? '');
        $this->district_court          = $office->district_court ?? '';

        // Step 2
        $this->microfilm_option_id              = $office->microfilm_option_id;
        $this->disabilities_access_id           = $office->disabilities_access_id;
        $this->fire_safety_id                   = $office->fire_safety_id;
        $this->document_photocopying_service_id = $office->document_photocopying_service_id;
        $this->buffet_service_id                = $office->buffet_service_id;
        $this->cleanliness_contract_id          = $office->cleanliness_contract_id;
        $this->Braille_sign_device              = $office->Braille_sign_device ?? '';
        $this->queue_management_system          = $office->queue_management_system ?? '';
        $this->payment_machine_count            = (string) ($office->payment_machine_count ?? '');
        $this->computers_count                  = (string) ($office->computers_count ?? '');
        $this->scanners_count                   = (string) ($office->scanners_count ?? '');
        $this->printers_count                   = (string) ($office->printers_count ?? '');
        $this->fingerprints_count               = (string) ($office->fingerprints_count ?? '');
    }

    private function step1Validation(): void
    {
        $this->validate([
            'governorate_id'  => 'required|exists:governorates,id',
            'name'            => 'required|string|max:255',
            'type_id'         => 'required|exists:office_types,id',
            'working_hours_id' => 'required|exists:working_hours,id',
        ], [
            'governorate_id.required'   => 'يرجى اختيار المحافظة',
            'name.required'             => 'يرجى إدخال اسم المقر',
            'type_id.required'          => 'يرجى اختيار نوع المقر',
            'working_hours_id.required' => 'يرجى اختيار ساعات العمل',
        ]);
    }

    private function step1Data(): array
    {
        return [
            'governorate_id'         => $this->governorate_id,
            'parent_office_id'       => $this->parent_office_id ?: null,
            'name'                   => $this->name,
            'established_at'         => $this->established_at ?: null,
            'type_id'                => $this->type_id,
            'location_description_id' => $this->location_description_id ?: null,
            'work_system_id'         => $this->work_system_id ?: null,
            'address'                => $this->address ?: null,
            'google_maps_link'       => $this->google_maps_link ?: null,
            'floors_description'     => $this->floors_description ?: null,
            'connection_type_id'     => $this->connection_type_id ?: null,
            'working_hours_id'       => $this->working_hours_id,
            'avg_daily_transactions' => $this->avg_daily_transactions !== '' ? (int) $this->avg_daily_transactions : null,
            'contractual_status_id'  => $this->contractual_status_id ?: null,
            'structural_condition'   => $this->structural_condition ?: null,
            'office_area'            => $this->office_area !== '' ? (int) $this->office_area : null,
            'district_court'         => $this->district_court ?: null,
        ];
    }

    private function step2Data(): array
    {
        return [
            'microfilm_option_id'               => $this->microfilm_option_id ?: null,
            'disabilities_access_id'            => $this->disabilities_access_id ?: null,
            'fire_safety_id'                    => $this->fire_safety_id ?: null,
            'document_photocopying_service_id'  => $this->document_photocopying_service_id ?: null,
            'buffet_service_id'                 => $this->buffet_service_id ?: null,
            'cleanliness_contract_id'           => $this->cleanliness_contract_id ?: null,
            'Braille_sign_device'               => $this->Braille_sign_device ?: null,
            'queue_management_system'           => $this->queue_management_system ?: null,
            'payment_machine_count'             => $this->payment_machine_count !== '' ? (int) $this->payment_machine_count : null,
            'computers_count'                   => $this->computers_count !== '' ? (int) $this->computers_count : null,
            'scanners_count'                    => $this->scanners_count !== '' ? (int) $this->scanners_count : null,
            'printers_count'                    => $this->printers_count !== '' ? (int) $this->printers_count : null,
            'fingerprints_count'                => $this->fingerprints_count !== '' ? (int) $this->fingerprints_count : null,
        ];
    }

    private function persistStep1(): void
    {
        if ($this->office_id) {
            Office::find($this->office_id)?->update($this->step1Data());
        } else {
            $office = Office::create($this->step1Data());
            $this->office_id = $office->id;
        }
    }

    private function persistStep2(): void
    {
        Office::find($this->office_id)?->update($this->step2Data());
    }

    public function saveAndExit(): void
    {
        $this->step1Validation();
        $this->persistStep1();
        Flux::toast(variant: 'success', text: __('home.office_created'));
        $this->redirect(route('offices.index'), navigate: true);
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->step1Validation();
            $this->persistStep1();
        } elseif ($this->step === 2) {
            $this->persistStep2();
        }
        $this->step++;
    }

    public function prevStep(): void
    {
        $this->step--;
    }

    public function save(): void
    {
        Flux::toast(variant: 'success', text: __('home.office_created'));
        $this->redirect(route('offices.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.offices.create', [
            'governorates'        => Governorate::orderBy('id')->get(),
            'mainOffices'         => Office::with('officeType')->whereNotNull('type_id')->orderBy('name')->get(),
            'types'               => OfficeType::orderBy('id')->get(),
            'locations'           => LocationDescription::orderBy('id')->get(),
            'workSystems'         => WorkSystem::orderBy('id')->get(),
            'connections'         => ConnectionType::orderBy('id')->get(),
            'workingHoursOptions' => WorkingHour::orderBy('id')->get(),
            'contractualStatuses' => ContractualStatus::orderBy('id')->get(),
            'microfilmOptions'             => MicrofilmOption::orderBy('id')->get(),
            'disabilitiesOptions'          => DisabilitieAccess::orderBy('id')->get(),
            'fireSafetyOptions'            => FireSafety::orderBy('id')->get(),
            'photocopyingOptions'          => DocumentPhotocopyingService::orderBy('id')->get(),
            'buffetOptions'               => BuffetService::orderBy('id')->get(),
            'cleanlinessContractOptions'  => CleanlinessContract::orderBy('id')->get(),
        ]);
    }
}
