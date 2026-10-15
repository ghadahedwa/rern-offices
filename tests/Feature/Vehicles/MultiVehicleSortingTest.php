<?php

use App\Livewire\Reports\MultiVehicle;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));

    $yy = Governorate::factory()->create(['name' => 'يي', 'order' => 1]);
    $aa = Governorate::factory()->create(['name' => 'أأ', 'order' => 2]);

    Vehicle::create(['governorate_id' => $aa->id, 'name' => 'أ', 'status' => 'working']);
    Vehicle::create(['governorate_id' => $yy->id, 'name' => 'ج', 'status' => 'stopped']);
    Vehicle::create(['governorate_id' => $yy->id, 'name' => 'ب', 'status' => 'maintenance']);
});

function multiNames($test): array
{
    return collect($test->viewData('vehicles')->items())->pluck('name')->all();
}

/** يلتقط السيارات الداخلة إلى قالب الـPDF لحظة رسمه */
function capturePdfVehicles(string $view): \Closure
{
    $captured = null;
    View::composer($view, function ($v) use (&$captured) { $captured = $v->getData()['vehicles']->pluck('name')->all(); });

    return function () use (&$captured) { return $captured; };
}

it('keeps the default: governorate order then name', function () {
    $t = Livewire::actingAs($this->admin)->test(MultiVehicle::class)->call('search');
    expect(multiNames($t))->toBe(['ب', 'ج', 'أ']);
});

it('sorts by name both ways and back to the default', function () {
    $t = Livewire::actingAs($this->admin)->test(MultiVehicle::class)->call('search')->call('sort', 'name');
    expect(multiNames($t))->toBe(['أ', 'ب', 'ج']);

    $t->call('sort', 'name');
    expect(multiNames($t))->toBe(['ج', 'ب', 'أ']);

    $t->call('sort', 'name');
    expect(multiNames($t))->toBe(['ب', 'ج', 'أ']);
});

it('falls back to the default for an unknown column from the link', function () {
    $t = Livewire::withQueryParams(['sort' => 'expiry', 'dir' => 'desc'])
        ->actingAs($this->admin)->test(MultiVehicle::class)->call('search');

    expect(multiNames($t))->toBe(['ب', 'ج', 'أ']);
});

it('prints the comprehensive pdf in the screen order', function () {
    $get = capturePdfVehicles('print.multi-vehicle-pdf');

    Livewire::actingAs($this->admin)->test(MultiVehicle::class)
        ->call('search')->call('sort', 'name')->call('sort', 'name')->call('exportPdf');

    $this->actingAs($this->admin)->get(route('reports.multi-vehicle.pdf'))->assertOk();
    expect($get())->toBe(['ج', 'ب', 'أ']);
});

it('prints the custom pdf in the screen order', function () {
    $get = capturePdfVehicles('print.multi-vehicle-custom-pdf');

    $t = Livewire::actingAs($this->admin)->test(MultiVehicle::class)->call('search')->call('sort', 'status');
    $t->set('selectedColumns', ['governorate', 'name', 'status'])->call('exportCustomPdf');

    $this->actingAs($this->admin)->get(route('reports.multi-vehicle.custom-pdf'))->assertOk();
    expect($get())->toBe(['ب', 'ج', 'أ']);   // maintenance · stopped · working
});
