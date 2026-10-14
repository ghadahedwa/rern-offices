<?php

use App\Livewire\Vehicles\Create;
use App\Livewire\Vehicles\Show;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Models\VehicleWorkSystem;
use App\Reports\VehicleColumns;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/** فورم البيانات الأساسية بالحقول الإلزامية معبّأة — يبقى على الاختبار الحقلان الجديدان */
function basicVehicleForm(): \Livewire\Features\SupportTesting\Testable
{
    $governorate = Governorate::factory()->create();
    $admin       = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));

    return Livewire::actingAs($admin)->test(Create::class)
        ->set('governorate_id', $governorate->id)
        ->set('name', 'سيارة اختبار')
        ->set('type_id', VehicleType::create(['name' => 'نوع'])->id)
        ->set('work_system_id', VehicleWorkSystem::create(['name' => 'نظام'])->id);
}

it('saves engine number and friday work', function () {
    basicVehicleForm()
        ->set('engine_number', '00123456')
        ->set('works_on_friday', '1')
        ->call('nextTab')
        ->assertHasNoErrors();

    $vehicle = Vehicle::first();
    expect($vehicle->engine_number)->toBe('00123456')   // الصفر الأول جزء من الرقم
        ->and($vehicle->works_on_friday)->toBeTrue();
});

it('rejects an engine number with letters or symbols', function (string $value) {
    basicVehicleForm()
        ->set('engine_number', $value)
        ->call('nextTab')
        ->assertHasErrors(['engine_number' => 'regex']);

    expect(Vehicle::count())->toBe(0);
})->with(['AB123', '123-456', '12 34', '١٢٣x']);

it('accepts arabic-indic digits and stores them latin', function () {
    basicVehicleForm()
        ->set('engine_number', ' ٤٥٦٧٨٩ ')
        ->call('nextTab')
        ->assertHasNoErrors();

    expect(Vehicle::first()->engine_number)->toBe('456789');
});

it('keeps "does not work" distinct from "not set"', function () {
    basicVehicleForm()->set('works_on_friday', '0')->call('nextTab')->assertHasNoErrors();
    expect(Vehicle::first()->works_on_friday)->toBeFalse();

    Vehicle::query()->delete();

    basicVehicleForm()->set('name', 'سيارة ثانية')->call('nextTab')->assertHasNoErrors();
    expect(Vehicle::first()->works_on_friday)->toBeNull();
});

it('rejects a friday value outside the list', function () {
    basicVehicleForm()->set('works_on_friday', '2')->call('nextTab')->assertHasErrors(['works_on_friday']);
});

it('loads both fields back on edit', function () {
    $vehicle = Vehicle::create([
        'governorate_id' => Governorate::factory()->create()->id,
        'name' => 'سيارة', 'engine_number' => '987', 'works_on_friday' => false,
    ]);
    $admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));

    Livewire::actingAs($admin)->test(Create::class, ['vehicle' => $vehicle])
        ->assertSet('engine_number', '987')
        ->assertSet('works_on_friday', '0');
});

it('shows both fields on the vehicle page and in the report columns', function () {
    $vehicle = Vehicle::create([
        'governorate_id' => Governorate::factory()->create()->id,
        'name' => 'سيارة', 'engine_number' => '55501', 'works_on_friday' => false,
    ]);
    $admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));

    Livewire::actingAs($admin)->test(Show::class, ['vehicle' => $vehicle])
        ->assertSee('55501')
        ->assertSee('لا تعمل');

    $columns = VehicleColumns::all();
    expect(($columns['engine_number']['value'])($vehicle))->toBe('55501')
        ->and(($columns['works_on_friday']['value'])($vehicle))->toBe('لا تعمل')
        ->and(($columns['works_on_friday']['value'])(new Vehicle()))->toBe('—');
});

it('does not save letters when leaving the tab by clicking another tab', function (string $action) {
    $vehicle = Vehicle::create([
        'governorate_id' => Governorate::factory()->create()->id,
        'name' => 'سيارة', 'type_id' => VehicleType::create(['name' => 'نوع'])->id,
        'work_system_id' => VehicleWorkSystem::create(['name' => 'نظام'])->id, 'engine_number' => '111',
    ]);
    $admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));

    $test = Livewire::actingAs($admin)->test(Create::class, ['vehicle' => $vehicle])->set('engine_number', 'AB12');
    $action === 'goToTab' ? $test->call('goToTab', 2) : $test->call('prevTab');

    $test->assertHasErrors(['engine_number' => 'regex'])->assertSet('activeTab', 1);
    expect($vehicle->fresh()->engine_number)->toBe('111');
})->with(['goToTab', 'prevTab']);
