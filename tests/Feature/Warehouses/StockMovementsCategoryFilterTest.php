<?php

use App\Livewire\Warehouses\Movements;
use App\Livewire\Warehouses\Stock;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use App\Models\WarehouseType;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/** مستخدم يملك صلاحية عرض المخازن وحدها. */
function stockViewer(): User
{
    Permission::findOrCreate('warehouses.index', 'web');
    $role = Role::findOrCreate('wh-viewer', 'web');
    $role->givePermissionTo('warehouses.index');

    return tap(User::factory()->create())->assignRole($role);
}

function mainWarehouse(string $name = 'المخزن الرئيسي'): Warehouse
{
    $type = WarehouseType::firstOrCreate(['name' => 'رئيسي'], ['level' => 1, 'order' => 1]);

    return Warehouse::create(['name' => $name, 'warehouse_type_id' => $type->id]);
}

function itemIn(string $categoryName, string $itemName, int $order = 1): Item
{
    $category = ItemCategory::firstOrCreate(['name' => $categoryName], ['order' => $order]);

    return Item::create([
        'name'             => $itemName,
        'item_category_id' => $category->id,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
    ]);
}

function stockRow(Warehouse $w, Item $i, int $qty = 10): WarehouseStock
{
    return WarehouseStock::create(['warehouse_id' => $w->id, 'item_id' => $i->id, 'quantity' => $qty]);
}

function movementRow(Warehouse $w, Item $i, int $qty = 5): WarehouseMovement
{
    return WarehouseMovement::create([
        'warehouse_id'    => $w->id,
        'item_id'         => $i->id,
        'type'            => 'opening',
        'quantity'        => $qty,
        'balance_before'  => 0,
        'balance_after'   => $qty,
        'created_at'      => now(),
    ]);
}

// ── شاشة الأرصدة ─────────────────────────────────────────

it('يفلتر الأرصدة بقسم الصنف', function () {
    $w     = mainWarehouse();
    $photo = itemIn('مخزن التصوير', 'حبر توشيبا', 1);
    $fixed = itemIn('مخزن المستديم', 'بونطة شنيور', 2);
    stockRow($w, $photo);
    stockRow($w, $fixed);
    $this->actingAs(stockViewer());

    Livewire::test(Stock::class)
        ->set('categoryFilter', (string) $photo->item_category_id)
        ->assertSee('حبر توشيبا')
        ->assertDontSee('بونطة شنيور');
});

it('يجمع فلتر القسم مع فلتر المخزن في الأرصدة', function () {
    // هذا هو الطلب الأصلي: «تقرير من المخزن الرئيسي، قسم المستديم»
    $main   = mainWarehouse('المخزن الرئيسي');
    $branch = mainWarehouse('مخزن أسيوط');
    $fixed  = itemIn('مخزن المستديم', 'بونطة شنيور', 1);
    $photo  = itemIn('مخزن التصوير', 'حبر توشيبا', 2);

    stockRow($main, $fixed);
    stockRow($main, $photo);
    stockRow($branch, $fixed);
    $this->actingAs(stockViewer());

    $rows = Livewire::test(Stock::class)
        ->set('warehouseFilter', (string) $main->id)
        ->set('categoryFilter', (string) $fixed->item_category_id)
        ->viewData('stocks');

    expect($rows->total())->toBe(1)
        ->and($rows->first()->item->name)->toBe('بونطة شنيور')
        ->and($rows->first()->warehouse->name)->toBe('المخزن الرئيسي');
});

it('يعرض أرصدة الأصناف بلا قسم عند اختيار «بلا قسم»', function () {
    $w     = mainWarehouse();
    $photo = itemIn('مخزن التصوير', 'حبر توشيبا', 1);
    $loose = Item::create([
        'name'             => 'صنف بلا تصنيف',
        'item_category_id' => null,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
    ]);
    stockRow($w, $photo);
    stockRow($w, $loose);
    $this->actingAs(stockViewer());

    Livewire::test(Stock::class)
        ->set('categoryFilter', 'none')
        ->assertSee('صنف بلا تصنيف')
        ->assertDontSee('حبر توشيبا');
});

it('يتجاهل قيمة قسم تالفة في شاشة الأرصدة', function () {
    $w     = mainWarehouse();
    $photo = itemIn('مخزن التصوير', 'حبر توشيبا', 1);
    stockRow($w, $photo);
    $this->actingAs(stockViewer());

    Livewire::test(Stock::class)
        ->set('categoryFilter', 'مخزن التصوير')
        ->assertOk()
        ->assertSee('حبر توشيبا');
});

// ── سجل الحركات ──────────────────────────────────────────

it('يفلتر الحركات بقسم الصنف', function () {
    // ⚠️ التحقّق على صفوف viewData لا على الـHTML: شاشة الحركات تعرض كل
    //    الأصناف في قائمة الفلتر المنسدلة، فـassertDontSee يجد الاسم فيها
    //    ولو لم يظهر في الجدول — فيمرّ الاختبار أو يسقط لسببٍ غير الذي يقيسه.
    $w     = mainWarehouse();
    $photo = itemIn('مخزن التصوير', 'حبر توشيبا', 1);
    $fixed = itemIn('مخزن المستديم', 'بونطة شنيور', 2);
    movementRow($w, $photo);
    movementRow($w, $fixed);
    $this->actingAs(stockViewer());

    $rows = Livewire::test(Movements::class)
        ->set('categoryFilter', (string) $photo->item_category_id)
        ->viewData('movements');

    expect($rows->total())->toBe(1)
        ->and($rows->first()->item->name)->toBe('حبر توشيبا');
});

it('يجمع فلتر القسم مع المخزن والنوع في الحركات', function () {
    $main   = mainWarehouse('المخزن الرئيسي');
    $branch = mainWarehouse('مخزن أسيوط');
    $fixed  = itemIn('مخزن المستديم', 'بونطة شنيور', 1);
    $photo  = itemIn('مخزن التصوير', 'حبر توشيبا', 2);

    movementRow($main, $fixed);
    movementRow($main, $photo);
    movementRow($branch, $fixed);
    $this->actingAs(stockViewer());

    $rows = Livewire::test(Movements::class)
        ->set('warehouseFilter', (string) $main->id)
        ->set('categoryFilter', (string) $fixed->item_category_id)
        ->set('typeFilter', 'opening')
        ->viewData('movements');

    expect($rows->total())->toBe(1)
        ->and($rows->first()->item->name)->toBe('بونطة شنيور');
});

it('يتجاهل قيمة قسم تالفة في سجل الحركات', function () {
    $w     = mainWarehouse();
    $photo = itemIn('مخزن التصوير', 'حبر توشيبا', 1);
    movementRow($w, $photo);
    $this->actingAs(stockViewer());

    $screen = Livewire::test(Movements::class)
        ->set('categoryFilter', '1) or 1=1--')
        ->assertOk();

    expect($screen->viewData('movements')->total())->toBe(1);
});

it('يبقي البحث العربي عاملاً مع فلتر القسم', function () {
    $w     = mainWarehouse();
    $photo = itemIn('مخزن التصوير', 'حبر توشيبا', 1);
    $other = itemIn('مخزن التصوير', 'درام زيروكس', 1);
    movementRow($w, $photo);
    movementRow($w, $other);
    $this->actingAs(stockViewer());

    $rows = Livewire::test(Movements::class)
        ->set('categoryFilter', (string) $photo->item_category_id)
        ->set('search', 'توشيبا')
        ->viewData('movements');

    expect($rows->total())->toBe(1)
        ->and($rows->first()->item->name)->toBe('حبر توشيبا');
});
