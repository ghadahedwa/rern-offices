<?php

use App\Livewire\Warehouses\DocumentAttachments;
use App\Livewire\Warehouses\Issues\Create as IssueCreate;
use App\Livewire\Warehouses\Manage\Show as WarehouseShow;
use App\Models\Governorate;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Office;
use App\Models\OfficeType;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseAttachment;
use App\Models\WarehouseIncoming;
use App\Models\WarehouseIssue;
use App\Models\WarehouseStock;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseType;
use App\Support\WarehouseLedger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * مرفقات مستندات المخازن: حتى خمسة للمستند، واحدٌ على الأقل إجباري،
 * وتُضاف بعد الحفظ لمن ينشئ النوع نفسه.
 */
function mattUser(array $permissions, array $warehouses = [], bool $all = false): User
{
    foreach ($permissions as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $role = 'matt-' . md5(implode(',', $permissions));
    Role::findOrCreate($role, 'web')->syncPermissions($permissions);

    $user = tap(User::factory()->create(['all_warehouses' => $all]))->assignRole($role);
    $user->warehouses()->sync(collect($warehouses)->pluck('id')->all());

    return $user->fresh();
}

function mattWarehouse(string $name, int $level = 3): Warehouse
{
    return Warehouse::create([
        'name'              => $name,
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'نوع '.$level], ['level' => $level, 'order' => $level])->id,
        'governorate_id'    => Governorate::firstOrCreate(['name' => 'قنا'], ['order' => 1])->id,
        'is_active'         => true,
    ]);
}

function mattItem(Warehouse $w, int $qty): Item
{
    $item = Item::create(['name' => 'كمبيوتر', 'item_unit_id' => ItemUnit::firstOrCreate(['name' => 'قطعة'])->id]);
    WarehouseStock::create(['warehouse_id' => $w->id, 'item_id' => $item->id, 'quantity' => $qty]);

    return $item;
}

function mattOffice(): Office
{
    return Office::create([
        'name'           => 'مقر قنا',
        'governorate_id' => Governorate::firstOrCreate(['name' => 'قنا'], ['order' => 1])->id,
        'type_id'        => OfficeType::firstOrCreate(['name' => 'توثيق'])->id,
    ]);
}

function mattPdf(string $name = 'doc.pdf'): UploadedFile
{
    return UploadedFile::fake()->create($name, 100, 'application/pdf');
}

/** مستند صرف محفوظ ومعه عددٌ من المرفقات */
function mattIssue(Warehouse $w, int $attachments = 1): WarehouseIssue
{
    $issue = WarehouseIssue::create(['warehouse_id' => $w->id, 'office_id' => mattOffice()->id, 'issued_at' => '2026-09-01']);
    for ($i = 1; $i <= $attachments; $i++) {
        $issue->addAttachment(mattPdf("old-$i.pdf"));
    }

    return $issue;
}

beforeEach(fn () => Storage::fake('public'));

// ── الإنشاء ─────────────────────────────────────────────

it('يحفظ عدة مرفقات مع المستند ويخزّن ملفاتها', function () {
    $wh   = mattWarehouse('مخزن قنا');
    $item = mattItem($wh, 40);
    $this->actingAs(mattUser(['warehouses.index', 'warehouses.issue'], [$wh]));

    Livewire::test(IssueCreate::class)
        ->set('warehouse_id', $wh->id)
        ->set('office_id', mattOffice()->id)
        ->set('lines', [['item_id' => $item->id, 'quantity' => 5]])
        ->set('pickedFiles', [mattPdf('a.pdf'), UploadedFile::fake()->image('b.jpg')])
        ->set('pickedFiles', [mattPdf('c.pdf')])     // اختيارٌ ثانٍ يُضاف ولا يستبدل
        ->call('save')
        ->assertHasNoErrors();

    $issue = WarehouseIssue::sole();
    $names = $issue->attachments->pluck('original_name')->all();

    expect($names)->toBe(['a.pdf', 'b.jpg', 'c.pdf']);
    $issue->attachments->each(fn ($a) => Storage::disk('public')->assertExists($a->path));
});

it('يرفض الحفظ بلا مرفق', function () {
    $wh   = mattWarehouse('مخزن قنا');
    $item = mattItem($wh, 40);
    $this->actingAs(mattUser(['warehouses.index', 'warehouses.issue'], [$wh]));

    Livewire::test(IssueCreate::class)
        ->set('warehouse_id', $wh->id)
        ->set('office_id', mattOffice()->id)
        ->set('lines', [['item_id' => $item->id, 'quantity' => 5]])
        ->call('save')
        ->assertHasErrors('attachments');

    expect(WarehouseIssue::count())->toBe(0);
});

it('لا يقبل أكثر من خمسة ولا ملفاً من غير نوعه', function () {
    $wh = mattWarehouse('مخزن قنا');
    $this->actingAs(mattUser(['warehouses.index', 'warehouses.issue'], [$wh]));

    $c = Livewire::test(IssueCreate::class)
        ->set('pickedFiles', array_map(fn ($i) => mattPdf("f$i.pdf"), range(1, 6)))
        ->assertHasErrors('attachments');
    expect($c->get('attachments'))->toHaveCount(5)
        ->and($c->get('pickedFiles'))->toBe([]);

    $c = Livewire::test(IssueCreate::class)
        ->set('pickedFiles', [UploadedFile::fake()->create('x.txt', 5, 'text/plain'), mattPdf()])
        ->assertHasErrors('attachments');
    expect($c->get('attachments'))->toHaveCount(1);
});

it('يحذف الملفات المخزَّنة إن تراجع الحفظ', function () {
    $wh   = mattWarehouse('مخزن قنا');
    $item = mattItem($wh, 2);
    $this->actingAs(mattUser(['warehouses.index', 'warehouses.issue'], [$wh]));

    Livewire::test(IssueCreate::class)
        ->set('warehouse_id', $wh->id)
        ->set('office_id', mattOffice()->id)
        ->set('lines', [['item_id' => $item->id, 'quantity' => 5]])   // أكثر من الرصيد
        ->set('pickedFiles', [mattPdf('a.pdf'), mattPdf('b.pdf')])
        ->call('save')
        ->assertHasErrors('lines');

    expect(WarehouseIssue::count())->toBe(0)
        ->and(WarehouseAttachment::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles(WarehouseIssue::ATTACHMENT_DIR))->toBe([]);
});

it('يحذف المرفقات كلها وملفاتها مع المستند', function () {
    $wh    = mattWarehouse('مخزن قنا');
    $item  = mattItem($wh, 40);
    $issue = mattIssue($wh, 3);
    $issue->items()->create(['item_id' => $item->id, 'quantity' => 1]);
    WarehouseLedger::recordIssue($issue->fresh('items'));
    $paths = $issue->attachments->pluck('path');

    WarehouseLedger::reverseIssue($issue->fresh('items'));

    expect(WarehouseAttachment::count())->toBe(0);
    $paths->each(fn ($p) => Storage::disk('public')->assertMissing($p));
});

it('لا يخلط مرفقات الأنواع ذات المعرّف نفسه', function () {
    $wh    = mattWarehouse('مخزن قنا', 1);
    $issue = mattIssue($wh, 2);
    $in    = WarehouseIncoming::create(['warehouse_id' => $wh->id, 'received_at' => '2026-09-01']);
    $in->addAttachment(mattPdf('in.pdf'));

    expect($issue->id)->toBe($in->id)
        ->and($issue->attachments)->toHaveCount(2)
        ->and($in->attachments->pluck('original_name')->all())->toBe(['in.pdf']);

    $in->delete();
    expect($issue->fresh()->attachments)->toHaveCount(2);
});

// ── الإضافة بعد الحفظ ───────────────────────────────────

it('يضيف مرفقاً لمستندٍ محفوظ', function () {
    $wh    = mattWarehouse('مخزن قنا');
    $issue = mattIssue($wh, 1);
    $this->actingAs(mattUser(['warehouses.index', 'warehouses.attachments', 'warehouses.issue'], [$wh]));

    Livewire::test(DocumentAttachments::class, ['type' => 'issue', 'documentId' => $issue->id])
        ->assertViewHas('canAdd', true)
        ->set('pickedFiles', [mattPdf('late.pdf')])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('attachments', []);

    expect($issue->attachments()->pluck('original_name')->all())->toBe(['old-1.pdf', 'late.pdf']);
});

it('لا يتجاوز خمسة مع الموجود', function () {
    $wh    = mattWarehouse('مخزن قنا');
    $issue = mattIssue($wh, 4);
    $this->actingAs(mattUser(['warehouses.index', 'warehouses.attachments', 'warehouses.issue'], [$wh]));

    $c = Livewire::test(DocumentAttachments::class, ['type' => 'issue', 'documentId' => $issue->id])
        ->set('pickedFiles', [mattPdf('a.pdf'), mattPdf('b.pdf')])
        ->assertHasErrors('attachments');
    expect($c->get('attachments'))->toHaveCount(1);

    // ⚠️ والحارس في الحفظ لا في الاختيار وحده: مرفقٌ أُضيف من جلسةٍ أخرى بعد الاختيار
    $issue->addAttachment(mattPdf('other.pdf'));
    $c->call('save')->assertHasErrors('attachments');

    expect($issue->attachments()->count())->toBe(5);
});

it('يعرض المرفقات بلا إضافة لمن لا ينشئ النوع، ويرفض الحفظ منه', function () {
    $wh    = mattWarehouse('مخزن قنا');
    $issue = mattIssue($wh, 1);
    $this->actingAs(mattUser(['warehouses.index', 'warehouses.attachments'], [$wh]));

    Livewire::test(DocumentAttachments::class, ['type' => 'issue', 'documentId' => $issue->id])
        ->assertViewHas('canAdd', false)
        ->assertSee('old-1.pdf')
        ->set('pickedFiles', [mattPdf()])
        ->call('save')
        ->assertForbidden();

    expect($issue->attachments()->count())->toBe(1);
});

it('يمنع المرفقات عمّن لا warehouses.attachments له ولو أنشأ النوع', function () {
    $wh    = mattWarehouse('مخزن قنا');
    $issue = mattIssue($wh, 1);
    $this->actingAs(mattUser(['warehouses.index', 'warehouses.issue'], [$wh]));

    Livewire::test(DocumentAttachments::class, ['type' => 'issue', 'documentId' => $issue->id])
        ->assertForbidden();
});

it('يمنع مستنداً خارج النطاق ونوعاً مجهولاً', function () {
    $mine   = mattWarehouse('مخزني');
    $theirs = mattWarehouse('مخزن غيري');
    $issue  = mattIssue($theirs, 1);
    $this->actingAs(mattUser(['warehouses.index', 'warehouses.attachments', 'warehouses.issue'], [$mine]));

    Livewire::test(DocumentAttachments::class, ['type' => 'issue', 'documentId' => $issue->id])
        ->assertForbidden();
    Livewire::test(DocumentAttachments::class, ['type' => 'office', 'documentId' => $issue->id])
        ->assertNotFound();
});

it('يقصر الإضافة على مصدر النقل لا مستلمه', function () {
    $from = mattWarehouse('المصدر', 1);
    $to   = mattWarehouse('الوجهة');
    $t    = WarehouseTransfer::create(['from_warehouse_id' => $from->id, 'to_warehouse_id' => $to->id, 'transferred_at' => '2026-09-01']);
    $t->addAttachment(mattPdf());

    $perms = ['warehouses.index', 'warehouses.attachments', 'warehouses.transfer'];

    $this->actingAs(mattUser($perms, [$to]));
    Livewire::test(DocumentAttachments::class, ['type' => 'transfer', 'documentId' => $t->id])
        ->assertViewHas('canAdd', false);

    $this->actingAs(mattUser($perms, [$from]));
    Livewire::test(DocumentAttachments::class, ['type' => 'transfer', 'documentId' => $t->id])
        ->assertViewHas('canAdd', true);
});

it('لا يفتح بروفايل المخزن وارداً لمخزنٍ آخر', function () {
    $mine   = mattWarehouse('مخزني', 1);
    $theirs = mattWarehouse('مخزن غيري', 1);
    $in     = WarehouseIncoming::create(['warehouse_id' => $theirs->id, 'received_at' => '2026-09-01']);
    $this->actingAs(mattUser(['warehouses.index'], [], all: true));

    Livewire::test(WarehouseShow::class, ['warehouse' => $mine])
        ->call('viewIncoming', $in->id)
        ->assertForbidden();
});
