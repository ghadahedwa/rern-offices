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

it('يزرع صلاحيات الشئون المالية الست وحدها', function () {
    expect(Permission::where('name', 'like', 'finance.%')->pluck('name')->sort()->values()->all())
        ->toBe(collect(\App\Support\Finance\FinanceAccess::ALL)->sort()->values()->all());
});

// ── الفرع والصفحات ───────────────────────────────────────

it('يفتح الصفحات الأربع لصاحب العرض باسم حسابها', function () {
    $this->actingAs(finUser(['finance.total.view']));

    foreach (FIN_PAGES as $route) {
        $key = str_replace('-', '_', substr($route, strlen('finance.')));

        $this->get(route($route))->assertOk()->assertSee(__('home.fin_account_'.$key));
    }
});

it('يطلب عناصر أبنية المحاكم في فورم شهر الوحدة وحده', function () {
    $user = finUser(['finance.total.view', 'finance.units.create', 'finance.units.view', 'finance.units.create']);
    $unit = \App\Models\FinanceUnit::ordered()->first();
    $user->financeUnits()->sync([$unit->id]);
    $this->actingAs($user);

    $this->get(route('finance.unit-entry', [$unit, 'court-buildings']))->assertSee('مكاتب مميزة');
    $this->get(route('finance.entry', 'mechanization'))->assertDontSee('مكاتب مميزة');
});

it('يمنع الصفحات عمّن لا يملك العرض — ولو ملك الإدخال والتصحيح', function () {
    $this->actingAs(finUser(['finance.units.create', 'finance.edit', 'finance.delete']));

    foreach (FIN_PAGES as $route) {
        $this->get(route($route))->assertForbidden();
    }
});

it('لا يفتح الفرع إلا بالعرض', function () {
    expect(Branch::canAccess('finance', finUser(['finance.total.view'])))->toBeTrue()
        ->and(Branch::canAccess('finance', finUser(['finance.units.create', 'finance.edit', 'finance.delete'])))->toBeFalse()
        ->and(Branch::canAccess('finance', finUser(['offices.index'])))->toBeFalse();
});

it('يجعل أبنية المحاكم صفحة دخول الفرع', function () {
    expect(Branch::entryUrlFor('finance', finUser(['finance.total.view'])))
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
    $this->actingAs(finUser(['finance.total.view']));

    $response = $this->get(route('finance.mechanization'));

    foreach (FIN_PAGES as $route) {
        $response->assertSee(route($route), false);
    }
});

it('يجمع الصلاحيات تحت عنوانها بلا نطاق محافظات', function () {
    $groups = PermissionGroups::group(Permission::all());

    expect($groups['home.branch_finance']['الشئون المالية']->pluck('name')->all())
        ->toBe(\App\Support\Finance\FinanceAccess::ALL)
        ->and(PermissionGroups::needsGovernorates(['finance.total.view', 'finance.units.create']))->toBeFalse();
});

// ── عناصر أبنية المحاكم ──────────────────────────────────

it('يقصر قائمة العناصر على السوبر أدمن', function () {
    $this->actingAs(finUser(['finance.total.view', 'finance.units.create', 'finance.edit', 'finance.delete']));

    $this->get(route('court-building-items.index'))->assertForbidden();
    $this->get(route('court-building-items.create'))->assertForbidden();

    $this->actingAs(finSuperAdmin());

    $this->get(route('court-building-items.index'))->assertOk()->assertSee('سيارات جمعة');
});

it('يرفض مكوّني العناصر من غير السوبر أدمن', function () {
    $this->actingAs(finUser(['finance.total.view', 'finance.edit', 'finance.delete']));

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


// ── الصلاحيات الست (المرحلة ٤ · 2026-11-15) ───────────────

/** يُرجع الداتابيز لما قبل الدمج: الأسماء الثمانية القديمة على دور، ثم يُشغَّل الدمج */
function mergeWith(array $old): Role
{
    $migration = require database_path('migrations/2026_11_15_000002_merge_finance_permissions.php');
    $migration->down();

    $role = Role::findOrCreate('legacy-'.uniqid(), 'web');
    $role->givePermissionTo($old);

    $migration->up();

    return $role->fresh();
}

it('لا يفقد دورٌ شيئاً بالدمج: عرض الحسابات العامة ← الإجمالي، وإدخالها ← إدخال الوحدات', function () {
    $role = mergeWith(['finance.accounts.view', 'finance.accounts.create']);

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['finance.total.view', 'finance.units.create'])
        ->and(Permission::whereIn('name', ['finance.accounts.view', 'finance.accounts.create', 'finance.court-total.view'])->exists())->toBeFalse();
});

it('يحتفظ صاحب إجمالي أبنية المحاكم بالإجمالي (إعادة تسمية لا حذف)', function () {
    $role = mergeWith(['finance.court-total.view', 'finance.units.view', 'finance.edit']);

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['finance.edit', 'finance.total.view', 'finance.units.view']);
});

it('العرض القديم وحده لا يأخذ إدخالاً', function () {
    $role = mergeWith(['finance.accounts.view']);

    expect($role->permissions->pluck('name')->all())->toBe(['finance.total.view']);
});

// ── عرض الإجمالي والوحدات ─────────────────────────────────

it('عرض الإجمالي يفتح صفحات الحسابات الأربعة كلها', function () {
    $this->actingAs(finUser(['finance.total.view']));

    foreach (['finance.court-buildings', 'finance.mechanization', 'finance.telecom', 'finance.ministry-of-finance'] as $route) {
        $this->get(route($route))->assertOk();
    }

    $this->actingAs(finUser(['finance.units.view']));
    $this->get(route('finance.mechanization'))->assertForbidden();
});

it('يهبط عارض الإجمالي على أبنية المحاكم، وصاحب الوحدات وحدها على وحدته', function () {
    expect(Branch::entryUrlFor('finance', finUser(['finance.total.view'])))->toBe(route('finance.court-buildings'))
        ->and(Branch::entryUrlFor('finance', finUser(['finance.units.view'])))->toBe(route('finance.my-unit'));
});

it('الحسابات الأربعة لا تُدخَل إلا من وحدةٍ مربوطة', function () {
    $this->actingAs(finUser(['finance.total.view', 'finance.units.create']));

    $unit = \App\Models\FinanceUnit::ordered()->first();

    $this->get(route('finance.unit-entry', [$unit, 'mechanization']))->assertForbidden();

    $unitUser = finUser(['finance.units.view', 'finance.units.create']);
    $unitUser->financeUnits()->sync([$unit->id]);
    $this->actingAs($unitUser);

    foreach (['court-buildings', 'mechanization', 'telecom', 'ministry-of-finance'] as $slug) {
        $this->get(route('finance.unit-entry', [$unit, $slug]))->assertOk();
        // ⚠️ حسابات الوحدات لا تُدخَل بلا وحدة
        $this->get(route('finance.entry', $slug))->assertNotFound();
    }
});

it('ملاحظة صفحة الإجمالي بعرضه مع إدخال الوحدات — والعرض وحده لا يكتبها', function () {
    $this->actingAs(finUser(['finance.total.view', 'finance.units.create']));
    Livewire::test(\App\Livewire\Finance\CourtBuildings::class)
        ->set('noteDrafts.2026-10', 'ملاحظة الوحدات')
        ->call('addNote', '2026-10')
        ->assertHasNoErrors();

    $this->actingAs(finUser(['finance.total.view']));
    Livewire::test(\App\Livewire\Finance\CourtBuildings::class)
        ->set('noteDrafts.2026-10', 'ملاحظة العارض')
        ->call('addNote', '2026-10')
        ->assertForbidden();

    expect(\App\Models\FinanceNote::count())->toBe(1);
})->travelTo('2026-11-05 10:00:00');

it('لا يفتح صاحب الوحدات صورة أمر دفعٍ لوحدةٍ ليست له برابطها', function () {
    [$mine, $other] = \App\Models\FinanceUnit::ordered()->take(2)->get()->all();

    $month = \App\Models\FinanceMonth::create([
        'finance_account_id' => FinanceAccount::byKey('mechanization')->id,
        'finance_unit_id'    => $other->id,
        'month'              => '2026-10-01',
        'amount'             => 0,
    ]);
    $attachment = $month->paymentOrders()->create(['number' => '1', 'issued_on' => '2026-10-02', 'position' => 1])
        ->attachments()->create(['path' => 'payment-orders/x.jpg', 'original_name' => 'x.jpg']);

    $user = finUser(['finance.units.view']);
    $user->financeUnits()->sync([$mine->id]);
    $this->actingAs($user);

    $this->get(route('finance.attachments.show', $attachment))->assertForbidden();
});

// ── فورم الدور والقائمة الجانبية ──────────────────────────

it('فورم الدور يعرض الصلاحيات الست بسطر شرحٍ تحت كلٍّ منها', function () {
    $html = view('livewire.roles.partials.permissions-grid', [
        'permissions'         => Permission::where('name', 'like', 'finance.%')->get(),
        'selectedPermissions' => [],
    ])->render();

    expect($html)->toContain('عرض الإجمالي')
        ->toContain('أرقام الحسابات الأربعة لكل الوحدات مجمَّعة')
        ->toContain('صفحة الوحدة المربوط بها المستخدم وحدها')
        ->not->toContain('finance.accounts.view');
});

it('القائمة: «وحدتي» لمن رُبط بوحدةٍ واحدة، ووحداته بأسمائها الكاملة لمن يفتح أكثر', function () {
    $units = \App\Models\FinanceUnit::ordered()->get();

    $unitUser = finUser(['finance.units.view']);
    $unitUser->financeUnits()->sync([$units[1]->id]);
    $this->actingAs($unitUser)->get(route('finance.unit', $units[1]))
        ->assertSee(__('home.fin_my_unit'))
        ->assertDontSee(__('home.fin_account_mechanization'));

    $viewer = finUser(['finance.total.view', 'finance.units.view']);
    $viewer->financeUnits()->sync($units->pluck('id')->all());
    // صفحة وحدةٍ — لا رابط فيها لغيرها إلا من القائمة
    $page = $this->actingAs($viewer)->get(route('finance.unit', $units[0]))
        ->assertDontSee(__('home.fin_my_unit'))
        ->assertSee(__('home.fin_units_menu'));
    foreach ($units as $unit) {
        $page->assertSee('href="'.route('finance.unit', $unit).'"', false)->assertSee($unit->name);
    }

    // والأدمن بلا ربط يرى الست
    $this->actingAs(finSuperAdmin())->get(route('finance.unit', $units[0]))
        ->assertSee(__('home.fin_units_menu'))
        ->assertSee('href="'.route('finance.unit', $units[5]).'"', false);
});
