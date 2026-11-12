<?php

use App\Livewire\Finance\CourtBuildings;
use App\Livewire\Finance\MonthEntry;
use App\Livewire\Finance\Unit;
use App\Models\CourtBuildingItem;
use App\Models\FinanceAttachment;
use App\Models\FinanceMonth;
use App\Models\FinanceNote;
use App\Models\FinanceUnit;
use App\Models\User;
use App\Support\Branch;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(FinanceAttachment::DISK);
    // ٥ نوفمبر ٢٠٢٦: أكتوبر انتهى ونوفمبر جارٍ — تاريخٌ في الماضي (الملفات المؤقتة لا تحتمل تقديم الساعة)
    $this->travelTo(CarbonImmutable::parse('2026-11-05 10:00:00', 'UTC'));
});

function unitOf(string $city): FinanceUnit
{
    return FinanceUnit::where('name', 'like', '%'.$city)->sole();
}

/** مستخدمٌ بصلاحيات مربوطٌ بوحدات */
function unitUser(array $abilities, array $cities = []): User
{
    foreach ($abilities as $ability) {
        Permission::findOrCreate($ability, 'web');
    }
    $role = Role::findOrCreate('u-'.uniqid(), 'web');
    $role->givePermissionTo($abilities);

    $user = tap(User::factory()->create())->assignRole($role);
    $user->financeUnits()->sync(collect($cities)->map(fn ($c) => unitOf($c)->id)->all());

    return $user;
}

function entryAs(array $cities = ['بالقاهرة']): User
{
    return unitUser(['finance.units.view', 'finance.units.create', 'finance.court-total.view'], $cities);
}

/** يحفظ الشهر التالي للوحدة بقيمٍ ثمانٍ متساوية (المجموع ٨ × القيمة) */
function enterUnit(FinanceUnit $unit, int $each = 1000, string $order = '4471')
{
    $c = Livewire::test(MonthEntry::class, ['account' => 'court-buildings', 'financeUnit' => $unit]);
    foreach (CourtBuildingItem::active()->ordered()->pluck('id') as $id) {
        $c->set("items.$id", (string) $each);
    }

    return $c->set('orders.0.number', $order)
        ->set('orders.0.issued_on', '2026-11-03')
        ->set('picked.0', [UploadedFile::fake()->image('o.jpg')])
        ->call('save');
}

// ── صفحة الوحدة ──────────────────────────────────────────

it('يفتح المستخدمُ وحدته وحدها ولو كتب رابط غيرها', function () {
    $this->actingAs(unitUser(['finance.units.view'], ['بالقاهرة']));

    $this->get(route('finance.unit', unitOf('بالقاهرة')))->assertOk()->assertSee('الوحدة الحسابية بالقاهرة');
    $this->get(route('finance.unit', unitOf('بطنطا')))->assertForbidden();
});

it('لا يفتح الوحدة بربطٍ بلا صلاحية ولا بصلاحيةٍ بلا ربط', function () {
    $this->actingAs(unitUser(['finance.accounts.view'], ['بالقاهرة']));
    $this->get(route('finance.unit', unitOf('بالقاهرة')))->assertForbidden();

    $this->actingAs(unitUser(['finance.units.view'], []));
    $this->get(route('finance.unit', unitOf('بالقاهرة')))->assertForbidden();
});

it('يفتح السوبر أدمن الوحدات كلها بلا ربط', function () {
    $this->actingAs(tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web')));

    $this->get(route('finance.unit', unitOf('بالإسماعيلية')))->assertOk();
});

// ── الإدخال على الوحدة ───────────────────────────────────

it('يُدخل شهر وحدته ويُرفض على وحدةٍ ليست له', function () {
    $this->actingAs(entryAs(['بالقاهرة']));

    enterUnit(unitOf('بالقاهرة'))->assertHasNoErrors()->assertRedirect(route('finance.unit', ['financeUnit' => unitOf('بالقاهرة'), 'year' => 2026]));

    $month = FinanceMonth::sole();
    expect($month->finance_unit_id)->toBe(unitOf('بالقاهرة')->id)
        ->and($month->amount)->toBe(8000);

    $this->get(route('finance.unit-entry', unitOf('بطنطا')))->assertForbidden();
    Livewire::test(MonthEntry::class, ['account' => 'court-buildings', 'financeUnit' => unitOf('بطنطا')])->assertForbidden();
});

it('لا تُدخل الوحدة المعطَّلة شهراً جديداً', function () {
    $this->actingAs(entryAs(['بطنطا']));
    unitOf('بطنطا')->update(['is_active' => false]);

    $this->get(route('finance.unit-entry', unitOf('بطنطا')))->assertForbidden();
});

it('كل وحدة ماشية بشهورها والشهر نفسه لوحدتين', function () {
    $this->actingAs(entryAs(['بالقاهرة', 'بطنطا']));

    enterUnit(unitOf('بالقاهرة'))->assertHasNoErrors();  // أكتوبر
    enterUnit(unitOf('بالقاهرة'))->assertHasNoErrors();  // نوفمبر
    enterUnit(unitOf('بطنطا'))->assertHasNoErrors();     // أكتوبر طنطا

    $months = FinanceMonth::orderBy('finance_unit_id')->orderBy('month')->get()
        ->map(fn ($m) => $m->unit->shortName().' '.$m->month->format('Y-m'))->all();

    expect($months)->toBe(['القاهرة 2026-10', 'القاهرة 2026-11', 'طنطا 2026-10']);
});

it('يحذف آخر شهر الوحدة نفسها لا آخر شهر الحساب', function () {
    $this->actingAs(entryAs(['بالقاهرة', 'بطنطا']));
    enterUnit(unitOf('بالقاهرة'));
    enterUnit(unitOf('بالقاهرة'));
    enterUnit(unitOf('بطنطا'));

    $this->actingAs(unitUser(['finance.units.view', 'finance.delete'], ['بالقاهرة', 'بطنطا']));
    $tanta = FinanceMonth::where('finance_unit_id', unitOf('بطنطا')->id)->sole();

    // أكتوبر طنطا آخر شهرٍ لطنطا — ولو كان لنوفمبر القاهرة بعده
    Livewire::test(Unit::class, ['financeUnit' => unitOf('بطنطا')])
        ->call('confirmDelete', $tanta->id)
        ->set('deleteReason', 'أُدخل للوحدة الخطأ')
        ->call('deleteRow');

    expect(FinanceMonth::find($tanta->id))->toBeNull()
        ->and(FinanceMonth::count())->toBe(2);

    // وأكتوبر القاهرة ليس آخر شهرٍ للقاهرة
    $octCairo = FinanceMonth::where('finance_unit_id', unitOf('بالقاهرة')->id)->orderBy('month')->first();
    Livewire::test(Unit::class, ['financeUnit' => unitOf('بالقاهرة')])->call('confirmDelete', $octCairo->id)->assertForbidden();
});

it('سجلّ الوحدة وملاحظاتها لا تظهر في وحدةٍ أخرى ولا تُفتح منها', function () {
    $this->actingAs(entryAs(['بالقاهرة', 'بطنطا']));
    enterUnit(unitOf('بالقاهرة'));
    $note = FinanceNote::create([
        'finance_account_id' => FinanceMonth::sole()->finance_account_id,
        'finance_unit_id'    => unitOf('بالقاهرة')->id,
        // أكتوبر: صفّه ظاهرٌ في صفحة طنطا أيضاً (شهرها التالي) — فالتسرّب يُرى لو وقع
        'month'              => '2026-10-01',
        'body'               => 'ملاحظة القاهرة',
        'created_by'         => auth()->id(),
    ]);

    $this->get(route('finance.unit', unitOf('بالقاهرة')))->assertSee('ملاحظة القاهرة');
    $this->get(route('finance.unit', unitOf('بطنطا')))->assertDontSee('ملاحظة القاهرة');

    Livewire::test(Unit::class, ['financeUnit' => unitOf('بطنطا')])->call('startEditNote', $note->id)->assertNotFound();
    Livewire::test(CourtBuildings::class)->call('startEditNote', $note->id)->assertNotFound();
});

it('لا يفتح مستخدم وحدةٍ صورةَ أمر دفع وحدةٍ أخرى، ويفتحها عارض الإجمالي', function () {
    $this->actingAs(entryAs(['بالقاهرة']));
    enterUnit(unitOf('بالقاهرة'));
    $attachment = FinanceAttachment::sole();

    $this->actingAs(unitUser(['finance.units.view'], ['بطنطا']));
    $this->get(route('finance.attachments.show', $attachment))->assertForbidden();

    $this->actingAs(unitUser(['finance.court-total.view']));
    $this->get(route('finance.attachments.show', $attachment))->assertOk();
});

// ── صفحة الإجمالي ────────────────────────────────────────

it('يعرض الشهور × الوحدات والإجمالي والناقص', function () {
    $this->actingAs(entryAs(['بالقاهرة', 'بطنطا']));
    enterUnit(unitOf('بالقاهرة'), 1000);   // ٨٠٠٠
    enterUnit(unitOf('بطنطا'), 500, '9');  // ٤٠٠٠

    Livewire::test(CourtBuildings::class)
        ->assertSee('8,000')->assertSee('4,000')
        ->assertSee('12,000')
        ->assertSee(__('home.fin_units_of', ['entered' => 2, 'count' => 6]))
        ->assertSee(__('home.fin_units_missing', ['names' => 'الإسكندرية · الأقصر · أسيوط · الإسماعيلية']))
        ->assertSee(__('home.fin_month_not_ended'));
});

it('يجمع العناصر للشهر وللسنة', function () {
    $this->actingAs(entryAs(['بالقاهرة', 'بطنطا']));
    enterUnit(unitOf('بالقاهرة'), 1000);
    enterUnit(unitOf('بطنطا'), 500, '9');

    Livewire::test(CourtBuildings::class)
        ->assertSee(__('home.fin_items_of_month', ['month' => 'أكتوبر 2026']))
        ->assertSee(__('home.fin_items_of_year'))
        ->assertSeeInOrder(['مكاتب مميزة', '1,500']);
});

it('رابط الوحدة في رأس الجدول على وحدات المستخدم وحدها', function () {
    $this->actingAs(entryAs(['بالقاهرة']));

    Livewire::test(CourtBuildings::class)
        ->assertSeeHtml('href="'.route('finance.unit', unitOf('بالقاهرة')).'"')
        ->assertDontSeeHtml('href="'.route('finance.unit', unitOf('بطنطا')).'"');
});

it('لا يُدخَل أبنية المحاكم من غير وحدة', function () {
    $this->actingAs(entryAs());

    $this->get(route('finance.entry', 'court-buildings'))->assertNotFound();
});

// ── الفرع والقائمة ───────────────────────────────────────

it('يهبط صاحب الوحدة وحدها على وحدته وقائمته وحدته وحدها', function () {
    $user = unitUser(['finance.units.view', 'finance.units.create'], ['بطنطا']);
    $this->actingAs($user);

    $this->get(Branch::entryUrlFor('finance', $user))->assertRedirect(route('finance.unit', unitOf('بطنطا')));

    $this->get(route('finance.unit', unitOf('بطنطا')))
        ->assertSee('href="'.route('finance.unit', unitOf('بطنطا')).'"', false)
        ->assertDontSee('href="'.route('finance.unit', unitOf('بالقاهرة')).'"', false)
        ->assertDontSee('href="'.route('finance.court-buildings').'"', false)
        ->assertDontSee('href="'.route('finance.mechanization').'"', false);
});
