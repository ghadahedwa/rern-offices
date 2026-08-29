<?php

use App\Livewire\Warehouses\ItemBalances;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WarehouseType;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * «أرصدة الأصناف» — صفٌّ لكل صنف ومعه إجماليه.
 *
 * ⚠️ لا تُخلط بـ«أرصدة المخازن» (`Stock`): تلك صفٌّ لكل (مخزن × صنف).
 *    وأهم ما يُحرَس هنا: **الصنف بلا رصيدٍ في أي مخزن يبقى في الجدول**
 *    (الأصفار جزء من الجواب)، و**الحد الأدنى يُقاس على الرئيسي وحده**.
 */
function ibUser(array $permissions = ['warehouses.index'], string $role = 'ib-viewer'): User
{
    foreach ($permissions as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $roleModel = Role::findOrCreate($role, 'web');
    $roleModel->syncPermissions($permissions);

    return tap(User::factory()->create())->assignRole($roleModel);
}

function ibWarehouse(string $name, int $level = 1): Warehouse
{
    return Warehouse::create([
        'name'              => $name,
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'نوع '.$level], ['level' => $level, 'order' => $level])->id,
    ]);
}

function ibItem(string $name, ?int $minStock = null, ?string $code = null, ?string $category = null, int $order = 1): Item
{
    return Item::create([
        'name'             => $name,
        'code'             => $code,
        'order'            => $order,
        'min_stock'        => $minStock,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
        'item_category_id' => $category
            ? ItemCategory::firstOrCreate(['name' => $category], ['order' => $order])->id
            : null,
    ]);
}

function ibStock(Warehouse $w, Item $i, int $qty): WarehouseStock
{
    return WarehouseStock::create(['warehouse_id' => $w->id, 'item_id' => $i->id, 'quantity' => $qty]);
}

// ── الحارس ───────────────────────────────────────────────

it('يمنع مَن لا warehouses.index له', function () {
    $this->actingAs(ibUser(['warehouses.settings'], 'ib-settings-only'));

    Livewire::test(ItemBalances::class)->assertStatus(403);
});

it('يفتح الشاشة من رابطها لأمين المخزن — مدخل الأصناف الذي لم يكن له', function () {
    ibItem('كمبيوتر');
    $this->actingAs(ibUser(['warehouses.index'], 'ib-op'));

    $this->get(route('warehouses.item-balances'))
        ->assertOk()
        ->assertSee('كمبيوتر');
});

// ── الأرقام ──────────────────────────────────────────────

it('يجمع رصيد الصنف من المخازن كلها ويعدّ ما فيه رصيد منها', function () {
    $item = ibItem('كمبيوتر');
    $main = ibWarehouse('المخزن الرئيسي', 1);
    $sub  = ibWarehouse('مخزن فرعي', 3);
    $zero = ibWarehouse('مخزن صفره مسجَّل', 3);
    ibStock($main, $item, 40);
    ibStock($sub, $item, 60);
    ibStock($zero, $item, 0);
    $this->actingAs(ibUser());

    $row = Livewire::test(ItemBalances::class)->viewData('items')->first();

    expect((int) $row->total_quantity)->toBe(100)
        // «مخازن بها رصيد» لا «مخازن له فيها صفّ» — الصفر ليس وجوداً
        ->and((int) $row->warehouses_count)->toBe(2)
        ->and((int) $row->main_quantity)->toBe(40);
});

it('لا يخلط رصيد صنفٍ آخر في إجمالي الصنف', function () {
    $item  = ibItem('كمبيوتر', order: 1);
    $other = ibItem('طابعة', order: 2);
    $main  = ibWarehouse('المخزن الرئيسي', 1);
    ibStock($main, $item, 40);
    ibStock($main, $other, 999);
    $this->actingAs(ibUser());

    $rows = Livewire::test(ItemBalances::class)->viewData('items');

    expect($rows->pluck('name')->all())->toBe(['كمبيوتر', 'طابعة'])
        ->and(array_map('intval', $rows->pluck('total_quantity')->all()))->toBe([40, 999]);
});

it('يبقي الصنف الذي لا رصيد له في أي مخزن — الأصفار جزء من الجواب', function () {
    ibItem('صنف له رصيد', order: 1);
    ibItem('صنف بلا رصيد قط', order: 2);
    $main = ibWarehouse('المخزن الرئيسي', 1);
    ibStock($main, Item::where('name', 'صنف له رصيد')->first(), 5);
    $this->actingAs(ibUser());

    $rows = Livewire::test(ItemBalances::class)->viewData('items');

    expect($rows->pluck('name')->all())->toBe(['صنف له رصيد', 'صنف بلا رصيد قط'])
        ->and(array_map('intval', $rows->pluck('total_quantity')->all()))->toBe([5, 0]);
});

it('يقرأ رصيد الرئيسي من المخزن الرئيسي لا من أول مخزن', function () {
    $item = ibItem('كمبيوتر');
    $sub  = ibWarehouse('أ فرعي', 3);
    $main = ibWarehouse('ي رئيسي', 1);
    ibStock($sub, $item, 900);
    ibStock($main, $item, 7);
    $this->actingAs(ibUser());

    expect((int) Livewire::test(ItemBalances::class)->viewData('items')->first()->main_quantity)->toBe(7);
});

// ── الحد الأدنى ──────────────────────────────────────────

it('يقصر تنبيه الحد الأدنى على رصيد الرئيسي لا على الإجمالي', function () {
    // الإجمالي ٩٠٥ فوق الحد بكثير، لكن الرئيسي ٥ تحته — والقاعدة على الرئيسي
    $item = ibItem('كمبيوتر', minStock: 50);
    ibStock(ibWarehouse('مخزن فرعي', 3), $item, 900);
    ibStock(ibWarehouse('المخزن الرئيسي', 1), $item, 5);
    $this->actingAs(ibUser());

    $rows = Livewire::test(ItemBalances::class)->set('lowOnly', true)->viewData('items');

    expect($rows->pluck('name')->all())->toBe(['كمبيوتر']);
});

it('يعدّ الصنف الغائب عن الرئيسي أصلاً تحت الحد الأدنى', function () {
    $item = ibItem('كمبيوتر', minStock: 50);
    ibWarehouse('المخزن الرئيسي', 1);
    ibStock(ibWarehouse('مخزن فرعي', 3), $item, 900);
    $this->actingAs(ibUser());

    $rows = Livewire::test(ItemBalances::class)->set('lowOnly', true)->viewData('items');

    expect($rows->pluck('name')->all())->toBe(['كمبيوتر'])
        ->and((int) $rows->first()->main_quantity)->toBe(0);
});

it('يستبعد الصنف بلا حد أدنى من فلتر الحد الأدنى', function () {
    $withMin = ibItem('له حد', minStock: 50, order: 1);
    $noMin   = ibItem('بلا حد', order: 2);
    $main    = ibWarehouse('المخزن الرئيسي', 1);
    ibStock($main, $withMin, 1);
    ibStock($main, $noMin, 1);
    $this->actingAs(ibUser());

    $rows = Livewire::test(ItemBalances::class)->set('lowOnly', true)->viewData('items');

    expect($rows->pluck('name')->all())->toBe(['له حد']);
});

// ── الفلاتر ──────────────────────────────────────────────

it('يفلتر بحالة الرصيد على الإجمالي لا على مخزنٍ بعينه', function () {
    $has = ibItem('له رصيد', order: 1);
    ibItem('بلا رصيد', order: 2);
    ibStock(ibWarehouse('مخزن فرعي', 3), $has, 3);
    $this->actingAs(ibUser());

    $component = Livewire::test(ItemBalances::class)->set('balanceFilter', 'positive');
    expect($component->viewData('items')->pluck('name')->all())->toBe(['له رصيد']);

    $component->set('balanceFilter', 'zero');
    expect($component->viewData('items')->pluck('name')->all())->toBe(['بلا رصيد']);
});

it('يفلتر بالقسم والوحدة ويهمل معرّفاً تالفاً من الرابط', function () {
    ibItem('كمبيوتر', category: 'الكمبيوتر', order: 1);
    ibItem('دفتر', category: 'الورق', order: 2);
    $this->actingAs(ibUser());

    $category = ItemCategory::where('name', 'الورق')->first();

    $component = Livewire::test(ItemBalances::class)->set('categoryFilter', (string) $category->id);
    expect($component->viewData('items')->pluck('name')->all())->toBe(['دفتر']);

    $component->set('categoryFilter', 'abc');
    expect($component->viewData('items')->count())->toBe(2);
});

it('يفلتر بحالة الصنف بعمودٍ مؤهَّل — العمود على جدول الأقسام أيضاً', function () {
    $active = ibItem('نشط', category: 'الكمبيوتر', order: 1);
    $stopped = ibItem('موقوف', category: 'الكمبيوتر', order: 2);
    $stopped->update(['is_active' => false]);
    $this->actingAs(ibUser());

    $rows = Livewire::test(ItemBalances::class)->set('statusFilter', 'no')->viewData('items');

    expect($rows->pluck('name')->all())->toBe(['موقوف']);
});

it('يبحث برقم الصنف بأرقامٍ إنجليزية كتبها المستخدم', function () {
    ibItem('دفتر ملخّصات', code: '٥٤ ق', order: 1);
    ibItem('كمبيوتر', order: 2);
    $this->actingAs(ibUser());

    $rows = Livewire::test(ItemBalances::class)->set('search', '54')->viewData('items');

    expect($rows->pluck('name')->all())->toBe(['دفتر ملخّصات']);
});

// ── الترتيب ──────────────────────────────────────────────

it('يرتّب بترتيب الدفتر افتراضياً لا أبجدياً', function () {
    // القسم «أ» ترتيبه ٢ والقسم «ب» ترتيبه ١ — فالدفتر يقدّم «ب»
    ItemCategory::create(['name' => 'قسم أ', 'order' => 2]);
    ItemCategory::create(['name' => 'قسم ب', 'order' => 1]);
    Item::create(['name' => 'ألف', 'item_category_id' => ItemCategory::where('name', 'قسم أ')->value('id'), 'order' => 1]);
    Item::create(['name' => 'باء', 'item_category_id' => ItemCategory::where('name', 'قسم ب')->value('id'), 'order' => 1]);
    $this->actingAs(ibUser());

    expect(Livewire::test(ItemBalances::class)->viewData('items')->pluck('name')->all())
        ->toBe(['باء', 'ألف']);
});

it('يرتّب بالإجمالي وبعدد المخازن ويعود لترتيب الدفتر بالضغطة الثالثة', function () {
    $a = ibItem('ألف', order: 1);
    $b = ibItem('باء', order: 2);
    $main = ibWarehouse('المخزن الرئيسي', 1);
    $sub  = ibWarehouse('مخزن فرعي', 3);
    ibStock($main, $a, 10);
    ibStock($main, $b, 90);
    ibStock($sub, $b, 5);
    $this->actingAs(ibUser());

    $component = Livewire::test(ItemBalances::class)->call('sort', 'total');
    expect($component->viewData('items')->pluck('name')->all())->toBe(['ألف', 'باء']);

    $component->call('sort', 'total');
    expect($component->viewData('items')->pluck('name')->all())->toBe(['باء', 'ألف']);

    $component->call('sort', 'total');
    expect($component->viewData('items')->pluck('name')->all())->toBe(['ألف', 'باء'])
        ->and($component->get('sortBy'))->toBe('');

    $component->call('sort', 'warehouses');
    expect(array_map('intval', $component->viewData('items')->pluck('warehouses_count')->all()))->toBe([1, 2]);
});

it('يهمل عمود ترتيبٍ خارج القائمة البيضاء يصل من الرابط', function () {
    ibItem('ألف', order: 1);
    ibItem('باء', order: 2);
    $this->actingAs(ibUser());

    $rows = Livewire::withQueryParams(['sort' => 'min_stock', 'dir' => 'desc'])
        ->test(ItemBalances::class)
        ->viewData('items');

    expect($rows->pluck('name')->all())->toBe(['ألف', 'باء']);
});

// ── الرابط إلى صفحة الصنف ─────────────────────────────────

it('يضع في كل صفٍّ رابطاً إلى صفحة الصنف', function () {
    $item = ibItem('كمبيوتر');
    $this->actingAs(ibUser());

    $this->get(route('warehouses.item-balances'))
        ->assertOk()
        ->assertSee(route('warehouses.items.show', $item));
});
