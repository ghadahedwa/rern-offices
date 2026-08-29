<?php

use App\Livewire\Users\Create as UserCreate;
use App\Livewire\Users\Edit as UserEdit;
use App\Livewire\Warehouses\Dashboard;
use App\Livewire\Warehouses\Incoming\Index as IncomingIndex;
use App\Livewire\Warehouses\ItemBalances;
use App\Livewire\Warehouses\Items\Show as ItemShow;
use App\Livewire\Warehouses\Movements;
use App\Livewire\Warehouses\OpeningBalances;
use App\Livewire\Warehouses\Statement;
use App\Livewire\Warehouses\Stock;
use App\Livewire\Warehouses\Transfers\Index as TransfersIndex;
use App\Models\Governorate;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseIncoming;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseType;
use App\Support\WarehouseScope;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * نطاق رؤية المخازن — `App\Support\WarehouseScope`.
 *
 * ⚠️ ثلاث حالات لا حالتان: `null` بلا حدّ · `[]` **لا يرى شيئاً** · `[...]` مخازنه.
 *    وإرجاع `null` لمن لا مخزن له يفتح المنظومة كلها — وهو ما يحرسه هذا الملف.
 */
function wsPerms(array $names): array
{
    foreach ($names as $name) {
        Permission::findOrCreate($name, 'web');
    }

    return $names;
}

function wsUser(array $permissions, string $role, array $warehouses = [], bool $all = false): User
{
    $roleModel = Role::findOrCreate($role, 'web');
    $roleModel->syncPermissions(wsPerms($permissions));

    $user = tap(User::factory()->create(['all_warehouses' => $all]))->assignRole($roleModel);
    $user->warehouses()->sync(collect($warehouses)->pluck('id')->all());

    return $user->fresh();
}

function wsType(string $name, int $level): WarehouseType
{
    return WarehouseType::firstOrCreate(['name' => $name], ['level' => $level, 'order' => $level]);
}

function wsWarehouse(string $name, int $level = 3, ?Governorate $gov = null): Warehouse
{
    return Warehouse::create([
        'name'              => $name,
        'warehouse_type_id' => wsType('نوع '.$level, $level)->id,
        'governorate_id'    => $gov?->id,
        'is_active'         => true,
    ]);
}

function wsItem(string $name = 'كمبيوتر'): Item
{
    return Item::create([
        'name'             => $name,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
        'item_category_id' => ItemCategory::firstOrCreate(['name' => 'الكمبيوتر'], ['order' => 1])->id,
    ]);
}

function wsStock(Warehouse $w, Item $i, int $qty): WarehouseStock
{
    return WarehouseStock::create(['warehouse_id' => $w->id, 'item_id' => $i->id, 'quantity' => $qty]);
}

function wsMovement(Warehouse $w, Item $i, int $qty = 5): WarehouseMovement
{
    return WarehouseMovement::create([
        'warehouse_id'   => $w->id,
        'item_id'        => $i->id,
        'type'           => 'opening',
        'quantity'       => $qty,
        'balance_before' => 0,
        'balance_after'  => $qty,
    ]);
}

// ── الحالات الثلاث ───────────────────────────────────────

it('يفرّق بين «بلا حدّ» و«لا يرى شيئاً» — والفراغ ليس إذناً', function () {
    $mine = wsWarehouse('مخزني');

    $limited   = wsUser(['warehouses.index'], 'ws-limited', [$mine]);
    $empty     = wsUser(['warehouses.index'], 'ws-empty');
    $unlimited = wsUser(['warehouses.index'], 'ws-all', [], all: true);

    expect(WarehouseScope::warehouseIds($limited))->toBe([$mine->id])
        // ⚠️ الفراغ = لا شيء. لو صار null هنا لانفتحت المنظومة كلها لمن لا نطاق له
        ->and(WarehouseScope::warehouseIds($empty))->toBe([])
        ->and(WarehouseScope::warehouseIds($unlimited))->toBeNull();
});

it('يعطي super-admin رؤيةً بلا حدّ ولو لم يُربط بمخزن', function () {
    $role = Role::findOrCreate('super-admin', 'web');
    $user = tap(User::factory()->create())->assignRole($role);

    expect(WarehouseScope::warehouseIds($user))->toBeNull();
});

// ── الشاشات ──────────────────────────────────────────────

it('يقصر شاشة أرصدة المخازن ومنسدلتها على مخازن المستخدم', function () {
    $mine   = wsWarehouse('مخزني');
    $theirs = wsWarehouse('مخزن غيري');
    $item   = wsItem();
    wsStock($mine, $item, 10);
    wsStock($theirs, $item, 99);

    $this->actingAs(wsUser(['warehouses.index'], 'ws-stock', [$mine]));

    $component = Livewire::test(Stock::class);

    expect($component->viewData('stocks')->pluck('quantity')->all())->toBe([10])
        // ⚠️ المنسدلة تُفلتر كالنتائج، وإلا تسرّب اسم مخزنٍ خارج النطاق
        ->and($component->viewData('warehouses')->pluck('name')->all())->toBe(['مخزني']);
});

it('لا يُخرج شيئاً لصاحب صلاحيةٍ بلا مخزن مرتبط', function () {
    $w = wsWarehouse('مخزن');
    wsStock($w, wsItem(), 10);

    $this->actingAs(wsUser(['warehouses.index'], 'ws-none'));

    expect(Livewire::test(Stock::class)->viewData('stocks')->count())->toBe(0);
});

it('يقصر سجل الحركات على مخازن المستخدم', function () {
    $mine   = wsWarehouse('مخزني');
    $theirs = wsWarehouse('مخزن غيري');
    $item   = wsItem();
    wsMovement($mine, $item, 3);
    wsMovement($theirs, $item, 77);

    $this->actingAs(wsUser(['warehouses.index'], 'ws-mov', [$mine]));

    expect(Livewire::test(Movements::class)->viewData('movements')->pluck('quantity')->all())->toBe([3]);
});

it('يقصر إجمالي الصنف في شاشة أرصدة الأصناف على مخازن المستخدم', function () {
    $mine   = wsWarehouse('مخزني');
    $theirs = wsWarehouse('مخزن غيري');
    $item   = wsItem();
    wsStock($mine, $item, 10);
    wsStock($theirs, $item, 90);

    $this->actingAs(wsUser(['warehouses.index'], 'ws-ib', [$mine]));

    $row = Livewire::test(ItemBalances::class)->viewData('items')->first();

    // ⚠️ الإجمالي ١٠ لا ١٠٠: رقمٌ يتجاوز النطاق يسرّب حجم مخزنٍ لا يراه
    expect((int) $row->total_quantity)->toBe(10)
        ->and((int) $row->warehouses_count)->toBe(1);
});

it('يقصر صفحة الصنف على مخازن المستخدم ويُخفي بطاقة الرئيسي خارج نطاقه', function () {
    $main = wsWarehouse('الرئيسي', 1);
    $mine = wsWarehouse('مخزني', 3);
    $item = wsItem();
    wsStock($main, $item, 900);
    wsStock($mine, $item, 10);

    $this->actingAs(wsUser(['warehouses.index'], 'ws-item', [$mine]));

    $component = Livewire::test(ItemShow::class, ['item' => $item]);
    $summary   = $component->viewData('summary');

    expect($component->viewData('balances')->pluck('name')->all())->toBe(['مخزني'])
        ->and($summary['total'])->toBe(10)
        // ⚠️ فرقٌ بين «لا رئيسي في المنظومة» و«الرئيسي خارج نطاقي» — الثانية تُخفى
        ->and($summary['showMain'])->toBeFalse();
});

it('يعرض بطاقة الرئيسي لمن نطاقه بلا حدّ', function () {
    wsWarehouse('الرئيسي', 1);
    $item = wsItem();

    $this->actingAs(wsUser(['warehouses.index'], 'ws-item-all', [], all: true));

    expect(Livewire::test(ItemShow::class, ['item' => $item])->viewData('summary')['showMain'])->toBeTrue();
});

it('يقصر أرقام اللوحة على نطاق قارئها', function () {
    $mine   = wsWarehouse('مخزني');
    wsWarehouse('مخزن غيري');
    $item = wsItem();
    wsMovement($mine, $item);

    $this->actingAs(wsUser(['warehouses.index'], 'ws-dash', [$mine]));

    expect(Livewire::test(Dashboard::class)->viewData('warehousesCount'))->toBe(1);
});

it('يقصر البيان المطبوع ومنسدلته على نطاق القارئ', function () {
    $mine   = wsWarehouse('مخزني');
    $theirs = wsWarehouse('مخزن غيري');
    $category = ItemCategory::firstOrCreate(['name' => 'الكمبيوتر'], ['order' => 1]);

    $this->actingAs(wsUser(['warehouses.index', 'warehouses.export'], 'ws-stmt', [$mine]));

    $component = Livewire::withQueryParams(['wh' => (string) $theirs->id, 'category' => (string) $category->id])
        ->test(Statement::class);

    // معرّف مخزنٍ خارج النطاق يصل من الرابط فلا يُبنى له بيان
    expect($component->viewData('statement'))->toBeNull()
        ->and($component->viewData('warehouses')->pluck('name')->all())->toBe(['مخزني']);
});

it('يمنع تقرير البيان المطبوع لمخزنٍ خارج النطاق', function () {
    $mine     = wsWarehouse('مخزني');
    $theirs   = wsWarehouse('مخزن غيري');
    $category = ItemCategory::firstOrCreate(['name' => 'الكمبيوتر'], ['order' => 1]);

    $this->actingAs(wsUser(['warehouses.index', 'warehouses.export'], 'ws-pdf', [$mine]));

    $this->get(route('warehouses.statement.pdf', ['wh' => $theirs->id, 'category' => $category->id]))
        ->assertNotFound();
});

// ── الحُرّاس على الأفعال ───────────────────────────────────

it('يمنع ضبط رصيدٍ افتتاحي لمخزنٍ خارج النطاق ولو دُسّ معرّفه', function () {
    $mine   = wsWarehouse('مخزني');
    $theirs = wsWarehouse('مخزن غيري');
    $item   = wsItem();

    $this->actingAs(wsUser(['warehouses.index', 'warehouses.opening'], 'ws-open', [$mine]));

    Livewire::test(OpeningBalances::class)
        ->set('warehouse_id', $theirs->id)
        ->set('lines', [['item_id' => $item->id, 'quantity' => 5]])
        ->call('save')
        ->assertStatus(403);

    // ولم يُمَسّ رصيدُه
    expect(WarehouseStock::where('warehouse_id', $theirs->id)->count())->toBe(0);
});

it('يقصر منسدلة الرصيد الافتتاحي على مخازن المستخدم', function () {
    $mine = wsWarehouse('مخزني');
    wsWarehouse('مخزن غيري');

    $this->actingAs(wsUser(['warehouses.index', 'warehouses.opening'], 'ws-open2', [$mine]));

    expect(Livewire::test(OpeningBalances::class)->viewData('warehouses')->pluck('name')->all())
        ->toBe(['مخزني']);
});

it('يحرس مصدر النقل لا وجهته — فأمين الرئيسي ينقل إلى المحافظات كلها', function () {
    $main   = wsWarehouse('الرئيسي', 1);
    $branch = wsWarehouse('فرع', 3);

    $this->actingAs(wsUser(['warehouses.index', 'warehouses.transfer'], 'ws-tr', [$main]));

    $component = Livewire::test(\App\Livewire\Warehouses\Transfers\Create::class);

    // المصدر نطاقُه، والوجهة كل المخازن
    expect($component->viewData('sourceWarehouses')->pluck('name')->all())->toBe(['الرئيسي'])
        ->and($component->viewData('warehouses')->pluck('name')->all())->toContain('فرع');
});

it('يمنع حذف نقلٍ مصدرُه خارج نطاق المستخدم', function () {
    $main   = wsWarehouse('الرئيسي', 1);
    $mine   = wsWarehouse('مخزني', 3);
    $item   = wsItem();
    wsStock($mine, $item, 5);

    $transfer = WarehouseTransfer::create([
        'from_warehouse_id' => $main->id,
        'to_warehouse_id'   => $mine->id,
        'transferred_at'    => '2026-08-20',
        'attachment_path'   => 'x.pdf',
        'attachment_original_name' => 'x.pdf',
    ]);

    // ⚠️ المستلِم يرى النقل (طرفٌ فيه) لكنه لا يحذفه: الحذف يعكس الحركة على
    //    الطرفين، فيُنقص رصيدَه ويزيد الرئيسي بلا علم المركز
    $this->actingAs(wsUser(['warehouses.index', 'warehouses.delete'], 'ws-del', [$mine]));

    $component = Livewire::test(TransfersIndex::class);
    expect($component->viewData('transfers')->count())->toBe(1);

    $component->call('askDelete', $transfer->id)->assertStatus(403);
});

it('يُظهر النقل لمن هو طرفٌ فيه ولو لم يملك الطرف الآخر', function () {
    $main = wsWarehouse('الرئيسي', 1);
    $mine = wsWarehouse('مخزني', 3);
    $out  = wsWarehouse('لا يخصّني', 3);

    WarehouseTransfer::create([
        'from_warehouse_id' => $main->id, 'to_warehouse_id' => $mine->id,
        'transferred_at' => '2026-08-20', 'attachment_path' => 'a.pdf', 'attachment_original_name' => 'a.pdf',
    ]);
    WarehouseTransfer::create([
        'from_warehouse_id' => $main->id, 'to_warehouse_id' => $out->id,
        'transferred_at' => '2026-08-21', 'attachment_path' => 'b.pdf', 'attachment_original_name' => 'b.pdf',
    ]);

    $this->actingAs(wsUser(['warehouses.index'], 'ws-either', [$mine]));

    expect(Livewire::test(TransfersIndex::class)->viewData('transfers')->count())->toBe(1);
});

it('يمنع عرض واردٍ على مخزنٍ خارج النطاق ولو دُسّ معرّفه', function () {
    $main   = wsWarehouse('الرئيسي', 1);
    $mine   = wsWarehouse('مخزني', 3);

    $incoming = WarehouseIncoming::create([
        'warehouse_id' => $main->id,
        'received_at'  => '2026-08-20',
        'supplier_name' => 'مورد',
        'attachment_path' => 'x.pdf',
        'attachment_original_name' => 'x.pdf',
    ]);

    $this->actingAs(wsUser(['warehouses.index'], 'ws-inc', [$mine]));

    $component = Livewire::test(IncomingIndex::class);
    expect($component->viewData('incomings')->count())->toBe(0);

    $component->call('view', $incoming->id)->assertStatus(403);
});

// ── فورم المستخدم ────────────────────────────────────────

it('يُظهر منتقي المخازن لدور المخازن دون دور إعداداتها', function () {
    Permission::findOrCreate('warehouses.index', 'web');
    Permission::findOrCreate('warehouses.settings', 'web');

    Role::findOrCreate('ws-op-role', 'web')->syncPermissions(['warehouses.index']);
    Role::findOrCreate('ws-settings-role', 'web')->syncPermissions(['warehouses.settings']);

    $this->actingAs(tap(User::factory()->create())->assignRole(
        tap(Role::findOrCreate('super-admin', 'web'))->syncPermissions([])
    ));

    expect(Livewire::test(UserCreate::class)->set('role', 'ws-op-role')->viewData('needsWarehouses'))->toBeTrue()
        // ⚠️ العنوان لا البادئة: `warehouses.settings` تبدأ بـwarehouses. وهي
        //    تحت «إدارة النظام» — ومديرُ القوائم المرجعية لا نطاق مخزن له
        ->and(Livewire::test(UserCreate::class)->set('role', 'ws-settings-role')->viewData('needsWarehouses'))->toBeFalse();
});

it('يمنع حفظ مستخدم بصلاحية مخازن بلا مخزن ولا «كل المخازن»', function () {
    Permission::findOrCreate('warehouses.index', 'web');
    Role::findOrCreate('ws-need', 'web')->syncPermissions(['warehouses.index']);
    Role::findOrCreate('super-admin', 'web');

    $this->actingAs(tap(User::factory()->create())->assignRole('super-admin'));

    Livewire::test(UserCreate::class)
        ->set('name', 'مفتش')
        ->set('username', 'inspector1')
        ->set('password', '1234')
        ->set('password_confirmation', '1234')
        ->set('role', 'ws-need')
        ->set('selectedWarehouses', [])
        ->call('save')
        ->assertHasErrors('selectedWarehouses');

    expect(User::where('username', 'inspector1')->exists())->toBeFalse();
});

it('يحفظ «كل المخازن» بلا قائمة، ولا يُبقي قائمةً بائدة تحتها', function () {
    Permission::findOrCreate('warehouses.index', 'web');
    Role::findOrCreate('ws-all-role', 'web')->syncPermissions(['warehouses.index']);
    Role::findOrCreate('super-admin', 'web');
    $w = wsWarehouse('مخزن');

    $this->actingAs(tap(User::factory()->create())->assignRole('super-admin'));

    Livewire::test(UserCreate::class)
        ->set('name', 'أمين')
        ->set('username', 'keeper1')
        ->set('password', '1234')
        ->set('password_confirmation', '1234')
        ->set('role', 'ws-all-role')
        ->set('selectedWarehouses', [$w->id])
        ->set('allWarehouses', true)
        ->call('save');

    $user = User::where('username', 'keeper1')->first();

    expect($user->all_warehouses)->toBeTrue()
        ->and($user->warehouses()->count())->toBe(0)
        ->and(WarehouseScope::warehouseIds($user))->toBeNull();
});

it('يملأ المخازن من محافظات المستخدم ملءً تفاضلياً يحترم تعديله اليدوي', function () {
    $qena  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $aswan = Governorate::create(['name' => 'أسوان', 'order' => 2]);
    $wQena  = wsWarehouse('مخزن قنا', 3, $qena);
    $wAswan = wsWarehouse('مخزن أسوان', 3, $aswan);
    $main   = wsWarehouse('الرئيسي', 1);   // بلا محافظة — لا يبلغه الملء أبداً

    Permission::findOrCreate('warehouses.index', 'web');
    Permission::findOrCreate('offices.index', 'web');
    Role::findOrCreate('ws-fill', 'web')->syncPermissions(['warehouses.index', 'offices.index']);
    Role::findOrCreate('super-admin', 'web');

    $this->actingAs(tap(User::factory()->create())->assignRole('super-admin'));

    $component = Livewire::test(UserCreate::class)->set('role', 'ws-fill');

    // إضافة محافظة → تُعلَّم مخازنها
    $component->set('selectedGovernorates', [(string) $qena->id]);
    expect($component->get('selectedWarehouses'))->toBe([$wQena->id]);

    // إضافة أخرى → تُضاف مخازنها ولا تُمحى السابقة
    $component->set('selectedGovernorates', [(string) $qena->id, (string) $aswan->id]);
    expect($component->get('selectedWarehouses'))->toBe([$wQena->id, $wAswan->id]);

    // اختيارٌ يدوي للرئيسي (بلا محافظة) يثبت رغم تغيّر المحافظات
    $component->set('selectedWarehouses', [$wQena->id, $wAswan->id, $main->id]);
    $component->set('selectedGovernorates', [(string) $qena->id]);

    // نُزعت أسوان → نُزع مخزنها وحده، وبقي الرئيسي المختار يدوياً
    expect($component->get('selectedWarehouses'))->toBe([$wQena->id, $main->id]);
});

it('يحمّل نطاق المخازن في شاشة التعديل ويبدأ ظِلّ المحافظات بالمحفوظ', function () {
    $gov = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $wGov = wsWarehouse('مخزن قنا', 3, $gov);
    $main = wsWarehouse('الرئيسي', 1);

    Permission::findOrCreate('warehouses.index', 'web');
    Permission::findOrCreate('offices.index', 'web');
    Role::findOrCreate('ws-edit', 'web')->syncPermissions(['warehouses.index', 'offices.index']);
    Role::findOrCreate('super-admin', 'web');

    // ⚠️ المدير أولاً: شاشة التعديل تردّ عن المستخدم رقم ١، وقاعدةُ الاختبار
    //    تبدأ فارغةً — فأول مُنشأ يأخذ الرقم ١ ويصير غير قابل للتعديل
    $this->actingAs(tap(User::factory()->create())->assignRole('super-admin'));

    // مستخدمٌ محافظتُه قنا، لكن مخزنه الرئيسي وحده (نُزع مخزن قنا بيد المدير)
    $target = tap(User::factory()->create())->assignRole('ws-edit');
    $target->governorates()->sync([$gov->id]);
    $target->warehouses()->sync([$main->id]);

    $component = Livewire::test(UserEdit::class, ['user' => $target]);

    expect($component->get('selectedWarehouses'))->toBe([(string) $main->id]);

    // ⚠️ الظِلّ يبدأ بالمحفوظ: لولا ذلك لَحُسبت قنا «مضافةً» عند أول تعديل
    //    فعاد مخزنُها الذي نُزع بيد المدير
    $component->set('selectedGovernorates', [(string) $gov->id]);
    expect($component->get('selectedWarehouses'))->not->toContain($wGov->id);
});
