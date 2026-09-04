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
 * القسم **داخل صفّ المستند** (وارد · نقل · صرف).
 *
 * كان حصراً واحداً فوق الصفوف كلها، فكانت حالةٌ مشتركة تتبدّل تحت صفوفٍ
 * ممتلئة: تُبدَّل خيارات كل منسدلة، فيفقد الـ«select» قيمته المعروضة ويبدو
 * الصفّ فارغاً وهو ممتلئ — **ويمضي الحفظ بصنفٍ لا يراه صاحبه**.
 *
 * فصار لكل صفٍّ قسمُه: قسم ← صنف ← عدد. ولا شيء يتغيّر تحت صفٍّ آخر.
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

/** أسماء أصناف الصفّ رقم $i. */
function icfLineItems($component, int $i = 0): array
{
    return $component->viewData('lineItems')[$i]->pluck('name')->all();
}

// ── الحصر داخل الصفّ ─────────────────────────────────────

it('يعرض أصناف الصفّ كلها ما لم يُختر له قسم', function () {
    icfItem('كمبيوتر', icfCategory('الكمبيوتر', 1));
    icfItem('ورق', icfCategory('الورق', 2));

    $this->actingAs(icfUser());

    expect(icfLineItems(Livewire::test(IssueCreate::class)))->toHaveCount(2);
});

it('يحصر منسدلة الصفّ بقسمه المختار', function () {
    $computers = icfCategory('الكمبيوتر', 1);
    icfItem('كمبيوتر', $computers);
    icfItem('ورق', icfCategory('الورق', 2));

    $this->actingAs(icfUser());

    $component = Livewire::test(IssueCreate::class)->set('lines.0.category_id', (string) $computers->id);

    expect(icfLineItems($component))->toBe(['كمبيوتر']);
});

it('يحصر بـ«بلا قسم» على الأصناف التي لا قسم لها', function () {
    icfItem('كمبيوتر', icfCategory('الكمبيوتر', 1));
    icfItem('صنف يتيم');

    $this->actingAs(icfUser());

    $component = Livewire::test(IssueCreate::class)->set('lines.0.category_id', 'none');

    expect(icfLineItems($component))->toBe(['صنف يتيم']);
});

it('يهمل معرّف قسمٍ تالفاً يصل من العميل ولا يُفرّغ المنسدلة', function () {
    icfItem('كمبيوتر', icfCategory('الكمبيوتر', 1));
    icfItem('ورق', icfCategory('الورق', 2));

    $this->actingAs(icfUser());

    $component = Livewire::test(IssueCreate::class)->set('lines.0.category_id', 'abc');

    expect(icfLineItems($component))->toHaveCount(2);
});

it('يستبعد الصنف الموقوف من المنسدلة ولو حُصر قسمه', function () {
    $computers = icfCategory('الكمبيوتر', 1);
    icfItem('نشط', $computers, 1);
    tap(icfItem('موقوف', $computers, 2))->update(['is_active' => false]);

    $this->actingAs(icfUser());

    $component = Livewire::test(IssueCreate::class)->set('lines.0.category_id', (string) $computers->id);

    expect(icfLineItems($component))->toBe(['نشط']);
});

// ── استقلال الصفوف: العلّة التي أنشأت التصميم ────────────

it('لا يمسّ قسمُ صفٍّ منسدلةَ صفٍّ آخر ولا ما أُدخل فيه', function () {
    $computers = icfCategory('الكمبيوتر', 1);
    $paper     = icfCategory('الورق', 2);
    $chosen    = icfItem('ورق A4', $paper);
    icfItem('كمبيوتر', $computers);

    $this->actingAs(icfUser());

    $component = Livewire::test(IssueCreate::class)
        ->set('lines.0.category_id', (string) $paper->id)
        ->set('lines.0.item_id', $chosen->id)
        ->set('lines.0.quantity', 7)
        ->call('addLine')
        ->set('lines.1.category_id', (string) $computers->id);

    // ⚠️ هذه هي العلّة بعينها: الصفّ الأول يبقى ممتلئاً وقائمتُه قائمة
    $component->assertSet('lines.0.item_id', $chosen->id)
        ->assertSet('lines.0.quantity', 7);

    expect(icfLineItems($component, 0))->toBe(['ورق A4'])
        ->and(icfLineItems($component, 1))->toBe(['كمبيوتر']);
});

it('يُعلّم الصنف المختار بـselected في الوسم نفسه', function () {
    $paper  = icfCategory('الورق', 2);
    $chosen = icfItem('ورق A4', $paper);

    $this->actingAs(icfUser());

    $html = Livewire::test(IssueCreate::class)
        ->set('lines.0.category_id', (string) $paper->id)
        ->set('lines.0.item_id', $chosen->id)
        ->html();

    // ⚠️ بلا هذا لا يعرف المتصفح المختارَ حين تُبدَّل الخيارات، فتبدو المنسدلة
    //    فارغة والقيمة محفوظة — فيمضي الحفظ بصنفٍ لا يُرى
    expect($html)->toContain('value="'.$chosen->id.'" selected');
});

it('يُفرّغ صنفَ الصفّ حين يتغيّر قسمُه — ولا يمسّ صفاً آخر', function () {
    $paper     = icfCategory('الورق', 2);
    $computers = icfCategory('الكمبيوتر', 1);
    $kept      = icfItem('ورق A4', $paper);
    $dropped   = icfItem('ورق مقوّى', $paper, 2);

    $this->actingAs(icfUser());

    Livewire::test(IssueCreate::class)
        ->set('lines.0.category_id', (string) $paper->id)
        ->set('lines.0.item_id', $kept->id)
        ->call('addLine')
        ->set('lines.1.category_id', (string) $paper->id)
        ->set('lines.1.item_id', $dropped->id)
        // الصنف لا ينتمي لقسمين، فتغيير القسم يُفرّغ صنف صفّه هو وحده
        ->set('lines.1.category_id', (string) $computers->id)
        ->assertSet('lines.1.item_id', null)
        ->assertSet('lines.0.item_id', $kept->id);
});

it('يُخفي الصنف المختار في صفٍّ عن منسدلة صفٍّ آخر ويُبقيه في صفّه', function () {
    $paper  = icfCategory('الورق', 2);
    $chosen = icfItem('ورق A4', $paper, 1);
    icfItem('ورق مقوّى', $paper, 2);

    $this->actingAs(icfUser());

    $component = Livewire::test(IssueCreate::class)
        ->set('lines.0.category_id', (string) $paper->id)
        ->set('lines.0.item_id', $chosen->id)
        ->call('addLine')
        ->set('lines.1.category_id', (string) $paper->id);

    $html = $component->html();

    // 📌 العدّ بنصّ العنصر لا بمعرّفه: منسدلة الأقسام تحمل معرّفات قد تساوي معرّفات الأصناف
    expect(substr_count($html, '>ورق A4<'))->toBe(1)
        ->and(substr_count($html, '>ورق مقوّى<'))->toBe(2);
});

// ── الشاشات الثلاث ───────────────────────────────────────

it('يعمل القسم داخل الصفّ في الشاشات الثلاث', function () {
    icfMainWarehouse();
    $computers = icfCategory('الكمبيوتر', 1);
    icfItem('كمبيوتر', $computers);
    icfItem('ورق', icfCategory('الورق', 2));

    $this->actingAs(icfUser());

    foreach ([IssueCreate::class, IncomingCreate::class, TransferCreate::class] as $screen) {
        $component = Livewire::test($screen)->set('lines.0.category_id', (string) $computers->id);

        expect(icfLineItems($component))->toBe(['كمبيوتر']);
    }
});

it('يبدأ كل صفٍّ جديد بلا قسم ولا صنف', function () {
    icfItem('كمبيوتر', icfCategory('الكمبيوتر', 1));

    $this->actingAs(icfUser());

    Livewire::test(IssueCreate::class)
        ->set('lines.0.category_id', (string) ItemCategory::first()->id)
        ->call('addLine')
        ->assertSet('lines.1.category_id', '')
        ->assertSet('lines.1.item_id', null)
        ->assertSet('lines.1.quantity', null);
});
