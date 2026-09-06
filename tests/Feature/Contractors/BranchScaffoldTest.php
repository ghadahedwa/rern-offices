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
    $this->actingAs(deUser(['contractors.index', 'contractors.attendance']));

    foreach (['index', 'attendance', 'reports'] as $screen) {
        $this->get(route("contractors.{$screen}"))->assertOk();
    }
});

it('يمنع من لا يملك صلاحيات العاملين بالتعاقد', function () {
    $this->actingAs(deUser(['offices.index']));

    foreach (['index', 'attendance', 'reports'] as $screen) {
        $this->get(route("contractors.{$screen}"))->assertForbidden();
    }
});

it('يفصل التسجيل عن العرض: صاحب التسجيل وحده يُمنع من القائمة والتقارير', function () {
    // القاعدة التي قامت عليها الصلاحية المنفصلة — مَن يسجّل الحضور اليومي
    // ليس بالضرورة مَن يعدّل بيانات العاملين أو يطالع تقاريرهم
    $this->actingAs(deUser(['contractors.attendance']));

    $this->get(route('contractors.attendance'))->assertOk();
    $this->get(route('contractors.index'))->assertForbidden();
    $this->get(route('contractors.reports'))->assertForbidden();
});

it('يمنع التسجيل عن صاحب العرض وحده', function () {
    $this->actingAs(deUser(['contractors.index']));

    $this->get(route('contractors.index'))->assertOk();
    $this->get(route('contractors.attendance'))->assertForbidden();
});

it('لا يفتح الشاشات بصلاحية التصدير وحدها', function () {
    // `export` تحرس الملف لا الشاشة — وحدها لا تفتح شيئاً
    $this->actingAs(deUser(['contractors.export']));

    $this->get(route('contractors.index'))->assertForbidden();
    $this->get(route('contractors.reports'))->assertForbidden();
});

// ── الفرع وصفحة الدخول ───────────────────────────────────

it('يجعل قائمة العاملين صفحة دخول الفرع', function () {
    $user = deUser(['contractors.index', 'contractors.attendance']);

    expect(Branch::entryUrlFor('contractors', $user))->toBe(route('contractors.index'));
});

it('يهبط بصاحب التسجيل وحده على شاشة الحضور لا على قائمةٍ تردّه ٤٠٣', function () {
    $user = deUser(['contractors.attendance']);

    expect(Branch::entryUrlFor('contractors', $user))->toBe(route('contractors.attendance'));
});

it('يتيح الفرع لصاحب أي من صلاحيتي الدخول ويمنعه عن غيرهما', function () {
    expect(Branch::canAccess('contractors', deUser(['contractors.index'])))->toBeTrue()
        ->and(Branch::canAccess('contractors', deUser(['contractors.attendance'])))->toBeTrue()
        ->and(Branch::canAccess('contractors', deUser(['contractors.export'])))->toBeFalse()
        ->and(Branch::canAccess('contractors', deUser(['offices.index'])))->toBeFalse();
});

it('يضع شاشات الفرع في فرعها لا في فرع المقرات', function () {
    $this->actingAs(deUser(['contractors.index']));

    $this->get(route('contractors.index'))->assertOk();

    expect(Branch::current())->toBe('contractors');
});

// ── النطاق: المحافظات ────────────────────────────────────

it('يطالب دور العاملين بالتعاقد باختيار محافظات', function () {
    // ⚠️ بدون هذا يُحفظ المستخدم بلا محافظة فلا يرى مقرّاً يسجّل عليه — أبداً
    expect(PermissionGroups::needsGovernorates(['contractors.attendance']))->toBeTrue()
        ->and(PermissionGroups::needsGovernorates(['contractors.index']))->toBeTrue();
});

it('لا يطالب دور العاملين بالتعاقد بطرف مراسلات ولا بمخزن', function () {
    expect(PermissionGroups::needsEntity(['contractors.index']))->toBeFalse()
        ->and(PermissionGroups::needsWarehouses(['contractors.index']))->toBeFalse();
});

// ── شبكة الأدوار ─────────────────────────────────────────

it('يعرض الصلاحيات الست تحت عنوانها في شبكة الأدوار', function () {
    $names = [
        'contractors.index', 'contractors.attendance', 'contractors.create',
        'contractors.edit', 'contractors.export', 'contractors.delete',
    ];

    foreach ($names as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $grouped = PermissionGroups::group(Permission::whereIn('name', $names)->get());

    expect($grouped['home.branch_contractors']['العاملون بالتعاقد']->pluck('name')->all())
        ->toBe($names);   // الترتيب بالدور: العرض والتسجيل أولاً، والحذف آخراً
});

it('يحوّل الروابط القديمة لمسارات الفرع الجديدة', function () {
    // ⚠️ /data-entry كان مدخل الفرع قبل إعادة التسمية — والرابط المحفوظ عند
    //    المستخدم يعطي ٤٠٤ بلا تفسير، والتحويل الدائم يعالجه بلا تدخّل منه.
    // والتحويلات داخل مجموعة auth، فغير المسجَّل يمرّ على تسجيل الدخول أولاً.
    $this->actingAs(deUser(['contractors.index', 'contractors.attendance']));

    $this->get('/data-entry')->assertRedirect('/contractors');
    $this->get('/data-entry/attendance')->assertRedirect('/contractors/attendance');
    $this->get('/data-entry/reports')->assertRedirect('/contractors/reports');
    $this->get('/data-entry/operators/create')->assertRedirect('/contractors/create');
    $this->get('/data-entry/operators/import')->assertRedirect('/contractors/import');
});
