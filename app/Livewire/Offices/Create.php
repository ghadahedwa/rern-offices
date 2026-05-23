<?php

namespace App\Livewire\Offices;

use App\Models\BuffetService;
use App\Models\DeviceType;
use App\Models\OfficeBrokenDevice;
use App\Models\OfficeMedia;
use App\Models\OfficeStat;
use App\Models\StructuralCondition;
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
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('مقر')]
class Create extends Component
{
    use WithFileUploads;
    #[Url]
    public int $step = 1;
    public int $totalSteps = 4;
    #[Url]
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

    public ?int $structural_condition_id = null;
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
    public string $monitors_count = '';
    public string $scanners_count = '';
    public string $printers_count = '';
    public string $fingerprints_count = '';

    // Step 2 — extra
    public string $windows_count = '';
    public string $air_conditioners_count = '';

    // Step 3 — Assessments
    public string $visited_at = '';
    public string $cleanliness_rating = '';
    public string $archive_rating = '';
    public string $work_schedule_commitment = '';
    public string $citizen_treatment_commitment = '';
    public string $surveillance_cameras = '';
    public string $negatives_and_solutions = '';
    public string $development_proposals = '';
    public string $office_needs = '';

    // Step 3 — Broken devices (array of [device_type_id => int, count => int])
    public array $brokenDevices = [];

    // Step 4 — Statistics
    public array $transactionStats = []; // [year, value]
    public array $formSalesStats    = []; // [year, month, value]
    public array $folderSalesStats  = []; // [year, month, value]

    // Step 4 — Media uploads (single-file, instant save)
    public mixed $newPhoto    = null;
    public mixed $newVideo    = null;
    public mixed $newDocument = null;

    // kept for legacy validation compatibility
    public array $newPhotos    = [];
     public array $newVideos    = [];
    public array $newDocuments = [];

    public function mount(?Office $office = null): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if ($office) {
            abort_unless($user?->hasRole('super-admin') || $user?->can('offices.edit'), 403);
            $this->isEditing = true;
            $this->office_id = $office->id;
            $this->loadOffice($office);
        } elseif ($this->office_id) {
            // Resuming a create session (e.g. browser back) — office already persisted
            $existing = Office::find($this->office_id);
            if ($existing) {
                abort_unless($user?->hasRole('super-admin') || $user?->can('offices.create') || $user?->can('offices.edit'), 403);
                $this->loadOffice($existing);
            } else {
                $this->office_id = null;
                abort_unless($user?->hasRole('super-admin') || $user?->can('offices.create'), 403);
            }
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
        $this->structural_condition_id = $office->structural_condition_id;
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
        $this->monitors_count                   = (string) ($office->monitors_count ?? '');
        $this->scanners_count                   = (string) ($office->scanners_count ?? '');
        $this->printers_count                   = (string) ($office->printers_count ?? '');
        $this->fingerprints_count               = (string) ($office->fingerprints_count ?? '');
        $this->windows_count                    = (string) ($office->windows_count ?? '');
        $this->air_conditioners_count           = (string) ($office->air_conditioners_count ?? '');

        // Step 3
        $this->visited_at                   = $office->visited_at?->format('Y-m-d') ?? '';
        $this->cleanliness_rating           = $office->cleanliness_rating ?? '';
        $this->archive_rating               = $office->archive_rating ?? '';
        $this->work_schedule_commitment     = $office->work_schedule_commitment ?? '';
        $this->citizen_treatment_commitment = $office->citizen_treatment_commitment ?? '';
        $this->surveillance_cameras         = $office->surveillance_cameras ?? '';
        $this->negatives_and_solutions      = $office->negatives_and_solutions ?? '';
        $this->development_proposals        = $office->development_proposals ?? '';
        $this->office_needs                 = $office->office_needs ?? '';

        $this->brokenDevices = $office->brokenDevices()
            ->get()
            ->map(fn($d) => ['device_type_id' => $d->device_type_id, 'count' => $d->count])
            ->values()
            ->toArray();

        $stats = $office->statistics()->get();
        $this->transactionStats = $stats->where('stat_type_id', 1)
            ->map(fn($s) => ['year' => $s->year, 'value' => $s->value])
            ->values()->toArray();
        $this->formSalesStats = $stats->where('stat_type_id', 2)
            ->map(fn($s) => ['year' => $s->year, 'month' => $s->month, 'value' => $s->value])
            ->values()->toArray();
        $this->folderSalesStats = $stats->where('stat_type_id', 3)
            ->map(fn($s) => ['year' => $s->year, 'month' => $s->month, 'value' => $s->value])
            ->values()->toArray();
    }

    private function step1Validation(): void
    {
        $this->validate([
            'governorate_id'  => 'required|exists:governorates,id',
            'name'            => 'required|string|max:255',
            'type_id'         => 'required|exists:office_types,id',
            'location_description_id' => 'required|exists:location_descriptions,id',
            'working_hours_id' => 'required|exists:working_hours,id',
        ], [
            'governorate_id.required'   => 'يرجى اختيار المحافظة',
            'name.required'             => 'يرجى إدخال اسم المقر',
            'type_id.required'          => 'يرجى اختيار نوع المقر',
            'location_description_id.required' => 'يرجى اختيار وصف المقر',
            'working_hours_id.required' => 'يرجى اختيار ساعات العمل',
        ]);
    }

    public function clearPhotos(): void    { $this->newPhoto    = null; }
    public function clearVideo(): void     { $this->newVideo    = null; }
    public function clearDocuments(): void { $this->newDocument = null; }

    public function uploadPhoto(): void
    {
        $this->validate(
            ['newPhoto' => 'required|image|max:5120'],
            ['newPhoto.required' => 'يرجى اختيار صورة', 'newPhoto.image' => 'يجب أن يكون الملف صورة', 'newPhoto.max' => 'الحد الأقصى 5 ميجا']
        );

        $existing = OfficeMedia::where('office_id', $this->office_id)->where('type', 'photo')->count();
        if ($existing >= 10) { return; }

        $path = $this->newPhoto->store("offices/{$this->office_id}/photos", 'public');
        OfficeMedia::create(['office_id' => $this->office_id, 'type' => 'photo', 'path' => $path, 'original_name' => $this->newPhoto->getClientOriginalName()]);

        $this->newPhoto = null;
        $this->dispatch('mediaUploaded');
    }

    public function uploadVideo(): void
    {
        $this->validate(
            ['newVideo' => 'required|mimetypes:video/mp4,video/avi,video/x-msvideo,video/quicktime,video/webm|max:102400'],
            ['newVideo.required' => 'يرجى اختيار فيديو', 'newVideo.mimetypes' => 'نوع الفيديو غير مدعوم (MP4, AVI, MOV فقط)', 'newVideo.max' => 'الحد الأقصى 100 ميجا']
        );

        $existing = OfficeMedia::where('office_id', $this->office_id)->where('type', 'video')->count();
        if ($existing >= 1) { return; }
        $path = $this->newVideo->store("offices/{$this->office_id}/videos", 'public');
        OfficeMedia::create(['office_id' => $this->office_id, 'type' => 'video', 'path' => $path, 'original_name' => $this->newVideo->getClientOriginalName()]);


        $this->newVideo = null;
        $this->dispatch('mediaUploaded');
    }

    public function uploadDocument(): void
    {
        $this->validate(
            ['newDocument' => 'required|mimes:pdf|max:10240'],
            ['newDocument.required' => 'يرجى اختيار ملف', 'newDocument.mimes' => 'يجب أن يكون الملف PDF', 'newDocument.max' => 'الحد الأقصى 10 ميجا']
        );
$existing = OfficeMedia::where('office_id', $this->office_id)->where('type', 'document')->count();
        if ($existing >= 1) { return; }
        $path = $this->newDocument->store("offices/{$this->office_id}/documents", 'public');
        OfficeMedia::create(['office_id' => $this->office_id, 'type' => 'document', 'path' => $path, 'original_name' => $this->newDocument->getClientOriginalName()]);

        $this->newDocument = null;
        $this->dispatch('mediaUploaded');
    }

    private function mediaValidation(): void
    {
        $existingCount = $this->office_id
            ? OfficeMedia::where('office_id', $this->office_id)->where('type', 'photo')->count()
            : 0;
        $remaining = max(0, 10 - $existingCount);

        $existingvideoCount = $this->office_id
            ? OfficeMedia::where('office_id', $this->office_id)->where('type', 'video')->count()
            : 0;
        $remainingvideo = max(0, 10 - $existingCount);

        $this->validate([
            'newPhotos'    => "array|max:{$remaining}",
            'newPhotos.*'  => 'image|max:5120',
            'newVideos'    => "array|max:{$remainingvideo}",
            'newVideo'     => 'nullable|mimetypes:video/mp4,video/avi,video/quicktime|max:102400',
            'newDocuments'   => 'array',
            'newDocuments.*' => 'mimes:pdf|max:10240',
        ], [
            'newPhotos.max'        => "الحد الأقصى {$remaining} صورة متبقية",
            'newPhotos.*.image'    => 'يجب أن تكون الملفات صوراً',
            'newPhotos.*.max'      => 'الحد الأقصى لحجم الصورة 5 ميجا',
            'newVideo.max'         => 'الحد الأقصى لحجم الفيديو 100 ميجا',
            'newDocuments.*.mimes' => 'يجب أن تكون الملفات بصيغة PDF',
            'newDocuments.*.max'   => 'الحد الأقصى لحجم الملف 10 ميجا',
        ]);
    }

    public function deleteMedia(int $id): void
    {
        $media = OfficeMedia::find($id);
        if ($media && $media->office_id === $this->office_id) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($media->path);
            $media->delete();
        }
    }

    private function step3Validation(): void
    {
        $commitmentKeys = array_keys(\App\Models\Office::COMMITMENT_RATINGS);

        $this->validate([
            'visited_at'                   => 'required|date',
            'structural_condition_id'      => 'required|exists:structural_conditions,id',
            'cleanliness_rating'           => 'required|in:' . implode(',', array_keys(\App\Models\Office::CLEANLINESS_RATINGS)),
            'archive_rating'               => 'required|in:' . implode(',', array_keys(\App\Models\Office::ARCHIVE_RATINGS)),
            'work_schedule_commitment'     => 'required|in:' . implode(',', $commitmentKeys),
            'citizen_treatment_commitment' => 'required|in:' . implode(',', $commitmentKeys),
        ], [
            'visited_at.required'                   => 'يرجى إدخال تاريخ الزيارة',
            'visited_at.date'                       => 'تاريخ الزيارة غير صحيح',
            'structural_condition_id.required'      => 'يرجى اختيار الحالة الإنشائية',
            'cleanliness_rating.required'           => 'يرجى اختيار تقييم النظافة',
            'archive_rating.required'               => 'يرجى اختيار تقييم غرف الحفظ',
            'work_schedule_commitment.required'     => 'يرجى اختيار مدى الالتزام بمواعيد العمل',
            'citizen_treatment_commitment.required' => 'يرجى اختيار مدى الالتزام بحسن المعاملة',
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
            'office_area'            => $this->office_area !== '' ? (int) $this->office_area : null,
            'district_court'         => $this->district_court ?: null,
            'windows_count'          => $this->windows_count !== '' ? (int) $this->windows_count : null,
        ];
    }

    private function step3Data(): array
    {
        return [
            'visited_at'                  => $this->visited_at ?: null,
            'structural_condition_id'     => $this->structural_condition_id ?: null,
            'cleanliness_rating'          => $this->cleanliness_rating ?: null,
            'archive_rating'              => $this->archive_rating ?: null,
            'work_schedule_commitment'    => $this->work_schedule_commitment ?: null,
            'citizen_treatment_commitment' => $this->citizen_treatment_commitment ?: null,
            'surveillance_cameras'        => $this->surveillance_cameras ?: null,
            'negatives_and_solutions'     => $this->negatives_and_solutions ?: null,
            'development_proposals'       => $this->development_proposals ?: null,
            'office_needs'                => $this->office_needs ?: null,
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
            'monitors_count'                    => $this->monitors_count !== '' ? (int) $this->monitors_count : null,
            'scanners_count'                    => $this->scanners_count !== '' ? (int) $this->scanners_count : null,
            'printers_count'                    => $this->printers_count !== '' ? (int) $this->printers_count : null,
            'fingerprints_count'                => $this->fingerprints_count !== '' ? (int) $this->fingerprints_count : null,
            'air_conditioners_count'            => $this->air_conditioners_count !== '' ? (int) $this->air_conditioners_count : null,
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

        OfficeBrokenDevice::where('office_id', $this->office_id)->delete();
        foreach ($this->brokenDevices as $row) {
            if (!empty($row['device_type_id']) && isset($row['count']) && (int) $row['count'] > 0) {
                OfficeBrokenDevice::create([
                    'office_id'      => $this->office_id,
                    'device_type_id' => $row['device_type_id'],
                    'count'          => (int) $row['count'],
                ]);
            }
        }
    }

    private function persistStep3(): void
    {
        Office::find($this->office_id)?->update($this->step3Data());
    }

    private function persistStep4(): void
    {
        OfficeStat::where('office_id', $this->office_id)->delete();

        foreach ($this->transactionStats as $row) {
            if (!empty($row['year'])) {
                OfficeStat::create(['office_id' => $this->office_id, 'stat_type_id' => 1, 'year' => $row['year'], 'month' => null, 'value' => $row['value'] ?? 0]);
            }
        }
        foreach ($this->formSalesStats as $row) {
            if (!empty($row['year']) && !empty($row['month'])) {
                OfficeStat::create(['office_id' => $this->office_id, 'stat_type_id' => 2, 'year' => $row['year'], 'month' => $row['month'], 'value' => $row['value'] ?? 0]);
            }
        }
        foreach ($this->folderSalesStats as $row) {
            if (!empty($row['year']) && !empty($row['month'])) {
                OfficeStat::create(['office_id' => $this->office_id, 'stat_type_id' => 3, 'year' => $row['year'], 'month' => $row['month'], 'value' => $row['value'] ?? 0]);
            }
        }
    }

    public function addTransactionStat(): void  { array_unshift($this->transactionStats,  ['year' => '', 'value' => '']); }
    public function removeTransactionStat(int $i): void { array_splice($this->transactionStats, $i, 1); }

    public function addFormSaleStat(): void     { array_unshift($this->formSalesStats,    ['year' => '', 'month' => '', 'value' => '']); }
    public function removeFormSaleStat(int $i): void { array_splice($this->formSalesStats, $i, 1); }

    public function addFolderSaleStat(): void   { array_unshift($this->folderSalesStats,  ['year' => '', 'month' => '', 'value' => '']); }
    public function removeFolderSaleStat(int $i): void { array_splice($this->folderSalesStats, $i, 1); }

    public function addBrokenDevice(): void
    {
        $this->brokenDevices[] = ['device_type_id' => '', 'count' => 1];
    }

    public function removeBrokenDevice(int $index): void
    {
        array_splice($this->brokenDevices, $index, 1);
    }

    private function persistMedia(): void
    {
        $this->mediaValidation();

        $existingPhotos = OfficeMedia::where('office_id', $this->office_id)->where('type', 'photo')->count();

        foreach ($this->newPhotos as $photo) {
            if ($existingPhotos >= 10) break;
            $path = $photo->store("offices/{$this->office_id}/photos", 'public');
            OfficeMedia::create(['office_id' => $this->office_id, 'type' => 'photo', 'path' => $path, 'original_name' => $photo->getClientOriginalName()]);
            $existingPhotos++;
        }

        if ($this->newVideo) {
            OfficeMedia::where('office_id', $this->office_id)->where('type', 'video')
                ->each(fn($m) => \Illuminate\Support\Facades\Storage::disk('public')->delete($m->path));
            OfficeMedia::where('office_id', $this->office_id)->where('type', 'video')->delete();
            $path = $this->newVideo->store("offices/{$this->office_id}/videos", 'public');
            OfficeMedia::create(['office_id' => $this->office_id, 'type' => 'video', 'path' => $path, 'original_name' => $this->newVideo->getClientOriginalName()]);
        }

        foreach ($this->newDocuments as $doc) {
            $path = $doc->store("offices/{$this->office_id}/documents", 'public');
            OfficeMedia::create(['office_id' => $this->office_id, 'type' => 'document', 'path' => $path, 'original_name' => $doc->getClientOriginalName()]);
        }

        $this->newPhotos    = [];
        $this->newVideo     = null;
        $this->newDocuments = [];
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
        } elseif ($this->step === 3) {
            //$this->step3Validation();
            $this->persistStep3();
        } else {
            $this->saveCurrentStep();
        }
        $this->step++;
    }

    public function prevStep(): void
    {
        $this->saveCurrentStep();
        $this->step--;
    }

    public function goToStep(int $target): void
    {
        if (!$this->office_id || $target === $this->step) return;
        $this->saveCurrentStep();
        $this->step = $target;
    }

    private function saveCurrentStep(): void
    {
        match($this->step) {
            2 => $this->persistStep2(),
            3 => $this->persistStep3(),
            4 => $this->persistStep4(),
            default => null,
        };
    }

    public function save(): void
    {
        Flux::toast(variant: 'success', text: __('home.office_created'));
        $this->redirect(route('offices.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.offices.create', [
            'governorates'        => auth()->user()?->hasRole('super-admin')
                ? Governorate::orderBy('order')->orderBy('id')->get()
                : auth()->user()->governorates()->orderBy('order')->orderBy('id')->get(),
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
            'structuralConditions'        => StructuralCondition::orderBy('id')->get(),
            'deviceTypes'                 => DeviceType::orderBy('id')->get(),
            'existingMedia'               => $this->office_id
                ? OfficeMedia::where('office_id', $this->office_id)->get()->groupBy('type')
                : collect(),
        ]);
    }
}
