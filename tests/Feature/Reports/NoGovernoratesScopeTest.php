<?php

use App\Livewire\Reports;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * مستخدم بصلاحية التقرير وبلا محافظات لا يرى شيئاً — `[]` كان يُقرأ «بلا حدّ» فيرى الجمهورية
 * (`->when($allowedGovIds, …)`؛ الصحيح `!== null`). العلامة اسم محافظة لا تظهر في منسدلة
 * فلاتره أصلاً، فظهورها في الصفحة = تسرّب من النتائج.
 */
const NO_GOV_MARKER = 'محافظة-خارج-النطاق';

beforeEach(function () {
    $gov = Governorate::factory()->create(['name' => NO_GOV_MARKER]);
    Office::factory()->create(['governorate_id' => $gov->id, 'type_id' => OfficeType::factory()->create()->id, 'name' => 'مقر-خارج-النطاق']);
    Vehicle::create([
        'governorate_id' => $gov->id, 'name' => 'سيارة-خارج-النطاق', 'status' => 'working',
        'license_expiry_date' => now()->addDays(10)->toDateString(),
    ]);
    $this->gov = $gov;
});

function reportUser(string $permission, array $govIds): User
{
    Permission::findOrCreate($permission, 'web');
    $role = Role::findOrCreate('no-gov-' . $permission, 'web');
    $role->givePermissionTo($permission);
    $user = tap(User::factory()->create())->assignRole($role);
    $user->governorates()->sync($govIds);

    return $user->fresh();
}

dataset('scoped reports', [
    'multi office'         => [Reports\MultiOffice::class, 'offices.export', 'مقر-خارج-النطاق', []],
    'offices by type'      => [Reports\OfficesByType::class, 'offices.export', NO_GOV_MARKER, []],
    'stats comparison'     => [Reports\StatsComparison::class, 'offices.export', NO_GOV_MARKER, ['year1' => '2024', 'year2' => '2025']],
    'device count'         => [Reports\DeviceCount::class, 'offices.export', NO_GOV_MARKER, []],
    'multi vehicle'        => [Reports\MultiVehicle::class, 'vehicles.export', 'سيارة-خارج-النطاق', []],
    'vehicle status'       => [Reports\VehicleStatus::class, 'vehicles.export', NO_GOV_MARKER, []],
    'vehicle coverage'     => [Reports\VehicleCoverage::class, 'vehicles.export', 'سيارة-خارج-النطاق', []],
    'vehicle licenses'     => [Reports\VehicleLicenses::class, 'vehicles.export', 'سيارة-خارج-النطاق', []],
    'vehicle device count' => [Reports\VehicleDeviceCount::class, 'vehicles.export', NO_GOV_MARKER, []],
]);

it('shows nothing to a user with the permission but no governorates', function (string $component, string $perm, string $marker, array $set) {
    $test = Livewire::actingAs(reportUser($perm, []))->test($component);
    foreach ($set as $k => $v) {
        $test->set($k, $v);
    }

    $test->call('search')
        ->assertDontSee($marker)
        ->assertSee(__('home.no_governorates_title'));
})->with('scoped reports');

it('still shows the user their own governorate, without the notice', function (string $component, string $perm, string $marker, array $set) {
    $test = Livewire::actingAs(reportUser($perm, [$this->gov->id]))->test($component);
    foreach ($set as $k => $v) {
        $test->set($k, $v);
    }

    $test->call('search')
        ->assertSee($marker)
        ->assertDontSee(__('home.no_governorates_title'));
})->with('scoped reports');

it('shows the notice on the offices and vehicles lists, which stay empty', function () {
    $this->actingAs(reportUser('offices.index', []))->get(route('offices.index'))
        ->assertOk()->assertSee(__('home.no_governorates_title'))->assertDontSee('مقر-خارج-النطاق');

    $this->actingAs(reportUser('vehicles.index', []))->get(route('vehicles.index'))
        ->assertOk()->assertSee(__('home.no_governorates_title'))->assertDontSee('سيارة-خارج-النطاق');
});

it('never shows the notice to super-admin', function () {
    $admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));

    $this->actingAs($admin)->get(route('offices.index'))->assertOk()->assertDontSee(__('home.no_governorates_title'));
});
