<?php

use App\Livewire\Finance\CourtBuildings;
use App\Livewire\Finance\MonthEntry;
use App\Models\CourtBuildingItem;
use App\Models\FinanceAttachment;
use App\Models\FinanceMonth;
use App\Models\FinanceMonthItem;
use App\Models\User;
use App\Support\Finance\AmountInWords;
use App\Support\Finance\FinanceHistory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(FinanceAttachment::DISK);
    // تاريخٌ في الماضي — انظر MonthEntryTest (الملفات المؤقتة لا تحتمل تقديم الساعة)
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00:00', 'UTC'));
});

function courtUser(array $abilities = ['finance.view', 'finance.create', 'finance.edit', 'finance.delete']): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }

    $role = Role::findOrCreate('fin-court-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    return tap(User::factory()->create())->assignRole($role);
}

/** القيم الثمانية بترتيب العناصر — مجموعها 1,000 + 2,000 + … + 8,000 = 36,000 */
function courtValues(): array
{
    return CourtBuildingItem::ordered()->pluck('id')
        ->mapWithKeys(fn ($id, $i) => [$id => (string) (($i + 1) * 1000)])
        ->all();
}

function courtEntry(?array $values = null)
{
    $c = Livewire::test(MonthEntry::class, ['account' => 'court-buildings']);
    foreach ($values ?? courtValues() as $id => $value) {
        $c->set("items.$id", $value);
    }

    return $c->set('orders.0.number', '4471')
        ->set('orders.0.issued_on', '2026-11-03')
        ->set('picked.0', [UploadedFile::fake()->image('o.jpg')]);
}

it('يحفظ الشهر بعناصره الثمانية والإجمالي مجموعها', function () {
    $this->actingAs(courtUser());

    courtEntry()
        ->assertSee(AmountInWords::pounds(36000))
        ->call('review')
        ->assertHasNoErrors()
        ->call('save')
        ->assertRedirect();

    $month = FinanceMonth::with('items')->sole();
    expect($month->amount)->toBe(36000)
        ->and($month->items)->toHaveCount(8)
        ->and($month->items->first()->amount)->toBe(1000)
        ->and($month->items->last()->amount)->toBe(8000);
});

it('يطلب كل عنصر ويقبل الصفر', function () {
    $this->actingAs(courtUser());
    $values = courtValues();
    $ids    = array_keys($values);

    $values[$ids[7]] = '';
    $values[$ids[5]] = '0';

    courtEntry($values)->call('review')
        ->assertHasErrors("items.{$ids[7]}")
        ->assertHasNoErrors("items.{$ids[5]}");

    expect(FinanceMonth::count())->toBe(0);
});

it('لا يحفظ عنصراً مدسوساً، ويبقى المحذوف من المتصفح مطلوباً', function () {
    $this->actingAs(courtUser());
    $values = courtValues();
    $ids    = array_keys($values);

    // عنصرٌ ليس في الفورم يُهمَل
    courtEntry()->set('items.99999', '500')->call('save')->assertHasNoErrors();
    expect(FinanceMonthItem::count())->toBe(8)
        ->and(FinanceMonth::sole()->amount)->toBe(36000);

    // عنصرٌ أُسقط مفتاحه من المتصفح يبقى مطلوباً (نوفمبر)
    $c = courtEntry();
    $partial = $values;
    unset($partial[$ids[0]]);
    $c->set('items', $partial)->call('review')->assertHasErrors("items.{$ids[0]}");
});

it('لا يطلب العنصر المعطَّل في شهرٍ جديد، ويبقى في تصحيح شهرٍ سُجِّل فيه', function () {
    $this->actingAs(courtUser());
    courtEntry()->call('save');

    $item = CourtBuildingItem::where('name', 'سيارات جمعة')->first();
    $item->update(['is_active' => false]);

    Livewire::test(MonthEntry::class, ['account' => 'court-buildings'])
        ->assertDontSee('سيارات جمعة');

    Livewire::test(MonthEntry::class, ['account' => 'court-buildings', 'period' => '2026-10'])
        ->assertSee('سيارات جمعة')
        ->assertSet("items.{$item->id}", '6000');
});

it('يصحّح عنصراً فيتبعه الإجمالي، والسجلّ يسمّي العنصر', function () {
    $this->actingAs(courtUser());
    courtEntry()->call('save');
    $item = CourtBuildingItem::where('name', 'مكاتب مميزة')->first();

    Livewire::test(MonthEntry::class, ['account' => 'court-buildings', 'period' => '2026-10'])
        ->set("items.{$item->id}", '700')
        ->set('reason', 'مكاتب مميزة كُتبت بزيادة صفر')
        ->call('save')
        ->assertHasNoErrors();

    $month = FinanceMonth::with('items')->sole();
    expect($month->amount)->toBe(36000 - 7000 + 700)
        ->and($month->items->firstWhere('court_building_item_id', $item->id)->amount)->toBe(700);

    $log = Activity::where('log_name', FinanceHistory::LOG)->sole();
    expect($log->properties['before']['items']['مكاتب مميزة'])->toBe(7000)
        ->and($log->properties['after']['items']['مكاتب مميزة'])->toBe(700);

    $this->get(route('finance.court-buildings'))
        ->assertSeeInOrder(['مكاتب مميزة', '7,000', '700', __('home.fin_total_label'), '36,000', '29,700']);
});

it('يعرض العناصر تحت الشهر في صفحة الحساب', function () {
    $this->actingAs(courtUser());
    courtEntry()->call('save');

    Livewire::test(CourtBuildings::class)
        ->assertSee(__('home.fin_total_label_unit'))
        ->assertSee(__('home.fin_items_toggle'))
        ->assertSee('36,000')
        ->assertSeeInOrder(['قيمة نموذج طلب الشهر ق٩', '1,000', 'فترة مسائية ثانية', '8,000']);
});

it('يحذف عناصر الشهر بحذفه فيصير العنصر قابلاً للحذف', function () {
    $this->actingAs(courtUser());
    courtEntry()->call('save');
    $month = FinanceMonth::sole();
    $item  = CourtBuildingItem::first();

    expect($item->isInUse())->toBeTrue();

    Livewire::test(CourtBuildings::class)
        ->call('confirmDelete', $month->id)
        ->set('deleteReason', 'أُدخل خطأً')
        ->call('deleteRow');

    expect(FinanceMonthItem::count())->toBe(0)
        ->and($item->isInUse())->toBeFalse();
});

it('يحفظ شهراً إجماليه صفر بلا أمر دفع', function () {
    $this->actingAs(courtUser());
    $zeros = array_fill_keys(array_keys(courtValues()), '0');

    $c = Livewire::test(MonthEntry::class, ['account' => 'court-buildings']);
    foreach ($zeros as $id => $v) {
        $c->set("items.$id", $v);
    }
    $c->set('noOrder', true)->call('save')->assertHasNoErrors();

    expect(FinanceMonth::sole()->amount)->toBe(0)
        ->and(FinanceMonth::sole()->paymentOrders)->toHaveCount(0);
});
