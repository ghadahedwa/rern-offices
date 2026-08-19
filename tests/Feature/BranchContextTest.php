<?php

use App\Models\User;
use App\Support\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function branchUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role = Role::findOrCreate('branch-tester-'.uniqid(), 'web');
    $role->givePermissionTo($permissions);

    return tap(User::factory()->create())->assignRole($role);
}

it('يبقى في فرع المستخدم الأخير على شاشة لا تنتمي لفرع', function () {
    // الملف الشخصي والمظهر شاشات شخصية — لا يجوز أن تنقل المستخدم لفرع آخر
    $user = branchUser(['correspondence.settings', 'offices.index']);
    $this->actingAs($user);

    $this->get(route('correspondence-entities.index'))->assertOk();

    expect(session(Branch::SESSION_KEY))->toBe('system');

    $this->get(route('appearance.edit'))->assertOk();

    expect(Branch::current())->toBe('system');
});

it('يفضّل تطابق الراوت على ما في الجلسة', function () {
    $user = branchUser(['correspondence.settings', 'offices.index']);
    $this->actingAs($user);

    session([Branch::SESSION_KEY => 'system']);

    $this->get(route('offices.index'))->assertOk();

    expect(session(Branch::SESSION_KEY))->toBe('offices');
});

it('يتجاهل فرعاً في الجلسة لم يعد المستخدم يملك صلاحيته', function () {
    // القيمة قد تكون من قبل سحب دور — فلا تُقرأ بلا إعادة فحص
    $user = branchUser(['offices.index']);
    $this->actingAs($user);

    session([Branch::SESSION_KEY => 'system']);   // لا يملك أي صلاحية إعدادات

    $this->get(route('appearance.edit'))->assertOk();

    expect(Branch::current())->toBe('offices');
});

it('يتجاهل مفتاح فرع غير معرَّف في الإعدادات', function () {
    $user = branchUser(['offices.index']);
    $this->actingAs($user);

    session([Branch::SESSION_KEY => 'branch-that-was-removed']);

    $this->get(route('appearance.edit'))->assertOk();

    expect(Branch::current())->toBe('offices');
});
