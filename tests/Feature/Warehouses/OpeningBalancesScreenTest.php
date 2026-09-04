<?php

use App\Livewire\Warehouses\OpeningBalances;
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
 * الأرصدة الافتتاحية — **على صورة بيان القسم الورقي**.
 *
 * ⚠️ كانت صفوفاً بمنسدلة ٣٧٧ صنفاً في كل صفّ، والمفتش يُدخل محتوى مخزنه كله
 *    عند التشغيل. فصارت: قسمٌ واحد ← كل أصنافه صفوفاً جاهزة بخانة عدد.
 *
 * ⚠️ وأهم ما يُحرَس: **الفارغ يُترك ولا يُسجَّل، والصفر المكتوب يُسجَّل**.
 *    الافتتاحي يكتب الرصيد كتابةً، فعدُّ الفارغ صفراً يمحو أرصدة ما لم يبلغه
 *    المُدخِل بعد.
 */
function obUser(array $warehouses = [], bool $all = true): User
{
    foreach (['warehouses.index', 'warehouses.opening'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    Role::findOrCreate('ob-inspector', 'web')->syncPermissions(['warehouses.index', 'warehouses.opening']);

    $user = tap(User::factory()->create(['all_warehouses' => $all]))->assignRole('ob-inspector');
    $user->warehouses()->sync(collect($warehouses)->pluck('id')->all());

    return $user->fresh();
}

function obWarehouse(string $name = 'بنها'): Warehouse
{
    return Warehouse::create([
        'name'              => $name,
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'فرعي'], ['level' => 3, 'order' => 3])->id,
        'is_active'         => true,
    ]);
}

function obCategory(string $name, int $order = 1): ItemCategory
{
    return ItemCategory::create(['name' => $name, 'order' => $order]);
}

function obItem(string $name, ?ItemCategory $category = null, int $order = 1, bool $active = true): Item
{
    return Item::create([
        'name'             => $name,
        'order'            => $order,
        'is_active'        => $active,
        'item_category_id' => $category?->id,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
    ]);
}

// ── صورة البيان ──────────────────────────────────────────

it('لا يعرض أصنافاً قبل اختيار المخزن والقسم', function () {
    $category = obCategory('المستديم');
    obItem('كمبيوتر', $category);
    $warehouse = obWarehouse();

    $this->actingAs(obUser());

    $component = Livewire::test(OpeningBalances::class);
    expect($component->viewData('items')->count())->toBe(0);

    // المخزن وحده لا يكفي — البيان بيانُ قسمٍ بعينه
    $component->set('warehouse_id', $warehouse->id);
    expect($component->viewData('items')->count())->toBe(0);
});

it('يعرض كل أصناف القسم صفوفاً — بما لا رصيد له', function () {
    $category  = obCategory('المستديم');
    $warehouse = obWarehouse();
    obItem('كمبيوتر', $category, 1);
    obItem('كرسي', $category, 2);
    obItem('دولاب', $category, 3);
    // صنف قسمٍ آخر لا يظهر
    obItem('ورق', obCategory('الورق', 2));

    $this->actingAs(obUser());

    $items = Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $category->id)
        ->viewData('items');

    // ⚠️ الثلاثة كلها — والدفتر يطبع أصناف القسم كلها ولو بلا رصيد
    expect($items->pluck('name')->all())->toBe(['كمبيوتر', 'كرسي', 'دولاب']);
});

it('يرتّب الأصناف بترتيب الدفتر داخل القسم', function () {
    $category  = obCategory('المستديم');
    $warehouse = obWarehouse();
    obItem('ياء', $category, 1);
    obItem('ألف', $category, 2);

    $this->actingAs(obUser());

    $items = Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $category->id)
        ->viewData('items');

    // `items.order` قبل الاسم — لا الأبجدي
    expect($items->pluck('name')->all())->toBe(['ياء', 'ألف']);
});

it('يستبعد الصنف الموقوف من البيان', function () {
    $category  = obCategory('المستديم');
    $warehouse = obWarehouse();
    obItem('نشط', $category, 1);
    obItem('موقوف', $category, 2, active: false);

    $this->actingAs(obUser());

    $items = Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $category->id)
        ->viewData('items');

    expect($items->pluck('name')->all())->toBe(['نشط']);
});

it('يعرض الرصيد المسجَّل حالياً بجوار كل صنف', function () {
    $category  = obCategory('المستديم');
    $warehouse = obWarehouse();
    $item      = obItem('كمبيوتر', $category);
    WarehouseStock::create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'quantity' => 12]);

    $this->actingAs(obUser());

    $current = Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $category->id)
        ->viewData('current');

    expect((int) $current[$item->id])->toBe(12);
});

it('يعرض أصناف «بلا قسم» عند اختيارها', function () {
    $warehouse = obWarehouse();
    obItem('بلا قسم', null);
    obItem('له قسم', obCategory('المستديم'));

    $this->actingAs(obUser());

    $items = Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', 'none')
        ->viewData('items');

    expect($items->pluck('name')->all())->toBe(['بلا قسم']);
});

// ── الحفظ: الفارغ والصفر ─────────────────────────────────

it('يسجّل ما أُدخل ويترك الفارغ بلا حركة', function () {
    $category  = obCategory('المستديم');
    $warehouse = obWarehouse();
    $filled    = obItem('كمبيوتر', $category, 1);
    $blank     = obItem('كرسي', $category, 2);

    $this->actingAs(obUser());

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $category->id)
        ->set('quantities.'.$filled->id, 40)
        ->call('save')
        ->assertHasNoErrors();

    expect((int) WarehouseStock::where('item_id', $filled->id)->value('quantity'))->toBe(40)
        // ⚠️ الفارغ لا يُسجَّل — وإلا محا حفظُ قسمٍ أرصدةَ ما لم يبلغه المُدخِل
        ->and(WarehouseStock::where('item_id', $blank->id)->count())->toBe(0)
        ->and(WarehouseMovement::count())->toBe(1);
});

it('يسجّل الصفر المكتوب حركةً — إقراراً بالعدّ', function () {
    $category  = obCategory('المستديم');
    $warehouse = obWarehouse();
    $item      = obItem('كمبيوتر', $category);
    WarehouseStock::create(['warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'quantity' => 9]);

    $this->actingAs(obUser());

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $category->id)
        ->set('quantities.'.$item->id, 0)
        ->call('save');

    expect((int) WarehouseStock::where('item_id', $item->id)->value('quantity'))->toBe(0)
        ->and(WarehouseMovement::where('type', 'opening')->count())->toBe(1);
});

it('يرفض الحفظ بلا إدخال ولا يمسّ رصيداً', function () {
    $category  = obCategory('المستديم');
    $warehouse = obWarehouse();
    obItem('كمبيوتر', $category);

    $this->actingAs(obUser());

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $category->id)
        ->call('save')
        ->assertHasErrors('quantities');

    expect(WarehouseMovement::count())->toBe(0);
});

it('يبقى على الشاشة بعد الحفظ ويمسح المُدخَل', function () {
    $category  = obCategory('المستديم');
    $warehouse = obWarehouse();
    $item      = obItem('كمبيوتر', $category);

    $this->actingAs(obUser());

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $category->id)
        ->set('quantities.'.$item->id, 5)
        ->call('save')
        // ⚠️ الإدخال قسمٌ بعد قسم — فلا يُخرَج إلى اللوحة بعد كل قسم
        ->assertNoRedirect()
        ->assertSet('quantities', [])
        ->assertSet('category_id', (string) $category->id);
});

// ── ما يُدسّ من العميل ───────────────────────────────────

it('لا يسجّل لصنفٍ خارج القسم المعروض ولو دُسّ مفتاحه', function () {
    $shown     = obCategory('المستديم');
    $warehouse = obWarehouse();
    $item      = obItem('كمبيوتر', $shown);
    $outsider  = obItem('ورق', obCategory('الورق', 2));

    $this->actingAs(obUser());

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $shown->id)
        ->set('quantities.'.$item->id, 5)
        ->set('quantities.'.$outsider->id, 999)
        ->call('save');

    expect((int) WarehouseStock::where('item_id', $item->id)->value('quantity'))->toBe(5)
        ->and(WarehouseStock::where('item_id', $outsider->id)->count())->toBe(0);
});

it('يمنع الحفظ على مخزنٍ خارج نطاق المستخدم', function () {
    $category = obCategory('المستديم');
    $mine     = obWarehouse('مخزني');
    $theirs   = obWarehouse('مخزن غيري');
    $item     = obItem('كمبيوتر', $category);

    $this->actingAs(obUser([$mine], all: false));

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $theirs->id)
        ->set('category_id', (string) $category->id)
        ->set('quantities.'.$item->id, 5)
        ->call('save')
        ->assertStatus(403);

    expect(WarehouseStock::count())->toBe(0);
});

it('يبدّل بلا سؤال ما لم يكن ثمّ ما يُفقد', function () {
    $one       = obCategory('المستديم', 1);
    $two       = obCategory('الورق', 2);
    $warehouse = obWarehouse();
    obItem('كمبيوتر', $one);

    $this->actingAs(obUser());

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $one->id)
        ->set('category_id', (string) $two->id)
        ->assertSet('showSwitchWarning', false)
        ->assertSet('category_id', (string) $two->id);
});

it('يسأل قبل أن يمسح، ويُبقي المُدخَل والقسم ريثما يُجاب', function () {
    $one       = obCategory('المستديم', 1);
    $two       = obCategory('الورق', 2);
    $warehouse = obWarehouse();
    $item      = obItem('كمبيوتر', $one);

    $this->actingAs(obUser());

    $component = Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $one->id)
        ->set('quantities.'.$item->id, 40)
        ->set('category_id', (string) $two->id);

    // ⚠️ المسح صحيح وصمتُه غلط: عشرون خانة تضيع بضغطة سهو على شاشةٍ إدخالها طويل
    $component->assertSet('showSwitchWarning', true)
        ->assertSet('quantities.'.$item->id, 40)
        // ويعود القسم إلى سابقه: أرقامٌ مملوءة تحت عناوين أصناف قسمٍ آخر عرضٌ كاذب
        ->assertSet('category_id', (string) $one->id);

    $component->call('cancelSwitch')
        ->assertSet('showSwitchWarning', false)
        ->assertSet('category_id', (string) $one->id)
        ->assertSet('quantities.'.$item->id, 40);
});

it('ينتقل بلا حفظ إن اختار صاحبه ذلك', function () {
    $one       = obCategory('المستديم', 1);
    $two       = obCategory('الورق', 2);
    $warehouse = obWarehouse();
    $item      = obItem('كمبيوتر', $one);

    $this->actingAs(obUser());

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $one->id)
        ->set('quantities.'.$item->id, 40)
        ->set('category_id', (string) $two->id)
        ->call('discardThenSwitch')
        ->assertSet('category_id', (string) $two->id)
        ->assertSet('quantities', [])
        ->assertSet('showSwitchWarning', false);

    // ⚠️ ولم يُسجَّل شيء: الفقد كان باختياره
    expect(WarehouseStock::where('item_id', $item->id)->count())->toBe(0);
});

it('يحفظ ثم ينتقل', function () {
    $one       = obCategory('المستديم', 1);
    $two       = obCategory('الورق', 2);
    $warehouse = obWarehouse();
    $item      = obItem('كمبيوتر', $one);

    $this->actingAs(obUser());

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $one->id)
        ->set('quantities.'.$item->id, 40)
        ->set('category_id', (string) $two->id)
        ->call('saveThenSwitch')
        ->assertSet('category_id', (string) $two->id)
        ->assertSet('quantities', []);

    expect(WarehouseStock::where('warehouse_id', $warehouse->id)->where('item_id', $item->id)->value('quantity'))->toBe(40);
});

it('يحذّر ولو وصلت الكمية والقسم في طلبٍ واحد', function () {
    $one       = obCategory('المستديم', 1);
    $two       = obCategory('الورق', 2);
    $warehouse = obWarehouse();
    $item      = obItem('كمبيوتر', $one);

    $this->actingAs(obUser());

    $component = Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $one->id);

    // ⚠️ هذا ما يقع في المتصفح لا في اختبارٍ يرسل كل تغيير وحده: خانةُ العدد
    //    تُرسَل مع تغيير القسم في الطلب نفسه. ولولا أن Livewire يطبّق التحديثات
    //    كلها قبل استدعاء خطّافات `updated` لَرأى الخطّافُ خاناتٍ فارغة فمسح صامتاً.
    $component->set([
        'quantities.'.$item->id => 40,
        'category_id'           => (string) $two->id,
    ]);

    $component->assertSet('showSwitchWarning', true)
        ->assertSet('category_id', (string) $one->id)
        ->assertSet('quantities.'.$item->id, 40);
});
it('لا ينتقل إن سقط الحفظ — ولا يفقد ما كُتب', function () {
    $one       = obCategory('المستديم', 1);
    $two       = obCategory('الورق', 2);
    $warehouse = obWarehouse();
    $item      = obItem('كمبيوتر', $one);

    $this->actingAs(obUser());

    // ⚠️ «احفظ ثم انتقل» على رقمٍ مرفوض: الانتقال بعده يمسح ما لم يُحفظ
    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $one->id)
        ->set('quantities.'.$item->id, -5)
        ->set('category_id', (string) $two->id)
        ->call('saveThenSwitch')
        ->assertHasErrors()
        ->assertSet('category_id', (string) $one->id)
        ->assertSet('quantities.'.$item->id, -5)
        ->assertSet('showSwitchWarning', true);
});
it('يسأل عند تبديل المخزن أيضاً', function () {
    $one       = obCategory('المستديم', 1);
    $warehouse = obWarehouse();
    $other     = obWarehouse('مخزن آخر');
    $item      = obItem('كمبيوتر', $one);

    $this->actingAs(obUser());

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $one->id)
        ->set('quantities.'.$item->id, 40)
        ->set('warehouse_id', $other->id)
        ->assertSet('showSwitchWarning', true)
        ->assertSet('warehouse_id', $warehouse->id)
        ->call('discardThenSwitch')
        ->assertSet('warehouse_id', $other->id)
        ->assertSet('quantities', []);
});

it('يمنع الكمية السالبة', function () {
    $category  = obCategory('المستديم');
    $warehouse = obWarehouse();
    $item      = obItem('كمبيوتر', $category);

    $this->actingAs(obUser());

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $warehouse->id)
        ->set('category_id', (string) $category->id)
        ->set('quantities.'.$item->id, -5)
        ->call('save')
        ->assertHasErrors('quantities.'.$item->id);

    expect(WarehouseMovement::count())->toBe(0);
});
