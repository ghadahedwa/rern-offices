<?php

use App\Livewire\Offices\VisitReportsIndex;
use App\Livewire\Vehicles\Create as VehicleCreate;
use App\Livewire\Vehicles\Index as VehiclesIndex;
use App\Models\Governorate;
use App\Models\Office;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Models\VehicleWorkSystem;
use App\Support\ListReturnUrl;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * الرجوع إلى القائمة بفلاترها — السيارات وتقارير الزيارة (طلب العميل 2026-10-25).
 * والمقرات في tests/Feature/Offices/ListFiltersReturnTest.php.
 */
beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(Role::findOrCreate('super-admin', 'web'));
    $this->actingAs($this->admin);

    $this->gov     = Governorate::factory()->create();
    $this->vehicle = Vehicle::create([
        'governorate_id' => $this->gov->id, 'name' => 'سيارة ١',
        'type_id'        => VehicleType::create(['name' => 'نوع'])->id,
        'work_system_id' => VehicleWorkSystem::create(['name' => 'نظام'])->id,
    ]);
    $this->office  = Office::factory()->create(['governorate_id' => $this->gov->id]);
});

// ── السيارات ──

it('السيارات: يحفظ البحث والفلاتر ويرجع إليها كما هي', function () {
    $query = ['q' => 'سيارة', 'governorate_id' => $this->gov->id, 'status' => 'active', 'sort' => 'name', 'dir' => 'desc'];

    Livewire::withQueryParams($query)->test(VehiclesIndex::class);

    expect(ListReturnUrl::query('vehicles.index'))->toEqual($query);

    Livewire::withQueryParams(ListReturnUrl::query('vehicles.index'))->test(VehiclesIndex::class)
        ->assertSet('search', 'سيارة')
        ->assertSet('governorate_id', $this->gov->id)
        ->assertSet('status', 'active')
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDir', 'desc')
        ->assertSet('showAdvanced', true);
});

it('السيارات: لوحة البحث المتقدم مقفولة بلا فلتر الحالة', function () {
    Livewire::withQueryParams(['q' => 'سيارة'])->test(VehiclesIndex::class)->assertSet('showAdvanced', false);
});

it('السيارات: يحفظ رقم الصفحة', function () {
    foreach (range(2, 20) as $i) {
        Vehicle::create(['governorate_id' => $this->gov->id, 'name' => "سيارة $i"]);
    }

    Livewire::withQueryParams(['page' => 2])->test(VehiclesIndex::class);

    expect(ListReturnUrl::query('vehicles.index'))->toEqual(['page' => 2]);
});

it('السيارات: زرّا الرجوع في العرض والتعديل يحملان الفلاتر', function (string $route) {
    Livewire::withQueryParams(['q' => 'سيارة', 'governorate_id' => $this->gov->id])->test(VehiclesIndex::class);

    $back = e(ListReturnUrl::back('vehicles.index'));
    expect($back)->toContain('governorate_id='.$this->gov->id);

    $this->get(route($route, $this->vehicle))->assertOk()->assertSee('href="'.$back.'"', false);
})->with(['vehicles.show', 'vehicles.edit']);

it('السيارات: الحفظ والخروج يرجع للقائمة بفلاترها', function () {
    Livewire::withQueryParams(['governorate_id' => $this->gov->id])->test(VehiclesIndex::class);

    Livewire::test(VehicleCreate::class, ['vehicle' => $this->vehicle])
        ->call('saveAndExit')
        ->assertHasNoErrors()
        ->assertRedirect(route('vehicles.index', ['governorate_id' => $this->gov->id]));
});

// ── تقارير الزيارة ──

it('تقارير الزيارة: يحفظ الفلاتر لكل تقريرٍ على حدة', function () {
    Livewire::withQueryParams(['q' => 'مقر', 'gov' => (string) $this->gov->id, 'visit' => 'm6'])
        ->test(VisitReportsIndex::class, ['type' => 'inspector']);

    expect(ListReturnUrl::query('offices.visit-reports', ['type' => 'inspector']))
        ->toEqual(['q' => 'مقر', 'gov' => (string) $this->gov->id, 'visit' => 'm6'])
        ->and(ListReturnUrl::query('offices.visit-reports', ['type' => 'counselor']))->toBe([]);

    Livewire::withQueryParams(ListReturnUrl::query('offices.visit-reports', ['type' => 'inspector']))
        ->test(VisitReportsIndex::class, ['type' => 'inspector'])
        ->assertSet('search', 'مقر')
        ->assertSet('governorate', (string) $this->gov->id)
        ->assertSet('visit', 'm6');
});

it('تقارير الزيارة: زرّ الرجوع في صفحة التقرير يحمل فلاتر قائمته', function () {
    Livewire::withQueryParams(['visit' => 'm6'])->test(VisitReportsIndex::class, ['type' => 'counselor']);

    $back = e(ListReturnUrl::back('offices.visit-reports', ['type' => 'counselor']));
    expect($back)->toBe(e(route('offices.visit-reports', ['type' => 'counselor', 'visit' => 'm6'])));

    $this->get(route('offices.visit-report', [$this->office->id, 'counselor']))
        ->assertOk()
        ->assertSee('href="'.$back.'"', false);
});

it('بلا قائمةٍ سابقة يرجع زرّ الرجوع للقائمة بلا فلاتر', function () {
    expect(ListReturnUrl::back('vehicles.index'))->toBe(route('vehicles.index'))
        ->and(ListReturnUrl::back('offices.visit-reports', ['type' => 'inspector']))->toBe(route('offices.visit-reports', 'inspector'));
});
