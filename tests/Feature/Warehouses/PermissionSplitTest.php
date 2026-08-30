<?php

use App\Livewire\Warehouses\Dashboard;
use App\Livewire\Warehouses\Incoming\Create as IncomingCreate;
use App\Livewire\Warehouses\OpeningBalances;
use App\Livewire\Warehouses\Transfers\Create as TransferCreate;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * تفتيت `warehouses.create` الخشنة إلى ثلاث.
 *
 * ⚠️ العلّة التي يحرسها هذا الملف: الرصيد الافتتاحي **يكتب الرصيد كتابةً**
 *    على أي مخزن، فمنحُ موظفٍ حقَّ النقل كان يمنحه — بالصلاحية نفسها —
 *    حقَّ إعادة كتابة أرصدة المخزن الرئيسي.
 */
function psUser(array $permissions, string $role): User
{
    foreach ($permissions as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $roleModel = Role::findOrCreate($role, 'web');
    $roleModel->syncPermissions($permissions);

    // ⚠️ `all_warehouses` = **بلا حدّ**: هذه الاختبارات تفحص منطق الشاشة لا
    //    النطاق، ومستخدمٌ بلا مخزن مرتبط يرى صفراً بحقّ (الفراغ = لا شيء).
    //    اختبارات النطاق نفسها في WarehouseScopeTest.
    return tap(User::factory()->create(['all_warehouses' => true]))->assignRole($roleModel);
}

// ── الفصل نفسه ───────────────────────────────────────────

it('يمنع صاحب النقل من شاشة الرصيد الافتتاحي', function () {
    $this->actingAs(psUser(['warehouses.index', 'warehouses.transfer'], 'ps-transfer'));

    Livewire::test(OpeningBalances::class)->assertStatus(403);
});

it('يمنع صاحب النقل من شاشة الوارد', function () {
    $this->actingAs(psUser(['warehouses.index', 'warehouses.transfer'], 'ps-transfer2'));

    Livewire::test(IncomingCreate::class)->assertStatus(403);
});

it('يمنع صاحب الافتتاحي من شاشتَي الوارد والنقل', function () {
    $this->actingAs(psUser(['warehouses.index', 'warehouses.opening'], 'ps-opening'));

    Livewire::test(IncomingCreate::class)->assertStatus(403);
    Livewire::test(TransferCreate::class)->assertStatus(403);
});

it('يفتح لكلٍّ شاشتَه', function () {
    $this->actingAs(psUser(['warehouses.index', 'warehouses.opening'], 'ps-o'));
    Livewire::test(OpeningBalances::class)->assertStatus(200);

    $this->actingAs(psUser(['warehouses.index', 'warehouses.incoming'], 'ps-i'));
    Livewire::test(IncomingCreate::class)->assertStatus(200);

    $this->actingAs(psUser(['warehouses.index', 'warehouses.transfer'], 'ps-t'));
    Livewire::test(TransferCreate::class)->assertStatus(200);
});

// ── الراوتات، لا الحُرّاس وحدهم ────────────────────────────

it('يحجب رابط الرصيد الافتتاحي عن صاحب النقل', function () {
    $this->actingAs(psUser(['warehouses.index', 'warehouses.transfer'], 'ps-route'));

    $this->get(route('warehouses.opening-balances'))->assertForbidden();
    $this->get(route('warehouses.incoming.create'))->assertForbidden();
    $this->get(route('warehouses.transfers.create'))->assertOk();
});

// ── لوحة التحكم: زرٌّ لكل صلاحية ──────────────────────────

it('يعرض في اللوحة أزرار ما يملكه المستخدم وحده', function () {
    $this->actingAs(psUser(['warehouses.index', 'warehouses.opening'], 'ps-dash2'));

    $html = Livewire::test(Dashboard::class)->html();

    expect($html)->toContain(route('warehouses.opening-balances'))
        ->and($html)->not->toContain(route('warehouses.incoming.create'))
        ->and($html)->not->toContain(route('warehouses.transfers.create'));
});

it('يعرض لأمين المخزن الرئيسي الوارد والنقل دون الافتتاحي', function () {
    $this->actingAs(psUser(['warehouses.index', 'warehouses.incoming', 'warehouses.transfer'], 'ps-main'));

    $html = Livewire::test(Dashboard::class)->html();

    expect($html)->toContain(route('warehouses.incoming.create'))
        ->and($html)->toContain(route('warehouses.transfers.create'))
        ->and($html)->not->toContain(route('warehouses.opening-balances'));
});

// ── الصلاحيتان الميتتان ──────────────────────────────────

it('يحذف الصلاحيتين الميتتين ولا يُبقي الخشنة', function () {
    expect(Permission::where('name', 'warehouses.create')->exists())->toBeFalse()
        ->and(Permission::where('name', 'warehouses.view')->exists())->toBeFalse()
        ->and(Permission::where('name', 'warehouses.edit')->exists())->toBeFalse();
});

it('ينقل الخشنة إلى الثلاث لكل من ملكها — دوراً كان أو مستخدماً', function () {
    // نُعيد تمثيل الحال قبل الهجرة: خشنةٌ يملكها دورٌ ويملكها مستخدمٌ مباشرةً
    $old  = Permission::findOrCreate('warehouses.create', 'web');
    $role = Role::findOrCreate('ps-legacy', 'web');
    $role->givePermissionTo($old);

    $direct = User::factory()->create();
    $direct->givePermissionTo($old);

    $holder = tap(User::factory()->create())->assignRole($role);

    // ثم نُجري الهجرة نفسها على هذا الحال
    (require database_path('migrations/2026_08_29_000001_split_warehouse_create_permission.php'))->up();

    expect(Permission::where('name', 'warehouses.create')->exists())->toBeFalse();

    foreach (['warehouses.opening', 'warehouses.incoming', 'warehouses.transfer'] as $name) {
        // ⚠️ الإسناد المباشر للمستخدم وارد في هذا المشروع — وتركُه كان
        //    يُخرج مستخدماً كان يعمل أمس عاجزاً اليوم بلا سبب ظاهر
        expect($direct->fresh()->hasPermissionTo($name))->toBeTrue()
            ->and($holder->fresh()->hasPermissionTo($name))->toBeTrue();
    }
});

// ── شبكة الأدوار تعرض الجديدة ─────────────────────────────

it('يعرض الصلاحيات الثلاث في شبكة الأدوار مرتَّبةً بالدور', function () {
    $grouped = \App\Support\PermissionGroups::group(
        Permission::where('name', 'like', 'warehouses.%')->get()
    );

    $names = collect($grouped['home.branch_warehouses']['المخازن'])->pluck('name')->all();

    // الثلاث حاضرة (المطابقة بالبادئة فلا تسقط سهواً)
    expect($names)->toContain('warehouses.opening', 'warehouses.incoming', 'warehouses.transfer')
        // والإعدادات مستثناة — عنوانها فرع النظام لا فرع المخازن
        ->and($names)->not->toContain('warehouses.settings');

    // ⚠️ صلاحيات **المفتش الخمس متتالية في أعلى الجدول** — فمَن يُنشئ دوره
    //    يعلّمها بلا تخطٍّ ولا بحثٍ في القائمة. وهو أكثر الأدوار حَمَلةً.
    expect(array_slice($names, 0, 5))->toBe([
        'warehouses.index',
        'warehouses.opening',
        'warehouses.issue',
        'warehouses.export',
        'warehouses.attachments',
    ]);

    // وما ينفرد به أمين المخزن الرئيسي يليها، والحذف آخراً
    $rank = array_flip($names);
    expect($rank['warehouses.attachments'])->toBeLessThan($rank['warehouses.incoming'])
        ->and($rank['warehouses.incoming'])->toBeLessThan($rank['warehouses.delete']);
});
