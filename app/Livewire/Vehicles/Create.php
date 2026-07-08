<?php

namespace App\Livewire\Vehicles;

use App\Models\Governorate;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleBrokenDevice;
use App\Models\VehicleDeviceType;
use App\Models\VehicleLocation;
use App\Models\VehicleMedia;
use App\Models\VehicleType;
use App\Models\VehicleWorkingHour;
use App\Models\VehicleWorkSystem;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('سيارة متنقلة')]
class Create extends Component
{
    use WithFileUploads;

    #[Url]
    public int $activeTab  = 1;
    public int $totalTabs  = 5; // تاب 5: الإحصائيات (nested component بيحفظ فوراً، مش جزء من تدفق next/previous)

    #[Url]
    public ?int $vehicle_id = null;
    public bool $isEditing  = false;

    // Basic data
    public $governorate_id     = null;
    public string $name        = '';
    public $type_id            = null;
    public $work_system_id     = null;
    public $working_hours_id   = null;
    public $brand_id           = null;
    public string $license_plate         = '';
    public string $manufacture_year      = '';
    public string $operated_at           = '';
    public string $chassis_number        = '';
    public string $license_expiry_date   = '';
    public string $status                = '';
    public string $overnight_address     = '';
    public string $storage_room_location = '';
    public string $notes                 = '';

    // مواقع التمركز (صفوف ديناميكية)
    public array $locations = [];

    // Tab 2 — العاملون
    public string $driver_name     = '';
    public string $driver_phone    = '';
    public string $notary_name     = '';
    public string $notary_phone    = '';
    public string $reviewer_name   = '';
    public string $reviewer_phone  = '';

    // Tab 3 — التجهيزات
    public string $mobility_bag               = '';
    public string $laptops_count              = '';
    public string $fingerprints_count         = '';
    public string $printers_count             = '';
    public string $collection_machines_count  = '';
    public string $mifi_count                 = '';
    public string $generator_status           = '';
    public string $surveillance_cameras       = '';
    public array $brokenDevices = []; // [device_type_id, count]

    // Tab 4 — الوسائط (رفع فوري — منفصل عن حفظ التاب)
    public array $newPhotos          = [];
    public mixed $newVideo           = null;
    public mixed $newDocument        = null; // قرار الإنشاء
    public mixed $newLicensePhoto    = null; // صورة الرخصة

    public function mount(?Vehicle $vehicle = null): void
    {
        $user = auth()->user();

        if ($vehicle && $vehicle->exists) {
            abort_unless($user?->hasRole('super-admin') || $user?->can('vehicles.edit'), 403);
            $this->assertVehicleInScope($vehicle);
            $this->isEditing   = true;
            $this->vehicle_id  = $vehicle->id;
            $this->loadVehicle($vehicle);
        } elseif ($this->vehicle_id) {
            // استئناف جلسة إنشاء (مثلاً رجوع المتصفح) — السيارة اتحفظت بالفعل في تاب 1
            $existing = Vehicle::find($this->vehicle_id);
            if ($existing) {
                abort_unless($user?->hasRole('super-admin') || $user?->can('vehicles.create') || $user?->can('vehicles.edit'), 403);
                $this->assertVehicleInScope($existing);
                $this->isEditing = true;
                $this->loadVehicle($existing);
            } else {
                $this->vehicle_id = null;
                abort_unless($user?->hasRole('super-admin') || $user?->can('vehicles.create'), 403);
            }
        } else {
            abort_unless($user?->hasRole('super-admin') || $user?->can('vehicles.create'), 403);
        }

        if (empty($this->locations)) {
            $this->locations = [['days' => [], 'address' => '']];
        }

        if (!$this->vehicle_id || $this->activeTab > $this->totalTabs) {
            $this->activeTab = 1;
        }
    }

    public function addLocation(): void
    {
        $this->locations[] = ['days' => [], 'address' => ''];
    }

    public function removeLocation(int $index): void
    {
        array_splice($this->locations, $index, 1);
        if (empty($this->locations)) {
            $this->locations = [['days' => [], 'address' => '']];
        }
    }

    public function addBrokenDevice(): void
    {
        $this->brokenDevices[] = ['device_type_id' => '', 'count' => 1];
    }

    public function removeBrokenDevice(int $index): void
    {
        array_splice($this->brokenDevices, $index, 1);
    }

    public function uploadPhoto(): void
    {
        $this->validate(
            ['newPhotos' => 'required|array', 'newPhotos.*' => 'image|max:5120'],
            [
                'newPhotos.required' => 'يرجى اختيار صورة على الأقل',
                'newPhotos.*.image'  => 'يجب أن تكون الملفات صوراً',
                'newPhotos.*.max'    => 'الحد الأقصى لحجم الصورة 5 ميجا',
            ]
        );

        $existing  = VehicleMedia::where('vehicle_id', $this->vehicle_id)->where('type', 'photo')->count();
        $remaining = max(0, 5 - $existing);

        foreach (array_slice($this->newPhotos, 0, $remaining) as $photo) {
            $path = $photo->store("vehicles/{$this->vehicle_id}/photos", 'public');
            VehicleMedia::create([
                'vehicle_id'    => $this->vehicle_id,
                'type'          => 'photo',
                'path'          => $path,
                'original_name' => $photo->getClientOriginalName(),
            ]);
        }

        $this->newPhotos = [];
        $this->dispatch('mediaUploaded');
    }

    public function uploadVideo(): void
    {
        $this->validate(
            ['newVideo' => 'required|mimetypes:video/mp4,video/avi,video/x-msvideo,video/quicktime,video/webm|max:102400'],
            ['newVideo.required' => 'يرجى اختيار فيديو', 'newVideo.mimetypes' => 'نوع الفيديو غير مدعوم (MP4, AVI, MOV فقط)', 'newVideo.max' => 'الحد الأقصى 100 ميجا']
        );

        if (VehicleMedia::where('vehicle_id', $this->vehicle_id)->where('type', 'video')->exists()) return;

        $path = $this->newVideo->store("vehicles/{$this->vehicle_id}/videos", 'public');
        VehicleMedia::create(['vehicle_id' => $this->vehicle_id, 'type' => 'video', 'path' => $path, 'original_name' => $this->newVideo->getClientOriginalName()]);

        $this->newVideo = null;
        $this->dispatch('mediaUploaded');
    }

    public function uploadDocument(): void
    {
        $this->validate(
            ['newDocument' => 'required|mimes:pdf|max:10240'],
            ['newDocument.required' => 'يرجى اختيار ملف', 'newDocument.mimes' => 'يجب أن يكون الملف PDF', 'newDocument.max' => 'الحد الأقصى 10 ميجا']
        );

        if (VehicleMedia::where('vehicle_id', $this->vehicle_id)->where('type', 'document')->exists()) return;

        $path = $this->newDocument->store("vehicles/{$this->vehicle_id}/documents", 'public');
        VehicleMedia::create(['vehicle_id' => $this->vehicle_id, 'type' => 'document', 'path' => $path, 'original_name' => $this->newDocument->getClientOriginalName()]);

        $this->newDocument = null;
        $this->dispatch('mediaUploaded');
    }

    public function uploadLicensePhoto(): void
    {
        $this->validate(
            ['newLicensePhoto' => 'required|image|max:5120'],
            ['newLicensePhoto.required' => 'يرجى اختيار صورة', 'newLicensePhoto.image' => 'يجب أن يكون الملف صورة', 'newLicensePhoto.max' => 'الحد الأقصى لحجم الصورة 5 ميجا']
        );

        if (VehicleMedia::where('vehicle_id', $this->vehicle_id)->where('type', 'license_photo')->exists()) return;

        $path = $this->newLicensePhoto->store("vehicles/{$this->vehicle_id}/license", 'public');
        VehicleMedia::create(['vehicle_id' => $this->vehicle_id, 'type' => 'license_photo', 'path' => $path, 'original_name' => $this->newLicensePhoto->getClientOriginalName()]);

        $this->newLicensePhoto = null;
        $this->dispatch('mediaUploaded');
    }

    public function deleteMedia(int $id): void
    {
        $media = VehicleMedia::find($id);
        if ($media && $media->vehicle_id === $this->vehicle_id) {
            Storage::disk('public')->delete($media->path);
            $media->delete();
        }
    }

    public function saveAndExit(): void
    {
        $this->validateCurrentTab();
        $this->persistCurrentTab();
        Flux::toast(variant: 'success', text: $this->isEditing ? __('home.vehicle_updated') : __('home.vehicle_created'));
        $this->redirect(route('vehicles.index'), navigate: true);
    }

    public function nextTab(): void
    {
        $this->validateCurrentTab();
        $this->persistCurrentTab();
        if ($this->activeTab < $this->totalTabs) {
            $this->activeTab++;
        }
    }

    public function prevTab(): void
    {
        $this->persistCurrentTab();
        if ($this->activeTab > 1) {
            $this->activeTab--;
        }
    }

    public function goToTab(int $target): void
    {
        if (!$this->vehicle_id || $target === $this->activeTab || $target > $this->totalTabs) return;
        $this->persistCurrentTab();
        $this->activeTab = $target;
    }

    private function validateCurrentTab(): void
    {
        match ($this->activeTab) {
            1       => $this->validateBasicTab(),
            2       => $this->validateWorkersTab(),
            3       => $this->validateEquipmentTab(),
            default => null,
        };
    }

    private function persistCurrentTab(): void
    {
        match ($this->activeTab) {
            1       => $this->persistBasicTab(),
            2       => $this->persistWorkersTab(),
            3       => $this->persistEquipmentTab(),
            default => null,
        };
    }

    private function validateBasicTab(): void
    {
        $governorateRule = ['required', 'exists:governorates,id'];
        if (!auth()->user()?->hasRole('super-admin')) {
            $governorateRule[] = Rule::in($this->allowedGovernorateIds());
        }

        $this->validate([
            'governorate_id'         => $governorateRule,
            'name'                   => ['required', 'string', 'max:255', Rule::unique('vehicles', 'name')->ignore($this->vehicle_id)],
            'type_id'                => ['required', 'exists:vehicle_types,id'],
            'work_system_id'         => ['required', 'exists:vehicle_work_systems,id'],
            'working_hours_id'       => ['nullable', 'exists:vehicle_working_hours,id'],
            'brand_id'               => ['nullable', 'exists:vehicle_brands,id'],
            'license_plate'          => ['nullable', 'string', 'max:50'],
            'manufacture_year'       => ['nullable', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'operated_at'            => ['nullable', 'date'],
            'chassis_number'         => ['nullable', 'string', 'max:100'],
            'license_expiry_date'    => ['nullable', 'date'],
            'status'                 => ['nullable', Rule::in(array_keys(Vehicle::STATUSES))],
            'overnight_address'      => ['nullable', 'string', 'max:500'],
            'storage_room_location'  => ['nullable', 'string', 'max:500'],
            'notes'                  => ['nullable', 'string'],
            'locations.*.days'       => ['nullable', 'array'],
            'locations.*.days.*'     => ['nullable', Rule::in(array_keys(VehicleLocation::DAYS))],
            'locations.*.address'    => ['nullable', 'string', 'max:500'],
        ], [
            'governorate_id.required' => 'يرجى اختيار المحافظة',
            'governorate_id.in'       => 'لا يمكنك حفظ سيارة في محافظة خارج نطاقك',
            'name.required'           => 'يرجى إدخال اسم السيارة',
            'name.unique'             => 'اسم السيارة مستخدم من قبل، يرجى اختيار اسم آخر',
            'type_id.required'        => 'يرجى اختيار نوع السيارة',
            'work_system_id.required' => 'يرجى اختيار نظام العمل',
        ]);
    }

    private function validateWorkersTab(): void
    {
        $this->validate([
            'driver_name'    => ['nullable', 'string', 'max:255'],
            'driver_phone'   => ['nullable', 'regex:/^01[0-9]{9}$/'],
            'notary_name'    => ['nullable', 'string', 'max:255'],
            'notary_phone'   => ['nullable', 'regex:/^01[0-9]{9}$/'],
            'reviewer_name'  => ['nullable', 'string', 'max:255'],
            'reviewer_phone' => ['nullable', 'regex:/^01[0-9]{9}$/'],
        ], [
            'driver_phone.regex'   => __('home.head_mobile_invalid'),
            'notary_phone.regex'   => __('home.head_mobile_invalid'),
            'reviewer_phone.regex' => __('home.head_mobile_invalid'),
        ]);
    }

    private function persistBasicTab(): void
    {
        $data = [
            'governorate_id'        => $this->governorate_id ?: null,
            'name'                  => $this->name,
            'type_id'               => $this->type_id ?: null,
            'work_system_id'        => $this->work_system_id ?: null,
            'working_hours_id'      => $this->working_hours_id ?: null,
            'brand_id'              => $this->brand_id ?: null,
            'license_plate'         => $this->license_plate ?: null,
            'manufacture_year'      => $this->manufacture_year ?: null,
            'operated_at'           => $this->operated_at ?: null,
            'chassis_number'        => $this->chassis_number ?: null,
            'license_expiry_date'   => $this->license_expiry_date ?: null,
            'status'                => $this->status ?: null,
            'overnight_address'     => $this->overnight_address ?: null,
            'storage_room_location' => $this->storage_room_location ?: null,
            'notes'                 => $this->notes ?: null,
        ];

        if ($this->isEditing) {
            $vehicle = Vehicle::findOrFail($this->vehicle_id);
            $vehicle->update($data);
        } else {
            $vehicle = Vehicle::create($data);
            $this->vehicle_id = $vehicle->id;
            $this->isEditing  = true;
        }

        // حفظ مواقع التمركز — كل يوم مُختار في الصف بينفصل لسجل مستقل بنفس العنوان
        $vehicle->locations()->delete();
        foreach ($this->locations as $loc) {
            if (!empty($loc['address']) && !empty($loc['days'])) {
                foreach ($loc['days'] as $day) {
                    $vehicle->locations()->create(['day' => $day, 'address' => $loc['address']]);
                }
            }
        }
    }

    private function persistWorkersTab(): void
    {
        Vehicle::findOrFail($this->vehicle_id)->update([
            'driver_name'    => $this->driver_name ?: null,
            'driver_phone'   => $this->driver_phone ?: null,
            'notary_name'    => $this->notary_name ?: null,
            'notary_phone'   => $this->notary_phone ?: null,
            'reviewer_name'  => $this->reviewer_name ?: null,
            'reviewer_phone' => $this->reviewer_phone ?: null,
        ]);
    }

    private function validateEquipmentTab(): void
    {
        $this->validate([
            'mobility_bag'              => ['nullable', Rule::in(['available', 'not_available'])],
            'laptops_count'             => ['nullable', 'integer', 'min:0'],
            'fingerprints_count'        => ['nullable', 'integer', 'min:0'],
            'printers_count'            => ['nullable', 'integer', 'min:0'],
            'collection_machines_count' => ['nullable', 'integer', 'min:0'],
            'mifi_count'                => ['nullable', 'integer', 'min:0'],
            'generator_status'          => ['nullable', Rule::in(['available', 'not_available', 'broken'])],
            'surveillance_cameras'      => ['nullable', Rule::in(['available', 'not_available', 'broken'])],
            'brokenDevices.*.device_type_id' => ['nullable', 'exists:vehicle_device_types,id'],
            'brokenDevices.*.count'          => ['nullable', 'integer', 'min:1'],
        ]);
    }

    private function persistEquipmentTab(): void
    {
        Vehicle::findOrFail($this->vehicle_id)->update([
            'mobility_bag'              => $this->mobility_bag ?: null,
            'laptops_count'             => $this->laptops_count !== '' ? (int) $this->laptops_count : null,
            'fingerprints_count'        => $this->fingerprints_count !== '' ? (int) $this->fingerprints_count : null,
            'printers_count'            => $this->printers_count !== '' ? (int) $this->printers_count : null,
            'collection_machines_count' => $this->collection_machines_count !== '' ? (int) $this->collection_machines_count : null,
            'mifi_count'                => $this->mifi_count !== '' ? (int) $this->mifi_count : null,
            'generator_status'          => $this->generator_status ?: null,
            'surveillance_cameras'      => $this->surveillance_cameras ?: null,
        ]);

        VehicleBrokenDevice::where('vehicle_id', $this->vehicle_id)->delete();
        foreach ($this->brokenDevices as $row) {
            if (!empty($row['device_type_id']) && isset($row['count']) && (int) $row['count'] > 0) {
                VehicleBrokenDevice::create([
                    'vehicle_id'     => $this->vehicle_id,
                    'device_type_id' => $row['device_type_id'],
                    'count'          => (int) $row['count'],
                ]);
            }
        }
    }

    private function loadVehicle(Vehicle $vehicle): void
    {
        $this->governorate_id        = $vehicle->governorate_id;
        $this->name                  = $vehicle->name;
        $this->type_id               = $vehicle->type_id;
        $this->work_system_id        = $vehicle->work_system_id;
        $this->working_hours_id      = $vehicle->working_hours_id;
        $this->brand_id              = $vehicle->brand_id;
        $this->license_plate         = $vehicle->license_plate ?? '';
        $this->manufacture_year      = (string) ($vehicle->manufacture_year ?? '');
        $this->operated_at           = $vehicle->operated_at?->format('Y-m-d') ?? '';
        $this->chassis_number        = $vehicle->chassis_number ?? '';
        $this->license_expiry_date   = $vehicle->license_expiry_date?->format('Y-m-d') ?? '';
        $this->status                = $vehicle->status ?? '';
        $this->overnight_address     = $vehicle->overnight_address ?? '';
        $this->storage_room_location = $vehicle->storage_room_location ?? '';
        $this->notes                 = $vehicle->notes ?? '';

        // تجميع الأيام اللي بتشترك في نفس العنوان في صف واجهة واحد
        $this->locations = $vehicle->locations
            ->groupBy('address')
            ->map(fn($group, $address) => [
                'days'    => $group->pluck('day')->values()->all(),
                'address' => $address,
            ])
            ->values()
            ->toArray();

        $this->driver_name    = $vehicle->driver_name ?? '';
        $this->driver_phone   = $vehicle->driver_phone ?? '';
        $this->notary_name    = $vehicle->notary_name ?? '';
        $this->notary_phone   = $vehicle->notary_phone ?? '';
        $this->reviewer_name  = $vehicle->reviewer_name ?? '';
        $this->reviewer_phone = $vehicle->reviewer_phone ?? '';

        $this->mobility_bag              = $vehicle->mobility_bag ?? '';
        $this->laptops_count             = (string) ($vehicle->laptops_count ?? '');
        $this->fingerprints_count        = (string) ($vehicle->fingerprints_count ?? '');
        $this->printers_count            = (string) ($vehicle->printers_count ?? '');
        $this->collection_machines_count = (string) ($vehicle->collection_machines_count ?? '');
        $this->mifi_count                = (string) ($vehicle->mifi_count ?? '');
        $this->generator_status          = $vehicle->generator_status ?? '';
        $this->surveillance_cameras      = $vehicle->surveillance_cameras ?? '';

        $this->brokenDevices = $vehicle->brokenDevices
            ->map(fn($d) => ['device_type_id' => $d->device_type_id, 'count' => $d->count])
            ->toArray();
    }

    private function assertVehicleInScope(Vehicle $vehicle): void
    {
        if (auth()->user()?->hasRole('super-admin')) return;
        abort_unless(in_array($vehicle->governorate_id, $this->allowedGovernorateIds(), true), 403);
    }

    private function allowedGovernorateIds(): array
    {
        return auth()->user()?->governorates()->pluck('id')->all() ?? [];
    }

    public function render()
    {
        $user = auth()->user();
        return view('livewire.vehicles.create', [
            'governorates' => $user?->hasRole('super-admin')
                ? Governorate::orderBy('order')->orderBy('id')->get()
                : $user->governorates()->orderBy('order')->orderBy('id')->get(),
            'types'        => VehicleType::orderBy('name')->get(),
            'brands'       => VehicleBrand::orderBy('name')->get(),
            'workSystems'  => VehicleWorkSystem::orderBy('name')->get(),
            'workingHours' => VehicleWorkingHour::orderBy('name')->get(),
            'deviceTypes'  => VehicleDeviceType::orderBy('name')->get(),
            'existingMedia' => $this->vehicle_id
                ? VehicleMedia::where('vehicle_id', $this->vehicle_id)->get()->groupBy('type')
                : collect(),
            'vehicle' => $this->vehicle_id ? Vehicle::find($this->vehicle_id) : null,
            'canView' => $user?->hasRole('super-admin') || $user?->can('vehicles.view'),
        ]);
    }
}
