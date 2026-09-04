<?php

use App\Livewire\Warehouses\Manage\Show as WarehouseProfile;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * بروفايل المخزن — مدخلان في فرعين.
 *
 * مدير الإعدادات يدخله من «إدارة المخازن»، وصاحب المخزن من شاشة الأرصدة:
 * وهو أقرب شاشة إلى «مخزني» (أرصدته وحركاته ونقله معاً)، وكان محجوباً عنه.
 *
 * ⚠️ وحارسان لا واحد: الصلاحية تقول «له أن يطالع بروفايل مخزنٍ ما»، والنطاق
 *    يقول **أيّ** مخزن — والمعرّف يصل من الرابط لا من قائمة.
 */
function wpUser(array $abilities, array $warehouses = [], bool $all = false): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('wp-'.md5(implode(',', $abilities)), 'web');
    $role->syncPermissions($abilities);

    $user = tap(User::factory()->create(['all_warehouses' => $all]))->assignRole($role);
    $user->warehouses()->sync(collect($warehouses)->pluck('id')->all());

    return $user->fresh();
}

function wpWarehouse(string $name, int $level = 3): Warehouse
{
    return Warehouse::create([
        'name'              => $name,
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'نوع '.$level], ['level' => $level, 'order' => $level])->id,
        'governorate_id'    => Governorate::factory()->create()->id,
        'is_active'         => true,
    ]);
}

// ── الفتح والمنع ─────────────────────────────────────────

it('يفتح بروفايل مخزنه لصاحب warehouses.index وحدها', function () {
    $mine = wpWarehouse('بنها');

    $this->actingAs(wpUser(['warehouses.index'], [$mine]));

    $this->get(route('warehouse-manage.show', $mine))->assertOk();
});

it('يمنع بروفايل مخزنٍ خارج النطاق ولو ملك الصلاحية', function () {
    $mine   = wpWarehouse('بنها');
    $theirs = wpWarehouse('مخزن غيري');

    $this->actingAs(wpUser(['warehouses.index'], [$mine]));

    // ⚠️ المعرّف يصل من الرابط لا من قائمة — فالصلاحية وحدها لا تكفي
    $this->get(route('warehouse-manage.show', $theirs))->assertForbidden();
});

it('يمنع مَن لا index له ولا settings', function () {
    $w = wpWarehouse('بنها');

    $this->actingAs(wpUser(['warehouses.opening'], [$w]));

    $this->get(route('warehouse-manage.show', $w))->assertForbidden();
});

it('يفتح أي بروفايل لصاحب النطاق المفتوح', function () {
    $w = wpWarehouse('مخزن بعيد');

    $this->actingAs(wpUser(['warehouses.index'], [], all: true));

    $this->get(route('warehouse-manage.show', $w))->assertOk();
});

// ── التعديل والرجوع ──────────────────────────────────────

it('يُخفي زرّ التعديل عن صاحب المخزن الذي لا يملك الإعدادات', function () {
    $mine = wpWarehouse('بنها');

    $this->actingAs(wpUser(['warehouses.index'], [$mine]));

    // التعديل فعلُ إعداداتٍ لا فعلُ تشغيل — والرابط نفسه محجوب بصلاحيته
    $this->get(route('warehouse-manage.show', $mine))
        ->assertOk()
        ->assertDontSee(route('warehouse-manage.edit', $mine), false);
});

it('يُظهر زرّ التعديل لمدير الإعدادات', function () {
    $w = wpWarehouse('بنها');

    $this->actingAs(wpUser(['warehouses.settings'], [], all: true));

    $this->get(route('warehouse-manage.show', $w))
        ->assertOk()
        ->assertSee(route('warehouse-manage.edit', $w), false);
});

it('يُرجع كلَّ داخلٍ من حيث دخل', function () {
    $mine = wpWarehouse('بنها');

    // ⚠️ وجهةٌ ثابتة تهبط بصاحب المخزن على ٤٠٣ في شاشةٍ ليست له
    $this->actingAs(wpUser(['warehouses.index'], [$mine]));
    expect(Livewire::test(WarehouseProfile::class, ['warehouse' => $mine])->instance()->backRoute())
        ->toBe(route('warehouses.stock'));

    $this->actingAs(wpUser(['warehouses.index', 'warehouses.settings'], [], all: true));
    expect(Livewire::test(WarehouseProfile::class, ['warehouse' => $mine])->instance()->backRoute())
        ->toBe(route('warehouse-manage.index'));
});

// ── المدخل من شاشة الأرصدة ───────────────────────────────

it('يجعل اسم المخزن في شاشة الأرصدة رابطاً إلى بروفايله', function () {
    $mine = wpWarehouse('بنها');
    $item = App\Models\Item::create([
        'name'         => 'كمبيوتر',
        'item_unit_id' => App\Models\ItemUnit::firstOrCreate(['name' => 'قطعة'])->id,
    ]);
    App\Models\WarehouseStock::create(['warehouse_id' => $mine->id, 'item_id' => $item->id, 'quantity' => 5]);

    $this->actingAs(wpUser(['warehouses.index'], [$mine]));

    $this->get(route('warehouses.stock'))
        ->assertOk()
        ->assertSee(route('warehouse-manage.show', $mine), false)
        // 📌 رابطٌ لا يُميَّز إلا بمرور الماوس رابطٌ غير موجود
        ->assertSee('decoration-dotted', false);
});

// ── ترتيب الراوتات ───────────────────────────────────────

it('لا يلتقط الحرفُ البدل مسارَ إنشاء المخزن', function () {
    // ⚠️ `warehouse-manage/{warehouse}` خرج من مجموعة الإعدادات بحارسٍ أوسع،
    //    ولو سبق `warehouse-manage/create` لصار الإنشاء بحثاً عن مخزنٍ اسمه create
    $route = app('router')->getRoutes()->match(Request::create('/warehouse-manage/create', 'GET'));

    expect($route->getName())->toBe('warehouse-manage.create');
});

it('يُبقي مسار الإنشاء والتعديل خلف صلاحية الإعدادات وحدها', function () {
    $w = wpWarehouse('بنها');

    $this->actingAs(wpUser(['warehouses.index'], [$w]));

    // ⚠️ القائمة والبروفايل فُتحا لصاحب التشغيل، والإنشاء والتعديل لا — فهما
    //    فعلا إعدادات لا فعلا تشغيل
    $this->get(route('warehouse-manage.create'))->assertForbidden();
    $this->get(route('warehouse-manage.edit', $w))->assertForbidden();
    $this->get(route('warehouse-manage.index'))->assertOk();
});

// ── قائمة المخازن: مدخل صاحب المخزن إلى بروفايله ─────────

it('يفتح قائمة المخازن لصاحب warehouses.index مقصورةً على نطاقه', function () {
    $mine   = wpWarehouse('بنها');
    $theirs = wpWarehouse('مخزن غيري');

    $this->actingAs(wpUser(['warehouses.index'], [$mine]));

    $this->get(route('warehouse-manage.index'))
        ->assertOk()
        ->assertSee('بنها')
        ->assertDontSee('مخزن غيري');
});

it('يعرض القائمة كلها لمدير الإعدادات ولو لم يُربط بمخزن', function () {
    wpWarehouse('بنها');
    wpWarehouse('مخزن غيري');

    // ⚠️ شاشة إعدادات بلا نطاق لمديرها — وتقييدُها بنطاقه يُفرغها عليه
    $this->actingAs(wpUser(['warehouses.settings']));

    $this->get(route('warehouse-manage.index'))
        ->assertOk()
        ->assertSee('بنها')
        ->assertSee('مخزن غيري');
});

it('يعطي صاحب المخزن زرّ العرض ويمنعه أزرار التعديل والحذف والإنشاء', function () {
    $mine = wpWarehouse('بنها');

    $this->actingAs(wpUser(['warehouses.index'], [$mine]));

    // 📌 الزرّ هو مدخله الوحيد للبروفايل: المخزن الفارغ لا صفَّ له في جدول الأرصدة
    $this->get(route('warehouse-manage.index'))
        ->assertOk()
        ->assertSee(route('warehouse-manage.show', $mine), false)
        ->assertDontSee(route('warehouse-manage.edit', $mine), false)
        ->assertDontSee(route('warehouse-manage.create'), false);
});

it('يبلغ صاحب المخزن الفارغ بروفايلَه رغم خلوّ جدول الأرصدة', function () {
    // 📌 هذه هي الحالة الواقعة اليوم: ٢٩ مخزناً بلا أصناف تنتظر أرصدتها الافتتاحية
    $mine = wpWarehouse('بنها');

    $this->actingAs(wpUser(['warehouses.index'], [$mine]));

    expect(App\Models\WarehouseStock::where('warehouse_id', $mine->id)->count())->toBe(0);

    $this->get(route('warehouse-manage.index'))->assertSee(route('warehouse-manage.show', $mine), false);
    $this->get(route('warehouse-manage.show', $mine))->assertOk();
});

it('يسمّي البند «مخازني» لصاحب المخزن و«إدارة المخازن» لمديرها', function () {
    $mine = wpWarehouse('بنها');

    $this->actingAs(wpUser(['warehouses.index'], [$mine]));
    $this->get(route('warehouses.stock'))->assertSee('مخازني')->assertDontSee('إدارة المخازن');

    $this->actingAs(wpUser(['warehouses.index', 'warehouses.settings'], [], all: true));
    $this->get(route('warehouses.stock'))->assertSee('إدارة المخازن');
});
