<?php

use App\Livewire\Warehouses\Incoming\Index as IncomingIndex;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseIncoming;
use App\Models\WarehouseType;
use App\Support\WarehouseScope;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * إظهار بند «الوارد» — `WarehouseScope::hasMainWarehouse()`.
 *
 * الوارد توريدُ مورّدٍ من خارج المنظومة، ولا يُسجَّل إلا على المخزن الرئيسي.
 * فمَن لا رئيسيَّ في نطاقه (المفتش) شاشتُه **فارغة أبداً** لا فارغة اليوم،
 * وبندٌ كهذا يعلّمه أن المنظومة معطّلة لا أن هذا ليس عمله. وما يصل مخزنَه
 * يصله **نقلاً**، ويراه في شاشة النقل بفلتر «وارد إلى مخازني».
 *
 * ⚠️ والمقياس **النطاق لا الصلاحية**: قد يأتي دورٌ يطالع الوارد ولا يسجّله.
 */
function ivUser(array $warehouses, bool $all = false, array $extra = []): User
{
    $abilities = array_merge(['warehouses.index'], $extra);

    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('iv-role-'.md5(implode(',', $abilities)), 'web');
    $role->syncPermissions($abilities);

    $user = tap(User::factory()->create(['all_warehouses' => $all]))->assignRole($role);
    $user->warehouses()->sync(collect($warehouses)->pluck('id')->all());

    return $user->fresh();
}

function ivWarehouse(string $name, int $level): Warehouse
{
    return Warehouse::create([
        'name'              => $name,
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'نوع '.$level], ['level' => $level, 'order' => $level])->id,
        'is_active'         => true,
    ]);
}

// ── القاعدة ──────────────────────────────────────────────

it('يعدّ النطاق المفتوح مالكاً للرئيسي — ويدخله الجديد تلقائياً', function () {
    expect(WarehouseScope::hasMainWarehouse(ivUser([], all: true)))->toBeTrue();
});

it('لا يعدّ صاحب المخزن الفرعي مالكاً للرئيسي', function () {
    ivWarehouse('المخزن الرئيسي', 1); // موجود في المنظومة — وليس في نطاقه

    expect(WarehouseScope::hasMainWarehouse(ivUser([ivWarehouse('بنها', 3)])))->toBeFalse();
});

it('يعدّ صاحب الرئيسي مالكاً له ولو كان معه فرعي', function () {
    $main = ivWarehouse('المخزن الرئيسي', 1);
    $sub  = ivWarehouse('بنها', 3);

    expect(WarehouseScope::hasMainWarehouse(ivUser([$sub, $main])))->toBeTrue();
});

it('لا يعدّ مَن لا مخزن له مالكاً للرئيسي', function () {
    ivWarehouse('المخزن الرئيسي', 1);

    // ⚠️ `[]` لا يرى شيئاً — والفراغ ليس إذناً
    expect(WarehouseScope::hasMainWarehouse(ivUser([])))->toBeFalse();
});

// ── المنيو ───────────────────────────────────────────────

it('يُخفي بند الوارد عن المفتش ويُبقي بقية بنود الفرع', function () {
    ivWarehouse('المخزن الرئيسي', 1);
    $this->actingAs(ivUser([ivWarehouse('بنها', 3)]));

    $page = $this->get(route('warehouses.stock'))->assertOk();

    // ⚠️ بند النقل شاهدٌ على أن السايدبار رُسم أصلاً — بلا هذا يمرّ
    //    assertDontSee على صفحةٍ فارغة فيبدو الإخفاء ناجحاً وهو لم يقع
    $page->assertSee(route('warehouses.transfers.index'), false)
        ->assertDontSee(route('warehouses.incoming.index'), false);
});

it('يُظهر بند الوارد لصاحب المخزن الرئيسي', function () {
    $this->actingAs(ivUser([ivWarehouse('المخزن الرئيسي', 1)]));

    $this->get(route('warehouses.stock'))
        ->assertOk()
        ->assertSee(route('warehouses.incoming.index'), false);
});

// ── الشاشة ───────────────────────────────────────────────

it('يُبقي شاشة الوارد مفتوحةً لمن أُخفي عنه البند — فارغةً لا محجوبة', function () {
    $main = ivWarehouse('المخزن الرئيسي', 1);
    WarehouseIncoming::create([
        'warehouse_id'             => $main->id,
        'received_at'              => '2026-08-20',
        'supplier_name'            => 'مورّد',
        'attachment_path'          => 'x.pdf',
        'attachment_original_name' => 'x.pdf',
    ]);

    $this->actingAs(ivUser([ivWarehouse('بنها', 3)]));

    // البند يُخفى ولا يُغلق الرابط: رابطٌ محفوظ يُخرج شاشةً فارغة لا ٤٠٣،
    // والنطاق هو الذي يحجب الصفوف كما يحجبها في كل شاشة أخرى
    expect(Livewire::test(IncomingIndex::class)->viewData('incomings')->count())->toBe(0);
});

// ── ترتيب القائمة ومجموعاتها ─────────────────────────────

it('يعرض عناوين مجموعات القائمة بترتيب إيقاع العمل', function () {
    $this->actingAs(ivUser([ivWarehouse('المخزن الرئيسي', 1)], all: true, extra: ['warehouses.export', 'warehouses.opening']));

    $html = $this->get(route('warehouses.stock'))->assertOk()->getContent();

    // ⚠️ الترتيب نفسه هو المطلوب لا مجرّد الوجود: ما عندي ← ما تحرّك ← ما أُخرِجه
    $positions = collect(['الأرصدة', 'الحركات', 'مخرجات وضبط'])
        ->map(fn ($heading) => mb_strpos($html, '>'.$heading.'<'));

    expect($positions->every(fn ($p) => $p !== false))->toBeTrue()
        ->and($positions->all())->toBe($positions->sort()->values()->all());
});

it('لا يعرض عنوان «مخرجات وضبط» لمن لا بند له تحته', function () {
    // ⚠️ عنوانٌ بلا بنود أسوأ من قائمة مسطّحة — والمفتش بلا export ولا opening
    //    في هذا الاختبار، فالمجموعة كلها تسقط لا عنوانها وحده
    ivWarehouse('المخزن الرئيسي', 1);
    $this->actingAs(ivUser([ivWarehouse('بنها', 3)]));

    $this->get(route('warehouses.stock'))
        ->assertOk()
        ->assertSee('>الأرصدة<', false)
        ->assertDontSee('>مخرجات وضبط<', false);
});
