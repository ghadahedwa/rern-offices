<?php

use App\Livewire\Vehicles\Index;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/** أسماء السيارات بترتيب العرض */
function vehicleOrder($test): array
{
    return collect($test->viewData('vehicles')->items())->pluck('name')->all();
}

beforeEach(function () {
    $this->admin = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));

    // الترتيب المعتمد يخالف الأبجدي عمداً: «ي» قبل «أ»
    $yy = Governorate::factory()->create(['name' => 'يي', 'order' => 1]);
    $aa = Governorate::factory()->create(['name' => 'أأ', 'order' => 2]);
    $t1 = VehicleType::create(['name' => 'ب-نوع']);
    $t2 = VehicleType::create(['name' => 'أ-نوع']);

    // أُنشئت بترتيبٍ يخالف المتوقَّع، فالترتيب الافتراضي لا ينجح بالمصادفة
    Vehicle::create(['governorate_id' => $aa->id, 'name' => 'ج', 'type_id' => $t2->id, 'status' => 'stopped']);
    Vehicle::create(['governorate_id' => $yy->id, 'name' => 'د', 'type_id' => $t1->id, 'status' => 'working']);
    Vehicle::create(['governorate_id' => $aa->id, 'name' => 'أ', 'type_id' => $t1->id, 'status' => 'maintenance']);
    Vehicle::create(['governorate_id' => $yy->id, 'name' => 'ب', 'type_id' => $t2->id, 'status' => 'working']);
});

it('orders by governorate order then name by default', function () {
    expect(vehicleOrder(Livewire::actingAs($this->admin)->test(Index::class)))
        ->toBe(['ب', 'د', 'أ', 'ج']);
});

it('sorts by name both ways and returns to the default on the third click', function () {
    $t = Livewire::actingAs($this->admin)->test(Index::class)->call('sort', 'name');
    expect(vehicleOrder($t))->toBe(['أ', 'ب', 'ج', 'د']);

    $t->call('sort', 'name');
    expect(vehicleOrder($t))->toBe(['د', 'ج', 'ب', 'أ']);

    $t->call('sort', 'name')->assertSet('sortBy', '');
    expect(vehicleOrder($t))->toBe(['ب', 'د', 'أ', 'ج']);
});

it('sorts governorates by their configured order, not alphabetically', function () {
    $t = Livewire::actingAs($this->admin)->test(Index::class)->call('sort', 'governorate');
    expect(array_slice(vehicleOrder($t), 0, 2))->toEqualCanonicalizing(['ب', 'د']);   // «يي» order=1 أولاً

    $t->call('sort', 'governorate');
    expect(array_slice(vehicleOrder($t), 0, 2))->toEqualCanonicalizing(['أ', 'ج']);
});

it('sorts by type name', function () {
    $t = Livewire::actingAs($this->admin)->test(Index::class)->call('sort', 'type');
    expect(array_slice(vehicleOrder($t), 0, 2))->toEqualCanonicalizing(['ج', 'ب']);   // «أ-نوع»
});

it('falls back to the default order for an unknown column from the link', function () {
    $t = Livewire::withQueryParams(['sort' => 'id; drop table vehicles', 'dir' => 'desc'])
        ->actingAs($this->admin)->test(Index::class);

    expect(vehicleOrder($t))->toBe(['ب', 'د', 'أ', 'ج']);
});

it('keeps the sort in the link', function () {
    Livewire::actingAs($this->admin)->test(Index::class)
        ->call('sort', 'status')
        ->assertSet('sortBy', 'status');

    $t = Livewire::withQueryParams(['sort' => 'status'])->actingAs($this->admin)->test(Index::class);
    expect(vehicleOrder($t)[0])->toBe('أ');   // maintenance قبل stopped وworking أبجدياً
});
