<?php

use App\Livewire\Warehouses\Incoming\Create as IncomingCreate;
use App\Livewire\Warehouses\Issues\Create as IssueCreate;
use App\Livewire\Warehouses\Transfers\Create as TransferCreate;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * حصر منسدلات الصنف بقسم في فورمات المستندات (وارد · نقل · صرف).
 *
 * ⚠️ الحارس الأهم: **الصنف المختار في صفٍّ يبقى معروضاً مهما ضاق الحصر**.
 *    المنتقي يُبقيه في صفّه، لكنه لا يستطيع ذلك إن أُخرج من القائمة أصلاً —
 *    فيختفي من منسدلته ويبدو الصف فارغاً وقد كان ممتلئاً.
 */
function icfUser(): User
{
    $permissions = ['warehouses.index', 'warehouses.issue', 'warehouses.incoming', 'warehouses.transfer'];

    foreach ($permissions as $name) {
        Permission::findOrCreate($name, 'web');
    }

    Role::findOrCreate('icf-user', 'web')->syncPermissions($permissions);

    return tap(User::factory()->create(['all_warehouses' => true]))->assignRole('icf-user');
}

/** شاشة الوارد تُخفي الفورم كله بلا مخزن رئيسي — فيلزم وجوده لاختبارها. */
function icfMainWarehouse(): \App\Models\Warehouse
{
    return \App\Models\Warehouse::create([
        'name'              => 'المخزن الرئيسي',
        'warehouse_type_id' => \App\Models\WarehouseType::firstOrCreate(['name' => 'رئيسي'], ['level' => 1, 'order' => 1])->id,
        'is_active'         => true,
    ]);
}

function icfCategory(string $name, int $order = 1): ItemCategory
{
    return ItemCategory::create(['name' => $name, 'order' => $order]);
}

function icfItem(string $name, ?ItemCategory $category = null, int $order = 1): Item
{
    return Item::create([
        'name'             => $name,
        'order'            => $order,
        'item_category_id' => $category?->id,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
    ]);
}

// ── الحصر ────────────────────────────────────────────────

it('يعرض الأصناف كلها بلا حصر', function () {
    icfItem('كمبيوتر', icfCategory('الكمبيوتر', 1));
    icfItem('ورق', icfCategory('الورق', 2));

    $this->actingAs(icfUser());

    expect(Livewire::test(IssueCreate::class)->viewData('items')->count())->toBe(2);
});

it('يحصر المنسدلة بالقسم المختار', function () {
    $computers = icfCategory('الكمبيوتر', 1);
    icfItem('كمبيوتر', $computers);
    icfItem('ورق', icfCategory('الورق', 2));

    $this->actingAs(icfUser());

    $items = Livewire::test(IssueCreate::class)
        ->set('itemCategoryId', (string) $computers->id)
        ->viewData('items');

    expect($items->pluck('name')->all())->toBe(['كمبيوتر']);
});

it('يحصر بـ«بلا قسم» على الأصناف التي لا قسم لها', function () {
    icfItem('كمبيوتر', icfCategory('الكمبيوتر', 1));
    icfItem('صنف يتيم');

    $this->actingAs(icfUser());

    $items = Livewire::test(IssueCreate::class)
        ->set('itemCategoryId', 'none')
        ->viewData('items');

    expect($items->pluck('name')->all())->toBe(['صنف يتيم']);
});

it('يهمل معرّف قسمٍ تالفاً يصل من العميل ولا يُفرّغ المنسدلة', function () {
    icfItem('كمبيوتر', icfCategory('الكمبيوتر', 1));
    icfItem('ورق', icfCategory('الورق', 2));

    $this->actingAs(icfUser());

    $items = Livewire::test(IssueCreate::class)
        ->set('itemCategoryId', 'abc')
        ->viewData('items');

    expect($items->count())->toBe(2);
});

// ── الصنف المختار يبقى ───────────────────────────────────

it('يُبقي الصنف المختار في صفّه ولو كان خارج القسم المحصور', function () {
    $computers = icfCategory('الكمبيوتر', 1);
    $paper     = icfCategory('الورق', 2);
    $chosen    = icfItem('ورق A4', $paper);
    icfItem('كمبيوتر', $computers);

    $this->actingAs(icfUser());

    $component = Livewire::test(IssueCreate::class)
        ->set('lines.0.item_id', $chosen->id)
        ->set('itemCategoryId', (string) $computers->id);

    // ⚠️ وإلا اختفى من منسدلته فبدا الصف فارغاً وقد كان ممتلئاً
    expect($component->viewData('items')->pluck('name')->all())
        ->toContain('ورق A4')
        ->toContain('كمبيوتر');

    expect($component->html())->toContain('value="'.$chosen->id.'"');
});

it('لا يُبقي إلا المختار فعلاً من خارج القسم', function () {
    $computers = icfCategory('الكمبيوتر', 1);
    $paper     = icfCategory('الورق', 2);
    $chosen    = icfItem('ورق A4', $paper, 1);
    icfItem('ورق مقوّى', $paper, 2);
    icfItem('كمبيوتر', $computers);

    $this->actingAs(icfUser());

    $items = Livewire::test(IssueCreate::class)
        ->set('lines.0.item_id', $chosen->id)
        ->set('itemCategoryId', (string) $computers->id)
        ->viewData('items');

    // «ورق مقوّى» من قسمٍ محصورٍ عنه ولم يُختر — فلا يُضمّ
    expect($items->pluck('name')->all())->not->toContain('ورق مقوّى');
});

it('لا يمسّ الحصرُ ما أُدخل في الصفوف', function () {
    $computers = icfCategory('الكمبيوتر', 1);
    $chosen    = icfItem('ورق A4', icfCategory('الورق', 2));

    $this->actingAs(icfUser());

    Livewire::test(IssueCreate::class)
        ->set('lines.0.item_id', $chosen->id)
        ->set('lines.0.quantity', 7)
        ->set('itemCategoryId', (string) $computers->id)
        ->assertSet('lines.0.item_id', $chosen->id)
        ->assertSet('lines.0.quantity', 7);
});

// ── الشاشات الثلاث ───────────────────────────────────────

it('يظهر الحصر في الشاشات الثلاث ويعمل فيها', function () {
    icfMainWarehouse();
    $computers = icfCategory('الكمبيوتر', 1);
    icfItem('كمبيوتر', $computers);
    icfItem('ورق', icfCategory('الورق', 2));

    $this->actingAs(icfUser());

    foreach ([IssueCreate::class, IncomingCreate::class, TransferCreate::class] as $screen) {
        $component = Livewire::test($screen);

        expect($component->html())->toContain(__('home.wh_items_category_filter'));

        expect($component->set('itemCategoryId', (string) $computers->id)->viewData('items')->pluck('name')->all())
            ->toBe(['كمبيوتر']);
    }
});

it('يستبعد الصنف الموقوف من المنسدلة ولو حُصر قسمه', function () {
    $computers = icfCategory('الكمبيوتر', 1);
    icfItem('نشط', $computers, 1);
    tap(icfItem('موقوف', $computers, 2))->update(['is_active' => false]);

    $this->actingAs(icfUser());

    $items = Livewire::test(IssueCreate::class)
        ->set('itemCategoryId', (string) $computers->id)
        ->viewData('items');

    expect($items->pluck('name')->all())->toBe(['نشط']);
});
