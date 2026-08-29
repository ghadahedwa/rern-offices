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

    // ⚠️ `all_warehouses` = **بلا حدّ**: هذه الاختبارات تفحص منطق الشاشة لا
    //    النطاق، ومستخدمٌ بلا مخزن مرتبط يرى صفراً بحقّ (الفراغ = لا شيء).
    //    اختبارات النطاق نفسها في WarehouseScopeTest.
    return tap(User::factory()->create(['all_warehouses' => true]))->assignRole($role);
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

// ── فلاتر تاب الأرصدة ────────────────────────────────────

function ptItemIn(string $name, string $categoryName, ?int $minStock = null): Item
{
    return Item::create([
        'name'             => $name,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
        'item_category_id' => \App\Models\ItemCategory::firstOrCreate(['name' => $categoryName], ['order' => 1])->id,
        'min_stock'        => $minStock,
    ]);
}

it('يفلتر أرصدة المخزن بقسم الصنف', function () {
    $w = ptWarehouse();
    ptStock($w, ptItemIn('حبر توشيبا', 'مخزن التصوير'), 5);
    ptStock($w, ptItemIn('بونطة شنيور', 'مخزن المستديم'), 9);
    $this->actingAs(ptUser());

    $photo = \App\Models\ItemCategory::where('name', 'مخزن التصوير')->first();

    $rows = Livewire::test(Show::class, ['warehouse' => $w])
        ->set('categoryFilter', (string) $photo->id)
        ->viewData('stocks');

    expect($rows->pluck('quantity')->all())->toBe([5]);
});

it('يفصل الرصيد الصفر عن الرصيد الموجب', function () {
    $w = ptWarehouse();
    ptStock($w, ptItem('حبر'), 0);
    ptStock($w, ptItem('ورق'), 12);
    $this->actingAs(ptUser());

    $component = Livewire::test(Show::class, ['warehouse' => $w]);

    expect($component->set('balanceFilter', 'zero')->viewData('stocks')->pluck('quantity')->all())->toBe([0])
        ->and($component->set('balanceFilter', 'positive')->viewData('stocks')->pluck('quantity')->all())->toBe([12]);
});

it('يعرض ما بلغ حدّه الأدنى وحده في المخزن الرئيسي', function () {
    $w = ptWarehouse();
    ptStock($w, ptItemIn('حبر', 'التصوير', 10), 3);   // تحت الحد
    ptStock($w, ptItemIn('ورق', 'التصوير', 10), 40);  // فوقه
    ptStock($w, ptItemIn('دبابيس', 'التصوير', null), 1); // بلا حدّ — خارج الفلتر
    $this->actingAs(ptUser());

    $rows = Livewire::test(Show::class, ['warehouse' => $w])
        ->set('lowOnly', true)
        ->viewData('stocks');

    expect($rows->pluck('quantity')->all())->toBe([3]);
});

it('لا يطبّق فلتر الحد الأدنى على مخزن غير رئيسي ولو من الرابط', function () {
    // ⚠️ الحد الأدنى قاعدةٌ على الرئيسي وحده — والفلتر يصل من الرابط لا من الخانة فقط
    $branch = ptWarehouse('فرعي', 3);
    ptStock($branch, ptItemIn('حبر', 'التصوير', 10), 3);
    ptStock($branch, ptItemIn('ورق', 'التصوير', 10), 40);
    $this->actingAs(ptUser());

    $rows = Livewire::withQueryParams(['low' => '1'])
        ->test(Show::class, ['warehouse' => $branch])
        ->viewData('stocks');

    expect($rows->total())->toBe(2);
});

it('يحصر منسدلة الأقسام في أقسام أصناف هذا المخزن', function () {
    // قسمٌ بلا صنف في هذا المخزن يُوهم أن الشاشة فارغة لخلل لا لغياب الصنف
    $w = ptWarehouse();
    ptStock($w, ptItemIn('حبر توشيبا', 'مخزن التصوير'), 5);
    \App\Models\ItemCategory::firstOrCreate(['name' => 'مخزن السيارات'], ['order' => 9]);
    $this->actingAs(ptUser());

    $names = Livewire::test(Show::class, ['warehouse' => $w])->viewData('categories')->pluck('name')->all();

    expect($names)->toBe(['مخزن التصوير']);
});

it('يعرض الأصناف بلا قسم عند اختيار «بلا قسم» ويتجاهل قيمة تالفة', function () {
    $w = ptWarehouse();
    ptStock($w, ptItemIn('حبر توشيبا', 'مخزن التصوير'), 5);
    ptStock($w, ptItem('صنف بلا قسم'), 9);
    $this->actingAs(ptUser());

    $component = Livewire::test(Show::class, ['warehouse' => $w]);

    expect($component->set('categoryFilter', 'none')->viewData('stocks')->pluck('quantity')->all())->toBe([9])
        // قيمة نصّية ليست 'none' تصل من الرابط — تُهمَل بدل إفراغ الشاشة
        ->and($component->set('categoryFilter', 'التصوير')->viewData('stocks')->total())->toBe(2);
});

it('يفلتر بالوحدة ويتجاهل قيمة وحدة تالفة', function () {
    $w   = ptWarehouse();
    $box = ItemUnit::firstOrCreate(['name' => 'دفتر']);
    ptStock($w, ptItem('حبر'), 5);
    ptStock($w, Item::create(['name' => 'دفتر يومية', 'item_unit_id' => $box->id]), 9);
    $this->actingAs(ptUser());

    $component = Livewire::test(Show::class, ['warehouse' => $w]);

    expect($component->set('unitFilter', (string) $box->id)->viewData('stocks')->pluck('quantity')->all())->toBe([9])
        ->and($component->set('unitFilter', 'دفتر')->viewData('stocks')->total())->toBe(2);
});

// ── ترتيب الدفتر داخل البروفايل ──────────────────────────

function ptOrderedItem(string $name, string $categoryName, int $categoryOrder): Item
{
    return Item::create([
        'name'             => $name,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
        'item_category_id' => \App\Models\ItemCategory::firstOrCreate(['name' => $categoryName], ['order' => $categoryOrder])->id,
    ]);
}

it('يرتّب أرصدة البروفايل بترتيب الدفتر لا أبجدياً', function () {
    $w = ptWarehouse();
    ptStock($w, ptOrderedItem('ياء', 'مخزن التصوير', 1), 1);
    ptStock($w, ptOrderedItem('ألف', 'مخزن المستديم', 2), 2);
    $this->actingAs(ptUser());

    expect(Livewire::test(Show::class, ['warehouse' => $w])->viewData('stocks')->pluck('item.name')->all())
        ->toBe(['ياء', 'ألف']);
});

it('يرتّب منسدلة أصناف تاب الحركات بترتيب الدفتر', function () {
    // ⚠️ كانت تقرأ علاقة stocks مباشرةً — أي بترتيب إدراج الصفوف لا بترتيبٍ معلن
    $w = ptWarehouse();
    ptStock($w, ptOrderedItem('ألف', 'مخزن المستديم', 2), 1);
    ptStock($w, ptOrderedItem('ياء', 'مخزن التصوير', 1), 2);
    $this->actingAs(ptUser());

    $names = Livewire::withQueryParams(['tab' => 'movements'])
        ->test(Show::class, ['warehouse' => $w])
        ->viewData('movementItems')->pluck('name')->all();

    expect($names)->toBe(['ياء', 'ألف']);
});
