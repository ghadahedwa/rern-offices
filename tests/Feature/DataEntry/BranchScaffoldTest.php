<?php

use App\Models\User;
use App\Support\Branch;
use App\Support\PermissionGroups;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function deUser(array $abilities): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('de-scaffold-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

// ── الشاشات وحراستها ─────────────────────────────────────

it('يفتح شاشات الفرع الثلاث لصاحب صلاحياتها', function () {
    $this->actingAs(deUser(['data-entry.index', 'data-entry.attendance']));

    foreach (['index', 'attendance', 'reports'] as $screen) {
        $this->get(route("data-entry.{$screen}"))->assertOk();
    }
});

it('يمنع من لا يملك صلاحيات مدخلي البيانات', function () {
    $this->actingAs(deUser(['offices.index']));

    foreach (['index', 'attendance', 'reports'] as $screen) {
        $this->get(route("data-entry.{$screen}"))->assertForbidden();
    }
});

it('يفصل التسجيل عن العرض: صاحب التسجيل وحده يُمنع من القائمة والتقارير', function () {
    // القاعدة التي قامت عليها الصلاحية المنفصلة — مَن يسجّل الحضور اليومي
    // ليس بالضرورة مَن يعدّل بيانات المدخلين أو يطالع تقاريرهم
    $this->actingAs(deUser(['data-entry.attendance']));

    $this->get(route('data-entry.attendance'))->assertOk();
    $this->get(route('data-entry.index'))->assertForbidden();
    $this->get(route('data-entry.reports'))->assertForbidden();
});

it('يمنع التسجيل عن صاحب العرض وحده', function () {
    $this->actingAs(deUser(['data-entry.index']));

    $this->get(route('data-entry.index'))->assertOk();
    $this->get(route('data-entry.attendance'))->assertForbidden();
});

it('لا يفتح الشاشات بصلاحية التصدير وحدها', function () {
    // `export` تحرس الملف لا الشاشة — وحدها لا تفتح شيئاً
    $this->actingAs(deUser(['data-entry.export']));

    $this->get(route('data-entry.index'))->assertForbidden();
    $this->get(route('data-entry.reports'))->assertForbidden();
});

// ── الفرع وصفحة الدخول ───────────────────────────────────

it('يجعل قائمة المدخلين صفحة دخول الفرع', function () {
    $user = deUser(['data-entry.index', 'data-entry.attendance']);

    expect(Branch::entryUrlFor('data-entry', $user))->toBe(route('data-entry.index'));
});

it('يهبط بصاحب التسجيل وحده على شاشة الحضور لا على قائمةٍ تردّه ٤٠٣', function () {
    $user = deUser(['data-entry.attendance']);

    expect(Branch::entryUrlFor('data-entry', $user))->toBe(route('data-entry.attendance'));
});

it('يتيح الفرع لصاحب أي من صلاحيتي الدخول ويمنعه عن غيرهما', function () {
    expect(Branch::canAccess('data-entry', deUser(['data-entry.index'])))->toBeTrue()
        ->and(Branch::canAccess('data-entry', deUser(['data-entry.attendance'])))->toBeTrue()
        ->and(Branch::canAccess('data-entry', deUser(['data-entry.export'])))->toBeFalse()
        ->and(Branch::canAccess('data-entry', deUser(['offices.index'])))->toBeFalse();
});

it('يضع شاشات الفرع في فرعها لا في فرع المقرات', function () {
    $this->actingAs(deUser(['data-entry.index']));

    $this->get(route('data-entry.index'))->assertOk();

    expect(Branch::current())->toBe('data-entry');
});

// ── النطاق: المحافظات ────────────────────────────────────

it('يطالب دور مدخلي البيانات باختيار محافظات', function () {
    // ⚠️ بدون هذا يُحفظ المستخدم بلا محافظة فلا يرى مقرّاً يسجّل عليه — أبداً
    expect(PermissionGroups::needsGovernorates(['data-entry.attendance']))->toBeTrue()
        ->and(PermissionGroups::needsGovernorates(['data-entry.index']))->toBeTrue();
});

it('لا يطالب دور مدخلي البيانات بطرف مراسلات ولا بمخزن', function () {
    expect(PermissionGroups::needsEntity(['data-entry.index']))->toBeFalse()
        ->and(PermissionGroups::needsWarehouses(['data-entry.index']))->toBeFalse();
});

// ── شبكة الأدوار ─────────────────────────────────────────

it('يعرض الصلاحيات الست تحت عنوانها في شبكة الأدوار', function () {
    $names = [
        'data-entry.index', 'data-entry.attendance', 'data-entry.create',
        'data-entry.edit', 'data-entry.export', 'data-entry.delete',
    ];

    foreach ($names as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $grouped = PermissionGroups::group(Permission::whereIn('name', $names)->get());

    expect($grouped['home.branch_data_entry']['مدخلو البيانات']->pluck('name')->all())
        ->toBe($names);   // الترتيب بالدور: العرض والتسجيل أولاً، والحذف آخراً
});
