<?php

use App\Livewire\Roles\Edit as RoleEdit;
use App\Models\Governorate;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * الربط التلقائي لمخازن مستخدمي الدور من شاشة الأدوار.
 *
 * ⚠️ العلّة: **الصلاحية تُمنح بالدور والنطاق يُربط بالمستخدم** — فتعديل الدور
 *    وحده يُخرج لمستخدميه فرعاً فارغاً. والربط يسدّ الفجوة بلا أن يُلغي القاعدة:
 *    يُعرض للتأكيد، ولا يمحو ربطاً قائماً بيد المدير.
 */
function rlAdmin(): User
{
    Role::findOrCreate('super-admin', 'web');

    return tap(User::factory()->create())->assignRole('super-admin');
}

function rlRole(string $name, array $permissions): Role
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    return tap(Role::findOrCreate($name, 'web'))->syncPermissions($permissions);
}

function rlWarehouse(string $name, ?Governorate $gov = null, int $level = 3): Warehouse
{
    return Warehouse::create([
        'name'              => $name,
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'نوع '.$level], ['level' => $level, 'order' => $level])->id,
        'governorate_id'    => $gov?->id,
    ]);
}

function rlMember(Role $role, array $governorates = [], array $warehouses = [], bool $all = false): User
{
    $user = tap(User::factory()->create(['all_warehouses' => $all]))->assignRole($role);
    $user->governorates()->sync(collect($governorates)->pluck('id')->all());
    $user->warehouses()->sync(collect($warehouses)->pluck('id')->all());

    return $user->fresh();
}

// ── متى يُطلب التأكيد ─────────────────────────────────────

it('يطلب التأكيد قبل الحفظ حين يكون في الدور مَن يحتاج ربطاً', function () {
    $gov  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    rlWarehouse('مخزن قنا', $gov);
    $role = rlRole('rl-inspector', ['warehouses.index']);
    rlMember($role, [$gov]);

    $this->actingAs(rlAdmin());

    Livewire::test(RoleEdit::class, ['role' => $role])
        ->call('save')
        ->assertSet('showLinkConfirm', true)
        // ⚠️ ولم يُحفظ بعد — التأكيد **قبل** الحفظ لا بعده
        ->assertNoRedirect();
});

it('لا يطلب التأكيد لدورٍ بلا صلاحيات مخازن', function () {
    $gov  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    rlWarehouse('مخزن قنا', $gov);
    $role = rlRole('rl-offices', ['offices.index']);
    rlMember($role, [$gov]);

    $this->actingAs(rlAdmin());

    Livewire::test(RoleEdit::class, ['role' => $role])
        ->call('save')
        ->assertSet('showLinkConfirm', false)
        ->assertRedirect(route('roles.index'));
});

it('لا يطلب التأكيد حين يكون لكل المستخدمين نطاقٌ قائم', function () {
    $gov = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $w   = rlWarehouse('مخزن قنا', $gov);
    $role = rlRole('rl-linked', ['warehouses.index']);
    rlMember($role, [$gov], [$w]);

    $this->actingAs(rlAdmin());

    Livewire::test(RoleEdit::class, ['role' => $role])
        ->call('save')
        ->assertSet('showLinkConfirm', false)
        ->assertRedirect(route('roles.index'));
});

// ── الربط نفسه ───────────────────────────────────────────

it('يربط كل مستخدمٍ بمخازن محافظاته عند التأكيد', function () {
    $qena  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $aswan = Governorate::create(['name' => 'أسوان', 'order' => 2]);
    $wQena  = rlWarehouse('مخزن قنا', $qena);
    $wAswan = rlWarehouse('مخزن أسوان', $aswan);

    $role = rlRole('rl-link', ['warehouses.index']);
    $one  = rlMember($role, [$qena]);
    $two  = rlMember($role, [$qena, $aswan]);

    $this->actingAs(rlAdmin());

    Livewire::test(RoleEdit::class, ['role' => $role])
        ->call('save')
        ->call('saveAndLink')
        ->assertRedirect(route('roles.index'));

    expect($one->fresh()->warehouses->pluck('id')->all())->toBe([$wQena->id])
        ->and($two->fresh()->warehouses->pluck('id')->sort()->values()->all())
        ->toBe(collect([$wQena->id, $wAswan->id])->sort()->values()->all());
});

it('يربط محافظةً بمخزنين معاً — كأسيوط', function () {
    $asyut = Governorate::create(['name' => 'اسيوط', 'order' => 1]);
    $sub      = rlWarehouse('اسيوط', $asyut, 3);
    $regional = rlWarehouse('المخزن الاقليمي باسيوط', $asyut, 2);

    $role = rlRole('rl-asyut', ['warehouses.index']);
    $user = rlMember($role, [$asyut]);

    $this->actingAs(rlAdmin());

    Livewire::test(RoleEdit::class, ['role' => $role])->call('save')->call('saveAndLink');

    // ⚠️ الاثنان يُقترحان، والمدير ينزع الإقليمي من فورم المستخدم إن لزم —
    //    الربط لا يقرّر نيابةً عنه أيهما عهدةُ المفتش
    expect($user->fresh()->warehouses->pluck('id')->sort()->values()->all())
        ->toBe(collect([$sub->id, $regional->id])->sort()->values()->all());
});

it('لا يمسّ مستخدماً له ربطٌ قائم ولا صاحبَ «كل المخازن»', function () {
    $qena  = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $wQena = rlWarehouse('مخزن قنا', $qena);
    $other = rlWarehouse('مخزن آخر');

    $role   = rlRole('rl-keep', ['warehouses.index']);
    $manual = rlMember($role, [$qena], [$other]);   // ربطٌ بيد المدير يخالف محافظته
    $all    = rlMember($role, [$qena], [], all: true);
    $bare   = rlMember($role, [$qena]);

    $this->actingAs(rlAdmin());

    Livewire::test(RoleEdit::class, ['role' => $role])->call('save')->call('saveAndLink');

    expect($manual->fresh()->warehouses->pluck('id')->all())->toBe([$other->id])
        ->and($all->fresh()->warehouses->count())->toBe(0)
        ->and($all->fresh()->all_warehouses)->toBeTrue()
        ->and($bare->fresh()->warehouses->pluck('id')->all())->toBe([$wQena->id]);
});

it('لا يمنح «كل المخازن» لأحد', function () {
    $gov = Governorate::create(['name' => 'قنا', 'order' => 1]);
    rlWarehouse('مخزن قنا', $gov);
    $role = rlRole('rl-noall', ['warehouses.index']);
    $user = rlMember($role, [$gov]);

    $this->actingAs(rlAdmin());

    Livewire::test(RoleEdit::class, ['role' => $role])->call('save')->call('saveAndLink');

    expect($user->fresh()->all_warehouses)->toBeFalse();
});

it('لا يربط المخزن الرئيسي بأحد — فهو بلا محافظة', function () {
    $gov = Governorate::create(['name' => 'قنا', 'order' => 1]);
    rlWarehouse('مخزن قنا', $gov);
    $main = rlWarehouse('المخزن الرئيسي بالمصلحة', null, 1);

    $role = rlRole('rl-main', ['warehouses.index']);
    $user = rlMember($role, [$gov]);

    $this->actingAs(rlAdmin());

    Livewire::test(RoleEdit::class, ['role' => $role])->call('save')->call('saveAndLink');

    expect($user->fresh()->warehouses->pluck('id')->all())->not->toContain($main->id);
});

it('يترك بلا نطاق مَن لا مخزن في محافظاته ومَن بلا محافظات', function () {
    $gov      = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $noStores = Governorate::create(['name' => 'مكتب تملك الاجانب', 'order' => 2]);
    rlWarehouse('مخزن قنا', $gov);

    $role     = rlRole('rl-gap', ['warehouses.index']);
    $noWh     = rlMember($role, [$noStores]);
    $noGov    = rlMember($role, []);

    $this->actingAs(rlAdmin());

    Livewire::test(RoleEdit::class, ['role' => $role])->call('save')->call('saveAndLink');

    expect($noWh->fresh()->warehouses->count())->toBe(0)
        ->and($noGov->fresh()->warehouses->count())->toBe(0);
});

// ── الحفظ بلا ربط ────────────────────────────────────────

it('يحفظ الصلاحيات ولا يربط أحداً عند اختيار «حفظ بلا ربط»', function () {
    $gov = Governorate::create(['name' => 'قنا', 'order' => 1]);
    rlWarehouse('مخزن قنا', $gov);
    Permission::findOrCreate('warehouses.opening', 'web');

    $role = rlRole('rl-skip', ['warehouses.index']);
    $user = rlMember($role, [$gov]);

    $this->actingAs(rlAdmin());

    Livewire::test(RoleEdit::class, ['role' => $role])
        ->set('selectedPermissions', ['warehouses.index', 'warehouses.opening'])
        ->call('save')
        ->call('saveWithoutLink')
        ->assertRedirect(route('roles.index'));

    expect($user->fresh()->warehouses->count())->toBe(0)
        ->and($role->fresh()->hasPermissionTo('warehouses.opening'))->toBeTrue();
});

// ── المودال يعرض ما سيقع ─────────────────────────────────

it('يعرض في المودال كل مرشّحٍ ومخازنه — بمن لا مخزن له', function () {
    $qena     = Governorate::create(['name' => 'قنا', 'order' => 1]);
    $noStores = Governorate::create(['name' => 'مكتب تملك الاجانب', 'order' => 2]);
    rlWarehouse('مخزن قنا', $qena);

    $role = rlRole('rl-modal', ['warehouses.index']);
    rlMember($role, [$qena]);
    rlMember($role, [$noStores]);

    $this->actingAs(rlAdmin());

    $component = Livewire::test(RoleEdit::class, ['role' => $role])->call('save');

    expect($component->viewData('linkCandidates'))->toHaveCount(2);

    $html = $component->html();
    expect($html)->toContain('مخزن قنا')
        // ومَن لا مخزن في محافظاته يُبلَّغ به ولا يُسكَت عنه
        ->and($html)->toContain('لا مخزن في محافظاته');
});

it('يبني قائمة المرشّحين من الصلاحيات المختارة لا المحفوظة', function () {
    $gov = Governorate::create(['name' => 'قنا', 'order' => 1]);
    rlWarehouse('مخزن قنا', $gov);
    Permission::findOrCreate('warehouses.index', 'web');

    // الدور اليوم بلا صلاحيات مخازن — وتُضاف في الشاشة قبل الحفظ
    $role = rlRole('rl-pending', ['offices.index']);
    rlMember($role, [$gov]);

    $this->actingAs(rlAdmin());

    Livewire::test(RoleEdit::class, ['role' => $role])
        ->set('selectedPermissions', ['offices.index', 'warehouses.index'])
        ->call('save')
        ->assertSet('showLinkConfirm', true);
});
