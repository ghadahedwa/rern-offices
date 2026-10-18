<?php

use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleWorkingHour;
use App\Reports\VehicleColumns;
use Spatie\Permission\Models\Role;

/**
 * ساعات العمل كانت تظهر في فورم التعديل (يقرأ العمود) وتغيب عن العرض والتقارير (تقرأ العلاقة):
 * العمود `working_hours_id` والعلاقة بلا مفتاح تستنتج `working_hour_id` من اسم الدالة.
 */
beforeEach(function () {
    $this->hours   = VehicleWorkingHour::create(['name' => 'من ٩ إلى ٥ مساءً']);
    $this->vehicle = Vehicle::create([
        'governorate_id'   => Governorate::factory()->create()->id,
        'name'             => 'سيارة',
        'working_hours_id' => $this->hours->id,
    ]);
});

it('resolves the working hours relation from working_hours_id', function () {
    expect($this->vehicle->fresh()->workingHour?->id)->toBe($this->hours->id);
});

it('shows the working hours on the vehicle page', function () {
    Role::findOrCreate('super-admin', 'web');
    $admin = tap(User::factory()->create())->assignRole('super-admin');

    $this->actingAs($admin)->get(route('vehicles.show', $this->vehicle))
        ->assertOk()
        ->assertSee('من ٩ إلى ٥ مساءً');
});

it('fills the working hours report column', function () {
    $value = VehicleColumns::all()['working_hours']['value'];

    expect($value($this->vehicle->fresh()))->toBe('من ٩ إلى ٥ مساءً');
});
