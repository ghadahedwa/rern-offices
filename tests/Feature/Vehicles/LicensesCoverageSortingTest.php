<?php

use App\Livewire\Reports\VehicleCoverage;
use App\Livewire\Reports\VehicleLicenses;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));

    $yy = Governorate::factory()->create(['name' => 'يي', 'order' => 1]);
    $aa = Governorate::factory()->create(['name' => 'أأ', 'order' => 2]);

    // الترتيب الافتراضي للتراخيص بالتاريخ — مختلف عن الاسم وعن المحافظة عمداً
    Vehicle::create(['governorate_id' => $aa->id, 'name' => 'أ', 'license_plate' => '300', 'license_expiry_date' => now()->addDays(5)->toDateString()]);
    Vehicle::create(['governorate_id' => $yy->id, 'name' => 'ج', 'license_plate' => '100', 'license_expiry_date' => now()->addDays(40)->toDateString()]);
    Vehicle::create(['governorate_id' => $yy->id, 'name' => 'ب', 'license_plate' => '200', 'license_expiry_date' => null]);
});

// ── التراخيص ──

it('keeps the licenses default: nearest expiry first, no date last', function () {
    expect(VehicleLicenses::rowsFor(null, [])->pluck('name')->all())->toBe(['أ', 'ج', 'ب']);
});

it('sorts licenses by name, governorate order and plate', function (string $sort, string $dir, array $names) {
    expect(VehicleLicenses::rowsFor(null, ['sort' => $sort, 'dir' => $dir])->pluck('name')->all())->toBe($names);
})->with([
    'name'             => ['name', 'asc', ['أ', 'ب', 'ج']],
    'governorate asc'  => ['governorate', 'asc', ['ج', 'ب', 'أ']],   // «يي» order=1، والتعادل بالمعرّف
    'plate desc'       => ['plate', 'desc', ['أ', 'ب', 'ج']],
]);

it('ignores an unknown licenses sort column', function () {
    expect(VehicleLicenses::rowsFor(null, ['sort' => 'license_expiry_date desc --', 'dir' => 'desc'])->pluck('name')->all())
        ->toBe(['أ', 'ج', 'ب']);
});

it('applies the licenses header click on screen and hands it to the pdf', function () {
    // الفلتر الافتراضي (٦٠ يوماً) يستبعد «ب» بلا تاريخ
    $test = Livewire::actingAs($this->admin)->test(VehicleLicenses::class)
        ->call('search')->call('sort', 'name')->call('sort', 'name');

    expect($test->viewData('rows')->pluck('name')->all())->toBe(['ج', 'أ']);

    $test->call('exportPdf');
    expect(session('vehicle_licenses_filters'))->toMatchArray(['sort' => 'name', 'dir' => 'desc']);

    $this->actingAs($this->admin)->get(route('reports.vehicle-licenses.pdf'))->assertOk();
});

// ── التغطية ──

it('keeps the coverage default: governorate order then name', function () {
    expect(VehicleCoverage::rowsFor(null, [])->pluck('name')->all())->toBe(['ب', 'ج', 'أ']);
});

it('sorts coverage by name and governorate', function () {
    expect(VehicleCoverage::rowsFor(null, ['sort' => 'name', 'dir' => 'desc'])->pluck('name')->all())->toBe(['ج', 'ب', 'أ']);
    expect(VehicleCoverage::rowsFor(null, ['sort' => 'governorate', 'dir' => 'desc'])->pluck('name')->first())->toBe('أ');
});

it('applies the coverage header click on screen and hands it to the pdf', function () {
    $test = Livewire::actingAs($this->admin)->test(VehicleCoverage::class)
        ->call('search')->call('sort', 'name')->call('sort', 'name');

    expect($test->viewData('rows')->pluck('name')->all())->toBe(['ج', 'ب', 'أ']);

    $test->call('exportPdf');
    expect(session('vehicle_coverage_filters'))->toMatchArray(['sort' => 'name', 'dir' => 'desc']);

    $this->actingAs($this->admin)->get(route('reports.vehicle-coverage.pdf'))->assertOk();
});

it('keeps both pdfs inside the user governorates', function () {
    $user = tap(User::factory()->create())->assignRole(tap(Role::findOrCreate('lc-export', 'web'))
        ->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('vehicles.export', 'web')));

    expect(VehicleLicenses::rowsFor([], []))->toHaveCount(0)
        ->and(VehicleCoverage::rowsFor([], []))->toHaveCount(0);

    $this->actingAs($user->fresh())->get(route('reports.vehicle-licenses.pdf'))->assertOk();
});
