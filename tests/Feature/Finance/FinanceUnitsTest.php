<?php

use App\Livewire\Finance\Units\Create as UnitCreate;
use App\Livewire\Finance\Units\Index as UnitIndex;
use App\Models\FinanceAccount;
use App\Models\FinanceMonth;
use App\Models\FinanceUnit;
use App\Models\User;
use App\Support\Branch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function unitsSuperAdmin(): User
{
    return tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));
}

function unitsFinUser(): User
{
    $abilities = ['finance.accounts.view', 'finance.court-total.view', 'finance.accounts.create', 'finance.units.create', 'finance.edit', 'finance.delete'];
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }
    $role = Role::findOrCreate('fin-units-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

it('يزرع الوحدات الست بترتيبها', function () {
    expect(FinanceUnit::ordered()->pluck('name')->all())->toBe([
        'الوحدة الحسابية بالقاهرة',
        'الوحدة الحسابية بالإسكندرية',
        'الوحدة الحسابية بطنطا',
        'الوحدة الحسابية بالأقصر',
        'الوحدة الحسابية بأسيوط',
        'الوحدة الحسابية بالإسماعيلية',
    ])->and(FinanceUnit::active()->count())->toBe(6);
});

it('يقصر القائمة على السوبر أدمن ولو ملك صلاحيات الشئون المالية كلها', function () {
    $this->actingAs(unitsFinUser());

    $this->get(route('finance-units.index'))->assertForbidden();
    $this->get(route('finance-units.create'))->assertForbidden();
    Livewire::test(UnitIndex::class)->assertForbidden();
    Livewire::test(UnitCreate::class)->assertForbidden();

    $this->actingAs(unitsSuperAdmin());
    $this->get(route('finance-units.index'))->assertOk()->assertSee('الوحدة الحسابية بطنطا');
});

it('تقع الصفحة في فرع إدارة النظام وبندها في القائمة الجانبية', function () {
    $this->actingAs(unitsSuperAdmin());

    $this->get(route('finance-units.index'))->assertOk();
    expect(Branch::current())->toBe('system');

    // البند من صفحةٍ أخرى في الفرع، والرابط بعلامته الختامية — روابط الإضافة والتعديل تبدأ به
    $this->get(route('court-building-items.index'))->assertSee('href="'.route('finance-units.index').'"', false);
});

it('يضيف وحدةً سابعة في آخر الترتيب ويرفض الاسم المكرر', function () {
    $this->actingAs(unitsSuperAdmin());

    Livewire::test(UnitCreate::class)
        ->assertSet('order', 7)
        ->set('name', 'الوحدة الحسابية بالمنصورة')
        ->call('save')
        ->assertHasNoErrors();

    expect(FinanceUnit::active()->count())->toBe(7);

    Livewire::test(UnitCreate::class)
        ->set('name', 'الوحدة الحسابية بطنطا')
        ->call('save')
        ->assertHasErrors(['name' => 'unique']);
});

it('يعدّل الوحدة ويعطّلها', function () {
    $this->actingAs(unitsSuperAdmin());
    $unit = FinanceUnit::where('name', 'الوحدة الحسابية بالأقصر')->first();

    Livewire::test(UnitCreate::class, ['financeUnit' => $unit])
        ->set('name', 'الوحدة الحسابية بالأقصر وأسوان')
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($unit->fresh()->name)->toBe('الوحدة الحسابية بالأقصر وأسوان')
        ->and($unit->fresh()->is_active)->toBeFalse();
});

it('يحذف الوحدة غير المستعمَلة بعد التأكيد وحده', function () {
    $this->actingAs(unitsSuperAdmin());
    $unit = FinanceUnit::where('name', 'الوحدة الحسابية بأسيوط')->first();

    Livewire::test(UnitIndex::class)->set('deletingId', $unit->id)->call('deleteRow');
    expect(FinanceUnit::find($unit->id))->not->toBeNull();

    Livewire::test(UnitIndex::class)->call('askDelete', $unit->id)->call('deleteRow');
    expect(FinanceUnit::find($unit->id))->toBeNull();
});

it('لا يحذف وحدةً سُجِّل لها شهر', function () {
    $unit = FinanceUnit::where('name', 'الوحدة الحسابية بطنطا')->first();
    FinanceMonth::create([
        'finance_account_id' => FinanceAccount::byKey('court_buildings')->id,
        'finance_unit_id'    => $unit->id,
        'month'              => '2026-10-01',
        'amount'             => 0,
    ]);

    $this->actingAs(unitsSuperAdmin());
    Livewire::test(UnitIndex::class)->call('askDelete', $unit->id)->call('deleteRow');

    expect(FinanceUnit::find($unit->id))->not->toBeNull();
});

it('لا يعطِّل ولا يحذف آخر وحدة مفعَّلة', function () {
    $this->actingAs(unitsSuperAdmin());

    $last = FinanceUnit::ordered()->first();
    FinanceUnit::whereKeyNot($last->id)->update(['is_active' => false]);

    Livewire::test(UnitCreate::class, ['financeUnit' => $last])
        ->set('is_active', false)
        ->call('save')
        ->assertHasErrors('is_active');
    expect($last->fresh()->is_active)->toBeTrue();

    Livewire::test(UnitIndex::class)->call('askDelete', $last->id)->call('deleteRow');
    expect(FinanceUnit::find($last->id))->not->toBeNull();
});

// ── ربط المستخدم بالوحدات (فورم المستخدم) ─────────────────

function financeRole(string $name, array $abilities): Role
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    return tap(Role::findOrCreate($name, 'web'))->givePermissionTo($abilities);
}

function unitId(string $city): int
{
    return FinanceUnit::where('name', 'like', '%'.$city)->value('id');
}

it('يُظهر قسم الوحدات لدور الوحدات وحده لا للحسابات العامة', function () {
    $this->actingAs(unitsSuperAdmin());
    financeRole('وحدة حسابية', ['finance.units.view', 'finance.units.create']);
    financeRole('حسابات عامة فقط', ['finance.accounts.view', 'finance.accounts.create']);

    Livewire::test(\App\Livewire\Users\Create::class)
        ->set('role', 'وحدة حسابية')
        ->assertViewHas('needsFinanceUnits', true)
        ->assertSee('الوحدة الحسابية بالأقصر')
        ->set('role', 'حسابات عامة فقط')
        ->assertViewHas('needsFinanceUnits', false);
});

it('يحفظ المستخدم بوحدته ويرفض حفظه بلا وحدة', function () {
    $this->actingAs(unitsSuperAdmin());
    financeRole('وحدة حسابية', ['finance.units.view', 'finance.units.create']);

    $form = fn () => Livewire::test(\App\Livewire\Users\Create::class)
        ->set('name', 'مدخل طنطا')
        ->set('username', 'tanta'.uniqid())
        ->set('password', '1234')
        ->set('password_confirmation', '1234')
        ->set('role', 'وحدة حسابية');

    $form()->call('save')->assertHasErrors('selectedFinanceUnits');

    $form()->set('selectedFinanceUnits', [(string) unitId('بطنطا')])->call('save')->assertHasNoErrors();

    expect(User::where('name', 'مدخل طنطا')->sole()->financeUnits->pluck('id')->all())->toBe([unitId('بطنطا')]);
});

it('يرفض معرّف وحدةٍ غير موجودة', function () {
    $this->actingAs(unitsSuperAdmin());
    financeRole('وحدة حسابية', ['finance.units.view']);

    Livewire::test(\App\Livewire\Users\Create::class)
        ->set('name', 'س')->set('username', 'x'.uniqid())
        ->set('password', '1234')->set('password_confirmation', '1234')
        ->set('role', 'وحدة حسابية')
        ->set('selectedFinanceUnits', ['99999'])
        ->call('save')
        ->assertHasErrors('selectedFinanceUnits.0');
});

it('يحمّل وحدات المستخدم في التعديل ويحدّثها', function () {
    $this->actingAs(unitsSuperAdmin());
    financeRole('وحدة حسابية', ['finance.units.view', 'finance.units.create']);

    $user = tap(User::factory()->create())->assignRole('وحدة حسابية');
    $user->financeUnits()->sync([unitId('بأسيوط')]);

    Livewire::test(\App\Livewire\Users\Edit::class, ['user' => $user])
        ->assertSet('selectedFinanceUnits', [(string) unitId('بأسيوط')])
        ->set('selectedFinanceUnits', [(string) unitId('بأسيوط'), (string) unitId('بالأقصر')])
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->financeUnits->pluck('id')->sort()->values()->all())
        ->toBe(collect([unitId('بأسيوط'), unitId('بالأقصر')])->sort()->values()->all());
});

it('يُفرّغ الوحدات حين يتغيّر الدور إلى دورٍ بلا وحدات — ولو أرسلها المتصفح', function () {
    $this->actingAs(unitsSuperAdmin());
    financeRole('وحدة حسابية', ['finance.units.view']);
    financeRole('حسابات عامة فقط', ['finance.accounts.view']);

    $user = tap(User::factory()->create())->assignRole('وحدة حسابية');
    $user->financeUnits()->sync([unitId('بالقاهرة')]);

    Livewire::test(\App\Livewire\Users\Edit::class, ['user' => $user])
        ->set('role', 'حسابات عامة فقط')
        ->assertSet('selectedFinanceUnits', [])
        ->set('selectedFinanceUnits', [(string) unitId('بالقاهرة')])
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->financeUnits)->toHaveCount(0);
});

it('يرتّب منسدلة الأدوار بترتيب إنشائها في فورم المستخدم وصفحة المستخدمين', function () {
    $this->actingAs(unitsSuperAdmin());
    Role::findOrCreate('وحدة حسابية', 'web');
    Role::findOrCreate('الحسابات العامة', 'web');
    Role::findOrCreate('عرض فقط', 'web');

    $expected = Role::orderBy('id')->pluck('name')->all();

    Livewire::test(\App\Livewire\Users\Index::class)
        ->assertViewHas('roles', fn ($roles) => $roles->pluck('name')->all() === $expected);

    Livewire::test(\App\Livewire\Users\Create::class)
        ->assertViewHas('roles', fn ($roles) => $roles->pluck('name')->all() === $expected);

    $user = User::factory()->create();
    Livewire::test(\App\Livewire\Users\Edit::class, ['user' => $user])
        ->assertViewHas('roles', fn ($roles) => $roles->pluck('name')->all() === $expected);
});
