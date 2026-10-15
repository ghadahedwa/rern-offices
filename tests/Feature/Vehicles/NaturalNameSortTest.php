<?php

use App\Livewire\Reports\MultiVehicle;
use App\Livewire\Reports\VehicleCoverage;
use App\Livewire\Reports\VehicleLicenses;
use App\Livewire\Vehicles\Index;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleStat;
use App\Reports\VehicleFridayWork;
use App\Reports\VehicleStatistics;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * «سيارة … 2» قبل «سيارة … 10» — الترتيب النصي كان يضع 10 · 11 … 19 قبل 2 و9 آخراً
 * (أسماء السيرفر كلها «سيارة التوثيق المتنقل N»).
 */
const NATURAL = ['سيارة 2', 'سيارة 9', 'سيارة 10', 'سيارة 11', 'سيارة 20'];

beforeEach(function () {
    $this->admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));
    $gov = Governorate::factory()->create();

    // أُنشئت بترتيبٍ عشوائي — لا ينجح الترتيب بالمصادفة
    foreach (['سيارة 11', 'سيارة 2', 'سيارة 20', 'سيارة 9', 'سيارة 10'] as $name) {
        $v = Vehicle::create(['governorate_id' => $gov->id, 'name' => $name, 'license_expiry_date' => '2030-01-01']);
        VehicleStat::create(['vehicle_id' => $v->id, 'stat_type_id' => 1, 'year' => 2025, 'month' => null, 'value' => 100]);
    }
});

it('orders numbered names naturally by default everywhere', function () {
    $screen = fn ($test) => collect($test->viewData('vehicles')->items())->pluck('name')->all();

    expect($screen(Livewire::actingAs($this->admin)->test(Index::class)))->toBe(NATURAL)
        ->and($screen(Livewire::actingAs($this->admin)->test(MultiVehicle::class)->call('search')))->toBe(NATURAL)
        ->and(VehicleFridayWork::make(null, [])->vehicles()->pluck('name')->all())->toBe(NATURAL)
        ->and(VehicleCoverage::rowsFor(null, [])->pluck('name')->all())->toBe(NATURAL)
        ->and(VehicleLicenses::rowsFor(null, [])->pluck('name')->all())->toBe(NATURAL)   // التاريخ متساوٍ فيحكم الاسم
        ->and(array_map(fn ($r) => $r['vehicle']->name, VehicleStatistics::make(null, ['year' => '2025'])->groups()->first()['vehicles']))->toBe(NATURAL);
});

it('sorts by name naturally in both directions when the header is clicked', function () {
    $t = Livewire::actingAs($this->admin)->test(Index::class)->call('sort', 'name')->call('sort', 'name');
    expect(collect($t->viewData('vehicles')->items())->pluck('name')->all())->toBe(array_reverse(NATURAL));

    expect(VehicleFridayWork::make(null, ['sort' => 'name', 'dir' => 'desc'])->vehicles()->pluck('name')->all())
        ->toBe(array_reverse(NATURAL))
        ->and(array_map(fn ($r) => $r['vehicle']->name,
            VehicleStatistics::make(null, ['year' => '2025', 'sort' => 'name', 'dir' => 'desc'])->groups()->first()['vehicles']))
        ->toBe(array_reverse(NATURAL));
});

it('puts a name without a number before its numbered siblings and keeps different texts apart', function () {
    Vehicle::create(['governorate_id' => Governorate::first()->id, 'name' => 'سيارة']);
    Vehicle::create(['governorate_id' => Governorate::first()->id, 'name' => 'أتوبيس 3']);

    expect(VehicleFridayWork::make(null, [])->vehicles()->pluck('name')->all())
        ->toBe(['أتوبيس 3', 'سيارة', ...NATURAL]);
});

it('lists the vehicle filter options naturally', function () {
    expect(Livewire::actingAs($this->admin)->test(MultiVehicle::class)->viewData('vehicleOptions')->pluck('name')->all())
        ->toBe(NATURAL);
});
