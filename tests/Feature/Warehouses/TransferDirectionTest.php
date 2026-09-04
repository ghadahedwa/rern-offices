<?php

use App\Livewire\Warehouses\Transfers\Index as TransfersIndex;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseType;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * فلتر اتجاه النقل — «وارد إلى مخازني» / «صادر من مخازني».
 *
 * المفتش لا شاشة «وارد» له (الوارد توريدُ مورّدٍ إلى المخزن الرئيسي وحده)،
 * وما يصل مخزنَه يُسجَّل **نقلاً**. فشاشة النقل هي مكان جوابه عن «ماذا استلمت؟»،
 * والاتجاه هو ما يفصل ذلك عمّا أرسله.
 *
 * ⚠️ والاتجاه **لا يوسّع النطاق أبداً** — هو تضييق فوق `applyEither`،
 *    ومقياسه مخازن المستخدم نفسها لا عمودٌ مطلق.
 */
function tdfUser(array $warehouses, bool $all = false): User
{
    Permission::findOrCreate('warehouses.index', 'web');
    Role::findOrCreate('tdf-role', 'web')->syncPermissions(['warehouses.index']);

    $user = tap(User::factory()->create(['all_warehouses' => $all]))->assignRole('tdf-role');
    $user->warehouses()->sync(collect($warehouses)->pluck('id')->all());

    return $user->fresh();
}

function tdfWarehouse(string $name, int $level = 3): Warehouse
{
    return Warehouse::create([
        'name'              => $name,
        'warehouse_type_id' => WarehouseType::firstOrCreate(['name' => 'نوع '.$level], ['level' => $level, 'order' => $level])->id,
        'is_active'         => true,
    ]);
}

function tdfTransfer(Warehouse $from, Warehouse $to, string $date = '2026-08-20'): WarehouseTransfer
{
    return WarehouseTransfer::create([
        'from_warehouse_id'        => $from->id,
        'to_warehouse_id'          => $to->id,
        'transferred_at'           => $date,
        'attachment_path'          => 'x.pdf',
        'attachment_original_name' => 'x.pdf',
    ]);
}

/** @return array{0: User, 1: Warehouse, 2: WarehouseTransfer, 3: WarehouseTransfer} */
function tdfScene(): array
{
    $main   = tdfWarehouse('المخزن الرئيسي', 1);
    $mine   = tdfWarehouse('بنها');
    $office = tdfWarehouse('مخزن آخر');

    $received = tdfTransfer($main, $mine, '2026-08-20');   // وارد إليّ
    $sent     = tdfTransfer($mine, $office, '2026-08-21'); // صادر مني

    return [tdfUser([$mine]), $mine, $received, $sent];
}

// ── التضييق ──────────────────────────────────────────────

it('يعرض الطرفين بلا فلتر — فالاتجاه تضييق لا تصحيح', function () {
    [$user] = tdfScene();
    $this->actingAs($user);

    expect(Livewire::test(TransfersIndex::class)->viewData('transfers')->count())->toBe(2);
});

it('يعرض ما وصل مخازني وحده عند «وارد إليّ»', function () {
    [$user, , $received] = tdfScene();
    $this->actingAs($user);

    $rows = Livewire::test(TransfersIndex::class)
        ->set('directionFilter', 'in')
        ->viewData('transfers');

    expect($rows->pluck('id')->all())->toBe([$received->id]);
});

it('يعرض ما أرسلته مخازني وحده عند «صادر مني»', function () {
    [$user, , , $sent] = tdfScene();
    $this->actingAs($user);

    $rows = Livewire::test(TransfersIndex::class)
        ->set('directionFilter', 'out')
        ->viewData('transfers');

    expect($rows->pluck('id')->all())->toBe([$sent->id]);
});

it('يعدّ النقل بين مخزنين لي واردًا وصادرًا معاً', function () {
    $a = tdfWarehouse('مخزن أ');
    $b = tdfWarehouse('مخزن ب');
    $t = tdfTransfer($a, $b);

    $this->actingAs(tdfUser([$a, $b]));

    // ⚠️ الحركة الواحدة طرفاها في نطاقه، فهي وارد وصادر في آنٍ — لا تسقط من أيهما
    expect(Livewire::test(TransfersIndex::class)->set('directionFilter', 'in')->viewData('transfers')->pluck('id')->all())->toBe([$t->id])
        ->and(Livewire::test(TransfersIndex::class)->set('directionFilter', 'out')->viewData('transfers')->pluck('id')->all())->toBe([$t->id]);
});

// ── الحدود ───────────────────────────────────────────────

it('لا يتجاوز الاتجاه نطاق المستخدم', function () {
    $mine   = tdfWarehouse('بنها');
    $far1   = tdfWarehouse('مخزن بعيد ١');
    $far2   = tdfWarehouse('مخزن بعيد ٢');
    tdfTransfer($far1, $far2); // لا طرف لي فيه

    $this->actingAs(tdfUser([$mine]));

    // ⚠️ «وارد إليّ» يُقاس على مخازني — لا على «كل ما وصل مخزناً ما»
    expect(Livewire::test(TransfersIndex::class)->set('directionFilter', 'in')->viewData('transfers')->count())->toBe(0);
});

it('يهمل قيمة اتجاه تالفة من الرابط ولا يُفرّغ الشاشة', function () {
    [$user] = tdfScene();
    $this->actingAs($user);

    $component = Livewire::test(TransfersIndex::class)->set('directionFilter', "in') OR 1=1 --");

    expect($component->viewData('transfers')->count())->toBe(2)
        ->and($component->instance()->activeDirection())->toBe('');
});

it('يُهمل الاتجاه لصاحب النطاق المفتوح ولا يعرض منتقيه', function () {
    $main = tdfWarehouse('المخزن الرئيسي', 1);
    $mine = tdfWarehouse('بنها');
    tdfTransfer($main, $mine);

    $this->actingAs(tdfUser([], all: true));

    $component = Livewire::test(TransfersIndex::class)->set('directionFilter', 'in');

    // لا «مخازني» عنده يُقاس عليها الاتجاه — فالقيمة تُهمَل ولا تُخفي صفاً
    expect($component->viewData('transfers')->count())->toBe(1)
        ->and($component->viewData('showDirection'))->toBeFalse()
        ->and($component->instance()->activeDirection())->toBe('');
});

// ── الشريط ───────────────────────────────────────────────

it('يعدّ الاتجاه فلتراً مفعّلاً فيظهر زر المسح، ويمسحه المسح', function () {
    [$user] = tdfScene();
    $this->actingAs($user);

    $component = Livewire::test(TransfersIndex::class)->set('directionFilter', 'in');

    expect($component->instance()->hasActiveFilters())->toBeTrue();

    $component->call('resetFilters');

    expect($component->instance()->hasActiveFilters())->toBeFalse()
        ->and($component->viewData('transfers')->count())->toBe(2);
});

it('يعرض منتقي الاتجاه لصاحب المخازن المحدَّدة وحده', function () {
    [$user] = tdfScene();
    $this->actingAs($user);

    Livewire::test(TransfersIndex::class)
        ->assertViewHas('showDirection', true)
        ->assertSee('وارد إلى مخازني')
        ->assertSee('صادر من مخازني')
        // ⚠️ المنسدلة مقفولةً لا تُظهر إلا خيارها الافتراضي — فهو الذي يقول معناها،
        //    و«—» وحدها تعدّي عليها العين (وقع ذلك فعلاً: لم تجدها المستخدمة)
        ->assertSee('— وارد وصادر —');
});
