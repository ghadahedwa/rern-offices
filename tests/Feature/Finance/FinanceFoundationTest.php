<?php

use App\Livewire\Finance\CourtBuildingItems\Create as ItemCreate;
use App\Livewire\Finance\CourtBuildingItems\Index as ItemIndex;
use App\Models\CourtBuildingItem;
use App\Models\FinanceAccount;
use App\Models\User;
use App\Support\Branch;
use App\Support\PermissionGroups;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

const FIN_PAGES = [
    'finance.court-buildings',
    'finance.mechanization',
    'finance.telecom',
    'finance.ministry-of-finance',
];

function finUser(array $abilities): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('fin-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

function finSuperAdmin(): User
{
    return tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));
}

// ── الزرع ────────────────────────────────────────────────

it('يزرع الحسابات الأربعة بأشكالها وترتيب الورقة', function () {
    expect(FinanceAccount::orderBy('order')->pluck('kind', 'key')->all())->toBe([
        'court_buildings'     => 'itemized',
        'mechanization'       => 'single',
        'telecom'             => 'ledger',
        'ministry_of_finance' => 'single',
    ]);
});

it('يزرع عناصر أبنية المحاكم الثمانية مفعَّلة بترتيبها', function () {
    $items = CourtBuildingItem::ordered()->get();

    expect($items)->toHaveCount(8)
        ->and($items->first()->name)->toBe('قيمة نموذج طلب الشهر ق٩')
        ->and($items->last()->name)->toBe('فترة مسائية ثانية')
        ->and($items->every->is_active)->toBeTrue();
});

it('يزرع الصلاحيات الأربع', function () {
    expect(Permission::where('name', 'like', 'finance.%')->pluck('name')->sort()->values()->all())
        ->toBe(['finance.create', 'finance.delete', 'finance.edit', 'finance.view']);
});

// ── الفرع والصفحات ───────────────────────────────────────

it('يفتح الصفحات الأربع لصاحب العرض باسم حسابها', function () {
    $this->actingAs(finUser(['finance.view']));

    foreach (FIN_PAGES as $route) {
        $key = str_replace('-', '_', substr($route, strlen('finance.')));

        $this->get(route($route))->assertOk()->assertSee(__('home.fin_account_'.$key));
    }
});

it('يطلب عناصر أبنية المحاكم في فورم شهرها وحده', function () {
    $this->actingAs(finUser(['finance.view', 'finance.create']));

    $this->get(route('finance.entry', 'court-buildings'))->assertSee('مكاتب مميزة');
    $this->get(route('finance.entry', 'mechanization'))->assertDontSee('مكاتب مميزة');
});

it('يمنع الصفحات عمّن لا يملك العرض — ولو ملك الإدخال والتصحيح', function () {
    $this->actingAs(finUser(['finance.create', 'finance.edit', 'finance.delete']));

    foreach (FIN_PAGES as $route) {
        $this->get(route($route))->assertForbidden();
    }
});

it('لا يفتح الفرع إلا بالعرض', function () {
    expect(Branch::canAccess('finance', finUser(['finance.view'])))->toBeTrue()
        ->and(Branch::canAccess('finance', finUser(['finance.create', 'finance.edit', 'finance.delete'])))->toBeFalse()
        ->and(Branch::canAccess('finance', finUser(['offices.index'])))->toBeFalse();
});

it('يجعل أبنية المحاكم صفحة دخول الفرع', function () {
    expect(Branch::entryUrlFor('finance', finUser(['finance.view'])))
        ->toBe(route('finance.court-buildings'));
});

it('يضع الصفحات في فرع الشئون المالية وعناصرها في إدارة النظام', function () {
    $this->actingAs(finSuperAdmin());

    $this->get(route('finance.telecom'));
    expect(Branch::current())->toBe('finance');

    $this->get(route('court-building-items.index'));
    expect(Branch::current())->toBe('system');
});

it('يعرض بنود الحسابات الأربعة في القائمة الجانبية', function () {
    $this->actingAs(finUser(['finance.view']));

    $response = $this->get(route('finance.mechanization'));

    foreach (FIN_PAGES as $route) {
        $response->assertSee(route($route), false);
    }
});

it('يجمع الصلاحيات تحت عنوانها بلا نطاق محافظات', function () {
    $groups = PermissionGroups::group(Permission::all());

    expect($groups['home.branch_finance']['الشئون المالية']->pluck('name')->all())
        ->toBe(['finance.view', 'finance.create', 'finance.edit', 'finance.delete'])
        ->and(PermissionGroups::needsGovernorates(['finance.view', 'finance.create']))->toBeFalse();
});

// ── عناصر أبنية المحاكم ──────────────────────────────────

it('يقصر قائمة العناصر على السوبر أدمن', function () {
    $this->actingAs(finUser(['finance.view', 'finance.create', 'finance.edit', 'finance.delete']));

    $this->get(route('court-building-items.index'))->assertForbidden();
    $this->get(route('court-building-items.create'))->assertForbidden();

    $this->actingAs(finSuperAdmin());

    $this->get(route('court-building-items.index'))->assertOk()->assertSee('سيارات جمعة');
});

it('يرفض مكوّني العناصر من غير السوبر أدمن', function () {
    $this->actingAs(finUser(['finance.view', 'finance.edit', 'finance.delete']));

    Livewire::test(ItemIndex::class)->assertForbidden();
    Livewire::test(ItemCreate::class)->assertForbidden();
});

it('يضيف عنصراً تاسعاً في آخر الترتيب ويرفض الاسم المكرر', function () {
    $this->actingAs(finSuperAdmin());

    Livewire::test(ItemCreate::class)
        ->assertSet('order', 9)
        ->set('name', 'عنصر جديد')
        ->call('save')
        ->assertHasNoErrors();

    expect(CourtBuildingItem::active()->count())->toBe(9);

    Livewire::test(ItemCreate::class)
        ->set('name', 'مصاريف إدارية')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('يحذف العنصر غير المستعمَل بعد التأكيد وحده', function () {
    $this->actingAs(finSuperAdmin());
    $item = CourtBuildingItem::where('name', 'مكاتب مميزة')->first();

    // بلا تأكيد لا شيء
    Livewire::test(ItemIndex::class)->set('deletingId', $item->id)->call('deleteRow');
    expect(CourtBuildingItem::find($item->id))->not->toBeNull();

    Livewire::test(ItemIndex::class)->call('askDelete', $item->id)->call('deleteRow');
    expect(CourtBuildingItem::find($item->id))->toBeNull();
});

it('لا يحذف عنصراً سُجِّلت له قيمة في شهر', function () {
    $item  = CourtBuildingItem::where('name', 'سيارات صباحي')->first();
    $month = \App\Models\FinanceMonth::create([
        'finance_account_id' => \App\Models\FinanceAccount::byKey('court_buildings')->id,
        'month'              => '2026-10-01',
        'amount'             => 0,
    ]);
    DB::table(CourtBuildingItem::VALUES_TABLE)->insert([
        'finance_month_id' => $month->id, 'court_building_item_id' => $item->id, 'amount' => 0,
    ]);

    $this->actingAs(finSuperAdmin());

    Livewire::test(ItemIndex::class)->call('askDelete', $item->id)->call('deleteRow');

    expect(CourtBuildingItem::find($item->id))->not->toBeNull();
});

it('لا يعطِّل ولا يحذف آخر عنصر مفعَّل', function () {
    $this->actingAs(finSuperAdmin());

    $last = CourtBuildingItem::ordered()->first();
    CourtBuildingItem::whereKeyNot($last->id)->update(['is_active' => false]);

    Livewire::test(ItemCreate::class, ['courtBuildingItem' => $last])
        ->set('is_active', false)
        ->call('save')
        ->assertHasErrors('is_active');

    expect($last->fresh()->is_active)->toBeTrue();

    Livewire::test(ItemIndex::class)->call('askDelete', $last->id)->call('deleteRow');

    expect(CourtBuildingItem::find($last->id))->not->toBeNull();
});

it('يسحب التصحيح والحذف من دور admin ويُبقي له العرض والإدخال', function () {
    // الأدوار تُنشأ من الشاشة لا من الهجرات — فاختبار الداتابيز الجديدة بلا admin، ويُنشأ هنا كما على السيرفر
    $admin = Role::findOrCreate('admin', 'web');
    $admin->givePermissionTo(['finance.view', 'finance.create', 'finance.edit', 'finance.delete']);

    (require database_path('migrations/2026_11_07_000003_revoke_finance_corrections_from_admin.php'))->up();

    expect($admin->fresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['finance.create', 'finance.view']);
});
