<?php

use App\Livewire\Warehouses\Manage\Index as ManageIndex;
use App\Livewire\Warehouses\Movements;
use App\Livewire\Warehouses\Stock;
use App\Models\Governorate;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function warehouseAdmin(): User
{
    Permission::findOrCreate('warehouses.index', 'web');
    Permission::findOrCreate('warehouses.settings', 'web');
    $role = Role::findOrCreate('wh-admin', 'web');
    $role->givePermissionTo(['warehouses.index', 'warehouses.settings']);

    return tap(User::factory()->create())->assignRole($role);
}

function typeLevel(string $name, int $level): WarehouseType
{
    return WarehouseType::firstOrCreate(['name' => $name], ['level' => $level, 'order' => $level]);
}

/**
 * ثلاثة مستويات وثلاث محافظات، مُنشأة بترتيبٍ يخالف المطلوب عمداً.
 *
 * ⚠️ وترتيب المحافظات هنا **يخالف ترتيبها الأبجدي** عن قصد: «قنا» أولاً بالرقم
 *    وأخيراً بالحروف. فلو رُتِّب بالاسم بدل العمود `order` اختلفت النتيجة —
 *    وبيانات متوافقة الترتيبين تُمرّر الاختبار وإن سقط الترتيب المطلوب.
 */
function seedWarehouseTree(): void
{
    $main     = typeLevel('رئيسي', 1);
    $regional = typeLevel('إقليمي', 2);
    $branch   = typeLevel('فرعي', 3);

    $qena  = Governorate::create(['name' => 'قنا',    'order' => 1]);
    $cairo = Governorate::create(['name' => 'القاهرة', 'order' => 2]);
    $giza  = Governorate::create(['name' => 'الجيزة',  'order' => 3]);

    // الإنشاء بترتيب معكوس: لو لم يُطبَّق النطاق ظهرت بترتيب المعرّفات
    Warehouse::create(['name' => 'فرع الجيزة',     'warehouse_type_id' => $branch->id,   'governorate_id' => $giza->id]);
    Warehouse::create(['name' => 'إقليمي القاهرة', 'warehouse_type_id' => $regional->id, 'governorate_id' => $cairo->id]);
    Warehouse::create(['name' => 'فرع قنا',        'warehouse_type_id' => $branch->id,   'governorate_id' => $qena->id]);
    Warehouse::create(['name' => 'إقليمي قنا',     'warehouse_type_id' => $regional->id, 'governorate_id' => $qena->id]);
    Warehouse::create(['name' => 'المخزن الرئيسي', 'warehouse_type_id' => $main->id,     'governorate_id' => null]);
}

/** الترتيب الصحيح المتوقَّع: المستوى ثم رقم المحافظة. */
function expectedWarehouseOrder(): array
{
    return [
        'المخزن الرئيسي',   // مستوى ١، بلا محافظة
        'إقليمي قنا',       // مستوى ٢، محافظة رقمها ١
        'إقليمي القاهرة',   // مستوى ٢، محافظة رقمها ٢
        'فرع قنا',          // مستوى ٣، محافظة رقمها ١
        'فرع الجيزة',       // مستوى ٣، محافظة رقمها ٣
    ];
}

// ── ترتيب المخازن ────────────────────────────────────────

it('يرتّب المخازن بالمستوى ثم المحافظة', function () {
    seedWarehouseTree();

    expect(Warehouse::ordered()->pluck('name')->all())->toBe(expectedWarehouseOrder());
});

it('لا يُسقط المخزن بلا محافظة من الترتيب', function () {
    // المخزن الرئيسي بلا محافظة — والانضمام الداخلي كان يُخفيه من كل قائمة
    seedWarehouseTree();

    expect(Warehouse::ordered()->pluck('name')->all())->toContain('المخزن الرئيسي')
        ->and(Warehouse::ordered()->count())->toBe(5);
});

it('يحفظ معرّف المخزن لا معرّف نوعه أو محافظته', function () {
    // بلا select('warehouses.*') يطغى id الجدولين المنضمّين على id المخزن
    seedWarehouseTree();
    $main = Warehouse::where('name', 'المخزن الرئيسي')->first();

    $fromScope = Warehouse::ordered()->get()->firstWhere('name', 'المخزن الرئيسي');

    expect($fromScope->id)->toBe($main->id);
});

it('يطبّق الترتيب في شاشة إدارة المخازن', function () {
    seedWarehouseTree();
    $this->actingAs(warehouseAdmin());

    $rows = Livewire::test(ManageIndex::class)->viewData('warehouses');

    expect($rows->pluck('name')->all())->toBe(expectedWarehouseOrder());
});

it('يطبّق الترتيب في منسدلة شاشة الأرصدة', function () {
    seedWarehouseTree();
    $this->actingAs(warehouseAdmin());

    expect(Livewire::test(Stock::class)->viewData('warehouses')->pluck('name')->all())
        ->toBe(expectedWarehouseOrder());
});

it('يطبّق الترتيب في منسدلة سجل الحركات', function () {
    seedWarehouseTree();
    $this->actingAs(warehouseAdmin());

    expect(Livewire::test(Movements::class)->viewData('warehouses')->pluck('name')->all())
        ->toBe(expectedWarehouseOrder());
});

it('يبقي البحث العربي عاملاً في شاشة إدارة المخازن بعد الترتيب', function () {
    // ⚠️ الاسم صار ملتبساً بعد الانضمام (warehouses.name مقابل governorates.name)
    seedWarehouseTree();
    $this->actingAs(warehouseAdmin());

    $rows = Livewire::test(ManageIndex::class)->set('search', 'الجيزه')->viewData('warehouses');

    expect($rows->pluck('name')->all())->toBe(['فرع الجيزة']);
});

// ── فلتر نوع المخزن ──────────────────────────────────────

it('يفلتر المخازن بنوعها', function () {
    seedWarehouseTree();
    $regional = WarehouseType::where('name', 'إقليمي')->first();
    $this->actingAs(warehouseAdmin());

    $rows = Livewire::test(ManageIndex::class)
        ->set('typeFilter', (string) $regional->id)
        ->viewData('warehouses');

    expect($rows->pluck('name')->all())->toBe(['إقليمي قنا', 'إقليمي القاهرة']);
});

it('يعرض كل المخازن حين لا نوع مختاراً', function () {
    seedWarehouseTree();
    $this->actingAs(warehouseAdmin());

    expect(Livewire::test(ManageIndex::class)->viewData('warehouses')->total())->toBe(5);
});

it('يتجاهل قيمة نوع تالفة تصل من العميل', function () {
    seedWarehouseTree();
    $this->actingAs(warehouseAdmin());

    $rows = Livewire::test(ManageIndex::class)
        ->set('typeFilter', 'إقليمي')
        ->viewData('warehouses');

    expect($rows->total())->toBe(5);
});

it('يجمع فلتر النوع مع البحث ويحفظ الترتيب', function () {
    seedWarehouseTree();
    $branch = WarehouseType::where('name', 'فرعي')->first();
    $this->actingAs(warehouseAdmin());

    $rows = Livewire::test(ManageIndex::class)
        ->set('typeFilter', (string) $branch->id)
        ->set('search', 'فرع')
        ->viewData('warehouses');

    // الترتيب داخل النوع الواحد يبقى بالمحافظة: قنا (١) قبل الجيزة (٣)
    expect($rows->pluck('name')->all())->toBe(['فرع قنا', 'فرع الجيزة']);
});

it('يعرض أنواع المخازن بترتيب المستوى لا أبجدياً', function () {
    seedWarehouseTree();
    $this->actingAs(warehouseAdmin());

    expect(Livewire::test(ManageIndex::class)->viewData('types')->pluck('name')->all())
        ->toBe(['رئيسي', 'إقليمي', 'فرعي']);
});

// ── منسدلة الأصناف تتبع القسم ────────────────────────────

it('يحصر منسدلة الأصناف في القسم المختار', function () {
    $photo = itemIn('مخزن التصوير', 'حبر توشيبا', 1);
    itemIn('مخزن المستديم', 'بونطة شنيور', 2);
    $this->actingAs(warehouseAdmin());

    $items = Livewire::test(Movements::class)
        ->set('categoryFilter', (string) $photo->item_category_id)
        ->viewData('items');

    expect($items->pluck('name')->all())->toBe(['حبر توشيبا']);
});

it('يعرض كل الأصناف في المنسدلة حين لا قسم مختاراً', function () {
    itemIn('مخزن التصوير', 'حبر توشيبا', 1);
    itemIn('مخزن المستديم', 'بونطة شنيور', 2);
    $this->actingAs(warehouseAdmin());

    expect(Livewire::test(Movements::class)->viewData('items')->count())->toBe(2);
});

it('يصفّر الصنف المختار عند تغيير القسم', function () {
    // وإلا بقي صنفٌ من قسمٍ آخر مُطبَّقاً وهو غائب عن المنسدلة، فتُعرض شاشة
    // فارغة بلا سببٍ يراه المستخدم
    $photo = itemIn('مخزن التصوير', 'حبر توشيبا', 1);
    $fixed = itemIn('مخزن المستديم', 'بونطة شنيور', 2);
    $this->actingAs(warehouseAdmin());

    Livewire::test(Movements::class)
        ->set('itemFilter', (string) $fixed->id)
        ->assertSet('itemFilter', (string) $fixed->id)
        ->set('categoryFilter', (string) $photo->item_category_id)
        ->assertSet('itemFilter', '');
});

it('يحصر المنسدلة في الأصناف بلا قسم عند اختيار «بلا قسم»', function () {
    itemIn('مخزن التصوير', 'حبر توشيبا', 1);
    Item::create(['name' => 'صنف بلا تصنيف', 'item_category_id' => null, 'item_unit_id' => null]);
    $this->actingAs(warehouseAdmin());

    $items = Livewire::test(Movements::class)
        ->set('categoryFilter', 'none')
        ->viewData('items');

    expect($items->pluck('name')->all())->toBe(['صنف بلا تصنيف']);
});
