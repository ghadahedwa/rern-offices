<?php

use App\Livewire\Contractors\Statuses\Create;
use App\Livewire\Contractors\Statuses\Index;
use App\Models\AttendanceStatus;
use App\Models\User;
use App\Support\Branch;
use App\Support\PermissionGroups;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function statusUser(array $abilities): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('de-settings-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

// ── الحالات المزروعة ─────────────────────────────────────

it('يزرع الحالات الثلاث أساسيةً ومفعَّلة', function () {
    $seeded = AttendanceStatus::ordered()->get();

    // «إجازة» قبل «غائب» منذ هجرة 2026_09_17_000001 (طلب المستخدمة)
    expect($seeded->pluck('name')->all())->toBe(['حاضر', 'إجازة', 'غائب'])
        ->and($seeded->every->is_system)->toBeTrue()
        ->and($seeded->every->is_active)->toBeTrue();
});

// ── الحراسة ──────────────────────────────────────────────

it('يفتح الشاشة لصاحب إعدادات العاملين بالتعاقد', function () {
    $this->actingAs(statusUser(['contractors.settings']));

    $this->get(route('attendance-statuses.index'))->assertOk();
    $this->get(route('attendance-statuses.create'))->assertOk();
});

it('يمنع صاحب صلاحيات الفرع التشغيلية من شاشة الإعدادات', function () {
    // ⚠️ `contractors.index` تفتح الفرع ولا تفتح قائمةً مرجعية
    $this->actingAs(statusUser(['contractors.index', 'contractors.attendance']));

    $this->get(route('attendance-statuses.index'))->assertForbidden();
    $this->get(route('attendance-statuses.create'))->assertForbidden();
});

it('يمنع الحفظ والحذف عمّن سُحبت صلاحيته والشاشة مفتوحة', function () {
    // الإجراء يصل في طلب مستقل عن فتح الشاشة
    $user = statusUser(['contractors.settings']);
    $this->actingAs($user);

    $component = Livewire::test(Index::class);

    $user->roles()->first()->revokePermissionTo('contractors.settings');
    $user->forgetCachedPermissions();

    $extra = AttendanceStatus::create(['name' => 'مأمورية', 'color' => '#2563eb']);

    $component->call('askDelete', $extra->id)->assertForbidden();
});

// ── القائمة المرجعية نفسها ───────────────────────────────

it('يضيف حالة جديدة تظهر في الترتيب', function () {
    $this->actingAs(statusUser(['contractors.settings']));

    Livewire::test(Create::class)
        ->set('name', 'حضر متأخر')
        ->set('color', '#c9a847')
        ->set('order', 4)
        ->call('save');

    $added = AttendanceStatus::where('name', 'حضر متأخر')->first();

    expect($added)->not->toBeNull()
        ->and($added->is_system)->toBeFalse()
        ->and($added->is_active)->toBeTrue();
});

it('يرفض اسماً مكرراً ولوناً خارج اللوحة', function () {
    $this->actingAs(statusUser(['contractors.settings']));

    Livewire::test(Create::class)
        ->set('name', 'حاضر')
        ->call('save')
        ->assertHasErrors(['name']);

    Livewire::test(Create::class)
        ->set('name', 'مأمورية')
        ->set('color', '#123456')
        ->call('save')
        ->assertHasErrors(['color']);
});

it('يحذف حالة مضافة', function () {
    $this->actingAs(statusUser(['contractors.settings']));

    $extra = AttendanceStatus::create(['name' => 'مأمورية', 'color' => '#2563eb']);

    Livewire::test(Index::class)
        ->call('askDelete', $extra->id)
        ->call('deleteRow');

    expect(AttendanceStatus::find($extra->id))->toBeNull();
});

it('يمنع حذف حالة أساسية حتى لو وصل النداء مباشرةً', function () {
    // ⚠️ الزرّ مخفيّ عن الأساسية في القالب — والحارس في الإجراء لأن النداء يصل بلا زرّ
    $this->actingAs(statusUser(['contractors.settings']));

    $system = AttendanceStatus::where('name', 'حاضر')->first();

    Livewire::test(Index::class)
        ->call('askDelete', $system->id)
        ->call('deleteRow');

    expect(AttendanceStatus::find($system->id))->not->toBeNull();
});

it('يتجاهل الحذف بلا تأكيد مسبق', function () {
    $this->actingAs(statusUser(['contractors.settings']));

    $extra = AttendanceStatus::create(['name' => 'مأمورية', 'color' => '#2563eb']);

    Livewire::test(Index::class)
        ->set('deletingId', $extra->id)     // بلا askDelete — showDelete = false
        ->call('deleteRow');

    expect(AttendanceStatus::find($extra->id))->not->toBeNull();
});

it('لا يعطّل حالة أساسية ولو وصلت القيمة من الطلب', function () {
    $this->actingAs(statusUser(['contractors.settings']));

    $system = AttendanceStatus::where('name', 'غائب')->first();

    Livewire::test(Create::class, ['attendanceStatus' => $system])
        ->set('is_active', false)
        ->call('save');

    expect($system->fresh()->is_active)->toBeTrue();
});

it('يخفي المعطَّلة عن منسدلة التسجيل ويبقيها في الجدول', function () {
    $extra = AttendanceStatus::create(['name' => 'مأمورية', 'color' => '#2563eb', 'is_active' => false]);

    expect(AttendanceStatus::selectable()->pluck('id')->all())->not->toContain($extra->id)
        ->and(AttendanceStatus::count())->toBe(4);
});

it('يبحث بالنص العربي المطبَّع', function () {
    $this->actingAs(statusUser(['contractors.settings']));

    AttendanceStatus::create(['name' => 'إجازة مرضية', 'color' => '#2563eb']);

    // «اجازه» بألف بلا همزة وهاء بدل التاء المربوطة
    Livewire::test(Index::class)
        ->set('search', 'اجازه')
        ->assertSee('إجازة مرضية');
});

// ── الموضع في المنظومة ───────────────────────────────────

it('يضع شاشة الحالات في فرع إدارة النظام لا في فرع العاملين بالتعاقد', function () {
    $this->actingAs(statusUser(['contractors.settings']));

    $this->get(route('attendance-statuses.index'))->assertOk();

    expect(Branch::current())->toBe('system');
});

it('لا يطالب مدير القوائم المرجعية بمحافظات', function () {
    // ⚠️ بادئة `contractors.` تلتقطها لولا `except` — فيُطالَب بنطاقٍ لا معنى له
    expect(PermissionGroups::needsGovernorates(['contractors.settings']))->toBeFalse()
        ->and(PermissionGroups::needsGovernorates(['contractors.index']))->toBeTrue();
});

it('يعرض صلاحية الإعدادات تحت إدارة النظام لا تحت الفرع', function () {
    Permission::findOrCreate('contractors.settings', 'web');
    Permission::findOrCreate('contractors.index', 'web');

    $grouped = PermissionGroups::group(Permission::whereIn('name', ['contractors.settings', 'contractors.index'])->get());

    expect($grouped['home.branch_system']['إعدادات العاملين بالتعاقد']->pluck('name')->all())
        ->toBe(['contractors.settings'])
        ->and($grouped['home.branch_contractors']['العاملون بالتعاقد']->pluck('name')->all())
        ->toBe(['contractors.index']);
});
