<?php

use App\Livewire\Warehouses\Manage\Show;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use App\Models\WarehouseType;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/** فلاتر بروفايل المخزن وترتيبه: التاب في الرابط · فلاتر موحّدة · مُرقِّم لكل تاب. */
function ptUser(): User
{
    Permission::findOrCreate('warehouses.settings', 'web');
    $role = Role::findOrCreate('pt-admin', 'web');
    $role->givePermissionTo('warehouses.settings');

    return tap(User::factory()->create())->assignRole($role);
}

function ptWarehouse(string $typeName = 'رئيسي', int $level = 1): Warehouse
{
    $type = WarehouseType::firstOrCreate(['name' => $typeName], ['level' => $level, 'order' => $level]);

    return Warehouse::create(['name' => 'مخزن '.$typeName, 'warehouse_type_id' => $type->id]);
}

function ptItem(string $name): Item
{
    return Item::create([
        'name'         => $name,
        'item_unit_id' => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
    ]);
}

function ptStock(Warehouse $w, Item $i, int $qty): WarehouseStock
{
    return WarehouseStock::create(['warehouse_id' => $w->id, 'item_id' => $i->id, 'quantity' => $qty]);
}

function ptMovementAt(Warehouse $w, Item $i, string $utcTimestamp, string $type = 'opening'): WarehouseMovement
{
    $movement = WarehouseMovement::create([
        'warehouse_id'   => $w->id,
        'item_id'        => $i->id,
        'type'           => $type,
        'quantity'       => 5,
        'balance_before' => 0,
        'balance_after'  => 5,
    ]);

    WarehouseMovement::where('id', $movement->id)->update(['created_at' => $utcTimestamp]);

    return $movement->refresh();
}

// ── التاب في الرابط ──────────────────────────────────────

it('يفتح التاب المطلوب من الرابط', function () {
    $w = ptWarehouse();
    $this->actingAs(ptUser());

    Livewire::withQueryParams(['tab' => 'movements'])
        ->test(Show::class, ['warehouse' => $w])
        ->assertSet('tab', 'movements');
});

it('يردّ تاباً مجهولاً في الرابط إلى الأرصدة', function () {
    $w = ptWarehouse();
    $this->actingAs(ptUser());

    Livewire::withQueryParams(['tab' => 'salaries'])
        ->test(Show::class, ['warehouse' => $w])
        ->assertSet('tab', 'stock');
});

it('لا يفتح تاب الوارد لمخزن غير رئيسي ولو من الرابط', function () {
    // الوارد يُسجَّل على الرئيسي وحده — والرابط لا يتجاوز القاعدة
    $branch = ptWarehouse('فرعي', 3);
    $this->actingAs(ptUser());

    Livewire::withQueryParams(['tab' => 'incoming'])
        ->test(Show::class, ['warehouse' => $branch])
        ->assertSet('tab', 'stock');
});

// ── الفلاتر الموحّدة ─────────────────────────────────────

it('يمسح الفلاتر والترتيب عند تغيير التاب', function () {
    // بحث الأرصدة اسم صنف وبحث النقل اسم مخزن — بقاؤه يُخرج شاشة فارغة بلا سبب
    $w = ptWarehouse();
    $this->actingAs(ptUser());

    Livewire::test(Show::class, ['warehouse' => $w])
        ->set('search', 'حبر')
        ->call('sort', 'quantity')
        ->call('setTab', 'transfers')
        ->assertSet('search', '')
        ->assertSet('sortBy', '')
        ->assertSet('tab', 'transfers');
});

it('يبحث في أصناف المخزن بحثاً عربياً مطبَّعاً', function () {
    $w = ptWarehouse();
    ptStock($w, ptItem('حبر أسود'), 5);
    ptStock($w, ptItem('ورق تصوير'), 9);
    $this->actingAs(ptUser());

    $rows = Livewire::test(Show::class, ['warehouse' => $w])
        ->set('search', 'حبر اسود')
        ->viewData('stocks');

    expect($rows->pluck('quantity')->all())->toBe([5]);
});

// ── الترتيب داخل التاب ───────────────────────────────────

it('يرتّب أرصدة المخزن بالكمية ويعود للافتراضي بالضغطة الثالثة', function () {
    $w = ptWarehouse();
    ptStock($w, ptItem('ألف'), 90);
    ptStock($w, ptItem('باء'), 10);
    $this->actingAs(ptUser());

    $component = Livewire::test(Show::class, ['warehouse' => $w])->call('sort', 'quantity');
    expect($component->viewData('stocks')->pluck('quantity')->all())->toBe([10, 90]);

    $component->call('sort', 'quantity');
    expect($component->viewData('stocks')->pluck('quantity')->all())->toBe([90, 10]);

    // الافتراضي: باسم الصنف
    $component->call('sort', 'quantity');
    expect($component->viewData('stocks')->pluck('quantity')->all())->toBe([90, 10])
        ->and($component->get('sortBy'))->toBe('');
});

it('يتجاهل عمود ترتيب من تابٍ آخر يصل من الرابط', function () {
    // 'supplier' عمود تاب الوارد — لا معنى له في جدول الأرصدة
    $w = ptWarehouse();
    ptStock($w, ptItem('ألف'), 90);
    ptStock($w, ptItem('باء'), 10);
    $this->actingAs(ptUser());

    $rows = Livewire::withQueryParams(['sort' => 'supplier'])
        ->test(Show::class, ['warehouse' => $w])
        ->viewData('stocks');

    expect($rows->pluck('quantity')->all())->toBe([90, 10]);
});

// ── فلاتر تاب الحركات ────────────────────────────────────

it('يفلتر حركات المخزن بالصنف ويتجاهل معرّفاً تالفاً', function () {
    $w    = ptWarehouse();
    $ink  = ptItem('حبر');
    $pap  = ptItem('ورق');
    ptMovementAt($w, $ink, '2026-08-20 09:00:00');
    ptMovementAt($w, $pap, '2026-08-21 09:00:00');
    $this->actingAs(ptUser());

    $component = Livewire::withQueryParams(['tab' => 'movements'])->test(Show::class, ['warehouse' => $w]);

    expect($component->set('itemFilter', (string) $ink->id)->viewData('movements')->total())->toBe(1)
        ->and($component->set('itemFilter', 'حبر')->viewData('movements')->total())->toBe(2);
});

it('يتجاهل نوع حركة غير معروف يصل من الرابط', function () {
    $w = ptWarehouse();
    ptMovementAt($w, ptItem('حبر'), '2026-08-20 09:00:00');
    $this->actingAs(ptUser());

    $rows = Livewire::withQueryParams(['tab' => 'movements', 'type' => 'refund'])
        ->test(Show::class, ['warehouse' => $w])
        ->viewData('movements');

    expect($rows->total())->toBe(1);
});

it('يُدخل حركة الواحدة فجراً بتوقيت القاهرة في فلتر يومها داخل البروفايل', function () {
    $w = ptWarehouse();
    ptMovementAt($w, ptItem('حبر'), '2026-08-25 22:30:00');
    $this->actingAs(ptUser());

    $component = Livewire::withQueryParams(['tab' => 'movements'])->test(Show::class, ['warehouse' => $w]);

    expect($component->set('dateFrom', '2026-08-26')->set('dateTo', '2026-08-26')->viewData('movements')->total())->toBe(1)
        ->and($component->set('dateFrom', '2026-08-25')->set('dateTo', '2026-08-25')->viewData('movements')->total())->toBe(0);
});

// ── الترقيم لكل تاب ──────────────────────────────────────

it('يرجع لأول صفحة في مُرقِّم التاب عند تغيير الفلتر', function () {
    // ⚠️ الصفحة هنا مُرقِّم مسمّى (stockPage) — والـtraits تنادي resetPage() بلا اسم
    $w = ptWarehouse();
    foreach (range(1, 20) as $n) {
        ptStock($w, ptItem('صنف '.str_pad((string) $n, 2, '0', STR_PAD_LEFT)), $n);
    }
    $this->actingAs(ptUser());

    $component = Livewire::test(Show::class, ['warehouse' => $w])->call('setPage', 2, 'stockPage');
    expect($component->viewData('stocks')->currentPage())->toBe(2);

    $component->set('search', 'صنف');
    expect($component->viewData('stocks')->currentPage())->toBe(1);
});

it('يطبّق عدد الصفوف المختار على جدول التاب', function () {
    $w = ptWarehouse();
    foreach (range(1, 20) as $n) {
        ptStock($w, ptItem('صنف '.$n), $n);
    }
    $this->actingAs(ptUser());

    $component = Livewire::test(Show::class, ['warehouse' => $w]);
    expect($component->viewData('stocks')->count())->toBe(15);

    $component->set('perPage', '25');
    expect($component->viewData('stocks')->count())->toBe(20);
});
