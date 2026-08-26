<?php

use App\Livewire\Warehouses\Incoming\Index as IncomingIndex;
use App\Livewire\Warehouses\Items\Index as ItemsIndex;
use App\Livewire\Warehouses\Manage\Index as ManageIndex;
use App\Livewire\Warehouses\Movements;
use App\Livewire\Warehouses\Stock;
use App\Models\Governorate;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseIncoming;
use App\Models\WarehouseIncomingItem;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use App\Models\WarehouseType;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * دفعة الفلاتر والترتيب: الحالة في الرابط · الترتيب بالقائمة البيضاء ·
 * عدد الصفوف · فلتر الفترة (وفارق UTC/القاهرة في سجل الحركات).
 */
function tblUser(): User
{
    foreach (['warehouses.index', 'warehouses.settings'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $role = Role::findOrCreate('tbl-admin', 'web');
    $role->givePermissionTo(['warehouses.index', 'warehouses.settings']);

    return tap(User::factory()->create())->assignRole($role);
}

function tblType(string $name, int $level): WarehouseType
{
    return WarehouseType::firstOrCreate(['name' => $name], ['level' => $level, 'order' => $level]);
}

/** مخزنان: رئيسي اسمه «ي...» وفرعي اسمه «أ...» — فالترتيب الأبجدي يخالف ترتيب المستوى. */
function tblTwoWarehouses(): array
{
    $gov = Governorate::create(['name' => 'قنا', 'order' => 1]);

    return [
        Warehouse::create(['name' => 'يونيو الرئيسي', 'warehouse_type_id' => tblType('رئيسي', 1)->id]),
        Warehouse::create(['name' => 'أسوان الفرعي', 'warehouse_type_id' => tblType('فرعي', 3)->id, 'governorate_id' => $gov->id]),
    ];
}

function tblItem(string $name, ?string $categoryName = null, int $order = 1): Item
{
    return Item::create([
        'name'             => $name,
        'order'            => $order,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
        'item_category_id' => $categoryName
            ? ItemCategory::firstOrCreate(['name' => $categoryName], ['order' => $order])->id
            : null,
    ]);
}

/** حركة بلحظة محدَّدة — created_at غير قابل للإسناد الجماعي فيُكتب مباشرة. */
function tblMovementAt(Warehouse $w, Item $i, string $utcTimestamp): WarehouseMovement
{
    $movement = WarehouseMovement::create([
        'warehouse_id'   => $w->id,
        'item_id'        => $i->id,
        'type'           => 'opening',
        'quantity'       => 5,
        'balance_before' => 0,
        'balance_after'  => 5,
    ]);

    WarehouseMovement::where('id', $movement->id)->update(['created_at' => $utcTimestamp]);

    return $movement->refresh();
}

// ── الترتيب ──────────────────────────────────────────────

it('يبقى على الترتيب الافتراضي ما لم يُختر عمود', function () {
    tblTwoWarehouses();
    $this->actingAs(tblUser());

    // المستوى أولاً: الرئيسي قبل الفرعي وإن خالف ذلك ترتيب الحروف
    expect(Livewire::test(ManageIndex::class)->viewData('warehouses')->pluck('name')->all())
        ->toBe(['يونيو الرئيسي', 'أسوان الفرعي']);
});

it('يرتّب تصاعدياً بالضغطة الأولى وتنازلياً بالثانية', function () {
    tblTwoWarehouses();
    $this->actingAs(tblUser());

    $component = Livewire::test(ManageIndex::class)->call('sort', 'name');

    expect($component->get('sortDir'))->toBe('asc')
        ->and($component->viewData('warehouses')->pluck('name')->all())
        ->toBe(['أسوان الفرعي', 'يونيو الرئيسي']);

    $component->call('sort', 'name');

    expect($component->get('sortDir'))->toBe('desc')
        ->and($component->viewData('warehouses')->pluck('name')->all())
        ->toBe(['يونيو الرئيسي', 'أسوان الفرعي']);
});

it('يعود للترتيب الافتراضي بالضغطة الثالثة', function () {
    tblTwoWarehouses();
    $this->actingAs(tblUser());

    $component = Livewire::test(ManageIndex::class)
        ->call('sort', 'name')   // تصاعدي
        ->call('sort', 'name')   // تنازلي
        ->call('sort', 'name');  // الافتراضي

    expect($component->get('sortBy'))->toBe('')
        ->and($component->viewData('warehouses')->pluck('name')->all())
        ->toBe(['يونيو الرئيسي', 'أسوان الفرعي']);
});

it('يتجاهل عمود ترتيب خارج القائمة البيضاء يصل من الرابط', function () {
    tblTwoWarehouses();
    $this->actingAs(tblUser());

    $rows = Livewire::withQueryParams(['sort' => 'warehouses.name) --', 'dir' => 'desc'])
        ->test(ManageIndex::class)
        ->viewData('warehouses');

    // لا انهيار ولا ترتيب بعمود مدسوس: الشاشة على ترتيبها الافتراضي
    expect($rows->pluck('name')->all())->toBe(['يونيو الرئيسي', 'أسوان الفرعي']);
});

it('لا يستجيب لطلب ترتيب بعمود غير مسموح', function () {
    tblTwoWarehouses();
    $this->actingAs(tblUser());

    Livewire::test(ManageIndex::class)
        ->call('sort', 'is_active_secret')
        ->assertSet('sortBy', '')
        ->assertSet('sortDir', 'asc');
});

it('يرتّب الوارد بعدد أصنافه', function () {
    [$main] = tblTwoWarehouses();
    $this->actingAs(tblUser());

    $one = WarehouseIncoming::create(['warehouse_id' => $main->id, 'received_at' => '2026-08-01', 'created_by' => auth()->id(), 'attachment_path' => 'x.pdf', 'attachment_original_name' => 'x.pdf']);
    $two = WarehouseIncoming::create(['warehouse_id' => $main->id, 'received_at' => '2026-08-02', 'created_by' => auth()->id(), 'attachment_path' => 'x.pdf', 'attachment_original_name' => 'x.pdf']);

    $itemA = tblItem('حبر');
    $itemB = tblItem('ورق');
    WarehouseIncomingItem::create(['warehouse_incoming_id' => $one->id, 'item_id' => $itemA->id, 'quantity' => 1]);
    WarehouseIncomingItem::create(['warehouse_incoming_id' => $two->id, 'item_id' => $itemA->id, 'quantity' => 1]);
    WarehouseIncomingItem::create(['warehouse_incoming_id' => $two->id, 'item_id' => $itemB->id, 'quantity' => 1]);

    $rows = Livewire::test(IncomingIndex::class)->call('sort', 'items_count')->viewData('incomings');

    expect($rows->pluck('items_count')->all())->toBe([1, 2]);
});

// ── عدد الصفوف ───────────────────────────────────────────

it('يطبّق عدد الصفوف الذي يختاره المستخدم', function () {
    foreach (range(1, 20) as $n) {
        tblItem('صنف '.$n);
    }
    $this->actingAs(tblUser());

    $component = Livewire::test(ItemsIndex::class);
    expect($component->viewData('items')->count())->toBe(15);

    $component->set('perPage', '50');
    expect($component->viewData('items')->count())->toBe(20);
});

it('يتجاهل عدد صفوف غير مسموح يصل من الرابط', function () {
    foreach (range(1, 20) as $n) {
        tblItem('صنف '.$n);
    }
    $this->actingAs(tblUser());

    // ٩٩٩ ليست في القائمة — يسقط للافتراضي بدل سحب الجدول كله
    $rows = Livewire::withQueryParams(['per' => '999'])->test(ItemsIndex::class)->viewData('items');

    expect($rows->count())->toBe(15);
});

// ── الفلاتر في الرابط ────────────────────────────────────

it('يقرأ فلتر المخزن من الرابط في شاشة الأرصدة', function () {
    [$main, $branch] = tblTwoWarehouses();
    $item = tblItem('حبر');
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => $item->id, 'quantity' => 7]);
    WarehouseStock::create(['warehouse_id' => $branch->id, 'item_id' => $item->id, 'quantity' => 3]);
    $this->actingAs(tblUser());

    $rows = Livewire::withQueryParams(['wh' => (string) $main->id])->test(Stock::class)->viewData('stocks');

    expect($rows->pluck('quantity')->all())->toBe([7]);
});

it('يقرأ كلمة البحث من الرابط في شاشة الأصناف', function () {
    tblItem('حبر توشيبا');
    tblItem('ورق تصوير');
    $this->actingAs(tblUser());

    $rows = Livewire::withQueryParams(['q' => 'حبر'])->test(ItemsIndex::class)->viewData('items');

    expect($rows->pluck('name')->all())->toBe(['حبر توشيبا']);
});

it('يتجاهل معرّف مخزن تالفاً في الرابط بدل إفراغ الشاشة', function () {
    [$main, $branch] = tblTwoWarehouses();
    $item = tblItem('حبر');
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => $item->id, 'quantity' => 7]);
    WarehouseStock::create(['warehouse_id' => $branch->id, 'item_id' => $item->id, 'quantity' => 3]);
    $this->actingAs(tblUser());

    $rows = Livewire::withQueryParams(['wh' => 'المخزن'])->test(Stock::class)->viewData('stocks');

    expect($rows->total())->toBe(2);
});

it('يتجاهل نوع حركة تالفاً يصل من الرابط', function () {
    [$main] = tblTwoWarehouses();
    tblMovementAt($main, tblItem('حبر'), '2026-08-26 09:00:00');
    $this->actingAs(tblUser());

    $rows = Livewire::withQueryParams(['type' => 'opening OR 1=1'])->test(Movements::class)->viewData('movements');

    expect($rows->total())->toBe(1);
});

it('يمسح كل الفلاتر بزر واحد', function () {
    [$main] = tblTwoWarehouses();
    tblMovementAt($main, tblItem('حبر'), '2026-08-26 09:00:00');
    $this->actingAs(tblUser());

    $component = Livewire::test(Movements::class)
        ->set('search', 'لا شيء')
        ->set('typeFilter', 'incoming')
        ->set('dateFrom', '2020-01-01');

    expect($component->viewData('movements')->total())->toBe(0);

    $component->call('resetFilters');

    expect($component->get('search'))->toBe('')
        ->and($component->get('dateFrom'))->toBe('')
        ->and($component->viewData('movements')->total())->toBe(1);
});

// ── الفترة وفارق التوقيت ─────────────────────────────────

it('يُدخل حركة الواحدة فجراً بتوقيت القاهرة في فلتر يومها', function () {
    // ٢٢:٣٠ بـUTC = الواحدة والنصف فجر اليوم التالي بالقاهرة (+٣ صيفاً)
    [$main] = tblTwoWarehouses();
    tblMovementAt($main, tblItem('حبر'), '2026-08-25 22:30:00');
    $this->actingAs(tblUser());

    $rows = Livewire::test(Movements::class)
        ->set('dateFrom', '2026-08-26')
        ->set('dateTo', '2026-08-26')
        ->viewData('movements');

    expect($rows->total())->toBe(1);
});

it('لا يُدخل حركة فجر اليوم التالي في فلتر اليوم السابق', function () {
    [$main] = tblTwoWarehouses();
    tblMovementAt($main, tblItem('حبر'), '2026-08-25 22:30:00');
    $this->actingAs(tblUser());

    $rows = Livewire::test(Movements::class)
        ->set('dateFrom', '2026-08-25')
        ->set('dateTo', '2026-08-25')
        ->viewData('movements');

    expect($rows->total())->toBe(0);
});

it('يُدخل آخر لحظة في يوم النهاية', function () {
    // ٢٣:٤٥ بالقاهرة = ٢٠:٤٥ بـUTC في اليوم نفسه
    [$main] = tblTwoWarehouses();
    tblMovementAt($main, tblItem('حبر'), '2026-08-26 20:45:00');
    $this->actingAs(tblUser());

    $rows = Livewire::test(Movements::class)
        ->set('dateTo', '2026-08-26')
        ->viewData('movements');

    expect($rows->total())->toBe(1);
});

it('يفلتر الوارد بيوم كتبه المستخدم بلا إزاحة توقيت', function () {
    [$main] = tblTwoWarehouses();
    $this->actingAs(tblUser());
    WarehouseIncoming::create(['warehouse_id' => $main->id, 'received_at' => '2026-08-26', 'created_by' => auth()->id(), 'attachment_path' => 'x.pdf', 'attachment_original_name' => 'x.pdf']);

    $rows = Livewire::test(IncomingIndex::class)
        ->set('dateFrom', '2026-08-26')
        ->set('dateTo', '2026-08-26')
        ->viewData('incomings');

    expect($rows->total())->toBe(1);
});

it('يتجاهل تاريخاً تالفاً يصل من الرابط', function () {
    [$main] = tblTwoWarehouses();
    tblMovementAt($main, tblItem('حبر'), '2026-08-26 09:00:00');
    $this->actingAs(tblUser());

    $rows = Livewire::withQueryParams(['from' => 'أمس'])->test(Movements::class)->viewData('movements');

    expect($rows->total())->toBe(1);
});

it('يضبط الفترة من اختصار جاهز ويلغيها بضغطة ثانية', function () {
    $this->actingAs(tblUser());
    $startOfMonth = CarbonImmutable::now(LocalTime::timezone())->startOfMonth()->toDateString();

    $component = Livewire::test(Movements::class)->call('setPeriod', 'this_month');

    expect($component->get('dateFrom'))->toBe($startOfMonth)
        ->and($component->instance()->activePeriod())->toBe('this_month');

    $component->call('setPeriod', 'this_month');

    expect($component->get('dateFrom'))->toBe('')
        ->and($component->get('dateTo'))->toBe('');
});

it('يعرض شاشة النقل ويرتّبها بنوع المستند', function () {
    [$main, $branch] = tblTwoWarehouses();
    $this->actingAs(tblUser());

    foreach ([['استمارة نقل عهدة', '2026-08-01'], ['إذن صرف', '2026-08-02']] as [$doc, $day]) {
        \App\Models\WarehouseTransfer::create([
            'from_warehouse_id'        => $main->id,
            'to_warehouse_id'          => $branch->id,
            'transferred_at'           => $day,
            'document_type'            => $doc,
            'attachment_path'          => 'x.pdf',
            'attachment_original_name' => 'x.pdf',
            'created_by'               => auth()->id(),
        ]);
    }

    $component = Livewire::test(\App\Livewire\Warehouses\Transfers\Index::class)
        ->assertOk()
        ->call('sort', 'document_type');

    expect($component->viewData('transfers')->pluck('document_type')->all())
        ->toBe(['إذن صرف', 'استمارة نقل عهدة']);
});

it('يُظهر زر مسح الفلاتر عند وجود فلتر ويخفيه بدونه', function () {
    tblTwoWarehouses();
    $this->actingAs(tblUser());

    Livewire::test(ManageIndex::class)
        ->assertDontSee(__('home.reset_filters'))
        ->set('search', 'أسوان')
        ->assertSee(__('home.reset_filters'));
});

it('يعرض منتقي عدد الصفوف ورؤوس الأعمدة القابلة للترتيب', function () {
    tblTwoWarehouses();
    $this->actingAs(tblUser());

    Livewire::test(ManageIndex::class)
        ->assertSeeHtml('wire:model.live="perPage"')
        ->assertSeeHtml('wire:click="sort(\'name\')"');
});

// ── فلاتر شاشة الأرصدة العامة ────────────────────────────

function tblStockedItem(string $name, ?string $code = null, ?int $minStock = null): Item
{
    return Item::create([
        'name'         => $name,
        'code'         => $code,
        'min_stock'    => $minStock,
        'item_unit_id' => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
    ]);
}

it('يفلتر الأرصدة بالوحدة ويتجاهل قيمة تالفة', function () {
    [$main] = tblTwoWarehouses();
    $book = ItemUnit::firstOrCreate(['name' => 'دفتر']);
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblStockedItem('حبر')->id, 'quantity' => 5]);
    WarehouseStock::create([
        'warehouse_id' => $main->id,
        'item_id'      => Item::create(['name' => 'دفتر يومية', 'item_unit_id' => $book->id])->id,
        'quantity'     => 9,
    ]);
    $this->actingAs(tblUser());

    $component = Livewire::test(Stock::class);

    expect($component->set('unitFilter', (string) $book->id)->viewData('stocks')->pluck('quantity')->all())->toBe([9])
        ->and($component->set('unitFilter', 'دفتر')->viewData('stocks')->total())->toBe(2);
});

it('يفصل الرصيد الصفر عن الموجب في شاشة الأرصدة', function () {
    [$main] = tblTwoWarehouses();
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblStockedItem('حبر')->id, 'quantity' => 0]);
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblStockedItem('ورق')->id, 'quantity' => 12]);
    $this->actingAs(tblUser());

    $component = Livewire::test(Stock::class);

    expect($component->set('balanceFilter', 'zero')->viewData('stocks')->pluck('quantity')->all())->toBe([0])
        ->and($component->set('balanceFilter', 'positive')->viewData('stocks')->pluck('quantity')->all())->toBe([12]);
});

it('يقصر فلتر الحد الأدنى على صفوف المخازن الرئيسية', function () {
    // ⚠️ الشرط على نوع مخزن الصف نفسه — صفٌّ في فرعٍ تحت الحد لا يُعدّ تنبيهاً
    [$main, $branch] = tblTwoWarehouses();
    $item = tblStockedItem('حبر', null, 10);
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => $item->id, 'quantity' => 3]);
    WarehouseStock::create(['warehouse_id' => $branch->id, 'item_id' => $item->id, 'quantity' => 2]);
    $this->actingAs(tblUser());

    $rows = Livewire::test(Stock::class)->set('lowOnly', true)->viewData('stocks');

    expect($rows->total())->toBe(1)
        ->and($rows->first()->warehouse_id)->toBe($main->id);
});

it('يستثني الأصناف بلا حدّ أدنى من فلتر الحد الأدنى', function () {
    [$main] = tblTwoWarehouses();
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblStockedItem('حبر', null, 10)->id, 'quantity' => 3]);
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblStockedItem('دبابيس', null, null)->id, 'quantity' => 1]);
    $this->actingAs(tblUser());

    $rows = Livewire::test(Stock::class)->set('lowOnly', true)->viewData('stocks');

    expect($rows->pluck('quantity')->all())->toBe([3]);
});

it('يبحث في الأرصدة برقم الصنف بأرقام إنجليزية أو هندية', function () {
    // الرقم مخزَّن بأرقام هندية، والموظف قد يكتبه بالإنجليزية
    [$main] = tblTwoWarehouses();
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblStockedItem('ملف خاص', '٥٤ ق')->id, 'quantity' => 5]);
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblStockedItem('ورق تصوير', '٧٧')->id, 'quantity' => 9]);
    $this->actingAs(tblUser());

    $component = Livewire::test(Stock::class);

    expect($component->set('search', '54')->viewData('stocks')->pluck('quantity')->all())->toBe([5])
        ->and($component->set('search', '٥٤')->viewData('stocks')->pluck('quantity')->all())->toBe([5]);
});

// ── ترتيب الدفتر في عروض الأصناف ─────────────────────────

/** صنفٌ في قسمٍ برقمٍ معيّن — لاختبار ترتيب الدفتر لا الأبجدي. */
function tblOrderedItem(string $name, ?string $categoryName, int $categoryOrder = 1, int $itemOrder = 1): Item
{
    return Item::create([
        'name'             => $name,
        'order'            => $itemOrder,
        'item_unit_id'     => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
        'item_category_id' => $categoryName
            ? \App\Models\ItemCategory::firstOrCreate(['name' => $categoryName], ['order' => $categoryOrder])->id
            : null,
    ]);
}

it('يرتّب الأرصدة بترتيب الدفتر لا أبجدياً', function () {
    // «ياء» في القسم الأول و«ألف» في الثاني — الأبجدي يعكسهما
    [$main] = tblTwoWarehouses();
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblOrderedItem('ياء', 'مخزن التصوير', 1)->id, 'quantity' => 1]);
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblOrderedItem('ألف', 'مخزن المستديم', 2)->id, 'quantity' => 2]);
    $this->actingAs(tblUser());

    expect(Livewire::test(Stock::class)->viewData('stocks')->pluck('item.name')->all())
        ->toBe(['ياء', 'ألف']);
});

it('يحترم ترتيب الصنف داخل قسمه قبل اسمه', function () {
    [$main] = tblTwoWarehouses();
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblOrderedItem('ياء', 'مخزن التصوير', 1, 1)->id, 'quantity' => 1]);
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblOrderedItem('ألف', 'مخزن التصوير', 1, 2)->id, 'quantity' => 2]);
    $this->actingAs(tblUser());

    expect(Livewire::test(Stock::class)->viewData('stocks')->pluck('item.name')->all())
        ->toBe(['ياء', 'ألف']);
});

it('يضع الأصناف بلا قسم في آخر الأرصدة', function () {
    [$main] = tblTwoWarehouses();
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblOrderedItem('ألف بلا قسم', null)->id, 'quantity' => 1]);
    WarehouseStock::create(['warehouse_id' => $main->id, 'item_id' => tblOrderedItem('ياء بقسم', 'مخزن التصوير', 1)->id, 'quantity' => 2]);
    $this->actingAs(tblUser());

    expect(Livewire::test(Stock::class)->viewData('stocks')->pluck('item.name')->all())
        ->toBe(['ياء بقسم', 'ألف بلا قسم']);
});

it('يرتّب منسدلة أصناف سجل الحركات بترتيب الدفتر', function () {
    tblOrderedItem('ياء', 'مخزن التصوير', 1);
    tblOrderedItem('ألف', 'مخزن المستديم', 2);
    $this->actingAs(tblUser());

    expect(Livewire::test(Movements::class)->viewData('items')->pluck('name')->all())
        ->toBe(['ياء', 'ألف']);
});
