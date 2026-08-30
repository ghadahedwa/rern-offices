<?php

use App\Livewire\Warehouses\Issues\Create as IssueCreate;
use App\Livewire\Warehouses\Issues\Index as IssueIndex;
use App\Models\Governorate;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseIssue;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use App\Models\WarehouseType;
use App\Support\WarehouseLedger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * الصرف إلى مقر — النوع الخامس من الحركة.
 *
 * ⚠️ العلّة التي يسدّها: الأنواع الأربعة السابقة **ليس فيها ما يُنقص مخزناً
 *    مستقبِلاً**، فرصيد مخزن المحافظة كان مجموع ما وصله منذ نشأته لا ما فيه.
 *    ووصفه العميل: «يودّي الكمبيوتر لفرع شبين القناطر… يخصم من الرصيد».
 */
function isoUser(array $permissions, string $role, array $warehouses = [], bool $all = false): User
{
    foreach ($permissions as $name) {
        Permission::findOrCreate($name, 'web');
    }

    Role::findOrCreate($role, 'web')->syncPermissions($permissions);

    $user = tap(User::factory()->create(['all_warehouses' => $all]))->assignRole($role);
    $user->warehouses()->sync(collect($warehouses)->pluck('id')->all());

    return $user->fresh();
}

function isoWarehouse(string $name, ?Governorate $gov = null, int $level = 3): Warehouse
{
    return Warehouse::create([
        'name'              => $name,
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'نوع '.$level], ['level' => $level, 'order' => $level])->id,
        'governorate_id'    => $gov?->id,
        'is_active'         => true,
    ]);
}

function isoOffice(string $name, Governorate $gov): Office
{
    return Office::create([
        'name'           => $name,
        'governorate_id' => $gov->id,
        'type_id'        => OfficeType::firstOrCreate(['name' => 'توثيق'])->id,
    ]);
}

function isoItem(string $name = 'كمبيوتر'): Item
{
    return Item::create([
        'name'         => $name,
        'item_unit_id' => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
    ]);
}

function isoStock(Warehouse $w, Item $i, int $qty): WarehouseStock
{
    return WarehouseStock::create(['warehouse_id' => $w->id, 'item_id' => $i->id, 'quantity' => $qty]);
}

function isoIssue(Warehouse $w, Office $o, Item $i, int $qty): WarehouseIssue
{
    $issue = WarehouseIssue::create([
        'warehouse_id'             => $w->id,
        'office_id'                => $o->id,
        'issued_at'                => '2026-08-20',
        'attachment_path'          => 'x.pdf',
        'attachment_original_name' => 'x.pdf',
    ]);

    $issue->items()->create(['item_id' => $i->id, 'quantity' => $qty]);
    WarehouseLedger::recordIssue($issue->fresh('items'));

    return $issue->fresh();
}

// ── القلب: الخصم والسجل ──────────────────────────────────

it('ينقص رصيد المخزن ويسجّل حركة صرف', function () {
    $gov  = Governorate::create(['name' => 'القليوبية', 'order' => 1]);
    $wh   = isoWarehouse('بنها', $gov);
    $off  = isoOffice('فرع توثيق شبين القناطر', $gov);
    $item = isoItem();
    isoStock($wh, $item, 40);

    isoIssue($wh, $off, $item, 5);

    $movement = WarehouseMovement::where('type', 'issue')->first();

    expect(WarehouseStock::where('warehouse_id', $wh->id)->value('quantity'))->toBe(35)
        ->and($movement)->not->toBeNull()
        ->and($movement->quantity)->toBe(5)
        ->and($movement->balance_before)->toBe(40)
        ->and($movement->balance_after)->toBe(35);
});

it('لا يزيد أي مخزن آخر — المقر ليس مخزناً', function () {
    $gov   = Governorate::create(['name' => 'القليوبية', 'order' => 1]);
    $wh    = isoWarehouse('بنها', $gov);
    $other = isoWarehouse('مخزن آخر', $gov);
    $off   = isoOffice('فرع توثيق شبين القناطر', $gov);
    $item  = isoItem();
    isoStock($wh, $item, 40);

    isoIssue($wh, $off, $item, 5);

    // ⚠️ هذا ما يفرّق الصرف عن النقل: خصمٌ بلا إضافة في أي مكان
    expect(WarehouseStock::where('warehouse_id', $other->id)->count())->toBe(0)
        ->and(WarehouseMovement::count())->toBe(1);
});

it('يرفض الصرف حين لا يكفي الرصيد ولا يمسّه', function () {
    $gov  = Governorate::create(['name' => 'القليوبية', 'order' => 1]);
    $wh   = isoWarehouse('بنها', $gov);
    $off  = isoOffice('مقر', $gov);
    $item = isoItem();
    isoStock($wh, $item, 3);

    expect(fn () => isoIssue($wh, $off, $item, 5))
        ->toThrow(\App\Exceptions\WarehouseException::class);

    expect(WarehouseStock::where('warehouse_id', $wh->id)->value('quantity'))->toBe(3)
        ->and(WarehouseMovement::count())->toBe(0);
});

it('يردّ الرصيد ويحذف الحركة عند الحذف بإرجاع', function () {
    $gov  = Governorate::create(['name' => 'القليوبية', 'order' => 1]);
    $wh   = isoWarehouse('بنها', $gov);
    $off  = isoOffice('مقر', $gov);
    $item = isoItem();
    isoStock($wh, $item, 40);

    $issue = isoIssue($wh, $off, $item, 5);
    WarehouseLedger::reverseIssue($issue->fresh('items'));

    expect(WarehouseStock::where('warehouse_id', $wh->id)->value('quantity'))->toBe(40)
        ->and(WarehouseMovement::count())->toBe(0)
        ->and(WarehouseIssue::count())->toBe(0);
});

// ── قائمة المقرات ────────────────────────────────────────

it('يقصر المقرات على محافظة المخزن المختار', function () {
    $qena  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $aswan = Governorate::create(['name' => 'أسوان', 'order' => 2]);
    $wh    = isoWarehouse('مخزن قنا', $qena);
    isoOffice('مقر قنا', $qena);
    isoOffice('مقر أسوان', $aswan);

    $this->actingAs(isoUser(['warehouses.index', 'warehouses.issue'], 'iso-op', [$wh]));

    $offices = Livewire::test(IssueCreate::class)
        ->set('warehouse_id', $wh->id)
        ->viewData('offices');

    expect($offices->pluck('name')->all())->toBe(['مقر قنا']);
});

it('يعرض المقرات كلها للمخزن بلا محافظة — الرئيسي يخدم القطر', function () {
    $qena  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $aswan = Governorate::create(['name' => 'أسوان', 'order' => 2]);
    $main  = isoWarehouse('المخزن الرئيسي بالمصلحة', null, 1);
    isoOffice('مقر قنا', $qena);
    isoOffice('مقر أسوان', $aswan);

    $this->actingAs(isoUser(['warehouses.index', 'warehouses.issue'], 'iso-main', [$main]));

    $offices = Livewire::test(IssueCreate::class)
        ->set('warehouse_id', $main->id)
        ->viewData('offices');

    expect($offices->count())->toBe(2);
});

it('لا يعرض مقرات قبل اختيار المخزن', function () {
    $gov = Governorate::create(['name' => 'قنا', 'order' => 1]);
    isoOffice('مقر قنا', $gov);

    $this->actingAs(isoUser(['warehouses.index', 'warehouses.issue'], 'iso-nowh', [], all: true));

    expect(Livewire::test(IssueCreate::class)->viewData('offices')->count())->toBe(0);
});

it('يُلغي المقر المختار عند تبديل المخزن', function () {
    $qena  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $aswan = Governorate::create(['name' => 'أسوان', 'order' => 2]);
    $w1 = isoWarehouse('مخزن قنا', $qena);
    $w2 = isoWarehouse('مخزن أسوان', $aswan);
    $o1 = isoOffice('مقر قنا', $qena);

    $this->actingAs(isoUser(['warehouses.index', 'warehouses.issue'], 'iso-switch', [], all: true));

    Livewire::test(IssueCreate::class)
        ->set('warehouse_id', $w1->id)
        ->set('office_id', $o1->id)
        ->set('warehouse_id', $w2->id)
        // ⚠️ وإلا بقي مقرُّ محافظةٍ أخرى مختاراً فصُرف إليه من مخزنٍ لا يخدمه
        ->assertSet('office_id', null);
});

it('يرفض مقراً من محافظة أخرى يُدسّ في الطلب', function () {
    Storage::fake('public');

    $qena  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $aswan = Governorate::create(['name' => 'أسوان', 'order' => 2]);
    $wh    = isoWarehouse('مخزن قنا', $qena);
    $far   = isoOffice('مقر أسوان', $aswan);
    $item  = isoItem();
    isoStock($wh, $item, 40);

    $this->actingAs(isoUser(['warehouses.index', 'warehouses.issue'], 'iso-inject', [$wh]));

    Livewire::test(IssueCreate::class)
        ->set('warehouse_id', $wh->id)
        ->set('office_id', $far->id)
        ->set('lines', [['item_id' => $item->id, 'quantity' => 5]])
        ->set('attachment', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors('office_id');

    expect(WarehouseIssue::count())->toBe(0)
        ->and(WarehouseStock::where('warehouse_id', $wh->id)->value('quantity'))->toBe(40);
});

// ── الصلاحية والنطاق ─────────────────────────────────────

it('يمنع شاشة التسجيل عمّن لا warehouses.issue له', function () {
    $this->actingAs(isoUser(['warehouses.index', 'warehouses.transfer'], 'iso-notissue', [], all: true));

    Livewire::test(IssueCreate::class)->assertStatus(403);
    $this->get(route('warehouses.issues.create'))->assertForbidden();
});

it('يفتح السجل لصاحب القراءة ويخفي زر التسجيل عنه', function () {
    $this->actingAs(isoUser(['warehouses.index'], 'iso-read', [], all: true));

    Livewire::test(IssueIndex::class)
        ->assertStatus(200)
        ->assertSet('search', '');

    expect(Livewire::test(IssueIndex::class)->viewData('canCreate'))->toBeFalse();
});

it('يمنع الصرف من مخزنٍ خارج النطاق ولو دُسّ معرّفه', function () {
    Storage::fake('public');

    $gov    = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $mine   = isoWarehouse('مخزني', $gov);
    $theirs = isoWarehouse('مخزن غيري', $gov);
    $off    = isoOffice('مقر قنا', $gov);
    $item   = isoItem();
    isoStock($theirs, $item, 40);

    $this->actingAs(isoUser(['warehouses.index', 'warehouses.issue'], 'iso-scope', [$mine]));

    Livewire::test(IssueCreate::class)
        ->set('warehouse_id', $theirs->id)
        ->set('office_id', $off->id)
        ->set('lines', [['item_id' => $item->id, 'quantity' => 5]])
        ->set('attachment', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertStatus(403);

    expect(WarehouseStock::where('warehouse_id', $theirs->id)->value('quantity'))->toBe(40);
});

it('يقصر سجل الصرف على مخازن المستخدم', function () {
    $gov    = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $mine   = isoWarehouse('مخزني', $gov);
    $theirs = isoWarehouse('مخزن غيري', $gov);
    $off    = isoOffice('مقر قنا', $gov);
    $item   = isoItem();
    isoStock($mine, $item, 40);
    isoStock($theirs, $item, 40);
    isoIssue($mine, $off, $item, 3);
    isoIssue($theirs, $off, $item, 7);

    $this->actingAs(isoUser(['warehouses.index'], 'iso-list-scope', [$mine]));

    $rows = Livewire::test(IssueIndex::class)->viewData('issues');

    expect($rows->count())->toBe(1)
        ->and($rows->first()->warehouse_id)->toBe($mine->id);
});

it('يمنع عرض وحذف صرفٍ خارج النطاق', function () {
    $gov    = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $mine   = isoWarehouse('مخزني', $gov);
    $theirs = isoWarehouse('مخزن غيري', $gov);
    $off    = isoOffice('مقر قنا', $gov);
    $item   = isoItem();
    isoStock($theirs, $item, 40);
    $issue = isoIssue($theirs, $off, $item, 7);

    $this->actingAs(isoUser(['warehouses.index', 'warehouses.delete'], 'iso-guard', [$mine]));

    Livewire::test(IssueIndex::class)->call('view', $issue->id)->assertStatus(403);
    Livewire::test(IssueIndex::class)->call('askDelete', $issue->id)->assertStatus(403);
});

// ── البحث والفلاتر ───────────────────────────────────────

it('يبحث باسم المقر بحثاً عربياً مطبَّعاً', function () {
    $gov  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $wh   = isoWarehouse('مخزن قنا', $gov);
    $o1   = isoOffice('فرع توثيق أسيوط', $gov);
    $o2   = isoOffice('فرع توثيق سوهاج', $gov);
    $item = isoItem();
    isoStock($wh, $item, 40);
    isoIssue($wh, $o1, $item, 1);
    isoIssue($wh, $o2, $item, 2);

    $this->actingAs(isoUser(['warehouses.index'], 'iso-search', [], all: true));

    $rows = Livewire::test(IssueIndex::class)->set('search', 'اسيوط')->viewData('issues');

    expect($rows->count())->toBe(1)
        ->and($rows->first()->office_id)->toBe($o1->id);
});

it('يهمل معرّف مخزنٍ تالفاً يصل من الرابط', function () {
    $gov  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $wh   = isoWarehouse('مخزن قنا', $gov);
    $off  = isoOffice('مقر', $gov);
    $item = isoItem();
    isoStock($wh, $item, 40);
    isoIssue($wh, $off, $item, 1);

    $this->actingAs(isoUser(['warehouses.index'], 'iso-badfilter', [], all: true));

    expect(Livewire::test(IssueIndex::class)->set('warehouseFilter', 'abc')->viewData('issues')->count())->toBe(1);
});

// ── تكامل الحركة مع بقية الشاشات ─────────────────────────

it('يُدرج نوع «صرف لمقر» في قائمة أنواع الحركة المعروفة', function () {
    expect(WarehouseMovement::TYPES)->toContain('issue')
        // المصدر الواحد: الشاشات الثلاث تقرأ من الموديل لا من نسخها
        ->and(\App\Livewire\Warehouses\Movements::TYPES)->toBe(WarehouseMovement::TYPES)
        ->and(\App\Livewire\Warehouses\Items\Show::MOVEMENT_TYPES)->toBe(WarehouseMovement::TYPES)
        ->and(\App\Livewire\Warehouses\Manage\Show::MOVEMENT_TYPES)->toBe(WarehouseMovement::TYPES);
});

it('يظهر الصرف في سجل الحركات العام وفي صفحة الصنف', function () {
    $gov  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $wh   = isoWarehouse('مخزن قنا', $gov);
    $off  = isoOffice('مقر', $gov);
    $item = isoItem();
    isoStock($wh, $item, 40);
    isoIssue($wh, $off, $item, 5);

    $this->actingAs(isoUser(['warehouses.index'], 'iso-integration', [], all: true));

    $movements = Livewire::test(\App\Livewire\Warehouses\Movements::class)
        ->set('typeFilter', 'issue')
        ->viewData('movements');

    expect($movements->count())->toBe(1)
        ->and($movements->first()->type)->toBe('issue');

    // وأثره على الرصيد ظاهر في صفحة الصنف
    $balances = Livewire::test(\App\Livewire\Warehouses\Items\Show::class, ['item' => $item])
        ->viewData('balances');

    expect((int) $balances->firstWhere('id', $wh->id)->stock_quantity)->toBe(35);
});
