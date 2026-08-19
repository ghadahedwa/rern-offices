<?php

use App\Livewire\Users\Create;
use App\Livewire\Users\Edit;
use App\Models\CorrespondenceEntity;
use App\Models\Governorate;
use App\Models\User;
use App\Support\PermissionGroups;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function scopeSuperAdmin(): User
{
    Role::findOrCreate('super-admin', 'web');

    return tap(User::factory()->create())->assignRole('super-admin');
}

/** دور بصلاحيات مراسلات تشغيلية. */
function corrRole(): Role
{
    Permission::findOrCreate('correspondence.index', 'web');
    Permission::findOrCreate('correspondence.approve', 'web');

    return tap(Role::findOrCreate('رئيس جهة', 'web'))
        ->givePermissionTo(['correspondence.index', 'correspondence.approve']);
}

/** دور بصلاحيات مقرات. */
function officesRole(): Role
{
    Permission::findOrCreate('offices.index', 'web');

    return tap(Role::findOrCreate('مفتش مقرات', 'web'))->givePermissionTo('offices.index');
}

/** دور بصلاحيات سيارات وحدها — لا يبدأ بـoffices. لكنه تحت عنوان «إدارة المقرات». */
function vehiclesRole(): Role
{
    Permission::findOrCreate('vehicles.index', 'web');

    return tap(Role::findOrCreate('سيارات', 'web'))->givePermissionTo('vehicles.index');
}

/** دور بإعدادات المراسلات وحدها — تحت «إدارة النظام»، فلا طرف له. */
function corrSettingsRole(): Role
{
    Permission::findOrCreate('correspondence.settings', 'web');

    return tap(Role::findOrCreate('مدير إعدادات', 'web'))->givePermissionTo('correspondence.settings');
}

function anEntity(string $name = 'رئاسة المصلحة'): CorrespondenceEntity
{
    return CorrespondenceEntity::firstOrCreate(
        ['name' => $name],
        ['code' => mb_substr($name, 0, 6), 'order' => 1]
    );
}

// ── مفتاح الأقسام: العنوان لا بادئة الصلاحية ─────────────

it('يُظهر المحافظات لدور السيارات — البادئة وحدها كانت ستفوّته', function () {
    expect(PermissionGroups::needsGovernorates(['vehicles.index']))->toBeTrue()
        ->and(PermissionGroups::needsEntity(['vehicles.index']))->toBeFalse();
});

it('لا يُظهر المحافظات لإعدادات المقرات — تبدأ بـoffices لكنها تحت إدارة النظام', function () {
    expect(PermissionGroups::needsGovernorates(['offices.settings']))->toBeFalse();
});

it('لا يُظهر الطرف لإعدادات المراسلات — مدير النظام يدير القائمة بلا انتماء لطرف', function () {
    expect(PermissionGroups::needsEntity(['correspondence.settings']))->toBeFalse();
});

it('يُظهر الطرف لصلاحيات المراسلات التشغيلية', function () {
    expect(PermissionGroups::needsEntity(['correspondence.index']))->toBeTrue()
        ->and(PermissionGroups::needsGovernorates(['correspondence.index']))->toBeFalse();
});

// ── الفورم ───────────────────────────────────────────────

it('يُظهر قسم الطرف عند اختيار دور مراسلات ويُخفي المحافظات', function () {
    $this->actingAs(scopeSuperAdmin());
    corrRole();

    Livewire::test(Create::class)
        ->set('role', 'رئيس جهة')
        ->assertViewHas('needsEntity', true)
        ->assertViewHas('needsGovernorates', false);
});

it('يُظهر قسم المحافظات عند اختيار دور مقرات ويُخفي الطرف', function () {
    $this->actingAs(scopeSuperAdmin());
    officesRole();

    Livewire::test(Create::class)
        ->set('role', 'مفتش مقرات')
        ->assertViewHas('needsGovernorates', true)
        ->assertViewHas('needsEntity', false);
});

it('يُظهر قسم المحافظات لدور السيارات في الفورم فعلاً', function () {
    $this->actingAs(scopeSuperAdmin());
    vehiclesRole();

    Livewire::test(Create::class)
        ->set('role', 'سيارات')
        ->assertViewHas('needsGovernorates', true);
});

it('لا يُظهر أي قسم لدور إعدادات المراسلات', function () {
    $this->actingAs(scopeSuperAdmin());
    corrSettingsRole();

    Livewire::test(Create::class)
        ->set('role', 'مدير إعدادات')
        ->assertViewHas('needsGovernorates', false)
        ->assertViewHas('needsEntity', false);
});

it('يُلزم بالطرف لدور المراسلات', function () {
    $this->actingAs(scopeSuperAdmin());
    corrRole();

    Livewire::test(Create::class)
        ->set('name', 'أ. خالد')
        ->set('username', 'khaled')
        ->set('password', 'secret')
        ->set('password_confirmation', 'secret')
        ->set('role', 'رئيس جهة')
        ->call('save')
        ->assertHasErrors(['correspondence_entity_id' => 'required']);
});

it('يحفظ الطرف والمسمّى الوظيفي', function () {
    $this->actingAs(scopeSuperAdmin());
    corrRole();
    $entity = anEntity();

    Livewire::test(Create::class)
        ->set('name', 'أ. خالد')
        ->set('username', 'khaled')
        ->set('password', 'secret')
        ->set('password_confirmation', 'secret')
        ->set('role', 'رئيس جهة')
        ->set('correspondence_entity_id', (string) $entity->id)
        ->set('job_title', 'رئيس القطاع')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('username', 'khaled')->first();

    expect($user->correspondence_entity_id)->toBe($entity->id)
        ->and($user->job_title)->toBe('رئيس القطاع')
        ->and($user->governorates)->toHaveCount(0);
});

it('يحفظ مسمّيين مختلفين لنفس الدور — المسمّى ليس الدور', function () {
    $this->actingAs(scopeSuperAdmin());
    corrRole();
    $presidency = anEntity('رئاسة المصلحة');
    $technical  = anEntity('المكتب الفني');

    foreach ([['khaled', $presidency, 'رئيس القطاع'], ['yasser', $technical, 'رئيس المكتب']] as [$username, $entity, $title]) {
        Livewire::test(Create::class)
            ->set('name', $username)
            ->set('username', $username)
            ->set('password', 'secret')
            ->set('password_confirmation', 'secret')
            ->set('role', 'رئيس جهة')
            ->set('correspondence_entity_id', (string) $entity->id)
            ->set('job_title', $title)
            ->call('save')
            ->assertHasNoErrors();
    }

    expect(User::where('username', 'khaled')->first()->job_title)->toBe('رئيس القطاع')
        ->and(User::where('username', 'yasser')->first()->job_title)->toBe('رئيس المكتب')
        ->and(User::where('username', 'khaled')->first()->roles->first()->name)
        ->toBe(User::where('username', 'yasser')->first()->roles->first()->name);
});

// ── تصفير النطاق غير المنطبق ─────────────────────────────

it('يتجاهل محافظات مدسوسة لدور مراسلات', function () {
    // القيمة تصل من العميل — ودور بلا صلاحيات مقرات لا يحتفظ بمحافظات
    $this->actingAs(scopeSuperAdmin());
    corrRole();
    $entity = anEntity();
    $gov    = Governorate::create(['name' => 'المنيا', 'order' => 1]);

    Livewire::test(Create::class)
        ->set('name', 'أ. محمد')
        ->set('username', 'mohamed')
        ->set('password', 'secret')
        ->set('password_confirmation', 'secret')
        ->set('role', 'رئيس جهة')
        ->set('correspondence_entity_id', (string) $entity->id)
        ->set('selectedGovernorates', [(string) $gov->id])
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('username', 'mohamed')->first()->governorates)->toHaveCount(0);
});

it('يتجاهل طرفاً مدسوساً لدور مقرات', function () {
    $this->actingAs(scopeSuperAdmin());
    officesRole();
    $entity = anEntity();

    Livewire::test(Create::class)
        ->set('name', 'م. أحمد')
        ->set('username', 'ahmed')
        ->set('password', 'secret')
        ->set('password_confirmation', 'secret')
        ->set('role', 'مفتش مقرات')
        ->set('correspondence_entity_id', (string) $entity->id)
        ->set('job_title', 'مفتش')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('username', 'ahmed')->first();

    expect($user->correspondence_entity_id)->toBeNull()
        ->and($user->job_title)->toBeNull();
});

it('يُخلي الطرف عند تغيير الدور إلى دور مقرات', function () {
    $this->actingAs(scopeSuperAdmin());
    corrRole();
    officesRole();
    $entity = anEntity();

    Livewire::test(Create::class)
        ->set('role', 'رئيس جهة')
        ->set('correspondence_entity_id', (string) $entity->id)
        ->set('job_title', 'رئيس القطاع')
        ->set('role', 'مفتش مقرات')
        ->assertSet('correspondence_entity_id', '')
        ->assertSet('job_title', '');
});

// ── التعديل ──────────────────────────────────────────────

it('يحمّل الطرف والمسمّى في شاشة التعديل ويحفظ تغييرهما', function () {
    $this->actingAs(scopeSuperAdmin());
    $role       = corrRole();
    $presidency = anEntity('رئاسة المصلحة');
    $technical  = anEntity('المكتب الفني');

    $user = User::factory()->create([
        'correspondence_entity_id' => $presidency->id,
        'job_title'                => 'رئيس القطاع',
    ]);
    $user->assignRole($role);

    Livewire::test(Edit::class, ['user' => $user])
        ->assertSet('correspondence_entity_id', (string) $presidency->id)
        ->assertSet('job_title', 'رئيس القطاع')
        ->set('correspondence_entity_id', (string) $technical->id)
        ->set('job_title', 'رئيس المكتب')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->correspondence_entity_id)->toBe($technical->id)
        ->and($user->fresh()->job_title)->toBe('رئيس المكتب');
});
