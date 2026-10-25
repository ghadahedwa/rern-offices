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
use App\Livewire\Contractors\Create as ContractorCreate;
use App\Livewire\Contractors\Index as ContractorsIndex;
use App\Livewire\Warehouses\Items\Create as ItemCreate;
use App\Livewire\Warehouses\Items\Index as ItemsIndex;
use App\Livewire\Warehouses\Items\Show as ItemShow;
use App\Livewire\Warehouses\Manage\Create as ManageCreate;
use App\Livewire\Warehouses\Manage\Index as ManageIndex;
use App\Livewire\Warehouses\Manage\Show as ManageShow;
use App\Livewire\Warehouses\Stock;
use App\Models\ContractorAssignment;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\Warehouse;
use App\Models\WarehouseType;
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

it('السيارات: زرّا الرجوع في العرض والتعديل يحملان الفلاتر', function (string $route, int $links) {
    Livewire::withQueryParams(['q' => 'سيارة', 'governorate_id' => $this->gov->id])->test(VehiclesIndex::class);

    $back = e(ListReturnUrl::back('vehicles.index'));
    expect($back)->toContain('governorate_id='.$this->gov->id);

    expect(substr_count($this->get(route($route, $this->vehicle))->assertOk()->getContent(), 'href="'.$back.'"'))->toBe($links);
})->with([['vehicles.show', 1], ['vehicles.edit', 1]]);

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
        ->assertSee('href="'.$back.'"', false)
        ->assertOk();
});

it('بلا قائمةٍ سابقة يرجع زرّ الرجوع للقائمة بلا فلاتر', function () {
    expect(ListReturnUrl::back('vehicles.index'))->toBe(route('vehicles.index'))
        ->and(ListReturnUrl::back('offices.visit-reports', ['type' => 'inspector']))->toBe(route('offices.visit-reports', 'inspector'));
});

// ── العاملون بالتعاقد ──

it('العاملون: يحفظ الفلاتر ويرجع إليها كما هي — و«الكل» في الحالة', function () {
    $query = ['q' => 'عامل', 'gov' => (string) $this->gov->id, 'status' => 'all', 'sort' => 'name'];

    Livewire::withQueryParams($query)->test(ContractorsIndex::class);

    expect(ListReturnUrl::query('contractors.index'))->toEqual($query);

    Livewire::withQueryParams(ListReturnUrl::query('contractors.index'))->test(ContractorsIndex::class)
        ->assertSet('search', 'عامل')
        ->assertSet('governorate', (string) $this->gov->id)
        ->assertSet('status', 'all');
});

it('فلترٌ افتراضيه غير فارغ ثم فُرِّغ يُحفظ فارغاً لا يسقط إلى الافتراضي', function () {
    Livewire::withQueryParams([])->test(ContractorsIndex::class)->set('status', '');

    expect(ListReturnUrl::query('contractors.index'))->toEqual(['status' => '']);
});

it('العاملون: زرّا الرجوع في التعديل وحفظه يحملان الفلاتر', function () {
    $contractor = ContractorAssignment::factory()->create(['office_id' => $this->office->id])->contractor;

    Livewire::withQueryParams(['status' => 'ended'])->test(ContractorsIndex::class);
    $back = route('contractors.index', ['status' => 'ended']);

    expect(substr_count($this->get(route('contractors.edit', $contractor))->assertOk()->getContent(), 'href="'.e($back).'"'))->toBe(2);

    Livewire::test(ContractorCreate::class, ['contractor' => $contractor])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect($back);
});

// ── المخازن: الأصناف · إدارة المخازن · الأرصدة ──

function lrItem(): Item
{
    return Item::create([
        'name'             => 'كمبيوتر',
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
        'item_category_id' => ItemCategory::firstOrCreate(['name' => 'الكمبيوتر'], ['order' => 1])->id,
    ]);
}

function lrWarehouse(Governorate $gov): Warehouse
{
    return Warehouse::create([
        'name'              => 'مخزن بنها',
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'رئيسي'], ['level' => 1, 'order' => 1])->id,
        'governorate_id'    => $gov->id,
        'is_active'         => true,
    ]);
}

it('الأصناف: زرّا الرجوع في التعديل وحفظه يحملان الفلاتر', function () {
    $item = lrItem();

    Livewire::withQueryParams(['q' => 'كمب', 'category' => (string) $item->item_category_id])->test(ItemsIndex::class);
    $back = route('items.index', ['q' => 'كمب', 'category' => (string) $item->item_category_id]);
    expect(ListReturnUrl::back('items.index'))->toBe($back);

    expect(substr_count($this->get(route('items.edit', $item))->assertOk()->getContent(), 'href="'.e($back).'"'))->toBe(2);

    Livewire::test(ItemCreate::class, ['item' => $item])->call('save')->assertHasNoErrors()->assertRedirect($back);
});

it('إدارة المخازن: زرّا الرجوع في التعديل وحفظه وبروفايل المخزن يحملون الفلاتر', function () {
    $warehouse = lrWarehouse($this->gov);

    Livewire::withQueryParams(['q' => 'بنها'])->test(ManageIndex::class);
    $back = route('warehouse-manage.index', ['q' => 'بنها']);

    expect(substr_count($this->get(route('warehouse-manage.edit', $warehouse))->assertOk()->getContent(), 'href="'.e($back).'"'))->toBe(2);
    Livewire::test(ManageCreate::class, ['warehouse' => $warehouse])->call('save')->assertHasNoErrors()->assertRedirect($back);

    // صاحب إدارة المخازن يرجع من البروفايل إليها
    expect(Livewire::test(ManageShow::class, ['warehouse' => $warehouse])->instance()->backRoute())->toBe($back);
});

it('الأرصدة: بروفايلا الصنف والمخزن يرجعان إليها بفلاترها لمن مدخله الأرصدة', function () {
    $item      = lrItem();
    $warehouse = lrWarehouse($this->gov);

    foreach (['warehouses.index'] as $p) {
        Spatie\Permission\Models\Permission::findOrCreate($p, 'web');
    }
    $operator = User::factory()->create();
    $operator->givePermissionTo('warehouses.index');
    $operator->warehouses()->sync([$warehouse->id]);
    $this->actingAs($operator->fresh());

    Livewire::withQueryParams(['q' => 'كمب', 'low' => '1'])->test(Stock::class);
    $back = route('warehouses.stock', ['q' => 'كمب', 'low' => 1]);

    expect(Livewire::test(ItemShow::class, ['item' => $item])->instance()->backRoute())->toBe($back)
        ->and(Livewire::test(ManageShow::class, ['warehouse' => $warehouse])->instance()->backRoute())->toBe($back);
});
