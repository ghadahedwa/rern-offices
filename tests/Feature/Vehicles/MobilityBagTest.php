<?php

use App\Livewire\Reports\MultiVehicle;
use App\Livewire\Vehicles\Create;
use App\Livewire\Vehicles\Show;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use App\Reports\VehicleColumns;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function bagAdmin(): User
{
    return tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));
}

function bagVehicle(string $name, ?string $bag): Vehicle
{
    return Vehicle::create([
        'governorate_id' => Governorate::factory()->create()->id,
        'name' => $name, 'mobility_bag' => $bag,
    ]);
}

it('saves a broken mobility bag from the equipment tab', function () {
    $vehicle = bagVehicle('سيارة', 'available');

    Livewire::actingAs(bagAdmin())->test(Create::class, ['vehicle' => $vehicle])
        ->set('activeTab', 3)
        ->set('mobility_bag', 'broken')
        ->call('saveAndExit')
        ->assertHasNoErrors();

    expect($vehicle->fresh()->mobility_bag)->toBe('broken');
});

it('rejects a mobility bag value outside the list', function () {
    $vehicle = bagVehicle('سيارة', 'available');

    Livewire::actingAs(bagAdmin())->test(Create::class, ['vehicle' => $vehicle])
        ->set('activeTab', 3)
        ->set('mobility_bag', 'lost')
        ->call('saveAndExit')
        ->assertHasErrors(['mobility_bag']);

    expect($vehicle->fresh()->mobility_bag)->toBe('available');
});

it('offers the three choices in the form', function () {
    $vehicle = bagVehicle('سيارة', null);

    Livewire::actingAs(bagAdmin())->test(Create::class, ['vehicle' => $vehicle])
        ->set('activeTab', 3)
        ->assertSeeHtml('<option value="broken">معطلة</option>');
});

it('labels the broken bag on the vehicle page and in the report columns', function () {
    $vehicle = bagVehicle('سيارة', 'broken');

    Livewire::actingAs(bagAdmin())->test(Show::class, ['vehicle' => $vehicle])
        ->set('activeTab', 'equipment')
        ->assertSee('معطلة');

    $value = VehicleColumns::all()['mobility_bag']['value'];
    expect($value($vehicle))->toBe('معطلة')
        ->and($value(bagVehicle('ب', 'available')))->toBe('توجد')
        ->and($value(bagVehicle('ج', 'not_available')))->toBe('لا توجد');
});

it('filters the multi-vehicle report by a broken bag', function () {
    bagVehicle('سيارة الشنطة المعطلة', 'broken');
    bagVehicle('سيارة الشنطة السليمة', 'available');

    Livewire::actingAs(bagAdmin())->test(MultiVehicle::class)
        ->set('mobilityBag', 'broken')
        ->call('search')
        ->assertViewHas('vehicles', fn ($rows) => collect($rows->items())->pluck('name')->all() === ['سيارة الشنطة المعطلة']);
});
