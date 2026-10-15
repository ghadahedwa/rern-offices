<?php

use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\User;
use App\Models\Vehicle;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * صفحات تفتح مقراً أو سيارة بمعرّفه في الرابط — الصلاحية وحدها لا تكفي، والمحافظة تُفحص.
 * (كانت تفحص الصلاحية وحدها، فيفتح صاحب `offices.view` مقرّ أي محافظة بكتابة رقمه.)
 */
beforeEach(function () {
    $this->mine   = Governorate::factory()->create(['name' => 'محافظتي']);
    $this->other  = Governorate::factory()->create(['name' => 'محافظة أخرى']);
    $type         = OfficeType::factory()->create();
    $this->myOffice    = Office::factory()->create(['governorate_id' => $this->mine->id, 'type_id' => $type->id]);
    $this->otherOffice = Office::factory()->create(['governorate_id' => $this->other->id, 'type_id' => $type->id]);
    $this->myVehicle    = Vehicle::create(['governorate_id' => $this->mine->id, 'name' => 'سيارتي']);
    $this->otherVehicle = Vehicle::create(['governorate_id' => $this->other->id, 'name' => 'سيارة أخرى']);
});

function scopedViewer(array $govIds): User
{
    foreach (['offices.view', 'offices.edit', 'vehicles.view'] as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $role = Role::findOrCreate('record-viewer', 'web');
    $role->syncPermissions(['offices.view', 'offices.edit', 'vehicles.view']);
    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync($govIds);

    return $user->fresh();
}

dataset('record pages', [
    'office show'       => ['offices.show', 'Office'],
    'office statistics' => ['offices.statistics', 'Office'],
    'office pdf'        => ['offices.pdf', 'Office'],
    'vehicle show'      => ['vehicles.show', 'Vehicle'],
]);

it('opens a record in the user governorates', function (string $route, string $kind) {
    $record = $kind === 'Office' ? $this->myOffice : $this->myVehicle;

    $this->actingAs(scopedViewer([$this->mine->id]))->get(route($route, $record))->assertOk();
})->with('record pages');

it('forbids a record in another governorate', function (string $route, string $kind) {
    $record = $kind === 'Office' ? $this->otherOffice : $this->otherVehicle;

    $this->actingAs(scopedViewer([$this->mine->id]))->get(route($route, $record))->assertForbidden();
})->with('record pages');

it('forbids everything to a user without governorates', function (string $route, string $kind) {
    $record = $kind === 'Office' ? $this->myOffice : $this->myVehicle;

    $this->actingAs(scopedViewer([]))->get(route($route, $record))->assertForbidden();
})->with('record pages');

it('lets super-admin open any record', function (string $route, string $kind) {
    $admin  = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));
    $record = $kind === 'Office' ? $this->otherOffice : $this->otherVehicle;

    $this->actingAs($admin)->get(route($route, $record))->assertOk();
})->with('record pages');

it('does not log a refused view', function () {
    $this->actingAs(scopedViewer([$this->mine->id]))->get(route('offices.show', $this->otherOffice))->assertForbidden();

    expect(Activity::where('event', 'viewed')->count())->toBe(0);
});
