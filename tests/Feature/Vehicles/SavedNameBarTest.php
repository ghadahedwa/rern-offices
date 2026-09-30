<?php

use App\Livewire\Vehicles\Create;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('pins the saved vehicle name on the edit page, not the unsaved typing', function () {
    $governorate = Governorate::factory()->create();
    $vehicle     = Vehicle::create(['governorate_id' => $governorate->id, 'name' => 'سيارة المنيا ١']);

    Permission::findOrCreate('vehicles.edit', 'web');
    $role = Role::findOrCreate('vh-edit', 'web');
    $role->givePermissionTo('vehicles.edit');
    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync([$governorate->id]);

    Livewire::actingAs($user->fresh())->test(Create::class, ['vehicle' => $vehicle])
        ->assertSeeHtml('title="'.e($vehicle->name).'"')
        ->set('name', 'اسم لم يُحفظ')
        ->assertSeeHtml('title="'.e($vehicle->name).'"')
        ->assertDontSeeHtml('title="اسم لم يُحفظ"');
});
