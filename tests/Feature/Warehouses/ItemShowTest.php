<?php

use App\Livewire\Warehouses\Items\Show;
use App\Models\Governorate;
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

/**
 * صفحة الصنف — «أين هذا الصنف؟»: رصيده في المخازن كلها وسجل حركاته.
 *
 * الحارس الأهم هنا: **تاب الأرصدة يعرض كل مخزن**، ومخزنٌ بلا رصيدٍ للصنف
 * صفٌّ بشرطة لا صفٌّ ساقط — وهو نصف الجواب الذي تسأل عنه الشاشة.
 */
function isUser(array $permissions = ['warehouses.index'], string $role = 'is-viewer'): User
{
    foreach ($permissions as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $roleModel = Role::findOrCreate($role, 'web');
    $roleModel->syncPermissions($permissions);

    return tap(User::factory()->create())->assignRole($roleModel);
}

function isType(string $name, int $level): WarehouseType
{
    return WarehouseType::firstOrCreate(['name' => $name], ['level' => $level, 'order' => $level]);
}

function isWarehouse(string $name, int $level = 1, ?Governorate $gov = null): Warehouse
{
    return Warehouse::create([
        'name'              => $name,
        'warehouse_type_id' => isType('نوع '.$level, $level)->id,
        'governorate_id'    => $gov?->id,
    ]);
}

function isItem(string $name = 'كمبيوتر', ?int $minStock = null): Item
{
    return Item::create([
        'name'             => $name,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
        'item_category_id' => ItemCategory::firstOrCreate(['name' => 'الكمبيوتر'], ['order' => 1])->id,
        'min_stock'        => $minStock,
    ]);
}

function isStock(Warehouse $w, Item $i, int $qty): WarehouseStock
{
    return WarehouseStock::create(['warehouse_id' => $w->id, 'item_id' => $i->id, 'quantity' => $qty]);
}

function isMovementAt(Warehouse $w, Item $i, string $utcTimestamp, string $type = 'opening', int $qty = 5): WarehouseMovement
{
    $movement = WarehouseMovement::create([
        'warehouse_id'   => $w->id,
        'item_id'        => $i->id,
        'type'           => $type,
        'quantity'       => $qty,
        'balance_before' => 0,
        'balance_after'  => $qty,
    ]);

    WarehouseMovement::where('id', $movement->id)->update(['created_at' => $utcTimestamp]);

    return $movement->refresh();
}

// ── الحارس ───────────────────────────────────────────────

it('يمنع مَن لا صلاحية مخازن له', function () {
    $item = isItem();
    $this->actingAs(isUser(['offices.index'], 'is-outsider'));

    Livewire::test(Show::class, ['item' => $item])->assertStatus(403);
});

it('يفتح الصفحة من رابطها كاملةً بالتخطيط', function () {
    $item = isItem();
    isWarehouse('المخزن الرئيسي', 1);
    isStock(Warehouse::first(), $item, 40);
    $this->actingAs(isUser(['warehouses.index'], 'is-route'));

    $this->get(route('warehouses.items.show', $item))
        ->assertOk()
        ->assertSee($item->name)
        ->assertSee('المخزن الرئيسي');
});

it('يوجّه زر الرجوع إلى شاشةٍ يملكها القارئ لا إلى ٤٠٣', function () {
    $item = isItem();
    isWarehouse('المخزن الرئيسي', 1);

    // صاحب الإعدادات وحده لا يفتح شاشة الأرصدة، فمدخله شاشة الأصناف
    $this->actingAs(isUser(['warehouses.settings'], 'is-back-settings'));
    expect(Livewire::test(Show::class, ['item' => $item])->instance()->backRoute())
        ->toBe(route('items.index'));

    // وصاحب التشغيل مدخله شاشة الأرصدة
    $this->actingAs(isUser(['warehouses.index'], 'is-back-op'));
    expect(Livewire::test(Show::class, ['item' => $item])->instance()->backRoute())
        ->toBe(route('warehouses.stock'));
});

it('يحجب الرابط عمّن لا صلاحية مخازن له — قبل بلوغ mount', function () {
    $item = isItem();
    $this->actingAs(isUser(['offices.index'], 'is-route-outsider'));

    $this->get(route('warehouses.items.show', $item))->assertForbidden();
});

it('يفتح لصاحب warehouses.index وحدها — مدخلها شاشة الأرصدة', function () {
    $item = isItem();
    $this->actingAs(isUser(['warehouses.index'], 'is-op'));

    Livewire::test(Show::class, ['item' => $item])
        ->assertStatus(200)
        ->assertSet('canManage', false);
});

it('يفتح لصاحب warehouses.settings وحدها — مدخلها شاشة الأصناف', function () {
    $item = isItem();
    $this->actingAs(isUser(['warehouses.settings'], 'is-settings'));

    Livewire::test(Show::class, ['item' => $item])
        ->assertStatus(200)
        ->assertSet('canManage', true);
});

// ── تاب الأرصدة: المخازن كلها ─────────────────────────────

it('يعرض المخازن التي لا رصيد للصنف فيها لا التي له فيها رصيد وحدها', function () {
    $item  = isItem();
    $main  = isWarehouse('المخزن الرئيسي', 1);
    $empty = isWarehouse('مخزن بلا رصيد', 3, Governorate::create(['name' => 'قنا', 'order' => 1]));
    isStock($main, $item, 40);
    $this->actingAs(isUser());

    $rows = Livewire::test(Show::class, ['item' => $item])->viewData('balances');

    expect($rows->pluck('name')->all())->toBe(['المخزن الرئيسي', 'مخزن بلا رصيد'])
        ->and(array_map('intval', $rows->pluck('stock_quantity')->all()))->toBe([40, 0]);
});

it('لا يخلط رصيد صنفٍ آخر في العمود', function () {
    $item  = isItem('كمبيوتر');
    $other = isItem('طابعة');
    $main  = isWarehouse('المخزن الرئيسي', 1);
    isStock($main, $item, 40);
    isStock($main, $other, 999);
    $this->actingAs(isUser());

    $rows = Livewire::test(Show::class, ['item' => $item])->viewData('balances');

    expect(array_map('intval', $rows->pluck('stock_quantity')->all()))->toBe([40]);
});

it('يرتّب المخازن افتراضياً بالمستوى ثم المحافظة لا أبجدياً', function () {
    $item = isItem();
    $qena  = Governorate::create(['name' => 'قنا', 'order' => 2]);
    $aswan = Governorate::create(['name' => 'أسوان', 'order' => 1]);
    // الأبجدي يضع «أسيوط» أولاً، وقاعدة العرض تضع الرئيسي أولاً
    isWarehouse('أسيوط الفرعي', 3, $qena);
    isWarehouse('ياسين الرئيسي', 1);
    isWarehouse('باء الفرعي', 3, $aswan);
    $this->actingAs(isUser());

    $rows = Livewire::test(Show::class, ['item' => $item])->viewData('balances');

    expect($rows->pluck('name')->all())->toBe(['ياسين الرئيسي', 'باء الفرعي', 'أسيوط الفرعي']);
});

// ── فلاتر تاب الأرصدة ─────────────────────────────────────

it('فلتر «صفر» يشمل المخزن الذي لا صفَّ رصيد له أصلاً', function () {
    $item  = isItem();
    $main  = isWarehouse('المخزن الرئيسي', 1);
    isWarehouse('مخزن بلا صفّ', 3);
    $zero = isWarehouse('مخزن صفره مسجَّل', 3);
    isStock($main, $item, 40);
    isStock($zero, $item, 0);
    $this->actingAs(isUser());

    $rows = Livewire::test(Show::class, ['item' => $item])
        ->set('balanceFilter', 'zero')
        ->viewData('balances');

    expect($rows->pluck('name')->all())->toBe(['مخزن بلا صفّ', 'مخزن صفره مسجَّل']);
});

it('فلتر «أكبر من صفر» يقصر الجدول على ما فيه رصيد', function () {
    $item = isItem();
    $main = isWarehouse('المخزن الرئيسي', 1);
    isWarehouse('مخزن فارغ', 3);
    isStock($main, $item, 40);
    $this->actingAs(isUser());

    $rows = Livewire::test(Show::class, ['item' => $item])
        ->set('balanceFilter', 'positive')
        ->viewData('balances');

    expect($rows->pluck('name')->all())->toBe(['المخزن الرئيسي']);
});

it('يفلتر بنوع المخزن، ويهمل معرّف نوعٍ تالفاً يصل من الرابط', function () {
    $item = isItem();
    isWarehouse('المخزن الرئيسي', 1);
    isWarehouse('مخزن فرعي', 3);
    $this->actingAs(isUser());

    $component = Livewire::test(Show::class, ['item' => $item])
        ->set('warehouseTypeFilter', (string) isType('نوع 3', 3)->id);
    expect($component->viewData('balances')->pluck('name')->all())->toBe(['مخزن فرعي']);

    // قيمة غير رقمية تُهمَل ولا تُخرج شاشة فارغة بلا سبب ظاهر
    $component->set('warehouseTypeFilter', 'abc');
    expect($component->viewData('balances')->count())->toBe(2);
});

it('يبحث في اسم المخزن بحثاً عربياً مطبَّعاً', function () {
    $item = isItem();
    isWarehouse('مخزن أسيوط', 1);
    isWarehouse('مخزن سوهاج', 3);
    $this->actingAs(isUser());

    $rows = Livewire::test(Show::class, ['item' => $item])
        ->set('search', 'اسيوط')
        ->viewData('balances');

    expect($rows->pluck('name')->all())->toBe(['مخزن أسيوط']);
});

// ── الترتيب ──────────────────────────────────────────────

it('يرتّب بالكمية ويعود للافتراضي بالضغطة الثالثة', function () {
    $item = isItem();
    $low  = isWarehouse('ألف الرئيسي', 1);
    $high = isWarehouse('باء الفرعي', 3);
    isStock($low, $item, 10);
    isStock($high, $item, 90);
    $this->actingAs(isUser());

    $component = Livewire::test(Show::class, ['item' => $item])->call('sort', 'quantity');
    expect(array_map('intval', $component->viewData('balances')->pluck('stock_quantity')->all()))->toBe([10, 90]);

    $component->call('sort', 'quantity');
    expect(array_map('intval', $component->viewData('balances')->pluck('stock_quantity')->all()))->toBe([90, 10]);

    // الضغطة الثالثة ترجع لقاعدة العرض (المستوى أولاً) لا لترتيب الكمية
    $component->call('sort', 'quantity');
    expect($component->viewData('balances')->pluck('name')->all())->toBe(['ألف الرئيسي', 'باء الفرعي']);
});

it('يهمل عمود ترتيبٍ خارج القائمة البيضاء يصل من الرابط', function () {
    $item = isItem();
    isWarehouse('ألف الرئيسي', 1);
    isWarehouse('باء الفرعي', 3);
    $this->actingAs(isUser());

    $rows = Livewire::withQueryParams(['sort' => 'letterhead', 'dir' => 'desc'])
        ->test(Show::class, ['item' => $item])
        ->viewData('balances');

    expect($rows->pluck('name')->all())->toBe(['ألف الرئيسي', 'باء الفرعي']);
});

// ── بطاقات الرأس ─────────────────────────────────────────

it('يحسب البطاقات على كل المخازن لا على ما بقي بعد فلتر الجدول', function () {
    $item = isItem();
    $main = isWarehouse('المخزن الرئيسي', 1);
    $sub  = isWarehouse('مخزن فرعي', 3);
    isWarehouse('مخزن فارغ', 3);
    isStock($main, $item, 40);
    isStock($sub, $item, 60);
    $this->actingAs(isUser());

    $component = Livewire::test(Show::class, ['item' => $item])->set('balanceFilter', 'positive');
    $summary = $component->viewData('summary');

    expect($component->viewData('balances')->count())->toBe(2)
        ->and($summary['total'])->toBe(100)
        ->and($summary['withStock'])->toBe(2)
        ->and($summary['warehousesAll'])->toBe(3)
        ->and($summary['mainQuantity'])->toBe(40);
});

it('يقرأ رصيد الرئيسي صفراً حين لا صفَّ للصنف فيه', function () {
    $item = isItem();
    isWarehouse('المخزن الرئيسي', 1);
    $sub = isWarehouse('مخزن فرعي', 3);
    isStock($sub, $item, 60);
    $this->actingAs(isUser());

    $summary = Livewire::test(Show::class, ['item' => $item])->viewData('summary');

    expect($summary['mainQuantity'])->toBe(0)
        ->and($summary['total'])->toBe(60);
});

it('يبلّغ بغياب المخزن الرئيسي بدل أن يعرض صفراً موهماً', function () {
    $item = isItem();
    isWarehouse('مخزن فرعي', 3);
    $this->actingAs(isUser());

    $summary = Livewire::test(Show::class, ['item' => $item])->viewData('summary');

    expect($summary['mainWarehouse'])->toBeNull()
        ->and($summary['mainQuantity'])->toBeNull()
        ->and($summary['mainBelowMin'])->toBeFalse();
});

it('يقيس الحد الأدنى على المخزن الرئيسي وحده لا على الفرعي', function () {
    $item = isItem('كمبيوتر', minStock: 50);
    $main = isWarehouse('المخزن الرئيسي', 1);
    $sub  = isWarehouse('مخزن فرعي', 3);
    isStock($main, $item, 90);   // فوق الحد
    isStock($sub, $item, 1);     // تحت الحد، لكنه فرعي فلا تنبيه
    $this->actingAs(isUser());

    expect(Livewire::test(Show::class, ['item' => $item])->viewData('summary')['mainBelowMin'])->toBeFalse();

    WarehouseStock::where('warehouse_id', $main->id)->update(['quantity' => 50]);

    expect(Livewire::test(Show::class, ['item' => $item])->viewData('summary')['mainBelowMin'])->toBeTrue();
});

// ── تاب الحركات ──────────────────────────────────────────

it('يعرض حركات هذا الصنف وحده', function () {
    $item  = isItem('كمبيوتر');
    $other = isItem('طابعة');
    $w     = isWarehouse('المخزن الرئيسي', 1);
    isMovementAt($w, $item, '2026-08-20 10:00:00', qty: 7);
    isMovementAt($w, $other, '2026-08-20 11:00:00', qty: 99);
    $this->actingAs(isUser());

    $rows = Livewire::test(Show::class, ['item' => $item])
        ->call('setTab', 'movements')
        ->viewData('movements');

    expect($rows->pluck('quantity')->all())->toBe([7]);
});

it('يفلتر الحركات بالمخزن وبنوع الحركة', function () {
    $item = isItem();
    $main = isWarehouse('المخزن الرئيسي', 1);
    $sub  = isWarehouse('مخزن فرعي', 3);
    isMovementAt($main, $item, '2026-08-20 10:00:00', 'opening', 1);
    isMovementAt($main, $item, '2026-08-21 10:00:00', 'incoming', 2);
    isMovementAt($sub, $item, '2026-08-22 10:00:00', 'transfer_in', 3);
    $this->actingAs(isUser());

    $component = Livewire::test(Show::class, ['item' => $item])->call('setTab', 'movements');

    $component->set('warehouseFilter', (string) $sub->id);
    expect($component->viewData('movements')->pluck('quantity')->all())->toBe([3]);

    $component->set('warehouseFilter', '')->set('typeFilter', 'incoming');
    expect($component->viewData('movements')->pluck('quantity')->all())->toBe([2]);

    // نوع غير معروف يصل من الرابط يُهمَل ولا يُفرَّغ الجدول
    $component->set('typeFilter', 'destroyed');
    expect($component->viewData('movements')->count())->toBe(3);
});

it('يُدخل حركة الواحدة فجراً بالقاهرة في فلتر يومها لا في اليوم السابق', function () {
    $item = isItem();
    $w    = isWarehouse('المخزن الرئيسي', 1);
    // ٢٣:٠٠ UTC = الواحدة فجر اليوم التالي بالقاهرة (توقيت صيفي +3)
    isMovementAt($w, $item, '2026-08-20 23:00:00', qty: 4);
    $this->actingAs(isUser());

    $component = Livewire::test(Show::class, ['item' => $item])->call('setTab', 'movements');

    $component->set('dateFrom', '2026-08-21')->set('dateTo', '2026-08-21');
    expect($component->viewData('movements')->pluck('quantity')->all())->toBe([4]);

    $component->set('dateFrom', '2026-08-20')->set('dateTo', '2026-08-20');
    expect($component->viewData('movements')->count())->toBe(0);
});

it('يقصر منسدلة المخزن على مخازن تحرّك فيها هذا الصنف فعلاً', function () {
    $item  = isItem('كمبيوتر');
    $other = isItem('طابعة');
    $moved = isWarehouse('مخزن تحرّك فيه', 1);
    $idle  = isWarehouse('مخزن لم يتحرّك', 3);
    isMovementAt($moved, $item, '2026-08-20 10:00:00');
    isMovementAt($idle, $other, '2026-08-20 10:00:00');
    $this->actingAs(isUser());

    $options = Livewire::test(Show::class, ['item' => $item])
        ->call('setTab', 'movements')
        ->viewData('movementWarehouses');

    expect($options->pluck('name')->all())->toBe(['مخزن تحرّك فيه']);
});

// ── التاب والمُرقِّم والفلاتر بينهما ───────────────────────

it('يفتح التاب المطلوب من الرابط ويسقط التالف إلى الأرصدة', function () {
    $item = isItem();
    $this->actingAs(isUser());

    Livewire::withQueryParams(['tab' => 'movements'])
        ->test(Show::class, ['item' => $item])
        ->assertSet('tab', 'movements');

    Livewire::withQueryParams(['tab' => 'nonsense'])
        ->test(Show::class, ['item' => $item])
        ->assertSet('tab', 'balances');
});

it('يمسح الفلاتر والترتيب عند تغيير التاب', function () {
    $item = isItem();
    isWarehouse('المخزن الرئيسي', 1);
    $this->actingAs(isUser());

    Livewire::test(Show::class, ['item' => $item])
        ->set('search', 'أسيوط')
        ->set('balanceFilter', 'zero')
        ->call('sort', 'quantity')
        ->call('setTab', 'movements')
        ->assertSet('search', '')
        ->assertSet('balanceFilter', '')
        ->assertSet('sortBy', '')
        ->assertSet('tab', 'movements');
});

it('يصفّر مُرقِّم التاب المعروض لا مُرقِّماً لا يستعمله أحد', function () {
    $item = isItem();
    $this->actingAs(isUser());

    $component = Livewire::test(Show::class, ['item' => $item])->call('setPage', 3, 'balPage');
    expect($component->viewData('balances')->currentPage())->toBe(3);

    $component->set('search', 'مخزن');
    expect($component->viewData('balances')->currentPage())->toBe(1);
});
