<?php

use App\Livewire\Warehouses\Issues\Create as IssueCreate;
use App\Livewire\Warehouses\Movements;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * منتقي الأصناف المشترك — **مجمَّع بالقسم**.
 *
 * ⚠️ العلّة: بُنيت شاشات الإدخال قبل وجود أقسام الأصناف، فكانت تعرض ٣٧٧ صنفاً
 *    في قائمة مسطّحة بالاسم وحده — بلا قسم يرأسها وبلا رقم الدفتر الذي يعرف
 *    به الموظفُ الصنف.
 */
function ipUser(array $permissions = ['warehouses.index', 'warehouses.opening', 'warehouses.issue'], string $role = 'ip-user'): User
{
    foreach ($permissions as $name) {
        Permission::findOrCreate($name, 'web');
    }

    Role::findOrCreate($role, 'web')->syncPermissions($permissions);

    return tap(User::factory()->create(['all_warehouses' => true]))->assignRole($role);
}

function ipCategory(string $name, int $order): ItemCategory
{
    return ItemCategory::create(['name' => $name, 'order' => $order]);
}

function ipItem(string $name, ?ItemCategory $category = null, ?string $code = null, int $order = 1): Item
{
    return Item::create([
        'name'             => $name,
        'code'             => $code,
        'order'            => $order,
        'item_category_id' => $category?->id,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
    ]);
}

function ipWarehouse(): Warehouse
{
    return Warehouse::create([
        'name'              => 'المخزن الرئيسي',
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'رئيسي'], ['level' => 1, 'order' => 1])->id,
        'is_active'         => true,
    ]);
}

// ── التجميع ──────────────────────────────────────────────

it('يجمع الأصناف تحت أقسامها في منسدلة الإدخال', function () {
    $computers = ipCategory('الكمبيوتر', 1);
    $paper     = ipCategory('الورق والحافظات', 2);
    ipItem('جهاز كمبيوتر', $computers);
    ipItem('حبر أسود', $computers, order: 2);
    ipItem('ورق A4', $paper);

    $this->actingAs(ipUser());

    $html = Livewire::test(IssueCreate::class)->html();

    expect($html)->toContain('<optgroup label="الكمبيوتر">')
        ->and($html)->toContain('<optgroup label="الورق والحافظات">')
        ->and($html)->toContain('جهاز كمبيوتر');
});

it('يحافظ على ترتيب الدفتر بين الأقسام لا الترتيب الأبجدي', function () {
    // «الكمبيوتر» ترتيبه ٢ و«الورق» ترتيبه ١ — فالدفتر يقدّم الورق
    $computers = ipCategory('الكمبيوتر', 2);
    $paper     = ipCategory('الورق والحافظات', 1);
    ipItem('جهاز كمبيوتر', $computers);
    ipItem('ورق A4', $paper);

    $this->actingAs(ipUser());

    $html = Livewire::test(IssueCreate::class)->html();

    expect(strpos($html, 'الورق والحافظات'))->toBeLessThan(strpos($html, 'الكمبيوتر'));
});

it('يضع الأصناف بلا قسم في مجموعة أخيرة', function () {
    $computers = ipCategory('الكمبيوتر', 1);
    ipItem('جهاز كمبيوتر', $computers);
    ipItem('صنف بلا قسم');

    $this->actingAs(ipUser());

    $html = Livewire::test(IssueCreate::class)->html();

    expect($html)->toContain('<optgroup label="بلا قسم">')
        // ⚠️ آخراً كما في الدفتر — `statementOrder` يدفعها للنهاية
        ->and(strpos($html, 'label="الكمبيوتر"'))->toBeLessThan(strpos($html, 'label="بلا قسم"'));
});

it('يُظهر رقم الصنف بجوار اسمه حيث له رقم', function () {
    $registry = ipCategory('الدفتر العقاري', 1);
    ipItem('حركة التأشير الهامشى', $registry, code: '٥٤ ق');
    ipItem('صنف بلا رقم', $registry, order: 2);

    $this->actingAs(ipUser());

    $html = Livewire::test(IssueCreate::class)->html();

    expect($html)->toContain('حركة التأشير الهامشى — ٥٤ ق')
        // والذي بلا رقم يبقى باسمه وحده بلا شرطة معلَّقة
        ->and($html)->not->toContain('صنف بلا رقم —');
});

// ── إخفاء المُختار في صفٍّ آخر ────────────────────────────

it('يُخفي الصنف المختار في صفٍّ آخر ويُبقيه في صفّه', function () {
    $category = ipCategory('الكمبيوتر', 1);
    $first    = ipItem('جهاز كمبيوتر', $category);
    ipItem('حبر أسود', $category, order: 2);

    $this->actingAs(ipUser());

    $component = Livewire::test(IssueCreate::class)
        ->call('addLine')
        ->set('lines.0.item_id', $first->id);

    $html = $component->html();

    // مرة واحدة فقط: في صفّه هو، لا في الصف الثاني
    expect(substr_count($html, 'value="'.$first->id.'"'))->toBe(1);
});

it('لا يعرض عنوان قسمٍ خلت أصنافه كلها', function () {
    $computers = ipCategory('الكمبيوتر', 1);
    $paper     = ipCategory('الورق والحافظات', 2);
    $only      = ipItem('جهاز كمبيوتر', $computers);
    ipItem('ورق A4', $paper);

    $this->actingAs(ipUser());

    $component = Livewire::test(IssueCreate::class)
        ->call('addLine')
        ->set('lines.0.item_id', $only->id);

    // الصف الثاني لا يجد في «الكمبيوتر» صنفاً، فلا يُعرض عنوانه فارغاً
    expect(substr_count($component->html(), 'label="الكمبيوتر"'))->toBe(1);
});

// ── الفلاتر تستعمل المنتقي بلا صفوف ──────────────────────

it('يعمل في منسدلة الفلترة التي لا تمرّر صفوفاً', function () {
    $category = ipCategory('الكمبيوتر', 1);
    ipItem('جهاز كمبيوتر', $category, code: '٧ ك');

    $this->actingAs(ipUser());

    // ⚠️ سجل الحركات فلترٌ لا فورم — لا `$chosen` ولا `$line`، فلو لم يحتملهما
    //    المنتقي اختياريين لسقطت الشاشة بـundefined variable
    $html = Livewire::test(Movements::class)->html();

    expect($html)->toContain('<optgroup label="الكمبيوتر">')
        ->and($html)->toContain('جهاز كمبيوتر — ٧ ك');
});

it('يجمع بالقسم في شاشات الإدخال الثلاث', function () {
    $category = ipCategory('الكمبيوتر', 1);
    ipItem('جهاز كمبيوتر', $category);
    ipWarehouse();

    $this->actingAs(ipUser([
        'warehouses.index', 'warehouses.opening', 'warehouses.issue',
        'warehouses.incoming', 'warehouses.transfer',
    ], 'ip-all'));

    // ⚠️ ثلاثٌ لا أربع: «الأرصدة الافتتاحية» صارت جدولاً على صورة البيان
    //    الورقي بلا منسدلة صنف — يحرسها OpeningBalancesTest.
    foreach ([
        IssueCreate::class,
        \App\Livewire\Warehouses\Incoming\Create::class,
        \App\Livewire\Warehouses\Transfers\Create::class,
    ] as $screen) {
        expect(Livewire::test($screen)->html())
            ->toContain('<optgroup label="الكمبيوتر">');
    }
});
