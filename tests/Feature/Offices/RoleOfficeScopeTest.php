<?php

use App\Livewire\Dashboard;
use App\Livewire\LocationDescriptions\Index as DescriptionsIndex;
use App\Livewire\OfficeTypes\Index as TypesIndex;
use App\Livewire\Offices\Create;
use App\Livewire\Offices\Index;
use App\Livewire\Roles\Create as RoleCreate;
use App\Livewire\Roles\Edit as RoleEdit;
use App\Models\Governorate;
use App\Models\LocationDescription;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\User;
use App\Support\OfficeScope;
use App\Support\OfficeVisitReports;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * قصر الدور على أنواع/أوصاف مقرات (طلب العميل 2026-10-17 — دور «حياة كريمة»).
 * المحافظة على المستخدم، والقيد على الدور، وبين الأدوار الأوسع.
 */
beforeEach(function () {
    $this->gov      = Governorate::factory()->create(['name' => 'محافظتي']);
    $this->otherGov = Governorate::factory()->create(['name' => 'محافظة أخرى']);

    $this->typeA = OfficeType::factory()->create(['name' => 'مكتب توثيق']);
    $this->typeB = OfficeType::factory()->create(['name' => 'مكتب شهر']);

    $this->karima = LocationDescription::create(['name' => 'حياة كريمة']);
    $this->normal = LocationDescription::create(['name' => 'مبنى حكومي']);

    $make = fn ($gov, $type, $desc, $name) => Office::factory()->create([
        'governorate_id' => $gov->id, 'type_id' => $type->id, 'location_description_id' => $desc->id, 'name' => $name,
    ]);

    $this->karimaA      = $make($this->gov, $this->typeA, $this->karima, 'مقر كريمة توثيق');
    $this->karimaB      = $make($this->gov, $this->typeB, $this->karima, 'مقر كريمة شهر');
    $this->normalA      = $make($this->gov, $this->typeA, $this->normal, 'مقر عادي');
    $this->karimaOther  = $make($this->otherGov, $this->typeA, $this->karima, 'مقر كريمة بعيد');

    foreach ([...OfficeScope::OFFICE_PERMISSIONS, 'vehicles.index'] as $p) {
        Permission::findOrCreate($p, 'web');
    }
});

function rosRole(string $name, array $permissions, array $types = [], array $descriptions = []): Role
{
    $role = Role::findOrCreate($name, 'web');
    $role->syncPermissions($permissions);
    DB::table('office_type_role')->insert(array_map(fn ($id) => ['role_id' => $role->id, 'office_type_id' => $id], $types));
    DB::table('location_description_role')->insert(array_map(fn ($id) => ['role_id' => $role->id, 'location_description_id' => $id], $descriptions));

    return $role;
}

function rosUser(array $roles, array $govIds): User
{
    $user = User::factory()->create();
    $user->assignRole($roles);
    $user->governorates()->sync($govIds);

    return $user->fresh();
}

function rosKarimaUser($test, array $extraRoles = []): User
{
    $role = rosRole('karima', ['offices.index', 'offices.view', 'offices.edit', 'offices.create', 'offices.delete'], descriptions: [$test->karima->id]);

    return rosUser([$role, ...$extraRoles], [$test->gov->id]);
}

function rosVisibleNames(User $user): array
{
    return OfficeScope::apply(Office::query(), $user)->orderBy('name')->pluck('name')->all();
}

// ── الحساب ──

it('limits a restricted role to its descriptions inside the user governorates', function () {
    expect(rosVisibleNames(rosKarimaUser($this)))->toBe(['مقر كريمة توثيق', 'مقر كريمة شهر']);
});

it('leaves an unrestricted role on all offices of its governorates', function () {
    $user = rosUser([rosRole('inspector', ['offices.index'])], [$this->gov->id]);

    expect(rosVisibleNames($user))->toHaveCount(3);
});

it('requires both type and description on one role', function () {
    $role = rosRole('narrow', ['offices.index'], types: [$this->typeA->id], descriptions: [$this->karima->id]);

    expect(rosVisibleNames(rosUser([$role], [$this->gov->id])))->toBe(['مقر كريمة توثيق']);
});

it('unions the offices of two restricted roles', function () {
    $a = rosRole('only-a-normal', ['offices.index'], types: [$this->typeA->id], descriptions: [$this->normal->id]);
    $b = rosRole('only-b', ['offices.index'], types: [$this->typeB->id]);

    expect(rosVisibleNames(rosUser([$a, $b], [$this->gov->id])))->toBe(['مقر عادي', 'مقر كريمة شهر']);
});

it('lifts the restriction when another office role is unrestricted (broadest)', function () {
    $user = rosKarimaUser($this, [rosRole('inspector', ['offices.index'])]);

    expect(rosVisibleNames($user))->toHaveCount(3);
});

it('does not let a non-office role lift the restriction', function () {
    $user = rosKarimaUser($this, [rosRole('vehicles', ['vehicles.index'])]);

    expect(rosVisibleNames($user))->toBe(['مقر كريمة توثيق', 'مقر كريمة شهر']);
});

it('keeps super-admin above the restriction', function () {
    $admin = rosUser([Role::findOrCreate('super-admin', 'web')], []);

    expect(rosVisibleNames($admin))->toHaveCount(4);
});

// ── الشاشات ──

it('shows only allowed offices and filter options in the offices list', function () {
    Livewire::actingAs(rosKarimaUser($this))->test(Index::class)
        ->assertSee('مقر كريمة توثيق')
        ->assertDontSee('مقر عادي')
        ->assertDontSee('مقر كريمة بعيد')
        ->assertViewHas('locationDescriptions', fn ($items) => $items->pluck('id')->all() === [$this->karima->id]);
});

it('forbids opening an office outside the role by its id', function (string $route) {
    $user = rosKarimaUser($this);

    $this->actingAs($user)->get(route($route, $this->karimaA))->assertOk();
    $this->actingAs($user)->get(route($route, $this->normalA))->assertForbidden();
})->with(['offices.show', 'offices.statistics', 'offices.pdf']);

it('forbids editing an office outside the role', function () {
    $this->actingAs(rosKarimaUser($this))->get(route('offices.edit', $this->normalA))->assertForbidden();
});

it('refuses to save an office with a description outside the role', function () {
    Livewire::actingAs(rosKarimaUser($this))->test(Create::class, ['office' => $this->karimaA])
        ->set('location_description_id', $this->normal->id)
        ->call('saveAndExit')
        ->assertHasErrors('location_description_id');

    expect($this->karimaA->fresh()->location_description_id)->toBe($this->karima->id);
});

it('refuses a tampered office id in the form', function () {
    Livewire::actingAs(rosKarimaUser($this))->test(Create::class, ['office' => $this->karimaA])
        ->set('office_id', $this->normalA->id)
        ->assertForbidden();
});

it('forbids deleting an office outside the role', function () {
    Livewire::actingAs(rosKarimaUser($this))->test(Index::class)
        ->call('askDelete', $this->normalA->id)
        ->assertForbidden();

    expect(Office::find($this->normalA->id))->not->toBeNull();
});

it('counts only allowed offices on the dashboard', function () {
    Livewire::actingAs(rosKarimaUser($this))->test(Dashboard::class)
        ->assertViewHas('totalOffices', 2);
});

it('scopes the visit reports list', function () {
    $names = OfficeVisitReports::scopeOffices(Office::query(), rosKarimaUser($this))->pluck('name')->sort()->values()->all();

    expect($names)->toBe(['مقر كريمة توثيق', 'مقر كريمة شهر']);
});

// ── شاشة الأدوار ──

function rosSuperAdmin(): User
{
    return rosUser([Role::findOrCreate('super-admin', 'web')], []);
}

it('stores the descriptions chosen on a new role', function () {
    Livewire::actingAs(rosSuperAdmin())->test(RoleCreate::class)
        ->set('name', 'حياة كريمة')
        ->set('selectedPermissions', ['offices.index', 'offices.view'])
        ->set('locationDescriptionIds', [(string) $this->karima->id])
        ->call('save')
        ->assertHasNoErrors();

    $role = Role::findByName('حياة كريمة', 'web');
    expect(DB::table('location_description_role')->where('role_id', $role->id)->pluck('location_description_id')->all())
        ->toBe([$this->karima->id]);
});

it('accepts report permissions on a restricted role (reports apply the restriction)', function (string $permission) {
    Livewire::actingAs(rosSuperAdmin())->test(RoleCreate::class)
        ->set('name', 'حياة كريمة')
        ->set('selectedPermissions', ['offices.index', $permission])
        ->set('locationDescriptionIds', [(string) $this->karima->id])
        ->call('save')
        ->assertHasNoErrors();

    expect(Role::findByName('حياة كريمة', 'web')->hasPermissionTo($permission))->toBeTrue();
})->with(['offices.export', 'offices.phone-directory']);

it('loads and clears the restriction when editing a role', function () {
    $role = rosRole('karima', ['offices.index'], descriptions: [$this->karima->id]);

    Livewire::actingAs(rosSuperAdmin())->test(RoleEdit::class, ['role' => $role])
        ->assertSet('locationDescriptionIds', [(string) $this->karima->id])
        ->set('locationDescriptionIds', [])
        ->call('save')
        ->assertHasNoErrors();

    expect(DB::table('location_description_role')->where('role_id', $role->id)->exists())->toBeFalse();
});

it('refuses deleting a description or type a role is restricted to', function () {
    // بلا مقرات عليهما — فلا يمنع الحذفَ سببٌ آخر غير الدور
    $type = OfficeType::factory()->create(['name' => 'نوع بلا مقرات']);
    $desc = LocationDescription::create(['name' => 'وصف بلا مقرات']);
    $free = LocationDescription::create(['name' => 'وصف حر']);
    rosRole('karima', ['offices.index'], types: [$type->id], descriptions: [$desc->id]);

    $admin = rosSuperAdmin();
    Permission::findOrCreate('offices.settings', 'web');
    $admin->givePermissionTo('offices.settings');

    Livewire::actingAs($admin)->test(DescriptionsIndex::class)->call('askDelete', $desc->id)->call('deleteRow');
    Livewire::actingAs($admin)->test(TypesIndex::class)->call('askDelete', $type->id)->call('deleteRow');
    Livewire::actingAs($admin)->test(DescriptionsIndex::class)->call('askDelete', $free->id)->call('deleteRow');

    expect(LocationDescription::find($desc->id))->not->toBeNull()
        ->and(OfficeType::find($type->id))->not->toBeNull()
        ->and(LocationDescription::find($free->id))->toBeNull();
});

it('folds the scope section for an unrestricted role and opens it with a summary for a restricted one', function () {
    $plain = rosRole('inspector', ['offices.index']);
    $karima = rosRole('karima', ['offices.index'], descriptions: [$this->karima->id]);

    Livewire::actingAs(rosSuperAdmin())->test(RoleEdit::class, ['role' => $plain])
        ->assertSee(__('home.role_office_scope_summary_all'))
        ->assertSeeHtml('x-data="{ open: false }"');

    Livewire::actingAs(rosSuperAdmin())->test(RoleEdit::class, ['role' => $karima])
        ->assertSee(__('home.role_office_scope_summary_descriptions', ['names' => 'حياة كريمة']))
        ->assertSeeHtml('x-data="{ open: true }"');
});
